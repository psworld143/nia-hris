<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get parameters
$employee_id = $_GET['employee_id'] ?? '';
$employee_type = $_GET['employee_type'] ?? '';
$all_time = $_GET['all_time'] ?? 'false';
$limit = $_GET['limit'] ?? 50;

// Validate parameters
if (empty($employee_id) || empty($employee_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

// Validate employee type
if (!in_array($employee_type, ['employees', 'employee'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid employee type']);
    exit();
}

// Validate employee ID
if (!is_numeric($employee_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid employee ID']);
    exit();
}

try {
    // Build query for leave history
    $where_conditions = ["elr.employee_id = ?"];
    $params = [$employee_id];
    $param_types = "i";
    
    if ($all_time !== 'true') {
        $where_conditions[] = "YEAR(elr.start_date) = ?";
        $params[] = date('Y');
        $param_types .= "i";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $query = "SELECT elr.*, lt.name as leave_type_name, lt.description as leave_type_description
              FROM employee_leave_requests elr
              LEFT JOIN leave_types lt ON elr.leave_type_id = lt.id
              $where_clause
              ORDER BY elr.start_date DESC
              LIMIT ?";
    
    $params[] = (int)$limit;
    $param_types .= "i";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $leave_history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $leave_history[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'employee_type' => $employee_type,
        'all_time' => $all_time === 'true',
        'limit' => (int)$limit,
        'leave_history' => $leave_history,
        'total_count' => count($leave_history)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
    error_log("Error in get-leave-history.php: " . $e->getMessage());
}
?>
