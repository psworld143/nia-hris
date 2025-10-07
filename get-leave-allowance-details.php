<?php
session_start();

// Set JSON content type header and start output buffering
header('Content-Type: application/json');
ob_start();

// Suppress any error output that might corrupt JSON
error_reporting(0);
ini_set('display_errors', 0);

// Use unified database connection
require_once 'config/database.php';

if (!$conn) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get parameters
$employee_id = $_GET['employee_id'] ?? '';
$employee_type = $_GET['employee_type'] ?? '';
$year = $_GET['year'] ?? date('Y');

// Validate parameters
if (empty($employee_id) || empty($employee_type)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// Validate employee type
if (!in_array($employee_type, ['employee', 'faculty'])) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid employee type']);
    exit();
}

// Validate employee ID
if (!is_numeric($employee_id)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid employee ID']);
    exit();
}

try {
    // Get employee basic info
    if ($employee_type === 'employee') {
        $info_query = "SELECT e.*, ed.employment_type, ed.employment_status, 
                              er.regularization_date, rs.name as regularization_status_name
                       FROM employees e
                       LEFT JOIN employee_details ed ON e.id = ed.employee_id
                       LEFT JOIN employee_regularization er ON e.id = er.employee_id
                       LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                       WHERE e.id = ? AND e.is_active = 1";
    } else {
        $info_query = "SELECT f.*, fd.employment_type, fd.employment_status,
                              fr.regularization_date, rs.name as regularization_status_name
                       FROM employees f
                       LEFT JOIN employee_details fd ON f.id = fd.employee_id
                       LEFT JOIN employee_regularization fr ON f.id = fr.employee_id
                       LEFT JOIN regularization_status rs ON fr.current_status_id = rs.id
                       WHERE e.id = ? AND f.is_active = 1";
    }
    
    $info_stmt = mysqli_prepare($conn, $info_query);
    mysqli_stmt_bind_param($info_stmt, 'i', $employee_id);
    mysqli_stmt_execute($info_stmt);
    $info_result = mysqli_stmt_get_result($info_stmt);
    
    if (mysqli_num_rows($info_result) === 0) {
        ob_clean();
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found']);
        exit();
    }
    
    $employee_info = mysqli_fetch_assoc($info_result);
    
    // Use dynamic calculation (same logic as leave-allowance-management.php)
    function calculateDynamicLeaveBalance($employee_data, $employee_type, $year) {
        global $conn;
        
        $employee_id = $employee_data['id'];
        
        // Base allowance: 5 days for all employees/faculty
        $base_days = 5;
        $accumulated_days = 0;
        $can_accumulate = false;
        
        // Determine if employee is regular
        $is_regular = false;
        $regularization_date = null;
        $reg_info = null;
        
        if ($employee_type === 'employee') {
            // Get employee regularization info
            $reg_query = "SELECT er.regularization_date, rs.name as status_name 
                          FROM employee_regularization er 
                          LEFT JOIN regularization_status rs ON er.current_status_id = rs.id 
                          WHERE er.employee_id = ?";
            $reg_stmt = mysqli_prepare($conn, $reg_query);
            mysqli_stmt_bind_param($reg_stmt, 'i', $employee_id);
            mysqli_stmt_execute($reg_stmt);
            $reg_result = mysqli_stmt_get_result($reg_stmt);
            $reg_info = mysqli_fetch_assoc($reg_result);
            
            if ($reg_info && $reg_info['regularization_date']) {
                $regularization_date = new DateTime($reg_info['regularization_date']); // Convert to DateTime object
                $current_date = new DateTime(); // Use current date for accurate calculation
                $months_since_reg = $regularization_date->diff($current_date)->m + ($regularization_date->diff($current_date)->y * 12);
                
                // Regular after 6 months, can accumulate immediately after regularization
                $is_regular = $months_since_reg >= 6;
                $can_accumulate = $is_regular;
            }
        } else {
            // Faculty regularization info
            $reg_query = "SELECT fr.regularization_date, rs.name as status_name 
                          FROM employee_regularization fr 
                          LEFT JOIN regularization_status rs ON fr.current_status_id = rs.id 
                          WHERE fr.employee_id = ?";
            $reg_stmt = mysqli_prepare($conn, $reg_query);
            mysqli_stmt_bind_param($reg_stmt, 'i', $employee_id);
            mysqli_stmt_execute($reg_stmt);
            $reg_result = mysqli_stmt_get_result($reg_stmt);
            $reg_info = mysqli_fetch_assoc($reg_result);
            
            if ($reg_info && $reg_info['regularization_date']) {
                $regularization_date = new DateTime($reg_info['regularization_date']); // Convert to DateTime object
                $current_date = new DateTime(); // Use current date for accurate calculation
                $months_since_reg = $regularization_date->diff($current_date)->m + ($regularization_date->diff($current_date)->y * 12);
                
                // Regular after 3 years, can accumulate after 3 years
                $is_regular = $months_since_reg >= 36;
                $can_accumulate = $is_regular && $months_since_reg >= 36;
            }
        }
        
        // Calculate accumulated leave for eligible employees using Years of Service formula
        if ($can_accumulate) {
            // Get regularization date (when accumulation starts)
            $reg_date_query = $employee_type === 'employee' ? 
                "SELECT regularization_date FROM employee_regularization WHERE employee_id = ?" :
                "SELECT regularization_date FROM employee_regularization WHERE employee_id = ?";
            
            $reg_stmt = mysqli_prepare($conn, $reg_date_query);
            mysqli_stmt_bind_param($reg_stmt, 'i', $employee_id);
            mysqli_stmt_execute($reg_stmt);
            $reg_result = mysqli_stmt_get_result($reg_stmt);
            $reg_data = mysqli_fetch_assoc($reg_result);
            
            if ($reg_data && $reg_data['regularization_date']) {
                $regularization_date = new DateTime($reg_data['regularization_date']);
                $previous_year = new DateTime(($year - 1) . '-12-31');
                
                // Calculate Years of Service (from regularization date up to previous year, excluding current year)
                $years_of_service = max(0, $regularization_date->diff($previous_year)->y);
                
                if ($years_of_service > 0) {
                    // Calculate total leave entitlement for years of service (from regularization)
                    $total_entitlement = $years_of_service * 5;
                    
                    // Get total used leave since regularization date (all approved leave from when accumulation started)
                    $used_query = $employee_type === 'employee' ? 
                        "SELECT COALESCE(SUM(total_days), 0) as total_used
                         FROM employee_leave_requests 
                         WHERE employee_id = ? AND status IN ('approved_by_head', 'approved_by_hr') 
                         AND start_date >= ?" :
                        "SELECT COALESCE(SUM(total_days), 0) as total_used
                         FROM employee_leave_requests 
                         WHERE employee_id = ? AND status IN ('approved_by_head', 'approved_by_hr') 
                         AND start_date >= ?";
                    
                    $used_stmt = mysqli_prepare($conn, $used_query);
                    mysqli_stmt_bind_param($used_stmt, 'is', $employee_id, $reg_data['regularization_date']);
                    mysqli_stmt_execute($used_stmt);
                    $used_result = mysqli_stmt_get_result($used_stmt);
                    $used_data = mysqli_fetch_assoc($used_result);
                    $total_used_since_regularization = $used_data['total_used'] ?? 0;
                    
                    // Accumulated Leave = (Years of Service × 5) - All Approved Leave Since Regularization
                    $accumulated_days = max(0, $total_entitlement - $total_used_since_regularization);
                    
                    // Cap accumulated leave at reasonable limit (e.g., 50 days)
                    $accumulated_days = min(50, $accumulated_days);
                }
            }
        }
        
        // Get used days for current year (only fully approved)
        $used_query = $employee_type === 'employee' ? 
            "SELECT COALESCE(SUM(total_days), 0) as used_days
             FROM employee_leave_requests 
             WHERE employee_id = ? AND YEAR(start_date) = ? 
             AND status = 'approved_by_hr'" :
            "SELECT COALESCE(SUM(total_days), 0) as used_days
             FROM employee_leave_requests 
             WHERE employee_id = ? AND YEAR(start_date) = ? 
             AND status = 'approved_by_hr'";
        
        $used_stmt = mysqli_prepare($conn, $used_query);
        mysqli_stmt_bind_param($used_stmt, 'ii', $employee_id, $year);
        mysqli_stmt_execute($used_stmt);
        $used_result = mysqli_stmt_get_result($used_stmt);
        $used_data = mysqli_fetch_assoc($used_result);
        $used_days = $used_data['used_days'] ?? 0;
        
        // Calculate final values
        $remaining_base = max(0, $base_days - $used_days);
        $total_days = $base_days + $accumulated_days;
        $remaining_days = max(0, $total_days - $used_days);
        
        return [
            'employee_id' => $employee_id,
            'first_name' => $employee_data['first_name'],
            'last_name' => $employee_data['last_name'],
            'emp_id' => $employee_data[$employee_type === 'employee' ? 'employee_id' : 'qrcode'] ?? '',
            'department' => $employee_data['department'],
            'position' => $employee_data['position'],
            'employment_status' => $employee_data['employment_status'] ?? null,
            'employment_type' => $employee_data['employment_type'] ?? null,
            'regularization_status_name' => $reg_info['status_name'] ?? null,
            'profile_photo' => $employee_data['profile_photo'] ?? null,
            'image_url' => $employee_data['image_url'] ?? null,
            'base_days' => $base_days, // Always 5 days per year
            'remaining_base_days' => $remaining_base, // Add separate field for remaining
            'accumulated_days' => $accumulated_days,
            'total_days' => $total_days,
            'used_days' => $used_days,
            'remaining_days' => $remaining_days,
            'is_regular' => $is_regular,
            'can_accumulate' => $can_accumulate,
            'regularization_date' => $regularization_date ? $regularization_date->format('Y-m-d') : null,
            'accumulation_start_year' => $can_accumulate ? ($is_regular ? $regularization_date->format('Y') : null) : null,
            'source_table' => $employee_type,
            'leave_types' => 'All Types', // Simplified
        ];
    }
    
    // Calculate dynamic leave balance
    $allowance_data = calculateDynamicLeaveBalance($employee_info, $employee_type, $year);
    
    // Clean any stray output and return response
    ob_clean();
    echo json_encode([
        'success' => true,
        'year' => $year,
        'employee_info' => $employee_info,
        'allowance_calculation' => $allowance_data,
        'allowance_records' => [], // Empty since we use dynamic calculation
        'accumulation_history' => [] // Empty for now
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
    error_log("Error in get-leave-allowance-details.php: " . $e->getMessage());
}
?>