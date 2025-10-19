<?php
/**
 * Quick Migration: Update Employment Type Enum
 * Updates the employment_type enum to support the new 5 government employment types
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Employment Type Migration</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    .step { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
    .success { color: #4CAF50; } .error { color: #f44336; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th { background: #4CAF50; color: white; padding: 10px; text-align: left; }
    td { padding: 8px; border-bottom: 1px solid #ddd; }
    tr:hover { background: #f5f5f5; }
    .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px 0 0; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    .btn:hover { background: #45a049; }
</style></head><body><div class='container'>";

echo "<h2>🔄 Employment Type Enum Migration</h2>";
echo "<p>This script will update the employment_type enum to support the new government employment classifications.</p><hr>";

// Step 1: Check current values
echo "<div class='step'><h3>Step 1: Checking Current Enum</h3>";
$check = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE 'employment_type'");
if ($check && $row = mysqli_fetch_assoc($check)) {
    echo "<strong>Current:</strong> <code>" . htmlspecialchars($row['Type']) . "</code><br>";
} else {
    echo "<span class='error'>⚠️ Column not found</span><br>";
}
echo "</div>";

// Step 2: Convert to VARCHAR temporarily
echo "<div class='step'><h3>Step 2: Converting to VARCHAR (temporary)</h3>";
$convert = "ALTER TABLE employees MODIFY COLUMN employment_type VARCHAR(50) DEFAULT 'Permanent'";
if (mysqli_query($conn, $convert)) {
    echo "<span class='success'>✓ Converted to VARCHAR</span><br>";
} else {
    echo "<span class='error'>✗ Error: " . mysqli_error($conn) . "</span><br>";
}
echo "</div>";

// Step 3: Update existing values
echo "<div class='step'><h3>Step 3: Updating Existing Records</h3>";
$updates = [
    ['Full-time', 'Permanent'], ['Full Time', 'Permanent'], ['Regular', 'Permanent'],
    ['Part-time', 'Casual/Project'], ['Part Time', 'Casual/Project'], ['Casual', 'Casual/Project'],
    ['Contract', 'Contract of Service'], ['Contractual', 'Contract of Service'],
    ['Temporary', 'Job Order'], ['Probationary', 'Job Order']
];

$total = 0;
foreach ($updates as list($old, $new)) {
    $stmt = mysqli_prepare($conn, "UPDATE employees SET employment_type = ? WHERE employment_type = ?");
    mysqli_stmt_bind_param($stmt, "ss", $new, $old);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    if ($affected > 0) {
        echo "✓ Updated {$affected} record(s): '{$old}' → '{$new}'<br>";
        $total += $affected;
    }
}
echo "<strong>Total updated: {$total} record(s)</strong><br>";

// Set NULL/empty to default
mysqli_query($conn, "UPDATE employees SET employment_type = 'Permanent' WHERE employment_type IS NULL OR employment_type = ''");
echo "</div>";

// Step 4: Apply new ENUM
echo "<div class='step'><h3>Step 4: Applying New ENUM</h3>";
$new_enum = "ALTER TABLE employees 
             MODIFY COLUMN employment_type ENUM('Permanent', 'Casual/Project', 'Casual Subsidy', 'Job Order', 'Contract of Service') 
             NOT NULL DEFAULT 'Permanent'";
             
if (mysqli_query($conn, $new_enum)) {
    echo "<span class='success'>✓ Successfully updated enum to:</span><br>";
    echo "<ul><li>Permanent</li><li>Casual/Project</li><li>Casual Subsidy</li><li>Job Order</li><li>Contract of Service</li></ul>";
} else {
    echo "<span class='error'>✗ Error: " . mysqli_error($conn) . "</span><br>";
}
echo "</div>";

// Step 5: Verify
echo "<div class='step'><h3>Step 5: Verification</h3>";
$verify = mysqli_query($conn, "SHOW COLUMNS FROM employees LIKE 'employment_type'");
if ($verify && $row = mysqli_fetch_assoc($verify)) {
    echo "<strong>New:</strong> <code>" . htmlspecialchars($row['Type']) . "</code><br>";
}

// Show distribution
echo "<h4>Current Distribution:</h4>";
$dist = mysqli_query($conn, "SELECT employment_type, COUNT(*) as count FROM employees GROUP BY employment_type ORDER BY count DESC");
if ($dist) {
    echo "<table><tr><th>Employment Type</th><th>Count</th></tr>";
    while ($row = mysqli_fetch_assoc($dist)) {
        echo "<tr><td>" . htmlspecialchars($row['employment_type']) . "</td><td>" . $row['count'] . "</td></tr>";
    }
    echo "</table>";
}
echo "</div>";

echo "<hr><h3 class='success'>✓ Migration Completed Successfully!</h3>";
echo "<a href='add-employee-comprehensive-form.php' class='btn'>Add Employee Form</a>";
echo "<a href='edit-employee.php' class='btn' style='background: #2196F3;'>Edit Employee</a>";
echo "<a href='admin-employee.php' class='btn' style='background: #FF9800;'>Employee List</a>";

echo "</div></body></html>";
mysqli_close($conn);
?>

