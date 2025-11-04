<?php
/**
 * Create Medical History Attachments Table
 * This script creates a table to store multiple attachments for medical history records
 */

require_once 'config/database.php';

// Create medical_history_attachments table
$create_table = "CREATE TABLE IF NOT EXISTS medical_history_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medical_history_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_history_id) REFERENCES employee_medical_history(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_medical_history_id (medical_history_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $create_table)) {
    echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3 style='margin-top: 0;'>✓ Medical History Attachments Table Created Successfully!</h3>";
    echo "<p>The table has been created and is ready to store medical certificate attachments.</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3 style='margin-top: 0;'>✗ Error Creating Table</h3>";
    echo "<p>" . mysqli_error($conn) . "</p>";
    echo "</div>";
}

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/uploads/medical-certificates/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        chmod($upload_dir, 0755);
        echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
        echo "<p>✓ Upload directory created: uploads/medical-certificates/</p>";
        echo "<p>✓ Permissions set to 0755 (readable and executable by all, writable by owner)</p>";
        echo "</div>";
    } else {
        echo "<div style='padding: 20px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 5px; margin: 20px;'>";
        echo "<p>⚠ Warning: Could not create upload directory. Please create it manually: uploads/medical-certificates/</p>";
        echo "</div>";
    }
} else {
    // Fix permissions on existing directory
    chmod($upload_dir, 0755);
    $current_perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
    echo "<div style='padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
    echo "<p>✓ Upload directory already exists: uploads/medical-certificates/</p>";
    echo "<p>✓ Permissions updated to 0755 (was: $current_perms)</p>";
    
    // Also fix permissions on existing files
    $files = glob($upload_dir . '*');
    $files_fixed = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            chmod($file, 0644);
            $files_fixed++;
        }
    }
    if ($files_fixed > 0) {
        echo "<p>✓ Fixed permissions on $files_fixed existing file(s) to 0644</p>";
    }
    echo "</div>";
}

mysqli_close($conn);
?>

