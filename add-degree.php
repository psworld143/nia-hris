<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action']);
    $degree_name = sanitize_input($_POST['degree_name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($degree_name)) {
        $_SESSION['error_message'] = 'Degree name is required.';
        header('Location: manage-degrees.php');
        exit();
    }
    
    if ($action === 'add') {
        // Check if degree already exists
        $check_query = "SELECT id FROM degrees WHERE degree_name = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $degree_name);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error_message'] = 'A degree with this name already exists.';
            header('Location: manage-degrees.php');
            exit();
        }
        
        // Insert new degree
        $insert_query = "INSERT INTO degrees (degree_name, description, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssiii", $degree_name, $description, $sort_order, $is_active, $user_id);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $_SESSION['success_message'] = 'Degree added successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to add degree. Please try again.';
        }
        
    } elseif ($action === 'edit') {
        $degree_id = (int)$_POST['degree_id'];
        
        // Check if another degree with same name exists (excluding current)
        $check_query = "SELECT id FROM degrees WHERE degree_name = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $degree_name, $degree_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error_message'] = 'A degree with this name already exists.';
            header('Location: manage-degrees.php');
            exit();
        }
        
        // Update degree
        $update_query = "UPDATE degrees SET degree_name = ?, description = ?, sort_order = ?, is_active = ?, updated_by = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssiiii", $degree_name, $description, $sort_order, $is_active, $user_id, $degree_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success_message'] = 'Degree updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update degree. Please try again.';
        }
    }
}

header('Location: manage-degrees.php');
exit();
?>

