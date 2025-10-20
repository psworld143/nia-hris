<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager', 'human_resource'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'DTR Card Management';
$can_upload = in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager']);

// Get filter parameters
$period_filter = $_GET['period'] ?? '';
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Get payroll periods
$periods_query = "SELECT * FROM payroll_periods ORDER BY start_date DESC LIMIT 12";
$periods_result = mysqli_query($conn, $periods_query);
$payroll_periods = [];
while ($period = mysqli_fetch_assoc($periods_result)) {
    $payroll_periods[] = $period;
}

// Get departments
$departments_query = "SELECT DISTINCT department FROM employees WHERE is_active = 1 ORDER BY department";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
while ($dept = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $dept['department'];
}

// Get employees
$employees_query = "SELECT id, employee_id, first_name, last_name, department FROM employees WHERE is_active = 1 ORDER BY last_name, first_name";
$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
while ($emp = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $emp;
}

// Build WHERE clause for DTR cards
$where_conditions = ["1=1"];
$params = [];
$types = '';

if ($period_filter) {
    $where_conditions[] = "dtr.payroll_period_id = ?";
    $params[] = $period_filter;
    $types .= 'i';
}

if ($status_filter) {
    $where_conditions[] = "dtr.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($department_filter) {
    $where_conditions[] = "e.department = ?";
    $params[] = $department_filter;
    $types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get DTR cards with employee info
$dtr_query = "SELECT dtr.*, 
              e.employee_id, e.first_name, e.last_name, e.department,
              pp.period_name, pp.start_date as period_start, pp.end_date as period_end,
              u.first_name as uploader_first, u.last_name as uploader_last,
              v.first_name as verifier_first, v.last_name as verifier_last
              FROM employee_dtr_cards dtr
              LEFT JOIN employees e ON dtr.employee_id = e.id
              LEFT JOIN payroll_periods pp ON dtr.payroll_period_id = pp.id
              LEFT JOIN users u ON dtr.uploaded_by = u.id
              LEFT JOIN users v ON dtr.verified_by = v.id
              $where_clause
              ORDER BY dtr.upload_date DESC";

if (!empty($params)) {
    $dtr_stmt = mysqli_prepare($conn, $dtr_query);
    mysqli_stmt_bind_param($dtr_stmt, $types, ...$params);
    mysqli_stmt_execute($dtr_stmt);
    $dtr_result = mysqli_stmt_get_result($dtr_stmt);
} else {
    $dtr_result = mysqli_query($conn, $dtr_query);
}

$dtr_cards = [];
while ($row = mysqli_fetch_assoc($dtr_result)) {
    $dtr_cards[] = $row;
}

// Calculate statistics
$stats = [
    'total' => count($dtr_cards),
    'pending' => count(array_filter($dtr_cards, fn($d) => $d['status'] === 'pending')),
    'verified' => count(array_filter($dtr_cards, fn($d) => $d['status'] === 'verified')),
    'processed' => count(array_filter($dtr_cards, fn($d) => $d['status'] === 'processed')),
];

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-clock mr-2"></i>DTR Card Management
                </h2>
                <p class="opacity-90">Upload and manage Daily Time Record cards for payroll processing</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <?php if ($can_upload): ?>
                <button onclick="openUploadModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-upload mr-2"></i>Upload DTR Cards
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-file-image text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total DTR Cards</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Verified</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['verified']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-double text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Processed</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['processed']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-filter text-green-500 mr-2"></i>Filters
    </h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar text-blue-500 mr-1"></i>Payroll Period
            </label>
            <select name="period" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Periods</option>
                <?php foreach ($payroll_periods as $period): ?>
                <option value="<?php echo $period['id']; ?>" <?php echo $period_filter == $period['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($period['period_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>Status
            </label>
            <select name="status" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                <option value="processed" <?php echo $status_filter === 'processed' ? 'selected' : ''; ?>>Processed</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-building text-blue-500 mr-1"></i>Department
            </label>
            <select name="department" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="dtr-management.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- DTR Cards Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Upload Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Uploaded By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($dtr_cards)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-clock text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No DTR cards uploaded yet</p>
                            <p class="text-sm text-gray-500 mt-1">Upload DTR cards for the current payroll period</p>
                            <?php if ($can_upload): ?>
                            <button onclick="openUploadModal()" class="mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-upload mr-2"></i>Upload First DTR Card
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($dtr_cards as $dtr): ?>
                <tr class="hover:bg-green-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-green-600 font-semibold">
                                    <?php echo strtoupper(substr($dtr['first_name'], 0, 1) . substr($dtr['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($dtr['first_name'] . ' ' . $dtr['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($dtr['employee_id']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?php if ($dtr['period_name']): ?>
                            <div class="font-medium"><?php echo htmlspecialchars($dtr['period_name']); ?></div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('M d', strtotime($dtr['period_start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($dtr['period_end_date'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="text-xs text-gray-500">
                                <?php echo date('M d', strtotime($dtr['period_start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($dtr['period_end_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?php echo date('M d, Y', strtotime($dtr['upload_date'])); ?>
                        <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($dtr['upload_date'])); ?></div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?php echo htmlspecialchars(($dtr['uploader_first'] ?? '') . ' ' . ($dtr['uploader_last'] ?? '')); ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $status_classes = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'verified' => 'bg-green-100 text-green-800',
                            'processed' => 'bg-purple-100 text-purple-800',
                            'rejected' => 'bg-red-100 text-red-800'
                        ];
                        $status_icons = [
                            'pending' => 'fa-clock',
                            'verified' => 'fa-check-circle',
                            'processed' => 'fa-check-double',
                            'rejected' => 'fa-times-circle'
                        ];
                        ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_classes[$dtr['status']]; ?>">
                            <i class="fas <?php echo $status_icons[$dtr['status']]; ?> mr-1"></i>
                            <?php echo ucfirst($dtr['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewDTRCard(<?php echo htmlspecialchars(json_encode($dtr)); ?>)" 
                                class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($can_upload && $dtr['status'] === 'pending'): ?>
                        <button onclick="verifyDTR(<?php echo $dtr['id']; ?>)" 
                                class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-check"></i> Verify
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload DTR Modal -->
<div id="uploadDTRModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-upload text-green-600 mr-2"></i>Upload DTR Cards
            </h3>
            <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="uploadDTRForm" enctype="multipart/form-data" class="space-y-4">
            <!-- Payroll Period Selection -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                <label class="block text-sm font-semibold text-green-900 mb-2">
                    <i class="fas fa-calendar text-green-600 mr-1"></i>Select Payroll Period *
                </label>
                <select name="payroll_period_id" id="payroll_period" required onchange="handlePeriodSelection(this)"
                        class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="">-- Select Period --</option>
                    <option value="add_new" class="font-bold text-green-700" style="background-color: #f0fdf4;">
                        ➕ Add New Payroll Period
                    </option>
                    <option disabled>────────────────────</option>
                    <?php foreach ($payroll_periods as $period): ?>
                    <option value="<?php echo $period['id']; ?>" 
                            data-start="<?php echo $period['start_date']; ?>" 
                            data-end="<?php echo $period['end_date']; ?>">
                        <?php echo htmlspecialchars($period['period_name']); ?> 
                        (<?php echo date('M d', strtotime($period['start_date'])); ?> - <?php echo date('M d, Y', strtotime($period['end_date'])); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-blue-700 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>Select the payroll period for these DTR cards
                </p>
            </div>

            <!-- Add New Period Form (Hidden by default) -->
            <div id="newPeriodForm" class="hidden bg-green-50 border-2 border-green-500 rounded-lg p-6 mt-4">
                <h4 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create New Payroll Period
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tag text-green-500 mr-1"></i>Period Name *
                        </label>
                        <input type="text" id="new_period_name" required
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="e.g., October 2025 - 1st Half">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt text-green-500 mr-1"></i>Period Type *
                        </label>
                        <select id="new_period_type"
                                class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="monthly">Monthly</option>
                            <option value="semi-monthly" selected>Semi-Monthly</option>
                            <option value="bi-weekly">Bi-Weekly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-day text-green-500 mr-1"></i>Start Date *
                        </label>
                        <input type="date" id="new_start_date" required
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-check text-green-500 mr-1"></i>End Date *
                        </label>
                        <input type="date" id="new_end_date" required
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill-wave text-green-500 mr-1"></i>Payment Date *
                        </label>
                        <input type="date" id="new_payment_date" required
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-sticky-note text-green-500 mr-1"></i>Notes (Optional)
                        </label>
                        <textarea id="new_period_notes" rows="2"
                                  class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                  placeholder="Any additional notes about this period"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-green-200">
                    <button type="button" onclick="cancelNewPeriod()" 
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="button" onclick="saveNewPeriod()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Create Period
                    </button>
                </div>
            </div>

            <!-- Employee and File Upload Grid (Hidden when adding new period) -->
            <div id="dtrUploadsContainer" class="dtr-uploads-section">
                <div class="dtr-upload-row mb-4">
                    <div class="grid grid-cols-12 gap-3 items-start">
                        <div class="col-span-11">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user text-blue-500 mr-1"></i>Employee
                            </label>
                            <select name="employee_id[]" required 
                                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 mb-3">
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Enhanced File Upload -->
                            <div class="file-upload-wrapper">
                                <input type="file" name="dtr_file[]" accept="image/*,.pdf" required 
                                       class="file-input hidden" onchange="handleFileSelect(this)">
                                <div class="file-drop-area border-2 border-dashed border-blue-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 hover:bg-green-50 transition-all"
                                     onclick="this.previousElementSibling.click()"
                                     ondrop="handleFileDrop(event, this)" 
                                     ondragover="handleDragOver(event)"
                                     ondragleave="handleDragLeave(event)">
                                    <div class="file-upload-content">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-blue-400 mb-3"></i>
                                        <p class="text-sm font-semibold text-gray-700 mb-1">
                                            Click to upload or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            JPG, PNG, PDF (Max 5MB)
                                        </p>
                                    </div>
                                    <div class="file-preview hidden">
                                        <!-- Preview will be shown here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                            <button type="button" onclick="removeDTRRow(this)" 
                                    class="w-full bg-red-500 text-white p-2 rounded-lg hover:bg-red-600 transition-colors mt-[42px]">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="dtrUploadActions" class="flex justify-between items-center pt-4 border-t dtr-uploads-section">
                <button type="button" onclick="addMoreDTRRow()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add More Employee
                </button>
                <div class="flex gap-3">
                    <button type="button" onclick="closeUploadModal()" 
                            class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-upload mr-2"></i>Upload DTR Cards
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- View DTR Card Modal -->
<div id="viewDTRModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-file-image text-green-600 mr-2"></i>DTR Card
            </h3>
            <button onclick="closeViewDTRModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="dtrCardContent">
            <!-- Content loaded via JavaScript -->
        </div>
    </div>
</div>

<script>
let dtrRowCount = 1;

function openUploadModal() {
    document.getElementById('uploadDTRModal').classList.remove('hidden');
    
    // Hide upload sections initially until period is selected
    const uploadSections = document.querySelectorAll('.dtr-uploads-section');
    uploadSections.forEach(section => section.classList.add('hidden'));
}

function closeUploadModal() {
    document.getElementById('uploadDTRModal').classList.add('hidden');
    document.getElementById('uploadDTRForm').reset();
    document.getElementById('newPeriodForm').classList.add('hidden');
    document.getElementById('payroll_period').value = '';
    
    // Show upload sections again for next time
    const uploadSections = document.querySelectorAll('.dtr-uploads-section');
    uploadSections.forEach(section => section.classList.remove('hidden'));
    
    // Reset to single row
    const container = document.getElementById('dtrUploadsContainer');
    const firstRow = container.querySelector('.dtr-upload-row');
    container.innerHTML = '';
    container.appendChild(firstRow.cloneNode(true));
    dtrRowCount = 1;
}

function handlePeriodSelection(select) {
    const newPeriodForm = document.getElementById('newPeriodForm');
    const uploadSections = document.querySelectorAll('.dtr-uploads-section');
    
    if (select.value === 'add_new') {
        // Show new period form
        newPeriodForm.classList.remove('hidden');
        // Hide upload sections
        uploadSections.forEach(section => section.classList.add('hidden'));
        // Remove required from dropdown since we're adding new
        select.removeAttribute('required');
    } else {
        // Hide new period form
        newPeriodForm.classList.add('hidden');
        // Show upload sections if a period is selected
        if (select.value) {
            uploadSections.forEach(section => section.classList.remove('hidden'));
        }
        // Add required back
        select.setAttribute('required', 'required');
    }
}

function cancelNewPeriod() {
    document.getElementById('newPeriodForm').classList.add('hidden');
    document.getElementById('payroll_period').value = '';
    document.getElementById('payroll_period').setAttribute('required', 'required');
    
    // Show upload sections again
    const uploadSections = document.querySelectorAll('.dtr-uploads-section');
    uploadSections.forEach(section => section.classList.remove('hidden'));
}

function saveNewPeriod() {
    const periodName = document.getElementById('new_period_name').value;
    const periodType = document.getElementById('new_period_type').value;
    const startDate = document.getElementById('new_start_date').value;
    const endDate = document.getElementById('new_end_date').value;
    const paymentDate = document.getElementById('new_payment_date').value;
    const notes = document.getElementById('new_period_notes').value;
    
    // Validate required fields
    if (!periodName || !startDate || !endDate || !paymentDate) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Validate dates
    if (new Date(startDate) > new Date(endDate)) {
        alert('End date must be after start date');
        return;
    }
    
    const saveBtn = document.querySelector('#newPeriodForm button[onclick="saveNewPeriod()"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
    
    // Send to server
    fetch('save-payroll-period.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'create',
            period_name: periodName,
            period_type: periodType,
            start_date: startDate,
            end_date: endDate,
            payment_date: paymentDate,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new period to dropdown
            const select = document.getElementById('payroll_period');
            const newOption = document.createElement('option');
            newOption.value = data.period_id;
            newOption.textContent = `${periodName} (${new Date(startDate).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})} - ${new Date(endDate).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})})`;
            newOption.setAttribute('data-start', startDate);
            newOption.setAttribute('data-end', endDate);
            
            // Insert after the separator
            const separator = select.querySelector('option[disabled]');
            separator.parentNode.insertBefore(newOption, separator.nextSibling);
            
            // Select the new period
            select.value = data.period_id;
            
            // Hide the form
            document.getElementById('newPeriodForm').classList.add('hidden');
            
            // Re-enable dropdown validation
            select.setAttribute('required', 'required');
            
            // Show upload sections
            const uploadSections = document.querySelectorAll('.dtr-uploads-section');
            uploadSections.forEach(section => section.classList.remove('hidden'));
            
            alert('Payroll period created successfully! You can now upload DTR cards for this period.');
        } else {
            alert('Error: ' + data.message);
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error creating payroll period');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function addMoreDTRRow() {
    const container = document.getElementById('dtrUploadsContainer');
    const firstRow = container.querySelector('.dtr-upload-row');
    const newRow = firstRow.cloneNode(true);
    
    // Reset values
    newRow.querySelectorAll('select, input[type="file"]').forEach(el => el.value = '');
    
    // Reset file upload area
    const uploadContent = newRow.querySelector('.file-upload-content');
    const filePreview = newRow.querySelector('.file-preview');
    uploadContent.classList.remove('hidden');
    filePreview.classList.add('hidden');
    filePreview.innerHTML = '';
    
    container.appendChild(newRow);
    dtrRowCount++;
}

// Enhanced file upload functions
function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        displayFilePreview(input, file);
    }
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');
}

function handleFileDrop(event, dropArea) {
    event.preventDefault();
    event.stopPropagation();
    dropArea.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = dropArea.previousElementSibling;
        fileInput.files = files;
        displayFilePreview(fileInput, files[0]);
    }
}

function displayFilePreview(input, file) {
    const wrapper = input.closest('.file-upload-wrapper');
    const uploadContent = wrapper.querySelector('.file-upload-content');
    const filePreview = wrapper.querySelector('.file-preview');
    
    // Hide upload content, show preview
    uploadContent.classList.add('hidden');
    filePreview.classList.remove('hidden');
    
    // File info
    const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
    const fileType = file.type;
    
    let previewHTML = '';
    
    // Image preview
    if (fileType.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            filePreview.innerHTML = `
                <div class="relative">
                    <img src="${e.target.result}" alt="Preview" class="max-h-48 mx-auto rounded-lg shadow-md">
                    <button type="button" onclick="clearFileSelection(this)" 
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="mt-3 text-sm">
                        <p class="font-semibold text-gray-900 truncate">${file.name}</p>
                        <p class="text-xs text-gray-500">${fileSize} MB</p>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } 
    // PDF preview
    else if (fileType === 'application/pdf') {
        filePreview.innerHTML = `
            <div class="relative">
                <div class="bg-red-50 border-2 border-red-200 rounded-lg p-8">
                    <i class="fas fa-file-pdf text-6xl text-red-500 mb-3"></i>
                    <button type="button" onclick="clearFileSelection(this)" 
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-3 text-sm">
                    <p class="font-semibold text-gray-900 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${fileSize} MB</p>
                </div>
            </div>
        `;
    }
}

function clearFileSelection(button) {
    const wrapper = button.closest('.file-upload-wrapper');
    const fileInput = wrapper.querySelector('.file-input');
    const uploadContent = wrapper.querySelector('.file-upload-content');
    const filePreview = wrapper.querySelector('.file-preview');
    
    // Clear file input
    fileInput.value = '';
    
    // Show upload area, hide preview
    uploadContent.classList.remove('hidden');
    filePreview.classList.add('hidden');
    filePreview.innerHTML = '';
}

function removeDTRRow(button) {
    if (dtrRowCount > 1) {
        button.closest('.dtr-upload-row').remove();
        dtrRowCount--;
    } else {
        alert('At least one employee DTR is required');
    }
}

// Handle DTR upload
document.getElementById('uploadDTRForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Validate payroll period
    if (!formData.get('payroll_period_id')) {
        alert('Please select a payroll period');
        return;
    }
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
    
    fetch('upload-dtr-cards.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'DTR cards uploaded successfully!');
            closeUploadModal();
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error uploading DTR cards. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function viewDTRCard(dtr) {
    const content = `
        <div class="space-y-4">
            <!-- Employee Info -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-600">Employee</p>
                        <p class="font-semibold text-gray-900">${dtr.first_name} ${dtr.last_name}</p>
                        <p class="text-sm text-gray-600">${dtr.employee_id}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Period</p>
                        <p class="font-semibold text-gray-900">${dtr.period_name || 'Custom Period'}</p>
                        <p class="text-sm text-gray-600">${new Date(dtr.period_start_date).toLocaleDateString()} - ${new Date(dtr.period_end_date).toLocaleDateString()}</p>
                    </div>
                </div>
            </div>

            <!-- DTR Card Image -->
            <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                <img src="${dtr.file_path}" alt="DTR Card" class="max-w-full h-auto mx-auto rounded shadow-lg">
            </div>

            <!-- Details -->
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Status</p>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${
                        dtr.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                        dtr.status === 'verified' ? 'bg-green-100 text-green-800' :
                        dtr.status === 'processed' ? 'bg-purple-100 text-purple-800' :
                        'bg-red-100 text-red-800'
                    }">
                        ${dtr.status.charAt(0).toUpperCase() + dtr.status.slice(1)}
                    </span>
                </div>
                <div>
                    <p class="text-gray-600">Upload Date</p>
                    <p class="font-medium">${new Date(dtr.upload_date).toLocaleString()}</p>
                </div>
                ${dtr.verified_by ? `
                    <div class="col-span-2">
                        <p class="text-gray-600">Verified By</p>
                        <p class="font-medium">${dtr.verifier_first} ${dtr.verifier_last} on ${new Date(dtr.verified_at).toLocaleString()}</p>
                    </div>
                ` : ''}
                ${dtr.notes ? `
                    <div class="col-span-2">
                        <p class="text-gray-600">Notes</p>
                        <p class="font-medium">${dtr.notes}</p>
                    </div>
                ` : ''}
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="${dtr.file_path}" download class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
                <button onclick="closeViewDTRModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('dtrCardContent').innerHTML = content;
    document.getElementById('viewDTRModal').classList.remove('hidden');
}

function closeViewDTRModal() {
    document.getElementById('viewDTRModal').classList.add('hidden');
}

function verifyDTR(dtrId) {
    if (!confirm('Verify this DTR card? This confirms the DTR is valid and ready for payroll processing.')) {
        return;
    }
    
    fetch('verify-dtr-card.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dtr_id: dtrId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('DTR card verified successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error verifying DTR card');
    });
}
</script>
