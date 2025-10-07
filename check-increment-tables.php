<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Database Setup Check';

// Check if increment_requests tables exist
$tables_to_check = [
    'increment_types',
    'increment_approval_levels', 
    'increment_requests',
    'increment_request_approvals',
    'increment_request_history',
    'increment_request_comments',
    'increment_request_attachments'
];

$missing_tables = [];
$existing_tables = [];

foreach ($tables_to_check as $table) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        $existing_tables[] = $table;
    } else {
        $missing_tables[] = $table;
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Increment Requests Database Setup</h1>
        <p class="text-gray-600">Check and setup database tables for increment requests system</p>
    </div>

    <?php if (empty($missing_tables)): ?>
        <!-- All tables exist -->
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>Database Setup Complete!</strong> All increment requests tables are properly installed.
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Existing Tables</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($existing_tables as $table): ?>
                <div class="flex items-center p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-table text-green-600 mr-3"></i>
                    <span class="text-gray-900"><?php echo $table; ?></span>
                    <i class="fas fa-check text-green-600 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="flex space-x-4">
            <a href="increment-requests.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                Go to Increment Requests
            </a>
            <a href="create-increment-request.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                Create New Request
            </a>
        </div>
        
    <?php else: ?>
        <!-- Missing tables -->
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Database Setup Required!</strong> Some increment requests tables are missing.
            </div>
        </div>
        
        <?php if (!empty($existing_tables)): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Existing Tables</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($existing_tables as $table): ?>
                <div class="flex items-center p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-table text-green-600 mr-3"></i>
                    <span class="text-gray-900"><?php echo $table; ?></span>
                    <i class="fas fa-check text-green-600 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Missing Tables</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($missing_tables as $table): ?>
                <div class="flex items-center p-3 bg-red-50 rounded-lg">
                    <i class="fas fa-table text-red-600 mr-3"></i>
                    <span class="text-gray-900"><?php echo $table; ?></span>
                    <i class="fas fa-times text-red-600 ml-auto"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
            <h4 class="font-medium mb-2">Setup Instructions:</h4>
            <ol class="list-decimal list-inside space-y-1">
                <li>Open your MySQL/MariaDB database management tool (phpMyAdmin, MySQL Workbench, etc.)</li>
                <li>Navigate to your <strong>nia_hris</strong> database</li>
                <li>Import or run the SQL file: <code>database/increment_requests_system.sql</code></li>
                <li>Refresh this page to verify the installation</li>
            </ol>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">SQL File Location</h3>
            <div class="bg-gray-100 p-4 rounded-lg">
                <code class="text-sm">c:\xampp8.1.2\htdocs\seait-1\database\increment_requests_system.sql</code>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                This file contains all the necessary tables, sample data, and stored procedures for the increment requests system.
            </p>
        </div>
    <?php endif; ?>
</div>

