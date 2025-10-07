<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $code = strtoupper(sanitize_input($_POST['code']));
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description'] ?? '');
    $category = $_POST['category'];
    $is_percentage = isset($_POST['is_percentage']) ? 1 : 0;
    $default_value = floatval($_POST['default_value'] ?? 0);
    $sort_order = intval($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'add') {
        // Check if code already exists
        $check_query = "SELECT id FROM payroll_deduction_types WHERE code = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $code);
        mysqli_stmt_execute($check_stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
            $_SESSION['error_message'] = 'A deduction with this code already exists.';
            header('Location: manage-payroll-deductions.php');
            exit();
        }
        
        $query = "INSERT INTO payroll_deduction_types (code, name, description, category, is_percentage, default_value, sort_order, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssidii", $code, $name, $description, $category, $is_percentage, $default_value, $sort_order, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = 'Deduction type added successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to add deduction type.';
        }
        
    } elseif ($action === 'edit') {
        $deduction_id = (int)$_POST['deduction_id'];
        
        $query = "UPDATE payroll_deduction_types 
                  SET code = ?, name = ?, description = ?, category = ?, is_percentage = ?, default_value = ?, sort_order = ?, is_active = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssidiii", $code, $name, $description, $category, $is_percentage, $default_value, $sort_order, $is_active, $deduction_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = 'Deduction type updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update deduction type.';
        }
    }
}

header('Location: manage-payroll-deductions.php');
exit();
?>

