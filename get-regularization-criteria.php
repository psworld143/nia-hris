<?php
session_start();

require_once 'config/database.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_criteria':
                $employee_type = $_POST['employee_type'] ?? '';
                
                if (empty($employee_type)) {
                    echo json_encode(['success' => false, 'message' => 'Employee type is required']);
                    exit();
                }
                
                // Get criteria for the specified employee type
                $stmt = mysqli_prepare($conn, "SELECT * FROM regularization_criteria WHERE employee_type = ? AND is_active = 1 ORDER BY criteria_name");
                mysqli_stmt_bind_param($stmt, "s", $employee_type);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $criteria = mysqli_fetch_all($result, MYSQLI_ASSOC);
                
                echo json_encode(['success' => true, 'criteria' => $criteria]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// If not a POST request, return error
header('HTTP/1.1 400 Bad Request');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>
