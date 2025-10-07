<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $activate = $_POST['activate'] === 'true' ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    $query = "UPDATE degrees SET is_active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $activate, $user_id, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $action = $activate ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true,
            'message' => "Degree $action successfully!"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update degree status'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>

