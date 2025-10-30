<?php
// NIA-HRIS Database Configuration
// Standalone Human Resource Information System

$host = 'localhost';
$dbname = 'nia_hris';
$username = 'root';
$password = '';

// Create connection using XAMPP socket (for macOS)
$socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
$conn = @mysqli_connect($host, $username, $password, null, 3306, $socket);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$create_db_query = "CREATE DATABASE IF NOT EXISTS `nia_hris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
mysqli_query($conn, $create_db_query);

// Select the database
mysqli_select_db($conn, $dbname);

// Set charset to utf8mb4 to support 4-byte UTF-8 characters (emojis)
mysqli_set_charset($conn, "utf8mb4");

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Set MySQL session timezone to match PHP timezone
mysqli_query($conn, "SET time_zone = '+08:00'");
?>
