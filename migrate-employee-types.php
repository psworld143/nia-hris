<?php
/**
 * Migration Script: Update Employee Type Enum
 * This script updates the employee_type enum in the employees table
 * to support: Staff, Admin, Nurse
 */

require_once 'config/database.php';

echo "<h2>Employee Type Enum Migration</h2>";
echo "<p>Starting migration to update employee_type enum values...</p><hr>";

// Step 1: Check current enum values
echo "<h3>Step 1: Checking current enum values</h3>";
$check_query = "SHOW COLUMNS FROM employees LIKE 'employee_type'";
$result = mysqli_query($conn, $check_query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Current enum definition: <code>" . htmlspecialchars($row['Type']) . "</code><br>";
    echo "Current default: <code>" . htmlspecialchars($row['Default']) . "</code><br><br>";
}

// Step 2: Update existing values to new format (if needed)
echo "<h3>Step 2: Updating existing employee records</h3>";

// Map old values to new values
$updates = [
    ['old' => 'faculty', 'new' => 'Nurse'],
    ['old' => 'staff', 'new' => 'Staff'],
    ['old' => 'admin', 'new' => 'Admin']
];

// First, temporarily allow NULL or add a temporary enum value
$temp_query = "ALTER TABLE employees MODIFY COLUMN employee_type VARCHAR(50) DEFAULT 'Staff'";
if (mysqli_query($conn, $temp_query)) {
    echo "✓ Temporarily converted employee_type to VARCHAR<br>";
} else {
    echo "✗ Error converting to VARCHAR: " . mysqli_error($conn) . "<br>";
}

// Update the values
foreach ($updates as $update) {
    $update_query = "UPDATE employees SET employee_type = ? WHERE employee_type = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $update['new'], $update['old']);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        echo "✓ Updated {$affected} record(s) from '{$update['old']}' to '{$update['new']}'<br>";
        mysqli_stmt_close($stmt);
    }
}

echo "<br>";

// Step 3: Alter the enum to new values
echo "<h3>Step 3: Updating enum definition</h3>";
$alter_query = "ALTER TABLE employees 
                MODIFY COLUMN employee_type ENUM('Staff', 'Admin', 'Nurse') 
                NOT NULL DEFAULT 'Staff'";

if (mysqli_query($conn, $alter_query)) {
    echo "✓ Successfully updated employee_type enum to ('Staff', 'Admin', 'Nurse')<br><br>";
} else {
    echo "✗ Error updating enum: " . mysqli_error($conn) . "<br><br>";
}

// Step 4: Verify the change
echo "<h3>Step 4: Verifying changes</h3>";
$verify_query = "SHOW COLUMNS FROM employees LIKE 'employee_type'";
$result = mysqli_query($conn, $verify_query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "New enum definition: <code>" . htmlspecialchars($row['Type']) . "</code><br>";
    echo "New default: <code>" . htmlspecialchars($row['Default']) . "</code><br><br>";
}

// Step 5: Show current employee type distribution
echo "<h3>Step 5: Current employee type distribution</h3>";
$count_query = "SELECT employee_type, COUNT(*) as count FROM employees GROUP BY employee_type";
$result = mysqli_query($conn, $count_query);
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Employee Type</th><th>Count</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>" . htmlspecialchars($row['employee_type']) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table><br>";
}

echo "<hr>";
echo "<h3 style='color: green;'>✓ Migration completed successfully!</h3>";
echo "<p><a href='add-employee-comprehensive-form.php'>Go to Add Employee Form</a> | ";
echo "<a href='admin-employee.php'>Go to Employee List</a></p>";

mysqli_close($conn);
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
        border-bottom: 3px solid #4CAF50;
        padding-bottom: 10px;
    }
    h3 {
        color: #555;
        margin-top: 20px;
        background: #fff;
        padding: 10px;
        border-left: 4px solid #2196F3;
    }
    code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        color: #d63384;
    }
    table {
        background: white;
        border-collapse: collapse;
        width: 100%;
        margin: 10px 0;
    }
    th {
        background: #4CAF50;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
    tr:nth-child(even) {
        background: #f9f9f9;
    }
</style>

