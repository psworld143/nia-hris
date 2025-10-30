<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';
require_once 'includes/id_encryption.php';

// Check database connection
if (!$conn || mysqli_connect_errno()) {
    die('<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
            <h2>Database Connection Error</h2>
            <p>Unable to connect to the database. Please try refreshing the page or contact support if the problem persists.</p>
          </div>');
}

// Check if user is logged in and can add employees
if (!isset($_SESSION['user_id']) || !canAddEmployees()) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Admin Employee';

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';

// Build query conditions based on tab selection
$where_conditions = [];
$params = [];
$param_types = "";

// Load all employees for client-side filtering (no tab-based server filtering)
// We'll filter client-side to avoid page reloads
$where_conditions[] = "1 = 1"; // Always true condition to load all employees

if ($search) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.department LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= "ssss";
}

if ($department_filter) {
    $where_conditions[] = "e.department = ?";
    $params[] = $department_filter;
    $param_types .= "s";
}

if ($status_filter) {
    $where_conditions[] = "e.is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
    $param_types .= "i";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM employees e $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if ($count_stmt && !empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($conn, $count_query);
}
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get statistics for tabs using regularization status
$stats_query = "SELECT 
    COUNT(*) as total_active,
    SUM(CASE WHEN rs.name = 'Probationary' THEN 1 ELSE 0 END) as probationary_count,
    SUM(CASE WHEN rs.name = 'Regular' THEN 1 ELSE 0 END) as regular_count,
    SUM(CASE WHEN rs.name = 'Terminated' THEN 1 ELSE 0 END) as terminated_count,
    SUM(CASE WHEN rs.name = 'Resigned' THEN 1 ELSE 0 END) as resigned_count,
    SUM(CASE WHEN e.is_active = 0 THEN 1 ELSE 0 END) as inactive_count,
    SUM(CASE WHEN rs.name IS NULL AND e.is_active = 1 THEN 1 ELSE 0 END) as no_status_count
    FROM employees e 
    LEFT JOIN employee_regularization er ON e.id = er.employee_id 
    LEFT JOIN regularization_status rs ON er.current_status_id = rs.id";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
if (!$stats) {
    $stats = [
        'total_active' => 0,
        'probationary_count' => 0,
        'regular_count' => 0,
        'terminated_count' => 0,
        'resigned_count' => 0,
        'inactive_count' => 0,
        'no_status_count' => 0
    ];
}

// Get employees with pagination including regularization status
$employees_query = "SELECT e.*, 
                           ed.profile_photo,
                           ed.employment_type,
                           d.name as department_name,
                           d.icon as department_icon,
                           d.color_theme as department_color,
                           rs.name as regularization_status,
                           rs.color as status_color,
                           er.date_of_hire,
                           er.regularization_date,
                           COALESCE(rs.name, ed.employment_type, 'Not Set') as display_status
                   FROM employees e 
                   LEFT JOIN employee_details ed ON e.id = ed.employee_id
                   LEFT JOIN departments d ON e.department = d.name 
                   LEFT JOIN employee_regularization er ON e.id = er.employee_id 
                   LEFT JOIN regularization_status rs ON er.current_status_id = rs.id 
                   $where_clause 
                   ORDER BY e.last_name, e.first_name 
                   LIMIT ? OFFSET ?";

// Add pagination parameters
$pagination_params = array_merge($params, [$records_per_page, $offset]);
$pagination_param_types = $param_types . "ii";

$employees_stmt = mysqli_prepare($conn, $employees_query);
if ($employees_stmt && !empty($pagination_params)) {
    mysqli_stmt_bind_param($employees_stmt, $pagination_param_types, ...$pagination_params);
    mysqli_stmt_execute($employees_stmt);
    $employees_result = mysqli_stmt_get_result($employees_stmt);
} else {
    $employees_result = mysqli_query($conn, $employees_query);
    if (!$employees_result) {
        // If we can't redirect due to headers already sent, show a user-friendly error
        if (headers_sent()) {
            echo '<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                    <h2>Database Error</h2>
                    <p>Unable to retrieve employee list. Please try refreshing the page or contact support if the problem persists.</p>
                  </div>';
            exit();
        }
    }
}

$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

// Get unique departments for filter
$departments_query = "SELECT id, name, code FROM departments WHERE is_active = 1 ORDER BY name";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
while ($row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $row['name'];
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Employee</h1>
            <p class="text-gray-600">Add new employees to the system</p>
        </div>
        <div class="flex space-x-3">
            <a href="add-employee-comprehensive-form.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-plus mr-2"></i>Add Employee
            </a>
            <?php if (canManageSalary()): ?>
            <a href="add-employee-salary.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-blue-500 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-money-bill-wave mr-2"></i>Add Salary
            </a>
            <?php endif; ?>
            <a href="manage-degrees.php" class="bg-seait-dark text-white px-4 py-2 rounded-lg hover:bg-gray-800 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-graduation-cap mr-2"></i>Manage Degrees
            </a>
        </div>
    </div>
</div>

<!-- Employee Status Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 overflow-x-auto">
            <button onclick="switchTab('all')" data-tab="all"
               class="tab-button <?php echo $current_tab === 'all' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-users mr-2"></i>
                All Active
                <span class="ml-1 bg-gray-100 text-gray-600 py-1 px-2 rounded-full text-xs" id="count-all"><?php echo $stats['total_active']; ?></span>
            </button>
            <button onclick="switchTab('probationary')" data-tab="probationary"
               class="tab-button <?php echo $current_tab === 'probationary' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-clock mr-2"></i>
                Probationary
                <span class="ml-1 bg-yellow-100 text-yellow-600 py-1 px-2 rounded-full text-xs" id="count-probationary"><?php echo $stats['probationary_count']; ?></span>
            </button>
            <button onclick="switchTab('regular')" data-tab="regular"
               class="tab-button <?php echo $current_tab === 'regular' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-check-circle mr-2"></i>
                Regular
                <span class="ml-1 bg-green-100 text-green-600 py-1 px-2 rounded-full text-xs" id="count-regular"><?php echo $stats['regular_count']; ?></span>
            </button>
            <button onclick="switchTab('terminated')" data-tab="terminated"
               class="tab-button <?php echo $current_tab === 'terminated' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-times-circle mr-2"></i>
                Terminated
                <span class="ml-1 bg-red-100 text-red-600 py-1 px-2 rounded-full text-xs" id="count-terminated"><?php echo $stats['terminated_count']; ?></span>
            </button>
            <button onclick="switchTab('resigned')" data-tab="resigned"
               class="tab-button <?php echo $current_tab === 'resigned' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Resigned
                <span class="ml-1 bg-gray-100 text-gray-600 py-1 px-2 rounded-full text-xs" id="count-resigned"><?php echo $stats['resigned_count']; ?></span>
            </button>
            <button onclick="switchTab('inactive')" data-tab="inactive"
               class="tab-button <?php echo $current_tab === 'inactive' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center transition-all">
                <i class="fas fa-ban mr-2"></i>
                Inactive
                <span class="ml-1 bg-red-100 text-red-600 py-1 px-2 rounded-full text-xs" id="count-inactive"><?php echo $stats['inactive_count']; ?></span>
            </button>
        </nav>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
    <form method="GET" id="employee-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Preserve current tab -->
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($current_tab); ?>">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" id="employee-search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                   placeholder="Search by name, email, or department..."
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo htmlspecialchars($department ?? ''); ?>" <?php echo $department_filter === $department ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($department ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                <option value="">All Status</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </form>
</div>

<!-- Search Results -->
<div id="search-results" class="mb-6" style="display: none;"></div>

<!-- Employee Cards Grid -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-medium text-gray-900">Employees (<?php echo $total_records; ?> found)</h3>
        <div class="text-sm text-gray-500">
            <i class="fas fa-th-large mr-2"></i>Grid View
        </div>
    </div>
    
    <?php if (empty($employees)): ?>
        <div class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
            <p class="text-gray-500">Try adjusting your search filters or add a new employee.</p>
        </div>
    <?php else: ?>
        <!-- Employee Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
            <?php foreach ($employees as $employee): ?>
                <div class="employee-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-lg hover:border-green-500 transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full"
                     data-status="<?php echo strtolower($employee['regularization_status'] ?? 'not_set'); ?>"
                     data-active="<?php echo $employee['is_active'] ? '1' : '0'; ?>"
                     data-employee-id="<?php echo $employee['id']; ?>"
                     data-name="<?php echo strtolower(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?>"
                     data-department="<?php echo strtolower($employee['department'] ?? ''); ?>"
                     data-position="<?php echo strtolower($employee['position'] ?? ''); ?>">
                    <!-- Employee Avatar and Basic Info -->
                    <div class="text-center mb-3">
                        <?php if (!empty($employee['profile_photo'])): ?>
                            <?php 
                                $photo_path = '../' . $employee['profile_photo'];
                                $full_photo_path = $_SERVER['DOCUMENT_ROOT'] . '/seait/' . $employee['profile_photo'];
                            ?>
                            <?php if (file_exists($full_photo_path)): ?>
                                <div class="w-12 h-12 rounded-full overflow-hidden mx-auto mb-2 shadow-lg border-2 border-gray-200 hover:border-green-500 transition-colors">
                                    <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" 
                                         alt="<?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?> Photo"
                                         class="w-full h-full object-cover"
                                         onerror="this.parentElement.innerHTML='<div class=\'w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-lg\'><?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?></div>'">
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm mx-auto mb-2 shadow-lg">
                                    <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm mx-auto mb-2 shadow-lg">
                                <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <h4 class="text-sm font-semibold text-gray-900 mb-1 leading-tight">
                            <?php echo ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''); ?>
                        </h4>
                        <p class="text-xs text-gray-600 mb-2 truncate" title="<?php echo $employee['position'] ?? ''; ?>"><?php echo $employee['position'] ?? ''; ?></p>
                        
                        <!-- Employment Status -->
                        <?php if ($employee['regularization_status']): ?>
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium mb-1" 
                                  style="background-color: <?php echo $employee['status_color']; ?>20; color: <?php echo $employee['status_color']; ?>;">
                                <?php echo $employee['regularization_status']; ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium mb-1 bg-gray-100 text-gray-800">
                                Not Set
                            </span>
                        <?php endif; ?>
                        
                        <!-- Active/Inactive Status -->
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium <?php echo $employee['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Department Info -->
                    <div class="mb-3 pb-3 border-b border-gray-100">
                        <div class="text-center">
                            <?php if (($employee['department_icon'] ?? null) && ($employee['department_color'] ?? null)): ?>
                                <div class="w-6 h-6 rounded flex items-center justify-center text-white text-xs mx-auto mb-1 shadow-sm" 
                                     style="background-color: <?php echo htmlspecialchars($employee['department_color']); ?>">
                                    <i class="<?php echo htmlspecialchars($employee['department_icon']); ?>"></i>
                                </div>
                            <?php endif; ?>
                            <p class="text-xs font-medium text-gray-700 px-1 leading-tight" title="<?php echo htmlspecialchars(($employee['department_name'] ?: $employee['department']) ?? 'No Department'); ?>">
                                <?php echo htmlspecialchars(($employee['department_name'] ?: $employee['department']) ?? 'No Dept'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="mb-3 flex-grow">
                        <div class="text-center">
                            <p class="text-xs text-gray-700 truncate" title="<?php echo $employee['email'] ?? ''; ?>">
                                <?php echo $employee['email'] ?? 'No email'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-1 mt-auto">
                        <a href="view-employee.php?id=<?php echo encrypt_id($employee['id']); ?>" 
                           class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit-employee.php?id=<?php echo encrypt_id($employee['id']); ?>" 
                           class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')" 
                                class="text-red-600 hover:text-red-800 hover:bg-red-50 py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200"
                                title="Delete Employee">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="bg-white rounded-xl shadow-lg mt-6 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <!-- Mobile Pagination -->
        <div class="flex-1 flex justify-between lg:hidden">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            <div class="text-sm text-gray-700">
                Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
            </div>
            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Pagination -->
        <div class="hidden lg:flex lg:flex-1 lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                    <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of 
                    <span class="font-medium"><?php echo $total_records; ?></span> results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            1
                        </a>
                        <?php if ($start_page > 2): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $current_page ? 'z-10 bg-green-500 border-green-500 text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
<?php endif; ?>


<!-- Custom JavaScript for HR Dashboard -->
<script src="assets/js/hr-dashboard.js"></script>

<script>

// Global variables for force delete functionality
let currentEmployeeId = null;
let currentEmployeeName = null;

// Quick Add Modal functions removed

// Employee ID generation
function generateEmployeeID() {
    fetch('get-next-employee-id.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('employee_id_input').value = data.employee_id;
                showToast('Employee ID generated: ' + data.employee_id, 'success');
            } else {
                showToast(data.message || 'Error generating employee ID', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error. Please try again.', 'error');
        });
}
function updateFormProgress() {
    const form = document.getElementById('addEmployeeForm');
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
    const filledFields = Array.from(requiredFields).filter(field => field.value.trim() !== '');
    const progress = Math.round((filledFields.length / requiredFields.length) * 100);
    
    document.getElementById('formProgress').textContent = progress + '%';
    
    // Update progress bar color
    const progressElement = document.getElementById('formProgress');
    if (progress < 50) {
        progressElement.className = 'text-red-500 font-semibold';
    } else if (progress < 100) {
        progressElement.className = 'text-yellow-500 font-semibold';
    } else {
        progressElement.className = 'text-green-500 font-semibold';
    }
}

// Add event listeners to all form fields
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addEmployeeForm');
    if (form) {
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.addEventListener('input', updateFormProgress);
            field.addEventListener('change', updateFormProgress);
        });
    }
});

// Form submission with enhanced validation and error handling
const addEmployeeForm = document.getElementById('addEmployeeForm');
if (addEmployeeForm) {
    addEmployeeForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Client-side validation
    const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
    const missingFields = [];
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            missingFields.push(field.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    if (missingFields.length > 0) {
        showToast('Please fill in all required fields: ' + missingFields.join(', '), 'error');
        return;
    }
    
    // Email validation
    const emailField = this.querySelector('input[name="email"]');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailField.value)) {
        showToast('Please enter a valid email address', 'error');
        emailField.classList.add('border-red-500');
        return;
    } else {
        emailField.classList.remove('border-red-500');
    }
    
    // Password validation
    const passwordField = this.querySelector('input[name="password"]');
    const confirmPasswordField = this.querySelector('input[name="confirm_password"]');
    
    if (passwordField.value.length < 8) {
        showToast('Password must be at least 8 characters long', 'error');
        passwordField.classList.add('border-red-500');
        return;
    } else {
        passwordField.classList.remove('border-red-500');
    }
    
    if (passwordField.value !== confirmPasswordField.value) {
        showToast('Passwords do not match', 'error');
        confirmPasswordField.classList.add('border-red-500');
        return;
    } else {
        confirmPasswordField.classList.remove('border-red-500');
    }
    
    const formData = new FormData(this);
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Employee...';
    submitBtn.disabled = true;
    
    // Disable all form fields during submission
    const formFields = this.querySelectorAll('input, select, textarea');
    formFields.forEach(field => field.disabled = true);
    
    fetch('add-employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            showToast('Employee added successfully!', 'success');
            closeAddEmployeeModal();
            
            // Show success animation
            const successIcon = document.createElement('div');
            successIcon.className = 'fixed inset-0 bg-green-500 bg-opacity-20 flex items-center justify-center z-50';
            successIcon.innerHTML = '<div class="bg-white p-8 rounded-lg shadow-lg text-center"><i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i><h3 class="text-xl font-bold text-gray-900">Employee Added Successfully!</h3></div>';
            document.body.appendChild(successIcon);
            
            setTimeout(() => {
                document.body.removeChild(successIcon);
                // Reload page to show new employee
                window.location.reload();
            }, 2000);
        } else {
            showToast(data.message || 'Error adding employee', 'error');
            
            // Re-enable form fields on error
            formFields.forEach(field => field.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error: ' + error.message, 'error');
        
        // Re-enable form fields on error
        formFields.forEach(field => field.disabled = false);
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
}

// Enhanced toast notification function
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => document.body.removeChild(toast));
    
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-xl transform transition-all duration-300 translate-x-full max-w-md`;
    
    // Set background and icon based on type
    let bgColor, icon, iconColor;
    switch(type) {
        case 'success':
            bgColor = 'bg-gradient-to-r from-green-500 to-green-600';
            icon = 'fas fa-check-circle';
            iconColor = 'text-green-100';
            break;
        case 'error':
            bgColor = 'bg-gradient-to-r from-red-500 to-red-600';
            icon = 'fas fa-exclamation-circle';
            iconColor = 'text-red-100';
            break;
        case 'warning':
            bgColor = 'bg-gradient-to-r from-yellow-500 to-yellow-600';
            icon = 'fas fa-exclamation-triangle';
            iconColor = 'text-yellow-100';
            break;
        default:
            bgColor = 'bg-gradient-to-r from-blue-500 to-blue-600';
            icon = 'fas fa-info-circle';
            iconColor = 'text-blue-100';
    }
    
    toast.className += ` ${bgColor} text-white`;
    
    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="${icon} ${iconColor} text-xl"></i>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// Delete employee function - checks for related data
function deleteEmployee(employeeId, employeeName) {
    // Show loading state
    const deleteBtn = event.target.closest('button');
    const originalHTML = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    deleteBtn.disabled = true;
    
    fetch('delete-employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            employee_id: employeeId
        })
    })
    .then(response => {
        // Try to get response text first to see what we're getting
        return response.text().then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                // If not JSON, show the raw text (might be HTML error page)
                console.error('Non-JSON response:', text);
                throw new Error('Server returned invalid response. Check console for details.');
            }
            
            if (!response.ok) {
                // Response was parsed but status is not OK
                const errorMsg = data.message || data.error || `HTTP error! status: ${response.status}`;
                throw new Error(errorMsg);
            }
            
            return data;
        });
    })
    .then(data => {
        if (data.success) {
            showToast('Employee deleted successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Check if it's a constraint violation (related data exists)
            if (data.related_data && data.related_data.length > 0) {
                // Store employee ID for force delete functionality
                currentEmployeeId = employeeId;
                // Show detailed error modal for constraint violations
                showConstraintErrorModal(employeeName, data.message, data.related_data);
            } else if (data.deletion_disabled) {
                // Store employee ID for force delete functionality
                currentEmployeeId = employeeId;
                // Show general deletion disabled message
                showConstraintErrorModal(employeeName, data.message, []);
            } else {
                // Show regular error toast for other errors
                showToast(data.message || 'Error deleting employee', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Network error. Please try again.';
        showToast(errorMessage, 'error');
    })
    .finally(() => {
        // Reset button state
        deleteBtn.innerHTML = originalHTML;
        deleteBtn.disabled = false;
    });
}

// Show constraint error modal for detailed error information
function showConstraintErrorModal(employeeName, message, relatedData) {
    // Store employee data for force delete functionality
    currentEmployeeName = employeeName;
    
    // Create modal if it doesn't exist
    let modal = document.getElementById('constraintErrorModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'constraintErrorModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50';
        modal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
                    <div class="p-6 text-center">
                        <div class="mb-4">
                            <div class="p-4 rounded-full bg-red-100 text-red-600 inline-block mb-4">
                                <i class="fas fa-shield-alt text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Cannot Delete Employee</h3>
                            <p class="text-gray-700 mb-4 font-medium" id="constraintEmployeeName"></p>
                            <div class="text-gray-600 mb-4">
                                <p class="mb-3 text-sm" id="constraintMessage"></p>
                                <div id="relatedDataContainer" class="bg-red-50 border border-red-200 rounded-lg p-3" style="display: none;">
                                    <div class="text-red-800 mb-2 text-sm font-medium">
                                        <i class="fas fa-exclamation-circle mr-2"></i>Existing Data:
                                    </div>
                                    <ul id="relatedDataList" class="text-sm text-red-700 space-y-1 text-left">
                                        <!-- Related data will be populated here -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col space-y-3">
                            <button onclick="closeConstraintErrorModal()"
                                    class="w-full px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all duration-200 font-medium">
                                <i class="fas fa-check mr-2"></i>Understood
                            </button>
                            <button onclick="showForceDeleteEmployeeModal()"
                                    class="w-full px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all duration-200 font-medium">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Force Delete (Admin Only)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Update modal content
    document.getElementById('constraintEmployeeName').textContent = employeeName;
    document.getElementById('constraintMessage').textContent = message;
    
    // Show related data if available
    const relatedDataContainer = document.getElementById('relatedDataContainer');
    const relatedDataList = document.getElementById('relatedDataList');
    
    if (relatedData && relatedData.length > 0) {
        relatedDataContainer.style.display = 'block';
        relatedDataList.innerHTML = '';
        relatedData.forEach(item => {
            const li = document.createElement('li');
            li.className = 'text-red-700';
            li.innerHTML = `• ${item}`;
            relatedDataList.appendChild(li);
        });
    } else {
        relatedDataContainer.style.display = 'none';
    }
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeConstraintErrorModal() {
    const modal = document.getElementById('constraintErrorModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Show force delete modal with password and CAPTCHA verification
function showForceDeleteEmployeeModal() {
    // First close the constraint error modal
    closeConstraintErrorModal();
    
    // Use the stored employee data from the constraint modal
    if (!currentEmployeeId || !currentEmployeeName) {
        showToast('Error: Employee information not available', 'error');
        return;
    }
    
    // Create force delete modal
    let forceModal = document.getElementById('forceDeleteEmployeeModal');
    if (!forceModal) {
        forceModal = document.createElement('div');
        forceModal.id = 'forceDeleteEmployeeModal';
        forceModal.className = 'fixed inset-0 bg-red-600 bg-opacity-50 flex items-center justify-center p-4 z-50 hidden';
        forceModal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="forceDeleteEmployeeModalContent">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Force Delete Employee</h3>
                            <p class="text-red-600 text-sm font-medium">⚠️ DANGEROUS OPERATION</p>
                        </div>
                    </div>
                    
                    <!-- Warning -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-red-800 mb-2">WARNING: This will permanently delete all related data</h4>
                                <p class="text-sm text-red-700">Employee: <strong id="forceDeleteEmployeeName">Employee Name</strong></p>
                                <p class="text-xs text-red-600 mt-2">This action cannot be undone and will remove ALL associated records from the system.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- HR Password Verification -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>HR Officer Password *
                        </label>
                        <input type="password" id="hrEmployeePassword" placeholder="Enter your HR password"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
                        <p class="text-xs text-gray-500 mt-1">Password verification is required for force delete operations</p>
                    </div>
                    
                    <!-- CAPTCHA Verification -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-shield-alt mr-2"></i>Security Verification *
                        </label>
                        <div class="bg-gray-100 border-2 border-gray-200 rounded-lg p-4 mb-3">
                            <div class="flex items-center justify-between">
                                <div class="font-mono text-2xl font-bold text-gray-800 bg-white px-4 py-2 rounded border-2 border-gray-300 select-none" id="employeeCaptchaCode">
                                    <!-- CAPTCHA code will be generated here -->
                                </div>
                                <button type="button" onclick="generateEmployeeCaptcha()" class="text-blue-600 hover:text-blue-800 p-2">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <input type="text" id="employeeCaptchaInput" placeholder="Enter the code above"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
                        <p class="text-xs text-gray-500 mt-1">Enter the security code to verify you are human</p>
                    </div>
                    
                    <!-- Final Confirmation -->
                    <div class="mb-6">
                        <label class="flex items-start space-x-3">
                            <input type="checkbox" id="confirmForceDeleteEmployee" 
                                   class="mt-1 h-4 w-4 text-red-500 focus:ring-red-500 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">
                                I understand that this will <strong class="text-red-600">permanently delete all related data</strong> 
                                and cannot be undone. I take full responsibility for this action.
                            </span>
                        </label>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button onclick="closeForceDeleteEmployeeModal()" 
                                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button onclick="executeForceDeleteEmployee()" 
                                class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium">
                            <i class="fas fa-trash mr-2"></i>Force Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(forceModal);
    }
    
    // Update modal content
    document.getElementById('forceDeleteEmployeeName').textContent = currentEmployeeName;
    
    // Store employee data for later use
    forceModal.dataset.employeeName = currentEmployeeName;
    
    // Generate CAPTCHA
    generateEmployeeCaptcha();
    
    // Show modal with animation
    forceModal.classList.remove('hidden');
    setTimeout(() => {
        const content = document.getElementById('forceDeleteEmployeeModalContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Close force delete modal
function closeForceDeleteEmployeeModal() {
    const modal = document.getElementById('forceDeleteEmployeeModal');
    if (modal) {
        const content = document.getElementById('forceDeleteEmployeeModalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            // Reset form
            document.getElementById('hrEmployeePassword').value = '';
            document.getElementById('employeeCaptchaInput').value = '';
            document.getElementById('confirmForceDeleteEmployee').checked = false;
        }, 300);
    }
}

// Generate CAPTCHA code for employee
let currentEmployeeCaptcha = '';
function generateEmployeeCaptcha() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let captcha = '';
    for (let i = 0; i < 6; i++) {
        captcha += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    currentEmployeeCaptcha = captcha;
    document.getElementById('employeeCaptchaCode').textContent = captcha;
    document.getElementById('employeeCaptchaInput').value = '';
}

// Execute force delete with all verifications
function executeForceDeleteEmployee() {
    const modal = document.getElementById('forceDeleteEmployeeModal');
    const employeeName = modal.dataset.employeeName;
    const password = document.getElementById('hrEmployeePassword').value;
    const captchaInput = document.getElementById('employeeCaptchaInput').value;
    const confirmCheckbox = document.getElementById('confirmForceDeleteEmployee').checked;
    
    // Validation
    if (!password) {
        showToast('Please enter your HR password', 'error');
        return;
    }
    
    if (!captchaInput) {
        showToast('Please enter the security code', 'error');
        return;
    }
    
    if (captchaInput.toUpperCase() !== currentEmployeeCaptcha) {
        showToast('Security code is incorrect. Please try again.', 'error');
        generateEmployeeCaptcha(); // Generate new CAPTCHA
        return;
    }
    
    if (!confirmCheckbox) {
        showToast('Please confirm that you understand the consequences', 'error');
        return;
    }
    
    // Show loading state
    const deleteBtn = document.querySelector('#forceDeleteEmployeeModal button[onclick="executeForceDeleteEmployee()"]');
    const originalHTML = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteBtn.disabled = true;
    
    // Use the stored employee ID
    if (!currentEmployeeId) {
        showToast('Error: Could not determine employee ID', 'error');
        return;
    }
    
    const employeeId = currentEmployeeId;
    
    // Execute force delete
    fetch('force-delete-employee.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            employee_id: employeeId,
            hr_password: password,
            force_delete: true
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        // Check if response has content
        return response.text().then(text => {
            if (!text) {
                throw new Error('Empty response from server');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response format from server');
            }
        });
    })
    .then(data => {
        if (data.success) {
            closeForceDeleteEmployeeModal();
            showToast(`${employeeName} and all related data have been permanently deleted`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showToast(data.message || 'Error during force delete operation', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        deleteBtn.innerHTML = originalHTML;
        deleteBtn.disabled = false;
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('addEmployeeModal');
    if (e.target === modal) {
        closeAddEmployeeModal();
    }
    
    const constraintModal = document.getElementById('constraintErrorModal');
    if (e.target === constraintModal) {
        closeConstraintErrorModal();
    }
    
    const forceDeleteEmployeeModal = document.getElementById('forceDeleteEmployeeModal');
    if (e.target === forceDeleteEmployeeModal) {
        closeForceDeleteEmployeeModal();
    }
});

// Tab switching functionality without page reload
let currentActiveTab = '<?php echo $current_tab; ?>';

function switchTab(tabName) {
    console.log('Switching to tab:', tabName);
    
    // Update active tab
    currentActiveTab = tabName;
    
    // Update tab appearance
    document.querySelectorAll('.tab-button').forEach(button => {
        const isActive = button.dataset.tab === tabName;
        if (isActive) {
            button.className = button.className.replace(/border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300/, 'border-green-500 text-green-600');
        } else {
            button.className = button.className.replace(/border-green-500 text-green-600/, 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300');
        }
    });
    
    // Filter employee cards
    filterEmployeeCards();
    
    // Update URL without reloading page
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    url.searchParams.set('page', '1'); // Reset to first page
    window.history.pushState({}, '', url);
}

function filterEmployeeCards() {
    const cards = document.querySelectorAll('.employee-card');
    const searchTerm = document.getElementById('employee-search').value.toLowerCase();
    const departmentFilter = document.querySelector('select[name="department"]').value.toLowerCase();
    
    let visibleCount = 0;
    
    cards.forEach(card => {
        let shouldShow = true;
        
        // Tab-based filtering
        switch (currentActiveTab) {
            case 'all':
                shouldShow = card.dataset.active === '1';
                break;
            case 'probationary':
                shouldShow = card.dataset.status === 'probationary';
                break;
            case 'regular':
                shouldShow = card.dataset.status === 'regular';
                break;
            case 'terminated':
                shouldShow = card.dataset.status === 'terminated';
                break;
            case 'resigned':
                shouldShow = card.dataset.status === 'resigned';
                break;
            case 'inactive':
                shouldShow = card.dataset.active === '0';
                break;
            default:
                shouldShow = true;
        }
        
        // Search filtering
        if (shouldShow && searchTerm) {
            const matchesSearch = 
                card.dataset.name.includes(searchTerm) ||
                card.dataset.department.includes(searchTerm) ||
                card.dataset.position.includes(searchTerm);
            shouldShow = matchesSearch;
        }
        
        // Department filtering
        if (shouldShow && departmentFilter) {
            shouldShow = card.dataset.department.includes(departmentFilter.toLowerCase());
        }
        
        // Show/hide card with smooth animation
        if (shouldShow) {
            card.style.display = 'flex';
            card.style.opacity = '1';
            card.style.transform = 'scale(1)';
            visibleCount++;
        } else {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
                if (card.style.opacity === '0') {
                    card.style.display = 'none';
                }
            }, 200);
        }
    });
    
    // Update results count
    updateResultsCount(visibleCount);
    
    // Update tab counts (optional - could be dynamic)
    updateTabCounts();
    
    // Show/hide no results message
    const noResults = document.querySelector('.text-center.py-12');
    const employeeGrid = document.querySelector('.grid.grid-cols-2');
    
    if (visibleCount === 0) {
        if (noResults) noResults.style.display = 'block';
        if (employeeGrid) employeeGrid.style.display = 'none';
    } else {
        if (noResults) noResults.style.display = 'none';
        if (employeeGrid) employeeGrid.style.display = 'grid';
    }
}

function updateResultsCount(count) {
    const countElement = document.querySelector('h3.text-lg.font-medium.text-gray-900');
    if (countElement) {
        countElement.textContent = `Employees (${count} found)`;
    }
}

function updateTabCounts() {
    const cards = document.querySelectorAll('.employee-card');
    
    const counts = {
        all: 0,
        probationary: 0,
        regular: 0,
        terminated: 0,
        resigned: 0,
        inactive: 0
    };
    
    cards.forEach(card => {
        if (card.dataset.active === '1') counts.all++;
        if (card.dataset.status === 'probationary') counts.probationary++;
        if (card.dataset.status === 'regular') counts.regular++;
        if (card.dataset.status === 'terminated') counts.terminated++;
        if (card.dataset.status === 'resigned') counts.resigned++;
        if (card.dataset.active === '0') counts.inactive++;
    });
    
    // Update count displays
    Object.keys(counts).forEach(status => {
        const countElement = document.getElementById(`count-${status}`);
        if (countElement) {
            countElement.textContent = counts[status];
        }
    });
}

// Real-time search filtering
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('employee-search');
    const departmentSelect = document.querySelector('select[name="department"]');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterEmployeeCards();
        });
    }
    
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            filterEmployeeCards();
        });
    }
    
    // Apply initial filtering based on current tab
    setTimeout(() => {
        filterEmployeeCards();
    }, 100);
});

// Override form submission to use client-side filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('employee-filters');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent form submission
            filterEmployeeCards(); // Use client-side filtering instead
        });
    }
});
</script>
