<?php
/**
 * NIA-HRIS Database Import Script
 * This script imports the database from the SQL file (database/latestDate).sql
 */

// Set execution time limit for large databases
set_time_limit(300); // 5 minutes

// Start output buffering
ob_start();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Import Database - NIA HRIS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .success-msg { background: #d4edda; border-left: 4px solid #28a745; }
        .error-msg { background: #f8d7da; border-left: 4px solid #dc3545; }
        .info-msg { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1><i class='fas fa-database'></i> Import Database - NIA HRIS</h1>
";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'nia_hris';

// Connect to MySQL (Windows XAMPP - no socket needed)
$conn = @mysqli_connect($host, $username, $password);

if (!$conn) {
    die("<div class='error-msg'><strong>Error:</strong> Connection failed: " . mysqli_connect_error() . "</div></div></body></html>");
}

echo "<div class='success-msg'>✓ Connected to MySQL server</div>";

// Create database if it doesn't exist
$create_db_query = "CREATE DATABASE IF NOT EXISTS `nia_hris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $create_db_query)) {
    echo "<div class='success-msg'>✓ Database 'nia_hris' ready</div>";
} else {
    echo "<div class='error-msg'><strong>Error:</strong> Could not create database: " . mysqli_error($conn) . "</div>";
    mysqli_close($conn);
    exit;
}

// Select the database
mysqli_select_db($conn, $dbname);

// Enable exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Find the latest SQL file in the database directory
$database_dir = __DIR__ . '/database';
$sql_files = glob($database_dir . '/*.sql');

if (empty($sql_files)) {
    die("<div class='error-msg'><strong>Error:</strong> No SQL files found in the database directory.</div></div></body></html>");
}

// Sort by modification time (most recent first)
usort($sql_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$sql_file = $sql_files[0];
$sql_filename = basename($sql_file);

echo "<div class='info-msg'><strong>Info:</strong> Using SQL file: <strong>$sql_filename</strong></div>";
echo "<div class='info-msg'><strong>Info:</strong> File size: " . number_format(filesize($sql_file) / 1024, 2) . " KB</div>";

// Read SQL file
if (!file_exists($sql_file)) {
    die("<div class='error-msg'><strong>Error:</strong> SQL file not found: $sql_file</div></div></body></html>");
}

$sql_content = file_get_contents($sql_file);

if ($sql_content === false) {
    die("<div class='error-msg'><strong>Error:</strong> Could not read SQL file.</div></div></body></html>");
}

echo "<div class='info-msg'><strong>Info:</strong> SQL file loaded successfully</div>";

// Remove BOM if present
$sql_content = preg_replace('/^\xEF\xBB\xBF/', '', $sql_content);

// Split SQL content into individual statements
// Remove comments and split by semicolon
$sql_content = preg_replace('/--.*$/m', '', $sql_content); // Remove inline comments
$sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content); // Remove block comments

// Split by semicolon
$statements = explode(';', $sql_content);

// Filter and clean statements (keep SET statements as they're needed for import)
$statements = array_filter(
    array_map('trim', $statements),
    function($stmt) {
        return !empty($stmt) && strlen($stmt) > 2;
    }
);

echo "<div class='info-msg'><strong>Info:</strong> Found " . count($statements) . " SQL statements to execute</div>";

// Track views to skip INSERT statements for them
$views = [];

// Execute statements
$success_count = 0;
$error_count = 0;
$errors = [];
$tables_created = [];
$rows_inserted = 0;
$skipped_views = 0;

echo "<div style='margin-top: 20px;'><h2>Execution Progress:</h2>";
echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f8f9fa;'>";

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    // Convert CREATE VIEW to CREATE OR REPLACE VIEW to avoid "already exists" errors
    if (preg_match('/^CREATE\s+VIEW\s+/i', $statement) && !preg_match('/CREATE\s+OR\s+REPLACE\s+VIEW/i', $statement)) {
        $statement = preg_replace('/^CREATE\s+VIEW\s+/i', 'CREATE OR REPLACE VIEW ', $statement);
    }
    
    // Track CREATE VIEW statements
    if (preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
        $views[] = $matches[1];
    }
    
    // Skip INSERT statements for views
    if (preg_match('/INSERT\s+INTO\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
        $target_table = $matches[1];
        
        // Check if it's a view (either in our tracked list or in the database)
        $is_view = in_array(strtolower($target_table), array_map('strtolower', $views));
        
        if (!$is_view) {
            // Double-check against database views
            $check_view = mysqli_query($conn, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$target_table'");
            if ($check_view && mysqli_num_rows($check_view) > 0) {
                $is_view = true;
            }
        }
        
        if ($is_view) {
            $skipped_views++;
            if ($skipped_views <= 5) {
                echo "<div class='warning'>⚠ Skipped INSERT into view: $target_table (views are read-only)</div>";
            }
            continue;
        }
    }
    
    // Execute statement with error handling
    try {
        $result = mysqli_query($conn, $statement);
        
        if ($result) {
            $success_count++;
            
            // Track what was created
            if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                $tables_created[] = $matches[1];
                echo "<div class='success'>✓ Table created: {$matches[1]}</div>";
            } elseif (preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                echo "<div class='success'>✓ View created: {$matches[1]}</div>";
            } elseif (preg_match('/DROP\s+TABLE\s+IF\s+EXISTS\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                echo "<div class='info'>⊘ Table dropped (if exists): {$matches[1]}</div>";
            } elseif (preg_match('/DROP\s+VIEW\s+IF\s+EXISTS\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                echo "<div class='info'>⊘ View dropped (if exists): {$matches[1]}</div>";
            } elseif (preg_match('/INSERT\s+INTO/i', $statement)) {
                $rows_inserted++;
                if ($rows_inserted % 100 == 0) {
                    echo "<div class='info'>✓ Inserted $rows_inserted rows...</div>";
                }
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Handle exceptions (like inserting into views, views already existing)
        $error_msg = $e->getMessage();
        $error_code = $e->getCode();
        
        // Handle INSERT into views
        if (stripos($error_msg, 'not insertable-into') !== false || stripos($error_msg, 'is not insertable') !== false || $error_code == 1348) {
            $skipped_views++;
            if ($skipped_views <= 5) {
                if (preg_match('/`(\w+)`/', $error_msg, $matches)) {
                    echo "<div class='warning'>⚠ Skipped INSERT into view: {$matches[1]} (views are read-only)</div>";
                } else {
                    echo "<div class='warning'>⚠ Skipped INSERT into view (views are read-only)</div>";
                }
            }
            continue;
        }
        
        // Handle "already exists" errors for views - try to drop and recreate
        if ((stripos($error_msg, 'already exists') !== false || $error_code == 1050) && preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW/i', $statement)) {
            if (preg_match('/VIEW\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                $view_name = $matches[1];
                // Drop the view first, then retry
                @mysqli_query($conn, "DROP VIEW IF EXISTS `$view_name`");
                // Retry the CREATE OR REPLACE VIEW statement
                try {
                    if (mysqli_query($conn, $statement)) {
                        $success_count++;
                        echo "<div class='success'>✓ View recreated: $view_name</div>";
                        if (!in_array($view_name, $views)) {
                            $views[] = $view_name;
                        }
                        continue;
                    }
                } catch (Exception $retry_e) {
                    // If retry fails, continue to error reporting
                }
            }
        }
        
        // Handle "already exists" errors for tables - skip silently if it's intentional
        if ((stripos($error_msg, 'already exists') !== false || $error_code == 1050) && preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $statement)) {
            // This shouldn't happen with IF NOT EXISTS, but if it does, just skip
            continue;
        }
        
        $error_count++;
        $errors[] = [
            'statement' => substr($statement, 0, 100) . '...',
            'error' => $error_msg,
            'error_code' => $error_code
        ];
        echo "<div class='error'>✗ Exception in statement " . ($index + 1) . ": " . htmlspecialchars($error_msg) . "</div>";
    } catch (Exception $e) {
        // Handle other exceptions
        $error_msg = $e->getMessage();
        
        if (stripos($error_msg, 'not insertable-into') !== false || stripos($error_msg, 'is not insertable') !== false) {
            $skipped_views++;
            if ($skipped_views <= 5) {
                echo "<div class='warning'>⚠ Skipped INSERT into view (views are read-only)</div>";
            }
            continue;
        }
        
        $error_count++;
        $errors[] = [
            'statement' => substr($statement, 0, 100) . '...',
            'error' => $error_msg
        ];
        echo "<div class='error'>✗ Exception in statement " . ($index + 1) . ": " . htmlspecialchars($error_msg) . "</div>";
    }
    
    // Show progress every 50 statements
    if (($index + 1) % 50 == 0) {
        echo "<div class='info'>Progress: " . ($index + 1) . " / " . count($statements) . " statements executed...</div>";
        ob_flush();
        flush();
    }
}

echo "</div></div>";

// Summary
echo "<div style='margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;'>";
echo "<h2>Import Summary:</h2>";
echo "<div class='success-msg'>✓ Successful statements: $success_count</div>";
echo "<div class='info-msg'>✓ Tables created: " . count($tables_created) . "</div>";
echo "<div class='info-msg'>✓ Rows inserted: $rows_inserted</div>";
if ($skipped_views > 0) {
    echo "<div class='warning' style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 5px 0; border-radius: 5px;'>⚠ Skipped INSERT statements for views: $skipped_views (views are read-only in MySQL)</div>";
}

if ($error_count > 0) {
    echo "<div class='error-msg'>✗ Errors: $error_count</div>";
    echo "<details style='margin-top: 10px;'>";
    echo "<summary style='cursor: pointer; font-weight: bold;'>Show Errors</summary>";
    echo "<pre style='max-height: 200px; overflow-y: auto;'>";
    foreach ($errors as $error) {
        echo htmlspecialchars($error['error']) . "\n";
    }
    echo "</pre></details>";
}

if ($error_count == 0) {
    echo "<div class='success-msg' style='margin-top: 15px; font-size: 18px;'>";
    echo "<strong>✓ Database imported successfully!</strong>";
    echo "</div>";
}

echo "</div>";

// Close connection
mysqli_close($conn);

echo "<div style='margin-top: 20px; padding: 15px; background: #d1ecf1; border-radius: 5px;'>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li>Verify the database structure in phpMyAdmin</li>";
echo "<li>Check that all tables have been created</li>";
echo "<li>Test the application to ensure everything works</li>";
echo "</ul>";
echo "</div>";

echo "</div></body></html>";
ob_end_flush();
?>
