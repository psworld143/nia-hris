<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php?login=required&redirect=leave-accumulation-history');
    exit();
}

$page_title = 'Leave Accumulation History';

// Get parameters
$employee_id = $_GET['employee_id'] ?? '';
$employee_type = $_GET['employee_type'] ?? '';
$year_filter = $_GET['year'] ?? '';

// Get employee information
$employee_info = null;
if ($employee_id && $employee_type) {
    if ($employee_type === 'employee') {
        $query = "SELECT id, first_name, last_name, employee_id, department, position 
                 FROM employees WHERE employee_id = ? AND is_active = 1";
    } else {
        $query = "SELECT id, first_name, last_name, qrcode as employee_id, department, position 
                 FROM employees WHERE qrcode = ? AND is_active = 1";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employee_info = mysqli_fetch_assoc($result);
}

// Get accumulation history
$accumulation_history = [];
if ($employee_info) {
    $where_conditions = ["employee_id = ?", "employee_type = ?"];
    $params = [$employee_info['id'], $employee_type];
    $param_types = "is";
    
    if ($year_filter) {
        $where_conditions[] = "from_year = ?";
        $params[] = $year_filter;
        $param_types .= "i";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $query = "SELECT lah.*, lt.name as leave_type_name
              FROM leave_accumulation_history lah
              JOIN leave_types lt ON lah.leave_type_id = lt.id
              $where_clause
              ORDER BY from_year DESC, to_year DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $accumulation_history[] = $row;
    }
}

// Get leave balances for this employee
$leave_balances = [];
if ($employee_info) {
    $query = "SELECT elb.*, lt.name as leave_type_name
              FROM enhanced_leave_balances elb
              JOIN leave_types lt ON elb.leave_type_id = lt.id
              WHERE elb.employee_id = ? AND elb.employee_type = ?
              ORDER BY elb.year DESC, lt.name";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'is', $employee_info['id'], $employee_type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $leave_balances[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Leave Accumulation History</h1>
            <?php if ($employee_info): ?>
                <p class="text-gray-600"><?php echo htmlspecialchars($employee_info['first_name'] . ' ' . $employee_info['last_name']); ?> - <?php echo htmlspecialchars($employee_info['employee_id']); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex space-x-3">
            <a href="leave-allowance-management.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Back to Management
            </a>
        </div>
    </div>

    <?php if (!$employee_info): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Employee Not Found</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>The requested employee could not be found or is not active.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Employee Information Card -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Employee Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-seait-orange flex items-center justify-center text-white font-semibold text-lg">
                                <?php echo substr($employee_info['first_name'], 0, 1) . substr($employee_info['last_name'], 0, 1); ?>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee_info['first_name'] . ' ' . $employee_info['last_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee_info['employee_id']); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">Department</div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee_info['department']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">Position</div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee_info['position']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <form method="GET" class="flex space-x-4">
                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">
                    <input type="hidden" name="employee_type" value="<?php echo htmlspecialchars($employee_type); ?>">
                    
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Filter by Year</label>
                        <select name="year" id="year" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-seait-orange focus:border-transparent">
                            <option value="">All Years</option>
                            <?php
                            $current_year = date('Y');
                            for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-seait-orange hover:bg-orange-600 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave Balances Summary -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Leave Balances Summary</h3>
            </div>
            <div class="p-6">
                <?php if (empty($leave_balances)): ?>
                    <p class="text-gray-500 text-center py-4">No leave balance data available.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accumulated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Used</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($leave_balances as $balance): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $balance['year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($balance['leave_type_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $balance['base_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $balance['accumulated_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $balance['total_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $balance['used_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $balance['remaining_days'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $balance['remaining_days']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $balance['is_regular'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo $balance['is_regular'] ? 'Regular' : 'Probationary'; ?>
                                            </span>
                                            <?php if ($balance['can_accumulate']): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Can Accumulate
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Accumulation History -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Accumulation History</h3>
            </div>
            <div class="p-6">
                <?php if (empty($accumulation_history)): ?>
                    <p class="text-gray-500 text-center py-4">No accumulation history available.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accumulated Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Recorded</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($accumulation_history as $history): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $history['from_year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $history['to_year']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($history['leave_type_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        <?php echo $history['accumulated_days']; ?> days
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($history['reason']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($history['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

