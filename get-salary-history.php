<?php
/**
 * Get Salary History for Employee
 * AJAX endpoint to fetch salary increase history for a specific employee
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

// Get employee ID from query parameter
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

try {
    // Get employee information
    $employee_query = "SELECT 
        e.id,
        e.first_name,
        e.last_name,
        e.position,
        e.department,
        ed.basic_salary as current_salary
    FROM employees e 
    LEFT JOIN employee_details ed ON e.id = ed.employee_id 
    WHERE e.id = ? AND e.is_active = 1";
    
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    mysqli_stmt_bind_param($employee_stmt, 'i', $employee_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if (mysqli_num_rows($employee_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    $employee_info = mysqli_fetch_assoc($employee_result);
    
    // Get salary increment history
    $history_query = "SELECT 
        si.id,
        si.incrementation_name,
        si.current_salary,
        si.increment_amount,
        si.new_salary,
        si.increment_percentage,
        si.effective_date,
        si.status,
        si.reason,
        si.created_at,
        si.incrementation_frequency_years
    FROM salary_increments si 
    WHERE si.employee_id = ? 
    ORDER BY si.effective_date DESC, si.created_at DESC";
    
    $history_stmt = mysqli_prepare($conn, $history_query);
    mysqli_stmt_bind_param($history_stmt, 'i', $employee_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    
    $history = [];
    while ($row = mysqli_fetch_assoc($history_result)) {
        $history[] = $row;
    }
    
    // Get hire date for additional context
    $hire_query = "SELECT hire_date FROM employees WHERE id = ?";
    $hire_stmt = mysqli_prepare($conn, $hire_query);
    mysqli_stmt_bind_param($hire_stmt, 'i', $employee_id);
    mysqli_stmt_execute($hire_stmt);
    $hire_result = mysqli_stmt_get_result($hire_stmt);
    $hire_data = mysqli_fetch_assoc($hire_result);
    
    // Add hire date to employee info
    $employee_info['hire_date'] = $hire_data['hire_date'];
    
    // Calculate years of service
    if ($hire_data['hire_date']) {
        $hire_date = new DateTime($hire_data['hire_date']);
        $current_date = new DateTime();
        $years_service = $hire_date->diff($current_date)->y + ($hire_date->diff($current_date)->m / 12);
        $employee_info['years_of_service'] = round($years_service, 1);
    }
    
    echo json_encode([
        'success' => true,
        'employee_info' => $employee_info,
        'history' => $history,
        'total_increments' => count($history)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching salary history: ' . $e->getMessage()
    ]);
}
?>
