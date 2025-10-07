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

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query conditions
$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if ($search) {
    $where_conditions[] = "(d.name LIKE ? OR d.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $param_types .= "ss";
}

if ($status_filter) {
    $where_conditions[] = "d.is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
    $param_types .= "i";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM departments d $where_clause";
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

// Get departments with pagination
$departments_query = "SELECT d.*, u.first_name, u.last_name 
                     FROM departments d 
                     LEFT JOIN users u ON d.created_by = u.id
                     $where_clause 
                     ORDER BY d.sort_order, d.name 
                     LIMIT ? OFFSET ?";

// Add pagination parameters
$pagination_params = array_merge($params, [$records_per_page, $offset]);
$pagination_param_types = $param_types . "ii";

$departments_stmt = mysqli_prepare($conn, $departments_query);
if ($departments_stmt && !empty($pagination_params)) {
    mysqli_stmt_bind_param($departments_stmt, $pagination_param_types, ...$pagination_params);
    mysqli_stmt_execute($departments_stmt);
    $departments_result = mysqli_stmt_get_result($departments_stmt);
} else {
    $departments_result = mysqli_query($conn, $departments_query);
}

$departments = [];
while ($row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $row;
}

// Set page title
$page_title = 'Manage Departments';

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Departments</h1>
            <p class="text-gray-600">View and manage department information</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openAddDepartmentModal()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-plus mr-2"></i>Add Department
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
    <form method="GET" id="department-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" id="department-search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search by name or description..."
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
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
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>
    </form>
</div>

<!-- Search Results -->
<div id="search-results" class="mb-6" style="display: none;"></div>

<!-- Departments Cards Grid -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-medium text-gray-900">Departments (<?php echo $total_records; ?> found)</h3>
        <div class="text-sm text-gray-500">
            <i class="fas fa-th-large mr-2"></i>Grid View
        </div>
    </div>
    
    <?php if (empty($departments)): ?>
        <div class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No departments found</h3>
            <p class="text-gray-500">Try adjusting your search filters or add a new department.</p>
        </div>
    <?php else: ?>
        <!-- Departments Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($departments as $department): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg hover:border-green-500 transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full">
                    <!-- Department Avatar and Basic Info -->
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 rounded-lg flex items-center justify-center text-white font-bold text-lg mx-auto mb-3 shadow-lg" 
                             style="background-color: <?php echo htmlspecialchars($department['color_theme'] ?? '#6B7280'); ?>">
                            <i class="<?php echo htmlspecialchars($department['icon'] ?? 'fas fa-building'); ?> text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-1 leading-tight">
                            <?php echo htmlspecialchars($department['name']); ?>
                        </h4>
                        <span class="inline-flex items-center px-3 py-1 text-xs rounded-full font-semibold <?php echo $department['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $department['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-4 pb-4 border-b border-gray-100 flex-grow">
                        <div class="text-center">
                            <p class="text-sm text-gray-600" title="<?php echo htmlspecialchars($department['description'] ?? ''); ?>">
                                <?php echo $department['description'] ? (strlen($department['description']) > 80 ? substr(htmlspecialchars($department['description']), 0, 80) . '...' : htmlspecialchars($department['description'])) : 'No description'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Department Info -->
                    <div class="mb-4 space-y-2">
                        <div class="text-center">
                            <div class="flex justify-center space-x-4 text-xs text-gray-500">
                                <div>
                                    <span class="font-medium">Code:</span> <?php echo htmlspecialchars($department['code']); ?>
                                </div>
                                <div>
                                    <span class="font-medium">Order:</span> <?php echo $department['sort_order'] ?? 0; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-1 mt-auto">
                        <a href="view-department.php?id=<?php echo encrypt_id($department['id']); ?>" 
                           class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200"
                           title="View Department Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit-department.php?id=<?php echo encrypt_id($department['id']); ?>" 
                           class="flex-1 bg-green-400 hover:bg-green-500 text-white text-center py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200"
                           title="Edit Department">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="showDeleteDepartmentModal('<?php echo encrypt_id($department['id']); ?>', '<?php echo htmlspecialchars($department['name']); ?>')" 
                               class="text-red-600 hover:text-red-800 hover:bg-red-50 py-1.5 px-1 rounded text-xs font-medium transition-colors duration-200"
                               title="Delete Department">
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

<!-- Add Department Modal -->
<div id="addDepartmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-4 mx-auto p-6 border w-full max-w-2xl shadow-2xl rounded-xl bg-white max-h-[95vh] overflow-y-auto">
        <div class="mt-2">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Add New Department</h3>
                    <p class="text-gray-600 mt-1">Create a new department for the organization</p>
                </div>
                <button onclick="closeAddDepartmentModal()" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form id="addDepartmentForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
                        <input type="text" name="name" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               placeholder="Enter department name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department Code *</label>
                        <input type="text" name="code" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               placeholder="e.g., ENG, HR, FIN">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                              placeholder="Enter department description"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon Class *</label>
                        <input type="text" name="icon" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               placeholder="fas fa-building" value="fas fa-building">
                        <p class="text-xs text-gray-500 mt-1">FontAwesome icon class (e.g., fas fa-building)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color Theme</label>
                        <input type="color" name="color_theme"
                               class="w-full h-10 px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               value="#FF6B35">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                        <input type="number" name="sort_order" min="0"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               placeholder="0" value="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="is_active" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white border-t border-gray-200 mt-6 pt-6">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-2"></i>
                            Fields marked with * are required
                        </div>
                        <div class="flex space-x-4">
                            <button type="button" onclick="closeAddDepartmentModal()" 
                                    class="px-8 py-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors font-medium border border-gray-300">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                            <button type="submit" 
                                    class="px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transform transition-all hover:scale-105 font-medium shadow-lg">
                                <i class="fas fa-plus mr-2"></i>Add Department
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Department Modal -->
<div id="deleteDepartmentModal" class="fixed inset-0 bg-red-600 bg-opacity-50 flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="deleteDepartmentModalContent">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Delete Department</h3>
                    <p class="text-red-600 text-sm font-medium">⚠️ DANGEROUS OPERATION</p>
                </div>
            </div>
            
            <!-- Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-500 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-semibold text-red-800 mb-2">WARNING: This will permanently delete the department</h4>
                        <p class="text-sm text-red-700">Department: <strong id="deleteDepartmentName">Department Name</strong></p>
                        <p class="text-xs text-red-600 mt-2">This action cannot be undone and will remove ALL associated records from the system.</p>
                    </div>
                </div>
            </div>
            
            <!-- HR Password Verification -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>HR Officer Password *
                </label>
                <input type="password" id="hrPassword" placeholder="Enter your HR password"
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 transition-all">
                <p class="text-xs text-gray-500 mt-1">Password verification is required for delete operations</p>
            </div>
            
            <!-- Final Confirmation -->
            <div class="mb-6">
                <label class="flex items-start space-x-3">
                    <input type="checkbox" id="confirmDeleteDepartment" 
                           class="mt-1 h-4 w-4 text-red-500 focus:ring-red-500 border-gray-300 rounded">
                    <span class="text-sm text-gray-700">
                        I understand that this will <strong class="text-red-600">permanently delete the department</strong> 
                        and cannot be undone. I take full responsibility for this action.
                    </span>
                </label>
            </div>
            
            <!-- Actions -->
            <div class="flex space-x-3">
                <button onclick="closeDeleteDepartmentModal()" 
                        class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button onclick="executeDeleteDepartment()" 
                        class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-medium">
                    <i class="fas fa-trash mr-2"></i>Delete Department
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Custom JavaScript for HR Dashboard -->
<script src="assets/js/hr-dashboard.js"></script>

<script>
// Modal functions
function openAddDepartmentModal() {
    document.getElementById('addDepartmentModal').classList.remove('hidden');
}

function closeAddDepartmentModal() {
    document.getElementById('addDepartmentModal').classList.add('hidden');
    
    // Reset form
    const form = document.getElementById('addDepartmentForm');
    if (form) {
        form.reset();
        
        // Remove validation styling
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.classList.remove('border-red-500');
            field.disabled = false;
        });
    }
}

// Store department data globally for delete operations
let currentDepartmentId = null;
let currentDepartmentName = null;

// Delete Department Modal Functions
function showDeleteDepartmentModal(departmentId, departmentName) {
    currentDepartmentId = departmentId;
    currentDepartmentName = departmentName;
    
    // Update modal content
    document.getElementById('deleteDepartmentName').textContent = departmentName;
    
    // Reset form
    document.getElementById('hrPassword').value = '';
    document.getElementById('confirmDeleteDepartment').checked = false;
    
    // Show modal with animation
    const modal = document.getElementById('deleteDepartmentModal');
    const content = document.getElementById('deleteDepartmentModalContent');
    
    modal.classList.remove('hidden');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeDeleteDepartmentModal() {
    const modal = document.getElementById('deleteDepartmentModal');
    const content = document.getElementById('deleteDepartmentModalContent');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        // Reset form
        document.getElementById('hrPassword').value = '';
        document.getElementById('confirmDeleteDepartment').checked = false;
    }, 300);
}

function executeDeleteDepartment() {
    const password = document.getElementById('hrPassword').value;
    const confirmed = document.getElementById('confirmDeleteDepartment').checked;
    
    // Validate inputs
    if (!password.trim()) {
        showToast('Please enter your HR password', 'error');
        return;
    }
    
    if (!confirmed) {
        showToast('Please confirm that you understand the consequences', 'error');
        return;
    }
    
    // Show loading state
    const deleteBtn = document.querySelector('button[onclick="executeDeleteDepartment()"]');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    deleteBtn.disabled = true;
    
    // Execute delete
    fetch('delete-department.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            department_id: currentDepartmentId,
            hr_password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Department deleted successfully', 'success');
            closeDeleteDepartmentModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message || 'Error deleting department', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error deleting department', 'error');
    })
    .finally(() => {
        // Reset button state
        deleteBtn.innerHTML = originalText;
        deleteBtn.disabled = false;
    });
}

// Form submission with validation
document.getElementById('addDepartmentForm').addEventListener('submit', function(e) {
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
    
    const formData = new FormData(this);
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Department...';
    submitBtn.disabled = true;
    
    // Disable all form fields during submission
    const formFields = this.querySelectorAll('input, select, textarea');
    formFields.forEach(field => field.disabled = true);
    
    fetch('add-department.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('Department added successfully!', 'success');
            closeAddDepartmentModal();
            
            // Show success animation
            const successIcon = document.createElement('div');
            successIcon.className = 'fixed inset-0 bg-green-500 bg-opacity-20 flex items-center justify-center z-50';
            successIcon.innerHTML = '<div class="bg-white p-8 rounded-lg shadow-lg text-center"><i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i><h3 class="text-xl font-bold text-gray-900">Department Added Successfully!</h3></div>';
            document.body.appendChild(successIcon);
            
            setTimeout(() => {
                document.body.removeChild(successIcon);
                // Reload page to show new department
                window.location.reload();
            }, 2000);
        } else {
            showToast(data.message || 'Error adding department', 'error');
            
            // Re-enable form fields on error
            formFields.forEach(field => field.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        
        // Re-enable form fields on error
        formFields.forEach(field => field.disabled = false);
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

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

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const addModal = document.getElementById('addDepartmentModal');
    const deleteModal = document.getElementById('deleteDepartmentModal');
    
    if (e.target === addModal) {
        closeAddDepartmentModal();
    }
    if (e.target === deleteModal) {
        closeDeleteDepartmentModal();
    }
});
</script>
