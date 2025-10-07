<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Ensure no output before JSON
ob_start();

// Disable error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$employee_id = $input['employee_id'] ?? 0;
$hr_password = $input['hr_password'] ?? '';
$force_delete = $input['force_delete'] ?? false;

if (!$employee_id || !$hr_password || !$force_delete) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Verify HR officer password
    $user_id = $_SESSION['user_id'];
    $password_query = "SELECT password FROM users WHERE id = ? AND role = 'human_resource'";
    $password_stmt = mysqli_prepare($conn, $password_query);
    mysqli_stmt_bind_param($password_stmt, "i", $user_id);
    mysqli_stmt_execute($password_stmt);
    $password_result = mysqli_stmt_get_result($password_stmt);
    
    if (!$password_result || mysqli_num_rows($password_result) === 0) {
        throw new Exception('HR officer not found');
    }
    
    $user_data = mysqli_fetch_assoc($password_result);
    
    // Verify password
    if (!password_verify($hr_password, $user_data['password'])) {
        throw new Exception('Invalid HR password. Access denied.');
    }
    
    // Get employee information before deletion
    $employee_query = "SELECT first_name, last_name, email, department FROM employees WHERE id = ?";
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    mysqli_stmt_bind_param($employee_stmt, "i", $employee_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if (!$employee_result || mysqli_num_rows($employee_result) === 0) {
        throw new Exception('Employee not found');
    }
    
    $employee = mysqli_fetch_assoc($employee_result);
    $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
    
    // Start transaction for force delete
    mysqli_begin_transaction($conn);
    
    // Log the force delete action (check if table exists first)
    $check_log_table = "SHOW TABLES LIKE 'admin_activity_logs'";
    $log_table_check = mysqli_query($conn, $check_log_table);
    
    if ($log_table_check && mysqli_num_rows($log_table_check) > 0) {
        // Check the actual column structure
        $check_columns = "DESCRIBE admin_activity_logs";
        $columns_result = mysqli_query($conn, $check_columns);
        $columns = [];
        while ($col = mysqli_fetch_assoc($columns_result)) {
            $columns[] = $col['Field'];
        }
        
        // Adapt to existing table structure
        if (in_array('admin_id', $columns) && in_array('action', $columns) && in_array('details', $columns)) {
            // Use existing table structure: admin_id, action, details, ip_address, user_agent
            $log_query = "INSERT INTO admin_activity_logs (admin_id, action, details, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $action = 'FORCE_DELETE_EMPLOYEE';
            $log_details = "FORCE_DELETE_EMPLOYEE: {$employee_name} (ID: {$employee_id}) from {$employee['department']}";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            mysqli_stmt_bind_param($log_stmt, "issss", $user_id, $action, $log_details, $ip_address, $user_agent);
        } else if (in_array('user_id', $columns) && in_array('target_type', $columns)) {
            // Use new table structure: user_id, action, target_type, target_id, details, ip_address
            $log_query = "INSERT INTO admin_activity_logs (user_id, action, target_type, target_id, details, ip_address) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $action = 'FORCE_DELETE_EMPLOYEE';
            $target_type = 'employee';
            $log_details = "Force deleted employee: {$employee_name} ({$employee['email']}) from {$employee['department']}";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            mysqli_stmt_bind_param($log_stmt, "isiiss", $user_id, $action, $target_type, $employee_id, $log_details, $ip_address);
        } else {
            // Fallback: minimal logging
            $log_query = "INSERT INTO admin_activity_logs (admin_id, action, details) VALUES (?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $action = 'FORCE_DELETE_EMPLOYEE';
            $log_details = "FORCE_DELETE_EMPLOYEE: {$employee_name} (ID: {$employee_id})";
            mysqli_stmt_bind_param($log_stmt, "iss", $user_id, $action, $log_details);
        }
        
        if (!mysqli_stmt_execute($log_stmt)) {
            // If logging fails, continue with deletion but note the issue
            error_log("Failed to log force delete action: " . mysqli_error($conn));
        }
    }
    
    // Delete related data in proper order (respecting foreign key constraints)
    
    // 1. Delete employee regularization records (check if table exists)
    $check_reg_table = "SHOW TABLES LIKE 'employee_regularization'";
    $reg_check = mysqli_query($conn, $check_reg_table);
    if ($reg_check && mysqli_num_rows($reg_check) > 0) {
        $delete_regularization = "DELETE FROM employee_regularization WHERE employee_id = ?";
        $reg_stmt = mysqli_prepare($conn, $delete_regularization);
        mysqli_stmt_bind_param($reg_stmt, "i", $employee_id);
        mysqli_stmt_execute($reg_stmt);
    }
    
    // 2. Delete employee details (check if table exists)
    $check_details_table = "SHOW TABLES LIKE 'employee_details'";
    $details_check = mysqli_query($conn, $check_details_table);
    if ($details_check && mysqli_num_rows($details_check) > 0) {
        $delete_details = "DELETE FROM employee_details WHERE employee_id = ?";
        $details_stmt = mysqli_prepare($conn, $delete_details);
        mysqli_stmt_bind_param($details_stmt, "i", $employee_id);
        mysqli_stmt_execute($details_stmt);
    }
    
    // 3. Delete leave balances (check if table exists)
    $check_leave_table = "SHOW TABLES LIKE 'enhanced_leave_balances'";
    $leave_check = mysqli_query($conn, $check_leave_table);
    if ($leave_check && mysqli_num_rows($leave_check) > 0) {
        $delete_leave = "DELETE FROM enhanced_leave_balances WHERE employee_id = ? AND employee_type = 'employee'";
        $leave_stmt = mysqli_prepare($conn, $delete_leave);
        mysqli_stmt_bind_param($leave_stmt, "i", $employee_id);
        mysqli_stmt_execute($leave_stmt);
    }
    
    // 4. Delete department head assignments
    $check_dept_heads_table = "SHOW TABLES LIKE 'department_heads'";
    $dept_heads_check = mysqli_query($conn, $check_dept_heads_table);
    if ($dept_heads_check && mysqli_num_rows($dept_heads_check) > 0) {
        $delete_dept_heads = "DELETE FROM department_heads WHERE employee_id = ?";
        $dept_stmt = mysqli_prepare($conn, $delete_dept_heads);
        mysqli_stmt_bind_param($dept_stmt, "i", $employee_id);
        mysqli_stmt_execute($dept_stmt);
    }
    
    // 5. Update any posts/content created by this employee to system user (check if table exists)
    $check_posts_table = "SHOW TABLES LIKE 'posts'";
    $posts_check = mysqli_query($conn, $check_posts_table);
    if ($posts_check && mysqli_num_rows($posts_check) > 0) {
        // Check if posts table has author_type column
        $check_posts_columns = "SHOW COLUMNS FROM posts LIKE 'author_type'";
        $posts_columns_check = mysqli_query($conn, $check_posts_columns);
        
        if ($posts_columns_check && mysqli_num_rows($posts_columns_check) > 0) {
            $update_posts = "UPDATE posts SET author_id = 1 WHERE author_id = ? AND author_type = 'employee'";
        } else {
            $update_posts = "UPDATE posts SET author_id = 1 WHERE author_id = ?";
        }
        
        $posts_stmt = mysqli_prepare($conn, $update_posts);
        mysqli_stmt_bind_param($posts_stmt, "i", $employee_id);
        mysqli_stmt_execute($posts_stmt);
    }
    
    // 6. Delete any HR-related records (salary history, benefits, training)
    $check_salary_table = "SHOW TABLES LIKE 'employee_salary_history'";
    $salary_check = mysqli_query($conn, $check_salary_table);
    if ($salary_check && mysqli_num_rows($salary_check) > 0) {
        $delete_salary = "DELETE FROM employee_salary_history WHERE employee_id = ?";
        $salary_stmt = mysqli_prepare($conn, $delete_salary);
        mysqli_stmt_bind_param($salary_stmt, "i", $employee_id);
        mysqli_stmt_execute($salary_stmt);
    }
    
    $check_benefits_table = "SHOW TABLES LIKE 'employee_benefits'";
    $benefits_check = mysqli_query($conn, $check_benefits_table);
    if ($benefits_check && mysqli_num_rows($benefits_check) > 0) {
        $delete_benefits = "DELETE FROM employee_benefits WHERE employee_id = ?";
        $benefits_stmt = mysqli_prepare($conn, $delete_benefits);
        mysqli_stmt_bind_param($benefits_stmt, "i", $employee_id);
        mysqli_stmt_execute($benefits_stmt);
    }
    
    $check_training_table = "SHOW TABLES LIKE 'employee_training'";
    $training_check = mysqli_query($conn, $check_training_table);
    if ($training_check && mysqli_num_rows($training_check) > 0) {
        $delete_training = "DELETE FROM employee_training WHERE employee_id = ?";
        $training_stmt = mysqli_prepare($conn, $delete_training);
        mysqli_stmt_bind_param($training_stmt, "i", $employee_id);
        mysqli_stmt_execute($training_stmt);
    }
    
    // 7. Finally, delete the main employee record
    $delete_employee = "DELETE FROM employees WHERE id = ?";
    $employee_stmt = mysqli_prepare($conn, $delete_employee);
    mysqli_stmt_bind_param($employee_stmt, "i", $employee_id);
    
    if (!mysqli_stmt_execute($employee_stmt)) {
        throw new Exception('Failed to delete employee record: ' . mysqli_error($conn));
    }
    
    // Check if employee was actually deleted
    if (mysqli_affected_rows($conn) === 0) {
        throw new Exception('Employee record not found or already deleted');
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Clean output buffer and send JSON response
    ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => "Employee {$employee_name} and all related data have been permanently deleted"
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    // Clean output buffer and send JSON error response
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
