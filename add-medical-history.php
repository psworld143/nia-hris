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
    $record_id = mysqli_insert_id($conn);
    
    // Handle file uploads for medical certificates
    $uploaded_files = [];
    $upload_dir = __DIR__ . '/uploads/medical-certificates/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            chmod($upload_dir, 0755);
        }
    } else {
        // Ensure existing directory has correct permissions
        chmod($upload_dir, 0755);
    }
    
    // Process uploaded files
    if (isset($_FILES['medical_certificates']) && !empty($_FILES['medical_certificates']['name'][0])) {
        $files = $_FILES['medical_certificates'];
        $file_count = count($files['name']);
        
        // Allowed file types
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_file_size = 10 * 1024 * 1024; // 10MB
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                
                // Validate file size
                if ($file_size > $max_file_size) {
                    continue; // Skip files that are too large
                }
                
                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    continue; // Skip invalid file types
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = 'medical_cert_' . $record_id . '_' . $employee_id . '_' . time() . '_' . $i . '.' . $file_extension;
                $file_path = $upload_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Set proper file permissions (readable by web server, writable by owner)
                    chmod($file_path, 0644);
                    // Check if medical_history_attachments table exists
                    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'medical_history_attachments'");
                    if (mysqli_num_rows($table_check) > 0) {
                        // Save to database
                        $relative_path = '/uploads/medical-certificates/' . $unique_filename;
                        $attachment_query = "INSERT INTO medical_history_attachments 
                                            (medical_history_id, file_path, file_name, file_size, file_type, uploaded_by, created_at) 
                                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
                        $attachment_stmt = mysqli_prepare($conn, $attachment_query);
                        if ($attachment_stmt) {
                            $uploaded_by = $_SESSION['user_id'];
                            
                            mysqli_stmt_bind_param($attachment_stmt, "issisi", 
                                $record_id, $relative_path, $file_name, $file_size, $file_type, $uploaded_by
                            );
                            
                            if (mysqli_stmt_execute($attachment_stmt)) {
                                $uploaded_files[] = $file_name;
                            } else {
                                // Log error but don't fail the entire operation
                                error_log("Error inserting medical attachment: " . mysqli_error($conn) . " - File: $file_name");
                            }
                            
                            mysqli_stmt_close($attachment_stmt);
                        } else {
                            // Log error if statement preparation failed
                            error_log("Error preparing attachment statement: " . mysqli_error($conn) . " - File: $file_name");
                        }
                    } else {
                        // Table doesn't exist, but file was uploaded successfully
                        error_log("Warning: medical_history_attachments table does not exist. File uploaded but not linked: $file_name");
                        $uploaded_files[] = $file_name;
                    }
                }
            }
        }
    }
    
    // Log activity
    $attachment_info = !empty($uploaded_files) ? ' with ' . count($uploaded_files) . ' attachment(s)' : '';
    logActivity('ADD_MEDICAL_HISTORY', "Added medical history record for employee ID: $employee_id" . $attachment_info, $conn);
    
    $message = 'Medical history record added successfully';
    if (!empty($uploaded_files)) {
        $message .= ' with ' . count($uploaded_files) . ' certificate(s) attached';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'record_id' => $record_id,
        'attachments_count' => count($uploaded_files)
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

