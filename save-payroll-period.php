<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $period_name = sanitize_input($_POST['period_name']);
    $period_type = $_POST['period_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $payment_date = $_POST['payment_date'];
    $notes = sanitize_input($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($action === 'create') {
        $query = "INSERT INTO payroll_periods (period_name, period_type, start_date, end_date, payment_date, notes, status, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $period_name, $period_type, $start_date, $end_date, $payment_date, $notes, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = 'Payroll period created successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to create payroll period.';
        }
    } elseif ($action === 'edit') {
        $period_id = (int)$_POST['period_id'];
        
        $query = "UPDATE payroll_periods 
                  SET period_name = ?, period_type = ?, start_date = ?, end_date = ?, payment_date = ?, notes = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $period_name, $period_type, $start_date, $end_date, $payment_date, $notes, $period_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = 'Payroll period updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update payroll period.';
        }
    }
}

header('Location: payroll-management.php');
exit();
?>

