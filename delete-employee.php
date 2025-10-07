<?php
// Error reporting (log only)
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
header('Content-Type: application/json');

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['employee_id']) || empty($input['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

$employee_id = (int)$input['employee_id'];

// Validate employee ID
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit();
}

// Check if employee exists
$check_query = "SELECT id, first_name, last_name, email FROM employees WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $employee_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}

$employee = mysqli_fetch_assoc($check_result);
$employee_name = $employee['first_name'] . ' ' . $employee['last_name'];

// Check for existing related data that would prevent deletion
$related_data = [];

// Check for leave requests
$leave_check = "SELECT COUNT(*) as count FROM employee_leave_requests WHERE employee_id = ?";
$leave_stmt = mysqli_prepare($conn, $leave_check);
mysqli_stmt_bind_param($leave_stmt, "i", $employee_id);
mysqli_stmt_execute($leave_stmt);
$leave_result = mysqli_stmt_get_result($leave_stmt);
$leave_count = mysqli_fetch_assoc($leave_result)['count'];
if ($leave_count > 0) {
    $related_data[] = "$leave_count leave request(s)";
}

// Check for leave balances
$balance_check = "SELECT COUNT(*) as count FROM employee_leave_balances WHERE employee_id = ?";
$balance_stmt = mysqli_prepare($conn, $balance_check);
mysqli_stmt_bind_param($balance_stmt, "i", $employee_id);
mysqli_stmt_execute($balance_stmt);
$balance_result = mysqli_stmt_get_result($balance_stmt);
$balance_count = mysqli_fetch_assoc($balance_result)['count'];
if ($balance_count > 0) {
    $related_data[] = "$balance_count leave balance record(s)";
}

// Check for enhanced leave balances
$enhanced_balance_check = "SELECT COUNT(*) as count FROM enhanced_leave_balances WHERE employee_id = ? AND employee_type = 'employee'";
$enhanced_balance_stmt = mysqli_prepare($conn, $enhanced_balance_check);
mysqli_stmt_bind_param($enhanced_balance_stmt, "i", $employee_id);
mysqli_stmt_execute($enhanced_balance_stmt);
$enhanced_balance_result = mysqli_stmt_get_result($enhanced_balance_stmt);
$enhanced_balance_count = mysqli_fetch_assoc($enhanced_balance_result)['count'];
if ($enhanced_balance_count > 0) {
    $related_data[] = "$enhanced_balance_count enhanced leave balance record(s)";
}

// Check for employee regularization records
$emp_regularization_check = "SELECT COUNT(*) as count FROM employee_regularization WHERE employee_id = ?";
$emp_regularization_stmt = mysqli_prepare($conn, $emp_regularization_check);
if ($emp_regularization_stmt) {
    mysqli_stmt_bind_param($emp_regularization_stmt, "i", $employee_id);
    mysqli_stmt_execute($emp_regularization_stmt);
    $emp_regularization_result = mysqli_stmt_get_result($emp_regularization_stmt);
    $emp_regularization_count = mysqli_fetch_assoc($emp_regularization_result)['count'];
    if ($emp_regularization_count > 0) {
        $related_data[] = "$emp_regularization_count regularization record(s)";
    }
}

// Check for activity logs (if employee is referenced in admin activity logs)
if (mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_logs'")->num_rows > 0) {
    $activity_check = "SELECT COUNT(*) as count FROM admin_activity_logs WHERE admin_id = ?";
    $activity_stmt = mysqli_prepare($conn, $activity_check);
    if ($activity_stmt) {
        mysqli_stmt_bind_param($activity_stmt, "i", $employee_id);
        mysqli_stmt_execute($activity_stmt);
        $activity_result = mysqli_stmt_get_result($activity_stmt);
        $activity_count = mysqli_fetch_assoc($activity_result)['count'];
        if ($activity_count > 0) {
            $related_data[] = "$activity_count activity log(s)";
        }
    }
}

// Check for user account records (if employee has login credentials)
$user_check = "SELECT COUNT(*) as count FROM users WHERE email = ?";
$user_stmt = mysqli_prepare($conn, $user_check);
mysqli_stmt_bind_param($user_stmt, "s", $employee['email']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_count = mysqli_fetch_assoc($user_result)['count'];
if ($user_count > 0) {
    $related_data[] = "$user_count user account(s)";
}

// If there are related records, prevent deletion
if (!empty($related_data)) {
    echo json_encode([
        'success' => false, 
        'message' => "Cannot delete '$employee_name' - employee has existing data that must be removed first.",
        'related_data' => $related_data
    ]);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete from users table first (if exists)
    $delete_user_query = "DELETE FROM users WHERE email = ?";
    $delete_user_stmt = mysqli_prepare($conn, $delete_user_query);
    if ($delete_user_stmt) {
        mysqli_stmt_bind_param($delete_user_stmt, "s", $employee['email']);
        mysqli_stmt_execute($delete_user_stmt);
    }
    
    // Delete from employees table
    $delete_employee_query = "DELETE FROM employees WHERE id = ?";
    $delete_employee_stmt = mysqli_prepare($conn, $delete_employee_query);
    if (!$delete_employee_stmt) {
        throw new Exception('Error preparing employee delete: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_employee_stmt, "i", $employee_id);
    
    if (!mysqli_stmt_execute($delete_employee_stmt)) {
        throw new Exception('Error deleting employee: ' . mysqli_stmt_error($delete_employee_stmt));
    }
    
    // Log the action (optional - don't fail deletion if logging fails)
    try {
        $admin_id = $_SESSION['user_id'];
        
        // Check if the user exists in the users table
        $check_user_query = "SELECT id FROM users WHERE id = ?";
        $check_user_stmt = mysqli_prepare($conn, $check_user_query);
        mysqli_stmt_bind_param($check_user_stmt, "i", $admin_id);
        mysqli_stmt_execute($check_user_stmt);
        $check_user_result = mysqli_stmt_get_result($check_user_stmt);
        
        if (mysqli_num_rows($check_user_result) > 0) {
            // User exists, proceed with logging
            $action = "Deleted employee: $employee_name";
            $log_query = "INSERT INTO admin_activity_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
            $log_stmt = mysqli_prepare($conn, $log_query);
            if ($log_stmt) {
                mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $action);
                mysqli_stmt_execute($log_stmt);
            }
        } else {
            // User doesn't exist, skip logging
            error_log("Activity logging skipped: User ID $admin_id not found in users table");
        }
    } catch (Exception $e) {
        // Log error but don't fail the deletion
        error_log("Activity logging failed: " . $e->getMessage());
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => "Employee '$employee_name' deleted successfully"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode(['success' => false, 'message' => 'Error deleting employee: ' . $e->getMessage()]);
}

// Close statements
if (isset($delete_user_stmt)) mysqli_stmt_close($delete_user_stmt);
if (isset($delete_employee_stmt)) mysqli_stmt_close($delete_employee_stmt);

mysqli_close($conn);
?>
