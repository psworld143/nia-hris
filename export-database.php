<?php
/**
 * NIA HRIS Database Export Script
 * Exports the complete database with proper constraint handling
 * to avoid import errors on other PCs
 */

// Set execution time limit for large databases
set_time_limit(300); // 5 minutes

// Database configuration
require_once 'config/database.php';

// Check if user is Super Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die('Unauthorized access. Super Admin privileges required.');
}

// Get database name
$database_name = 'nia_hris';

// Set headers for file download
$filename = 'nia_hris_database_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Start output buffering
ob_start();

// SQL file header
echo "-- NIA HRIS Database Export\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
echo "-- Database: " . $database_name . "\n";
echo "-- Version: 1.0\n";
echo "-- \n";
echo "-- This file contains the complete database structure and data\n";
echo "-- with proper constraint handling for cross-platform compatibility\n";
echo "-- \n\n";

// Disable foreign key checks
echo "-- Disable foreign key checks for import\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n";
echo "SET UNIQUE_CHECKS = 0;\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n\n";

// Get all tables
$tables_query = "SHOW TABLES FROM `$database_name`";
$tables_result = mysqli_query($conn, $tables_query);

if (!$tables_result) {
    die("Error getting tables: " . mysqli_error($conn));
}

$tables = [];
while ($row = mysqli_fetch_array($tables_result)) {
    $tables[] = $row[0];
}

// Define table order to handle dependencies (parent tables first)
$table_order = [
    // Core system tables (no dependencies)
    'users',
    'departments',
    'degrees',
    'leave_types',
    'salary_structures',
    'benefit_types',
    'deduction_types',
    'payroll_periods',
    
    // Employee related tables
    'employees',
    'employee_details',
    'employee_leave_allowances',
    'employee_leave_requests',
    'salary_increments',
    'performance_reviews',
    'regularization_criteria',
    'training_programs',
    
    // Payroll related tables
    'payroll_records',
    'payroll_deductions',
    'payroll_benefits',
    'dtr_cards',
    'dtr_verifications',
    
    // Other tables (add any missing tables)
];

// Add any tables not in the predefined order
$ordered_tables = [];
foreach ($table_order as $table) {
    if (in_array($table, $tables)) {
        $ordered_tables[] = $table;
    }
}

// Add remaining tables
foreach ($tables as $table) {
    if (!in_array($table, $ordered_tables)) {
        $ordered_tables[] = $table;
    }
}

// Export each table
foreach ($ordered_tables as $table) {
    echo "-- Table structure for table `$table`\n";
    echo "DROP TABLE IF EXISTS `$table`;\n";
    
    // Get table structure
    $create_query = "SHOW CREATE TABLE `$table`";
    $create_result = mysqli_query($conn, $create_query);
    
    if ($create_result) {
        $create_row = mysqli_fetch_array($create_result);
        $create_sql = $create_row[1];
        
        // Clean up the CREATE TABLE statement
        $create_sql = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create_sql);
        $create_sql = str_replace('AUTO_INCREMENT=', 'AUTO_INCREMENT=', $create_sql);
        
        echo $create_sql . ";\n\n";
    } else {
        echo "-- Error getting structure for table `$table`: " . mysqli_error($conn) . "\n\n";
        continue;
    }
    
    // Get table data
    $data_query = "SELECT * FROM `$table`";
    $data_result = mysqli_query($conn, $data_query);
    
    if ($data_result && mysqli_num_rows($data_result) > 0) {
        echo "-- Data for table `$table`\n";
        
        // Get column information
        $columns_query = "SHOW COLUMNS FROM `$table`";
        $columns_result = mysqli_query($conn, $columns_query);
        $columns = [];
        while ($column = mysqli_fetch_assoc($columns_result)) {
            $columns[] = $column['Field'];
        }
        
        $column_list = '`' . implode('`, `', $columns) . '`';
        
        // Export data in batches
        $batch_size = 1000;
        $offset = 0;
        
        while (true) {
            $batch_query = "SELECT * FROM `$table` LIMIT $batch_size OFFSET $offset";
            $batch_result = mysqli_query($conn, $batch_query);
            
            if (!$batch_result || mysqli_num_rows($batch_result) == 0) {
                break;
            }
            
            $values = [];
            while ($row = mysqli_fetch_assoc($batch_result)) {
                $row_values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    if (is_null($value)) {
                        $row_values[] = 'NULL';
                    } else {
                        // Properly escape the value
                        $escaped_value = mysqli_real_escape_string($conn, $value);
                        $row_values[] = "'$escaped_value'";
                    }
                }
                $values[] = '(' . implode(', ', $row_values) . ')';
            }
            
            if (!empty($values)) {
                echo "INSERT INTO `$table` ($column_list) VALUES\n";
                echo implode(",\n", $values) . ";\n\n";
            }
            
            $offset += $batch_size;
        }
    } else {
        echo "-- No data for table `$table`\n\n";
    }
}

// Re-enable foreign key checks
echo "-- Re-enable foreign key checks\n";
echo "SET FOREIGN_KEY_CHECKS = 1;\n";
echo "SET UNIQUE_CHECKS = 1;\n";
echo "COMMIT;\n";
echo "SET AUTOCOMMIT = 1;\n\n";

echo "-- Export completed successfully\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";

// Flush output
ob_end_flush();

// Close database connection
mysqli_close($conn);
?>
