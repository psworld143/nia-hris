<?php
/**
 * Toggle User Status
 * Activate or deactivate user accounts
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check if Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$user_id = intval($input['user_id'] ?? 0);
$action = $input['action'] ?? '';

if (!$user_id || !in_array($action, ['activate', 'deactivate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Prevent deactivating yourself
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
    exit();
}

// Update status
$new_status = $action === 'activate' ? 'active' : 'inactive';
$update_query = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);

if (mysqli_stmt_execute($stmt)) {
    logActivity('TOGGLE_USER_STATUS', "User ID $user_id status changed to: $new_status", $conn);
    
    $message = $action === 'activate' ? 'User activated successfully' : 'User deactivated successfully';
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating user status']);
}

mysqli_close($conn);
?>

