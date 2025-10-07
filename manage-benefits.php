<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Check if benefit_types table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'benefit_types'");
if (mysqli_num_rows($table_check) == 0) {
    header('Location: setup-benefits-system.php');
    exit();
}

// Get all benefit types
$benefits_query = "SELECT * FROM benefit_types ORDER BY category, benefit_name";
$benefits_result = mysqli_query($conn, $benefits_query);

$page_title = 'Manage Benefits';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-gift text-blue-600 mr-2"></i>Benefit Management System
            </h1>
            <p class="text-gray-600 mt-1">Manage all employee benefits, deductions, and contribution rates</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openAddBenefitModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-medium shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add New Benefit Type
            </button>
            <a href="government-benefits.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <?php
    $stats = [
        'mandatory' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM benefit_types WHERE category='mandatory' AND is_active=1"))['count'],
        'optional' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM benefit_types WHERE category='optional' AND is_active=1"))['count'],
        'loan' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM benefit_types WHERE category='loan' AND is_active=1"))['count'],
        'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM benefit_types WHERE is_active=1"))['count']
    ];
    ?>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Mandatory</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $stats['mandatory']; ?></h3>
            </div>
            <div class="bg-blue-400 bg-opacity-30 rounded-full p-4">
                <i class="fas fa-shield-alt text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-medium">Optional</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $stats['optional']; ?></h3>
            </div>
            <div class="bg-green-400 bg-opacity-30 rounded-full p-4">
                <i class="fas fa-plus-circle text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm font-medium">Loans</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $stats['loan']; ?></h3>
            </div>
            <div class="bg-yellow-400 bg-opacity-30 rounded-full p-4">
                <i class="fas fa-hand-holding-usd text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm font-medium">Total Active</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $stats['total']; ?></h3>
            </div>
            <div class="bg-purple-400 bg-opacity-30 rounded-full p-4">
                <i class="fas fa-list text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Benefit Types Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 border-b border-blue-800">
        <h2 class="text-xl font-semibold text-white flex items-center">
            <i class="fas fa-list-ul mr-3"></i>All Benefit Types
        </h2>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Benefit Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Benefit Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employer Share</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($benefit = mysqli_fetch_assoc($benefits_result)): 
                    $category_colors = [
                        'mandatory' => 'blue',
                        'optional' => 'green',
                        'loan' => 'yellow',
                        'other' => 'gray'
                    ];
                    $color = $category_colors[$benefit['category']];
                    
                    $calc_type_icons = [
                        'fixed' => 'fa-dollar-sign',
                        'percentage' => 'fa-percent',
                        'table' => 'fa-table'
                    ];
                    $icon = $calc_type_icons[$benefit['calculation_type']];
                ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($benefit['benefit_code']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($benefit['benefit_name']); ?></div>
                            <?php if ($benefit['description']): ?>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($benefit['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                <?php echo ucfirst($benefit['category']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-700">
                                <i class="fas <?php echo $icon; ?> mr-2 text-<?php echo $color; ?>-600"></i>
                                <?php echo ucfirst($benefit['calculation_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($benefit['calculation_type'] === 'table'): ?>
                                <button onclick="viewRateTable(<?php echo $benefit['id']; ?>, '<?php echo htmlspecialchars($benefit['benefit_name']); ?>')" 
                                        class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    <i class="fas fa-table mr-1"></i>View Table
                                </button>
                            <?php elseif ($benefit['calculation_type'] === 'percentage'): ?>
                                <span class="text-sm font-semibold text-gray-900"><?php echo number_format($benefit['default_rate'], 2); ?>%</span>
                            <?php else: ?>
                                <span class="text-sm font-semibold text-gray-900">₱<?php echo number_format($benefit['default_rate'], 2); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($benefit['has_employer_share']): ?>
                                <i class="fas fa-check-circle text-green-600" title="Has employer share"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-gray-400" title="No employer share"></i>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($benefit['is_active']): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button onclick="editBenefit(<?php echo htmlspecialchars(json_encode($benefit)); ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleStatus(<?php echo $benefit['id']; ?>, <?php echo $benefit['is_active'] ? 'false' : 'true'; ?>)" 
                                    class="text-<?php echo $benefit['is_active'] ? 'yellow' : 'green'; ?>-600 hover:text-<?php echo $benefit['is_active'] ? 'yellow' : 'green'; ?>-900 mr-3" 
                                    title="<?php echo $benefit['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $benefit['is_active'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                            </button>
                            <?php if ($benefit['calculation_type'] === 'table'): ?>
                                <button onclick="manageRates(<?php echo $benefit['id']; ?>, '<?php echo htmlspecialchars($benefit['benefit_name']); ?>')" 
                                        class="text-purple-600 hover:text-purple-900" title="Manage Rates">
                                    <i class="fas fa-cog"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Benefit Modal -->
<div id="benefitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New Benefit Type</h3>
                <button onclick="closeBenefitModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="benefitForm" onsubmit="saveBenefit(event)">
                <input type="hidden" name="benefit_id" id="benefit_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Benefit Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="benefit_code" id="benefit_code" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase"
                               placeholder="e.g., SSS, LOAN01">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Benefit Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="benefit_name" id="benefit_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., Social Security System">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select name="category" id="category" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="mandatory">Mandatory (Government-required)</option>
                            <option value="optional">Optional (Company benefits)</option>
                            <option value="loan">Loan/Advance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Calculation Type <span class="text-red-500">*</span>
                        </label>
                        <select name="calculation_type" id="calculation_type" required onchange="updateRateField()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="fixed">Fixed Amount (₱)</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="table">Salary-based Table</option>
                        </select>
                    </div>
                </div>
                
                <div id="rateField" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Default Rate <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="default_rate" id="default_rate" step="0.01" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Enter amount or percentage">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="has_employer_share" id="has_employer_share" class="mr-2">
                        <span class="text-sm text-gray-700">Has Employer Share (split contribution)</span>
                    </label>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Brief description of this benefit"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" onclick="closeBenefitModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>Save Benefit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddBenefitModal() {
    document.getElementById('modalTitle').textContent = 'Add New Benefit Type';
    document.getElementById('benefitForm').reset();
    document.getElementById('benefit_id').value = '';
    document.getElementById('benefitModal').classList.remove('hidden');
    updateRateField();
}

function editBenefit(benefit) {
    document.getElementById('modalTitle').textContent = 'Edit Benefit Type';
    document.getElementById('benefit_id').value = benefit.id;
    document.getElementById('benefit_code').value = benefit.benefit_code;
    document.getElementById('benefit_name').value = benefit.benefit_name;
    document.getElementById('category').value = benefit.category;
    document.getElementById('calculation_type').value = benefit.calculation_type;
    document.getElementById('default_rate').value = benefit.default_rate;
    document.getElementById('has_employer_share').checked = benefit.has_employer_share == 1;
    document.getElementById('description').value = benefit.description || '';
    document.getElementById('benefitModal').classList.remove('hidden');
    updateRateField();
}

function closeBenefitModal() {
    document.getElementById('benefitModal').classList.add('hidden');
}

function updateRateField() {
    const calcType = document.getElementById('calculation_type').value;
    const rateField = document.getElementById('rateField');
    const defaultRate = document.getElementById('default_rate');
    
    if (calcType === 'table') {
        rateField.classList.add('hidden');
        defaultRate.required = false;
    } else {
        rateField.classList.remove('hidden');
        defaultRate.required = true;
        if (calcType === 'percentage') {
            defaultRate.placeholder = 'e.g., 5 for 5%';
        } else {
            defaultRate.placeholder = 'e.g., 200 for ₱200';
        }
    }
}

function saveBenefit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('save-benefit-type.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Error saving benefit: ' + error);
    });
}

function toggleStatus(id, activate) {
    if (confirm('Are you sure you want to ' + (activate ? 'activate' : 'deactivate') + ' this benefit?')) {
        fetch('toggle-benefit-status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `benefit_id=${id}&activate=${activate}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function viewRateTable(id, name) {
    window.location.href = `manage-benefit-rates-table.php?benefit_id=${id}&name=${encodeURIComponent(name)}`;
}

function manageRates(id, name) {
    window.location.href = `manage-benefit-rates-table.php?benefit_id=${id}&name=${encodeURIComponent(name)}`;
}
</script>

<?php include 'includes/footer.php'; ?>

