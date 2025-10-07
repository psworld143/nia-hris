<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    die('Unauthorized access. Admin or HR privileges required.');
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if ($is_ajax) {
    header('Content-Type: application/json');
}

$messages = [];
$success = true;

if (!$is_ajax) {
    echo "<h2>Migrating Degrees Table from SEAIT Website...</h2>";
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
    </style>";
}

// Connect to seait_website database
$seait_conn = mysqli_connect($host, $username, $password, 'seait_website', 3306, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

if (!$seait_conn) {
    $error_msg = "Failed to connect to seait_website database: " . mysqli_connect_error();
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo "<p class='error'>✗ $error_msg</p>";
    }
    exit();
}

if (!$is_ajax) {
    echo "<p class='success'>✓ Connected to seait_website database</p>";
}

// Check if degrees table exists in seait_website
$check_table = mysqli_query($seait_conn, "SHOW TABLES LIKE 'degrees'");
if (mysqli_num_rows($check_table) == 0) {
    $error_msg = "Degrees table not found in seait_website database";
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo "<p class='error'>✗ $error_msg</p>";
    }
    exit();
}

if (!$is_ajax) {
    echo "<p class='success'>✓ Found degrees table in seait_website</p>";
}

// Get the table structure
$create_table_query = mysqli_query($seait_conn, "SHOW CREATE TABLE degrees");
$create_table_result = mysqli_fetch_assoc($create_table_query);
$create_table_sql = $create_table_result['Create Table'];

if (!$is_ajax) {
    echo "<h3>Table Structure:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($create_table_sql) . "</pre>";
}

// Drop existing degrees table in nia_hris if it exists
$drop_query = "DROP TABLE IF EXISTS degrees";
if (mysqli_query($conn, $drop_query)) {
    if (!$is_ajax) {
        echo "<p class='info'>• Dropped existing degrees table (if any)</p>";
    }
} else {
    $error_msg = "Failed to drop existing table: " . mysqli_error($conn);
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo "<p class='error'>✗ $error_msg</p>";
    }
    exit();
}

// Create the table in nia_hris
if (mysqli_query($conn, $create_table_sql)) {
    if (!$is_ajax) {
        echo "<p class='success'>✓ Created degrees table in nia_hris database</p>";
    }
} else {
    $error_msg = "Failed to create table: " . mysqli_error($conn);
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        echo "<p class='error'>✗ $error_msg</p>";
    }
    exit();
}

// Get all data from seait_website degrees table
$select_query = "SELECT * FROM degrees ORDER BY id";
$select_result = mysqli_query($seait_conn, $select_query);
$total_rows = mysqli_num_rows($select_result);

if (!$is_ajax) {
    echo "<h3>Copying Data...</h3>";
    echo "<p class='info'>Found $total_rows degree(s) to copy</p>";
}

$copied_count = 0;
$failed_count = 0;

while ($row = mysqli_fetch_assoc($select_result)) {
    // Get column names and values
    $columns = array_keys($row);
    $values = array_values($row);
    
    // Build INSERT query
    $column_list = implode(', ', array_map(function($col) {
        return "`$col`";
    }, $columns));
    
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    
    $insert_query = "INSERT INTO degrees ($column_list) VALUES ($placeholders)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    
    if ($insert_stmt) {
        // Create type string (assume all strings for safety, MySQL will convert)
        $types = str_repeat('s', count($values));
        
        // Convert NULL values
        foreach ($values as $key => $value) {
            if ($value === null) {
                $values[$key] = null;
            }
        }
        
        mysqli_stmt_bind_param($insert_stmt, $types, ...$values);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $copied_count++;
            if (!$is_ajax) {
                $degree_name = isset($row['degree_name']) ? $row['degree_name'] : (isset($row['name']) ? $row['name'] : "ID: {$row['id']}");
                echo "<p class='success'>✓ Copied: $degree_name</p>";
            }
        } else {
            $failed_count++;
            if (!$is_ajax) {
                $degree_name = isset($row['degree_name']) ? $row['degree_name'] : (isset($row['name']) ? $row['name'] : "ID: {$row['id']}");
                echo "<p class='error'>✗ Failed to copy: $degree_name - " . mysqli_error($conn) . "</p>";
            }
        }
    } else {
        $failed_count++;
        if (!$is_ajax) {
            echo "<p class='error'>✗ Failed to prepare insert statement</p>";
        }
    }
}

// Close connections
mysqli_close($seait_conn);

// Prepare response
if ($is_ajax) {
    $response_message = "Migration completed! Copied $copied_count out of $total_rows degree(s).";
    if ($failed_count > 0) {
        $response_message .= " ($failed_count failed)";
        $success = false;
    }
    echo json_encode([
        'success' => $success && $failed_count == 0,
        'message' => $response_message,
        'copied' => $copied_count,
        'failed' => $failed_count,
        'total' => $total_rows
    ]);
} else {
    echo "<h3>Migration Complete!</h3>";
    echo "<p class='success'><strong>Successfully copied: $copied_count degree(s)</strong></p>";
    if ($failed_count > 0) {
        echo "<p class='error'><strong>Failed: $failed_count degree(s)</strong></p>";
    }
    echo "<p style='margin-top: 20px;'><a href='manage-degrees.php' style='display: inline-block; padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 5px;'>Go to Manage Degrees</a></p>";
}
?>

