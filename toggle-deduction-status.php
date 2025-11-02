<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $activate = $_POST['activate'] === 'true' ? 1 : 0;
    
    $query = "UPDATE payroll_deduction_types SET is_active = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $activate, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $action = $activate ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => "Deduction type $action successfully!"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update status'
        ]);
    }
}
?>

