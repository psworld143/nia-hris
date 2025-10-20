<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Performance Reviews';

$message = '';
$message_type = '';

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get current user info
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_review':
                // Handle new review creation
                $employee_id = (int)$_POST['employee_id'];
                $review_type = sanitize_input($_POST['review_type']);
                $review_period_start = $_POST['review_period_start'];
                $review_period_end = $_POST['review_period_end'];
                
                if (!empty($employee_id) && !empty($review_type) && !empty($review_period_start) && !empty($review_period_end)) {
                    // Create new performance review
                    $insert_query = "INSERT INTO performance_reviews (
                        employee_id, employee_type, reviewer_id, review_period_start, review_period_end, 
                        review_type, status, created_at
                    ) VALUES (?, 'employee', ?, ?, ?, ?, 'draft', NOW())";
                    
                    $stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($stmt, "iisss", $employee_id, $user_id, $review_period_start, $review_period_end, $review_type);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $review_id = mysqli_insert_id($conn);
                        $_SESSION['message'] = 'Performance review created successfully.';
                        $_SESSION['message_type'] = 'success';
                        header('Location: conduct-performance-review.php?id=' . safe_encrypt_id($review_id));
                        exit();
                    } else {
                        $message = 'Error creating performance review: ' . mysqli_error($conn);
                        $message_type = 'error';
                    }
                } else {
                    $message = 'All fields are required.';
                    $message_type = 'error';
                }
                break;
                
            case 'delete_review':
                $review_id = (int)$_POST['review_id'];
                if ($review_id > 0) {
                    $delete_query = "DELETE FROM performance_reviews WHERE id = ? AND reviewer_id = ? AND status = 'draft'";
                    $stmt = mysqli_prepare($conn, $delete_query);
                    mysqli_stmt_bind_param($stmt, "ii", $review_id, $user_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['message'] = 'Performance review deleted successfully.';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $message = 'Error deleting performance review.';
                        $message_type = 'error';
                    }
                }
                break;
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_department = $_GET['department'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query for performance reviews
$where_conditions = [];
$params = [];
$param_types = '';

// Base query
$query = "SELECT 
    pr.id,
    pr.employee_id,
    e.first_name,
    e.last_name,
    e.email,
    e.department,
    e.position,
    pr.review_type,
    pr.status,
    pr.overall_rating,
    pr.overall_percentage,
    pr.review_period_start,
    pr.review_period_end,
    pr.next_review_date,
    pr.created_at,
    pr.updated_at,
    u.username as reviewer_name,
    COUNT(prs.id) as criteria_scored,
    COUNT(prg.id) as goals_set
FROM performance_reviews pr
LEFT JOIN employees e ON pr.employee_id = e.id
LEFT JOIN users u ON pr.reviewer_id = u.id
LEFT JOIN performance_review_scores prs ON pr.id = prs.performance_review_id
LEFT JOIN performance_review_goals prg ON pr.id = prg.performance_review_id
WHERE 1=1";

// Add filters
if (!empty($filter_status)) {
    $where_conditions[] = "pr.status = ?";
    $params[] = $filter_status;
    $param_types .= 's';
}

if (!empty($filter_type)) {
    $where_conditions[] = "pr.review_type = ?";
    $params[] = $filter_type;
    $param_types .= 's';
}

if (!empty($filter_department)) {
    $where_conditions[] = "e.department = ?";
    $params[] = $filter_department;
    $param_types .= 's';
}

if (!empty($search_query)) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ? OR e.position LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ssss';
}

// Add where conditions
if (!empty($where_conditions)) {
    $query .= " AND " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY pr.id ORDER BY pr.created_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

$reviews = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reviews[] = $row;
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_reviews,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_reviews,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_reviews,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_reviews,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_reviews,
    AVG(overall_rating) as avg_rating,
    AVG(overall_percentage) as avg_percentage
FROM performance_reviews";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get departments for filter
$departments_query = "SELECT DISTINCT department FROM employees WHERE is_active = 1 AND department IS NOT NULL ORDER BY department";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
while ($row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $row['department'];
}

// Get review types
$review_types = ['annual', 'semi_annual', 'quarterly', 'probationary', 'promotion', 'special'];

// Get status options
$status_options = ['draft', 'in_progress', 'completed', 'approved', 'rejected'];

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-star mr-2"></i>Performance Reviews Management
                </h2>
                <p class="opacity-90">Manage and track employee performance evaluations</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <button onclick="openCreateModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus mr-2"></i>New Review
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-4 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Reviews</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_reviews'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">In Progress</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['in_progress_reviews'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Completed</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['completed_reviews'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-star text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Avg Rating</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['avg_rating'] ? number_format((float)$stats['avg_rating'], 2) : 'N/A'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Filter Reviews</h3>
        <a href="performance-reviews.php" class="text-sm text-green-600 hover:text-indigo-700 font-medium">
            <i class="fas fa-redo mr-1"></i>Reset Filters
        </a>
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                   placeholder="Search employees..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">All Status</option>
                <?php foreach ($status_options as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Review Type</label>
            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">All Types</option>
                <?php foreach ($review_types as $type): ?>
                <option value="<?php echo $type; ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $filter_department === $dept ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-1"></i> Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Reviews Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Performance Reviews List</h3>
        <span class="text-sm text-gray-500"><?php echo count($reviews); ?> review(s)</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="reviewsTable">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Review Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-clipboard-list text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No performance reviews found</p>
                            <p class="text-sm text-gray-500 mt-1">Create your first performance review to get started.</p>
                            <button onclick="openCreateModal()" class="mt-4 bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Create Review
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <tr class="hover:bg-green-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold text-green-600">
                                    <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($review['position']); ?> - <?php echo htmlspecialchars($review['department']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            <?php echo ucfirst(str_replace('_', ' ', $review['review_type'])); ?>
                        </span>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('M j, Y', strtotime($review['review_period_start'])); ?> - 
                        <?php echo date('M j, Y', strtotime($review['review_period_end'])); ?>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $status_colors = [
                            'draft' => 'bg-gray-100 text-gray-800',
                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'approved' => 'bg-blue-100 text-blue-800',
                            'rejected' => 'bg-red-100 text-red-800'
                        ];
                        $status_color = $status_colors[$review['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $review['status'])); ?>
                        </span>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?php if ($review['overall_rating']): ?>
                        <div class="flex items-center">
                            <span class="text-lg font-bold text-green-600"><?php echo number_format((float)$review['overall_rating'], 1); ?></span>
                            <span class="text-sm text-gray-500 ml-1">/ 5.0</span>
                        </div>
                        <?php else: ?>
                        <span class="text-gray-400">Not rated</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min(100, ($review['criteria_scored'] + $review['goals_set']) * 10); ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-600">
                                <?php echo $review['criteria_scored'] + $review['goals_set']; ?> items
                            </span>
                        </div>
                    </td>
                    
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-3">
                            <a href="view-performance-review.php?id=<?php echo safe_encrypt_id($review['id']); ?>" 
                               class="text-green-600 hover:text-green-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="conduct-performance-review.php?id=<?php echo safe_encrypt_id($review['id']); ?>" 
                               class="text-blue-600 hover:text-blue-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($review['status'] === 'draft'): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this review?')">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Review Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-xl bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create New Performance Review
                </h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-5">
                <input type="hidden" name="action" value="create_review">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-green-600"></i>Employee
                    </label>
                    <select name="employee_id" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Select Employee</option>
                        <?php
                        $employees_query = "SELECT id, first_name, last_name, email, department, position FROM employees WHERE is_active = 1 ORDER BY first_name, last_name";
                        $employees_result = mysqli_query($conn, $employees_query);
                        while ($employee = mysqli_fetch_assoc($employees_result)):
                        ?>
                        <option value="<?php echo $employee['id']; ?>">
                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' - ' . $employee['department']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-clipboard-check mr-2 text-green-600"></i>Review Type
                    </label>
                    <select name="review_type" required class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Select Review Type</option>
                        <?php foreach ($review_types as $type): ?>
                        <option value="<?php echo $type; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-2 text-green-600"></i>Period Start
                        </label>
                        <input type="date" name="review_period_start" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-check mr-2 text-green-600"></i>Period End
                        </label>
                        <input type="date" name="review_period_end" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeCreateModal()" 
                            class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Create Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>
