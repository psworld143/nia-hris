<?php
// Error reporting (log only, don't display in response)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
require_once 'includes/roles.php';

// Clear any output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Log the request for debugging
error_log("Delete department request received. Session role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !canManageDepartments()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$json_input = file_get_contents('php://input');
$input = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

error_log("Input data: " . print_r($input, true));

if (!isset($input['department_id']) || empty($input['department_id'])) {
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit();
}

if (!isset($input['hr_password']) || empty($input['hr_password'])) {
    echo json_encode(['success' => false, 'message' => 'HR password is required']);
    exit();
}

// Decrypt department ID with error handling
try {
    $department_id = decrypt_id($input['department_id']);
} catch (Exception $e) {
    error_log("Department ID decryption failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Invalid department ID format']);
    exit();
}

$hr_password = $input['hr_password'];

// Validate department ID
if ($department_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
    exit();
}

// Verify HR officer password
$user_id = $_SESSION['user_id'];

// Check for all roles that can manage departments: super_admin, admin, hr_manager
$password_query = "SELECT password FROM users WHERE id = ? AND role IN ('super_admin', 'admin', 'hr_manager')";
$password_stmt = mysqli_prepare($conn, $password_query);
mysqli_stmt_bind_param($password_stmt, "i", $user_id);
mysqli_stmt_execute($password_stmt);
$password_result = mysqli_stmt_get_result($password_stmt);

if (!$password_result || mysqli_num_rows($password_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'HR officer not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($password_result);

// Verify password
if (!password_verify($hr_password, $user_data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid HR password. Access denied.']);
    exit();
}

// Check if department exists
$check_query = "SELECT id, name FROM departments WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $department_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Department not found']);
    exit();
}

$department = mysqli_fetch_assoc($check_result);
$department_name = $department['name'];

// Check for existing related data that would prevent deletion
$related_data = [];

try {
    // Check for employees in this department (using department_id foreign key)
    $employee_check = "SELECT COUNT(*) as count FROM employees WHERE department_id = ? AND is_active = 1";
    $employee_stmt = mysqli_prepare($conn, $employee_check);
    if ($employee_stmt) {
        mysqli_stmt_bind_param($employee_stmt, "i", $department_id);
        mysqli_stmt_execute($employee_stmt);
        $employee_result = mysqli_stmt_get_result($employee_stmt);
        if ($employee_result) {
            $employee_count = mysqli_fetch_assoc($employee_result)['count'];
            if ($employee_count > 0) {
                $related_data[] = "$employee_count active employee(s)";
            }
        }
        mysqli_stmt_close($employee_stmt);
    }
} catch (Exception $e) {
    error_log("Error checking employees: " . $e->getMessage());
}

try {
    // Check for leave requests from employees in this department
    $leave_check = "SELECT COUNT(*) as count FROM employee_leave_requests elr 
                    INNER JOIN employees e ON elr.employee_id = e.id 
                    WHERE e.department_id = ?";
    $leave_stmt = mysqli_prepare($conn, $leave_check);
    if ($leave_stmt) {
        mysqli_stmt_bind_param($leave_stmt, "i", $department_id);
        mysqli_stmt_execute($leave_stmt);
        $leave_result = mysqli_stmt_get_result($leave_stmt);
        if ($leave_result) {
            $leave_count = mysqli_fetch_assoc($leave_result)['count'];
            if ($leave_count > 0) {
                $related_data[] = "$leave_count leave request(s)";
            }
        }
        mysqli_stmt_close($leave_stmt);
    }
} catch (Exception $e) {
    error_log("Error checking leave requests: " . $e->getMessage());
}

// If there are related records, prevent deletion
if (!empty($related_data)) {
    echo json_encode([
        'success' => false, 
        'message' => "Cannot delete '$department_name' - department has existing data that must be removed first.",
        'related_data' => $related_data
    ]);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete the department
    $delete_department_query = "DELETE FROM departments WHERE id = ?";
    $delete_department_stmt = mysqli_prepare($conn, $delete_department_query);
    if (!$delete_department_stmt) {
        throw new Exception('Error preparing department delete: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_department_stmt, "i", $department_id);
    
    if (!mysqli_stmt_execute($delete_department_stmt)) {
        throw new Exception('Error deleting department: ' . mysqli_stmt_error($delete_department_stmt));
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
            $action = "Deleted department: $department_name";
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
        'message' => "Department '$department_name' deleted successfully"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode(['success' => false, 'message' => 'Error deleting department: ' . $e->getMessage()]);
}

// Close statements
if (isset($delete_department_stmt)) mysqli_stmt_close($delete_department_stmt);

mysqli_close($conn);
?>
