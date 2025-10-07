<?php
// Prevent any HTML output before JSON response
ob_start();

session_start();
require_once 'config/database.php';

// Set JSON response header early
header('Content-Type: application/json; charset=utf-8');

// Wrap entire script in try-catch for proper error handling
try {
    // Enable error logging for debugging
    error_log("=== LEAVE ADDITION REQUEST START ===");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
    error_log("User Role: " . ($_SESSION['role'] ?? 'Not set'));

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    error_log("❌ AUTHORIZATION ERROR: Unauthorized access attempt");
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("❌ METHOD ERROR: Invalid request method - " . $_SERVER['REQUEST_METHOD']);
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$employee_id = $_POST['employee_id'] ?? '';
$leave_type_id = $_POST['leave_type_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = $_POST['reason'] ?? '';

// Log received form data
error_log("Form Data Received:");
error_log("  Employee ID: " . $employee_id);
error_log("  Leave Type ID: " . $leave_type_id);
error_log("  Start Date: " . $start_date);
error_log("  End Date: " . $end_date);
error_log("  Reason: " . substr($reason, 0, 100) . (strlen($reason) > 100 ? '...' : ''));

// Validate required fields
if (empty($employee_id) || empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
    error_log("❌ VALIDATION ERROR: Missing required fields");
    error_log("  Employee ID empty: " . (empty($employee_id) ? 'Yes' : 'No'));
    error_log("  Leave Type ID empty: " . (empty($leave_type_id) ? 'Yes' : 'No'));
    error_log("  Start Date empty: " . (empty($start_date) ? 'Yes' : 'No'));
    error_log("  End Date empty: " . (empty($end_date) ? 'Yes' : 'No'));
    error_log("  Reason empty: " . (empty($reason) ? 'Yes' : 'No'));
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    error_log("❌ DATE VALIDATION ERROR: Start date after end date");
    error_log("  Start Date: " . $start_date);
    error_log("  End Date: " . $end_date);
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Start date cannot be after end date']);
    exit();
}

// Calculate total days
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = $start->diff($end);
$total_days = $interval->days + 1; // Include both start and end dates

error_log("Date Calculation:");
error_log("  Start Date: " . $start_date);
error_log("  End Date: " . $end_date);
error_log("  Total Days: " . $total_days);

// Get employee and leave type details - check both tables
$employee = null;
$source_table = null;

// First check employees table - using COLLATE to prevent collation mismatch
error_log("Checking employees table for ID: " . $employee_id);
$employee_query = "SELECT e.*, dh.id as department_head_id 
                   FROM employees e 
                   LEFT JOIN department_heads dh ON e.department COLLATE utf8mb4_general_ci = dh.department COLLATE utf8mb4_general_ci
                   WHERE e.id = ? AND e.is_active = 1";
$employee_stmt = mysqli_prepare($conn, $employee_query);
if (!$employee_stmt) {
    error_log("❌ EMPLOYEE QUERY PREPARE ERROR: " . mysqli_error($conn));
    throw new Exception("Failed to prepare employee query: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($employee_stmt, 'i', $employee_id);
mysqli_stmt_execute($employee_stmt);
$employee_result = mysqli_stmt_get_result($employee_stmt);

if (mysqli_num_rows($employee_result) > 0) {
    $employee = mysqli_fetch_assoc($employee_result);
    $source_table = 'employee'; // Singular table name
} else {
    // Check employee table - using COLLATE to fix collation mismatch
    error_log("Employee not found in employees table, checking employee table for ID: " . $employee_id);
    $employee_query = "SELECT f.*, dh.id as department_head_id 
                      FROM employees f 
                      LEFT JOIN department_heads dh ON e.department COLLATE utf8mb4_general_ci = dh.department COLLATE utf8mb4_general_ci
                      WHERE e.id = ? AND f.is_active = 1";
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    if (!$employee_stmt) {
        error_log("❌ FACULTY QUERY PREPARE ERROR: " . mysqli_error($conn));
        throw new Exception("Failed to prepare employee query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($employee_stmt, 'i', $employee_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if (mysqli_num_rows($employee_result) > 0) {
        $employee = mysqli_fetch_assoc($employee_result);
        $source_table = 'employee'; // Correct table name
        error_log("✅ Employee found: " . ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
    } else {
        error_log("❌ Employee not found in employee table");
    }
}

if (!$employee) {
    error_log("❌ EMPLOYEE ERROR: Employee not found or inactive");
    error_log("  Searched Employee ID: " . $employee_id);
    error_log("  Source Table: " . ($source_table ?? 'Not determined'));
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Employee not found or inactive']);
    exit();
}

error_log("✅ Employee Found:");
error_log("  Name: " . ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
error_log("  Source Table: " . $source_table);
error_log("  Department: " . ($employee['department'] ?? 'Not set'));

// Get leave type details
$leave_type_query = "SELECT * FROM leave_types WHERE id = ? AND is_active = 1";
$leave_type_stmt = mysqli_prepare($conn, $leave_type_query);
if (!$leave_type_stmt) {
    error_log("❌ LEAVE TYPE QUERY PREPARE ERROR: " . mysqli_error($conn));
    throw new Exception("Failed to prepare leave type query: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($leave_type_stmt, 'i', $leave_type_id);
mysqli_stmt_execute($leave_type_stmt);
$leave_type_result = mysqli_stmt_get_result($leave_type_stmt);

if (mysqli_num_rows($leave_type_result) === 0) {
    error_log("❌ LEAVE TYPE ERROR: Leave type not found or inactive");
    error_log("  Leave Type ID: " . $leave_type_id);
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Leave type not found or inactive']);
    exit();
}

$leave_type = mysqli_fetch_assoc($leave_type_result);
error_log("✅ Leave Type Found:");
error_log("  Leave Type: " . ($leave_type['name'] ?? 'Unknown'));
error_log("  Default Days: " . ($leave_type['default_days_per_year'] ?? 'Not set'));

// Check leave balance based on source table - using new allowance tables
$balance_query = "";
if ($source_table === 'employee') {
    $balance_query = "SELECT total_days, used_days, (total_days - used_days) as remaining_days 
                      FROM employee_leave_allowances 
                      WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
} else {
    $balance_query = "SELECT total_days, used_days, (total_days - used_days) as remaining_days 
                      FROM employee_leave_allowances 
                      WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
}

$balance_stmt = mysqli_prepare($conn, $balance_query);
$current_year = date('Y');
mysqli_stmt_bind_param($balance_stmt, 'iii', $employee_id, $leave_type_id, $current_year);
mysqli_stmt_execute($balance_stmt);
$balance_result = mysqli_stmt_get_result($balance_stmt);

if (mysqli_num_rows($balance_result) === 0) {
    // No balance found - this should be initialized by the leave allowance calculator
    // For now, use default leave days, but recommend running the calculator first
    $remaining_days = $leave_type['default_days_per_year'];
    
    // Log that balance needs to be initialized
    error_log("Leave balance not found for " . ($source_table === 'employee' ? 'employee' : 'employee') . " ID: $employee_id, Year: $current_year. Using default: $remaining_days days.");
} else {
    $balance = mysqli_fetch_assoc($balance_result);
    $remaining_days = $balance['remaining_days'];
}

// Check if employee has enough leave balance
error_log("Leave Balance Check:");
error_log("  Available Days: " . $remaining_days);
error_log("  Requested Days: " . $total_days);
error_log("  Sufficient Balance: " . ($total_days <= $remaining_days ? 'Yes' : 'No'));

if ($total_days > $remaining_days) {
    error_log("❌ INSUFFICIENT BALANCE ERROR");
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => "Insufficient leave balance. Available: $remaining_days days, Requested: $total_days days"]);
    exit();
}

// Check for overlapping leave requests based on source table
$overlap_query = "";
if ($source_table === 'employee') {
    $overlap_query = "SELECT id FROM employee_leave_requests 
                      WHERE employee_id = ? AND status NOT IN ('rejected', 'cancelled')
                      AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (start_date <= ? AND end_date >= ?))";
} else {
    $overlap_query = "SELECT id FROM employee_leave_requests 
                      WHERE employee_id = ? AND status NOT IN ('rejected', 'cancelled')
                      AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?) OR (start_date <= ? AND end_date >= ?))";
}

$overlap_stmt = mysqli_prepare($conn, $overlap_query);
mysqli_stmt_bind_param($overlap_stmt, 'issssss', $employee_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($overlap_stmt);
$overlap_result = mysqli_stmt_get_result($overlap_stmt);

if (mysqli_num_rows($overlap_result) > 0) {
    error_log("❌ OVERLAP ERROR: Leave request overlaps with existing leave");
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Leave request overlaps with existing approved or pending leave']);
    exit();
}

// Insert leave request based on source table - auto-approved since HR is creating it
$hr_id = $_SESSION['user_id']; // HR user ID for approval tracking
$current_timestamp = date('Y-m-d H:i:s');

if ($source_table === 'employee') {
    $insert_query = "INSERT INTO employee_leave_requests (
                        employee_id, leave_type_id, start_date, end_date, total_days, reason, 
                        status, department_head_approval, hr_approval, 
                        department_head_id, hr_approver_id, 
                        department_head_approved_at, hr_approved_at, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'approved_by_hr', 'approved', 'approved', ?, ?, ?, ?, NOW())";
} else {
    $insert_query = "INSERT INTO employee_leave_requests (
                        employee_id, leave_type_id, start_date, end_date, total_days, reason, 
                        status, department_head_approval, hr_approval,
                        department_head_id, hr_approver_id, 
                        department_head_approved_at, hr_approved_at, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'approved_by_hr', 'approved', 'approved', ?, ?, ?, ?, NOW())";
}

$insert_stmt = mysqli_prepare($conn, $insert_query);
if (!$insert_stmt) {
    error_log("❌ INSERT QUERY PREPARE ERROR: " . mysqli_error($conn));
    throw new Exception("Failed to prepare insert query: " . mysqli_error($conn));
}

// Handle foreign key constraints for employee - both department_head_id and hr_approver_id must reference employee table
$department_head_id = null;
$hr_approver_id = null;

if ($source_table === 'employee') {
    // For employee, set both IDs to NULL since HR is approving directly and constraints expect employee IDs
    $department_head_id = null;
    $hr_approver_id = null;
    error_log("✅ Employee leave: department_head_id and hr_approver_id set to NULL (HR direct approval)");
} else {
    // For employees, department_head_id may exist, but hr_approver_id in schema references employees.id
    // The HR session user may not have a corresponding employees record, which would violate FK constraints.
    // To avoid FK errors, set hr_approver_id to NULL unless you have a mapped employees.id for the HR approver.
    $department_head_id = $employee['department_head_id'] ?? null;
    if (!is_numeric($department_head_id)) {
        $department_head_id = null;
    }
    $hr_approver_id = null; // Avoid FK violation by defaulting to NULL
    error_log("✅ Employee leave: department_head_id = " . ($department_head_id ?? 'NULL') . ", hr_approver_id = NULL (avoiding FK violation)");
}

error_log("Insert Parameters:");
error_log("  Employee ID: " . $employee_id);
error_log("  Leave Type ID: " . $leave_type_id);
error_log("  Start Date: " . $start_date);
error_log("  End Date: " . $end_date);
error_log("  Total Days: " . $total_days);
error_log("  Reason Length: " . strlen($reason));
error_log("  Department Head ID: " . ($department_head_id ?? 'NULL'));
error_log("  HR Approver ID: " . ($hr_approver_id ?? 'NULL'));
error_log("  Timestamp: " . $current_timestamp);

// Bind parameters: employee/employee id (i), leave_type_id (i), dates (s,s), total_days (i), reason (s), dept_head_id (i), hr_approver_id (i), timestamps (s,s)
if (!mysqli_stmt_bind_param($insert_stmt, 'iissisiiss', $employee_id, $leave_type_id, $start_date, $end_date, $total_days, $reason, $department_head_id, $hr_approver_id, $current_timestamp, $current_timestamp)) {
    error_log("❌ PARAMETER BINDING ERROR: " . mysqli_error($conn));
    throw new Exception("Failed to bind parameters: " . mysqli_error($conn));
}

error_log("Parameters bound successfully, executing insert...");

if (mysqli_stmt_execute($insert_stmt)) {
    $leave_request_id = mysqli_insert_id($conn);
    error_log("✅ Leave Request Inserted Successfully:");
    error_log("  Leave Request ID: " . $leave_request_id);
    error_log("  Status: approved_by_hr (auto-approved)");
    
    // Ensure leave allowance record exists before updating
    if ($source_table === 'employee') {
        $check_allowance_query = "SELECT id FROM employee_leave_allowances 
                                  WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
        $table_name = 'employee_leave_allowances';
        $id_field = 'employee_id';
    } else {
        $check_allowance_query = "SELECT id FROM employee_leave_allowances 
                                  WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
        $table_name = 'employee_leave_allowances';
        $id_field = 'employee_id';
    }
    
    $check_stmt = mysqli_prepare($conn, $check_allowance_query);
    mysqli_stmt_bind_param($check_stmt, 'iii', $employee_id, $leave_type_id, $current_year);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        error_log("⚠️ MISSING ALLOWANCE RECORD: Creating allowance record for " . $source_table . " ID " . $employee_id . " for leave type " . $leave_type_id);
        
        // Create missing allowance record using calculator
        require_once 'includes/leave_allowance_calculator_v2.php';
        $calculator = new LeaveAllowanceCalculatorV2($conn);
        $calculator->updateLeaveBalance($employee_id, $source_table, $current_year, $leave_type_id);
        
        error_log("✅ ALLOWANCE RECORD CREATED: Record created for " . $source_table . " ID " . $employee_id);
    }
    
    // Update leave balance in allowance tables
    if ($source_table === 'employee') {
        $update_balance_query = "UPDATE employee_leave_allowances 
                                 SET used_days = used_days + ?, 
                                     remaining_days = total_days - (used_days + ?),
                                     updated_at = NOW()
                                 WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
    } else {
        $update_balance_query = "UPDATE employee_leave_allowances 
                                 SET used_days = used_days + ?, 
                                     remaining_days = total_days - (used_days + ?),
                                     updated_at = NOW()
                                 WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
    }
    
    error_log("Updating leave balance:");
    error_log("  Table: " . ($source_table === 'employee' ? 'employee_leave_allowances' : 'employee_leave_allowances'));
    error_log("  Adding used days: " . $total_days);
    error_log("  Year: " . $current_year);
    
    $update_balance_stmt = mysqli_prepare($conn, $update_balance_query);
    mysqli_stmt_bind_param($update_balance_stmt, 'ddiii', $total_days, $total_days, $employee_id, $leave_type_id, $current_year);
    
    if (mysqli_stmt_execute($update_balance_stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($update_balance_stmt);
        error_log("✅ Leave Balance Updated:");
        error_log("  Affected Rows: " . $affected_rows);
        
        if ($affected_rows === 0) {
            error_log("⚠️ WARNING: No balance record found to update - this might be expected if balance hasn't been initialized");
        }
    } else {
        error_log("❌ BALANCE UPDATE ERROR: " . mysqli_error($conn));
    }
    
    error_log("✅ LEAVE ADDITION PROCESS COMPLETED SUCCESSFULLY");
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => true, 'message' => 'Leave added successfully and automatically approved']);
} else {
    error_log("❌ DATABASE INSERT ERROR: " . mysqli_error($conn));
    error_log("  SQL Error Code: " . mysqli_errno($conn));
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'Error creating leave request: ' . mysqli_error($conn)]);
}

} catch (Exception $e) {
    // Catch any unexpected errors and return proper JSON
    error_log("❌ UNEXPECTED ERROR: " . $e->getMessage());
    error_log("  File: " . $e->getFile());
    error_log("  Line: " . $e->getLine());
    error_log("  Stack Trace: " . $e->getTraceAsString());
    
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please check the error logs.']);
} catch (Error $e) {
    // Catch PHP fatal errors
    error_log("❌ FATAL ERROR: " . $e->getMessage());
    error_log("  File: " . $e->getFile());
    error_log("  Line: " . $e->getLine());
    
    ob_clean(); // Clear any output buffer
    echo json_encode(['success' => false, 'message' => 'A fatal error occurred. Please check the error logs.']);
}

mysqli_close($conn);
?>
