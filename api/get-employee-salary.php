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
$id = intval($_GET['id'] ?? 0);

if (!in_array($type, ['employee', 'faculty']) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

try {
    // For this example, we'll use a base salary calculation
    // In a real system, you'd have a salary table or field
    if ($type === 'employee') {
        $query = "SELECT 
                    CASE 
                        WHEN position LIKE '%Manager%' OR position LIKE '%Director%' THEN 65000
                        WHEN position LIKE '%Head%' OR position LIKE '%Supervisor%' THEN 55000
                        WHEN position LIKE '%Senior%' THEN 45000
                        ELSE 35000
                    END as base_salary,
                    DATEDIFF(CURRENT_DATE, hire_date) / 365 as years_service
                  FROM employees 
                  WHERE id = ? AND is_active = 1";
    } else {
        $query = "SELECT 
                    CASE 
                        WHEN position LIKE '%Professor%' THEN 60000
                        WHEN position LIKE '%Associate%' THEN 50000
                        WHEN position LIKE '%Assistant%' THEN 40000
                        WHEN position LIKE '%Instructor%' THEN 35000
                        ELSE 30000
                    END as base_salary,
                    DATEDIFF(CURRENT_DATE, hire_date) / 365 as years_service
                  FROM faculty 
                  WHERE id = ? AND is_active = 1";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    if ($data) {
        // Calculate current salary with years of service bonus
        $base_salary = floatval($data['base_salary']);
        $years_service = floatval($data['years_service']);
        $service_bonus = $years_service * 1000; // ₱1000 per year of service
        $current_salary = $base_salary + $service_bonus;
        
        header('Content-Type: application/json');
        echo json_encode([
            'salary' => number_format($current_salary, 2, '.', ''),
            'base_salary' => $base_salary,
            'years_service' => round($years_service, 1),
            'service_bonus' => $service_bonus
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
