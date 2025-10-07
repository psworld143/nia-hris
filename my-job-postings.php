<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Set page title
$page_title = 'My Job Postings';

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];
        
        // Verify the post belongs to the current user and is a job posting
        $verify_query = "SELECT id, status FROM posts WHERE id = ? AND author_id = ? AND type = 'hiring'";
        $stmt = mysqli_prepare($conn, $verify_query);
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($post = mysqli_fetch_assoc($result)) {
            if ($_POST['action'] === 'delete' && $post['status'] === 'draft') {
                // Only allow deletion of drafts
                $delete_query = "DELETE FROM posts WHERE id = ? AND author_id = ? AND status = 'draft' AND type = 'hiring'";
                $stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Job posting draft deleted successfully!';
                } else {
                    $message = 'Failed to delete job posting draft.';
                }
            } elseif ($_POST['action'] === 'submit' && $post['status'] === 'draft') {
                // Submit draft for approval
                $submit_query = "UPDATE posts SET status = 'pending', updated_at = NOW() WHERE id = ? AND author_id = ? AND status = 'draft' AND type = 'hiring'";
                $stmt = mysqli_prepare($conn, $submit_query);
                mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Job posting submitted for approval successfully!';
                } else {
                    $message = 'Failed to submit job posting for approval.';
                }
            } elseif ($_POST['action'] === 'resubmit' && $post['status'] === 'rejected') {
                // Resubmit rejected post
                $resubmit_query = "UPDATE posts SET status = 'pending', updated_at = NOW() WHERE id = ? AND author_id = ? AND status = 'rejected' AND type = 'hiring'";
                $stmt = mysqli_prepare($conn, $resubmit_query);
                mysqli_stmt_bind_param($stmt, "ii", $post_id, $_SESSION['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Job posting resubmitted for approval successfully!';
                } else {
                    $message = 'Failed to resubmit job posting.';
                }
            }
        } else {
            $message = 'Job posting not found or you do not have permission to modify it.';
        }
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["type = 'hiring'", "author_id = ?"];
$params = [$_SESSION['user_id']];
$param_types = "i";

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM posts $where_clause";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];

// Pagination
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$total_pages = ceil($total_records / $records_per_page);

// Get job postings with pagination
$query = "SELECT id, title, content, status, created_at, updated_at, image_url, rejected_at, rejection_reason
          FROM posts 
          $where_clause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_jobs,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_jobs,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_jobs
    FROM posts 
    WHERE type = 'hiring' AND author_id = ?";

$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Job Postings</h1>
            <p class="text-gray-600">Manage your job postings and track their approval status</p>
        </div>
        <div class="flex space-x-3">
            <a href="job-postings.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-plus mr-2"></i>Create Job Posting
            </a>
        </div>
    </div>
</div>

<!-- Success Message -->
<?php if (!empty($message)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-briefcase text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Jobs</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                <i class="fas fa-edit text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Drafts</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['draft_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Pending</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Approved</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['approved_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-times text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Rejected</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['rejected_jobs']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                   placeholder="Search job postings...">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                <option value="">All Statuses</option>
                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </div>
    </form>
</div>

<!-- Job Postings List -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Job Postings</h3>
        <p class="text-sm text-gray-600">Showing <?php echo $total_records; ?> job postings</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($job = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($job['image_url']): ?>
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-lg object-cover" src="../<?php echo htmlspecialchars($job['image_url']); ?>" alt="Job Image">
                                        </div>
                                    <?php else: ?>
                                        <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-briefcase text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr(strip_tags($job['content']), 0, 100)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$job['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                                <?php if ($job['status'] === 'rejected' && $job['rejection_reason']): ?>
                                    <div class="mt-1 text-xs text-red-600" title="<?php echo htmlspecialchars($job['rejection_reason']); ?>">
                                        <i class="fas fa-info-circle"></i> Reason available
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($job['updated_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <!-- View Button -->
                                    <button onclick="viewJobPosting(<?php echo $job['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- Edit Button (for drafts and rejected posts) -->
                                    <?php if ($job['status'] === 'draft' || $job['status'] === 'rejected'): ?>
                                        <a href="edit-job-posting.php?id=<?php echo safe_encrypt_id($job['id']); ?>" 
                                           class="text-green-600 hover:text-green-900 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Submit Button (for drafts) -->
                                    <?php if ($job['status'] === 'draft'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Submit this job posting for approval?')">
                                            <input type="hidden" name="post_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" name="action" value="submit" 
                                                    class="text-yellow-600 hover:text-yellow-900 transition-colors">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Resubmit Button (for rejected posts) -->
                                    <?php if ($job['status'] === 'rejected'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Resubmit this job posting for approval?')">
                                            <input type="hidden" name="post_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" name="action" value="resubmit" 
                                                    class="text-purple-600 hover:text-purple-900 transition-colors">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button (only for drafts) -->
                                    <?php if ($job['status'] === 'draft'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this draft?')">
                                            <input type="hidden" name="post_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" name="action" value="delete" 
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No job postings found. <a href="job-postings.php" class="text-green-600 hover:text-green-800">Create your first job posting</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results
        </div>
        
        <div class="flex space-x-2">
            <?php if ($current_page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="px-3 py-2 text-sm font-medium <?php echo $i === $current_page ? 'text-white bg-green-500 border border-green-500' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Next
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Job Posting View Modal -->
<div id="jobViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Job Posting Details</h3>
                <button onclick="closeJobViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="jobViewContent" class="max-h-96 overflow-y-auto">
                <!-- Job content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewJobPosting(postId) {
    // Show modal
    document.getElementById('jobViewModal').classList.remove('hidden');
    
    // Load job posting content
    fetch(`../api/get-post-content.php?id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('jobViewContent').innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">${data.post.title}</h4>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 mt-2">
                                <span><i class="fas fa-calendar mr-1"></i>${new Date(data.post.created_at).toLocaleDateString()}</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(data.post.status)}">${data.post.status.charAt(0).toUpperCase() + data.post.status.slice(1)}</span>
                            </div>
                        </div>
                        ${data.post.image_url ? `<img src="../${data.post.image_url}" alt="Job Image" class="w-full h-48 object-cover rounded-lg">` : ''}
                        <div class="prose max-w-none">
                            ${data.post.content}
                        </div>
                        ${data.post.status === 'rejected' && data.post.rejection_reason ? `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <h5 class="font-semibold text-red-800">Rejection Reason:</h5>
                                <p class="text-red-700 mt-1">${data.post.rejection_reason}</p>
                            </div>
                        ` : ''}
                    </div>
                `;
            } else {
                document.getElementById('jobViewContent').innerHTML = '<p class="text-red-600">Error loading job posting details.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('jobViewContent').innerHTML = '<p class="text-red-600">Error loading job posting details.</p>';
        });
}

function closeJobViewModal() {
    document.getElementById('jobViewModal').classList.add('hidden');
}

function getStatusColor(status) {
    const colors = {
        'draft': 'bg-gray-100 text-gray-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

// Close modal when clicking outside
document.getElementById('jobViewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeJobViewModal();
    }
});
</script>

