<?php
session_start();
require_once 'config/database.php';
require_once 'includes/roles.php';

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get user information including email
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
$user_email = $user_info['email'] ?? '';

// Get employee information by matching email
$employee_query = "SELECT e.*, d.name as department_name 
                   FROM employees e 
                   LEFT JOIN departments d ON e.department_id = d.id 
                   WHERE e.email = ? AND e.is_active = 1";
$employee_stmt = mysqli_prepare($conn, $employee_query);
mysqli_stmt_bind_param($employee_stmt, "s", $user_email);
mysqli_stmt_execute($employee_stmt);
$employee = mysqli_fetch_assoc(mysqli_stmt_get_result($employee_stmt));

if (!$employee) {
    die('Employee record not found');
}

$employee_id = $employee['id'];

// Get all leave requests for this employee
$leaves_query = "SELECT elr.*, lt.name as leave_type_name
                 FROM employee_leave_requests elr
                 LEFT JOIN leave_types lt ON elr.leave_type_id = lt.id
                 WHERE elr.employee_id = ? 
                 ORDER BY elr.created_at DESC";
$leaves_stmt = mysqli_prepare($conn, $leaves_query);
mysqli_stmt_bind_param($leaves_stmt, "i", $employee_id);
mysqli_stmt_execute($leaves_stmt);
$leave_requests = mysqli_fetch_all(mysqli_stmt_get_result($leaves_stmt), MYSQLI_ASSOC);

// Calculate statistics
$total_requests = count($leave_requests);
$approved_requests = count(array_filter($leave_requests, function($req) { 
    return in_array($req['status'], ['approved_by_hr', 'approved_by_head']); 
}));
$pending_requests = count(array_filter($leave_requests, function($req) { 
    return $req['status'] === 'pending'; 
}));
$rejected_requests = count(array_filter($leave_requests, function($req) { 
    return $req['status'] === 'rejected'; 
}));

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">Leave History</h1>
                <p class="text-orange-100">Leave Request History - NIA Human Resource Information System</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-orange-100">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <p class="text-sm text-orange-100">Department: <?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-list text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Requests</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $total_requests; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $approved_requests; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $pending_requests; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-times text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $rejected_requests; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-orange-600"></i>All Leave Requests
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($leave_requests)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No leave requests found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $leave): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($leave['leave_type_name'] ?? 'Leave'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['start_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $leave['total_days']; ?> day(s)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($leave['status']) {
                                            case 'approved_by_hr': echo 'bg-green-100 text-green-800'; break;
                                            case 'approved_by_head': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            case 'cancelled': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $leave['status'] ?? 'Unknown')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($leave['reason'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex justify-between">
        <a href="employee-dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
        <a href="create-leave-request-form.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i>Request New Leave
        </a>
    </div>
</div>
