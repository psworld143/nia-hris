<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Payroll Reports';

// Get filter parameters
$filter_year = $_GET['year'] ?? date('Y');
$filter_month = $_GET['month'] ?? date('m');

// Get monthly summary
$summary_query = "SELECT 
    pp.id,
    pp.period_name,
    pp.start_date,
    pp.end_date,
    pp.payment_date,
    pp.status,
    COUNT(pr.id) as employee_count,
    SUM(pr.gross_pay) as total_gross,
    SUM(pr.total_deductions) as total_deductions,
    SUM(pr.net_pay) as total_net,
    SUM(pr.sss_contribution) as total_sss,
    SUM(pr.philhealth_contribution) as total_philhealth,
    SUM(pr.pagibig_contribution) as total_pagibig,
    SUM(pr.withholding_tax) as total_tax
FROM payroll_periods pp
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
WHERE YEAR(pp.start_date) = ? AND MONTH(pp.start_date) = ?
GROUP BY pp.id
ORDER BY pp.start_date DESC";

$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "ii", $filter_year, $filter_month);
mysqli_stmt_execute($stmt);
$summary_result = mysqli_stmt_get_result($stmt);

// Get year-to-date totals
$ytd_query = "SELECT 
    COUNT(DISTINCT pp.id) as total_periods,
    COUNT(DISTINCT pr.employee_id) as total_employees,
    SUM(pr.gross_pay) as ytd_gross,
    SUM(pr.total_deductions) as ytd_deductions,
    SUM(pr.net_pay) as ytd_net
FROM payroll_periods pp
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
WHERE YEAR(pp.start_date) = ?";

$stmt = mysqli_prepare($conn, $ytd_query);
mysqli_stmt_bind_param($stmt, "i", $filter_year);
mysqli_stmt_execute($stmt);
$ytd = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-bar text-green-600 mr-2"></i>Payroll Reports
            </h1>
            <p class="text-gray-600">Comprehensive payroll reports and analytics</p>
        </div>
        <a href="payroll-management.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors font-medium">
            <i class="fas fa-arrow-left mr-2"></i>Back to Payroll
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select name="year" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
            <select name="month" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo $filter_month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors font-medium">
                <i class="fas fa-filter mr-2"></i>Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Year-to-Date Summary -->
<div class="bg-gradient-to-r from-green-50 to-blue-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Year-to-Date Summary (<?php echo $filter_year; ?>)</h3>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div>
            <p class="text-xs text-gray-600">Periods Processed</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $ytd['total_periods']; ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600">Total Employees</p>
            <p class="text-xl font-bold text-gray-900"><?php echo $ytd['total_employees']; ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600">YTD Gross Pay</p>
            <p class="text-xl font-bold text-green-600">₱<?php echo number_format($ytd['ytd_gross'] ?? 0, 2); ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600">YTD Deductions</p>
            <p class="text-xl font-bold text-red-600">₱<?php echo number_format($ytd['ytd_deductions'] ?? 0, 2); ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600">YTD Net Pay</p>
            <p class="text-xl font-bold text-purple-600">₱<?php echo number_format($ytd['ytd_net'] ?? 0, 2); ?></p>
        </div>
    </div>
</div>

<!-- Monthly Payroll Summary -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">
            <?php echo date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)); ?> Payroll Summary
        </h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SSS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PhilHealth</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pag-IBIG</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Deductions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $month_total_gross = 0;
                $month_total_deductions = 0;
                $month_total_net = 0;
                $month_total_employees = 0;
                
                while ($summary = mysqli_fetch_assoc($summary_result)): 
                    $month_total_gross += $summary['total_gross'] ?? 0;
                    $month_total_deductions += $summary['total_deductions'] ?? 0;
                    $month_total_net += $summary['total_net'] ?? 0;
                    $month_total_employees += $summary['employee_count'] ?? 0;
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($summary['period_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo date('M j - M j', strtotime($summary['start_date'])) . ', ' . date('Y', strtotime($summary['end_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $summary['employee_count']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱<?php echo number_format($summary['total_gross'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format($summary['total_sss'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format($summary['total_philhealth'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format($summary['total_pagibig'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱<?php echo number_format($summary['total_tax'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600">₱<?php echo number_format($summary['total_deductions'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-purple-600">₱<?php echo number_format($summary['total_net'] ?? 0, 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <?php echo ucfirst($summary['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
                
                <!-- Totals Row -->
                <tr class="bg-gray-100 font-bold">
                    <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $month_total_employees; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">₱<?php echo number_format($month_total_gross, 2); ?></td>
                    <td class="px-6 py-4" colspan="4"></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">₱<?php echo number_format($month_total_deductions, 2); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-purple-600">₱<?php echo number_format($month_total_net, 2); ?></td>
                    <td class="px-6 py-4"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

