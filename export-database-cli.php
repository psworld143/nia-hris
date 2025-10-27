<?php
/**
 * NIA HRIS Database Export Script (Command Line Version)
 * Usage: php export-database-cli.php [output_file]
 * 
 * Exports the complete database with proper constraint handling
 * to avoid import errors on other PCs
 */

// Database configuration
require_once 'config/database.php';

// Get database name
$database_name = 'nia_hris';

// Get output filename from command line argument or use default
$filename = isset($argv[1]) ? $argv[1] : 'nia_hris_database_' . date('Y-m-d_H-i-s') . '.sql';

echo "Starting database export...\n";
echo "Database: $database_name\n";
echo "Output file: $filename\n\n";

// Open output file
$file = fopen($filename, 'w');
if (!$file) {
    die("Error: Cannot create output file '$filename'\n");
}

// SQL file header
fwrite($file, "-- NIA HRIS Database Export\n");
fwrite($file, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
fwrite($file, "-- Database: " . $database_name . "\n");
fwrite($file, "-- Version: 1.0\n");
fwrite($file, "-- \n");
fwrite($file, "-- This file contains the complete database structure and data\n");
fwrite($file, "-- with proper constraint handling for cross-platform compatibility\n");
fwrite($file, "-- \n\n");

// Disable foreign key checks
fwrite($file, "-- Disable foreign key checks for import\n");
fwrite($file, "SET FOREIGN_KEY_CHECKS = 0;\n");
fwrite($file, "SET UNIQUE_CHECKS = 0;\n");
fwrite($file, "SET AUTOCOMMIT = 0;\n");
fwrite($file, "START TRANSACTION;\n\n");

// Get all tables
$tables_query = "SHOW TABLES FROM `$database_name`";
$tables_result = mysqli_query($conn, $tables_query);

if (!$tables_result) {
    fclose($file);
    die("Error getting tables: " . mysqli_error($conn) . "\n");
}

$tables = [];
while ($row = mysqli_fetch_array($tables_result)) {
    $tables[] = $row[0];
}

echo "Found " . count($tables) . " tables to export:\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}
echo "\n";

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

echo "Exporting tables in dependency order...\n\n";

// Export each table
foreach ($ordered_tables as $table) {
    echo "Exporting table: $table\n";
    
    fwrite($file, "-- Table structure for table `$table`\n");
    fwrite($file, "DROP TABLE IF EXISTS `$table`;\n");
    
    // Get table structure
    $create_query = "SHOW CREATE TABLE `$table`";
    $create_result = mysqli_query($conn, $create_query);
    
    if ($create_result) {
        $create_row = mysqli_fetch_array($create_result);
        $create_sql = $create_row[1];
        
        // Clean up the CREATE TABLE statement
        $create_sql = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create_sql);
        
        fwrite($file, $create_sql . ";\n\n");
    } else {
        fwrite($file, "-- Error getting structure for table `$table`: " . mysqli_error($conn) . "\n\n");
        continue;
    }
    
    // Get table data
    $data_query = "SELECT * FROM `$table`";
    $data_result = mysqli_query($conn, $data_query);
    
    if ($data_result && mysqli_num_rows($data_result) > 0) {
        fwrite($file, "-- Data for table `$table`\n");
        
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
        $total_rows = 0;
        
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
                $total_rows++;
            }
            
            if (!empty($values)) {
                fwrite($file, "INSERT INTO `$table` ($column_list) VALUES\n");
                fwrite($file, implode(",\n", $values) . ";\n\n");
            }
            
            $offset += $batch_size;
        }
        
        echo "  - Exported $total_rows rows\n";
    } else {
        fwrite($file, "-- No data for table `$table`\n\n");
        echo "  - No data\n";
    }
}

// Re-enable foreign key checks
fwrite($file, "-- Re-enable foreign key checks\n");
fwrite($file, "SET FOREIGN_KEY_CHECKS = 1;\n");
fwrite($file, "SET UNIQUE_CHECKS = 1;\n");
fwrite($file, "COMMIT;\n");
fwrite($file, "SET AUTOCOMMIT = 1;\n\n");

fwrite($file, "-- Export completed successfully\n");
fwrite($file, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");

// Close file
fclose($file);

// Close database connection
mysqli_close($conn);

echo "\nDatabase export completed successfully!\n";
echo "Output file: $filename\n";
echo "File size: " . number_format(filesize($filename) / 1024, 2) . " KB\n";
echo "\nTo import this database on another PC:\n";
echo "1. Create a new database: CREATE DATABASE nia_hris;\n";
echo "2. Import the file: mysql -u username -p nia_hris < $filename\n";
echo "3. Or use phpMyAdmin to import the SQL file\n";
?>
