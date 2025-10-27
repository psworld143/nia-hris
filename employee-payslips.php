<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$page_title = 'My Payslips';

// Get user information including email
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
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

// Get all payslips for this employee (only those with actual payroll records)
$payslips_query = "SELECT pp.*, pr.* 
                   FROM payroll_periods pp
                   INNER JOIN payroll_records pr ON pp.id = pr.payroll_period_id AND pr.employee_id = ?
                   WHERE pp.status IN ('approved', 'paid', 'closed')
                   ORDER BY pp.end_date DESC";
$payslips_stmt = mysqli_prepare($conn, $payslips_query);
mysqli_stmt_bind_param($payslips_stmt, "i", $employee_id);
mysqli_stmt_execute($payslips_stmt);
$payslips = mysqli_fetch_all(mysqli_stmt_get_result($payslips_stmt), MYSQLI_ASSOC);

// Include the header
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">My Payslips</h1>
                <p class="text-green-100">Employee Payslip History</p>
                <p class="text-sm text-green-200 mt-1">
                    Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?> | 
                    Name: <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                </p>
            </div>
            <div class="text-right">
                <a href="employee-dashboard.php" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Payslips Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Payslip History</h3>
            <p class="text-sm text-gray-600">Total payslips: <?php echo count($payslips); ?></p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($payslips)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">No payslips available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payslips as $payslip): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($payslip['period_name'] ?? 'Period'); ?></div>
                                        <div class="text-gray-500">
                                            <?php echo date('M d', strtotime($payslip['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($payslip['end_date'])); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['basic_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['overtime_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['allowances'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                    ₱<?php echo number_format($payslip['gross_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₱<?php echo number_format($payslip['total_deductions'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold text-green-600">
                                    ₱<?php echo number_format($payslip['net_pay'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($payslip['status'] ?? '') {
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
                                       target="_blank" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Statistics -->
    <?php if (!empty($payslips)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payslip Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-600">Total Payslips</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo count($payslips); ?></p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-600">Total Gross Pay</p>
                <p class="text-2xl font-bold text-green-600">
                    ₱<?php echo number_format(array_sum(array_column($payslips, 'gross_pay')), 2); ?>
                </p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-600">Total Net Pay</p>
                <p class="text-2xl font-bold text-purple-600">
                    ₱<?php echo number_format(array_sum(array_column($payslips, 'net_pay')), 2); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
