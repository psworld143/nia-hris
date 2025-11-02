<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Get all deduction types
$deductions_query = "SELECT * FROM payroll_deduction_types ORDER BY sort_order, name";
$deductions_result = mysqli_query($conn, $deductions_query);

$page_title = 'Manage Payroll Deductions';
include 'includes/header.php';
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-md">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3 text-xl"></i>
            <p class="font-medium"><?php echo $_SESSION['success_message']; ?></p>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-md">
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
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-minus-circle text-red-600 mr-2"></i>Manage Payroll Deductions
            </h1>
            <p class="text-gray-600">Configure deduction types that appear in payroll processing</p>
        </div>
        <div class="flex space-x-3">
            <a href="payroll-management.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Payroll
            </a>
            <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors font-medium">
                <i class="fas fa-plus mr-2"></i>Add Deduction Type
            </button>
        </div>
    </div>
</div>

<!-- Deductions Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($deduction = mysqli_fetch_assoc($deductions_result)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $deduction['sort_order']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($deduction['code']); ?></code>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($deduction['name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($deduction['description'] ?? ''); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php 
                                $cat_colors = [
                                    'government' => 'bg-indigo-100 text-indigo-800',
                                    'mandatory' => 'bg-blue-100 text-blue-800',
                                    'loan' => 'bg-yellow-100 text-yellow-800',
                                    'attendance' => 'bg-orange-100 text-orange-800',
                                    'other' => 'bg-gray-100 text-gray-800'
                                ];
                                echo $cat_colors[$deduction['category']] ?? 'bg-gray-100 text-gray-800';
                                ?>">
                                <?php echo ucfirst($deduction['category']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $deduction['is_percentage'] ? 'Percentage' : 'Fixed Amount'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            echo $deduction['is_percentage'] 
                                ? number_format($deduction['default_value'], 2) . '%' 
                                : '₱' . number_format($deduction['default_value'], 2); 
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($deduction['is_active']): ?>
                                <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex justify-center space-x-2">
                                <button onclick="editDeduction(<?php echo htmlspecialchars(json_encode($deduction)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                    <i class="fas fa-edit text-lg"></i>
                                </button>
                                <button onclick="toggleStatus(<?php echo $deduction['id']; ?>, <?php echo $deduction['is_active'] ? 'false' : 'true'; ?>)" 
                                        class="<?php echo $deduction['is_active'] ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900'; ?> transition-colors" 
                                        title="<?php echo $deduction['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $deduction['is_active'] ? 'ban' : 'check-circle'; ?> text-lg"></i>
                                </button>
                                <button onclick="confirmDelete(<?php echo $deduction['id']; ?>, '<?php echo htmlspecialchars($deduction['name']); ?>', '<?php echo htmlspecialchars($deduction['code']); ?>')" 
                                        class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                    <i class="fas fa-trash-alt text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
    <div class="relative w-full max-w-md mx-4">
        <div class="bg-white rounded-2xl shadow-2xl transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white">Delete Deduction Type</h3>
                            <p class="text-red-100 text-sm">This action cannot be undone</p>
                        </div>
                    </div>
                    <button onclick="closeDeleteModal()" class="text-white hover:text-red-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="p-6">
                <div class="flex items-start mb-6">
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-trash-alt text-red-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Are you sure?</h4>
                        <p class="text-gray-600 mb-4">
                            You are about to permanently delete the deduction type:
                        </p>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900" id="deleteDeductionName"></p>
                                    <p class="text-sm text-gray-500" id="deleteDeductionCode"></p>
                                </div>
                                <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Permanent
                                </span>
                            </div>
                        </div>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Warning:</strong> This will permanently remove this deduction type from the system. 
                                        Any payroll records using this deduction may be affected.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-2xl flex justify-end space-x-3">
                <button onclick="closeDeleteModal()" 
                        class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium transition-all transform hover:scale-105">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button onclick="deleteDeduction()" 
                        id="confirmDeleteBtn"
                        class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 font-medium shadow-lg transition-all transform hover:scale-105">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="deductionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">Add Deduction Type</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form id="deductionForm" method="POST" action="save-deduction-type.php">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="deduction_id" id="deductionId">
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code <span class="text-red-500">*</span></label>
                        <input type="text" name="code" id="code" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500"
                               placeholder="e.g., SSS">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                        <input type="number" name="sort_order" id="sortOrder" min="0"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500"
                           placeholder="e.g., SSS Contribution">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" id="category"
                            class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500">
                        <option value="government">Government (GSIS)</option>
                        <option value="mandatory">Mandatory (SSS, PhilHealth, Pag-IBIG)</option>
                        <option value="loan">Loan</option>
                        <option value="attendance">Attendance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_percentage" id="isPercentage" value="1"
                                   class="w-4 h-4 text-green-600 rounded">
                            <span class="ml-2 text-sm font-medium text-gray-700">Is Percentage?</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Value</label>
                        <input type="number" name="default_value" id="defaultValue" step="0.01" min="0"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:border-green-500">
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="isActive" value="1" checked
                               class="w-4 h-4 text-green-600 rounded">
                        <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal()" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    <i class="fas fa-save mr-2"></i><span id="submitText">Add Deduction</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Deduction Type';
    document.getElementById('formAction').value = 'add';
    document.getElementById('submitText').textContent = 'Add Deduction';
    document.getElementById('deductionForm').reset();
    document.getElementById('deductionId').value = '';
    document.getElementById('isActive').checked = true;
    document.getElementById('deductionModal').classList.remove('hidden');
}

function editDeduction(deduction) {
    document.getElementById('modalTitle').textContent = 'Edit Deduction Type';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('submitText').textContent = 'Update Deduction';
    document.getElementById('deductionId').value = deduction.id;
    document.getElementById('code').value = deduction.code;
    document.getElementById('name').value = deduction.name;
    document.getElementById('description').value = deduction.description || '';
    document.getElementById('category').value = deduction.category;
    document.getElementById('isPercentage').checked = deduction.is_percentage == 1;
    document.getElementById('defaultValue').value = deduction.default_value;
    document.getElementById('sortOrder').value = deduction.sort_order;
    document.getElementById('isActive').checked = deduction.is_active == 1;
    document.getElementById('deductionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('deductionModal').classList.add('hidden');
}

function toggleStatus(id, activate) {
    const action = activate ? 'activate' : 'deactivate';
    
    if (confirm(`Are you sure you want to ${action} this deduction type?`)) {
        fetch('toggle-deduction-status.php', {
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
        });
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Delete confirmation variables
let deleteDeductionId = null;
let deleteDeductionName = '';
let deleteDeductionCode = '';

function confirmDelete(id, name, code) {
    deleteDeductionId = id;
    deleteDeductionName = name;
    deleteDeductionCode = code;
    
    document.getElementById('deleteDeductionName').textContent = name;
    document.getElementById('deleteDeductionCode').textContent = `Code: ${code}`;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    deleteDeductionId = null;
    deleteDeductionName = '';
    deleteDeductionCode = '';
}

function deleteDeduction() {
    if (!deleteDeductionId) {
        showToast('Error: No deduction selected', 'error');
        return;
    }
    
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalHTML = deleteBtn.innerHTML;
    
    // Show loading state
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    
    // Create form data
    const formData = new URLSearchParams();
    formData.append('id', deleteDeductionId);
    
    fetch('delete-deduction-type.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeDeleteModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to delete deduction type', 'error');
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalHTML;
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('deductionModal');
    const deleteModal = document.getElementById('deleteConfirmModal');
    
    if (event.target == modal) {
        closeModal();
    }
    
    if (event.target == deleteModal) {
        closeDeleteModal();
    }
}
</script>

