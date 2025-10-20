<?php
/**
 * Add Medical History Record
 * AJAX endpoint for adding new medical history records
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user has permission (Super Admin or Nurse)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'nurse'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate inputs
$employee_id = intval($_POST['employee_id'] ?? 0);
$record_date = sanitize_input($_POST['record_date'] ?? '');
$record_type = sanitize_input($_POST['record_type'] ?? 'checkup');
$chief_complaint = sanitize_input($_POST['chief_complaint'] ?? '');
$diagnosis = sanitize_input($_POST['diagnosis'] ?? '');
$treatment = sanitize_input($_POST['treatment'] ?? '');
$medication_prescribed = sanitize_input($_POST['medication_prescribed'] ?? '');
$lab_results = sanitize_input($_POST['lab_results'] ?? '');
$doctor_name = sanitize_input($_POST['doctor_name'] ?? '');
$clinic_hospital = sanitize_input($_POST['clinic_hospital'] ?? '');
$follow_up_date = sanitize_input($_POST['follow_up_date'] ?? null);
$notes = sanitize_input($_POST['notes'] ?? '');

// Vital signs
$blood_pressure = sanitize_input($_POST['blood_pressure'] ?? '');
$heart_rate = intval($_POST['heart_rate'] ?? 0);
$temperature = floatval($_POST['temperature'] ?? 0);
$respiratory_rate = intval($_POST['respiratory_rate'] ?? 0);
$weight = floatval($_POST['weight'] ?? 0);
$height = floatval($_POST['height'] ?? 0);

// Build vital signs JSON
$vital_signs = null;
if ($blood_pressure || $heart_rate || $temperature) {
    $vitals = [];
    if ($blood_pressure) $vitals['blood_pressure'] = $blood_pressure;
    if ($heart_rate) $vitals['heart_rate'] = $heart_rate;
    if ($temperature) $vitals['temperature'] = $temperature;
    if ($respiratory_rate) $vitals['respiratory_rate'] = $respiratory_rate;
    if ($weight) $vitals['weight'] = $weight;
    if ($height) $vitals['height'] = $height;
    $vital_signs = json_encode($vitals);
}

// Validate required fields
if (!$employee_id || !$record_date || !$record_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Verify employee exists
$check_employee = mysqli_prepare($conn, "SELECT id FROM employees WHERE id = ?");
mysqli_stmt_bind_param($check_employee, "i", $employee_id);
mysqli_stmt_execute($check_employee);
$result = mysqli_stmt_get_result($check_employee);
if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}

// Insert medical history record
$insert_query = "INSERT INTO employee_medical_history (
    employee_id, record_date, record_type, chief_complaint, diagnosis, 
    treatment, medication_prescribed, lab_results, vital_signs, 
    doctor_name, clinic_hospital, follow_up_date, notes, recorded_by, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $insert_query);
$recorded_by = $_SESSION['user_id'];

mysqli_stmt_bind_param($stmt, "issssssssssssi",
    $employee_id, $record_date, $record_type, $chief_complaint, $diagnosis,
    $treatment, $medication_prescribed, $lab_results, $vital_signs,
    $doctor_name, $clinic_hospital, $follow_up_date, $notes, $recorded_by
);

if (mysqli_stmt_execute($stmt)) {
    // Log activity
    logActivity('ADD_MEDICAL_HISTORY', "Added medical history record for employee ID: $employee_id", $conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Medical history record added successfully',
        'record_id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Error adding medical history: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

