<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Validate required parameters
    $upload_type = $_POST['upload_type'] ?? ''; // 'employee' or 'faculty'
    $record_id = (int)($_POST['record_id'] ?? 0);
    
    if (!in_array($upload_type, ['employee', 'faculty'])) {
        throw new Exception('Invalid upload type');
    }
    
    if ($record_id <= 0) {
        throw new Exception('Invalid record ID');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['photo'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
    }
    
    // Validate file size (2MB limit)
    $max_size = 2 * 1024 * 1024; // 2MB in bytes
    if ($file['size'] > $max_size) {
        throw new Exception('File size must be less than 2MB');
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = $upload_type . '_' . $record_id . '_' . time() . '.' . $file_extension;
    
    // Set upload directory
    $upload_dir = $upload_type === 'employee' ? '../uploads/employee_photos/' : '../uploads/faculty_photos/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $upload_path = $upload_dir . $unique_filename;
    $relative_path = ($upload_type === 'employee' ? 'uploads/employee_photos/' : 'uploads/faculty_photos/') . $unique_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    // Update database with photo path
    if ($upload_type === 'employee') {
        // Update employee_details table
        $update_query = "UPDATE employee_details SET profile_photo = ?, updated_by = ?, updated_at = NOW() WHERE employee_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'sii', $relative_path, $_SESSION['user_id'], $record_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            // Delete uploaded file if database update fails
            unlink($upload_path);
            throw new Exception('Failed to update employee photo in database');
        }
    } else {
        // Update faculty table
        $update_query = "UPDATE faculty SET image_url = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $relative_path, $record_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            // Delete uploaded file if database update fails
            unlink($upload_path);
            throw new Exception('Failed to update faculty photo in database');
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => ucfirst($upload_type) . ' photo uploaded successfully!',
        'photo_url' => $relative_path,
        'filename' => $unique_filename
    ]);
    
} catch (Exception $e) {
    error_log('Photo upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
