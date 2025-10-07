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

$messages = [];
$success = true;
$migrated_tables = 0;
$migrated_records = 0;
$failed_tables = 0;

// Wrap everything in try-catch for AJAX mode
try {
    if (!$is_ajax) {
        echo "<h2>Migrating Training System Tables from SEAIT Website...</h2>";
        echo "<style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            .warning { color: orange; }
        </style>";
    }

    // Connect to seait_website database
    $seait_conn = @mysqli_connect($host, $username, $password, 'seait_website', 3306, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');

    if (!$seait_conn) {
        throw new Exception("Failed to connect to seait_website database: " . mysqli_connect_error());
    }

    if (!$is_ajax) {
        echo "<p class='success'>✓ Connected to seait_website database</p>";
    }

    // List of training-related tables to migrate (ORDER MATTERS - dependencies first!)
    $tables = [
        'training_categories',              // Must be first (referenced by trainings_seminars)
        'main_evaluation_categories',       // Must be second (referenced by trainings_seminars and evaluation_sub_categories)
        'evaluation_sub_categories',        // Must be third (references main_evaluation_categories, referenced by trainings_seminars)
        'trainings_seminars',              // Depends on above three tables
        'training_registrations',           // Depends on trainings_seminars
        'training_suggestions',             // Independent
        'training_materials',               // Depends on trainings_seminars
        'employee_training'                 // Depends on trainings_seminars
    ];
    
    // Disable foreign key checks for the entire migration process
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    if (!$is_ajax) {
        echo "<p class='info'>• Disabled foreign key checks for migration</p>";
    }
    
    // Drop old training_programs table if exists
    mysqli_query($conn, "DROP TABLE IF EXISTS training_programs");
    
    // Drop existing tables in reverse order
    foreach (array_reverse($tables) as $table) {
        mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
    }
    
    if (!$is_ajax) {
        echo "<p class='success'>✓ Cleaned existing tables</p>";
    }

    foreach ($tables as $table) {
        if (!$is_ajax) {
            echo "<h3>Processing table: $table</h3>";
        }
        
        // Check if table exists in source
        $check_table = mysqli_query($seait_conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($check_table) == 0) {
            if (!$is_ajax) {
                echo "<p class='warning'>⊗ Table $table not found in seait_website, skipping...</p>";
            }
            continue;
        }
        
        // Get table structure
        $create_table_query = mysqli_query($seait_conn, "SHOW CREATE TABLE `$table`");
        $create_table_result = mysqli_fetch_assoc($create_table_query);
        $create_table_sql = $create_table_result['Create Table'];
        
        // Create table in nia_hris
        if (!mysqli_query($conn, $create_table_sql)) {
            $error_msg = "Failed to create table $table: " . mysqli_error($conn);
            if (!$is_ajax) {
                echo "<p class='error'>✗ $error_msg</p>";
            }
            $failed_tables++;
            $success = false;
            continue;
        }
        
        if (!$is_ajax) {
            echo "<p class='success'>✓ Created table structure for $table</p>";
        }
        
        // Get all data from seait_website table
        $select_query = "SELECT * FROM `$table`";
        $select_result = mysqli_query($seait_conn, $select_query);
        $total_rows = mysqli_num_rows($select_result);
        
        if ($total_rows == 0) {
            if (!$is_ajax) {
                echo "<p class='info'>• No data to copy for $table</p>";
            }
            $migrated_tables++;
            continue;
        }
        
        if (!$is_ajax) {
            echo "<p class='info'>Found $total_rows record(s) to copy</p>";
        }
        
        $copied_count = 0;
        $table_failed = 0;
        
        while ($row = mysqli_fetch_assoc($select_result)) {
            // Get column names and values
            $columns = array_keys($row);
            $values = array_values($row);
            
            // Build INSERT query
            $column_list = implode(', ', array_map(function($col) {
                return "`$col`";
            }, $columns));
            
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            
            $insert_query = "INSERT INTO `$table` ($column_list) VALUES ($placeholders)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            
            if ($insert_stmt) {
                // Create type string (assume all strings for safety)
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
                    $migrated_records++;
                } else {
                    $table_failed++;
                }
            } else {
                $table_failed++;
            }
        }
        
        if (!$is_ajax) {
            echo "<p class='success'>✓ Copied $copied_count record(s) for $table</p>";
            if ($table_failed > 0) {
                echo "<p class='error'>✗ Failed to copy $table_failed record(s)</p>";
            }
        }
        
        $migrated_tables++;
    }

    // Re-enable foreign key checks after all migrations
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    if (!$is_ajax) {
        echo "<p class='success'>✓ Re-enabled foreign key checks</p>";
    }

    // Close connections
    mysqli_close($seait_conn);

} catch (Exception $e) {
    if ($is_ajax) {
        echo json_encode([
            'success' => false,
            'message' => 'Migration error: ' . $e->getMessage(),
            'tables_migrated' => $migrated_tables,
            'records_migrated' => $migrated_records,
            'tables_failed' => $failed_tables
        ]);
        exit();
    } else {
        echo "<p class='error'><strong>Migration Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        exit();
    }
}

// Prepare response
if ($is_ajax) {
    $response_message = "Migration completed! Migrated $migrated_tables table(s) with $migrated_records total record(s).";
    if ($failed_tables > 0) {
        $response_message .= " ($failed_tables table(s) failed)";
        $success = false;
    }
    
    echo json_encode([
        'success' => $success && $failed_tables == 0,
        'message' => $response_message,
        'tables_migrated' => $migrated_tables,
        'records_migrated' => $migrated_records,
        'tables_failed' => $failed_tables
    ]);
    exit();
} else {
    echo "<h3>Migration Complete!</h3>";
    echo "<p class='success'><strong>Successfully migrated: $migrated_tables table(s)</strong></p>";
    echo "<p class='success'><strong>Total records copied: $migrated_records</strong></p>";
    if ($failed_tables > 0) {
        echo "<p class='error'><strong>Failed tables: $failed_tables</strong></p>";
    }
    echo "<p style='margin-top: 20px;'><a href='training-programs.php' style='display: inline-block; padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 5px;'>Go to Training Programs</a></p>";
}
?>

