<?php
/**
 * Save User (Create/Update)
 * Handles user creation and updates
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Set JSON header
header('Content-Type: application/json');

// Check if Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = intval($_POST['user_id'] ?? 0);

if ($action === 'create') {
    // Create new user
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $role = sanitize_input($_POST['role']);
    $status = sanitize_input($_POST['status']);
    $password = $_POST['password'];
    
    // Validate required fields
    if (!$username || !$email || !$first_name || !$last_name || !$password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Check if username exists
    $check_query = "SELECT id FROM users WHERE username = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $username);
    mysqli_stmt_execute($check_stmt);
    if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Check if email exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $check_email_stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($check_email_stmt, "s", $email);
    mysqli_stmt_execute($check_email_stmt);
    if (mysqli_stmt_get_result($check_email_stmt)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $insert_query = "INSERT INTO users (username, password, first_name, last_name, email, role, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "sssssss", $username, $hashed_password, $first_name, $last_name, $email, $role, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity('CREATE_USER', "Created new user: $username ($role)", $conn);
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating user: ' . mysqli_error($conn)]);
    }
    
} elseif ($action === 'edit') {
    // Update existing user
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $role = sanitize_input($_POST['role']);
    $status = sanitize_input($_POST['status']);
    $password = $_POST['password'] ?? '';
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit();
    }
    
    // Check if username exists for another user
    $check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "si", $username, $user_id);
    mysqli_stmt_execute($check_stmt);
    if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Check if email exists for another user
    $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_email_stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($check_email_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_email_stmt);
    if (mysqli_stmt_get_result($check_email_stmt)->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Update user
    if (!empty($password)) {
        // Update with password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET username = ?, password = ?, first_name = ?, last_name = ?, 
                        email = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "sssssssi", $username, $hashed_password, $first_name, $last_name, $email, $role, $status, $user_id);
    } else {
        // Update without password
        $update_query = "UPDATE users SET username = ?, first_name = ?, last_name = ?, 
                        email = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $username, $first_name, $last_name, $email, $role, $status, $user_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        logActivity('UPDATE_USER', "Updated user: $username ($role)", $conn);
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . mysqli_error($conn)]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

mysqli_close($conn);
?>

