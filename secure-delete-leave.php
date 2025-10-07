<?php
session_start();

// Set JSON content type header and start output buffering
header('Content-Type: application/json');
ob_start();

// Suppress any error output that might corrupt JSON
error_reporting(0);
ini_set('display_errors', 0);

// Use shared application database config/connection
require_once 'config/database.php';

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

mysqli_set_charset($conn, 'utf8mb4');

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Validate required parameters
    $missing_params = [];
    if (!isset($_POST['leave_id'])) $missing_params[] = 'leave_id';
    if (!isset($_POST['source_table'])) $missing_params[] = 'source_table';
    if (!isset($_POST['password'])) $missing_params[] = 'password';
    if (!isset($_POST['captcha'])) $missing_params[] = 'captcha';
    if (!isset($_POST['captcha_answer'])) $missing_params[] = 'captcha_answer';
    if (!isset($_POST['final_confirmation'])) $missing_params[] = 'final_confirmation';
    
    if (!empty($missing_params)) {
        throw new Exception('Missing required security parameters: ' . implode(', ', $missing_params));
    }
    
    $leave_id = (int)$_POST['leave_id'];
    $source_table = $_POST['source_table'];
    $password = $_POST['password'];
    $captcha_input = strtoupper(trim($_POST['captcha']));
    $captcha_answer = strtoupper(trim($_POST['captcha_answer']));
    $final_confirmation = $_POST['final_confirmation'];
    
    // Validate source table
    if (!in_array($source_table, ['employee', 'faculty'])) {
        throw new Exception('Invalid source table');
    }
    
    // Validate final confirmation checkbox
    if (!$final_confirmation) {
        throw new Exception('Final confirmation is required');
    }
    
    // Step 1: Verify Password
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT password, email FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    
    if (!$user_data) {
        throw new Exception('User not found');
    }
    
    // Verify password using proper password_verify function
    if (!password_verify($password, $user_data['password'])) {
        throw new Exception('Invalid password');
    }
    
    // Step 2: Verify CAPTCHA
    if ($captcha_input !== $captcha_answer) {
        throw new Exception('CAPTCHA verification failed');
    }
    
    // All security checks passed - proceed with deletion
    
    // Get leave request details for logging
    $table_name = 'employee_leave_requests'; // All employees use same table
    $details_query = "SELECT * FROM {$table_name} WHERE id = ?";
    $details_stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($details_stmt, 'i', $leave_id);
    mysqli_stmt_execute($details_stmt);
    $details_result = mysqli_stmt_get_result($details_stmt);
    $leave_details = mysqli_fetch_assoc($details_result);
    
    if (!$leave_details) {
        throw new Exception('Leave request not found');
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Delete the leave request
        $delete_query = "DELETE FROM {$table_name} WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $leave_id);
        
        if (!mysqli_stmt_execute($delete_stmt)) {
            throw new Exception('Failed to delete leave request');
        }
        
        if (mysqli_affected_rows($conn) === 0) {
            throw new Exception('Leave request not found or already deleted');
        }
        
        // Log the secure deletion
        $log_query = "INSERT INTO security_logs (user_id, action, target_type, target_id, details, ip_address, user_agent, created_at) 
                      VALUES (?, 'SECURE_DELETE', 'LEAVE_REQUEST', ?, ?, ?, ?, NOW())";
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $log_details = json_encode([
            'leave_id' => $leave_id,
            'source_table' => $source_table,
            'employee_id' => $leave_details[$source_table . '_id'],
            'start_date' => $leave_details['start_date'],
            'end_date' => $leave_details['end_date'],
            'total_days' => $leave_details['total_days'],
            'reason' => $leave_details['reason'],
            'status' => $leave_details['status'],
            'security_checks' => ['password' => true, 'captcha' => true]
        ]);
        
        // Create security_logs table if it doesn't exist
        $create_log_table = "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NOT NULL,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_action (user_id, action),
            INDEX idx_created_at (created_at)
        )";
        mysqli_query($conn, $create_log_table);
        
        $log_stmt = mysqli_prepare($conn, $log_query);
        mysqli_stmt_bind_param($log_stmt, 'iisss', $user_id, $leave_id, $log_details, $ip_address, $user_agent);
        mysqli_stmt_execute($log_stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Log success
        error_log("SECURE DELETE: User {$user_data['email']} successfully deleted leave request ID {$leave_id} from {$source_table} table");
        
        // Clean any stray output and return success response
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Leave request has been permanently deleted'
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in secure-delete-leave.php: " . $e->getMessage());
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
