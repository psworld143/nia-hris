<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager', 'employee'])) {
    die('Unauthorized access');
}

$period_id = (int)($_GET['period_id'] ?? 0);
$employee_id = (int)($_GET['employee_id'] ?? 0);

if ($period_id <= 0 || $employee_id <= 0) {
    die('Invalid parameters');
}

// For employee role, ensure they can only view their own payslip
if ($_SESSION['role'] === 'employee') {
    // Get employee ID for current user
    $user_email_query = "SELECT email FROM users WHERE id = ?";
    $user_email_stmt = mysqli_prepare($conn, $user_email_query);
    mysqli_stmt_bind_param($user_email_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($user_email_stmt);
    $user_email = mysqli_fetch_assoc(mysqli_stmt_get_result($user_email_stmt))['email'] ?? '';
    
    // Get employee ID for this user
    $emp_query = "SELECT id FROM employees WHERE email = ? AND is_active = 1";
    $emp_stmt = mysqli_prepare($conn, $emp_query);
    mysqli_stmt_bind_param($emp_stmt, "s", $user_email);
    mysqli_stmt_execute($emp_stmt);
    $user_employee_id = mysqli_fetch_assoc(mysqli_stmt_get_result($emp_stmt))['id'] ?? 0;
    
    // Check if employee is trying to view their own payslip
    if ($employee_id !== $user_employee_id) {
        die('Unauthorized access - You can only view your own payslips');
    }
}

// Get payroll data
$query = "SELECT 
    pr.*,
    pp.period_name,
    pp.start_date,
    pp.end_date,
    pp.payment_date,
    e.employee_id as emp_number,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    ed.sss_number,
    ed.philhealth_number,
    ed.pagibig_number,
    ed.tin_number,
    ed.bank_name,
    ed.bank_account_number
FROM payroll_records pr
JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
JOIN employees e ON pr.employee_id = e.id
LEFT JOIN employee_details ed ON e.id = ed.employee_id
WHERE pr.payroll_period_id = ? AND pr.employee_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $period_id, $employee_id);
mysqli_stmt_execute($stmt);
$payroll = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$payroll) {
    die('Payroll record not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .payslip-container { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Print Button -->
        <div class="no-print mb-4 flex justify-between items-center">
            <button onclick="window.close()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i>Close
            </button>
            <button onclick="window.print()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                <i class="fas fa-print mr-2"></i>Print Payslip
            </button>
        </div>
        
        <!-- Payslip -->
        <div class="payslip-container bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="border-b-4 border-green-600 pb-6 mb-6">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">NIA HRIS</h1>
                    <h2 class="text-xl font-semibold text-green-600">PAYSLIP</h2>
                    <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($payroll['period_name']); ?></p>
                </div>
            </div>
            
            <!-- Employee Information -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-gray-200 pb-2">Employee Information</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Employee Name:</span>
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Employee ID:</span>
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payroll['emp_number']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Position:</span>
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payroll['position']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Department:</span>
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($payroll['department']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b-2 border-gray-200 pb-2">Period Information</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Period:</span>
                            <span class="font-semibold text-gray-900"><?php echo date('M j', strtotime($payroll['start_date'])) . ' - ' . date('M j, Y', strtotime($payroll['end_date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Payment Date:</span>
                            <span class="font-semibold text-gray-900"><?php echo date('M j, Y', strtotime($payroll['payment_date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rate Type:</span>
                            <span class="font-semibold text-gray-900">Monthly</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Earnings and Deductions -->
            <div class="grid grid-cols-2 gap-6 mb-6">
                <!-- Earnings -->
                <div class="bg-green-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-green-900 mb-4">Earnings</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Basic Pay (<?php echo number_format($payroll['regular_hours'], 1); ?> hrs)</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['basic_pay'], 2); ?></span>
                        </div>
                        <?php if ($payroll['overtime_pay'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Overtime Pay (<?php echo number_format($payroll['overtime_hours'], 1); ?> hrs)</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['overtime_pay'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['night_diff_pay'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Night Differential (<?php echo number_format($payroll['night_diff_hours'], 1); ?> hrs)</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['night_diff_pay'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['allowances'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Allowances</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['allowances'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between pt-2 border-t-2 border-green-200">
                            <span class="font-semibold text-green-900">Gross Pay</span>
                            <span class="font-bold text-green-900">₱<?php echo number_format($payroll['gross_pay'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Deductions -->
                <div class="bg-red-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-red-900 mb-4">Deductions</h3>
                    <div class="space-y-2">
                        <?php if ($payroll['sss_contribution'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">SSS Contribution</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['sss_contribution'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['philhealth_contribution'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">PhilHealth Contribution</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['philhealth_contribution'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['pagibig_contribution'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Pag-IBIG Contribution</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['pagibig_contribution'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['withholding_tax'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Withholding Tax</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['withholding_tax'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['sss_loan'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">SSS Loan</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['sss_loan'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['pagibig_loan'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Pag-IBIG Loan</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['pagibig_loan'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payroll['salary_loan'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-700">Salary Loan</span>
                            <span class="font-medium text-gray-900">₱<?php echo number_format($payroll['salary_loan'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between pt-2 border-t-2 border-red-200">
                            <span class="font-semibold text-red-900">Total Deductions</span>
                            <span class="font-bold text-red-900">₱<?php echo number_format($payroll['total_deductions'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Net Pay -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg p-6 text-white mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Net Pay (Take Home Pay)</p>
                        <p class="text-3xl font-bold">₱<?php echo number_format($payroll['net_pay'], 2); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs opacity-75">Payment Method</p>
                        <p class="text-sm font-semibold">Bank Transfer</p>
                        <?php if ($payroll['bank_name']): ?>
                            <p class="text-xs"><?php echo htmlspecialchars($payroll['bank_name']); ?></p>
                            <p class="text-xs"><?php echo htmlspecialchars($payroll['bank_account_number']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Government Numbers -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-gray-900 mb-3">Government IDs</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-gray-600">SSS:</span>
                        <span class="font-medium text-gray-900 ml-1"><?php echo $payroll['sss_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">PhilHealth:</span>
                        <span class="font-medium text-gray-900 ml-1"><?php echo $payroll['philhealth_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Pag-IBIG:</span>
                        <span class="font-medium text-gray-900 ml-1"><?php echo $payroll['pagibig_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">TIN:</span>
                        <span class="font-medium text-gray-900 ml-1"><?php echo $payroll['tin_number'] ?: 'N/A'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="border-t-2 border-gray-200 pt-4 text-center text-xs text-gray-500">
                <p>This is a computer-generated payslip and does not require a signature.</p>
                <p class="mt-1">Generated on <?php echo date('F j, Y g:i A'); ?></p>
                <p class="mt-2 text-gray-400">For inquiries, please contact the HR Department</p>
            </div>
        </div>
    </div>
</body>
</html>

