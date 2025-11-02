<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$id = $input['id'] ?? 0;
$type = $input['type'] ?? '';
$action = $input['action'] ?? '';
$criteria_ids = $input['criteria_ids'] ?? [];

if (!$id || !in_array($type, ['employee', 'faculty']) || !in_array($action, ['regularize'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Validate criteria if provided
$criteria_validation = [];
if (!empty($criteria_ids)) {
    $criteria_placeholders = str_repeat('?,', count($criteria_ids) - 1) . '?';
    $criteria_query = "SELECT id, criteria_name FROM regularization_criteria WHERE id IN ($criteria_placeholders) AND is_active = 1";
    $criteria_stmt = mysqli_prepare($conn, $criteria_query);
    
    $criteria_types = str_repeat('i', count($criteria_ids));
    mysqli_stmt_bind_param($criteria_stmt, $criteria_types, ...$criteria_ids);
    mysqli_stmt_execute($criteria_stmt);
    $criteria_result = mysqli_stmt_get_result($criteria_stmt);
    
    while ($row = mysqli_fetch_assoc($criteria_result)) {
        $criteria_validation[] = $row;
    }
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    if ($type === 'employee') {
        // Process employee regularization
        
        // Check if employee exists and is eligible
        $check_query = "SELECT e.id, e.first_name, e.last_name, e.hire_date, 
                               DATEDIFF(CURDATE(), e.hire_date) as days_employed
                        FROM employees e 
                        WHERE e.id = ? AND e.is_active = 1 
                        AND e.hire_date <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
        
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (!$check_result || mysqli_num_rows($check_result) === 0) {
            throw new Exception('Employee not found or not eligible for regularization');
        }
        
        $employee = mysqli_fetch_assoc($check_result);
        
        // Get regular status ID
        $status_query = "SELECT id FROM regularization_status WHERE name = 'Regular'";
        $status_result = mysqli_query($conn, $status_query);
        if (!$status_result || mysqli_num_rows($status_result) === 0) {
            throw new Exception('Regular status not found in system');
        }
        $regular_status = mysqli_fetch_assoc($status_result);
        
        // Get default staff category (you may want to make this configurable)
        $category_query = "SELECT id FROM staff_categories WHERE name = 'Administrative Staff' LIMIT 1";
        $category_result = mysqli_query($conn, $category_query);
        if (!$category_result || mysqli_num_rows($category_result) === 0) {
            // Create default category if it doesn't exist
            $create_category = "INSERT INTO staff_categories (name, description, regularization_period_months) 
                               VALUES ('Administrative Staff', 'General administrative staff', 6)";
            mysqli_query($conn, $create_category);
            $category_id = mysqli_insert_id($conn);
        } else {
            $category = mysqli_fetch_assoc($category_result);
            $category_id = $category['id'];
        }
        
        // Prepare criteria information for notes
        $criteria_notes = 'Regularized through HR management system';
        if (!empty($criteria_validation)) {
            $criteria_names = array_column($criteria_validation, 'criteria_name');
            $criteria_notes .= "\n\nCriteria validated: " . implode(', ', $criteria_names);
        }
        
        // Insert or update employee regularization record
        $regularization_query = "INSERT INTO employee_regularization 
                                (employee_id, staff_category_id, current_status_id, date_of_hire, 
                                 probation_start_date, probation_end_date, regularization_date, 
                                 review_notes, reviewed_by, reviewed_at)
                                VALUES (?, ?, ?, ?, ?, DATE_ADD(?, INTERVAL 6 MONTH), CURDATE(), 
                                        ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE
                                current_status_id = VALUES(current_status_id),
                                regularization_date = VALUES(regularization_date),
                                review_notes = VALUES(review_notes),
                                reviewed_by = VALUES(reviewed_by),
                                reviewed_at = VALUES(reviewed_at),
                                updated_at = NOW()";
        
        $reg_stmt = mysqli_prepare($conn, $regularization_query);
        mysqli_stmt_bind_param($reg_stmt, "iiissssi", 
            $id, $category_id, $regular_status['id'], 
            $employee['hire_date'], $employee['hire_date'], $employee['hire_date'], 
            $criteria_notes, $_SESSION['user_id']);
        
        if (!mysqli_stmt_execute($reg_stmt)) {
            throw new Exception('Failed to update employee regularization status');
        }
        
        $message = "Employee {$employee['first_name']} {$employee['last_name']} has been successfully regularized";
        
    } else {
        // Process faculty regularization
        
        // Check if faculty exists and is eligible
        $check_query = "SELECT f.id, e.first_name, e.last_name, fd.date_of_hire,
                               DATEDIFF(CURDATE(), fd.date_of_hire) as days_employed
                        FROM employees f 
                        LEFT JOIN employee_details fd ON f.id = fd.employee_id
                        WHERE e.id = ? AND f.is_active = 1 
                        AND fd.date_of_hire <= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)";
        
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (!$check_result || mysqli_num_rows($check_result) === 0) {
            throw new Exception('Faculty not found or not eligible for regularization');
        }
        
        $faculty = mysqli_fetch_assoc($check_result);
        
        // Get regular status ID
        $status_query = "SELECT id FROM regularization_status WHERE name = 'Regular'";
        $status_result = mysqli_query($conn, $status_query);
        if (!$status_result || mysqli_num_rows($status_result) === 0) {
            throw new Exception('Regular status not found in system');
        }
        $regular_status = mysqli_fetch_assoc($status_result);
        
        // Get default faculty category
        $category_query = "SELECT id FROM staff_categories WHERE name = 'Faculty' LIMIT 1";
        $category_result = mysqli_query($conn, $category_query);
        if (!$category_result || mysqli_num_rows($category_result) === 0) {
            // Create default category if it doesn't exist
            $create_category = "INSERT INTO staff_categories (name, description, regularization_period_months) 
                               VALUES ('Faculty', 'Teaching faculty members', 36)";
            mysqli_query($conn, $create_category);
            $category_id = mysqli_insert_id($conn);
        } else {
            $category = mysqli_fetch_assoc($category_result);
            $category_id = $category['id'];
        }
        
        // Prepare criteria information for notes
        $criteria_notes = 'Regularized through HR management system';
        if (!empty($criteria_validation)) {
            $criteria_names = array_column($criteria_validation, 'criteria_name');
            $criteria_notes .= "\n\nCriteria validated: " . implode(', ', $criteria_names);
        }
        
        // Insert or update faculty regularization record
        $regularization_query = "INSERT INTO employee_regularization 
                                (employee_id, staff_category_id, current_status_id, date_of_hire, 
                                 probation_start_date, probation_end_date, regularization_date, 
                                 review_notes, reviewed_by, reviewed_at)
                                VALUES (?, ?, ?, ?, ?, DATE_ADD(?, INTERVAL 3 YEAR), CURDATE(), 
                                        ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE
                                current_status_id = VALUES(current_status_id),
                                regularization_date = VALUES(regularization_date),
                                review_notes = VALUES(review_notes),
                                reviewed_by = VALUES(reviewed_by),
                                reviewed_at = VALUES(reviewed_at),
                                updated_at = NOW()";
        
        $reg_stmt = mysqli_prepare($conn, $regularization_query);
        mysqli_stmt_bind_param($reg_stmt, "iiissssi", 
            $id, $category_id, $regular_status['id'], 
            $faculty['date_of_hire'], $faculty['date_of_hire'], $faculty['date_of_hire'], 
            $criteria_notes, $_SESSION['user_id']);
        
        if (!mysqli_stmt_execute($reg_stmt)) {
            throw new Exception('Failed to update faculty regularization status');
        }
        
        $message = "Faculty {$faculty['first_name']} {$faculty['last_name']} has been successfully regularized";
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
