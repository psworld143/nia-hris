<?php
// Clean all output buffers at the very start
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once 'config/database.php';

// Check if this is an AJAX request first
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if ($is_ajax) {
    // Set content type and suppress errors for clean JSON
    header('Content-Type: application/json');
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    die('Unauthorized access. Admin or HR privileges required.');
}

$success = true;
$messages = [];

try {
    if (!$is_ajax) {
        echo "<h2>Setting up Payroll System...</h2>";
        echo "<style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
        </style>";
    }
    
    // Read SQL file
    $sql_file = __DIR__ . '/database/payroll_system.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception('Payroll SQL file not found: ' . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split into statements
    $statements = explode(';', $sql_content);
    
    $created_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        if (mysqli_query($conn, $statement)) {
            $created_count++;
            if (!$is_ajax && stripos($statement, 'CREATE TABLE') !== false) {
                // Extract table name
                preg_match('/CREATE.*TABLE.*`(\w+)`/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "<p class='success'>✓ Created table: {$matches[1]}</p>";
                }
            } else if (!$is_ajax && stripos($statement, 'INSERT INTO') !== false) {
                echo "<p class='success'>✓ Inserted default data</p>";
            } else if (!$is_ajax && stripos($statement, 'CREATE.*VIEW') !== false) {
                preg_match('/VIEW.*`(\w+)`/i', $statement, $matches);
                if (isset($matches[1])) {
                    echo "<p class='success'>✓ Created view: {$matches[1]}</p>";
                }
            }
        } else {
            $error_count++;
            $error_msg = mysqli_error($conn);
            $errors[] = $error_msg;
            
            // Don't fail on duplicate key or view already exists
            if (stripos($error_msg, 'Duplicate') === false && stripos($error_msg, 'already exists') === false) {
                if (!$is_ajax) {
                    echo "<p class='error'>✗ Error: $error_msg</p>";
                }
            }
        }
    }
    
    $result_message = "Setup completed! Created $created_count object(s).";
    if ($error_count > 0) {
        $result_message .= " ($error_count warnings/errors)";
    }
    
    if ($is_ajax) {
        echo json_encode([
            'success' => true,
            'message' => $result_message,
            'created' => $created_count,
            'errors' => $error_count
        ]);
    } else {
        echo "<h3>Setup Complete!</h3>";
        echo "<p class='success'>$result_message</p>";
        echo "<p style='margin-top: 20px;'><a href='payroll-management.php' style='display: inline-block; padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 5px;'>Go to Payroll Management</a></p>";
    }
    
} catch (Exception $e) {
    if ($is_ajax) {
        echo json_encode([
            'success' => false,
            'message' => 'Setup error: ' . $e->getMessage()
        ]);
    } else {
        echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

