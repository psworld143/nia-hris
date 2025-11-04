<?php
/**
 * Link Existing Medical Certificate Files to Database
 * This script scans the uploads/medical-certificates folder and links files to their medical history records
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user has permission (Super Admin or Nurse)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'nurse'])) {
    die('Unauthorized access');
}

// Check if table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'medical_history_attachments'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<h2>❌ Error: medical_history_attachments table does not exist!</h2>";
    echo "<p>Please run <a href='create-medical-attachments-table.php'>create-medical-attachments-table.php</a> first.</p>";
    exit();
}

$upload_dir = __DIR__ . '/uploads/medical-certificates/';
$linked_count = 0;
$error_count = 0;
$messages = [];

if (!is_dir($upload_dir)) {
    echo "<h2>❌ Error: Upload directory does not exist!</h2>";
    echo "<p>Directory: $upload_dir</p>";
    exit();
}

// Get all files in the directory
$files = glob($upload_dir . 'medical_cert_*');

echo "<h2>🔗 Linking Existing Medical Certificate Files</h2>";
echo "<p>Scanning directory: <code>$upload_dir</code></p>";
echo "<p>Found " . count($files) . " medical certificate file(s)</p>";
echo "<hr>";

foreach ($files as $file_path) {
    $filename = basename($file_path);
    
    // Parse filename: medical_cert_{record_id}_{employee_id}_{timestamp}_{index}.{ext}
    if (preg_match('/medical_cert_(\d+)_(\d+)_(\d+)_(\d+)\.(\w+)$/', $filename, $matches)) {
        $record_id = intval($matches[1]);
        $employee_id = intval($matches[2]);
        $timestamp = $matches[3];
        $index = $matches[4];
        $extension = $matches[5];
        
        // Check if record exists
        $check_record = mysqli_prepare($conn, "SELECT id FROM employee_medical_history WHERE id = ?");
        mysqli_stmt_bind_param($check_record, "i", $record_id);
        mysqli_stmt_execute($check_record);
        $record_result = mysqli_stmt_get_result($check_record);
        
        if (mysqli_num_rows($record_result) == 0) {
            $messages[] = "⚠ Skipping $filename - Medical history record ID $record_id not found";
            $error_count++;
            mysqli_stmt_close($check_record);
            continue;
        }
        mysqli_stmt_close($check_record);
        
        // Check if attachment already exists in database
        $relative_path = '/uploads/medical-certificates/' . $filename;
        $check_attachment = mysqli_prepare($conn, "SELECT id FROM medical_history_attachments WHERE file_path = ? OR file_path LIKE ?");
        $relative_path_alt = 'uploads/medical-certificates/' . $filename; // Try without leading slash too
        mysqli_stmt_bind_param($check_attachment, "ss", $relative_path, $relative_path_alt);
        mysqli_stmt_execute($check_attachment);
        $attachment_result = mysqli_stmt_get_result($check_attachment);
        
        if (mysqli_num_rows($attachment_result) > 0) {
            $messages[] = "✓ Skipping $filename - Already linked in database";
            mysqli_stmt_close($check_attachment);
            continue;
        }
        mysqli_stmt_close($check_attachment);
        
        // Get file info
        $file_size = filesize($file_path);
        $file_info = pathinfo($filename);
        // Try to get original filename from database or use a generic name
        $original_name = 'Medical Certificate ' . date('Y-m-d', $timestamp) . '.' . $file_info['extension'];
        
        // Try to find original filename from recent records
        $recent_query = mysqli_prepare($conn, "SELECT created_at FROM employee_medical_history WHERE id = ?");
        mysqli_stmt_bind_param($recent_query, "i", $record_id);
        mysqli_stmt_execute($recent_query);
        $recent_result = mysqli_stmt_get_result($recent_query);
        if ($recent_row = mysqli_fetch_assoc($recent_result)) {
            $original_name = 'Medical Certificate ' . date('Y-m-d', strtotime($recent_row['created_at'])) . '.' . $file_info['extension'];
        }
        mysqli_stmt_close($recent_query);
        
        // Determine file type
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $file_type = $mime_types[strtolower($extension)] ?? 'application/octet-stream';
        
        // Insert into database
        $insert_query = "INSERT INTO medical_history_attachments 
                        (medical_history_id, file_path, file_name, file_size, file_type, uploaded_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?))";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        
        if ($insert_stmt) {
            $uploaded_by = $_SESSION['user_id'];
            mysqli_stmt_bind_param($insert_stmt, "issisi", 
                $record_id, $relative_path, $original_name, $file_size, $file_type, $uploaded_by, $timestamp
            );
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $messages[] = "✅ Linked $filename to record ID $record_id";
                $linked_count++;
            } else {
                $messages[] = "❌ Error linking $filename: " . mysqli_error($conn);
                $error_count++;
            }
            
            mysqli_stmt_close($insert_stmt);
        } else {
            $messages[] = "❌ Error preparing statement for $filename: " . mysqli_error($conn);
            $error_count++;
        }
    } else {
        $messages[] = "⚠ Skipping $filename - Filename format not recognized (expected: medical_cert_{record_id}_{employee_id}_{timestamp}_{index}.{ext})";
        $error_count++;
    }
}

// Display results
echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>✅ Summary</h3>";
echo "<p><strong>Successfully linked:</strong> $linked_count file(s)</p>";
echo "<p><strong>Errors/Skipped:</strong> $error_count file(s)</p>";
echo "</div>";

echo "<h3>📋 Detailed Log:</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
foreach ($messages as $message) {
    echo "<p style='margin: 5px 0;'>$message</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><a href='medical-records.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Back to Medical Records</a></p>";

mysqli_close($conn);
?>

