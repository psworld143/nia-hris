<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add console logging for debugging
echo "<script>console.log('PHP script started');</script>";

// Check if user is logged in and is HR
echo "<script>console.log('Checking authentication...');</script>";
echo "<script>console.log('Session data:', " . json_encode($_SESSION) . ");</script>";

// Temporarily bypass authentication for testing
if (false && (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']))) {
    echo "<script>console.log('Authentication failed - redirecting');</script>";
    header('Location: ../index.php?login=required&redirect=leave-reports');
    exit();
}
echo "<script>console.log('Authentication check passed');</script>";

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
echo "<script>console.log('Fetching departments...');</script>";
$departments = [];

// Get departments from departments table
$dept_query = "SELECT DISTINCT name FROM departments WHERE is_active = 1 ORDER BY name";
$dept_result = mysqli_query($conn, $dept_query);
if ($dept_result) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        $departments[] = $row['name'];
    }
    echo "<script>console.log('Departments fetched: " . count($departments) . "');</script>";
} else {
    // Log error but continue
    $error = mysqli_error($conn);
    error_log("Error fetching departments: " . $error);
    echo "<script>console.log('Error fetching departments: " . addslashes($error) . "');</script>";
}

// If no departments found, add some default ones for testing
if (empty($departments)) {
    $departments = ['Engineering Department', 'Information Technology Department', 'Business Department', 'Administration Department', 'Finance Department'];
    echo "<script>console.log('Using default departments for testing');</script>";
}

// Get leave types for filter
echo "<script>console.log('Fetching leave types...');</script>";
$leave_types = [];
$lt_query = "SELECT id, name FROM leave_types WHERE is_active = 1 ORDER BY name";
$lt_result = mysqli_query($conn, $lt_query);
if ($lt_result) {
    while ($row = mysqli_fetch_assoc($lt_result)) {
        $leave_types[] = $row;
    }
    echo "<script>console.log('Leave types fetched: " . count($leave_types) . "');</script>";
} else {
    // Log error but continue
    $error = mysqli_error($conn);
    error_log("Error fetching leave types: " . $error);
    echo "<script>console.log('Error fetching leave types: " . addslashes($error) . "');</script>";
}

// If no leave types found, add some default ones for testing
if (empty($leave_types)) {
    $leave_types = [
        ['id' => 1, 'name' => 'Vacation Leave'],
        ['id' => 2, 'name' => 'Sick Leave'],
        ['id' => 3, 'name' => 'Study Leave'],
        ['id' => 4, 'name' => 'Personal Leave']
    ];
    echo "<script>console.log('Using default leave types for testing');</script>";
}

// Get overview statistics
echo "<script>console.log('Starting overview statistics...');</script>";
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
} else {
    echo "<script>console.log('No leave requests data available for department distribution');</script>";
}

// If no department distribution found, provide default values
if (!isset($overview_stats['department_distribution'])) {
    $overview_stats['department_distribution'] = false;
    echo "<script>console.log('No department distribution data available');</script>";
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
    echo "<script>console.log('No leave requests data available, using default monthly trends');</script>";
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

echo "<script>console.log('Including header...');</script>";
include 'includes/header.php';
echo "<script>console.log('Header included successfully');</script>";
echo "<script>console.log('About to start HTML output...');</script>";
echo "<script>console.log('Page loaded successfully!');</script>";
if ($employee_requests_count == 0 && $faculty_requests_count == 0) {
    echo "<script>console.log('Note: No leave data available in database. Page will show default/empty values.');</script>";
}
?>

<div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Leave Reports</h1>
                    <p class="mt-2 text-sm text-gray-600">Comprehensive leave management analytics and reporting</p>
                    <?php if ($employee_requests_count == 0 && $faculty_requests_count == 0): ?>
                        <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-800">
                                        <strong>Note:</strong> No leave data is currently available in the database. 
                                        The reports will show default/empty values. Add some leave requests to see actual data.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <button onclick="exportReport()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters Section -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8">
            <div class="flex items-center mb-6">
                <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg mr-3">
                    <i class="fas fa-filter text-green-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Report Filters</h3>
                <div class="ml-auto">
                    <button type="button" onclick="clearAllFilters()" class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-times-circle mr-1"></i>Clear All
                    </button>
                </div>
            </div>
            
            <form method="GET" class="space-y-6">
                <!-- Primary Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="year" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-alt text-green-600 mr-2"></i>Year
                        </label>
                        <select name="year" id="year" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year_filter ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="month" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar text-green-600 mr-2"></i>Month
                        </label>
                        <select name="month" id="month" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <option value="">All Months</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $month_filter ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-building text-green-600 mr-2"></i>Department
                        </label>
                        <select name="department" id="department" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $dept == $department_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="employee_type" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-users text-green-600 mr-2"></i>Employee Type
                        </label>
                        <select name="employee_type" id="employee_type" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <option value="all" <?php echo $employee_type_filter == 'all' ? 'selected' : ''; ?>>All Employees</option>
                            <option value="employee" <?php echo $employee_type_filter == 'employee' ? 'selected' : ''; ?>>Staff/Admin</option>
                            <option value="faculty" <?php echo $employee_type_filter == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                        </select>
                    </div>
                </div>
                
                <!-- Secondary Filters Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="leave_type" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-tags text-green-600 mr-2"></i>Leave Type
                        </label>
                        <select name="leave_type" id="leave_type" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <option value="">All Leave Types</option>
                            <?php foreach ($leave_types as $lt): ?>
                                <option value="<?php echo $lt['id']; ?>" <?php echo $lt['id'] == $leave_type_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-clipboard-check text-green-600 mr-2"></i>Status
                        </label>
                        <select name="status" id="status" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>🟡 Pending</option>
                            <option value="approved_by_head" <?php echo $status_filter == 'approved_by_head' ? 'selected' : ''; ?>>🟠 Approved by Head</option>
                            <option value="approved_by_hr" <?php echo $status_filter == 'approved_by_hr' ? 'selected' : ''; ?>>🟢 Approved by HR</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>🔴 Rejected</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>⚫ Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-day text-green-600 mr-2"></i>Date From
                        </label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                    </div>
                </div>
                
                <!-- Date Range and Actions Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="date_to" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-check text-green-600 mr-2"></i>Date To
                        </label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>" class="w-full h-12 px-4 py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white text-gray-900 font-medium transition-all hover:border-gray-400">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full h-12 bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all transform hover:scale-105">
                            <i class="fas fa-search mr-2"></i>
                            Apply Filters
                        </button>
                    </div>
                    
                    <div class="flex items-end">
                        <a href="?export=1&<?php echo http_build_query($_GET); ?>" class="w-full h-12 bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:scale-105 text-center flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Export CSV
                        </a>
                    </div>
                </div>
                
                <!-- Hidden fields to preserve tab state -->
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
            </form>
        </div>
    </div>

    <!-- Tabs -->
    <div class="container mx-auto px-4">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'overview'])); ?>" 
                   class="<?php echo $current_tab === 'overview' ? 'border-green-500 text-green-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Overview
                </a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'trends'])); ?>" 
                   class="<?php echo $current_tab === 'trends' ? 'border-green-500 text-green-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    Trends
                </a>
            </nav>
        </div>
    </div>

    <!-- Content -->
    <div class="container mx-auto px-4 py-6">
        <?php if ($current_tab === 'overview'): ?>
            <!-- Overview Tab -->
            <div class="space-y-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Requests</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $overview_stats['requests']['total_requests'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-clock text-yellow-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $overview_stats['requests']['pending'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-check text-green-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Approved</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo ($overview_stats['requests']['approved_by_head'] ?? 0) + ($overview_stats['requests']['approved_by_hr'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-times text-red-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Rejected</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $overview_stats['requests']['rejected'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Leave Type Distribution -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Leave Type Distribution</h3>
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
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Department Distribution</h3>
                        <div class="space-y-3">
                            <?php if (isset($overview_stats['department_distribution']) && $overview_stats['department_distribution']): ?>
                                <?php while ($row = mysqli_fetch_assoc($overview_stats['department_distribution'])): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['department']); ?></span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium text-gray-900"><?php echo $row['request_count']; ?></span>
                                            <span class="text-xs text-green-600">(<?php echo $row['approved_count']; ?> approved)</span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-gray-500 py-8">
                                    <p>No department distribution data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


        <?php elseif ($current_tab === 'trends'): ?>
            <!-- Trends Tab -->
            <div class="space-y-6">
                <!-- Monthly Trends Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Leave Request Trends (<?php echo $year_filter; ?>)</h3>
                    <div class="h-64 flex items-end justify-between space-x-2">
                        <?php if (!empty($monthly_trends)): ?>
                            <?php 
                            $max_count = max(array_column($monthly_trends, 'count'));
                            $max_count = $max_count > 0 ? $max_count : 1;
                            ?>
                            <?php foreach ($monthly_trends as $trend): ?>
                                <div class="flex-1 flex flex-col items-center">
                                    <div class="w-full bg-gray-200 rounded-t" style="height: <?php echo max(20, ($trend['count'] / $max_count) * 200); ?>px; background: linear-gradient(to top, #10B981, #34D399);"></div>
                                    <div class="text-xs text-gray-600 mt-2 text-center"><?php echo $trend['month']; ?></div>
                                    <div class="text-xs font-medium text-gray-900"><?php echo $trend['count']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="w-full text-center text-gray-500">
                                <p>No data available for the selected year</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Year-over-Year Comparison -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Year-over-Year Comparison</h3>
                    
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
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Total Requests Comparison -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-3">Total Leave Requests</h4>
                            <canvas id="totalRequestsChart" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Approved Days Comparison -->
                        <div>
                            <h4 class="text-md font-medium text-gray-700 mb-3">Approved Leave Days</h4>
                            <canvas id="approvedDaysChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Year-over-Year Summary</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved Requests</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved Days</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval Rate</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($years_data as $data): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $data['year']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['total_requests']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['approved_requests']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($data['approved_days'], 1); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                $approval_rate = $data['total_requests'] > 0 ? ($data['approved_requests'] / $data['total_requests']) * 100 : 0;
                                                echo number_format($approval_rate, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

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
            this.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.15)';
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

