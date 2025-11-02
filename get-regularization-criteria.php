<?php
session_start();

require_once 'config/database.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
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
                // Current schema has no employee_type column; return all active criteria
                $stmt = mysqli_prepare($conn, "SELECT * FROM regularization_criteria WHERE is_active = 1 ORDER BY criteria_name");
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
