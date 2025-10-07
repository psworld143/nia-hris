<?php
/**
 * NIA-HRIS System Test Script
 * This script tests the basic functionality of the standalone system
 */

echo "<h1>NIA-HRIS System Test</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
require_once 'config/database.php';

if ($conn) {
    echo "✓ Database connection successful<br>";
    
    // Test if tables exist
    $tables = ['users', 'settings', 'departments', 'employees', 'activity_log'];
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "✓ Table '$table' exists<br>";
        } else {
            echo "✗ Table '$table' missing<br>";
        }
    }
    
    // Test if admin user exists
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = 'admin'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Admin user created<br>";
    } else {
        echo "✗ Admin user missing<br>";
    }
    
    // Test settings
    $result = mysqli_query($conn, "SELECT * FROM settings");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Settings table populated<br>";
    } else {
        echo "✗ Settings table empty<br>";
    }
    
} else {
    echo "✗ Database connection failed<br>";
}

// Test file permissions
echo "<h2>File Permissions Test</h2>";
$directories = ['uploads', 'logs'];
foreach ($directories as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "✓ Directory '$dir' is writable<br>";
    } else {
        echo "✗ Directory '$dir' is not writable<br>";
    }
}

// Test includes
echo "<h2>Include Files Test</h2>";
foreach ($includes as $file) {
    if (file_exists($file)) {
        echo "✓ File '$file' exists<br>";
    } else {
        echo "✗ File '$file' missing<br>";
    }
}

echo "<h2>System Status</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Version: " . mysqli_get_server_info($conn) . "<br>";
echo "Current Time: " . date('Y-m-d H:i:s') . "<br>";

echo "<br><a href='login.php'>Go to Login Page</a>";
echo "<br><a href='index.php'>Go to Dashboard</a>";
?>
