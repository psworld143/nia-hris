<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Leave Reports';

// Get filter parameters
$year_filter = $_GET['year'] ?? date('Y');
$month_filter = $_GET['month'] ?? '';
$department_filter = $_GET['department'] ?? '';
$employee_type_filter = $_GET['employee_type'] ?? 'all';
$leave_type_filter = $_GET['leave_type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$current_tab = $_GET['tab'] ?? 'overview'; // overview, requests, balances, trends

// Get departments for filter
$departments = [];
$dept_query = "SELECT DISTINCT name FROM departments WHERE is_active = 1 ORDER BY name";
$dept_result = mysqli_query($conn, $dept_query);
if ($dept_result) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $departments[] = $row['name'];
    }
}

// Get leave types for filter
$leave_types = [];
$lt_query = "SELECT id, name FROM leave_types WHERE is_active = 1 ORDER BY name";
$lt_result = mysqli_query($conn, $lt_query);
if ($lt_result) {
    while ($row = mysqli_fetch_assoc($lt_result)) {
        $leave_types[] = $row;
    }
}

// Get overview statistics
$overview_stats = [];

// Check if tables have data
$employee_requests_count = 0;
$faculty_requests_count = 0;

$emp_count_query = "SELECT COUNT(*) as count FROM employee_leave_requests";
$emp_count_result = mysqli_query($conn, $emp_count_query);
if ($emp_count_result) {
    $row = mysqli_fetch_assoc($emp_count_result);
    $employee_requests_count = $row['count'];
}

// Faculty table removed - using employees only
$fac_count_result = false;
if (false) {
    $row = mysqli_fetch_assoc($fac_count_result);
    $faculty_requests_count = $row['count'];
}

echo "<script>console.log('Employee requests: $employee_requests_count, Faculty requests: $faculty_requests_count');</script>";

// Total leave requests for the year
if ($employee_requests_count > 0 || $faculty_requests_count > 0) {
    $total_requests_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved_by_head' THEN 1 ELSE 0 END) as approved_by_head,
        SUM(CASE WHEN status = 'approved_by_hr' THEN 1 ELSE 0 END) as approved_by_hr,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM (
            SELECT status COLLATE utf8mb4_unicode_ci as status FROM employee_leave_requests WHERE YEAR(start_date) = ?
        ) as combined_requests";

    $total_stmt = mysqli_prepare($conn, $total_requests_query);
    if ($total_stmt) {
        mysqli_stmt_bind_param($total_stmt, "i", $year_filter);
        if (mysqli_stmt_execute($total_stmt)) {
            $result = mysqli_stmt_get_result($total_stmt);
            if ($result) {
                $overview_stats['requests'] = mysqli_fetch_assoc($result);
            }
        } else {
            error_log("Error executing total requests query: " . mysqli_stmt_error($total_stmt));
        }
        mysqli_stmt_close($total_stmt);
    } else {
        error_log("Error preparing total requests query: " . mysqli_error($conn));
    }
} else {
    echo "<script>console.log('No leave requests data available, using defaults');</script>";
}

// If no statistics found, provide default values
if (!isset($overview_stats['requests'])) {
    $overview_stats['requests'] = [
        'total_requests' => 0,
        'pending' => 0,
        'approved_by_head' => 0,
        'approved_by_hr' => 0,
        'rejected' => 0,
        'cancelled' => 0
    ];
    echo "<script>console.log('Using default statistics (no data available)');</script>";
}

// Leave type distribution
if ($employee_requests_count > 0 || $faculty_requests_count > 0) {
    $leave_type_dist_query = "SELECT 
        lt.name as leave_type,
        COUNT(*) as request_count,
        SUM(CASE WHEN lr.status IN ('approved_by_head', 'approved_by_hr') THEN 1 ELSE 0 END) as approved_count
        FROM (
            SELECT leave_type_id, status COLLATE utf8mb4_unicode_ci as status FROM employee_leave_requests WHERE YEAR(start_date) = ?
        ) as lr
        JOIN leave_types lt ON lr.leave_type_id = lt.id
        GROUP BY lt.id, lt.name
        ORDER BY request_count DESC";

    $lt_dist_stmt = mysqli_prepare($conn, $leave_type_dist_query);
    if ($lt_dist_stmt) {
        mysqli_stmt_bind_param($lt_dist_stmt, "i", $year_filter);
        if (mysqli_stmt_execute($lt_dist_stmt)) {
            $result = mysqli_stmt_get_result($lt_dist_stmt);
            if ($result) {
                $overview_stats['leave_type_distribution'] = $result;
            }
        } else {
            error_log("Error executing leave type distribution query: " . mysqli_stmt_error($lt_dist_stmt));
        }
        mysqli_stmt_close($lt_dist_stmt);
    } else {
        error_log("Error preparing leave type distribution query: " . mysqli_error($conn));
    }
} else {
    echo "<script>console.log('No leave requests data available for type distribution');</script>";
}

// If no leave type distribution found, provide default values
if (!isset($overview_stats['leave_type_distribution'])) {
    $overview_stats['leave_type_distribution'] = false;
    echo "<script>console.log('No leave type distribution data available');</script>";
}

// Department distribution
if ($employee_requests_count > 0 || $faculty_requests_count > 0) {
    $dept_dist_query = "SELECT 
        department,
        COUNT(*) as request_count,
        SUM(CASE WHEN lr.status IN ('approved_by_head', 'approved_by_hr') THEN 1 ELSE 0 END) as approved_count
        FROM (
            SELECT e.department COLLATE utf8mb4_unicode_ci as department, lr.status COLLATE utf8mb4_unicode_ci as status FROM employee_leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            WHERE YEAR(lr.start_date) = ?
        ) as lr
        WHERE department IS NOT NULL AND department != ''
        GROUP BY department
        ORDER BY request_count DESC";

    $dept_dist_stmt = mysqli_prepare($conn, $dept_dist_query);
    if ($dept_dist_stmt) {
        mysqli_stmt_bind_param($dept_dist_stmt, "i", $year_filter);
        if (mysqli_stmt_execute($dept_dist_stmt)) {
            $result = mysqli_stmt_get_result($dept_dist_stmt);
            if ($result) {
                $overview_stats['department_distribution'] = $result;
            }
        } else {
            error_log("Error executing department distribution query: " . mysqli_stmt_error($dept_dist_stmt));
        }
        mysqli_stmt_close($dept_dist_stmt);
    } else {
        error_log("Error preparing department distribution query: " . mysqli_error($conn));
    }
}

// If no department distribution found, provide default values
if (!isset($overview_stats['department_distribution'])) {
    $overview_stats['department_distribution'] = false;
}

// Monthly trends for the current year
$monthly_trends = [];
if ($employee_requests_count > 0 || $faculty_requests_count > 0) {
    for ($month = 1; $month <= 12; $month++) {
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        
        $monthly_query = "SELECT COUNT(*) as request_count
                          FROM (
                              SELECT start_date FROM employee_leave_requests 
                              WHERE YEAR(start_date) = ? AND MONTH(start_date) = ?
                          ) as monthly_requests";
        
        $monthly_stmt = mysqli_prepare($conn, $monthly_query);
        if ($monthly_stmt) {
            mysqli_stmt_bind_param($monthly_stmt, "ii", $year_filter, $month);
            if (mysqli_stmt_execute($monthly_stmt)) {
                $result = mysqli_stmt_get_result($monthly_stmt);
                if ($result) {
                    $row = mysqli_fetch_assoc($result);
                    $count = $row ? $row['request_count'] : 0;
                    
                    $monthly_trends[] = [
                        'month' => $month_name,
                        'count' => $count
                    ];
                }
            } else {
                error_log("Error executing monthly query for month $month: " . mysqli_stmt_error($monthly_stmt));
            }
            mysqli_stmt_close($monthly_stmt);
        } else {
            error_log("Error preparing monthly query for month $month: " . mysqli_error($conn));
        }
    }
} else {
    // Create default monthly trends with zero counts
    for ($month = 1; $month <= 12; $month++) {
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $monthly_trends[] = [
            'month' => $month_name,
            'count' => 0
        ];
    }
}

// Leave requests and balances tabs removed - only overview and trends available

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] == '1') {
    // Set headers for CSV download
    $filename = "leave_reports_" . $current_tab . "_" . $year_filter . "_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($current_tab === 'overview') {
        // Export overview statistics
        $headers = ['Metric', 'Value'];
        fputcsv($output, $headers);
        
        if (isset($overview_stats['requests'])) {
            fputcsv($output, ['Total Requests', $overview_stats['requests']['total_requests'] ?? 0]);
            fputcsv($output, ['Pending', $overview_stats['requests']['pending'] ?? 0]);
            fputcsv($output, ['Approved by Head', $overview_stats['requests']['approved_by_head'] ?? 0]);
            fputcsv($output, ['Approved by HR', $overview_stats['requests']['approved_by_hr'] ?? 0]);
            fputcsv($output, ['Rejected', $overview_stats['requests']['rejected'] ?? 0]);
            fputcsv($output, ['Cancelled', $overview_stats['requests']['cancelled'] ?? 0]);
        }
        
        // Export leave type distribution
        if (isset($overview_stats['leave_type_distribution'])) {
            fputcsv($output, []);
            fputcsv($output, ['Leave Type Distribution']);
            fputcsv($output, ['Leave Type', 'Request Count', 'Approved Count']);
            mysqli_data_seek($overview_stats['leave_type_distribution'], 0);
            while ($row = mysqli_fetch_assoc($overview_stats['leave_type_distribution'])) {
                fputcsv($output, [$row['leave_type'], $row['request_count'], $row['approved_count']]);
            }
        }
        
        // Export department distribution
        if (isset($overview_stats['department_distribution'])) {
            fputcsv($output, []);
            fputcsv($output, ['Department Distribution']);
            fputcsv($output, ['Department', 'Request Count', 'Approved Count']);
            mysqli_data_seek($overview_stats['department_distribution'], 0);
            while ($row = mysqli_fetch_assoc($overview_stats['department_distribution'])) {
                fputcsv($output, [$row['department'], $row['request_count'], $row['approved_count']]);
            }
        }
        
    } elseif ($current_tab === 'trends') {
        // Export monthly trends
        $headers = ['Month', 'Request Count'];
        fputcsv($output, $headers);
        
        foreach ($monthly_trends as $trend) {
            fputcsv($output, [$trend['month'], $trend['count']]);
        }
    }
    
    fclose($output);
    exit();
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-chart-bar mr-2"></i>Leave Reports & Analytics
                </h2>
                <p class="opacity-90">Comprehensive leave management analytics and reporting</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <button onclick="exportReport()" class="bg-white text-cyan-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-download mr-2"></i>Export Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($employee_requests_count == 0 && $faculty_requests_count == 0): ?>
<div class="mb-4 bg-yellow-50 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg">
    <div class="flex items-start">
        <i class="fas fa-exclamation-triangle text-lg mr-3 mt-0.5"></i>
        <div>
            <p class="font-semibold">No Leave Data Available</p>
            <p class="text-sm">The reports will show default/empty values. Add some leave requests to see actual data.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters Section -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-filter text-cyan-600 mr-2"></i>Report Filters
        </h3>
        <button type="button" onclick="clearAllFilters()" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium transition-colors">
            <i class="fas fa-redo mr-1"></i>Reset Filters
        </button>
    </div>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select name="year" id="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year_filter ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div>
            <label for="month" class="block text-sm font-medium text-gray-700 mb-2">Month</label>
            <select name="month" id="month" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m == $month_filter ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department" id="department" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $dept == $department_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="employee_type" class="block text-sm font-medium text-gray-700 mb-2">Employee Type
            </label>
            <select name="employee_type" id="employee_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <option value="all" <?php echo $employee_type_filter == 'all' ? 'selected' : ''; ?>>All Employees</option>
                <option value="employee" <?php echo $employee_type_filter == 'employee' ? 'selected' : ''; ?>>Staff/Admin</option>
                <option value="faculty" <?php echo $employee_type_filter == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
            </select>
        </div>
        
        <div>
            <label for="leave_type" class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
            <select name="leave_type" id="leave_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <option value="">All Leave Types</option>
                <?php foreach ($leave_types as $lt): ?>
                    <option value="<?php echo $lt['id']; ?>" <?php echo $lt['id'] == $leave_type_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" id="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved_by_head" <?php echo $status_filter == 'approved_by_head' ? 'selected' : ''; ?>>Approved by Head</option>
                <option value="approved_by_hr" <?php echo $status_filter == 'approved_by_hr' ? 'selected' : ''; ?>>Approved by HR</option>
                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div>
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
        </div>
        
        <div>
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
        </div>
        
        <div class="flex items-end col-span-1 md:col-span-2 lg:col-span-1">
            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                <i class="fas fa-search mr-2"></i>Apply Filters
            </button>
        </div>
        
        <!-- Hidden fields to preserve tab state -->
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
    </form>
</div>

<!-- Tabs -->
<div class="bg-white rounded-xl shadow-lg p-4 mb-6">
    <nav class="flex space-x-4">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'overview'])); ?>" 
           class="<?php echo $current_tab === 'overview' ? 'bg-cyan-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> px-6 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-chart-pie mr-2"></i>Overview
        </a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'trends'])); ?>" 
           class="<?php echo $current_tab === 'trends' ? 'bg-cyan-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?> px-6 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-chart-line mr-2"></i>Trends
        </a>
    </nav>
</div>

<!-- Content -->
<?php if ($current_tab === 'overview'): ?>
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total Requests</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $overview_stats['requests']['total_requests'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $overview_stats['requests']['pending'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo ($overview_stats['requests']['approved_by_head'] ?? 0) + ($overview_stats['requests']['approved_by_hr'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-times text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $overview_stats['requests']['rejected'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Leave Type Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-pie text-cyan-600 mr-2"></i>Leave Type Distribution
            </h3>
                        <div class="space-y-3">
                            <?php if (isset($overview_stats['leave_type_distribution']) && $overview_stats['leave_type_distribution']): ?>
                                <?php while ($row = mysqli_fetch_assoc($overview_stats['leave_type_distribution'])): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['leave_type']); ?></span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-900"><?php echo $row['request_count']; ?></span>
                                            <span class="text-xs text-green-600">(<?php echo $row['approved_count']; ?> approved)</span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-gray-500 py-8">
                                    <p>No leave type distribution data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

        <!-- Department Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-building text-cyan-600 mr-2"></i>Department Distribution
            </h3>
            <div class="space-y-3">
                <?php if (isset($overview_stats['department_distribution']) && $overview_stats['department_distribution']): ?>
                    <?php while ($row = mysqli_fetch_assoc($overview_stats['department_distribution'])): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-cyan-50 transition-colors">
                            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($row['department']); ?></span>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo $row['request_count']; ?></span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><?php echo $row['approved_count']; ?> approved</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-chart-bar text-4xl text-gray-300 mb-2"></i>
                        <p>No department distribution data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php elseif ($current_tab === 'trends'): ?>
    <!-- Monthly Trends Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-line text-cyan-600 mr-2"></i>Monthly Leave Request Trends (<?php echo $year_filter; ?>)
        </h3>
        <div class="h-64 flex items-end justify-between space-x-2">
            <?php if (!empty($monthly_trends)): ?>
                <?php 
                $max_count = max(array_column($monthly_trends, 'count'));
                $max_count = $max_count > 0 ? $max_count : 1;
                ?>
                <?php foreach ($monthly_trends as $trend): ?>
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-gray-200 rounded-t" style="height: <?php echo max(20, ($trend['count'] / $max_count) * 200); ?>px; background: linear-gradient(to top, #06b6d4, #22d3ee);"></div>
                        <div class="text-xs text-gray-600 mt-2 text-center rotate-45 origin-left"><?php echo substr($trend['month'], 0, 3); ?></div>
                        <div class="text-xs font-bold text-cyan-600"><?php echo $trend['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-full text-center text-gray-500 py-8">
                    <i class="fas fa-chart-line text-4xl text-gray-300 mb-2"></i>
                    <p>No data available for the selected year</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Year-over-Year Comparison -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calendar-alt text-cyan-600 mr-2"></i>Year-over-Year Comparison
        </h3>
        
        <?php
        // Get data for multiple years for comparison
        $years_data = [];
        $current_year = date('Y');
        $years_to_compare = [$current_year - 2, $current_year - 1, $current_year];
        
        foreach ($years_to_compare as $year) {
            $year_query = "SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'approved_by_hr' THEN 1 ELSE 0 END) as approved_requests,
                SUM(CASE WHEN status = 'approved_by_hr' THEN total_days ELSE 0 END) as approved_days
                FROM (
                    SELECT status, total_days FROM employee_leave_requests WHERE YEAR(start_date) = ?
                ) as yearly_requests";
            
            $year_stmt = mysqli_prepare($conn, $year_query);
            if ($year_stmt) {
                mysqli_stmt_bind_param($year_stmt, "i", $year);
                mysqli_stmt_execute($year_stmt);
                $year_result = mysqli_stmt_get_result($year_stmt);
                $year_data = mysqli_fetch_assoc($year_result);
                
                $years_data[] = [
                    'year' => $year,
                    'total_requests' => $year_data['total_requests'] ?? 0,
                    'approved_requests' => $year_data['approved_requests'] ?? 0,
                    'approved_days' => $year_data['approved_days'] ?? 0
                ];
                mysqli_stmt_close($year_stmt);
            }
        }
        ?>
        
        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-cyan-600 to-cyan-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Total Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Approved Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Approved Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Approval Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($years_data as $data): ?>
                        <tr class="hover:bg-cyan-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo $data['year']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><?php echo $data['total_requests']; ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"><?php echo $data['approved_requests']; ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($data['approved_days'], 1); ?> days</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $approval_rate = $data['total_requests'] > 0 ? ($data['approved_requests'] / $data['total_requests']) * 100 : 0;
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800">
                                    <?php echo number_format($approval_rate, 1); ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function exportReport() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', '1');
    window.location.href = currentUrl.toString();
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('select, input[type="date"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Only auto-submit for year, month, and employee_type changes
            if (['year', 'month', 'employee_type'].includes(this.name)) {
                this.closest('form').submit();
            }
        });
    });
});
</script>

<script>
function clearAllFilters() {
    // Reset all form fields to default values
    document.getElementById('year').value = '<?php echo date('Y'); ?>';
    document.getElementById('month').value = '';
    document.getElementById('department').value = '';
    document.getElementById('employee_type').value = 'all';
    document.getElementById('leave_type').value = '';
    document.getElementById('status').value = '';
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value = '';
    
    // Submit the form to apply the cleared filters
    document.querySelector('form').submit();
}

// Add visual feedback for form interactions
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select');
    const inputs = document.querySelectorAll('input[type="date"]');
    
    // Add enhanced focus effects
    [...selects, ...inputs].forEach(element => {
        element.addEventListener('focus', function() {
            this.style.transform = 'scale(1.02)';
            this.style.boxShadow = '0 4px 12px rgba(6, 182, 212, 0.15)';
        });
        
        element.addEventListener('blur', function() {
            this.style.transform = 'scale(1)';
            this.style.boxShadow = '';
        });
    });
    
    // Auto-submit on filter change (optional - can be removed if not desired)
    selects.forEach(select => {
        select.addEventListener('change', function() {
            // Add a small delay to allow for multiple quick changes
            clearTimeout(this.autoSubmitTimer);
            this.autoSubmitTimer = setTimeout(() => {
                document.querySelector('form').submit();
            }, 500);
        });
    });
});
</script>

