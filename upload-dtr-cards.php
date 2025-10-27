<?php
/**
 * Upload DTR Cards Handler
 * Processes multiple DTR card uploads per payroll period
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

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get payroll period
$payroll_period_id = intval($_POST['payroll_period_id'] ?? 0);

if (!$payroll_period_id) {
    echo json_encode(['success' => false, 'message' => 'Payroll period is required']);
    exit();
}

// Get period dates
$period_query = "SELECT start_date, end_date FROM payroll_periods WHERE id = ?";
$period_stmt = mysqli_prepare($conn, $period_query);
mysqli_stmt_bind_param($period_stmt, "i", $payroll_period_id);
mysqli_stmt_execute($period_stmt);
$period_result = mysqli_stmt_get_result($period_stmt);
$period = mysqli_fetch_assoc($period_result);

if (!$period) {
    echo json_encode(['success' => false, 'message' => 'Invalid payroll period']);
    exit();
}

$period_start = $period['start_date'];
$period_end = $period['end_date'];

// Process uploads
$employee_ids = $_POST['employee_id'] ?? [];
$upload_dir = __DIR__ . '/uploads/dtr-cards/';
$uploaded_count = 0;
$errors = [];

// Ensure upload directory exists
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

foreach ($employee_ids as $index => $employee_id) {
    $employee_id = intval($employee_id);
    
    if (!$employee_id) {
        continue;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['dtr_file']['name'][$index]) || $_FILES['dtr_file']['error'][$index] !== UPLOAD_ERR_OK) {
        $errors[] = "No file uploaded for employee ID: $employee_id";
        continue;
    }
    
    $file_name = $_FILES['dtr_file']['name'][$index];
    $file_tmp = $_FILES['dtr_file']['tmp_name'][$index];
    $file_size = $_FILES['dtr_file']['size'][$index];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($file_ext, $allowed_extensions)) {
        $errors[] = "Invalid file type for employee ID: $employee_id. Allowed: " . implode(', ', $allowed_extensions);
        continue;
    }
    
    // Validate file size (max 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        $errors[] = "File too large for employee ID: $employee_id. Max size: 5MB";
        continue;
    }
    
    // Generate unique filename
    $new_filename = 'dtr_' . $employee_id . '_' . $payroll_period_id . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $new_filename;
    $relative_path = 'uploads/dtr-cards/' . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Insert into database
        $insert_query = "INSERT INTO employee_dtr_cards (
            employee_id, payroll_period_id, period_start_date, period_end_date,
            file_path, file_name, file_size, uploaded_by, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        $uploaded_by = $_SESSION['user_id'];
        
        mysqli_stmt_bind_param($stmt, "iissssi",
            $employee_id, $payroll_period_id, $period_start, $period_end,
            $relative_path, $file_name, $file_size, $uploaded_by
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $uploaded_count++;
        } else {
            $errors[] = "Database error for employee ID: $employee_id";
            // Delete uploaded file if database insert failed
            unlink($file_path);
        }
    } else {
        $errors[] = "Failed to upload file for employee ID: $employee_id";
    }
}

// Return response
if ($uploaded_count > 0) {
    $message = "$uploaded_count DTR card(s) uploaded successfully";
    if (!empty($errors)) {
        $message .= ". " . count($errors) . " error(s) occurred.";
    }
    echo json_encode([
        'success' => true,
        'message' => $message,
        'uploaded' => $uploaded_count,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No DTR cards were uploaded',
        'errors' => $errors
    ]);
}

mysqli_close($conn);
?>

