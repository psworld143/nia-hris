<?php
/**
 * Delete Medical History Record
 * AJAX endpoint to delete a medical history record
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Check if user has permission to update medical records
if (!canUpdateMedicalRecords()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$record_id = intval($input['record_id'] ?? 0);

if (!$record_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
    exit();
}

// Get the record details first to verify it exists and for logging
$record_query = "SELECT emh.*, e.first_name, e.last_name, e.employee_id 
                 FROM employee_medical_history emh
                 JOIN employees e ON emh.employee_id = e.id
                 WHERE emh.id = ?";
$record_stmt = mysqli_prepare($conn, $record_query);
mysqli_stmt_bind_param($record_stmt, "i", $record_id);
mysqli_stmt_execute($record_stmt);
$record_result = mysqli_stmt_get_result($record_stmt);
$record = mysqli_fetch_assoc($record_result);

if (!$record) {
    echo json_encode(['success' => false, 'message' => 'Medical record not found']);
    exit();
}

// Delete the record
$delete_query = "DELETE FROM employee_medical_history WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);

if (!$delete_stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare delete query: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($delete_stmt, "i", $record_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Log the deletion
    $employee_name = $record['first_name'] . ' ' . $record['last_name'];
    $record_date = date('F j, Y', strtotime($record['record_date']));
    $record_type = str_replace('_', ' ', $record['record_type']);
    
    logActivity('DELETE_MEDICAL_HISTORY', 
        "Deleted medical history record for employee: {$employee_name} (ID: {$record['employee_id']}), Record: {$record_type} on {$record_date}", 
        $conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Medical record deleted successfully',
        'employee_id' => $record['employee_id']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete medical record: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($delete_stmt);
mysqli_close($conn);
?>

