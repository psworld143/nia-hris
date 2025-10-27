<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Get period ID
$period_id = (int)($_GET['period_id'] ?? 0);

if ($period_id <= 0) {
    header('Location: payroll-management.php');
    exit();
}

// Get period details
$period_query = "SELECT * FROM payroll_periods WHERE id = ?";
$stmt = mysqli_prepare($conn, $period_query);
mysqli_stmt_bind_param($stmt, "i", $period_id);
mysqli_stmt_execute($stmt);
$period = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$period) {
    $_SESSION['error_message'] = 'Payroll period not found.';
    header('Location: payroll-management.php');
    exit();
}

// Get all active employees with their salary information
$employees_query = "SELECT 
    e.id,
    e.employee_id,
    e.first_name,
    e.last_name,
    e.email,
    e.position,
    e.department,
    ed.basic_salary,
    ed.allowances,
    ed.overtime_rate,
    ed.night_differential_rate,
    ed.hazard_pay,
    ed.pay_schedule,
    ed.sss_number,
    ed.philhealth_number,
    ed.pagibig_number,
    ed.tin_number,
    pr.id as payroll_record_id,
    pr.regular_hours,
    pr.overtime_hours,
    pr.night_diff_hours,
    pr.gross_pay,
    pr.total_deductions,
    pr.net_pay,
    pr.sss_contribution,
    pr.philhealth_contribution,
    pr.pagibig_contribution,
    pr.withholding_tax,
    pr.sss_loan,
    pr.pagibig_loan,
    pr.salary_loan,
    pr.status as payroll_status
FROM employees e
LEFT JOIN employee_details ed ON e.id = ed.employee_id
LEFT JOIN payroll_records pr ON e.id = pr.employee_id AND pr.payroll_period_id = ?
WHERE e.is_active = 1
ORDER BY e.last_name, e.first_name";

$stmt = mysqli_prepare($conn, $employees_query);
mysqli_stmt_bind_param($stmt, "i", $period_id);
mysqli_stmt_execute($stmt);
$employees_result = mysqli_stmt_get_result($stmt);

// Get all active deduction types from database
$deductions_query = "SELECT * FROM payroll_deduction_types WHERE is_active = 1 ORDER BY sort_order, name";
$deductions_result = mysqli_query($conn, $deductions_query);
$deduction_types = [];
while ($deduction = mysqli_fetch_assoc($deductions_result)) {
    $deduction_types[] = $deduction;
}

$page_title = 'Process Payroll - ' . $period['period_name'];
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-calculator text-green-600 mr-2"></i>Process Payroll
            </h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($period['period_name']); ?></p>
            <p class="text-sm text-gray-500">
                <?php echo date('M j, Y', strtotime($period['start_date'])); ?> - 
                <?php echo date('M j, Y', strtotime($period['end_date'])); ?>
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="payroll-management.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <button onclick="calculateAll()" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-colors font-medium">
                <i class="fas fa-calculator mr-2"></i>Calculate All
            </button>
            <button onclick="saveAllChanges()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors font-medium">
                <i class="fas fa-save mr-2"></i>Save All
            </button>
        </div>
    </div>
</div>

<!-- Period Information -->
<div class="bg-gradient-to-r from-green-50 to-blue-50  p-4 rounded-lg mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-xs text-gray-600 mb-1">Period Type</p>
            <p class="text-sm font-semibold text-gray-900"><?php echo ucfirst($period['period_type']); ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600 mb-1">Payment Date</p>
            <p class="text-sm font-semibold text-gray-900"><?php echo date('M j, Y', strtotime($period['payment_date'])); ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600 mb-1">Status</p>
            <p class="text-sm font-semibold text-gray-900">
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                    <?php echo ucfirst($period['status']); ?>
                </span>
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-600 mb-1">Employees</p>
            <p class="text-sm font-semibold text-gray-900"><?php echo mysqli_num_rows($employees_result); ?></p>
        </div>
    </div>
</div>

<!-- Payroll Processing Form -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="payrollTable">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Basic Salary</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Regular Hrs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">OT Hrs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Night Diff</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Gross Pay</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Deductions</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Net Pay</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($employee = mysqli_fetch_assoc($employees_result)): ?>
                    <?php
                    $full_name = $employee['first_name'] . ' ' . $employee['last_name'];
                    $basic_salary = $employee['basic_salary'] ?? 0;
                    $allowances = $employee['allowances'] ?? 0;
                    $row_id = 'emp_' . $employee['id'];
                    ?>
                    <tr class="hover:bg-gray-50" id="<?php echo $row_id; ?>" data-employee-id="<?php echo $employee['id']; ?>">
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-green-600 font-semibold text-sm">
                                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($full_name); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['position']); ?></span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">₱<?php echo number_format($basic_salary, 2); ?></span>
                        </td>
                        <td class="px-4 py-4">
                            <input type="number" 
                                   class="w-20 px-2 py-1 border-2 border-gray-200 rounded focus:border-green-500 text-sm" 
                                   id="regular_hours_<?php echo $employee['id']; ?>"
                                   value="<?php echo $employee['regular_hours'] ?? '0'; ?>"
                                   min="0" max="999" step="0.5"
                                   onchange="calculateRow(<?php echo $employee['id']; ?>)">
                        </td>
                        <td class="px-4 py-4">
                            <input type="number" 
                                   class="w-20 px-2 py-1 border-2 border-gray-200 rounded focus:border-green-500 text-sm" 
                                   id="overtime_hours_<?php echo $employee['id']; ?>"
                                   value="<?php echo $employee['overtime_hours'] ?? '0'; ?>"
                                   min="0" max="999" step="0.5"
                                   onchange="calculateRow(<?php echo $employee['id']; ?>)">
                        </td>
                        <td class="px-4 py-4">
                            <input type="number" 
                                   class="w-20 px-2 py-1 border-2 border-gray-200 rounded focus:border-green-500 text-sm" 
                                   id="night_diff_hours_<?php echo $employee['id']; ?>"
                                   value="<?php echo $employee['night_diff_hours'] ?? '0'; ?>"
                                   min="0" max="999" step="0.5"
                                   onchange="calculateRow(<?php echo $employee['id']; ?>)">
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-green-600" id="gross_pay_<?php echo $employee['id']; ?>">
                                ₱<?php echo number_format($employee['gross_pay'] ?? 0, 2); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <button onclick="openDeductionsModal(<?php echo $employee['id']; ?>)" 
                                    class="text-sm text-blue-600 hover:text-blue-900 font-medium">
                                <span id="deductions_<?php echo $employee['id']; ?>">₱<?php echo number_format($employee['total_deductions'] ?? 0, 2); ?></span>
                                <i class="fas fa-edit ml-1"></i>
                            </button>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-purple-600" id="net_pay_<?php echo $employee['id']; ?>">
                                ₱<?php echo number_format($employee['net_pay'] ?? 0, 2); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button onclick="viewPayslip(<?php echo $employee['id']; ?>)" 
                                    class="text-green-600 hover:text-green-900 transition-colors" title="View Payslip">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </button>
                        </td>
                    </tr>
                    <script>
                    // Store employee data for calculations
                    window.employeeData = window.employeeData || {};
                    window.employeeData[<?php echo $employee['id']; ?>] = {
                        id: <?php echo $employee['id']; ?>,
                        name: '<?php echo htmlspecialchars($full_name); ?>',
                        basic_salary: <?php echo $basic_salary; ?>,
                        allowances: <?php echo $allowances; ?>,
                        overtime_rate: <?php echo $employee['overtime_rate'] ?? 1.25; ?>,
                        night_diff_rate: <?php echo $employee['night_differential_rate'] ?? 0.10; ?>,
                        sss_number: '<?php echo $employee['sss_number'] ?? ''; ?>',
                        philhealth_number: '<?php echo $employee['philhealth_number'] ?? ''; ?>',
                        pagibig_number: '<?php echo $employee['pagibig_number'] ?? ''; ?>',
                        tin_number: '<?php echo $employee['tin_number'] ?? ''; ?>',
                        deductions: {
                            sss: <?php echo $employee['sss_contribution'] ?? 0; ?>,
                            philhealth: <?php echo $employee['philhealth_contribution'] ?? 0; ?>,
                            pagibig: <?php echo $employee['pagibig_contribution'] ?? 0; ?>,
                            tax: <?php echo $employee['withholding_tax'] ?? 0; ?>,
                            sss_loan: <?php echo $employee['sss_loan'] ?? 0; ?>,
                            pagibig_loan: <?php echo $employee['pagibig_loan'] ?? 0; ?>,
                            salary_loan: <?php echo $employee['salary_loan'] ?? 0; ?>
                        }
                    };
                    </script>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Deductions Modal -->
<div id="deductionsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900" id="deductionsTitle">
                <i class="fas fa-minus-circle text-red-600 mr-2"></i>Manage Deductions
            </h3>
            <button onclick="closeDeductionsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <div id="deductionsContent" class="space-y-6">
            <!-- Will be populated by JavaScript -->
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="closeDeductionsModal()" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                Cancel
            </button>
            <button onclick="saveDeductions()" 
                    class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                <i class="fas fa-save mr-2"></i>Apply Deductions
            </button>
        </div>
    </div>
</div>

<script>
const periodId = <?php echo $period_id; ?>;
let currentEmployeeId = null;

// Deduction types from database
const deductionTypes = <?php echo json_encode($deduction_types); ?>;

// Calculate payroll for a single employee
function calculateRow(employeeId) {
    const empData = window.employeeData[employeeId];
    if (!empData) return;
    
    const regularHours = parseFloat(document.getElementById(`regular_hours_${employeeId}`).value) || 0;
    const overtimeHours = parseFloat(document.getElementById(`overtime_hours_${employeeId}`).value) || 0;
    const nightDiffHours = parseFloat(document.getElementById(`night_diff_hours_${employeeId}`).value) || 0;
    
    // Calculate rates
    const monthlyRate = empData.basic_salary;
    const dailyRate = monthlyRate / 22; // 22 working days per month
    const hourlyRate = dailyRate / 8; // 8 hours per day
    
    // Calculate earnings
    const basicPay = (regularHours / 176) * monthlyRate; // 176 hours per month (22 days * 8 hours)
    const overtimePay = overtimeHours * hourlyRate * empData.overtime_rate;
    const nightDiffPay = nightDiffHours * hourlyRate * empData.night_diff_rate;
    const allowancePay = empData.allowances;
    
    const grossPay = basicPay + overtimePay + nightDiffPay + allowancePay;
    
    // Dynamically calculate total deductions from all deduction types
    let totalDeductions = 0;
    if (empData.deductions && typeof empData.deductions === 'object') {
        Object.values(empData.deductions).forEach(deduction => {
            totalDeductions += parseFloat(deduction) || 0;
        });
    }
    
    const netPay = grossPay - totalDeductions;
    
    // Update display
    document.getElementById(`gross_pay_${employeeId}`).textContent = '₱' + grossPay.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById(`deductions_${employeeId}`).textContent = '₱' + totalDeductions.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById(`net_pay_${employeeId}`).textContent = '₱' + netPay.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Store calculated values
    empData.calculated = {
        regularHours, overtimeHours, nightDiffHours,
        hourlyRate, dailyRate, monthlyRate,
        basicPay, overtimePay, nightDiffPay, allowancePay,
        grossPay, totalDeductions, netPay
    };
}

// Calculate all employees
function calculateAll() {
    showToast('Calculating payroll for all employees...', 'info');
    
    Object.keys(window.employeeData).forEach(employeeId => {
        calculateRow(employeeId);
    });
    
    setTimeout(() => {
        showToast('Calculations completed!', 'success');
    }, 500);
}

// Open deductions modal - DYNAMICALLY GENERATED FROM DATABASE
function openDeductionsModal(employeeId) {
    currentEmployeeId = employeeId;
    const empData = window.employeeData[employeeId];
    
    document.getElementById('deductionsTitle').innerHTML = 
        `<i class="fas fa-minus-circle text-red-600 mr-2"></i>Deductions for ${empData.name}`;
    
    // Group deductions by category
    const categories = {
        'mandatory': { title: 'Government Contributions (Mandatory)', color: 'blue', items: [] },
        'loan': { title: 'Loan Deductions', color: 'yellow', items: [] },
        'attendance': { title: 'Attendance Deductions', color: 'orange', items: [] },
        'other': { title: 'Other Deductions', color: 'gray', items: [] }
    };
    
    // Organize deductions by category
    deductionTypes.forEach(deduction => {
        if (categories[deduction.category]) {
            categories[deduction.category].items.push(deduction);
        }
    });
    
    // Build HTML dynamically
    let deductionsHTML = '';
    
    Object.keys(categories).forEach(catKey => {
        const category = categories[catKey];
        if (category.items.length === 0) return;
        
        const borderColor = `border-${category.color}-500`;
        const bgColor = `bg-${category.color}-50`;
        const textColor = `text-${category.color}-900`;
        
        deductionsHTML += `
            <div class="${bgColor} border-l-4 ${borderColor} p-4">
                <h4 class="font-semibold ${textColor} mb-3">${category.title}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        `;
        
        category.items.forEach(deduction => {
            const code = deduction.code.toLowerCase();
            const deductionKey = code.replace(/_/g, '_');
            const currentValue = empData.deductions[deductionKey] || 0;
            
            // Auto-check for mandatory deductions if employee has corresponding ID
            let autoCheck = false;
            let idInfo = '';
            
            if (code === 'sss' && empData.sss_number) {
                autoCheck = true;
                idInfo = `SSS #: ${empData.sss_number}`;
            } else if (code === 'phic' && empData.philhealth_number) {
                autoCheck = true;
                idInfo = `PhilHealth #: ${empData.philhealth_number}`;
            } else if (code === 'hdmf' && empData.pagibig_number) {
                autoCheck = true;
                idInfo = `Pag-IBIG #: ${empData.pagibig_number}`;
            } else if (code === 'wtax' && empData.tin_number) {
                autoCheck = true;
                idInfo = `TIN: ${empData.tin_number}`;
            } else if (currentValue > 0) {
                autoCheck = true;
            }
            
            deductionsHTML += `
                <div>
                    <label class="flex items-center mb-2">
                        <input type="checkbox" 
                               id="deduct_${deductionKey}" 
                               data-deduction-code="${code}"
                               ${autoCheck ? 'checked' : ''} 
                               onchange="toggleDeduction('${deductionKey}')"
                               class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">${deduction.name}</span>
                    </label>
                    <input type="number" 
                           id="deduction_${deductionKey}" 
                           value="${currentValue}" 
                           step="0.01" 
                           min="0"
                           onchange="updateDeductionsTotal()"
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500 text-sm"
                           placeholder="${deduction.description || ''}">
                    ${idInfo ? `<p class="text-xs text-gray-500 mt-1">${idInfo}</p>` : ''}
                </div>
            `;
        });
        
        deductionsHTML += `
                </div>
            </div>
        `;
    });
    
    deductionsHTML += `
        <div class="bg-green-50  p-4">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-green-900">Total Deductions:</span>
                <span class="text-xl font-bold text-green-900" id="modalTotalDeductions">₱0.00</span>
            </div>
        </div>
    `;
    
    document.getElementById('deductionsContent').innerHTML = deductionsHTML;
    document.getElementById('deductionsModal').classList.remove('hidden');
    
    // Calculate initial total
    updateDeductionsTotal();
}

function toggleDeduction(type) {
    const checkbox = document.getElementById(`deduct_${type}`);
    const input = document.getElementById(`deduction_${type}`);
    
    if (!checkbox.checked) {
        input.value = '0';
    }
    
    updateDeductionsTotal();
}

function updateDeductionsTotal() {
    let total = 0;
    
    // Dynamically sum all deduction inputs
    deductionTypes.forEach(deduction => {
        const code = deduction.code.toLowerCase();
        const deductionKey = code.replace(/_/g, '_');
        const checkbox = document.getElementById(`deduct_${deductionKey}`);
        const input = document.getElementById(`deduction_${deductionKey}`);
        
        if (checkbox && checkbox.checked && input) {
            total += parseFloat(input.value) || 0;
        }
    });
    
    document.getElementById('modalTotalDeductions').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function saveDeductions() {
    if (!currentEmployeeId) return;
    
    const empData = window.employeeData[currentEmployeeId];
    
    // Dynamically update all deductions from database types
    const newDeductions = {};
    
    deductionTypes.forEach(deduction => {
        const code = deduction.code.toLowerCase();
        const deductionKey = code.replace(/_/g, '_');
        const checkbox = document.getElementById(`deduct_${deductionKey}`);
        const input = document.getElementById(`deduction_${deductionKey}`);
        
        if (input) {
            // Save value if checkbox is checked, otherwise set to 0
            newDeductions[deductionKey] = (checkbox && checkbox.checked) ? (parseFloat(input.value) || 0) : 0;
        }
    });
    
    // Update employee deductions data
    empData.deductions = newDeductions;
    
    // Recalculate row
    calculateRow(currentEmployeeId);
    
    closeDeductionsModal();
    showToast('Deductions updated!', 'success');
}

function closeDeductionsModal() {
    document.getElementById('deductionsModal').classList.add('hidden');
    currentEmployeeId = null;
}

// Save all payroll changes
function saveAllChanges() {
    const payrollData = [];
    
    Object.keys(window.employeeData).forEach(employeeId => {
        const empData = window.employeeData[employeeId];
        const regularHours = parseFloat(document.getElementById(`regular_hours_${employeeId}`).value) || 0;
        const overtimeHours = parseFloat(document.getElementById(`overtime_hours_${employeeId}`).value) || 0;
        const nightDiffHours = parseFloat(document.getElementById(`night_diff_hours_${employeeId}`).value) || 0;
        
        // Only include if there are hours entered
        if (regularHours > 0 || overtimeHours > 0 || nightDiffHours > 0) {
            payrollData.push({
                employee_id: employeeId,
                regular_hours: regularHours,
                overtime_hours: overtimeHours,
                night_diff_hours: nightDiffHours,
                ...empData.calculated,
                deductions: empData.deductions
            });
        }
    });
    
    if (payrollData.length === 0) {
        showToast('Please enter hours for at least one employee', 'error');
        return;
    }
    
    showToast('Saving payroll data...', 'info');
    
    fetch('save-payroll-data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            period_id: periodId,
            payroll_data: payrollData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error saving payroll data', 'error');
        console.error(error);
    });
}

function viewPayslip(employeeId) {
    window.open(`payslip-view.php?period_id=${periodId}&employee_id=${employeeId}`, '_blank');
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Auto-calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payroll processing page loaded. Employee data:', window.employeeData);
});
</script>

