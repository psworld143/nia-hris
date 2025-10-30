<?php
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Employment Type (employee_details) Migration</title>";
echo "<style>body{font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:40px auto;padding:24px;background:#f6f8fa}h2{margin:0 0 12px}section{background:#fff;border:1px solid #e1e4e8;border-radius:6px;margin:16px 0;padding:16px}code{background:#f1f1f1;padding:2px 4px;border-radius:4px}ul{margin:8px 0 0 18px} .ok{color:#2e7d32} .err{color:#c62828}</style></head><body>";
echo "<h2>Employment Type Migration for <code>employee_details.employment_type</code></h2>";
echo "<p>This will align the enum with: <strong>Permanent, Casual/Project, Casual Subsidy, Job Order, Contract of Service</strong>.</p>";

// 1) Inspect current column
echo "<section><h3>Step 1: Inspect current column</h3>";
$checkSql = "SHOW COLUMNS FROM employee_details LIKE 'employment_type'";
$checkRes = mysqli_query($conn, $checkSql);
if ($checkRes && $col = mysqli_fetch_assoc($checkRes)) {
    echo "Current definition: <code>" . htmlspecialchars($col['Type']) . "</code><br>";
    echo "Default: <code>" . htmlspecialchars((string)$col['Default']) . "</code>";
} else {
    echo "<span class='err'>Column not found or query failed.</span>";
}
echo "</section>";

// 2) Temporarily convert to VARCHAR to allow remapping
echo "<section><h3>Step 2: Convert to VARCHAR temporarily</h3>";
$toVarChar = "ALTER TABLE employee_details MODIFY COLUMN employment_type VARCHAR(50) DEFAULT 'Permanent'";
if (mysqli_query($conn, $toVarChar)) {
    echo "<span class='ok'>✓ Converted to VARCHAR(50)</span>";
} else {
    echo "<span class='err'>✗ Error: " . htmlspecialchars(mysqli_error($conn)) . "</span>";
}
echo "</section>";

// 3) Remap legacy values to new set
echo "<section><h3>Step 3: Remap existing values</h3>";
$mappings = [
    ['Full-time', 'Permanent'], ['Full Time', 'Permanent'], ['Regular', 'Permanent'],
    ['Part-time', 'Casual/Project'], ['Part Time', 'Casual/Project'], ['Casual', 'Casual/Project'],
    ['Contract', 'Contract of Service'], ['Contractual', 'Contract of Service'],
    ['Temporary', 'Job Order'], ['Probationary', 'Job Order']
];

$totalUpdated = 0;
foreach ($mappings as $pair) {
    [$old, $new] = $pair;
    $stmt = mysqli_prepare($conn, "UPDATE employee_details SET employment_type = ? WHERE employment_type = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $new, $old);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        if ($affected > 0) {
            echo "Updated {$affected} row(s): '<code>" . htmlspecialchars($old) . "</code>' → '<code>" . htmlspecialchars($new) . "</code>'<br>";
            $totalUpdated += $affected;
        }
        mysqli_stmt_close($stmt);
    }
}
// Normalize NULL/empty
mysqli_query($conn, "UPDATE employee_details SET employment_type = 'Permanent' WHERE employment_type IS NULL OR employment_type = ''");
echo "<strong>Total updated: {$totalUpdated} row(s)</strong>";
echo "</section>";

// 4) Apply new ENUM
echo "<section><h3>Step 4: Apply new ENUM</h3>";
$applyEnum = "ALTER TABLE employee_details 
              MODIFY COLUMN employment_type ENUM('Permanent','Casual/Project','Casual Subsidy','Job Order','Contract of Service') 
              NOT NULL DEFAULT 'Permanent'";
if (mysqli_query($conn, $applyEnum)) {
    echo "<span class='ok'>✓ Updated enum to: Permanent, Casual/Project, Casual Subsidy, Job Order, Contract of Service</span>";
} else {
    echo "<span class='err'>✗ Error applying enum: " . htmlspecialchars(mysqli_error($conn)) . "</span>";
}
echo "</section>";

// 5) Show distribution
echo "<section><h3>Step 5: Verify distribution</h3>";
$dist = mysqli_query($conn, "SELECT employment_type, COUNT(*) AS cnt FROM employee_details GROUP BY employment_type ORDER BY employment_type");
if ($dist) {
    echo "<table border='1' cellspacing='0' cellpadding='6'><tr><th>Employment Type</th><th>Count</th></tr>";
    while ($r = mysqli_fetch_assoc($dist)) {
        echo "<tr><td>" . htmlspecialchars($r['employment_type']) . "</td><td>" . (int)$r['cnt'] . "</td></tr>";
    }
    echo "</table>";
}
echo "</section>";

echo "<p class='ok'><strong>Done.</strong> You can now retry editing the employee.</p>";
echo "<p><a href='edit-employee.php'>Back to Edit Employee</a></p>";

echo "</body></html>";

mysqli_close($conn);
?>


