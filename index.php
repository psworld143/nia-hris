<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse', 'employee'])) {
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Redirect employees to their dedicated dashboard
if ($_SESSION['role'] === 'employee') {
    header('Location: employee-dashboard.php');
    exit();
}

// Set page title
$page_title = 'NIA-HRIS Dashboard';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get comprehensive HR statistics
$stats = [];

// Get total employees count
$employees_query = "SELECT COUNT(*) as total FROM employees WHERE is_active = 1";
$employees_result = mysqli_query($conn, $employees_query);
if ($employees_result) {
    $stats['total_employees'] = mysqli_fetch_assoc($employees_result)['total'];
} else {
    $stats['total_employees'] = 0;
}

// Get total users count
$users_query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$users_result = mysqli_query($conn, $users_query);
$stats['total_users'] = mysqli_fetch_assoc($users_result)['total'];

// Get total departments count
$departments_query = "SELECT COUNT(*) as total FROM departments WHERE is_active = 1";
$departments_result = mysqli_query($conn, $departments_query);
$stats['total_departments'] = mysqli_fetch_assoc($departments_result)['total'];

// Get pending leave requests count
$pending_leaves_query = "SELECT COUNT(*) as total FROM employee_leave_requests WHERE status = 'pending'";
$pending_leaves_result = mysqli_query($conn, $pending_leaves_query);
if ($pending_leaves_result) {
    $stats['pending_leaves'] = mysqli_fetch_assoc($pending_leaves_result)['total'];
} else {
    $stats['pending_leaves'] = 0;
}

// Get recent activities (last 5 activities)
$recent_activities = [];
$activities_query = "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5";
$activities_result = mysqli_query($conn, $activities_query);
if ($activities_result) {
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $recent_activities[] = $row;
    }
}

// Include the header
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>
                <p class="text-blue-100">NIA Human Resource Information System</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-blue-100">Last Login: <?php echo date('M d, Y H:i'); ?></p>
                <p class="text-sm text-blue-100">Role: <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 ">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-user-tie text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_employees']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 ">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Departments</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_departments']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 ">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">System Users</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 ">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Leaves</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_leaves']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="admin-employee.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-user-plus text-blue-600 text-xl mr-3"></i>
                    <span class="text-blue-800 font-medium">Add Employee</span>
                </a>
                <a href="manage-departments.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-building text-green-600 text-xl mr-3"></i>
                    <span class="text-green-800 font-medium">Manage Departments</span>
                </a>
                <a href="leave-management.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                    <i class="fas fa-calendar-alt text-orange-600 text-xl mr-3"></i>
                    <span class="text-orange-800 font-medium">Leave Management</span>
                </a>
                <a href="salary-structures.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl mr-3"></i>
                    <span class="text-purple-800 font-medium">Salary Structures</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
            <div class="space-y-3">
                <?php if (empty($recent_activities)): ?>
                    <p class="text-gray-500 text-sm">No recent activities</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">System Version</p>
                <p class="text-lg font-semibold text-gray-900">NIA-HRIS v1.0</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Database Status</p>
                <p class="text-lg font-semibold text-green-600">Connected</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Server Time</p>
                <p class="text-lg font-semibold text-gray-900"><?php echo date('M d, Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
</div>

