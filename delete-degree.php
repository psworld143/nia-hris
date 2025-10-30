<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role (include super_admin)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    
    // Check if degree is being used by any employees
    $check_query = "SELECT COUNT(*) as count FROM employee_details WHERE highest_education = (SELECT degree_name FROM degrees WHERE id = ?)";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $count = mysqli_fetch_assoc($check_result)['count'];
    
    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete degree. It is currently assigned to $count employee(s)."
        ]);
        exit();
    }
    
    // Delete the degree
    $delete_query = "DELETE FROM degrees WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        echo json_encode([
            'success' => true,
            'message' => 'Degree deleted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete degree'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>

