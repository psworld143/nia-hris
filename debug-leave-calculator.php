<?php
session_start();
require_once 'config/database.php';
require_once 'includes/leave_allowance_calculator.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

echo "<h1>Leave Calculator Debug</h1>";

try {
    // Test database connection
    echo "<p>✓ Database connected to: " . mysqli_get_server_info($conn) . "</p>";
    echo "<p>✓ Current database: ";
    $db_result = mysqli_query($conn, "SELECT DATABASE()");
    $db_row = mysqli_fetch_row($db_result);
    echo $db_row[0] . "</p>";
    
    // Check if enhanced_leave_balances table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'enhanced_leave_balances'");
    if (mysqli_num_rows($table_check) > 0) {
        echo "<p>✓ enhanced_leave_balances table exists</p>";
    } else {
        echo "<p>✗ enhanced_leave_balances table NOT found</p>";
    }
    
    // Test LeaveAllowanceCalculator
    $calculator = new LeaveAllowanceCalculator($conn);
    echo "<p>✓ LeaveAllowanceCalculator instantiated</p>";
    
    // Test with Michael Paul Sebando (faculty id = 9)
    echo "<h2>Testing with Michael Paul Sebando (Faculty ID: 9)</h2>";
    
    // Test the calculateLeaveAllowance method
    $test_result = $calculator->calculateLeaveAllowance(9, 'faculty', 2025, 1);
    
    if ($test_result) {
        echo "<p>✓ calculateLeaveAllowance returned data:</p>";
        echo "<pre>" . print_r($test_result, true) . "</pre>";
        
        // Test the updateLeaveBalance method
        echo "<h3>Testing updateLeaveBalance...</h3>";
        $update_result = $calculator->updateLeaveBalance(9, 'faculty', 2025, 1);
        
        if ($update_result) {
            echo "<p style='color: green;'>✓ updateLeaveBalance succeeded</p>";
        } else {
            echo "<p style='color: red;'>✗ updateLeaveBalance failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ calculateLeaveAllowance returned false/null</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>Fatal Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
