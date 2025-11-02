<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deduction_id = (int)($_POST['id'] ?? 0);
    
    if (!$deduction_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid deduction ID']);
        exit();
    }
    
    // Check if deduction exists
    $check_query = "SELECT id, name FROM payroll_deduction_types WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $deduction_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Deduction type not found']);
        exit();
    }
    
    $deduction = mysqli_fetch_assoc($check_result);
    
    // Delete the deduction type
    $delete_query = "DELETE FROM payroll_deduction_types WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $deduction_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Deduction type "' . htmlspecialchars($deduction['name']) . '" deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete deduction type: ' . mysqli_error($conn)
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

