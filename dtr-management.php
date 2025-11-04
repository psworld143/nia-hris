<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
$upload_dir = __DIR__ . '/uploads/dtr-cards/';

while ($row = mysqli_fetch_assoc($dtr_result)) {
    // Normalize file_path to ensure it's always a valid string
    $file_path = trim($row['file_path'] ?? '');
    
    // Check if file_path is valid (not empty, not "0", not null)
    if ($file_path && $file_path !== '0' && $file_path !== 'null') {
        // Ensure path starts with / for absolute path from document root
        $row['file_path'] = '/' . ltrim($file_path, '/');
    } else {
        // Try to reconstruct file path from employee_id and payroll_period_id
        $reconstructed_path = '';
        $employee_id = $row['employee_id'] ?? 0;
        $payroll_period_id = $row['payroll_period_id'] ?? 0;
        $upload_date = $row['upload_date'] ?? '';
        
        if ($employee_id && $payroll_period_id && is_dir($upload_dir)) {
            // Build pattern: dtr_{employee_id}_{payroll_period_id}_*.{ext}
            // Try multiple patterns to find the file
            $patterns = [
                $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.jpg",
                $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.jpeg",
                $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.png",
                $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.gif",
                $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.pdf"
            ];
            
            $files = [];
            foreach ($patterns as $pattern) {
                $found = glob($pattern);
                if ($found) {
                    $files = array_merge($files, $found);
                }
            }
            
            if (!empty($files)) {
                // If multiple files, try to match by upload_date if available
                if (count($files) === 1) {
                    $matched_file = $files[0];
                } else if ($upload_date) {
                    // Try to find file with timestamp closest to upload_date
                    $upload_timestamp = strtotime($upload_date);
                    $best_match = null;
                    $min_diff = PHP_INT_MAX;
                    
                    foreach ($files as $file) {
                        // Extract timestamp from filename (dtr_X_Y_timestamp.ext)
                        if (preg_match('/dtr_\d+_\d+_(\d+)\.\w+$/', basename($file), $matches)) {
                            $file_timestamp = intval($matches[1]);
                            $diff = abs($file_timestamp - $upload_timestamp);
                            if ($diff < $min_diff) {
                                $min_diff = $diff;
                                $best_match = $file;
                            }
                        }
                    }
                    
                    if ($best_match) {
                        $matched_file = $best_match;
                    } else {
                        // Use the most recent file
                        $matched_file = $files[count($files) - 1];
                    }
                } else {
                    // Use the most recent file
                    usort($files, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $matched_file = $files[0];
                }
                
                if (isset($matched_file) && file_exists($matched_file)) {
                    // Convert absolute path to relative path
                    $relative_path = str_replace(__DIR__ . '/', '', $matched_file);
                    $reconstructed_path = '/' . $relative_path;
                    
                    // Optionally update the database
                    if ($reconstructed_path) {
                        $update_query = "UPDATE employee_dtr_cards SET file_path = ? WHERE id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, "si", $reconstructed_path, $row['id']);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                    }
                }
            }
        }
        
        $row['file_path'] = $reconstructed_path;
    }
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
                <?php if (function_exists('getRoleBadge') && $_SESSION['role'] !== 'hr_manager'): ?>
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
                        <button onclick="confirmVerifyDTR(<?php echo $dtr['id']; ?>, '<?php echo htmlspecialchars(addslashes($dtr['first_name'] . ' ' . $dtr['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($dtr['period_name'] ?? 'Custom Period')); ?>')" 
                                class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-check"></i> Verify
                        </button>
                        <?php endif; ?>
                        <?php if ($can_upload): ?>
                        <button onclick="confirmDeleteDTR(<?php echo $dtr['id']; ?>, '<?php echo htmlspecialchars(addslashes($dtr['first_name'] . ' ' . $dtr['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($dtr['period_name'] ?? 'Custom Period')); ?>')" 
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i> Delete
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
                        <input type="text" id="new_period_name" name="new_period_name" disabled
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="e.g., October 2025 - 1st Half">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt text-green-500 mr-1"></i>Period Type *
                        </label>
                        <select id="new_period_type" name="new_period_type" disabled
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
                        <input type="date" id="new_start_date" name="new_start_date" disabled
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar-check text-green-500 mr-1"></i>End Date *
                        </label>
                        <input type="date" id="new_end_date" name="new_end_date" disabled
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-money-bill-wave text-green-500 mr-1"></i>Payment Date *
                        </label>
                        <input type="date" id="new_payment_date" name="new_payment_date" disabled
                               class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-sticky-note text-green-500 mr-1"></i>Notes (Optional)
                        </label>
                        <textarea id="new_period_notes" name="new_period_notes" rows="2" disabled
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
    const fields = [
        'new_period_name', 'new_period_type', 'new_start_date', 'new_end_date', 'new_payment_date', 'new_period_notes'
    ].map(id => document.getElementById(id));
    
    if (select.value === 'add_new') {
        // Show new period form
        newPeriodForm.classList.remove('hidden');
        // Hide upload sections
        uploadSections.forEach(section => section.classList.add('hidden'));
        // Remove required from dropdown since we're adding new
        select.removeAttribute('required');
        // Enable and set required for needed fields
        fields.forEach(el => { if (el) el.removeAttribute('disabled'); });
        ['new_period_name','new_start_date','new_end_date','new_payment_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.setAttribute('required','required');
        });
    } else {
        // Hide new period form
        newPeriodForm.classList.add('hidden');
        // Show upload sections if a period is selected
        if (select.value) {
            uploadSections.forEach(section => section.classList.remove('hidden'));
        }
        // Add required back
        select.setAttribute('required', 'required');
        // Disable and remove required from new period fields
        fields.forEach(el => { if (el) { el.setAttribute('disabled','disabled'); el.removeAttribute('required'); }});
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
        showErrorModal('Validation Error', 'Please fill in all required fields');
        return;
    }
    
    // Validate dates
    if (new Date(startDate) > new Date(endDate)) {
        showErrorModal('Validation Error', 'End date must be after start date');
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
            
            showSuccessModal('Success', 'Payroll period created successfully! You can now upload DTR cards for this period.');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorModal('Error', data.message || 'Failed to create payroll period');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        showErrorModal('Error', 'Error creating payroll period');
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
        showErrorModal('Validation Error', 'At least one employee DTR is required');
    }
}

// Handle DTR upload
document.getElementById('uploadDTRForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('=== DTR UPLOAD FORM SUBMISSION START ===');
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Log form data being sent
    console.log('Form Data:');
    const payrollPeriodId = formData.get('payroll_period_id');
    console.log('  Payroll Period ID:', payrollPeriodId);
    
    const employeeIds = formData.getAll('employee_id[]');
    console.log('  Employee IDs:', employeeIds);
    
    const files = formData.getAll('dtr_file[]');
    console.log('  Files count:', files.length);
    files.forEach((file, index) => {
        if (file instanceof File) {
            console.log(`    File ${index + 1}:`, {
                name: file.name,
                size: file.size,
                type: file.type
            });
        }
    });
    
    // Validate payroll period
    if (!payrollPeriodId) {
        console.error('Validation failed: No payroll period selected');
        showErrorModal('Validation Error', 'Please select a payroll period');
        return;
    }
    
    // Validate at least one employee selected
    const validEmployeeIds = employeeIds.filter(id => id && id !== '');
    if (validEmployeeIds.length === 0) {
        console.error('Validation failed: No employees selected');
        showErrorModal('Validation Error', 'Please select at least one employee');
        return;
    }
    
    // Validate files match employees
    const validFiles = files.filter(file => file instanceof File && file.size > 0);
    if (validFiles.length === 0) {
        console.error('Validation failed: No files selected');
        showErrorModal('Validation Error', 'Please select at least one DTR file to upload');
        return;
    }
    
    if (validFiles.length !== validEmployeeIds.length) {
        console.error('Validation failed: File count mismatch', {
            employees: validEmployeeIds.length,
            files: validFiles.length
        });
        showErrorModal('Validation Error', 'Please ensure each employee has a corresponding DTR file');
        return;
    }
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
    
    console.log('Sending upload request to upload-dtr-cards.php...');
    
    fetch('upload-dtr-cards.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('=== RESPONSE RECEIVED ===');
        console.log('Status:', response.status);
        console.log('Status Text:', response.statusText);
        console.log('Headers:', Object.fromEntries(response.headers.entries()));
        
        // Get response text first
        return response.text().then(text => {
            console.log('Raw response text:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON response:', data);
                return { response, data };
            } catch (parseError) {
                console.error('=== JSON PARSE ERROR ===');
                console.error('Failed to parse response as JSON:', parseError);
                console.error('Response text that failed to parse:', text.substring(0, 500));
                throw new Error('Invalid JSON response: ' + text.substring(0, 200));
            }
        });
    })
    .then(({ response, data }) => {
        console.log('=== UPLOAD RESULT ===');
        console.log('Success:', data.success);
        console.log('Message:', data.message);
        console.log('Uploaded Count:', data.uploaded || 0);
        console.log('Error Count:', data.errors ? data.errors.length : 0);
        
        if (data.errors && data.errors.length > 0) {
            console.error('=== UPLOAD ERRORS ===');
            data.errors.forEach((error, index) => {
                console.error(`Error ${index + 1}:`, error);
            });
        }
        
        if (data.success) {
            console.log('✅ Upload successful!');
            closeUploadModal();
            const successMsg = data.message || 'DTR cards uploaded successfully!';
            showSuccessModal('Success', successMsg);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            console.error('❌ Upload failed');
            console.error('Full error data:', data);
            let errorMsg = data.message || 'Unknown error occurred';
            
            // Add error details if available
            if (data.errors && data.errors.length > 0) {
                errorMsg += '<br><br><strong>Details:</strong><ul class="list-disc list-inside text-left mt-2">';
                data.errors.slice(0, 5).forEach(error => {
                    errorMsg += '<li>' + escapeHtml(error) + '</li>';
                });
                errorMsg += '</ul>';
                if (data.errors.length > 5) {
                    errorMsg += '<p class="text-sm mt-2">(and ' + (data.errors.length - 5) + ' more errors)</p>';
                }
            }
            
            showErrorModal('Upload Failed', errorMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('=== FETCH ERROR ===');
        console.error('Error Type:', error.name);
        console.error('Error Message:', error.message);
        console.error('Error Stack:', error.stack);
        console.error('Full Error Object:', error);
        
        showErrorModal('Upload Error', 'Error uploading DTR cards: ' + error.message + '. Please check console for details.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function viewDTRCard(dtr) {
    // Validate and fix file path
    let filePath = dtr.file_path || '';
    
    // Ensure file_path is valid and properly formatted
    if (!filePath || filePath === '0' || filePath === 'null' || filePath.trim() === '') {
        // Show error if file path is invalid
        const employeeName = escapeHtml((dtr.first_name || '') + ' ' + (dtr.last_name || ''));
        const periodName = escapeHtml(dtr.period_name || 'Custom Period');
        showErrorModal('File Not Found', 
            'The DTR card file path is missing or invalid for ' + employeeName + ' (' + periodName + ').<br><br>' +
            'The system attempted to automatically locate the file but could not find it.<br><br>' +
            'Please re-upload the DTR card or contact the administrator if the file should exist.');
        return;
    }
    
    // Normalize the path - ensure it includes the application base path
    // Remove leading dots and slashes
    filePath = filePath.replace(/^\.\//, '').replace(/^\.\.\//, '');
    
    // If not an absolute URL, ensure it includes the application base path
    if (!filePath.startsWith('http://') && !filePath.startsWith('https://')) {
        // Get the current page pathname (e.g., /nia-hris/dtr-management.php)
        const currentPath = window.location.pathname;
        
        // Extract the base path (e.g., /nia-hris from /nia-hris/dtr-management.php)
        let basePath = '';
        const pathParts = currentPath.split('/').filter(Boolean);
        if (pathParts.length > 0) {
            // Remove the filename to get the directory path
            basePath = '/' + pathParts[0]; // First part after root is usually the app name
        }
        
        // Remove leading slashes from file path
        const stripped = filePath.replace(/^\/+/, '');
        
        // Construct the full path
        if (basePath) {
            // Ensure basePath doesn't already start the stripped path
            if (!stripped.startsWith(basePath.replace(/^\//, '') + '/')) {
                filePath = basePath + '/' + stripped;
            } else {
                filePath = '/' + stripped;
            }
        } else {
            filePath = '/' + stripped;
        }
    }

    const content = `
        <div class="space-y-4">
            <!-- Employee Info -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-600">Employee</p>
                        <p class="font-semibold text-gray-900">${escapeHtml(dtr.first_name || '')} ${escapeHtml(dtr.last_name || '')}</p>
                        <p class="text-sm text-gray-600">${escapeHtml(dtr.employee_id || '')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Period</p>
                        <p class="font-semibold text-gray-900">${escapeHtml(dtr.period_name || 'Custom Period')}</p>
                        <p class="text-sm text-gray-600">${dtr.period_start_date ? new Date(dtr.period_start_date).toLocaleDateString() : 'N/A'} - ${dtr.period_end_date ? new Date(dtr.period_end_date).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
            </div>

            <!-- DTR Card Image -->
            <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                <img src="${escapeHtml(filePath)}" 
                     alt="DTR Card" 
                     class="max-w-full h-auto mx-auto rounded shadow-lg"
                     onerror="handleImageError(this, '${escapeHtml(filePath)}')">
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
                        <p class="font-medium">${escapeHtml(dtr.verifier_first || '')} ${escapeHtml(dtr.verifier_last || '')} ${dtr.verified_at ? 'on ' + new Date(dtr.verified_at).toLocaleString() : ''}</p>
                    </div>
                ` : ''}
                ${dtr.notes ? `
                    <div class="col-span-2">
                        <p class="text-gray-600">Notes</p>
                        <p class="font-medium">${escapeHtml(dtr.notes)}</p>
                    </div>
                ` : ''}
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="${escapeHtml(filePath)}" download class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
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

// Verify DTR Card Functions
function confirmVerifyDTR(dtrId, employeeName, periodName) {
    // Set the verify target info
    document.getElementById('verifyDTRTargetName').textContent = employeeName;
    document.getElementById('verifyDTRPeriodName').textContent = periodName;
    document.getElementById('verifyDTRId').value = dtrId;
    
    // Show the confirmation modal
    showVerifyDTRModal();
}

function showVerifyDTRModal() {
    const modal = document.getElementById('verifyDTRModal');
    const content = document.getElementById('verifyDTRModalContent');
    
    if (!modal || !content) return;
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.add('scale-100', 'opacity-100');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeVerifyDTRModal() {
    const modal = document.getElementById('verifyDTRModal');
    const content = document.getElementById('verifyDTRModalContent');
    
    if (!modal || !content) return;
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function verifyDTRCard() {
    const dtrId = document.getElementById('verifyDTRId').value;
    
    if (!dtrId) {
        showErrorModal('Error', 'DTR card ID is missing');
        return;
    }
    
    const submitBtn = document.getElementById('verifyDTRSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verifying...';
    
    // Submit verify request
    fetch('verify-dtr-card.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dtr_id: dtrId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the verify modal first
            closeVerifyDTRModal();
            
            // Wait for modal to close, then show success
            setTimeout(() => {
                showSuccessModal('Verification Successful', 'DTR card verified successfully! This card is now ready for payroll processing.');
                // Refresh the page to update the list
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }, 300);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            // Close the verify modal first
            closeVerifyDTRModal();
            
            // Wait for modal to close, then show error
            setTimeout(() => {
                showErrorModal('Verification Failed', data.message || 'Failed to verify DTR card');
            }, 300);
        }
    })
    .catch(error => {
        console.error('Verify error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Close the verify modal first
        closeVerifyDTRModal();
        
        // Wait for modal to close, then show error
        setTimeout(() => {
            showErrorModal('Network Error', 'A network error occurred while verifying the DTR card.');
        }, 300);
    });
}

// Delete DTR Card Functions
function confirmDeleteDTR(dtrId, employeeName, periodName) {
    // Set the delete target info
    document.getElementById('deleteDTRTargetName').textContent = employeeName;
    document.getElementById('deleteDTRPeriodName').textContent = periodName;
    document.getElementById('deleteDTRId').value = dtrId;
    
    // Show the confirmation modal
    showDeleteDTRModal();
}

function showDeleteDTRModal() {
    const modal = document.getElementById('deleteDTRModal');
    const content = document.getElementById('deleteDTRModalContent');
    
    if (!modal || !content) return;
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.add('scale-100', 'opacity-100');
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);
}

function closeDeleteDTRModal() {
    const modal = document.getElementById('deleteDTRModal');
    const content = document.getElementById('deleteDTRModalContent');
    
    if (!modal || !content) return;
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function deleteDTRCard() {
    const dtrId = document.getElementById('deleteDTRId').value;
    
    if (!dtrId) {
        showErrorModal('Error', 'DTR card ID is missing');
        return;
    }
    
    const submitBtn = document.getElementById('deleteDTRSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    
    // Create form data
    const formData = new FormData();
    formData.append('dtr_id', dtrId);
    
    // Submit delete request
    fetch('delete-dtr-card.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the delete modal first
            closeDeleteDTRModal();
            
            // Wait for modal to close, then show success
            setTimeout(() => {
                showSuccessModal('Delete Successful', data.message || 'DTR card deleted successfully');
                // Refresh the page to update the list
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }, 300);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            // Close the delete modal first
            closeDeleteDTRModal();
            
            // Wait for modal to close, then show error
            setTimeout(() => {
                showErrorModal('Delete Failed', data.message || 'Failed to delete DTR card');
            }, 300);
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Close the delete modal first
        closeDeleteDTRModal();
        
        // Wait for modal to close, then show error
        setTimeout(() => {
            showErrorModal('Network Error', 'A network error occurred while deleting the DTR card.');
        }, 300);
    });
}

// Success Modal Functions
function showSuccessModal(title, message) {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    const titleEl = document.getElementById('successTitle');
    const messageEl = document.getElementById('successMessage');
    
    if (!modal || !content || !titleEl || !messageEl) return;
    
    titleEl.textContent = title || 'Success!';
    messageEl.textContent = message || 'Operation completed successfully.';
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.add('scale-100');
        content.classList.remove('scale-95');
    }, 10);
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    if (!modal || !content) return;
    
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle image loading errors
function handleImageError(img, filePath) {
    img.onerror = null; // Prevent infinite loop
    img.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23f3f4f6" width="400" height="300"/%3E%3Ctext fill="%239ca3af" font-family="sans-serif" font-size="16" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3EFile Not Found%3C/text%3E%3C/svg%3E';
    
    // Add error message below the image
    const errorMsg = document.createElement('p');
    errorMsg.className = 'text-red-600 text-center mt-2 text-sm';
    errorMsg.textContent = 'Image failed to load. Path: ' + filePath;
    img.parentElement.appendChild(errorMsg);
}

// Error Modal Functions
function showErrorModal(title, message) {
    const modal = document.getElementById('errorModal');
    const content = document.getElementById('errorModalContent');
    const titleEl = document.getElementById('errorTitle');
    const messageEl = document.getElementById('errorMessage');
    
    if (!modal || !content || !titleEl || !messageEl) return;
    
    titleEl.textContent = title || 'Error';
    
    // Check if message contains HTML
    if (typeof message === 'string' && (message.includes('<') || message.includes('&lt;'))) {
        messageEl.innerHTML = message;
    } else {
        messageEl.textContent = message || 'An error occurred.';
    }
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.add('scale-100');
        content.classList.remove('scale-95');
    }, 10);
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    const content = document.getElementById('errorModalContent');
    
    if (!modal || !content) return;
    
    content.classList.remove('scale-100');
    content.classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const successModal = document.getElementById('successModal');
    const errorModal = document.getElementById('errorModal');
    const deleteDTRModal = document.getElementById('deleteDTRModal');
    const verifyDTRModal = document.getElementById('verifyDTRModal');
    
    if (event.target === successModal) {
        closeSuccessModal();
    }
    if (event.target === errorModal) {
        closeErrorModal();
    }
    if (event.target === deleteDTRModal) {
        closeDeleteDTRModal();
    }
    if (event.target === verifyDTRModal) {
        closeVerifyDTRModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const successModal = document.getElementById('successModal');
        const errorModal = document.getElementById('errorModal');
        const deleteDTRModal = document.getElementById('deleteDTRModal');
        const verifyDTRModal = document.getElementById('verifyDTRModal');
        
        if (successModal && !successModal.classList.contains('hidden')) {
            closeSuccessModal();
        }
        if (errorModal && !errorModal.classList.contains('hidden')) {
            closeErrorModal();
        }
        if (deleteDTRModal && !deleteDTRModal.classList.contains('hidden')) {
            closeDeleteDTRModal();
        }
        if (verifyDTRModal && !verifyDTRModal.classList.contains('hidden')) {
            closeVerifyDTRModal();
        }
    }
});
</script>

<!-- Success Message Modal -->
<div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center h-full w-full hidden z-[10000]">
    <div class="relative mx-auto p-8 border w-96 shadow-2xl rounded-2xl bg-white transform transition-all duration-300 scale-95" id="successModalContent">
        <div class="text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <i class="fas fa-check text-green-600 text-2xl"></i>
            </div>
            
            <!-- Success Title -->
            <h3 class="text-xl font-bold text-gray-900 mb-4" id="successTitle">Success!</h3>
            
            <!-- Success Message -->
            <p class="text-gray-600 mb-8" id="successMessage">Operation completed successfully.</p>
            
            <!-- Success Button -->
            <button onclick="closeSuccessModal()" class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg font-medium transition-colors shadow-md w-full">
                <i class="fas fa-check mr-2"></i>Continue
            </button>
        </div>
    </div>
</div>

<!-- Error Message Modal -->
<div id="errorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center h-full w-full hidden z-[10000]">
    <div class="relative mx-auto p-8 border w-96 shadow-2xl rounded-2xl bg-white transform transition-all duration-300 scale-95 max-w-md" id="errorModalContent">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            
            <!-- Error Title -->
            <h3 class="text-xl font-bold text-gray-900 mb-4" id="errorTitle">Error</h3>
            
            <!-- Error Message -->
            <div class="text-gray-600 mb-8 break-words text-left max-h-96 overflow-y-auto" id="errorMessage">An error occurred.</div>
            
            <!-- Error Button -->
            <button onclick="closeErrorModal()" class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-medium transition-colors shadow-md w-full">
                <i class="fas fa-times mr-2"></i>Close
            </button>
        </div>
    </div>
</div>

<!-- Verify DTR Card Confirmation Modal -->
<div id="verifyDTRModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-[9999] flex items-center justify-center p-4" onclick="closeVerifyDTRModal()">
    <div class="relative w-full max-w-md mx-auto bg-white rounded-xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="verifyDTRModalContent" onclick="event.stopPropagation()">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mr-3">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Verify DTR Card</h3>
                        <p class="text-sm text-gray-500">Confirm verification</p>
                    </div>
                </div>
                <button onclick="closeVerifyDTRModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Information -->
            <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-info-circle text-blue-600 mr-2 text-lg"></i>
                    <span class="text-sm font-bold text-blue-800">Verification Information</span>
                </div>
                <p class="text-sm text-blue-700">You are about to verify the DTR card for <strong id="verifyDTRTargetName" class="text-blue-900"></strong> for period <strong id="verifyDTRPeriodName" class="text-blue-900"></strong>.</p>
                <p class="text-sm text-blue-700 mt-2">This confirms the DTR is valid and ready for payroll processing.</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3 pt-4">
                <input type="hidden" id="verifyDTRId" value="">
                <button type="button" onclick="closeVerifyDTRModal()" 
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="button" onclick="verifyDTRCard()" id="verifyDTRSubmitBtn"
                        class="flex-1 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <i class="fas fa-check-circle mr-2"></i>Verify DTR Card
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete DTR Card Confirmation Modal -->
<div id="deleteDTRModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-[9999] flex items-center justify-center p-4" onclick="closeDeleteDTRModal()">
    <div class="relative w-full max-w-md mx-auto bg-white rounded-xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="deleteDTRModalContent" onclick="event.stopPropagation()">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mr-3">
                        <i class="fas fa-trash-alt text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Delete DTR Card</h3>
                        <p class="text-sm text-gray-500">Confirm deletion</p>
                    </div>
                </div>
                <button onclick="closeDeleteDTRModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Warning -->
            <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2 text-lg"></i>
                    <span class="text-sm font-bold text-red-800">Warning: This action cannot be undone!</span>
                </div>
                <p class="text-sm text-red-700">You are about to permanently delete the DTR card for <strong id="deleteDTRTargetName" class="text-red-900"></strong> for period <strong id="deleteDTRPeriodName" class="text-red-900"></strong>.</p>
                <p class="text-sm text-red-700 mt-2">This will also delete the associated file from the server.</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3 pt-4">
                <input type="hidden" id="deleteDTRId" value="">
                <button type="button" onclick="closeDeleteDTRModal()" 
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="button" onclick="deleteDTRCard()" id="deleteDTRSubmitBtn"
                        class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>
