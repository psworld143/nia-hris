<?php
// Clean output buffers
while (ob_get_level()) { ob_end_clean(); }

session_start();
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Fix: Update performance_reviews employee_type enum</h1>";
echo "<p>This will update the employee_type enum to include 'employee' as a valid value.</p><hr>";

try {
    mysqli_begin_transaction($conn);

    // Check current enum values
    echo "<h3>Step 1: Checking current enum values...</h3>";
    $check = "SHOW COLUMNS FROM performance_reviews WHERE Field = 'employee_type'";
    $result = mysqli_query($conn, $check);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<p>Current enum values: <strong>" . htmlspecialchars($row['Type']) . "</strong></p>";
    }

    // Update enum to include 'employee'
    echo "<h3>Step 2: Updating enum to include 'employee'...</h3>";
    $alter = "ALTER TABLE performance_reviews 
              MODIFY COLUMN employee_type 
              ENUM('faculty','staff','admin','employee') 
              NOT NULL DEFAULT 'employee'";
    
    if (mysqli_query($conn, $alter)) {
        echo "<p style='color: green;'>✓ Successfully updated employee_type enum to include 'employee'.</p>";
    } else {
        // Check if it already includes employee
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate') !== false || strpos($error, 'already') !== false) {
            echo "<p style='color: orange;'>Enum may already include 'employee', or there was a duplicate issue. Error: " . htmlspecialchars($error) . "</p>";
        } else {
            throw new Exception('Failed to update enum: ' . $error);
        }
    }

    // Verify the change
    echo "<h3>Step 3: Verifying the change...</h3>";
    $verify = "SHOW COLUMNS FROM performance_reviews WHERE Field = 'employee_type'";
    $verify_result = mysqli_query($conn, $verify);
    if ($verify_result && $row = mysqli_fetch_assoc($verify_result)) {
        echo "<p>Updated enum values: <strong style='color: green;'>" . htmlspecialchars($row['Type']) . "</strong></p>";
    }

    mysqli_commit($conn);
    echo "<hr><p style='color: green; font-weight: bold;'>✓ Migration completed successfully!</p>";
    echo "<p>You can now create performance reviews with employee_type = 'employee'.</p>";
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<p style='color: red;'>✗ Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the error and try again.</p>";
}

?>

