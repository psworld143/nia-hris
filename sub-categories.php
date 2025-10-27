<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['human_resource', 'hr_manager'])) {
    header('Location: login.php');
    exit();
}

// Set page title
$page_title = 'Evaluation Sub-Categories';

$message = '';
$message_type = '';

// Get main_category_id from URL
$main_category_id = isset($_GET['main_category_id']) ? (int)$_GET['main_category_id'] : null;

if (!$main_category_id) {
    header('Location: evaluations.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_sub_category':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $order_number = (int)$_POST['order_number'];
                
                if (empty($name)) {
                    $message = 'Sub-category name is required.';
                    $message_type = 'error';
                } else {
                    try {
                        $insert_query = "INSERT INTO evaluation_sub_categories (main_category_id, name, description, order_number, status, created_by) 
                                        VALUES (?, ?, ?, ?, 'active', ?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_query);
                        $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
                        mysqli_stmt_bind_param($insert_stmt, "issii", $main_category_id, $name, $description, $order_number, $created_by);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $message = 'Sub-category "' . htmlspecialchars($name) . '" added successfully!';
                            $message_type = 'success';
                        } else {
                            throw new Exception("Insert failed: " . mysqli_error($conn));
                        }
                    } catch (Exception $e) {
                        $message = 'Error adding sub-category: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'edit_sub_category':
                $sub_category_id = (int)$_POST['sub_category_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $order_number = (int)$_POST['order_number'];
                
                if (empty($name)) {
                    $message = 'Sub-category name is required.';
                    $message_type = 'error';
                } else {
                    try {
                        $update_query = "UPDATE evaluation_sub_categories SET name = ?, description = ?, order_number = ? WHERE id = ? AND main_category_id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($update_stmt, "ssiii", $name, $description, $order_number, $sub_category_id, $main_category_id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            $message = 'Sub-category "' . htmlspecialchars($name) . '" updated successfully!';
                            $message_type = 'success';
                        } else {
                            throw new Exception("Update failed: " . mysqli_error($conn));
                        }
                    } catch (Exception $e) {
                        $message = 'Error updating sub-category: ' . $e->getMessage();
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete_sub_category':
                $sub_category_id = (int)$_POST['sub_category_id'];
                
                try {
                    mysqli_begin_transaction($conn);
                    
                    // Delete questionnaires first
                    $delete_questions_query = "DELETE FROM evaluation_questionnaires WHERE sub_category_id = ?";
                    $delete_questions_stmt = mysqli_prepare($conn, $delete_questions_query);
                    mysqli_stmt_bind_param($delete_questions_stmt, "i", $sub_category_id);
                    mysqli_stmt_execute($delete_questions_stmt);
                    
                    // Delete sub-category
                    $delete_sub_query = "DELETE FROM evaluation_sub_categories WHERE id = ? AND main_category_id = ?";
                    $delete_sub_stmt = mysqli_prepare($conn, $delete_sub_query);
                    mysqli_stmt_bind_param($delete_sub_stmt, "ii", $sub_category_id, $main_category_id);
                    
                    if (mysqli_stmt_execute($delete_sub_stmt)) {
                        mysqli_commit($conn);
                        $message = 'Sub-category and all its questions deleted successfully!';
                        $message_type = 'success';
                    } else {
                        throw new Exception("Delete failed: " . mysqli_error($conn));
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = 'Error deleting sub-category: ' . $e->getMessage();
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get main category details
$category_query = "SELECT * FROM main_evaluation_categories WHERE id = ? AND status = 'active' AND evaluation_type = 'peer_to_peer'";
$category_stmt = mysqli_prepare($conn, $category_query);
mysqli_stmt_bind_param($category_stmt, "i", $main_category_id);
mysqli_stmt_execute($category_stmt);
$category_result = mysqli_stmt_get_result($category_stmt);
$main_category = mysqli_fetch_assoc($category_result);

if (!$main_category) {
    header('Location: evaluations.php');
    exit();
}

// Get sub-categories for this main category
$sub_categories_query = "SELECT * FROM evaluation_sub_categories 
                        WHERE main_category_id = ? AND status = 'active' 
                        ORDER BY order_number ASC";
$sub_categories_stmt = mysqli_prepare($conn, $sub_categories_query);
mysqli_stmt_bind_param($sub_categories_stmt, "i", $main_category_id);
mysqli_stmt_execute($sub_categories_stmt);
$sub_categories_result = mysqli_stmt_get_result($sub_categories_stmt);
$sub_categories = [];
while ($row = mysqli_fetch_assoc($sub_categories_result)) {
    // Get questionnaire count for each sub-category
    $questionnaire_count_query = "SELECT COUNT(*) as count FROM evaluation_questionnaires 
                                 WHERE sub_category_id = ? AND status = 'active'";
    $count_stmt = mysqli_prepare($conn, $questionnaire_count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $row['id']);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_data = mysqli_fetch_assoc($count_result);
    
    $row['questionnaire_count'] = $count_data['count'];
    $sub_categories[] = $row;
}

// Include header
include 'includes/header.php';
?>

<div class="mb-6 sm:mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-seait-dark mb-2">Evaluation Structure</h1>
            <p class="text-sm sm:text-base text-gray-600">
                Category: <span class="font-medium text-purple-600"><?php echo htmlspecialchars($main_category['name']); ?></span>
            </p>
        </div>
        <a href="evaluations.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>
</div>

<!-- Main Category Info -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($main_category['name']); ?></h2>
            <span class="px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-800">
                <i class="fas fa-users mr-1"></i>Peer to Peer
            </span>
        </div>
    </div>
    
    <div class="p-6">
        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($main_category['description']); ?></p>
        
        <div class="flex space-x-6 text-sm text-gray-500">
            <span><i class="fas fa-layer-group mr-1"></i><?php echo count($sub_categories); ?> Sub-categories</span>
            <span><i class="fas fa-question-circle mr-1"></i><?php echo array_sum(array_column($sub_categories, 'questionnaire_count')); ?> Total Questions</span>
        </div>
    </div>
</div>

<!-- Message Display -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
    <div class="flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
</div>
<?php endif; ?>

<!-- Add Sub-Category Button -->
<?php if (in_array($_SESSION['role'], ['human_resource', 'hr_manager'])): ?>
<div class="mb-6">
    <button onclick="showAddSubCategoryModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
        <i class="fas fa-plus mr-2"></i>Add New Sub-Category
    </button>
</div>
<?php endif; ?>

<!-- Sub-Categories -->
<div class="space-y-6">
    <?php if (empty($sub_categories)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-layer-group text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500">No sub-categories found for this evaluation category.</p>
        </div>
    <?php else: ?>
        <?php foreach ($sub_categories as $index => $sub_category): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-purple-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center mr-3">
                            <span class="text-white font-bold text-sm"><?php echo $index + 1; ?></span>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($sub_category['name']); ?></h3>
                    </div>
                    <span class="px-3 py-1 text-sm rounded-full bg-purple-200 text-purple-800">
                        <?php echo $sub_category['questionnaire_count']; ?> questions
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($sub_category['description']); ?></p>
                
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        <span><i class="fas fa-sort-numeric-up mr-1"></i>Order: <?php echo $sub_category['order_number']; ?></span>
                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i>Created: <?php echo date('M d, Y', strtotime($sub_category['created_at'])); ?></span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <!-- View Questions Button -->
                        <a href="questionnaires.php?sub_category_id=<?php echo $sub_category['id']; ?>" 
                           class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm" 
                           title="View Questions">
                            <i class="fas fa-eye mr-1"></i>View Questions (<?php echo $sub_category['questionnaire_count']; ?>)
                        </a>
                        
                        <?php if (in_array($_SESSION['role'], ['human_resource', 'hr_manager'])): ?>
                        <!-- Edit Button -->
                        <button onclick="editSubCategory(<?php echo $sub_category['id']; ?>, '<?php echo addslashes($sub_category['name']); ?>', '<?php echo addslashes($sub_category['description']); ?>', <?php echo $sub_category['order_number']; ?>)" 
                                class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition text-sm" 
                                title="Edit Sub-Category">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <!-- Delete Button -->
                        <button onclick="deleteSubCategory(<?php echo $sub_category['id']; ?>, '<?php echo addslashes($sub_category['name']); ?>')" 
                                class="bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition text-sm" 
                                title="Delete Sub-Category">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($sub_category['questionnaire_count'] == 0): ?>
                <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="flex items-center justify-between text-yellow-800">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="text-sm">No questions available for this sub-category.</span>
                        </div>
                        <a href="questionnaires.php?sub_category_id=<?php echo $sub_category['id']; ?>" 
                           class="text-yellow-700 hover:text-yellow-900 text-sm underline">
                            Add Questions
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="mt-8 flex justify-center space-x-4">
    <a href="peer-evaluation-progress.php?main_category_id=<?php echo safe_encrypt_id($main_category_id); ?>" 
       class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
        <i class="fas fa-chart-line mr-2"></i>View Progress
    </a>
    <a href="conduct-evaluation.php?main_category_id=<?php echo $main_category_id; ?>" 
       class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
        <i class="fas fa-play mr-2"></i>Conduct Evaluation
    </a>
    <a href="evaluations.php" 
       class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
        <i class="fas fa-list mr-2"></i>Back to Categories
    </a>
</div>

<!-- Add Sub-Category Modal -->
<div id="addSubCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Add New Sub-Category</h3>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_sub_category">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sub-Category Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                        <input type="number" name="order_number" value="<?php echo count($sub_categories) + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideAddSubCategoryModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Add Sub-Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div id="editSubCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-edit text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Edit Sub-Category</h3>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="edit_sub_category">
                    <input type="hidden" name="sub_category_id" id="edit_sub_category_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sub-Category Name</label>
                        <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                        <input type="number" name="order_number" id="edit_order_number" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideEditSubCategoryModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            Update Sub-Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function showAddSubCategoryModal() {
    document.getElementById('addSubCategoryModal').classList.remove('hidden');
}

function hideAddSubCategoryModal() {
    document.getElementById('addSubCategoryModal').classList.add('hidden');
}

function showEditSubCategoryModal() {
    document.getElementById('editSubCategoryModal').classList.remove('hidden');
}

function hideEditSubCategoryModal() {
    document.getElementById('editSubCategoryModal').classList.add('hidden');
}

// Edit sub-category function
function editSubCategory(id, name, description, orderNumber) {
    document.getElementById('edit_sub_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_order_number').value = orderNumber;
    showEditSubCategoryModal();
}

// Delete sub-category function
function deleteSubCategory(id, name) {
    if (confirm('Are you sure you want to delete the sub-category "' + name + '"? This will also delete all questions associated with this sub-category. This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_sub_category">
            <input type="hidden" name="sub_category_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0')) {
        hideAddSubCategoryModal();
        hideEditSubCategoryModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideAddSubCategoryModal();
        hideEditSubCategoryModal();
    }
});
</script>

