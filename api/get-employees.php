<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? '';

if (!in_array($type, ['employee', 'faculty'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid employee type']);
    exit();
}

try {
    if ($type === 'employee') {
        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, department, position 
                  FROM employees 
                  WHERE is_active = 1 
                  ORDER BY first_name, last_name";
    } else {
        $query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, department, position 
                  FROM faculty 
                  WHERE is_active = 1 
                  ORDER BY first_name, last_name";
    }
    
    $result = mysqli_query($conn, $query);
    $employees = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($employees);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
