<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Check if payroll tables exist
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'payroll_periods'");
$tables_exist = mysqli_num_rows($table_check) > 0;

// If tables don't exist, show setup page
if (!$tables_exist) {
    $page_title = 'Setup Payroll System';
    include 'includes/header.php';
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Payroll System Not Installed</h2>
            <p class="text-gray-600 mb-6 text-lg">
                The payroll system tables haven't been created yet. Click the button below to install the complete payroll system.
            </p>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 text-left">
                <h3 class="font-semibold text-blue-900 mb-2">What will be installed:</h3>
                <ul class="text-blue-800 space-y-1 ml-4">
                    <li>✓ Payroll periods management</li>
                    <li>✓ Payroll records with hours tracking</li>
                    <li>✓ Deduction types (SSS, PhilHealth, Pag-IBIG, Tax, Loans)</li>
                    <li>✓ Earning types (Basic, Overtime, Bonuses, Allowances)</li>
                    <li>✓ Payroll adjustments and audit log</li>
                    <li>✓ Automated calculation engine</li>
                    <li>✓ Payslip generation system</li>
                </ul>
            </div>
            
            <button onclick="setupPayroll()" id="setupBtn" 
                    class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium text-lg shadow-lg">
                <i class="fas fa-cog mr-2"></i>Install Payroll System
            </button>
            
            <div id="setupResult" class="mt-6 hidden"></div>
        </div>
    </div>
    
    <script>
    function setupPayroll() {
        const btn = document.getElementById('setupBtn');
        const result = document.getElementById('setupResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Installing...';
        
        fetch('setup-payroll-system.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            result.classList.remove('hidden');
            if (data.success) {
                result.innerHTML = `
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-check-circle text-2xl mr-3"></i>
                            <h3 class="font-bold text-lg">Installation Successful!</h3>
                        </div>
                        <p class="mb-4">${data.message}</p>
                        <button onclick="location.reload()" 
                                class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-arrow-right mr-2"></i>Continue to Payroll Management
                        </button>
                    </div>
                `;
            } else {
                result.innerHTML = `
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <h3 class="font-bold text-lg">Installation Failed</h3>
                        </div>
                        <p>${data.message}</p>
                    </div>
                `;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo mr-2"></i>Try Again';
            }
        })
        .catch(error => {
            result.classList.remove('hidden');
            result.innerHTML = `
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                    <p><strong>Error:</strong> ${error.message}</p>
                </div>
            `;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo mr-2"></i>Try Again';
        });
    }
    </script>
    
    <?php
    exit();
}

$page_title = 'Payroll Management';

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_year = $_GET['year'] ?? date('Y');

// Get payroll periods
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if ($filter_status) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if ($filter_year) {
    $where_conditions[] = "YEAR(start_date) = ?";
    $params[] = $filter_year;
    $param_types .= "i";
}

$where_clause = implode(' AND ', $where_conditions);

$periods_query = "SELECT 
    pp.*,
    u.first_name as created_by_name,
    u.last_name as created_by_lastname,
    COUNT(pr.id) as employee_count,
    SUM(pr.gross_pay) as period_gross,
    SUM(pr.total_deductions) as period_deductions,
    SUM(pr.net_pay) as period_net
FROM payroll_periods pp
LEFT JOIN users u ON pp.created_by = u.id
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
WHERE $where_clause
GROUP BY pp.id
ORDER BY pp.start_date DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $periods_query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $periods_result = mysqli_stmt_get_result($stmt);
} else {
    $periods_result = mysqli_query($conn, $periods_query);
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_periods,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_periods,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_periods,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_periods
FROM payroll_periods";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-md" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo $_SESSION['success_message']; ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md" role="alert">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo $_SESSION['error_message']; ?></p>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-money-check-alt text-green-600 mr-2"></i>Payroll Management
            </h1>
            <p class="text-gray-600">Manage payroll periods, hours, deductions, and generate payslips</p>
        </div>
        <div class="flex space-x-3">
            <a href="payroll-reports.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-chart-bar mr-2"></i>Reports
            </a>
            <button onclick="openCreatePeriodModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-plus mr-2"></i>New Payroll Period
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Periods</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_periods']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-folder-open text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Open Periods</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['open_periods']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calculator text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Processing</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['processing_periods']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Paid</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['paid_periods']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                <option value="">All Statuses</option>
                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="open" <?php echo $filter_status === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="calculated" <?php echo $filter_status === 'calculated' ? 'selected' : ''; ?>>Calculated</option>
                <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="closed" <?php echo $filter_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select name="year" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $filter_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors font-medium">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="payroll-management.php" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors font-medium text-center">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Payroll Periods List -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">Payroll Periods</h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($periods_result) > 0): ?>
                    <?php while ($period = mysqli_fetch_assoc($periods_result)): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($period['period_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo ucfirst($period['period_type']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($period['start_date'])); ?> -<br>
                                    <?php echo date('M j, Y', strtotime($period['end_date'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Payment: <?php echo date('M j, Y', strtotime($period['payment_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo $period['employee_count']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">₱<?php echo number_format($period['period_gross'] ?? 0, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-red-600">₱<?php echo number_format($period['period_deductions'] ?? 0, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-green-600">₱<?php echo number_format($period['period_net'] ?? 0, 2); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'open' => 'bg-blue-100 text-blue-800',
                                    'processing' => 'bg-yellow-100 text-yellow-800',
                                    'calculated' => 'bg-purple-100 text-purple-800',
                                    'approved' => 'bg-indigo-100 text-indigo-800',
                                    'paid' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-gray-100 text-gray-600'
                                ];
                                $color = $status_colors[$period['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                    <?php echo ucfirst($period['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if ($period['status'] === 'draft' || $period['status'] === 'open'): ?>
                                        <a href="payroll-process.php?period_id=<?php echo $period['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 transition-colors" title="Process Payroll">
                                            <i class="fas fa-calculator text-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="payroll-view.php?period_id=<?php echo $period['id']; ?>" 
                                       class="text-green-600 hover:text-green-900 transition-colors" title="View Details">
                                        <i class="fas fa-eye text-lg"></i>
                                    </a>
                                    
                                    <?php if ($period['status'] !== 'closed' && $period['status'] !== 'paid'): ?>
                                        <button onclick="editPeriod(<?php echo htmlspecialchars(json_encode($period)); ?>)" 
                                                class="text-yellow-600 hover:text-yellow-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($period['status'] === 'draft'): ?>
                                        <button onclick="deletePeriod(<?php echo $period['id']; ?>, '<?php echo htmlspecialchars($period['period_name']); ?>')" 
                                                class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-money-check-alt text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No payroll periods found</h3>
                                <p class="text-gray-500 mb-4">Create your first payroll period to get started.</p>
                                <button onclick="openCreatePeriodModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Create Payroll Period
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Payroll Period Modal -->
<div id="periodModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create Payroll Period
            </h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="periodForm" method="POST" action="save-payroll-period.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="period_id" id="periodId">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Period Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="period_name" id="periodName" required
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500"
                           placeholder="e.g., January 2025 - 1st Half">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Period Type</label>
                    <select name="period_type" id="periodType" 
                            class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                        <option value="monthly">Monthly</option>
                        <option value="semi-monthly">Semi-Monthly</option>
                        <option value="bi-weekly">Bi-Weekly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="startDate" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="endDate" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="payment_date" id="paymentDate" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="periodNotes" rows="3"
                              class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500"
                              placeholder="Optional notes about this payroll period..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-8">
                <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i><span id="submitButtonText">Create Period</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePeriodModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600 mr-2"></i>Create Payroll Period';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitButtonText').textContent = 'Create Period';
    document.getElementById('periodForm').reset();
    document.getElementById('periodId').value = '';
    
    // Set default dates (current month)
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    const payDay = new Date(now.getFullYear(), now.getMonth() + 1, 5);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
    document.getElementById('paymentDate').value = payDay.toISOString().split('T')[0];
    
    // Set default period name
    const monthName = firstDay.toLocaleString('default', { month: 'long', year: 'numeric' });
    document.getElementById('periodName').value = monthName;
    
    document.getElementById('periodModal').classList.remove('hidden');
}

function editPeriod(period) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-yellow-600 mr-2"></i>Edit Payroll Period';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('submitButtonText').textContent = 'Update Period';
    document.getElementById('periodId').value = period.id;
    document.getElementById('periodName').value = period.period_name;
    document.getElementById('periodType').value = period.period_type;
    document.getElementById('startDate').value = period.start_date;
    document.getElementById('endDate').value = period.end_date;
    document.getElementById('paymentDate').value = period.payment_date;
    document.getElementById('periodNotes').value = period.notes || '';
    document.getElementById('periodModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('periodModal').classList.add('hidden');
}

function deletePeriod(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis will also delete all payroll records associated with this period.\n\nThis action cannot be undone!`)) {
        showToast('Deleting period...', 'info');
        
        fetch('delete-payroll-period.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        });
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('periodModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

