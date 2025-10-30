<?php
// Start output buffering to catch any accidental output
ob_start();

// Error reporting - log errors but don't display them (would corrupt JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors - they corrupt JSON responses
ini_set('log_errors', 1);

try {
    // Check if session is already started to avoid warnings
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    require_once 'includes/roles.php';
    require_once 'includes/id_encryption.php';
    
    // Check database connection
    if (!isset($conn) || !$conn) {
        send_json_error('Database connection failed. Please try again later.', 500);
    }

// Check if user is logged in and can delete employees
if (!isset($_SESSION['user_id']) || !canDeleteEmployees()) {
    send_json_error('Unauthorized access', 403);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Invalid request method', 405);
}

// Get JSON input
$raw_input = file_get_contents('php://input');
if ($raw_input === false) {
    send_json_error('Failed to read request data', 400);
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_error('Invalid JSON format in request: ' . json_last_error_msg(), 400);
}

if (!isset($input['employee_id']) || empty($input['employee_id'])) {
    send_json_error('Employee ID is required', 400);
}

$employee_id = (int)$input['employee_id'];

// Validate employee ID
if ($employee_id <= 0) {
    send_json_error('Invalid employee ID', 400);
}

// Check if employee exists
$check_query = "SELECT id, first_name, last_name, email FROM employees WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
if (!$check_stmt) {
    send_json_error('Database error: ' . mysqli_error($conn), 500);
}

mysqli_stmt_bind_param($check_stmt, "i", $employee_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (!$check_result) {
    mysqli_stmt_close($check_stmt);
    send_json_error('Database error: ' . mysqli_error($conn), 500);
}

if (mysqli_num_rows($check_result) === 0) {
    mysqli_stmt_close($check_stmt);
    send_json_error('Employee not found', 404);
}

$employee = mysqli_fetch_assoc($check_result);
$employee_name = $employee['first_name'] . ' ' . $employee['last_name'];

// Check for existing related data that would prevent deletion
$related_data = [];

// Check for leave requests (only if table exists)
$table_check_requests = mysqli_query($conn, "SHOW TABLES LIKE 'employee_leave_requests'");
if ($table_check_requests && mysqli_num_rows($table_check_requests) > 0) {
    mysqli_free_result($table_check_requests);
    $leave_check = "SELECT COUNT(*) as count FROM employee_leave_requests WHERE employee_id = ?";
    $leave_stmt = mysqli_prepare($conn, $leave_check);
    if ($leave_stmt) {
        mysqli_stmt_bind_param($leave_stmt, "i", $employee_id);
        mysqli_stmt_execute($leave_stmt);
        $leave_result = mysqli_stmt_get_result($leave_stmt);
        $leave_count = mysqli_fetch_assoc($leave_result)['count'];
        if ($leave_count > 0) {
            $related_data[] = "$leave_count leave request(s)";
        }
        mysqli_stmt_close($leave_stmt);
    }
} else {
    if ($table_check_requests) {
        mysqli_free_result($table_check_requests);
    }
}

// Check for leave balances (only if table exists)
$table_check_balances = mysqli_query($conn, "SHOW TABLES LIKE 'employee_leave_balances'");
if ($table_check_balances && mysqli_num_rows($table_check_balances) > 0) {
    mysqli_free_result($table_check_balances);
    $balance_check = "SELECT COUNT(*) as count FROM employee_leave_balances WHERE employee_id = ?";
    $balance_stmt = mysqli_prepare($conn, $balance_check);
    if ($balance_stmt) {
        mysqli_stmt_bind_param($balance_stmt, "i", $employee_id);
        mysqli_stmt_execute($balance_stmt);
        $balance_result = mysqli_stmt_get_result($balance_stmt);
        $balance_count = mysqli_fetch_assoc($balance_result)['count'];
        if ($balance_count > 0) {
            $related_data[] = "$balance_count leave balance record(s)";
        }
        mysqli_stmt_close($balance_stmt);
    }
} else {
    if ($table_check_balances) {
        mysqli_free_result($table_check_balances);
    }
}

// Check for enhanced leave balances (only if table exists)
$table_check_enhanced = mysqli_query($conn, "SHOW TABLES LIKE 'enhanced_leave_balances'");
if ($table_check_enhanced && mysqli_num_rows($table_check_enhanced) > 0) {
    mysqli_free_result($table_check_enhanced);
    $enhanced_balance_check = "SELECT COUNT(*) as count FROM enhanced_leave_balances WHERE employee_id = ? AND employee_type = 'employee'";
    $enhanced_balance_stmt = mysqli_prepare($conn, $enhanced_balance_check);
    if ($enhanced_balance_stmt) {
        mysqli_stmt_bind_param($enhanced_balance_stmt, "i", $employee_id);
        mysqli_stmt_execute($enhanced_balance_stmt);
        $enhanced_balance_result = mysqli_stmt_get_result($enhanced_balance_stmt);
        $enhanced_balance_count = mysqli_fetch_assoc($enhanced_balance_result)['count'];
        if ($enhanced_balance_count > 0) {
            $related_data[] = "$enhanced_balance_count enhanced leave balance record(s)";
        }
        mysqli_stmt_close($enhanced_balance_stmt);
    }
} else {
    if ($table_check_enhanced) {
        mysqli_free_result($table_check_enhanced);
    }
}

// Check for employee regularization records (only if table exists)
$table_check_regularization = mysqli_query($conn, "SHOW TABLES LIKE 'employee_regularization'");
if ($table_check_regularization && mysqli_num_rows($table_check_regularization) > 0) {
    mysqli_free_result($table_check_regularization);
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
        mysqli_stmt_close($emp_regularization_stmt);
    }
} else {
    if ($table_check_regularization) {
        mysqli_free_result($table_check_regularization);
    }
}

// Check for activity logs (if employee is referenced in admin activity logs)
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_logs'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    mysqli_free_result($table_check);
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
        mysqli_stmt_close($activity_stmt);
    }
} else {
    if ($table_check) {
        mysqli_free_result($table_check);
    }
}

// Check for user account records (if employee has login credentials - only if table exists)
$table_check_users = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if ($table_check_users && mysqli_num_rows($table_check_users) > 0) {
    mysqli_free_result($table_check_users);
    $user_check = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $user_stmt = mysqli_prepare($conn, $user_check);
    if ($user_stmt) {
        mysqli_stmt_bind_param($user_stmt, "s", $employee['email']);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_count = mysqli_fetch_assoc($user_result)['count'];
        if ($user_count > 0) {
            $related_data[] = "$user_count user account(s)";
        }
        mysqli_stmt_close($user_stmt);
    }
} else {
    if ($table_check_users) {
        mysqli_free_result($table_check_users);
    }
}

// If there are related records, prevent deletion
if (!empty($related_data)) {
    send_json([
        'success' => false, 
        'message' => "Cannot delete '$employee_name' - employee has existing data that must be removed first.",
        'related_data' => $related_data
    ], 409);
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
    
    send_json([
        'success' => true, 
        'message' => "Employee '$employee_name' deleted successfully"
    ], 200);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    send_json_error('Error deleting employee: ' . $e->getMessage(), 500);
}

// Close statements
if (isset($delete_user_stmt)) mysqli_stmt_close($delete_user_stmt);
if (isset($delete_employee_stmt)) mysqli_stmt_close($delete_employee_stmt);
if (isset($check_stmt)) mysqli_stmt_close($check_stmt);

mysqli_close($conn);

} catch (Throwable $e) {
    // Clean any output that might have been generated
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Log the error with full details
    $error_details = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log("Delete Employee Error: " . print_r($error_details, true));
    
    // Send clean JSON error response
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    
    // Ensure we only output JSON - no whitespace or other content
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please check server logs for details.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
?>
