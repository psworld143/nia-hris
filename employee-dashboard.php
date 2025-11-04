<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
require_once 'includes/roles.php';

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Employee Dashboard';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get user information including email and last login
$user_query = "SELECT email, updated_at FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
$user_email = $user_info['email'] ?? '';
$last_login = $user_info['updated_at'] ?? date('Y-m-d H:i:s');

// Get employee information by matching email
$employee_query = "SELECT e.*, d.name as department_name 
                   FROM employees e 
                   LEFT JOIN departments d ON e.department_id = d.id 
                   WHERE e.email = ? AND e.is_active = 1";
$employee_stmt = mysqli_prepare($conn, $employee_query);
mysqli_stmt_bind_param($employee_stmt, "s", $user_email);
mysqli_stmt_execute($employee_stmt);
$employee = mysqli_fetch_assoc(mysqli_stmt_get_result($employee_stmt));

// Get leave balance
$employee_id = $employee['id'] ?? 0;
$leave_balance_query = "SELECT 
    COALESCE(SUM(CASE WHEN lt.name = 'Vacation Leave' THEN ela.remaining_days ELSE 0 END), 0) as vacation_balance,
    COALESCE(SUM(CASE WHEN lt.name = 'Sick Leave' THEN ela.remaining_days ELSE 0 END), 0) as sick_balance,
    COALESCE(SUM(CASE WHEN lt.name = 'Emergency Leave' THEN ela.remaining_days ELSE 0 END), 0) as personal_balance
    FROM employee_leave_allowances ela
    LEFT JOIN leave_types lt ON ela.leave_type_id = lt.id
    WHERE ela.employee_id = ? AND ela.year = YEAR(CURDATE())";
$leave_stmt = mysqli_prepare($conn, $leave_balance_query);
mysqli_stmt_bind_param($leave_stmt, "i", $employee_id);
mysqli_stmt_execute($leave_stmt);
$leave_balance = mysqli_fetch_assoc(mysqli_stmt_get_result($leave_stmt));

// Get recent leave requests
$recent_leaves_query = "SELECT elr.*, lt.name as leave_type_name
                       FROM employee_leave_requests elr
                       LEFT JOIN leave_types lt ON elr.leave_type_id = lt.id
                       WHERE elr.employee_id = ? 
                       ORDER BY elr.created_at DESC LIMIT 5";
$recent_stmt = mysqli_prepare($conn, $recent_leaves_query);
mysqli_stmt_bind_param($recent_stmt, "i", $employee_id);
mysqli_stmt_execute($recent_stmt);
$recent_leaves = mysqli_fetch_all(mysqli_stmt_get_result($recent_stmt), MYSQLI_ASSOC);

// Get recent payslips (only those with actual payroll records)
$recent_payslips_query = "SELECT pp.*, pr.* 
                         FROM payroll_periods pp
                         INNER JOIN payroll_records pr ON pp.id = pr.payroll_period_id AND pr.employee_id = ?
                         WHERE pp.status IN ('approved', 'paid', 'closed')
                         ORDER BY pp.end_date DESC LIMIT 3";
$payslip_stmt = mysqli_prepare($conn, $recent_payslips_query);
mysqli_stmt_bind_param($payslip_stmt, "i", $employee_id);
mysqli_stmt_execute($payslip_stmt);
$recent_payslips = mysqli_fetch_all(mysqli_stmt_get_result($payslip_stmt), MYSQLI_ASSOC);

// Get additional employee statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN elr.status IN ('approved_by_hr', 'approved_by_head') THEN 1 END) as approved_leaves,
    COUNT(CASE WHEN elr.status = 'pending' THEN 1 END) as pending_leaves,
    SUM(CASE WHEN elr.status IN ('approved_by_hr', 'approved_by_head') THEN elr.total_days ELSE 0 END) as total_leave_days_used
    FROM employee_leave_requests elr 
    WHERE elr.employee_id = ? AND YEAR(elr.created_at) = YEAR(CURDATE())";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $employee_id);
mysqli_stmt_execute($stats_stmt);
$employee_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

// Get total years of service
$years_of_service = 0;
if ($employee && $employee['hire_date']) {
    $hire_date = new DateTime($employee['hire_date']);
    $current_date = new DateTime();
    $years_of_service = $current_date->diff($hire_date)->y;
}

// Include the header
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>
                <p class="text-green-100">Employee Portal - NIA Human Resource Information System</p>
                <?php if ($employee): ?>
                    <p class="text-sm text-green-200 mt-1">
                        Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?> | 
                        Department: <?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <p class="text-sm text-green-100">Last Login: <?php echo date('M d, Y H:i', strtotime($last_login)); ?></p>
                <p class="text-sm text-green-100">Role: Employee</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Leave Balance -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Vacation Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['vacation_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-heartbeat text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Sick Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['sick_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Emergency Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['personal_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mb-8">
        <!-- Recent Leave Requests -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Leave Requests</h3>
            <div class="space-y-3">
                <?php if (empty($recent_leaves)): ?>
                    <p class="text-gray-500 text-sm">No leave requests found</p>
                <?php else: ?>
                    <?php foreach ($recent_leaves as $leave): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($leave['leave_type_name'] ?? 'Leave'); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($leave['start_date'])); ?> - <?php echo date('M d, Y', strtotime($leave['end_date'])); ?></p>
                            </div>
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
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Payslips -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Payslips</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_payslips)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No payslips available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_payslips as $payslip): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d', strtotime($payslip['start_date'])); ?> - <?php echo date('M d, Y', strtotime($payslip['end_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['basic_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['net_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($payslip['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'approved': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'closed': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($payslip['status'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="payslip-view.php?period_id=<?php echo $payslip['payroll_period_id']; ?>&employee_id=<?php echo $employee_id; ?>" 
                                       target="_blank" class="text-blue-600 hover:text-blue-900">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Employee Statistics -->
    <?php if ($employee): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Statistics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600">Years of Service</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo $years_of_service; ?></p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-600">Approved Leaves (<?php echo date('Y'); ?>)</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $employee_stats['approved_leaves'] ?? 0; ?></p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-sm text-gray-600">Pending Leaves</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $employee_stats['pending_leaves'] ?? 0; ?></p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-600">Leave Days Used</p>
                <p class="text-2xl font-bold text-purple-600"><?php echo $employee_stats['total_leave_days_used'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personal Information Summary -->
    <?php if ($employee): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-600">Employee ID</p>
                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($employee['employee_id']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Department</p>
                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Position</p>
                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Employment Type</p>
                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($employee['employee_type'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Hire Date</p>
                <p class="font-semibold text-gray-900"><?php echo $employee['hire_date'] ? date('M d, Y', strtotime($employee['hire_date'])) : 'Not specified'; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Email</p>
                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($employee['email'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

