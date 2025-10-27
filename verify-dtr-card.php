<?php
/**
 * Verify DTR Card
 * Marks DTR card as verified by authorized personnel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$dtr_id = intval($input['dtr_id'] ?? 0);

if (!$dtr_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid DTR ID']);
    exit();
}

// Update DTR status to verified
$update_query = "UPDATE employee_dtr_cards 
                 SET status = 'verified',
                     verified_by = ?,
                     verified_at = NOW()
                 WHERE id = ? AND status = 'pending'";

$stmt = mysqli_prepare($conn, $update_query);
$verified_by = $_SESSION['user_id'];
mysqli_stmt_bind_param($stmt, "ii", $verified_by, $dtr_id);

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    if ($affected_rows > 0) {
        // Log activity
        logActivity('VERIFY_DTR_CARD', "Verified DTR card ID: $dtr_id", $conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'DTR card verified successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'DTR card not found or already verified'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>

