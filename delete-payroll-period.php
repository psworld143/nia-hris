<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    
    // Check if period has records
    $check_query = "SELECT COUNT(*) as count FROM payroll_records WHERE payroll_period_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete period. It has $count payroll record(s). Delete the records first or change period status."
        ]);
        exit();
    }
    
    // Delete the period
    $delete_query = "DELETE FROM payroll_periods WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Payroll period deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete payroll period'
        ]);
    }
}
?>

