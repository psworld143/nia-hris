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
$page_title = 'Salary Structures Management';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_structure':
                $position_title = $_POST['position_title'];
                $grade_level = $_POST['grade_level'];
                $base_salary = floatval($_POST['base_salary']);
                $minimum_salary = floatval($_POST['minimum_salary']);
                $maximum_salary = floatval($_POST['maximum_salary']);
                $increment_type = $_POST['increment_type'];
                $increment_percentage = ($increment_type === 'percentage') ? floatval($_POST['increment_percentage']) : null;
                $increment_amount = ($increment_type === 'fixed') ? floatval($_POST['increment_amount']) : null;
                $increment_frequency_years = intval($_POST['increment_frequency_years']);
                
                $query = "INSERT INTO salary_structures (position_title, grade_level, base_salary, minimum_salary, maximum_salary, increment_percentage, incrementation_amount, incrementation_frequency_years, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdddddii", $position_title, $grade_level, $base_salary, $minimum_salary, $maximum_salary, $increment_percentage, $increment_amount, $increment_frequency_years, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Salary structure created successfully!";
                } else {
                    $error_message = "Error creating salary structure: " . mysqli_error($conn);
                }
                break;
                
            case 'update_structure':
                $structure_id = intval($_POST['structure_id']);
                $position_title = $_POST['position_title'];
                $grade_level = $_POST['grade_level'];
                $base_salary = floatval($_POST['base_salary']);
                $minimum_salary = floatval($_POST['minimum_salary']);
                $maximum_salary = floatval($_POST['maximum_salary']);
                $increment_type = $_POST['increment_type'];
                $increment_percentage = ($increment_type === 'percentage') ? floatval($_POST['increment_percentage']) : null;
                $increment_amount = ($increment_type === 'fixed') ? floatval($_POST['increment_amount']) : null;
                $increment_frequency_years = intval($_POST['increment_frequency_years']);
                
                $query = "UPDATE salary_structures SET position_title = ?, grade_level = ?, base_salary = ?, minimum_salary = ?, maximum_salary = ?, increment_percentage = ?, incrementation_amount = ?, incrementation_frequency_years = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdddddii", $position_title, $grade_level, $base_salary, $minimum_salary, $maximum_salary, $increment_percentage, $increment_amount, $increment_frequency_years, $structure_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Salary structure updated successfully!";
                } else {
                    $error_message = "Error updating salary structure: " . mysqli_error($conn);
                }
                break;
                
            case 'delete_structure':
                $structure_id = intval($_POST['structure_id']);
                $query = "UPDATE salary_structures SET is_active = 0 WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $structure_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Salary structure deactivated successfully!";
                } else {
                    $error_message = "Error deactivating salary structure: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get salary structures
$structures_query = "SELECT ss.*, u.first_name, u.last_name 
                     FROM salary_structures ss 
                     LEFT JOIN users u ON ss.created_by = u.id 
                     WHERE ss.is_active = 1 
                     ORDER BY ss.grade_level, ss.position_title";
$structures_result = mysqli_query($conn, $structures_query);

// Check for query errors
if (!$structures_result) {
    die("Query failed: " . mysqli_error($conn));
}

$structures = [];
while ($row = mysqli_fetch_assoc($structures_result)) {
    $structures[] = $row;
}


// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Salary Structures Management</h2>
                <p class="opacity-90">Define and manage salary structures for different positions</p>
            </div>
            <div class="text-right">
                <button onclick="openAddStructureModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Structure
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-sitemap"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Structures</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($structures); ?></p>
                <p class="text-xs text-blue-600 mt-1">Active structures</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Positions</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_unique(array_column($structures, 'position_title'))); ?></p>
                <p class="text-xs text-green-600 mt-1">Different positions</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Grade Levels</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_unique(array_column($structures, 'grade_level'))); ?></p>
                <p class="text-xs text-purple-600 mt-1">Different grades</p>
            </div>
        </div>
    </div>
</div>

<!-- Salary Structures Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Salary Structures</h3>
        <div class="flex space-x-2">
            <button onclick="exportSalaryStructures()" class="px-4 py-2 text-sm bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                <i class="fas fa-download mr-1"></i>Export
            </button>
            <button onclick="printSalaryStructures()" class="px-4 py-2 text-sm bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-colors">
                <i class="fas fa-print mr-1"></i>Print
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Grade Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Base Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Salary Range</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Increment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($structures)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-sitemap text-4xl mb-4"></i>
                            <p>No salary structures found.</p>
                            <a href="seed-salary-structures.php" class="inline-block mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-seedling mr-2"></i>Load Philippine Salary Structures
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($structures as $structure): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($structure['position_title']); ?></div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo htmlspecialchars($structure['grade_level']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₱<?php echo number_format($structure['base_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    ₱<?php echo number_format($structure['minimum_salary'], 0); ?> - 
                                    ₱<?php echo number_format($structure['maximum_salary'], 0); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-green-600 font-medium">
                                    <?php if (!empty($structure['increment_percentage'])): ?>
                                        <?php echo $structure['increment_percentage']; ?>%
                                    <?php elseif (!empty($structure['incrementation_amount'])): ?>
                                        ₱<?php echo number_format($structure['incrementation_amount'], 2); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                    <?php echo $structure['incrementation_frequency_years']; ?> year<?php echo $structure['incrementation_frequency_years'] > 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="editStructure(<?php echo htmlspecialchars(json_encode($structure)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteStructure(<?php echo $structure['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                        <i class="fas fa-trash"></i>
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

<!-- Add/Edit Structure Modal -->
<div id="structureModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add New Salary Structure</h3>
                <button onclick="closeStructureModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="structureForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="add_structure">
                <input type="hidden" name="structure_id" id="structure_id" value="">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position Title</label>
                    <input type="text" name="position_title" id="position_title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Grade Level</label>
                        <input type="text" name="grade_level" id="grade_level" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                               placeholder="e.g., Grade 1, Level A">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Increment Type</label>
                        <select name="increment_type" id="increment_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" onchange="toggleIncrementFields()">
                            <option value="">Select Increment Type</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Base Salary</label>
                        <input type="number" name="base_salary" id="base_salary" step="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Salary</label>
                        <input type="number" name="minimum_salary" id="minimum_salary" step="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Salary</label>
                        <input type="number" name="maximum_salary" id="maximum_salary" step="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div id="increment_percentage_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Increment Percentage (%)</label>
                        <input type="number" name="increment_percentage" id="increment_percentage" step="0.01" min="0" max="50" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                               placeholder="e.g., 5">
                    </div>
                    
                    <div id="increment_amount_field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fixed Increment Amount (₱)</label>
                        <input type="number" name="increment_amount" id="increment_amount" step="0.01" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                               placeholder="e.g., 1000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Years)</label>
                        <input type="number" name="increment_frequency_years" id="increment_frequency_years" min="1" max="10" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                               placeholder="e.g., 3" value="3">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeStructureModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Save Structure
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// Modal functions
function openAddStructureModal() {
    document.getElementById('modalTitle').textContent = 'Add New Salary Structure';
    document.getElementById('formAction').value = 'add_structure';
    document.getElementById('structure_id').value = '';
    document.getElementById('structureForm').reset();
    document.getElementById('structureModal').classList.remove('hidden');
}

function closeStructureModal() {
    document.getElementById('structureModal').classList.add('hidden');
    document.getElementById('structureForm').reset();
    // Hide both increment fields when closing
    document.getElementById('increment_percentage_field').style.display = 'none';
    document.getElementById('increment_amount_field').style.display = 'none';
}

function toggleIncrementFields() {
    const incrementType = document.getElementById('increment_type').value;
    const percentageField = document.getElementById('increment_percentage_field');
    const amountField = document.getElementById('increment_amount_field');
    const percentageInput = document.getElementById('increment_percentage');
    const amountInput = document.getElementById('increment_amount');
    
    // Reset both fields
    percentageInput.value = '';
    amountInput.value = '';
    
    if (incrementType === 'percentage') {
        percentageField.style.display = 'block';
        amountField.style.display = 'none';
        percentageInput.required = true;
        amountInput.required = false;
    } else if (incrementType === 'fixed') {
        percentageField.style.display = 'none';
        amountField.style.display = 'block';
        percentageInput.required = false;
        amountInput.required = true;
    } else {
        percentageField.style.display = 'none';
        amountField.style.display = 'none';
        percentageInput.required = false;
        amountInput.required = false;
    }
}

function editStructure(structure) {
    document.getElementById('modalTitle').textContent = 'Edit Salary Structure';
    document.getElementById('formAction').value = 'update_structure';
    document.getElementById('structure_id').value = structure.id;
    document.getElementById('position_title').value = structure.position_title;
    document.getElementById('grade_level').value = structure.grade_level;
    document.getElementById('base_salary').value = structure.base_salary;
    document.getElementById('minimum_salary').value = structure.minimum_salary;
    document.getElementById('maximum_salary').value = structure.maximum_salary;
    document.getElementById('increment_frequency_years').value = structure.incrementation_frequency_years || 3;
    
    // Determine increment type and set values
    if (structure.increment_percentage && structure.increment_percentage > 0) {
        document.getElementById('increment_type').value = 'percentage';
        document.getElementById('increment_percentage').value = structure.increment_percentage;
        document.getElementById('increment_amount').value = '';
    } else if (structure.incrementation_amount && structure.incrementation_amount > 0) {
        document.getElementById('increment_type').value = 'fixed';
        document.getElementById('increment_amount').value = structure.incrementation_amount;
        document.getElementById('increment_percentage').value = '';
    }
    
    toggleIncrementFields();
    document.getElementById('structureModal').classList.remove('hidden');
}

function deleteStructure(structureId) {
    if (confirm('Are you sure you want to delete this salary structure? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_structure">
            <input type="hidden" name="structure_id" value="${structureId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-calculate minimum and maximum based on base salary
document.getElementById('base_salary').addEventListener('input', function() {
    const baseSalary = parseFloat(this.value) || 0;
    const minimum = baseSalary * 0.8; // 20% below base
    const maximum = baseSalary * 1.4; // 40% above base
    
    document.getElementById('minimum_salary').value = minimum.toFixed(2);
    document.getElementById('maximum_salary').value = maximum.toFixed(2);
});

// Auto-hide success/error messages
setTimeout(function() {
    const messages = document.querySelectorAll('.alert');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 5000);

// Export functionality
function exportSalaryStructures() {
    // Get table data
    const table = document.querySelector('.min-w-full');
    const rows = table.querySelectorAll('tr');
    
    // Create CSV content
    let csvContent = '';
    
    // Add headers
    const headerRow = rows[0];
    const headers = headerRow.querySelectorAll('th');
    const headerTexts = Array.from(headers).map(header => `"${header.textContent.trim()}"`);
    csvContent += headerTexts.join(',') + '\n';
    
    // Add data rows
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.querySelectorAll('td');
        const cellTexts = Array.from(cells).map(cell => {
            // Clean up the text content
            let text = cell.textContent.trim();
            // Remove action buttons text
            text = text.replace(/Edit|Delete/g, '').trim();
            return `"${text}"`;
        });
        csvContent += cellTexts.join(',') + '\n';
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `salary_structures_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print functionality
function printSalaryStructures() {
    // Get table data
    const table = document.querySelector('.min-w-full');
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Create print-friendly HTML
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Salary Structures Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .print-date { text-align: right; margin-bottom: 20px; color: #666; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>Salary Structures Report</h1>
            <div class="print-date">Generated on: ${new Date().toLocaleDateString()}</div>
            ${table.outerHTML}
        </body>
        </html>
    `;
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>

<style>
/* Custom styles for salary structures */
.structure-card {
    transition: all 0.3s ease;
}

.structure-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.grade-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
}

/* Animation for modal */
.modal-enter {
    animation: modalEnter 0.3s ease-out;
}

@keyframes modalEnter {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
