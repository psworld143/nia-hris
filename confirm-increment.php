<?php
/**
 * Confirm or Reject Salary Increment
 * AJAX endpoint to confirm or reject pending salary increments with password verification
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$increment_id = isset($input['increment_id']) ? intval($input['increment_id']) : 0;
$action = isset($input['action']) ? $input['action'] : '';
$password = isset($input['password']) ? $input['password'] : '';

// Validate input
if (!$increment_id || !in_array($action, ['confirm', 'reject']) || !$password) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit();
}

// Verify password
$user_id = $_SESSION['user_id'];
$password_query = "SELECT password FROM users WHERE id = ? AND role = 'human_resource'";
$password_stmt = mysqli_prepare($conn, $password_query);
mysqli_stmt_bind_param($password_stmt, 'i', $user_id);
mysqli_stmt_execute($password_stmt);
$password_result = mysqli_stmt_get_result($password_stmt);

if (mysqli_num_rows($password_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user_data = mysqli_fetch_assoc($password_result);
if (!password_verify($password, $user_data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    exit();
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // Get increment details
    $increment_query = "SELECT 
        si.*,
        e.first_name,
        e.last_name,
        e.position,
        ed.basic_salary as current_salary
    FROM salary_increments si
    JOIN employees e ON si.employee_id = e.id
    LEFT JOIN employee_details ed ON e.id = ed.employee_id
    WHERE si.id = ? AND si.status = 'pending'";
    
    $increment_stmt = mysqli_prepare($conn, $increment_query);
    mysqli_stmt_bind_param($increment_stmt, 'i', $increment_id);
    mysqli_stmt_execute($increment_stmt);
    $increment_result = mysqli_stmt_get_result($increment_stmt);
    
    if (mysqli_num_rows($increment_result) == 0) {
        throw new Exception('Increment not found or already processed');
    }
    
    $increment_data = mysqli_fetch_assoc($increment_result);
    
    if ($action === 'confirm') {
        // Update increment status to approved
        $update_query = "UPDATE salary_increments SET 
            status = 'approved',
            approved_by = ?,
            approved_at = NOW(),
            updated_at = NOW()
        WHERE id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'ii', $user_id, $increment_id);
        mysqli_stmt_execute($update_stmt);
        
        // Update employee's basic salary
        $new_salary = $increment_data['new_salary'];
        $salary_update_query = "UPDATE employee_details SET 
            basic_salary = ?,
            updated_at = NOW()
        WHERE employee_id = ?";
        
        $salary_stmt = mysqli_prepare($conn, $salary_update_query);
        mysqli_stmt_bind_param($salary_stmt, 'di', $new_salary, $increment_data['employee_id']);
        mysqli_stmt_execute($salary_stmt);
        
        // Add to increment history
        $history_query = "INSERT INTO increment_history (
            employee_id, 
            increment_id, 
            old_salary, 
            new_salary, 
            increment_amount, 
            increment_percentage, 
            effective_date, 
            action, 
            action_by,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?)";
        
        $history_stmt = mysqli_prepare($conn, $history_query);
        $notes = "Salary increment approved and implemented by HR";
        mysqli_stmt_bind_param($history_stmt, "iiddddsis", 
            $increment_data['employee_id'], 
            $increment_id, 
            $increment_data['current_salary'], 
            $new_salary, 
            $increment_data['increment_amount'], 
            $increment_data['increment_percentage'], 
            $increment_data['effective_date'],
            $user_id,
            $notes
        );
        mysqli_stmt_execute($history_stmt);
        
        // Log salary change
        $monitor_query = "INSERT INTO salary_change_monitor (
            employee_id, 
            field_changed, 
            old_value, 
            new_value, 
            change_reason, 
            changed_by,
            change_source, 
            is_authorized, 
            authorization_reference
        ) VALUES (?, 'basic_salary', ?, ?, 'Salary increment approved by HR', ?, 'hr_confirmation', 1, ?)";
        
        $monitor_stmt = mysqli_prepare($conn, $monitor_query);
        $reference = "HR_CONFIRMED_" . $increment_id;
        mysqli_stmt_bind_param($monitor_stmt, "issss", 
            $increment_data['employee_id'],
            $increment_data['current_salary'],
            $new_salary,
            $user_id,
            $reference
        );
        mysqli_stmt_execute($monitor_stmt);
        
        $message = "Salary increment confirmed and applied successfully";
        
    } else { // reject
        // Update increment status to rejected
        $update_query = "UPDATE salary_increments SET 
            status = 'rejected',
            rejected_by = ?,
            rejected_at = NOW(),
            updated_at = NOW()
        WHERE id = ?";
        
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'ii', $user_id, $increment_id);
        mysqli_stmt_execute($update_stmt);
        
        // Add to increment history
        $history_query = "INSERT INTO increment_history (
            employee_id, 
            increment_id, 
            old_salary, 
            new_salary, 
            increment_amount, 
            increment_percentage, 
            effective_date, 
            action, 
            action_by,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'rejected', ?, ?)";
        
        $history_stmt = mysqli_prepare($conn, $history_query);
        $notes = "Salary increment rejected by HR";
        mysqli_stmt_bind_param($history_stmt, "iiddddsis", 
            $increment_data['employee_id'], 
            $increment_id, 
            $increment_data['current_salary'], 
            $increment_data['new_salary'], 
            $increment_data['increment_amount'], 
            $increment_data['increment_percentage'], 
            $increment_data['effective_date'],
            $user_id,
            $notes
        );
        mysqli_stmt_execute($history_stmt);
        
        $message = "Salary increment rejected successfully";
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log the action
    $log_message = "HR {$action}ed salary increment #{$increment_id} for {$increment_data['first_name']} {$increment_data['last_name']} by user #{$user_id}";
    error_log("[HR_CONFIRMATION] {$log_message}");
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action,
        'employee_name' => $increment_data['first_name'] . ' ' . $increment_data['last_name']
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing request: ' . $e->getMessage()
    ]);
}
?>
