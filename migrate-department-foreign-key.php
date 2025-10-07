<?php
/**
 * Database Migration: Add department_id Foreign Key to Employees Table
 * This script migrates from varchar department to proper foreign key relationship
 */

require_once 'config/database.php';

echo "<h1>Database Migration: Department Foreign Key</h1>";
echo "<p>Adding proper foreign key relationship between employees and departments...</p><hr>";

$errors = [];
$success = [];

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Step 1: Check if department_id column already exists
    echo "<h3>Step 1: Checking for existing department_id column...</h3>";
    $check_column = "SHOW COLUMNS FROM employees LIKE 'department_id'";
    $result = mysqli_query($conn, $check_column);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: orange;'>⚠️ department_id column already exists. Skipping column creation.</p>";
    } else {
        // Step 2: Add department_id column
        echo "<h3>Step 2: Adding department_id column...</h3>";
        $add_column = "ALTER TABLE employees ADD COLUMN department_id INT(11) NULL AFTER department";
        if (mysqli_query($conn, $add_column)) {
            echo "<p style='color: green;'>✅ Successfully added department_id column</p>";
            $success[] = "Added department_id column";
        } else {
            throw new Exception("Failed to add department_id column: " . mysqli_error($conn));
        }
    }
    
    // Step 3: Migrate existing department data
    echo "<h3>Step 3: Migrating existing department data...</h3>";
    $migrate_query = "
        UPDATE employees e
        INNER JOIN departments d ON e.department = d.name
        SET e.department_id = d.id
        WHERE e.department IS NOT NULL AND e.department != ''
    ";
    if (mysqli_query($conn, $migrate_query)) {
        $affected = mysqli_affected_rows($conn);
        echo "<p style='color: green;'>✅ Migrated {$affected} employee records to use department_id</p>";
        $success[] = "Migrated {$affected} employee records";
    } else {
        echo "<p style='color: orange;'>⚠️ No existing data to migrate or migration failed: " . mysqli_error($conn) . "</p>";
    }
    
    // Step 4: Check for foreign key constraint
    echo "<h3>Step 4: Checking for existing foreign key constraint...</h3>";
    $check_fk = "
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = 'nia_hris' 
        AND TABLE_NAME = 'employees' 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        AND CONSTRAINT_NAME LIKE '%department%'
    ";
    $fk_result = mysqli_query($conn, $check_fk);
    
    if (mysqli_num_rows($fk_result) > 0) {
        echo "<p style='color: orange;'>⚠️ Foreign key constraint already exists. Skipping constraint creation.</p>";
    } else {
        // Step 5: Add foreign key constraint
        echo "<h3>Step 5: Adding foreign key constraint...</h3>";
        $add_fk = "
            ALTER TABLE employees 
            ADD CONSTRAINT fk_employees_department 
            FOREIGN KEY (department_id) 
            REFERENCES departments(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
        ";
        if (mysqli_query($conn, $add_fk)) {
            echo "<p style='color: green;'>✅ Successfully added foreign key constraint</p>";
            $success[] = "Added foreign key constraint";
        } else {
            throw new Exception("Failed to add foreign key constraint: " . mysqli_error($conn));
        }
    }
    
    // Step 6: Add index for performance
    echo "<h3>Step 6: Adding index on department_id...</h3>";
    $check_index = "SHOW INDEX FROM employees WHERE Key_name = 'idx_department_id'";
    $index_result = mysqli_query($conn, $check_index);
    
    if (mysqli_num_rows($index_result) > 0) {
        echo "<p style='color: orange;'>⚠️ Index already exists. Skipping index creation.</p>";
    } else {
        $add_index = "CREATE INDEX idx_department_id ON employees(department_id)";
        if (mysqli_query($conn, $add_index)) {
            echo "<p style='color: green;'>✅ Successfully added index on department_id</p>";
            $success[] = "Added performance index";
        } else {
            echo "<p style='color: orange;'>⚠️ Failed to add index (non-critical): " . mysqli_error($conn) . "</p>";
        }
    }
    
    // Step 7: Keep old department column for now (can be dropped later after verification)
    echo "<h3>Step 7: Preserving old department column...</h3>";
    echo "<p style='color: blue;'>ℹ️ The old 'department' column is preserved for backup. You can drop it later after verifying the migration.</p>";
    echo "<p style='color: blue;'>ℹ️ To drop it later, run: <code>ALTER TABLE employees DROP COLUMN department;</code></p>";
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo "<hr><h2 style='color: green;'>✅ Migration Completed Successfully!</h2>";
    echo "<h3>Summary:</h3><ul>";
    foreach ($success as $item) {
        echo "<li>✅ {$item}</li>";
    }
    echo "</ul>";
    
    // Show current table structure
    echo "<hr><h3>Current Employees Table Structure:</h3>";
    $show_columns = "SHOW COLUMNS FROM employees";
    $columns_result = mysqli_query($conn, $show_columns);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($col = mysqli_fetch_assoc($columns_result)) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show foreign keys
    echo "<hr><h3>Foreign Key Constraints:</h3>";
    $show_fk = "
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'nia_hris'
        AND TABLE_NAME = 'employees'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ";
    $fk_list = mysqli_query($conn, $show_fk);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Constraint Name</th><th>Column</th><th>References Table</th><th>References Column</th></tr>";
    while ($fk = mysqli_fetch_assoc($fk_list)) {
        echo "<tr>";
        echo "<td>{$fk['CONSTRAINT_NAME']}</td>";
        echo "<td>{$fk['COLUMN_NAME']}</td>";
        echo "<td>{$fk['REFERENCED_TABLE_NAME']}</td>";
        echo "<td>{$fk['REFERENCED_COLUMN_NAME']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo "<hr><h2 style='color: red;'>❌ Migration Failed!</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>The database has been rolled back to its previous state.</p>";
}

mysqli_close($conn);

echo "<hr><p><a href='index.php'>← Back to Dashboard</a></p>";
?>

