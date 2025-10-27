<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Regularization Criteria Management';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';

// Handle AJAX requests
if ((php_sapi_name() === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $stmt = mysqli_prepare($conn, "INSERT INTO regularization_criteria (
                    criteria_name, criteria_description, minimum_months,
                    performance_rating_min, attendance_percentage_min, disciplinary_issues_max,
                    training_completion_required, evaluation_score_min, additional_requirements,
                    created_by, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $training_required = isset($_POST['training_completion_required']) ? 1 : 0;
                $is_active = 1;
                
                $criteria_name = $_POST['criteria_name'];
                $criteria_description = $_POST['criteria_description'];
                $minimum_months = intval($_POST['minimum_months']);
                $performance_rating_min = floatval($_POST['performance_rating_min']);
                $attendance_percentage_min = floatval($_POST['attendance_percentage_min']);
                $disciplinary_issues_max = intval($_POST['disciplinary_issues_max']);
                $evaluation_score_min = floatval($_POST['evaluation_score_min']);
                $additional_requirements = $_POST['additional_requirements'];
                
                mysqli_stmt_bind_param($stmt, "ssiiddiiisi", 
                    $criteria_name,
                    $criteria_description,
                    $minimum_months,
                    $performance_rating_min,
                    $attendance_percentage_min,
                    $disciplinary_issues_max,
                    $training_required,
                    $evaluation_score_min,
                    $additional_requirements,
                    $user_id,
                    $is_active
                );
                
                mysqli_stmt_execute($stmt);
                
                echo json_encode(['success' => true, 'message' => 'Criteria created successfully!']);
                break;
                
            case 'update':
                $stmt = mysqli_prepare($conn, "UPDATE regularization_criteria SET 
                    criteria_name = ?, criteria_description = ?, minimum_months = ?,
                    performance_rating_min = ?, attendance_percentage_min = ?, disciplinary_issues_max = ?,
                    training_completion_required = ?, evaluation_score_min = ?, additional_requirements = ?,
                    updated_by = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?");
                
                $training_required = isset($_POST['training_completion_required']) ? 1 : 0;
                
                $criteria_name = $_POST['criteria_name'];
                $criteria_description = $_POST['criteria_description'];
                $minimum_months = intval($_POST['minimum_months']);
                $performance_rating_min = floatval($_POST['performance_rating_min']);
                $attendance_percentage_min = floatval($_POST['attendance_percentage_min']);
                $disciplinary_issues_max = intval($_POST['disciplinary_issues_max']);
                $evaluation_score_min = floatval($_POST['evaluation_score_min']);
                $additional_requirements = $_POST['additional_requirements'];
                $criteria_id = intval($_POST['id']);
                
                mysqli_stmt_bind_param($stmt, "ssiiddiisii", 
                    $criteria_name,
                    $criteria_description,
                    $minimum_months,
                    $performance_rating_min,
                    $attendance_percentage_min,
                    $disciplinary_issues_max,
                    $training_required,
                    $evaluation_score_min,
                    $additional_requirements,
                    $user_id,
                    $criteria_id
                );
                
                mysqli_stmt_execute($stmt);
                
                echo json_encode(['success' => true, 'message' => 'Criteria updated successfully!']);
                break;
                
            case 'delete':
                $stmt = mysqli_prepare($conn, "DELETE FROM regularization_criteria WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
                mysqli_stmt_execute($stmt);
                
                echo json_encode(['success' => true, 'message' => 'Criteria deleted successfully!']);
                break;
                
            case 'toggle_status':
                $stmt = mysqli_prepare($conn, "UPDATE regularization_criteria SET is_active = NOT is_active, updated_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $_POST['id']);
                mysqli_stmt_execute($stmt);
                
                echo json_encode(['success' => true, 'message' => 'Status updated successfully!']);
                break;
                
            case 'get_criteria':
                $stmt = mysqli_prepare($conn, "SELECT * FROM regularization_criteria WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $criteria = mysqli_fetch_assoc($result);
                
                echo json_encode(['success' => true, 'data' => $criteria]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get all criteria
$result = mysqli_query($conn, "SELECT * FROM regularization_criteria ORDER BY criteria_name");
$all_criteria = mysqli_fetch_all($result, MYSQLI_ASSOC);

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-clipboard-check mr-2"></i>Regularization Criteria Management
                </h2>
                <p class="opacity-90">Manage criteria for employee regularization process</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <button onclick="openAddModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Criteria
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-list-check text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Criteria</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($all_criteria); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Employee Criteria</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($all_criteria); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Active Criteria</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($all_criteria, function($c) { return $c['is_active'] == 1; })); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Criteria Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Regularization Criteria List</h3>
        <span class="text-sm text-gray-500"><?php echo count($all_criteria); ?> criteria</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Criteria Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Min Months</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Performance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Attendance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($all_criteria)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-clipboard-list text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No regularization criteria found</p>
                            <p class="text-sm text-gray-500 mt-1">Create your first criteria to get started.</p>
                            <button onclick="openAddModal()" class="mt-4 bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>Add Criteria
                            </button>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($all_criteria as $criteria): ?>
                <tr class="hover:bg-green-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($criteria['criteria_name']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($criteria['criteria_description'], 0, 50)) . (strlen($criteria['criteria_description']) > 50 ? '...' : ''); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-user-tie mr-1"></i>Employee
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?php echo $criteria['minimum_months']; ?> months
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-star mr-1"></i><?php echo $criteria['performance_rating_min']; ?>/5.0
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <?php echo $criteria['attendance_percentage_min']; ?>%
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $criteria['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <i class="fas <?php echo $criteria['is_active'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                            <?php echo $criteria['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-3">
                            <button onclick="viewCriteria(<?php echo $criteria['id']; ?>)" class="text-green-600 hover:text-green-900" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="editCriteria(<?php echo $criteria['id']; ?>)" class="text-blue-600 hover:text-blue-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleStatus(<?php echo $criteria['id']; ?>, <?php echo $criteria['is_active'] ? 'false' : 'true'; ?>)" class="text-<?php echo $criteria['is_active'] ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $criteria['is_active'] ? 'yellow' : 'green'; ?>-900" title="<?php echo $criteria['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas <?php echo $criteria['is_active'] ? 'fa-pause-circle' : 'fa-play-circle'; ?>"></i>
                            </button>
                            <button onclick="deleteCriteria(<?php echo $criteria['id']; ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="criteriaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-xl bg-white mb-10" id="criteriaModalContent">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-900">
                    <i class="fas fa-plus-circle text-green-600 mr-2"></i>
                    <span>Add New Criteria</span>
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="criteriaForm" class="space-y-5">
                <input type="hidden" id="criteriaId" name="id">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" id="formAction" name="action" value="create">
                <input type="hidden" name="employee_type" value="employee">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Criteria Name -->
                    <div class="md:col-span-2">
                        <label for="criteria_name" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tag text-green-600 mr-2"></i>Criteria Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="criteria_name" name="criteria_name" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="Enter criteria name">
                    </div>
                    
                    <!-- Minimum Months -->
                    <div>
                        <label for="minimum_months" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt text-green-600 mr-2"></i>Minimum Months <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="minimum_months" name="minimum_months" min="0" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0">
                    </div>
                    
                    <!-- Performance Rating -->
                    <div>
                        <label for="performance_rating_min" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-star text-green-600 mr-2"></i>Min Performance <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="performance_rating_min" name="performance_rating_min" min="0" max="5" step="0.1" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0.0">
                    </div>
                    
                    <!-- Attendance Percentage -->
                    <div>
                        <label for="attendance_percentage_min" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-percentage text-green-600 mr-2"></i>Min Attendance % <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="attendance_percentage_min" name="attendance_percentage_min" min="0" max="100" step="0.01" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0.00">
                    </div>
                    
                    <!-- Disciplinary Issues -->
                    <div>
                        <label for="disciplinary_issues_max" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-exclamation-triangle text-green-600 mr-2"></i>Max Disciplinary
                        </label>
                        <input type="number" id="disciplinary_issues_max" name="disciplinary_issues_max" min="0" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0">
                    </div>
                    
                    <!-- Evaluation Score -->
                    <div>
                        <label for="evaluation_score_min" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-chart-line text-green-600 mr-2"></i>Min Evaluation Score
                        </label>
                        <input type="number" id="evaluation_score_min" name="evaluation_score_min" min="0" max="100" step="0.01" 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0.00">
                    </div>
                    
                    <!-- Training Completion -->
                    <div class="md:col-span-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="training_completion_required" name="training_completion_required" 
                                   class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 cursor-pointer">
                            <span class="ml-3 text-sm font-semibold text-gray-700">
                                <i class="fas fa-graduation-cap text-green-600 mr-2"></i>Training Completion Required
                            </span>
                        </label>
                    </div>
                </div>
                
                <!-- Description -->
                <div>
                    <label for="criteria_description" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left text-green-600 mr-2"></i>Description
                    </label>
                    <textarea id="criteria_description" name="criteria_description" rows="3" 
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none" 
                              placeholder="Enter criteria description..."></textarea>
                </div>
                
                <!-- Additional Requirements -->
                <div>
                    <label for="additional_requirements" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-list-check text-green-600 mr-2"></i>Additional Requirements
                    </label>
                    <textarea id="additional_requirements" name="additional_requirements" rows="3" 
                              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none" 
                              placeholder="Enter any additional requirements..."></textarea>
                </div>
                
                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" 
                            class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        <span id="submitText">Add Criteria</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-xl bg-white mb-10" id="viewModalContent">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-info-circle text-green-600 mr-2"></i>Criteria Details
                </h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div id="viewContent" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end pt-6 border-t border-gray-200">
                <button onclick="closeViewModal()" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-1/2 transform -translate-y-1/2 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-xl bg-white" id="confirmModalContent">
        <div class="text-center">
            <div id="confirmIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4">
                <!-- Icon will be populated by JavaScript -->
            </div>
            <h3 id="confirmTitle" class="text-xl font-bold text-gray-900 mb-2">
                <!-- Title will be populated by JavaScript -->
            </h3>
            <p id="confirmMessage" class="text-gray-600 mb-6">
                <!-- Message will be populated by JavaScript -->
            </p>
            <div class="flex justify-center gap-3">
                <button id="confirmCancelBtn" onclick="closeConfirmModal()" 
                        class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmActionBtn" class="px-6 py-2 text-sm font-medium rounded-lg transition-colors">
                    <!-- Button content will be populated by JavaScript -->
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Functions
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function hideModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Modal Functions
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle text-green-600 mr-2"></i><span>Add New Criteria</span>';
    document.getElementById('submitText').textContent = 'Add Criteria';
    document.getElementById('formAction').value = 'create';
    document.getElementById('criteriaForm').reset();
    document.getElementById('criteriaId').value = '';
    showModal('criteriaModal');
}

function editCriteria(id) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=get_criteria&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const criteria = data.data;
            
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-green-600 mr-2"></i><span>Edit Criteria</span>';
            document.getElementById('submitText').textContent = 'Update Criteria';
            document.getElementById('formAction').value = 'update';
            document.getElementById('criteriaId').value = criteria.id;
            document.getElementById('criteria_name').value = criteria.criteria_name;
            document.getElementById('criteria_description').value = criteria.criteria_description || '';
            document.getElementById('minimum_months').value = criteria.minimum_months;
            document.getElementById('performance_rating_min').value = criteria.performance_rating_min;
            document.getElementById('attendance_percentage_min').value = criteria.attendance_percentage_min;
            document.getElementById('disciplinary_issues_max').value = criteria.disciplinary_issues_max;
            document.getElementById('evaluation_score_min').value = criteria.evaluation_score_min;
            document.getElementById('additional_requirements').value = criteria.additional_requirements || '';
            document.getElementById('training_completion_required').checked = criteria.training_completion_required == 1;
            
            showModal('criteriaModal');
        } else {
            showNotification(data.message, 'error');
        }
    });
}

function viewCriteria(id) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=get_criteria&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const criteria = data.data;
            
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-tag text-blue-500 mr-2"></i>Criteria Name
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.criteria_name}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-user-tie text-purple-500 mr-2"></i>Employee Type
                        </label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-user-tie mr-2"></i>Employee
                        </span>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-teal-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-calendar-alt text-green-500 mr-2"></i>Minimum Months
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.minimum_months} months</p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>Performance Rating
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.performance_rating_min}/5.0</p>
                    </div>
                    <div class="bg-gradient-to-br from-cyan-50 to-blue-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-percentage text-cyan-500 mr-2"></i>Attendance Percentage
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.attendance_percentage_min}%</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-pink-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Max Disciplinary Issues
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.disciplinary_issues_max}</p>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-chart-line text-indigo-500 mr-2"></i>Evaluation Score
                        </label>
                        <p class="text-lg font-bold text-gray-900">${criteria.evaluation_score_min}</p>
                    </div>
                    <div class="bg-gradient-to-br from-teal-50 to-green-50 p-4 rounded-lg ">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                            <i class="fas fa-graduation-cap text-green-500 mr-2"></i>Training Required
                        </label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${criteria.training_completion_required == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            <i class="fas ${criteria.training_completion_required == 1 ? 'fa-check' : 'fa-times'} mr-2"></i>
                            ${criteria.training_completion_required == 1 ? 'Yes' : 'No'}
                        </span>
                    </div>
                </div>
                ${criteria.criteria_description ? `
                <div class="bg-gradient-to-br from-gray-50 to-slate-50 p-4 rounded-lg  mt-6">
                    <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                        <i class="fas fa-align-left text-gray-500 mr-2"></i>Description
                    </label>
                    <p class="text-gray-900 leading-relaxed">${criteria.criteria_description}</p>
                </div>
                ` : ''}
                ${criteria.additional_requirements ? `
                <div class="bg-gradient-to-br from-amber-50 to-yellow-50 p-4 rounded-lg  mt-6">
                    <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                        <i class="fas fa-list-check text-amber-500 mr-2"></i>Additional Requirements
                    </label>
                    <p class="text-gray-900 leading-relaxed">${criteria.additional_requirements}</p>
                </div>
                ` : ''}
                <div class="bg-gradient-to-br from-${criteria.is_active == 1 ? 'green' : 'red'}-50 to-${criteria.is_active == 1 ? 'emerald' : 'rose'}-50 p-4 rounded-lg border-l-4 border-${criteria.is_active == 1 ? 'green' : 'red'}-500 mt-6">
                    <label class="block text-xs font-semibold text-gray-600 mb-2 uppercase tracking-wide flex items-center">
                        <i class="fas fa-toggle-${criteria.is_active == 1 ? 'on' : 'off'} text-${criteria.is_active == 1 ? 'green' : 'red'}-500 mr-2"></i>Status
                    </label>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${criteria.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        <i class="fas ${criteria.is_active == 1 ? 'fa-check-circle' : 'fa-times-circle'} mr-2"></i>
                        ${criteria.is_active == 1 ? 'Active' : 'Inactive'}
                    </span>
                </div>
            `;
            
            document.getElementById('viewContent').innerHTML = content;
            showModal('viewModal');
        } else {
            showNotification(data.message, 'error');
        }
    });
}

function closeModal() {
    hideModal('criteriaModal');
}

function closeViewModal() {
    hideModal('viewModal');
}

function closeConfirmModal() {
    hideModal('confirmModal');
}

// Beautiful Confirmation Modal
function showConfirmModal(config) {
    const { title, message, icon, iconColor, confirmText, confirmColor, onConfirm } = config;
    
    // Set icon
    document.getElementById('confirmIcon').innerHTML = `<i class="fas ${icon} text-4xl text-${iconColor}-500"></i>`;
    document.getElementById('confirmIcon').className = `mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-${iconColor}-100 mb-4`;
    
    // Set title and message
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    
    // Set confirm button
    const confirmBtn = document.getElementById('confirmActionBtn');
    confirmBtn.innerHTML = `<i class="fas ${icon} mr-2"></i>${confirmText}`;
    confirmBtn.className = `px-6 py-3 bg-gradient-to-r from-${confirmColor}-500 to-${confirmColor}-600 text-white rounded-lg hover:from-${confirmColor}-600 hover:to-${confirmColor}-700 transition-all duration-200 font-semibold flex items-center shadow-md hover:shadow-lg transform hover:scale-105`;
    
    // Set onclick handler
    confirmBtn.onclick = () => {
        closeConfirmModal();
        onConfirm();
    };
    
    showModal('confirmModal');
}

function toggleStatus(id, currentStatus) {
    const action = currentStatus === 'true' ? 'Deactivate' : 'Activate';
    const isDeactivating = currentStatus === 'true';
    
    showConfirmModal({
        title: `${action} Criteria`,
        message: `Are you sure you want to ${action.toLowerCase()} this criteria?`,
        icon: isDeactivating ? 'fa-pause-circle' : 'fa-play-circle',
        iconColor: isDeactivating ? 'yellow' : 'green',
        confirmText: action,
        confirmColor: isDeactivating ? 'yellow' : 'green',
        onConfirm: () => {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=toggle_status&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }
    });
}

function deleteCriteria(id) {
    showConfirmModal({
        title: 'Delete Criteria',
        message: 'Are you sure you want to delete this criteria? This action cannot be undone.',
        icon: 'fa-trash-alt',
        iconColor: 'red',
        confirmText: 'Delete',
        confirmColor: 'red',
        onConfirm: () => {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=delete&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }
    });
}

// Form submission
document.getElementById('criteriaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const submitText = document.getElementById('submitText');
    const originalText = submitText.textContent;
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            submitText.innerHTML = '<i class="fas fa-check mr-2"></i>Success!';
            showNotification(data.message, 'success');
            setTimeout(() => {
                closeModal();
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
            submitBtn.disabled = false;
            submitText.textContent = originalText;
        }
    })
    .catch(error => {
        showNotification('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitText.textContent = originalText;
    });
});

// Notification function
function showNotification(message, type) {
    $.jGrowl(message, {
        header: type === 'success' ? 'Success' : 'Error',
        theme: type === 'success' ? 'jGrowl-success' : 'jGrowl-error',
        life: 5000,
        position: 'top-right'
    });
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const criteriaModal = document.getElementById('criteriaModal');
    const viewModal = document.getElementById('viewModal');
    const confirmModal = document.getElementById('confirmModal');
    
    if (event.target === criteriaModal) {
        closeModal();
    }
    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === confirmModal) {
        closeConfirmModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const criteriaModal = document.getElementById('criteriaModal');
        const viewModal = document.getElementById('viewModal');
        const confirmModal = document.getElementById('confirmModal');
        
        if (!criteriaModal.classList.contains('hidden')) {
            closeModal();
        } else if (!viewModal.classList.contains('hidden')) {
            closeViewModal();
        } else if (!confirmModal.classList.contains('hidden')) {
            closeConfirmModal();
        }
    }
});
</script>
