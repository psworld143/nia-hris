<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$leave_id = $input['leave_id'] ?? '';
$table = $input['table'] ?? '';
$action = $input['action'] ?? '';
$reason = $input['reason'] ?? '';

if (empty($leave_id) || empty($table) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Get leave request details based on table
$leave = null;

if ($table === 'employee') {
    // Try employee leave requests table
    $employee_query = "SELECT elr.*, e.department, e.first_name, e.last_name, e.email 
                       FROM employee_leave_requests elr 
                       JOIN employees e ON elr.employee_id = e.id 
                       WHERE elr.id = ? AND elr.status = 'pending'";
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    mysqli_stmt_bind_param($employee_stmt, 'i', $leave_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if (mysqli_num_rows($employee_result) > 0) {
        $leave = mysqli_fetch_assoc($employee_result);
    }
} else if ($table === 'faculty') {
    // Try faculty leave requests table - only allow approval if already approved by head
    $faculty_query = "SELECT flr.*, e.department, e.first_name, e.last_name, e.email 
                      FROM employee_leave_requests flr 
                      JOIN employees f ON flr.employee_id = f.id 
                      WHERE flr.id = ? AND flr.status = 'approved_by_head'";
    $faculty_stmt = mysqli_prepare($conn, $faculty_query);
    mysqli_stmt_bind_param($faculty_stmt, 'i', $leave_id);
    mysqli_stmt_execute($faculty_stmt);
    $faculty_result = mysqli_stmt_get_result($faculty_stmt);
    
    if (mysqli_num_rows($faculty_result) > 0) {
        $leave = mysqli_fetch_assoc($faculty_result);
    }
}

if (!$leave) {
    echo json_encode(['success' => false, 'message' => 'Leave request not found or not ready for approval']);
    exit();
}

$hr_id = $_SESSION['user_id'];
$current_time = date('Y-m-d H:i:s');

// Update leave request based on action and table
if ($action === 'approve') {
    $new_status = 'approved_by_hr';
    $hr_approval = 'approved';
    $hr_comment = 'Approved by HR';
} else if ($action === 'reject') {
    $new_status = 'rejected';
    $hr_approval = 'rejected';
    $hr_comment = $reason ?: 'Rejected by HR';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Update the appropriate table
if ($table === 'employee') {
    $update_query = "UPDATE employee_leave_requests 
                     SET status = ?, hr_approval = ?, hr_comment = ?, hr_approver_id = ?, hr_approved_at = ?, updated_at = NOW() 
                     WHERE id = ?";
} else {
    $update_query = "UPDATE employee_leave_requests 
                     SET status = ?, hr_approval = ?, hr_comment = ?, hr_approver_id = ?, hr_approved_at = ?, updated_at = NOW() 
                     WHERE id = ?";
}

$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, 'sssisi', $new_status, $hr_approval, $hr_comment, $hr_id, $current_time, $leave_id);

if (mysqli_stmt_execute($update_stmt)) {
    $current_year = date('Y');
    
    // Get leave request details for balance update
    $leave_details_query = "SELECT employee_id, leave_type_id, total_days FROM employee_leave_requests WHERE id = ?";
    $leave_details_stmt = mysqli_prepare($conn, $leave_details_query);
    mysqli_stmt_bind_param($leave_details_stmt, 'i', $leave_id);
    mysqli_stmt_execute($leave_details_stmt);
    $leave_details_result = mysqli_stmt_get_result($leave_details_stmt);
    $leave_details = mysqli_fetch_assoc($leave_details_result);
    
    if ($leave_details) {
        $employee_id = $leave_details['employee_id'];
        $leave_type_id = $leave_details['leave_type_id'];
        $total_days = $leave_details['total_days'];
        
        if ($action === 'approve') {
            // Update leave balance when approving - deduct from available balance
            // Ensure leave allowance record exists
            $check_allowance_query = "SELECT id FROM employee_leave_allowances 
                                     WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
            $check_stmt = mysqli_prepare($conn, $check_allowance_query);
            mysqli_stmt_bind_param($check_stmt, 'iii', $employee_id, $leave_type_id, $current_year);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) == 0) {
                // Create missing allowance record using calculator
                require_once 'includes/leave_allowance_calculator_v2.php';
                $calculator = new LeaveAllowanceCalculatorV2($conn);
                $calculator->updateLeaveBalance($employee_id, 'employee', $current_year, $leave_type_id);
            }
            
            // Update leave balance - deduct used days
            $balance_query = "UPDATE employee_leave_allowances 
                             SET used_days = used_days + ?,
                                 remaining_days = total_days - (used_days + ?),
                                 updated_at = NOW()
                             WHERE employee_id = ? AND leave_type_id = ? AND year = ?";
            
            $balance_stmt = mysqli_prepare($conn, $balance_query);
            mysqli_stmt_bind_param($balance_stmt, 'ddiii', $total_days, $total_days, $employee_id, $leave_type_id, $current_year);
            
            if (mysqli_stmt_execute($balance_stmt)) {
                error_log("✅ Leave balance updated for approved request - ID: $leave_id, Employee: $employee_id, Days: $total_days");
            } else {
                error_log("❌ Error updating leave balance: " . mysqli_error($conn));
            }
        } else if ($action === 'reject') {
            // If rejected, no need to update balance since pending requests don't affect balance
            // Balance was never deducted for pending requests
            error_log("✅ Leave request rejected - no balance change needed (was pending)");
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Leave request ' . $action . 'd successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating leave request: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
