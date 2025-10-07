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

// Check if degrees table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'degrees'");
$table_exists = mysqli_num_rows($table_check) > 0;

// If table doesn't exist, show setup page
if (!$table_exists) {
    $page_title = 'Setup Degrees Table';
    include 'includes/header.php';
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Degrees Table Not Found</h2>
            <p class="text-gray-600 mb-6 text-lg">
                The degrees table hasn't been created yet. Click the button below to create it with default educational degree levels.
            </p>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 text-left">
                <h3 class="font-semibold text-blue-900 mb-2">What will be created:</h3>
                <ul class="text-blue-800 space-y-1 ml-4">
                    <li>✓ Degrees database table with proper structure</li>
                    <li>✓ 8 default degree levels (Elementary through Post-Doctorate)</li>
                    <li>✓ Proper indexes and relationships for optimal performance</li>
                </ul>
            </div>
            
            <button onclick="setupTable()" id="setupBtn" 
                    class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium text-lg shadow-lg">
                <i class="fas fa-cog mr-2"></i>Create Degrees Table
            </button>
            
            <div id="setupResult" class="mt-6 hidden"></div>
        </div>
    </div>
    
    <script>
    function setupTable() {
        const btn = document.getElementById('setupBtn');
        const result = document.getElementById('setupResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating table...';
        
        fetch('setup-degrees-table.php', {
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
                            <h3 class="font-bold text-lg">Setup Successful!</h3>
                        </div>
                        <p class="mb-2">${data.message}</p>
                        ${data.added ? `<p class="text-sm mb-4">Added ${data.added} degree(s)</p>` : ''}
                        <button onclick="location.reload()" 
                                class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-arrow-right mr-2"></i>Continue to Manage Degrees
                        </button>
                    </div>
                `;
            } else {
                result.innerHTML = `
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <h3 class="font-bold text-lg">Setup Failed</h3>
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
    $where_conditions[] = "(degree_name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $param_types .= "ss";
}

if ($status_filter) {
    $where_conditions[] = "is_active = ?";
    $params[] = $status_filter === 'active' ? 1 : 0;
    $param_types .= "i";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM degrees $where_clause";
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

// Get degrees with pagination
$degrees_query = "SELECT d.*, u.first_name, u.last_name,
                  (SELECT COUNT(*) FROM employee_details ed WHERE ed.highest_education = d.degree_name) as employee_count
                  FROM degrees d
                  LEFT JOIN users u ON d.created_by = u.id
                  $where_clause
                  ORDER BY d.sort_order ASC, d.degree_name ASC
                  LIMIT ? OFFSET ?";

$degrees_stmt = mysqli_prepare($conn, $degrees_query);
if (!empty($params)) {
    $params[] = $records_per_page;
    $params[] = $offset;
    $param_types .= "ii";
    mysqli_stmt_bind_param($degrees_stmt, $param_types, ...$params);
} else {
    mysqli_stmt_bind_param($degrees_stmt, "ii", $records_per_page, $offset);
}

mysqli_stmt_execute($degrees_stmt);
$degrees_result = mysqli_stmt_get_result($degrees_stmt);

$page_title = 'Manage Degrees';
include 'includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-md animate-fade-in" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo $_SESSION['success_message']; ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md animate-fade-in" role="alert">
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
            <h1 class="text-2xl font-bold text-gray-900">Manage Degrees</h1>
            <p class="text-gray-600">Manage educational degree levels and qualifications</p>
        </div>
        <div class="flex space-x-3">
            <a href="admin-employee.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Employees
            </a>
            <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-plus mr-2"></i>Add New Degree
            </button>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <?php
    $total_degrees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM degrees"))['count'];
    $active_degrees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM degrees WHERE is_active = 1"))['count'];
    $inactive_degrees = $total_degrees - $active_degrees;
    ?>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Degrees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_degrees; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active Degrees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $active_degrees; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-ban text-gray-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Inactive Degrees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $inactive_degrees; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search degrees..."
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
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </div>
        
        <div class="flex items-end">
            <a href="manage-degrees.php" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium text-center">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Degrees Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Degree Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($degrees_result) > 0): ?>
                    <?php while ($degree = mysqli_fetch_assoc($degrees_result)): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($degree['sort_order']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-graduation-cap text-green-600"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($degree['degree_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 max-w-xs truncate">
                                    <?php echo htmlspecialchars($degree['description'] ?? 'No description'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?php echo $degree['employee_count']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($degree['is_active']): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($degree['first_name'] . ' ' . $degree['last_name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($degree['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex justify-center space-x-2">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($degree)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                        <i class="fas fa-edit text-lg"></i>
                                    </button>
                                    <button onclick="toggleStatus(<?php echo $degree['id']; ?>, <?php echo $degree['is_active'] ? 'false' : 'true'; ?>)" 
                                            class="<?php echo $degree['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900'; ?> transition-colors" 
                                            title="<?php echo $degree['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-<?php echo $degree['is_active'] ? 'ban' : 'check-circle'; ?> text-lg"></i>
                                    </button>
                                    <?php if ($degree['employee_count'] == 0): ?>
                                        <button onclick="deleteDegree(<?php echo $degree['id']; ?>, '<?php echo htmlspecialchars($degree['degree_name']); ?>')" 
                                                class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="text-gray-400 cursor-not-allowed" title="Cannot delete - has employees" disabled>
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-graduation-cap text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No degrees found</h3>
                                <p class="text-gray-500 mb-4">Get started by adding your first degree.</p>
                                <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Add Degree
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page <span class="font-medium"><?php echo $current_page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
                </div>
                <div class="flex space-x-2">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo ($current_page - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="px-4 py-2 <?php echo $i === $current_page ? 'bg-green-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-lg text-sm font-medium transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo ($current_page + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div id="degreeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">Add New Degree</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="degreeForm" method="POST" action="add-degree.php">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="degree_id" id="degreeId">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Degree Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="degree_name" id="degreeName" required
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                           placeholder="e.g., Bachelor's Degree">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="degreeDescription" rows="3"
                              class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                              placeholder="Enter degree description..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sort Order <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="sort_order" id="sortOrder" required min="1"
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                           placeholder="e.g., 1">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first in lists</p>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="isActive" value="1" checked
                               class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-8">
                <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i><span id="submitButtonText">Add Degree</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Degree';
    document.getElementById('formAction').value = 'add';
    document.getElementById('submitButtonText').textContent = 'Add Degree';
    document.getElementById('degreeForm').reset();
    document.getElementById('degreeId').value = '';
    document.getElementById('isActive').checked = true;
    document.getElementById('degreeModal').classList.remove('hidden');
}

function openEditModal(degree) {
    document.getElementById('modalTitle').textContent = 'Edit Degree';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('submitButtonText').textContent = 'Update Degree';
    document.getElementById('degreeId').value = degree.id;
    document.getElementById('degreeName').value = degree.degree_name;
    document.getElementById('degreeDescription').value = degree.description || '';
    document.getElementById('sortOrder').value = degree.sort_order;
    document.getElementById('isActive').checked = degree.is_active == 1;
    document.getElementById('degreeModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('degreeModal').classList.add('hidden');
}

function toggleStatus(id, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const actionText = activate ? 'Activating' : 'Deactivating';
    
    if (confirm(`Are you sure you want to ${action} this degree?`)) {
        showToast(`${actionText} degree...`, 'info');
        
        fetch('toggle-degree-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&activate=${activate}`
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
            console.error('Error:', error);
        });
    }
}

function deleteDegree(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone!`)) {
        showToast('Deleting degree...', 'info');
        
        fetch('delete-degree.php', {
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
            console.error('Error:', error);
        });
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-0`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('degreeModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

