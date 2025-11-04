<?php
/**
 * Fix Medical Certificates Upload Folder Permissions
 * This script sets proper permissions for the medical certificates upload folder and files
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user has permission (Super Admin or Nurse)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'nurse'])) {
    die('Unauthorized access');
}

$upload_dir = __DIR__ . '/uploads/medical-certificates/';
$results = [];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Medical Upload Permissions</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { padding: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { padding: 15px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { padding: 15px; background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { padding: 15px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 20px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Fix Medical Certificates Upload Permissions</h1>";

// Check if directory exists
if (!is_dir($upload_dir)) {
    echo "<div class='error'>";
    echo "<h3>❌ Directory Not Found</h3>";
    echo "<p>The upload directory does not exist: <code>$upload_dir</code></p>";
    echo "<p>Please run <a href='create-medical-attachments-table.php'>create-medical-attachments-table.php</a> first to create the directory.</p>";
    echo "</div>";
    echo "</div></body></html>";
    exit();
}

// Get current permissions
$current_dir_perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
$is_writable = is_writable($upload_dir);

echo "<div class='info'>";
echo "<h3>📁 Current Status</h3>";
echo "<p><strong>Directory:</strong> <code>$upload_dir</code></p>";
echo "<p><strong>Current Permissions:</strong> <code>$current_dir_perms</code></p>";
echo "<p><strong>Is Writable:</strong> " . ($is_writable ? "✅ Yes" : "❌ No") . "</p>";
echo "</div>";

// Fix directory permissions
echo "<h2>1. Fixing Directory Permissions</h2>";
if (chmod($upload_dir, 0755)) {
    $new_perms = substr(sprintf('%o', fileperms($upload_dir)), -4);
    echo "<div class='success'>";
    echo "<p>✅ Directory permissions changed from <code>$current_dir_perms</code> to <code>$new_perms</code></p>";
    echo "<p><strong>Permissions:</strong> 0755 (rwxr-xr-x) - Owner can read/write/execute, Group and Others can read/execute</p>";
    echo "</div>";
    $results[] = "Directory permissions fixed";
} else {
    echo "<div class='error'>";
    echo "<p>❌ Failed to change directory permissions. You may need to run this script with appropriate privileges.</p>";
    echo "</div>";
    $results[] = "Directory permissions fix failed";
}

// Fix file permissions
echo "<h2>2. Fixing File Permissions</h2>";
$files = glob($upload_dir . '*');
$files_fixed = 0;
$files_skipped = 0;

if (empty($files)) {
    echo "<div class='info'>";
    echo "<p>📂 No files found in the directory.</p>";
    echo "</div>";
} else {
    foreach ($files as $file) {
        if (is_file($file)) {
            $old_perms = substr(sprintf('%o', fileperms($file)), -4);
            if (chmod($file, 0644)) {
                $new_perms = substr(sprintf('%o', fileperms($file)), -4);
                $files_fixed++;
                $results[] = "File: " . basename($file) . " - Changed from $old_perms to $new_perms";
            } else {
                $files_skipped++;
                $results[] = "File: " . basename($file) . " - Failed to change permissions";
            }
        }
    }
    
    if ($files_fixed > 0) {
        echo "<div class='success'>";
        echo "<p>✅ Fixed permissions on <strong>$files_fixed</strong> file(s) to 0644</p>";
        echo "<p><strong>Permissions:</strong> 0644 (rw-r--r--) - Owner can read/write, Group and Others can read</p>";
        echo "</div>";
    }
    
    if ($files_skipped > 0) {
        echo "<div class='warning'>";
        echo "<p>⚠ Could not change permissions on <strong>$files_skipped</strong> file(s)</p>";
        echo "</div>";
    }
}

// Verify permissions
echo "<h2>3. Verification</h2>";
$is_writable_after = is_writable($upload_dir);
$final_perms = substr(sprintf('%o', fileperms($upload_dir)), -4);

if ($is_writable_after && $final_perms == '0755') {
    echo "<div class='success'>";
    echo "<h3>✅ All Permissions Fixed Successfully!</h3>";
    echo "<p>Directory is now writable and has correct permissions.</p>";
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Verification Warning</h3>";
    echo "<p>Directory permissions: <code>$final_perms</code></p>";
    echo "<p>Is writable: " . ($is_writable_after ? "Yes" : "No") . "</p>";
    if (!$is_writable_after) {
        echo "<p><strong>Note:</strong> If the directory is still not writable, you may need to:</p>";
        echo "<ul>";
        echo "<li>Run this script with appropriate privileges (sudo/chmod)</li>";
        echo "<li>Change ownership: <code>chown -R www-data:www-data $upload_dir</code></li>";
        echo "<li>Or set permissions to 0777: <code>chmod -R 0777 $upload_dir</code></li>";
        echo "</ul>";
    }
    echo "</div>";
}

// Summary
echo "<h2>📋 Summary</h2>";
echo "<div class='info'>";
echo "<ul>";
foreach ($results as $result) {
    echo "<li>$result</li>";
}
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='medical-records.php' class='btn'>← Back to Medical Records</a></p>";

echo "</div></body></html>";
?>

