<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Leave Management';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';
// Tab filter removed - only employees now

// Build queries for leave requests based on tab filter
$all_results = [];

// Query for all employees
{
    $employee_where_conditions = [];
    $employee_params = [];
    $employee_types = '';
    
    if ($status_filter) {
        $employee_where_conditions[] = "elr.status = ?";
        $employee_params[] = $status_filter;
        $employee_types .= 's';
    }
    
    if ($date_from) {
        $employee_where_conditions[] = "elr.start_date >= ?";
        $employee_params[] = $date_from;
        $employee_types .= 's';
    }
    
    if ($date_to) {
        $employee_where_conditions[] = "elr.end_date <= ?";
        $employee_params[] = $date_to;
        $employee_types .= 's';
    }
    
    $employee_where_clause = !empty($employee_where_conditions) ? "WHERE " . implode(" AND ", $employee_where_conditions) : "";
    
    $employee_query = "SELECT elr.*, 
                      e.first_name, e.last_name, e.employee_id, e.department, e.employee_type,
                      lt.name as leave_type_name,
                      dh.first_name as head_first_name, dh.last_name as head_last_name,
                      hr.first_name as hr_first_name, hr.last_name as hr_last_name,
                      'employee' as source_table
                      FROM employee_leave_requests elr
                      JOIN employees e ON elr.employee_id = e.id
                      JOIN leave_types lt ON elr.leave_type_id = lt.id
                      LEFT JOIN employees dh ON elr.department_head_id = dh.id
                      LEFT JOIN employees hr ON elr.hr_approver_id = hr.id
                      $employee_where_clause";
    
    if ($department_filter) {
        $employee_query .= " AND e.department = ?";
        $employee_params[] = $department_filter;
        $employee_types .= 's';
    }
    
    if ($search) {
        $employee_query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
        $search_param = "%$search%";
        $employee_params[] = $search_param;
        $employee_params[] = $search_param;
        $employee_params[] = $search_param;
        $employee_types .= 'sss';
    }
    
    $employee_query .= " ORDER BY elr.created_at DESC";
    
    // Execute employee query
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    if ($employee_stmt) {
        if (!empty($employee_params)) {
            mysqli_stmt_bind_param($employee_stmt, $employee_types, ...$employee_params);
        }
        mysqli_stmt_execute($employee_stmt);
        $employee_result = mysqli_stmt_get_result($employee_stmt);
        
        while ($row = mysqli_fetch_assoc($employee_result)) {
            $all_results[] = $row;
        }
    }
}

// Query for employee
{
    $employee_where_conditions = [];
    $employee_params = [];
    $employee_types = '';
    
    if ($status_filter) {
        $employee_where_conditions[] = "flr.status = ?";
        $employee_params[] = $status_filter;
        $employee_types .= 's';
    }
    
    if ($date_from) {
        $employee_where_conditions[] = "flr.start_date >= ?";
        $employee_params[] = $date_from;
        $employee_types .= 's';
    }
    
    if ($date_to) {
        $employee_where_conditions[] = "flr.end_date <= ?";
        $employee_params[] = $date_to;
        $employee_types .= 's';
    }
    
    if ($department_filter) {
        $employee_where_conditions[] = "e.department = ?";
        $employee_params[] = $department_filter;
        $employee_types .= 's';
    }
    
    if ($search) {
        $employee_where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR f.id LIKE ?)";
        $search_param = "%$search%";
        $employee_params[] = $search_param;
        $employee_params[] = $search_param;
        $employee_params[] = $search_param;
        $employee_types .= 'sss';
    }
    
    $employee_where_clause = !empty($employee_where_conditions) ? "WHERE " . implode(" AND ", $employee_where_conditions) : "";
    
    // Employee table removed - all employees use employee_leave_requests
    // This section is no longer needed
}

// Sort all results by created_at DESC
usort($all_results, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Calculate statistics for each tab
$stats = [];

// Employee statistics
{
    $employee_stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN elr.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN elr.status = 'approved_by_head' THEN 1 ELSE 0 END) as approved_by_head,
        SUM(CASE WHEN elr.status = 'approved_by_hr' THEN 1 ELSE 0 END) as approved_by_hr,
        SUM(CASE WHEN elr.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN elr.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM employee_leave_requests elr
        JOIN employees e ON elr.employee_id = e.id";
    
    $employee_stats_result = mysqli_query($conn, $employee_stats_query);
    if ($employee_stats_result) {
        $stats['employee'] = mysqli_fetch_assoc($employee_stats_result);
    }
}

// Employee statistics
{
    $employee_stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN flr.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN flr.status = 'approved_by_head' THEN 1 ELSE 0 END) as approved_by_head,
        SUM(CASE WHEN flr.status = 'approved_by_hr' THEN 1 ELSE 0 END) as approved_by_hr,
        SUM(CASE WHEN flr.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN flr.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM employee_leave_requests flr
        JOIN employees f ON flr.employee_id = f.id";
    
    $employee_stats_result = mysqli_query($conn, $employee_stats_query);
    if ($employee_stats_result) {
        $stats['employee'] = mysqli_fetch_assoc($employee_stats_result);
    }
}

// Get departments for filter from both tables
$departments = [];
$employee_depts_query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != ''";
$employee_depts_result = mysqli_query($conn, $employee_depts_query);
while ($row = mysqli_fetch_assoc($employee_depts_result)) {
    $departments[] = $row['department'];
}

$employee_depts_query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != ''";
$employee_depts_result = mysqli_query($conn, $employee_depts_query);
while ($row = mysqli_fetch_assoc($employee_depts_result)) {
    $departments[] = $row['department'];
}

$departments = array_unique($departments);
sort($departments);

include 'includes/header.php';
?>

<style>
/* Custom scrollbar styling for Employee Details */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #059669;
}

/* Firefox scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #10b981 #f1f5f9;
}

/* Modal height adjustments for mobile */
@media (max-width: 1024px) {
    .modal-height-mobile {
        height: calc(100vh - 2rem) !important;
        max-height: calc(100vh - 2rem) !important;
        margin: 1rem !important;
    }
    
    .scrollable-content-mobile {
        max-height: calc(100vh - 280px) !important;
    }
    
    .form-scrollable-content {
        max-height: calc(100vh - 220px) !important;
    }
}

/* Additional spacing improvements */
.modal-spacing {
    padding: 2rem;
}

@media (max-width: 768px) {
    .modal-spacing {
        padding: 1rem;
    }
    
    .modal-height-mobile {
        margin: 0.5rem !important;
        height: calc(100vh - 1rem) !important;
        max-height: calc(100vh - 1rem) !important;
    }
    
    .scrollable-content-mobile {
        max-height: calc(100vh - 250px) !important;
    }
}

/* Prevent content overlap with modal boundaries */
.prevent-overlap {
    margin-bottom: 2rem;
}

/* Ensure history section has proper bottom padding */
.history-section {
    padding-bottom: 2rem;
}

/* Improved scrollable content calculations */
.employee-details-scroll {
    height: calc(100% - 4rem);
    min-height: 400px;
}

/* Ensure proper spacing in scrollable areas */
.scrollable-content {
    padding-bottom: 3rem;
}

/* History list improvements */
.history-list-container {
    height: 20rem; /* 320px - equivalent to h-80 */
    min-height: 20rem; /* Consistent with h-80 */
}

/* Responsive adjustments for mobile */
@media (max-width: 768px) {
    .history-list-container {
        height: 16rem; /* Slightly smaller on mobile */
        min-height: 16rem;
    }
}

/* Secure delete modal improvements */
#secureDeleteModal {
    backdrop-filter: blur(4px);
}

#secureDeleteModalContent {
    max-height: 90vh;
    overflow-y: auto;
}

/* Ensure all modal content is properly layered */
#secureDeleteModal .relative {
    position: relative;
    z-index: 1;
}

/* Form scrollable area improvements */
.form-scrollable-content {
    padding-bottom: 2rem;
    scroll-behavior: smooth;
    position: relative;
}

.form-scrollable-content::-webkit-scrollbar {
    width: 8px;
}

.form-scrollable-content::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
    margin: 4px 0;
}

.form-scrollable-content::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #10b981, #059669);
    border-radius: 4px;
    border: 1px solid #f8fafc;
}

.form-scrollable-content::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #059669, #047857);
}

/* Firefox scrollbar for form */
.form-scrollable-content {
    scrollbar-width: thin;
    scrollbar-color: #10b981 #f8fafc;
}

/* Scroll fade effect */
.form-scrollable-content::before {
    content: '';
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(to bottom, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
    pointer-events: none;
}

.form-scrollable-content::after {
    content: '';
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    height: 20px;
    background: linear-gradient(to top, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
    pointer-events: none;
}

/* Beautiful Modal Animations */
.modal-enter {
    animation: modalEnter 0.3s ease-out forwards;
}

.modal-exit {
    animation: modalExit 0.3s ease-in forwards;
}

@keyframes modalEnter {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes modalExit {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
}

/* Success and Error Modal Styling */
.success-icon-bounce {
    animation: successBounce 0.6s ease-out;
}

.error-icon-shake {
    animation: errorShake 0.6s ease-out;
}

@keyframes successBounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

@keyframes errorShake {
    0%, 100% {
        transform: translateX(0);
    }
    10%, 30%, 50%, 70%, 90% {
        transform: translateX(-5px);
    }
    20%, 40%, 60%, 80% {
        transform: translateX(5px);
    }
}
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-calendar-alt mr-2"></i>Leave Management
                </h2>
                <p class="opacity-90">Manage and approve employee leave requests</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <button onclick="openCreateLeaveModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Employee Leave
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Requests</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['employee']['total_requests'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Approved</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo ($stats['employee']['approved_by_head'] ?? 0) + ($stats['employee']['approved_by_hr'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['employee']['pending'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Rejected</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['employee']['rejected'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Leave Requests -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <!-- Filters Section -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-filter text-green-500 mr-2"></i>Filters
        </h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            
            <div>
                <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-info-circle text-green-500 mr-1"></i>Status
                </label>
                <select name="status" id="status-filter" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved_by_head" <?php echo $status_filter === 'approved_by_head' ? 'selected' : ''; ?>>Approved by Head</option>
                    <option value="approved_by_hr" <?php echo $status_filter === 'approved_by_hr' ? 'selected' : ''; ?>>Approved by HR</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label for="department-filter" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-building text-green-500 mr-1"></i>Department
                </label>
                <select name="department" id="department-filter" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="date-from" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-day text-green-500 mr-1"></i>From Date
                </label>
                <input type="date" name="date_from" id="date-from" value="<?php echo htmlspecialchars($date_from ?? ''); ?>" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
            </div>
            
            <div>
                <label for="date-to" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-check text-green-500 mr-1"></i>To Date
                </label>
                <input type="date" name="date_to" id="date-to" value="<?php echo htmlspecialchars($date_to ?? ''); ?>" class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
            </div>
            
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search text-green-500 mr-1"></i>Search
                </label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Name, ID..." class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex-1 font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="leave-management.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
    
    <!-- Leave Requests Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Leave Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Dates</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($all_results)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-calendar-alt text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No leave requests found</p>
                            <p class="text-sm text-gray-500 mt-1">Try adjusting your filters or add a new leave request.</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($all_results as $leave): ?>
                    <tr class="hover:bg-green-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <span class="text-green-600 font-semibold">
                                        <?php echo strtoupper(substr($leave['first_name'] ?? '', 0, 1) . substr($leave['last_name'] ?? '', 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(($leave['first_name'] ?? '') . ' ' . ($leave['last_name'] ?? '')); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($leave['department'] ?? ''); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $leave['source_table'] === 'employee' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo $leave['source_table'] === 'employee' ? 'Employee' : 'Employee'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($leave['leave_type_name'] ?? ''); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></div>
                            <div class="text-gray-500 text-xs">to <?php echo date('M j, Y', strtotime($leave['end_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($leave['total_days'] ?? ''); ?> days</td>
                        <td class="px-6 py-4">
                            <?php
                            $status_colors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved_by_head' => 'bg-blue-100 text-blue-800',
                                'approved_by_hr' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800'
                            ];
                            $status_text = [
                                'pending' => 'Pending',
                                'approved_by_head' => 'Approved by Head',
                                'approved_by_hr' => 'Approved by HR',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled'
                            ];
                            $status = $leave['status'] ?? 'pending';
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $status_colors[$status]; ?>">
                                <?php echo $status_text[$status]; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewLeaveDetails(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php 
                            // Show approve/reject buttons for:
                            // - Employee leave requests that are pending (direct HR approval)
                            // - Employee leave requests that are approved by head (ready for HR approval)
                            $can_approve = false;
                            if ($leave['source_table'] === 'employee' && $leave['status'] === 'pending') {
                                $can_approve = true;
                            } elseif ($leave['source_table'] === 'employee' && $leave['status'] === 'approved_by_head') {
                                $can_approve = true;
                            }
                            
                            if ($can_approve): 
                            ?>
                            <button onclick="approveLeave(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-green-600 hover:text-green-800 mr-2">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="rejectLeave(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-red-600 hover:text-red-800 mr-2">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <?php endif; ?>
                            
                            <!-- Secure Delete Button -->
                            <button onclick="openSecureDeleteModal(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>', '<?php echo htmlspecialchars(($leave['first_name'] ?? '') . ' ' . ($leave['last_name'] ?? '')); ?>')" class="text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-200">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Card View -->
    <div class="lg:hidden">
        <?php if (empty($all_results)): ?>
        <div class="p-6 text-center text-gray-500">No leave requests found.</div>
        <?php else: ?>
            <?php foreach ($all_results as $leave): ?>
            <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center text-white font-semibold">
                                <?php echo substr($leave['first_name'] ?? '', 0, 1) . substr($leave['last_name'] ?? '', 0, 1); ?>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(($leave['first_name'] ?? '') . ' ' . ($leave['last_name'] ?? '')); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($leave['department'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $leave['source_table'] === 'employee' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo $leave['source_table'] === 'employee' ? 'Employee' : 'Employee'; ?>
                        </span>
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved_by_head' => 'bg-blue-100 text-blue-800',
                            'approved_by_hr' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'cancelled' => 'bg-gray-100 text-gray-800'
                        ];
                        $status_text = [
                            'pending' => 'Pending',
                            'approved_by_head' => 'Approved by Head',
                            'approved_by_hr' => 'Approved by HR',
                            'rejected' => 'Rejected',
                            'cancelled' => 'Cancelled'
                        ];
                        $status = $leave['status'] ?? 'pending';
                        ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $status_colors[$status]; ?>">
                            <?php echo $status_text[$status]; ?>
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-3 text-sm">
                    <div>
                        <span class="text-gray-500">Leave Type:</span>
                        <div class="font-medium"><?php echo htmlspecialchars($leave['leave_type_name'] ?? ''); ?></div>
                    </div>
                    <div>
                        <span class="text-gray-500">Days:</span>
                        <div class="font-medium"><?php echo htmlspecialchars($leave['total_days'] ?? ''); ?> days</div>
                    </div>
                    <div class="col-span-2">
                        <span class="text-gray-500">Dates:</span>
                        <div class="font-medium"><?php echo date('M j, Y', strtotime($leave['start_date'])); ?> to <?php echo date('M j, Y', strtotime($leave['end_date'])); ?></div>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="viewLeaveDetails(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-green-500 hover:text-green-600 text-sm">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <?php 
                    // Show approve/reject buttons for:
                    // - Employee leave requests that are pending
                    // - Employee leave requests that are approved by head (ready for HR approval)
                    $can_approve = false;
                    if ($leave['source_table'] === 'employee' && $leave['status'] === 'pending') {
                        $can_approve = true;
                    } elseif ($leave['source_table'] === 'employee' && $leave['status'] === 'approved_by_head') {
                        $can_approve = true;
                    }
                    
                    if ($can_approve): 
                    ?>
                    <button onclick="approveLeave(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-green-600 hover:text-green-800 text-sm">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button onclick="rejectLeave(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>')" class="text-red-600 hover:text-red-800 text-sm">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <?php endif; ?>
                    
                    <!-- Secure Delete Button (Mobile) -->
                    <button onclick="openSecureDeleteModal(<?php echo $leave['id']; ?>, '<?php echo $leave['source_table']; ?>', '<?php echo htmlspecialchars(($leave['first_name'] ?? '') . ' ' . ($leave['last_name'] ?? '')); ?>')" class="text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded border border-red-200 text-sm">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create Leave Request Modal -->
<div id="createLeaveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto my-8 border w-11/12 max-w-6xl shadow-2xl rounded-xl bg-white modal-height-mobile" style="height: calc(100vh - 4rem); max-height: 900px;">
        <div class="h-full flex flex-col">
            <!-- Fixed Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-white rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-900">Add Employee Leave</h3>
                <button type="button" onclick="closeCreateLeaveModal()" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-hidden">
                <div class="h-full grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                <!-- Left Column: Leave Request Form -->
                <div class="flex flex-col h-full">
                    <!-- Fixed Form Header -->
                    <div class="flex-shrink-0 mb-4">
                        <h4 class="text-md font-semibold text-gray-800 border-b pb-2">Add Leave Form</h4>
                    </div>
                    
                    <!-- Scrollable Form Content -->
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar form-scrollable-content" style="max-height: calc(100vh - 300px);">
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <form id="createLeaveForm" class="space-y-6 pb-4">
                <!-- Employee/Employee Selection Buttons (shown when no URL filter) -->
                <div id="employeeTypeSelection" class="mb-8 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-4">Select Employee Type</label>
                    <div class="flex space-x-4">
                        <button type="button" id="selectEmployeeBtn" onclick="selectEmployeeType('employee')" class="flex-1 px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors">
                            <i class="fas fa-users mr-2"></i>Employees
                        </button>
                    </div>
                </div>
                
                <div class="mb-8">
                    <label for="employee_id" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-user mr-2 text-green-600"></i>
                        <span id="employeeLabel">Employee</span>
                    </label>
                    <select name="employee_id" id="employee_id" required class="w-full border-2 border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm">
                        <option value="">Select Employee</option>
                        <!-- Will be populated via AJAX -->
                    </select>
                </div>
                
                <div class="mb-8">
                    <label for="leave_type_id" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-calendar-alt mr-2 text-green-600"></i>
                        Leave Type
                    </label>
                    <select name="leave_type_id" id="leave_type_id" required class="w-full border-2 border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm">
                        <option value="">Select Leave Type</option>
                        <!-- Will be populated via AJAX -->
                    </select>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-day mr-2 text-green-600"></i>
                            Start Date
                        </label>
                        <input type="date" name="start_date" id="start_date" required class="w-full border-2 border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm">
                </div>
                
                    <div>
                        <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-check mr-2 text-green-600"></i>
                            End Date
                        </label>
                        <input type="date" name="end_date" id="end_date" required class="w-full border-2 border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm">
                    </div>
                </div>
                
                <div class="mb-8">
                    <label for="reason" class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-comment-alt mr-2 text-green-600"></i>
                        Reason for Leave
                    </label>
                    <textarea name="reason" id="reason" rows="5" required class="w-full border-2 border-gray-300 rounded-xl px-5 py-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all shadow-sm resize-none" placeholder="Please provide a detailed reason for the leave..."></textarea>
                </div>
                
                            </form>
                        </div> <!-- End form container -->
                    </div> <!-- End scrollable form content -->
                </div> <!-- End form column -->
                
                <!-- Right Column: Employee Details & Leave Allowance (Scrollable) -->
                <div class="flex flex-col h-full">
                    <!-- Fixed Header for Employee Details -->
                    <div class="flex-shrink-0 mb-6">
                        <h4 class="text-lg font-bold text-gray-900 border-b-2 border-green-500 pb-3">
                            <i class="fas fa-user-circle text-green-600 mr-2"></i>Employee Details
                        </h4>
                    </div>
                    
                    <!-- Scrollable Employee Details Content -->
                    <div class="flex-1 overflow-y-auto pr-3 space-y-6 custom-scrollbar scrollable-content-mobile employee-details-scroll scrollable-content" style="max-height: calc(100vh - 350px);"">
                    
                    <!-- Employee Selection Message -->
                    <div id="employeeSelectionMessage" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-300 rounded-xl p-6 text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-plus text-blue-500 text-3xl"></i>
                        </div>
                        <h5 class="font-semibold text-blue-900 mb-2">Select an Employee</h5>
                        <p class="text-blue-700 text-sm">Choose an employee or employee member above to view their complete leave allowance details, calculation breakdown, and history.</p>
                    </div>
                    
                    <!-- Employee Info Card -->
                    <div id="employeeInfoCard" class="hidden">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-300 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center shadow-lg">
                                        <i class="fas fa-user text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h5 id="employeeName" class="text-xl font-bold text-gray-900"></h5>
                                        <p id="employeeDepartment" class="text-gray-600 font-medium"></p>
                                        <div id="employeeStatus" class="mt-1"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Employee ID</div>
                                    <div id="employeeIdDisplay" class="font-mono text-sm font-semibold text-gray-700"></div>
                                </div>
                            </div>
                            
                            <!-- Quick Info Grid -->
                            <div id="quickInfoGrid" class="grid grid-cols-2 gap-3 mt-4">
                                <div class="bg-white rounded-lg p-3 border shadow-sm">
                                    <div class="text-xs text-gray-500">Position</div>
                                    <div id="employeePosition" class="font-semibold text-gray-900 text-sm">-</div>
                                </div>
                                <div class="bg-white rounded-lg p-3 border shadow-sm">
                                    <div class="text-xs text-gray-500">Employment Type</div>
                                    <div id="employmentType" class="font-semibold text-gray-900 text-sm">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Allowance Details -->
                    <div id="leaveAllowanceCard" class="hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-green-500 to-blue-600 rounded-t-xl p-4 text-white">
                            <h5 class="text-lg font-bold flex items-center">
                                <i class="fas fa-calculator mr-3 text-xl"></i>
                                Leave Allowance Details
                            </h5>
                            <p class="text-green-100 text-sm mt-1">Year <span id="allowanceYear" class="font-semibold"></span> Calculation Breakdown</p>
                        </div>
                        
                        <!-- Main Allowance Display -->
                        <div class="bg-white border-2 border-green-200 rounded-b-xl p-6">
                            <!-- Key Metrics -->
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <!-- Total Available (Most Important) -->
                                <div class="col-span-2 bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-4 border-2 border-green-300 text-center">
                                    <div class="text-green-700 text-sm font-semibold mb-1">TOTAL LEAVE AVAILABLE</div>
                                    <div id="totalDays" class="text-4xl font-bold text-green-600 mb-1">-</div>
                                    <div class="text-green-600 text-xs">days for this year</div>
                                </div>
                                
                                <!-- Remaining Days (Critical) -->
                                <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl p-4 border-2 border-orange-300 text-center">
                                    <div class="text-orange-700 text-sm font-semibold mb-1">REMAINING</div>
                                    <div id="remainingDays" class="text-3xl font-bold text-orange-600 mb-1">-</div>
                                    <div class="text-orange-600 text-xs">days left</div>
                                </div>
                                
                                <!-- Used Days -->
                                <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-xl p-4 border-2 border-red-300 text-center">
                                    <div class="text-red-700 text-sm font-semibold mb-1">USED</div>
                                    <div id="usedDaysDisplay" class="text-3xl font-bold text-red-600 mb-1">-</div>
                                    <div class="text-red-600 text-xs">days taken</div>
                                </div>
                            </div>
                            
                            <!-- Calculation Breakdown -->
                            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                                <h6 class="font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-chart-pie text-gray-600 mr-2"></i>
                                    Calculation Breakdown
                                </h6>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-3 border shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs text-gray-500">Base Allowance</div>
                                                <div id="baseDays" class="text-xl font-bold text-gray-800">-</div>
                                            </div>
                                            <i class="fas fa-calendar text-gray-400 text-lg"></i>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">Standard annual leave</div>
                                    </div>
                                    
                                    <div class="bg-white rounded-lg p-3 border shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="text-xs text-gray-500">Accumulated</div>
                                                <div id="accumulatedDays" class="text-xl font-bold text-blue-600">-</div>
                                            </div>
                                            <i class="fas fa-plus-circle text-blue-400 text-lg"></i>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">From previous years</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Employment Status & Calculation Rules -->
                            <div id="calculationDetails" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                                <h6 class="font-semibold text-blue-800 mb-3 flex items-center">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    Employment Status & Rules
                                </h6>
                                <div id="calculationInfo" class="space-y-2">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                                
                                <!-- Regularization Info -->
                                <div id="regularizationInfo" class="mt-3 pt-3 border-t border-blue-200">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave History -->
                    <div id="leaveHistoryCard" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 history-section prevent-overlap">
                        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-history text-blue-600 mr-2"></i>
                            All Approved Leave History (Since Date of Hire)
                        </h5>
                        
                        <!-- Statistics -->
                        <div id="leaveStats" class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-white rounded p-2 border text-center">
                                <div id="totalRequests" class="text-lg font-semibold text-gray-900">0</div>
                                <div class="text-xs text-gray-600">Total Requests</div>
                            </div>
                            <div class="bg-white rounded p-2 border text-center">
                                <div id="approvedRequests" class="text-lg font-semibold text-green-600">0</div>
                                <div class="text-xs text-gray-600">Approved</div>
                            </div>
                            <div class="bg-white rounded p-2 border text-center">
                                <div id="usedDays" class="text-lg font-semibold text-red-600">0</div>
                                <div class="text-xs text-gray-600">Days Used</div>
                            </div>
                        </div>
                        
                        <!-- History List -->
                        <div id="historyList" class="history-list-container overflow-y-auto custom-scrollbar bg-white rounded-lg border p-3 h-80 min-h-80">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="loadingDetails" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 text-center prevent-overlap">
                        <i class="fas fa-spinner fa-spin text-gray-500 text-xl mb-2"></i>
                        <div class="text-gray-600">Loading employee details...</div>
                    </div>
                    
                    <!-- Bottom padding to ensure last content is visible and prevent footer overlap -->
                    <div class="h-16 prevent-overlap"></div>
                    
                    </div> <!-- End scrollable content -->
                </div> <!-- End Employee Details column -->
                </div> <!-- End grid -->
            </div> <!-- End scrollable content area -->
            
            <!-- Full Width Modal Footer -->
            <div class="flex-shrink-0 bg-gray-50 border-t border-gray-200 px-8 py-6 rounded-b-xl">
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeCreateLeaveModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-8 py-3 rounded-lg font-medium transition-colors shadow-md">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" form="createLeaveForm" class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg font-medium transition-colors shadow-md">
                        <i class="fas fa-check mr-2"></i>Add Leave
                    </button>
                </div>
        </div>
        </div> <!-- End modal content -->
    </div> <!-- End modal container -->
</div> <!-- End modal overlay -->

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
    <div class="relative mx-auto p-8 border w-96 shadow-2xl rounded-2xl bg-white transform transition-all duration-300 scale-95" id="errorModalContent">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            
            <!-- Error Title -->
            <h3 class="text-xl font-bold text-gray-900 mb-4" id="errorTitle">Error</h3>
            
            <!-- Error Message -->
            <p class="text-gray-600 mb-8" id="errorMessage">An error occurred.</p>
            
            <!-- Error Button -->
            <button onclick="closeErrorModal()" class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-medium transition-colors shadow-md w-full">
                <i class="fas fa-times mr-2"></i>Close
            </button>
        </div>
    </div>
</div>

<!-- Secure Delete Modal with Password and CAPTCHA -->
<div id="secureDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-[9999] flex items-center justify-center p-4" onclick="closeSecureDeleteModal()">
    <div class="relative w-full max-w-md mx-auto bg-white rounded-xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="secureDeleteModalContent" onclick="event.stopPropagation()">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mr-3">
                        <i class="fas fa-shield-alt text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Secure Delete</h3>
                        <p class="text-sm text-gray-500">Password and CAPTCHA verification required</p>
                    </div>
                </div>
                <button onclick="closeSecureDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Warning -->
            <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 mb-6 relative z-10">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2 text-lg"></i>
                    <span class="text-sm font-bold text-red-800">Warning: This action cannot be undone!</span>
                </div>
                <p class="text-sm text-red-700">You are about to permanently delete the leave request for <strong id="deleteTargetName" class="text-red-900"></strong>.</p>
            </div>
            
            <!-- Security Form -->
            <form id="secureDeleteForm" class="space-y-4">
                <input type="hidden" id="deleteLeaveId" name="leave_id">
                <input type="hidden" id="deleteSourceTable" name="source_table">
                
                <!-- Step 1: Password Verification -->
                <div class="bg-gray-50 rounded-lg p-4 border relative z-10">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-key text-gray-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-900">Step 1: Verify Password</span>
                    </div>
                    <input type="password" id="deletePassword" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Enter your current password">
                </div>
                
                <!-- Step 2: CAPTCHA -->
                <div class="bg-gray-50 rounded-lg p-4 border relative z-10">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-robot text-gray-600 mr-2"></i>
                        <span class="text-sm font-medium text-gray-900">Step 2: Verify CAPTCHA</span>
                    </div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div id="captchaDisplay" class="bg-white border-2 border-gray-300 rounded px-4 py-2 font-mono text-lg font-bold text-gray-800 tracking-wider select-none relative z-10" style="background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%), linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%); background-size: 4px 4px; background-position: 0 0, 2px 2px;">
                            <!-- CAPTCHA will be generated here -->
                        </div>
                        <button type="button" onclick="refreshCaptcha()" class="text-gray-500 hover:text-gray-700 p-2 relative z-10">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <input type="text" id="captchaInput" name="captcha" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Enter the CAPTCHA code above">
                </div>
                
                
                <!-- Final Confirmation -->
                <div class="bg-red-50 rounded-lg p-4 border border-red-200 relative z-10">
                    <div class="flex items-center">
                        <input type="checkbox" id="finalConfirmation" name="final_confirmation" required
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="finalConfirmation" class="ml-2 text-sm font-medium text-red-800">
                            I understand that this action is permanent and cannot be undone
                        </label>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeSecureDeleteModal()" 
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" id="deleteSubmitBtn"
                            class="flex-1 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-trash-alt mr-1"></i>Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leave Details Modal -->
<div id="leaveDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Leave Request Details</h3>
                <button onclick="closeLeaveDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="leaveDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Global variable to track current selection
let currentEmployeeFilter = 'all';

function openCreateLeaveModal() {
    document.getElementById('createLeaveModal').classList.remove('hidden');
    
    // Check URL parameters for tab filter
    const urlParams = new URLSearchParams(window.location.search);
    const tabFilter = urlParams.get('tab');
    
    if (tabFilter === 'employee') {
        // URL has filter - hide buttons and load filtered employees
        document.getElementById('employeeTypeSelection').classList.add('hidden');
        currentEmployeeFilter = tabFilter;
        updateEmployeeLabel(tabFilter);
        loadEmployees(tabFilter);
    } else {
        // No URL filter - show selection buttons and auto-select employees
        document.getElementById('employeeTypeSelection').classList.remove('hidden');
        resetEmployeeTypeButtons();
        // Auto-select employees since it's the only option
        selectEmployeeType('employee');
    }
    
    loadLeaveTypes();
}

function closeCreateLeaveModal() {
    document.getElementById('createLeaveModal').classList.add('hidden');
    document.getElementById('createLeaveForm').reset();
    resetEmployeeTypeButtons();
    hideEmployeeDetails();
    currentEmployeeFilter = 'all';
}

function selectEmployeeType(type) {
    currentEmployeeFilter = type;
    updateEmployeeTypeButtons(type);
    updateEmployeeLabel(type);
    loadEmployees(type);
}

function updateEmployeeTypeButtons(selectedType) {
    const employeeBtn = document.getElementById('selectEmployeeBtn');
    
    // Reset button
    employeeBtn.className = 'flex-1 px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors';
    
    // Highlight selected button (only employee now)
    if (selectedType === 'employee') {
        employeeBtn.className = 'flex-1 px-6 py-3 border border-green-500 rounded-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors';
    }
}

function resetEmployeeTypeButtons() {
    const employeeBtn = document.getElementById('selectEmployeeBtn');
    
    employeeBtn.className = 'flex-1 px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-colors';
}

function updateEmployeeLabel(type) {
    const label = document.getElementById('employeeLabel');
    label.textContent = 'Employee';
}

function clearEmployeeDropdown() {
    const select = document.getElementById('employee_id');
    select.innerHTML = '<option value="">Select Employee</option>';
}

function loadEmployees(filter = 'all') {
    const url = filter === 'all' ? 'get-employees.php' : `get-employees.php?filter=${filter}`;
    
    console.log('Loading employees from:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('Employees data received:', data);
            const select = document.getElementById('employee_id');
            select.innerHTML = '<option value="">Select Employee</option>';
            
            data.forEach(employee => {
                const option = document.createElement('option');
                option.value = employee.id;
                option.dataset.sourceTable = employee.source_table;
                option.dataset.firstName = employee.first_name;
                option.dataset.lastName = employee.last_name;
                option.dataset.department = employee.department || '';
                option.dataset.employeeId = employee.employee_id;
                option.dataset.employeeType = employee.employee_type || '';
                option.dataset.position = employee.position || '';
                option.dataset.employmentDisplay = employee.employment_display || '';
                
                // Format display text based on employee type
                let displayText = `${employee.first_name} ${employee.last_name}`;
                if (employee.employee_id && employee.employee_id !== employee.id) {
                    displayText += ` (${employee.employee_id})`;
                }
                if (employee.department) {
                    displayText += ` - ${employee.department}`;
                }
                
                option.textContent = displayText;
                select.appendChild(option);
            });
            
            // Add change event listener for employee selection
            select.addEventListener('change', handleEmployeeSelection);
        })
        .catch(error => console.error('Error loading employees:', error));
}

function loadLeaveTypes() {
    console.log('Loading leave types...');
    
    fetch('get-leave-types.php')
        .then(response => {
            console.log('Leave types response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Leave types data received:', data);
            const select = document.getElementById('leave_type_id');
            select.innerHTML = '<option value="">Select Leave Type</option>';
            
            if (data && Array.isArray(data)) {
                data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    select.appendChild(option);
                });
                console.log(`Loaded ${data.length} leave types`);
            } else {
                console.log('No leave types found or invalid data format');
                select.innerHTML = '<option value="">No leave types available</option>';
            }
        })
        .catch(error => {
            console.error('Error loading leave types:', error);
            const select = document.getElementById('leave_type_id');
            select.innerHTML = '<option value="">Error loading leave types</option>';
        });
}

// Handle employee selection change
function handleEmployeeSelection(event) {
    const selectedOption = event.target.selectedOptions[0];
    
    if (!selectedOption || !selectedOption.value) {
        hideEmployeeDetails();
        return;
    }
    
    const employeeId = selectedOption.value;
    const employeeType = selectedOption.dataset.sourceTable;
    const firstName = selectedOption.dataset.firstName;
    const lastName = selectedOption.dataset.lastName;
    const department = selectedOption.dataset.department;
    const employeeIdText = selectedOption.dataset.employeeId;
    const position = selectedOption.dataset.position;
    const empType = selectedOption.dataset.employeeType;
    const employmentDisplay = selectedOption.dataset.employmentDisplay;
    
    // Show employee basic info immediately with all available data
    showEmployeeInfo(firstName, lastName, department, employeeIdText, employeeType, position, employmentDisplay);
    
    // Load detailed information
    loadEmployeeLeaveDetails(employeeId, employeeType);
}

// Show employee basic information
function showEmployeeInfo(firstName, lastName, department, employeeId, employeeType, position = '', empType = '') {
    // Hide selection message and show info card
    document.getElementById('employeeSelectionMessage').classList.add('hidden');
    document.getElementById('employeeInfoCard').classList.remove('hidden');
    
    // Update employee info
    document.getElementById('employeeName').textContent = `${firstName} ${lastName}`;
    document.getElementById('employeeDepartment').textContent = department || 'No Department Assigned';
    document.getElementById('employeeIdDisplay').textContent = employeeId || 'N/A';
    
    // Update position and employment type immediately from dropdown data
    document.getElementById('employeePosition').textContent = position || 'Not Specified';
    document.getElementById('employmentType').textContent = empType || 'Not Specified';
    
    // Update status badge with enhanced styling
    const statusElement = document.getElementById('employeeStatus');
    if (employeeType === 'employee') {
        statusElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md"><i class="fas fa-users mr-2"></i>Employee</span>';
    } else {
        statusElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md"><i class="fas fa-chalkboard-teacher mr-2"></i>Employee</span>';
    }
}

// Hide employee details
function hideEmployeeDetails() {
    document.getElementById('employeeSelectionMessage').classList.remove('hidden');
    document.getElementById('employeeInfoCard').classList.add('hidden');
    document.getElementById('leaveAllowanceCard').classList.add('hidden');
    document.getElementById('leaveHistoryCard').classList.add('hidden');
    document.getElementById('loadingDetails').classList.add('hidden');
}

// Load employee leave details
function loadEmployeeLeaveDetails(employeeId, employeeType) {
    // Show loading state
    document.getElementById('loadingDetails').classList.remove('hidden');
    document.getElementById('leaveAllowanceCard').classList.add('hidden');
    document.getElementById('leaveHistoryCard').classList.add('hidden');
    
    const currentYear = new Date().getFullYear();
    
    // Load both allowance details and history in parallel
    Promise.all([
        fetch(`get-leave-allowance-details.php?employee_id=${employeeId}&employee_type=${employeeType}&year=${currentYear}`),
        fetch(`get-leave-history.php?employee_id=${employeeId}&employee_type=${employeeType}&all_time=true&limit=50`)
    ])
    .then(responses => {
        // Check if responses are ok before parsing JSON
        const allowanceResponse = responses[0];
        const historyResponse = responses[1];
        
        const allowancePromise = allowanceResponse.ok ? 
            allowanceResponse.json() : 
            Promise.resolve({success: false, error: `HTTP ${allowanceResponse.status}`});
            
        const historyPromise = historyResponse.ok ? 
            historyResponse.json() : 
            Promise.resolve({success: false, error: `HTTP ${historyResponse.status}`});
        
        return Promise.all([allowancePromise, historyPromise]);
    })
    .then(([allowanceData, historyData]) => {
        // Hide loading state
        document.getElementById('loadingDetails').classList.add('hidden');
        
        console.log('Allowance data:', allowanceData);
        console.log('History data:', historyData);
        
        if (allowanceData.success) {
            displayLeaveAllowanceDetails(allowanceData);
        } else {
            console.error('Allowance data error:', allowanceData.error);
        }
        
        if (historyData.success) {
            displayLeaveHistory(historyData);
        } else {
            console.error('History data error:', historyData.error);
        }
    })
    .catch(error => {
        console.error('Error loading employee details:', error);
        document.getElementById('loadingDetails').classList.add('hidden');
        
        // Show error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'bg-red-50 border border-red-200 rounded-lg p-4';
        errorDiv.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i><span class="text-red-800 text-sm">Error loading employee details</span></div>';
        document.getElementById('employeeInfoCard').after(errorDiv);
        
        setTimeout(() => errorDiv.remove(), 5000);
    });
}

// Display leave allowance details
function displayLeaveAllowanceDetails(data) {
    const allowanceCard = document.getElementById('leaveAllowanceCard');
    const calculation = data.allowance_calculation;
    const employeeInfo = data.employee_info;
    
    if (calculation) {
        // Update year
        document.getElementById('allowanceYear').textContent = data.year;
        
        // Update main calculation values (using same logic as leave-allowance-management.php)
        document.getElementById('baseDays').textContent = calculation.base_days || 0;
        document.getElementById('totalDays').textContent = calculation.total_days || 0;
        document.getElementById('remainingDays').textContent = calculation.remaining_days || 0;
        document.getElementById('usedDaysDisplay').textContent = calculation.used_days || 0;
        
        // Handle accumulated days display for employee (exact same logic as leave-allowance-management.php)
        const accumulatedElement = document.getElementById('accumulatedDays');
        if (calculation.source_table === 'employee') {
            // For employee, only show accumulated days if they are regular
            if (calculation.is_regular) {
                accumulatedElement.textContent = calculation.accumulated_days || 0;
            } else {
                accumulatedElement.innerHTML = '<span class="text-gray-400 italic text-lg">N/A</span>';
            }
        } else {
            // For employees, show accumulated days normally
            accumulatedElement.textContent = calculation.accumulated_days || 0;
        }
        
        // Update employee additional info (use calculation data which has the employee info)
        if (calculation) {
            // Position from the calculation data (which includes employee info)
            document.getElementById('employeePosition').textContent = calculation.position || 'Not Specified';
            
            // Employment type from calculation data
            let employmentTypeText = 'Not Specified';
            if (calculation.employment_type && calculation.employment_status) {
                employmentTypeText = `${calculation.employment_status} ${calculation.employment_type}`;
            } else if (calculation.employment_type) {
                employmentTypeText = calculation.employment_type;
            } else if (calculation.employment_status) {
                employmentTypeText = calculation.employment_status;
            } else if (employeeInfo && employeeInfo.position) {
                // Fallback to employeeInfo if available
                employmentTypeText = employeeInfo.employment_type || employeeInfo.employment_status || 'Not Specified';
            }
            document.getElementById('employmentType').textContent = employmentTypeText;
        } else if (employeeInfo) {
            // Fallback to employeeInfo if calculation is not available
            document.getElementById('employeePosition').textContent = employeeInfo.position || 'Not Specified';
            
            let employmentTypeText = 'Not Specified';
            if (employeeInfo.employment_type && employeeInfo.employment_status) {
                employmentTypeText = `${employeeInfo.employment_status} ${employeeInfo.employment_type}`;
            } else if (employeeInfo.employment_type) {
                employmentTypeText = employeeInfo.employment_type;
            } else if (employeeInfo.employment_status) {
                employmentTypeText = employeeInfo.employment_status;
            }
            document.getElementById('employmentType').textContent = employmentTypeText;
        }
        
        // Enhanced calculation info with better formatting (using same logic as leave-allowance-management.php)
        let calculationInfo = '';
        let regularizationInfo = '';
        
        // Employment Status (using same field names as leave-allowance-management.php)
        if (calculation.is_regular) {
            calculationInfo += `
                <div class="flex items-center justify-between bg-green-100 rounded-lg p-3 border border-green-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span class="font-semibold text-green-800">Regular ${calculation.source_table === 'employee' ? 'Employee' : 'Employee'}</span>
                    </div>
                    <span class="text-xs bg-green-200 text-green-700 px-2 py-1 rounded-full">Active Status</span>
                </div>
            `;
            
            // Accumulation Status
            if (calculation.can_accumulate) {
                calculationInfo += `
                    <div class="flex items-center justify-between bg-blue-100 rounded-lg p-3 border border-blue-200">
                        <div class="flex items-center">
                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                            <span class="font-semibold text-blue-800">Can Accumulate Leave</span>
                        </div>
                        <span class="text-xs bg-blue-200 text-blue-700 px-2 py-1 rounded-full">Eligible</span>
                    </div>
                `;
                
                if (calculation.accumulation_start_year) {
                    regularizationInfo += `
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-blue-700">Accumulation Started:</span>
                            <span class="text-sm font-semibold text-blue-800">${calculation.accumulation_start_year}</span>
                        </div>
                    `;
                }
            } else {
                calculationInfo += `
                    <div class="flex items-center justify-between bg-yellow-100 rounded-lg p-3 border border-yellow-200">
                        <div class="flex items-center">
                            <i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>
                            <span class="font-semibold text-yellow-800">Cannot Accumulate Yet</span>
                        </div>
                        <span class="text-xs bg-yellow-200 text-yellow-700 px-2 py-1 rounded-full">Waiting Period</span>
                    </div>
                `;
            }
        } else {
            calculationInfo += `
                <div class="flex items-center justify-between bg-yellow-100 rounded-lg p-3 border border-yellow-200">
                    <div class="flex items-center">
                        <i class="fas fa-clock text-yellow-600 mr-2"></i>
                        <span class="font-semibold text-yellow-800">Probationary ${calculation.source_table === 'employee' ? 'Employee' : 'Employee'}</span>
                    </div>
                    <span class="text-xs bg-yellow-200 text-yellow-700 px-2 py-1 rounded-full">Temporary Status</span>
                </div>
            `;
            
            // Dynamic regularization period based on employee type
            const regularizationPeriod = calculation.source_table === 'employee' ? '3 years' : '6 months';
            
            calculationInfo += `
                <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-info-circle text-orange-600 mr-2"></i>
                        <span class="text-sm font-semibold text-orange-800">Probationary Rules</span>
                    </div>
                    <p class="text-xs text-orange-700">• Leave resets every year (cannot accumulate)</p>
                    <p class="text-xs text-orange-700">• Eligible for regularization after ${regularizationPeriod}</p>
                </div>
            `;
        }
        
        // Regularization Date
        if (calculation.regularization_date) {
            regularizationInfo += `
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-700">Regularization Date:</span>
                    <span class="text-sm font-semibold text-blue-800">${new Date(calculation.regularization_date).toLocaleDateString()}</span>
                </div>
            `;
        }
        
        // Employee-specific rules (matching leave-allowance-management.php logic)
        if (calculation.source_table === 'employee') {
            if (calculation.is_regular && calculation.can_accumulate) {
                regularizationInfo += `
                    <div class="bg-purple-50 rounded-lg p-3 border border-purple-200 mt-2">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-graduation-cap text-purple-600 mr-2"></i>
                            <span class="text-sm font-semibold text-purple-800">Employee Accumulation Rules</span>
                        </div>
                        <p class="text-xs text-purple-700">• Can accumulate leave after 3 years of regularization</p>
                        <p class="text-xs text-purple-700">• Accumulated leave available for use</p>
                    </div>
                `;
            } else if (calculation.is_regular && !calculation.can_accumulate) {
                regularizationInfo += `
                    <div class="bg-purple-50 rounded-lg p-3 border border-purple-200 mt-2">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-graduation-cap text-purple-600 mr-2"></i>
                            <span class="text-sm font-semibold text-purple-800">Employee Accumulation Rules</span>
                        </div>
                        <p class="text-xs text-purple-700">• Must wait 3 years after regularization to accumulate leave</p>
                        <p class="text-xs text-purple-700">• Currently in waiting period</p>
                    </div>
                `;
            } else {
                // Non-regular employee
                regularizationInfo += `
                    <div class="bg-purple-50 rounded-lg p-3 border border-purple-200 mt-2">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-graduation-cap text-purple-600 mr-2"></i>
                            <span class="text-sm font-semibold text-purple-800">Employee Rules</span>
                        </div>
                        <p class="text-xs text-purple-700">• Non-regular employee cannot accumulate leave</p>
                        <p class="text-xs text-purple-700">• Leave resets every year</p>
                    </div>
                `;
            }
        }
        
        // Update the DOM
        document.getElementById('calculationInfo').innerHTML = calculationInfo;
        document.getElementById('regularizationInfo').innerHTML = regularizationInfo;
        
        allowanceCard.classList.remove('hidden');
    }
}

// Display leave history
function displayLeaveHistory(data) {
    const historyCard = document.getElementById('leaveHistoryCard');
    const history = data.leave_history || [];
    
    // Calculate statistics from the history data
    const stats = {
        total_requests: history.length,
        approved_requests: history.filter(req => req.status === 'approved_by_hr').length,
        total_approved_days: history
            .filter(req => req.status === 'approved_by_hr')
            .reduce((sum, req) => sum + (parseInt(req.total_days) || 0), 0)
    };
    
    // Update statistics
    document.getElementById('totalRequests').textContent = stats.total_requests || 0;
    document.getElementById('approvedRequests').textContent = stats.approved_requests || 0;
    document.getElementById('usedDays').textContent = Math.round(stats.total_approved_days || 0);
    
    // Update history list
    const historyList = document.getElementById('historyList');
    historyList.innerHTML = '';
    
    if (history.length === 0) {
        historyList.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-inbox mr-2 text-lg"></i><div class="mt-2">No leave requests this year</div></div>';
    } else {
        history.forEach((request, index) => {
            const statusColor = getStatusColor(request.status);
            const statusIcon = getStatusIcon(request.status);
            
            const historyItem = document.createElement('div');
            historyItem.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3 hover:bg-white transition-colors';
            historyItem.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div class="font-semibold text-sm text-gray-900">${request.leave_type_name}</div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${statusColor}">
                        <i class="${statusIcon} mr-1"></i>${request.status.replace('_', ' ').toUpperCase()}
                    </span>
                </div>
                <div class="text-sm text-gray-700 mb-2">
                    <i class="fas fa-calendar mr-1 text-gray-500"></i>
                    ${new Date(request.start_date).toLocaleDateString()} - ${new Date(request.end_date).toLocaleDateString()}
                </div>
                <div class="text-sm text-blue-600 font-medium mb-2">
                    <i class="fas fa-clock mr-1"></i>
                    ${request.total_days} day${request.total_days > 1 ? 's' : ''}
                </div>
                ${request.reason ? `<div class="text-xs text-gray-600 bg-gray-100 rounded p-2 italic">"${request.reason.substring(0, 80)}${request.reason.length > 80 ? '...' : ''}"</div>` : ''}
            `;
            historyList.appendChild(historyItem);
        });
        
        // Add extra padding at the end
        const paddingDiv = document.createElement('div');
        paddingDiv.className = 'h-4';
        historyList.appendChild(paddingDiv);
    }
    
    historyCard.classList.remove('hidden');
}

// Helper functions for status display
function getStatusColor(status) {
    switch (status) {
        case 'approved_by_hr':
        case 'approved_by_head':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-blue-100 text-blue-800';
    }
}

function getStatusIcon(status) {
    switch (status) {
        case 'approved_by_hr':
        case 'approved_by_head':
            return 'fas fa-check';
        case 'pending':
            return 'fas fa-clock';
        case 'rejected':
            return 'fas fa-times';
        case 'cancelled':
            return 'fas fa-ban';
        default:
            return 'fas fa-info';
    }
}

document.getElementById('createLeaveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('=== LEAVE ADDITION PROCESS START ===');
    console.log('Form submission initiated at:', new Date().toISOString());
    
    const formData = new FormData(this);
    
    // Log form data for debugging
    console.log('Form Data Contents:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    // Get selected employee info for additional logging
    const employeeSelect = document.getElementById('employee_id');
    const selectedOption = employeeSelect.selectedOptions[0];
    if (selectedOption) {
        console.log('Selected Employee Details:');
        console.log('  Employee ID:', selectedOption.value);
        console.log('  Source Table:', selectedOption.dataset.sourceTable);
        console.log('  Name:', selectedOption.dataset.firstName, selectedOption.dataset.lastName);
        console.log('  Department:', selectedOption.dataset.department);
    }
    
    console.log('Sending request to create-leave-request.php...');
    
    fetch('create-leave-request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }
        
        return response.text(); // Get as text first to see raw response
    })
    .then(responseText => {
        console.log('Raw response text:', responseText);
        
        try {
            const data = JSON.parse(responseText);
            console.log('Parsed JSON response:', data);
            
        if (data.success) {
                console.log('✅ SUCCESS: Leave added successfully');
            closeCreateLeaveModal();
                showSuccessModal('Leave Added Successfully!', data.message);
        } else {
                console.error('❌ SERVER ERROR:', data.message);
                showErrorModal('Leave Addition Failed', data.message);
            }
        } catch (parseError) {
            console.error('❌ JSON PARSE ERROR:', parseError);
            console.error('Response was not valid JSON:', responseText);
            showErrorModal('Invalid Server Response', 'The server returned an invalid response. Please check the console for details.');
        }
    })
    .catch(error => {
        console.error('=== LEAVE ADDITION ERROR ===');
        console.error('Error Type:', error.name);
        console.error('Error Message:', error.message);
        console.error('Error Stack:', error.stack);
        console.error('Timestamp:', new Date().toISOString());
        console.error('=== END ERROR LOG ===');
        
        showErrorModal('Network Error', 'A network error occurred while adding the leave. Please check your connection and try again.');
    });
});

function viewLeaveDetails(leaveId, sourceTable) {
    fetch(`get-leave-details.php?leave_id=${leaveId}&table=${sourceTable}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('leaveDetailsContent').innerHTML = html;
            document.getElementById('leaveDetailsModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error loading leave details:', error);
            showErrorModal('Loading Error', 'Error loading leave details. Please try again.');
        });
}

function closeLeaveDetailsModal() {
    document.getElementById('leaveDetailsModal').classList.add('hidden');
}

// Beautiful Modal Functions
function showSuccessModal(title, message) {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    const titleEl = document.getElementById('successTitle');
    const messageEl = document.getElementById('successMessage');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.classList.remove('hidden');
    content.classList.add('modal-enter');
    
    // Add bounce animation to icon
    const icon = modal.querySelector('.fa-check');
    icon.parentElement.classList.add('success-icon-bounce');
    
    // Remove animation classes after animation completes
    setTimeout(() => {
        content.classList.remove('modal-enter');
        icon.parentElement.classList.remove('success-icon-bounce');
    }, 600);
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    content.classList.add('modal-exit');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        content.classList.remove('modal-exit');
        
        // Auto-reload page after success modal closes
        location.reload();
    }, 300);
}

function showErrorModal(title, message) {
    const modal = document.getElementById('errorModal');
    const content = document.getElementById('errorModalContent');
    const titleEl = document.getElementById('errorTitle');
    const messageEl = document.getElementById('errorMessage');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.classList.remove('hidden');
    content.classList.add('modal-enter');
    
    // Add shake animation to icon
    const icon = modal.querySelector('.fa-exclamation-triangle');
    icon.parentElement.classList.add('error-icon-shake');
    
    // Remove animation classes after animation completes
    setTimeout(() => {
        content.classList.remove('modal-enter');
        icon.parentElement.classList.remove('error-icon-shake');
    }, 600);
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    const content = document.getElementById('errorModalContent');
    
    content.classList.add('modal-exit');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        content.classList.remove('modal-exit');
    }, 300);
}

function approveLeave(leaveId, sourceTable) {
    if (confirm('Are you sure you want to approve this leave request?')) {
        fetch('approve-leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                leave_id: leaveId,
                table: sourceTable,
                action: 'approve'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal('Leave Approved!', 'The leave request has been approved successfully.');
            } else {
                showErrorModal('Approval Failed', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Network Error', 'A network error occurred while approving the leave request.');
        });
    }
}

function rejectLeave(leaveId, sourceTable) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason !== null) {
        fetch('approve-leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                leave_id: leaveId,
                table: sourceTable,
                action: 'reject',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal('Leave Rejected', 'The leave request has been rejected successfully.');
            } else {
                showErrorModal('Rejection Failed', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Network Error', 'A network error occurred while rejecting the leave request.');
        });
    }
}

// Global variables for secure delete
let currentCaptcha = '';

// Open secure delete modal
function openSecureDeleteModal(leaveId, sourceTable, employeeName) {
    document.getElementById('deleteLeaveId').value = leaveId;
    document.getElementById('deleteSourceTable').value = sourceTable;
    document.getElementById('deleteTargetName').textContent = employeeName;
    
    // Reset form
    document.getElementById('secureDeleteForm').reset();
    document.getElementById('deleteSubmitBtn').disabled = false;
    
    // Generate new CAPTCHA
    refreshCaptcha();
    
    // Show modal with animation
    const modal = document.getElementById('secureDeleteModal');
    const modalContent = document.getElementById('secureDeleteModalContent');
    
    modal.classList.remove('hidden');
    
    // Trigger animation
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Close secure delete modal
function closeSecureDeleteModal() {
    const modal = document.getElementById('secureDeleteModal');
    const modalContent = document.getElementById('secureDeleteModalContent');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    // Hide modal after animation
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('secureDeleteForm').reset();
    }, 300);
}

// Generate and display CAPTCHA
function refreshCaptcha() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    currentCaptcha = '';
    for (let i = 0; i < 6; i++) {
        currentCaptcha += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // Display CAPTCHA with some visual styling
    const captchaDisplay = document.getElementById('captchaDisplay');
    captchaDisplay.innerHTML = '';
    
    for (let i = 0; i < currentCaptcha.length; i++) {
        const span = document.createElement('span');
        span.textContent = currentCaptcha[i];
        span.style.transform = `rotate(${Math.random() * 20 - 10}deg)`;
        span.style.display = 'inline-block';
        span.style.margin = '0 2px';
        span.style.color = `hsl(${Math.random() * 360}, 70%, 40%)`;
        captchaDisplay.appendChild(span);
    }
}


// Handle secure delete form submission
document.getElementById('secureDeleteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const captchaInput = document.getElementById('captchaInput').value.trim().toUpperCase();
    const password = document.getElementById('deletePassword').value;
    
    // Validate password
    if (!password || password.trim() === '') {
        // For validation errors, keep modal open but show error with higher z-index
        showErrorModal('Password Required', 'Please enter your password to continue.');
        return;
    }
    
    // Validate CAPTCHA (use trimmed, uppercased value)
    if (captchaInput !== currentCaptcha) {
        // For validation errors, keep modal open but show error with higher z-index
        showErrorModal('CAPTCHA Error', 'CAPTCHA code is incorrect. Please try again.');
        refreshCaptcha();
        return;
    }
    
    // Disable submit button
    const submitBtn = document.getElementById('deleteSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...';
    
    // Add CAPTCHA validation to form data (send the normalized input and the generated answer)
    formData.set('captcha', captchaInput);
    formData.set('captcha_answer', currentCaptcha);
    
    // Ensure all required fields are present
    const finalConfirmation = document.getElementById('finalConfirmation');
    if (!finalConfirmation || !finalConfirmation.checked) {
        // For validation errors, keep modal open but show error with higher z-index
        showErrorModal('Confirmation Required', 'Please check the final confirmation checkbox to proceed.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i>Delete Permanently';
        return;
    }
    
    // Explicitly set all form fields to ensure they're included
    formData.set('final_confirmation', 'on');
    
    // Debug: Log form data being sent
    console.log('Sending form data:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    // Submit secure delete request
    fetch('secure-delete-leave.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the secure delete modal first
            closeSecureDeleteModal();
            
            // Wait for modal to close, then show success
            setTimeout(() => {
                showSuccessModal('Delete Successful', 'The leave request has been permanently deleted.');
                // Refresh the page to update the list
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }, 350);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i>Delete Permanently';
            
            // Close the secure delete modal first
            closeSecureDeleteModal();
            
            // Wait for modal to close, then show error
            setTimeout(() => {
                showErrorModal('Delete Failed', data.message || 'Failed to delete leave request');
            }, 350);
            
            // Refresh CAPTCHA on failure
            refreshCaptcha();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i>Delete Permanently';
        
        // Close the secure delete modal first
        closeSecureDeleteModal();
        
        // Wait for modal to close, then show error
        setTimeout(() => {
            showErrorModal('Network Error', 'A network error occurred while deleting the leave request.');
        }, 350);
        
        refreshCaptcha();
    });
});
</script>


