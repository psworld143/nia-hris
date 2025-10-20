<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$period_id = (int)($_GET['period_id'] ?? 0);

if ($period_id <= 0) {
    header('Location: payroll-management.php');
    exit();
}

// Get period details with statistics
$period_query = "SELECT 
    pp.*,
    u.first_name as created_by_fname,
    u.last_name as created_by_lname,
    COUNT(pr.id) as total_employees,
    SUM(pr.gross_pay) as total_gross,
    SUM(pr.total_deductions) as total_deductions,
    SUM(pr.net_pay) as total_net
FROM payroll_periods pp
LEFT JOIN users u ON pp.created_by = u.id
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
WHERE pp.id = ?
GROUP BY pp.id";

$stmt = mysqli_prepare($conn, $period_query);
mysqli_stmt_bind_param($stmt, "i", $period_id);
mysqli_stmt_execute($stmt);
$period = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$period) {
    $_SESSION['error_message'] = 'Payroll period not found.';
    header('Location: payroll-management.php');
    exit();
}

// Get all payroll records for this period
$records_query = "SELECT * FROM payroll_records WHERE payroll_period_id = ? ORDER BY employee_name";
$stmt = mysqli_prepare($conn, $records_query);
mysqli_stmt_bind_param($stmt, "i", $period_id);
mysqli_stmt_execute($stmt);
$records = mysqli_stmt_get_result($stmt);

$page_title = 'View Payroll - ' . $period['period_name'];
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-eye text-green-600 mr-2"></i>View Payroll Period
            </h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($period['period_name']); ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="payroll-management.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <?php if ($period['status'] !== 'closed'): ?>
                <a href="payroll-process.php?period_id=<?php echo $period_id; ?>" 
                   class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                    <i class="fas fa-edit mr-2"></i>Edit Payroll
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors font-medium">
                <i class="fas fa-print mr-2"></i>Print Summary
            </button>
        </div>
    </div>
</div>

<!-- Period Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $period['total_employees']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Gross Pay</p>
                <p class="text-xl font-bold text-gray-900">₱<?php echo number_format($period['total_gross'], 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-minus-circle text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Deductions</p>
                <p class="text-xl font-bold text-gray-900">₱<?php echo number_format($period['total_deductions'], 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-hand-holding-usd text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Net Pay</p>
                <p class="text-xl font-bold text-gray-900">₱<?php echo number_format($period['total_net'], 2); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Records Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Payroll Records</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deductions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($record = mysqli_fetch_assoc($records)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['employee_number']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($record['position']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs">
                                <div>Reg: <?php echo number_format($record['regular_hours'], 1); ?> hrs</div>
                                <?php if ($record['overtime_hours'] > 0): ?>
                                    <div>OT: <?php echo number_format($record['overtime_hours'], 1); ?> hrs</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-green-600">₱<?php echo number_format($record['gross_pay'], 2); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-red-600">₱<?php echo number_format($record['total_deductions'], 2); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-purple-600">₱<?php echo number_format($record['net_pay'], 2); ?></span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="payslip-view.php?period_id=<?php echo $period_id; ?>&employee_id=<?php echo $record['employee_id']; ?>" 
                               target="_blank"
                               class="text-green-600 hover:text-green-900 transition-colors" title="View Payslip">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

