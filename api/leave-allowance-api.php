<?php
session_start();
require_once '../config/database.php';
require_once '../includes/leave_allowance_calculator.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Initialize calculator
$calculator = new LeaveAllowanceCalculator($conn);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $calculator);
            break;
        case 'POST':
            handlePostRequest($action, $calculator);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest($action, $calculator) {
    global $conn;
    
    switch ($action) {
        case 'get_employee_balance':
            $employee_id = $_GET['employee_id'] ?? '';
            $employee_type = $_GET['employee_type'] ?? '';
            $year = $_GET['year'] ?? date('Y');
            $leave_type_id = $_GET['leave_type_id'] ?? 1;
            
            if (!$employee_id || !$employee_type) {
                throw new Exception('Employee ID and type are required');
            }
            
            $balance = $calculator->calculateLeaveAllowance($employee_id, $employee_type, $year, $leave_type_id);
            echo json_encode(['success' => true, 'data' => $balance]);
            break;
            
        case 'get_balance_summary':
            $year = $_GET['year'] ?? date('Y');
            $summary = $calculator->getLeaveBalanceSummary($year);
            echo json_encode(['success' => true, 'data' => $summary]);
            break;
            
        case 'get_accumulation_history':
            $employee_id = $_GET['employee_id'] ?? '';
            $employee_type = $_GET['employee_type'] ?? '';
            
            if (!$employee_id || !$employee_type) {
                throw new Exception('Employee ID and type are required');
            }
            
            $query = "SELECT lah.*, lt.name as leave_type_name
                      FROM leave_accumulation_history lah
                      JOIN leave_types lt ON lah.leave_type_id = lt.id
                      WHERE lah.employee_id = ? AND lah.employee_type = ?
                      ORDER BY lah.from_year DESC, lah.to_year DESC";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'is', $employee_id, $employee_type);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $history = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $history]);
            break;
            
        case 'get_regularization_status':
            $employee_id = $_GET['employee_id'] ?? '';
            $employee_type = $_GET['employee_type'] ?? '';
            
            if (!$employee_id || !$employee_type) {
                throw new Exception('Employee ID and type are required');
            }
            
            if ($employee_type === 'employee') {
                $query = "SELECT er.*, rs.name as status_name, rs.color as status_color
                         FROM employee_regularization er
                         LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                         WHERE er.employee_id = ? AND er.is_active = 1";
            } else {
                $query = "SELECT fr.*, rs.name as status_name, rs.color as status_color
                         FROM faculty_regularization fr
                         LEFT JOIN regularization_status rs ON fr.current_status_id = rs.id
                         WHERE fr.faculty_id = ? AND fr.is_active = 1";
            }
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'i', $employee_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $regularization = mysqli_fetch_assoc($result);
            
            echo json_encode(['success' => true, 'data' => $regularization]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}

function handlePostRequest($action, $calculator) {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'initialize_year':
            $year = $input['year'] ?? date('Y');
            $result = $calculator->initializeYearlyLeaveBalances($year);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'recalculate_employee':
            $employee_id = $input['employee_id'] ?? '';
            $employee_type = $input['employee_type'] ?? '';
            $year = $input['year'] ?? date('Y');
            $leave_type_id = $input['leave_type_id'] ?? 1;
            
            if (!$employee_id || !$employee_type) {
                throw new Exception('Employee ID and type are required');
            }
            
            $success = $calculator->updateLeaveBalance($employee_id, $employee_type, $year, $leave_type_id);
            echo json_encode(['success' => $success, 'message' => $success ? 'Leave balance updated successfully' : 'Failed to update leave balance']);
            break;
            
        case 'recalculate_all':
            $year = $input['year'] ?? date('Y');
            
            // Get all active employees
            $employees = [];
            $employee_query = "SELECT id FROM employees WHERE is_active = 1";
            $employee_result = mysqli_query($conn, $employee_query);
            while ($row = mysqli_fetch_assoc($employee_result)) {
                $employees[] = ['id' => $row['id'], 'type' => 'employee'];
            }
            
            // Get all active faculty
            $faculty_query = "SELECT id FROM faculty WHERE is_active = 1";
            $faculty_result = mysqli_query($conn, $faculty_query);
            while ($row = mysqli_fetch_assoc($faculty_result)) {
                $employees[] = ['id' => $row['id'], 'type' => 'faculty'];
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ($employees as $emp) {
                if ($calculator->updateLeaveBalance($emp['id'], $emp['type'], $year, 1)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'total_processed' => $success_count + $error_count
                ]
            ]);
            break;
            
        case 'update_leave_balance':
            $employee_id = $input['employee_id'] ?? '';
            $employee_type = $input['employee_type'] ?? '';
            $year = $input['year'] ?? date('Y');
            $leave_type_id = $input['leave_type_id'] ?? 1;
            $used_days = $input['used_days'] ?? 0;
            
            if (!$employee_id || !$employee_type) {
                throw new Exception('Employee ID and type are required');
            }
            
            // Update used days in the balance record
            $query = "UPDATE enhanced_leave_balances 
                     SET used_days = ?, remaining_days = (total_days - ?), updated_at = NOW()
                     WHERE employee_id = ? AND employee_type = ? AND year = ? AND leave_type_id = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'iisiii', $used_days, $used_days, $employee_id, $employee_type, $year, $leave_type_id);
            $success = mysqli_stmt_execute($stmt);
            
            echo json_encode(['success' => $success, 'message' => $success ? 'Leave balance updated successfully' : 'Failed to update leave balance']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
}
?>
