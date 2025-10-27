<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access',
        'debug' => [
            'session_user_id' => $_SESSION['user_id'] ?? 'not set',
            'session_role' => $_SESSION['role'] ?? 'not set',
            'session_data' => $_SESSION
        ]
    ]);
    exit();
}

// Check database connection
if (!$conn || mysqli_connect_errno()) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request - user_id required']);
    exit();
}

$user_id = (int)$input['user_id'];

// Validate user ID
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // Check if user exists
    $check_query = "SELECT id, username, first_name, last_name FROM users WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($conn)]);
        exit();
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (!$user = mysqli_fetch_assoc($check_result)) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Hash the new password
    $new_password = 'NIA2025';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    if (!$hashed_password) {
        echo json_encode(['success' => false, 'message' => 'Password hashing failed']);
        exit();
    }
    
    // Update password
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    
    if (!$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Update prepare failed: ' . mysqli_error($conn)]);
        exit();
    }
    
    mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => "Password reset successfully for {$user['username']}"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset password: ' . mysqli_error($conn)]);
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while resetting password: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
