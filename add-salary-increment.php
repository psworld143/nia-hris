<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Add Salary Increment';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $incrementation_name = trim($_POST['incrementation_name']);
    $incrementation_description = trim($_POST['incrementation_description']);
    $incrementation_amount = floatval($_POST['incrementation_amount']);
    $frequency_years = intval($_POST['frequency_years']);
    $current_salary = floatval($_POST['current_salary']);
    $effective_date = $_POST['effective_date'];
    $reason = trim($_POST['reason']);
    
    // Calculate new salary
    $new_salary = $current_salary + $incrementation_amount;
    $increment_percentage = ($incrementation_amount / $current_salary) * 100;
    
    // Get employee's salary structure
    $structure_query = "SELECT id FROM salary_structures WHERE position_title = (SELECT position FROM employees WHERE id = ?) LIMIT 1";
    $structure_stmt = mysqli_prepare($conn, $structure_query);
    mysqli_stmt_bind_param($structure_stmt, "i", $employee_id);
    mysqli_stmt_execute($structure_stmt);
    $structure_result = mysqli_stmt_get_result($structure_stmt);
    $salary_structure_id = mysqli_fetch_assoc($structure_result)['id'] ?? 1;
    
    // Insert salary increment
    $query = "INSERT INTO salary_increments (
        employee_id, 
        salary_structure_id, 
        current_salary, 
        increment_amount, 
        new_salary, 
        increment_percentage, 
        increment_type, 
        incrementation_name,
        incrementation_description,
        incrementation_amount,
        incrementation_frequency_years,
        effective_date, 
        reason, 
        created_by
    ) VALUES (?, ?, ?, ?, ?, ?, 'regular', ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiddddsssissi", 
        $employee_id, 
        $salary_structure_id, 
        $current_salary, 
        $incrementation_amount, 
        $new_salary, 
        $increment_percentage,
        $incrementation_name,
        $incrementation_description,
        $incrementation_amount,
        $frequency_years,
        $effective_date, 
        $reason, 
        $user_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $increment_id = mysqli_insert_id($conn);
        
        // Add to history
        $history_query = "INSERT INTO increment_history (
            employee_id, 
            increment_id, 
            old_salary, 
            new_salary, 
            increment_amount, 
            increment_percentage, 
            effective_date, 
            action, 
            action_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'created', ?)";
        
        $history_stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($history_stmt, "iiddddsi", 
            $employee_id, 
            $increment_id, 
            $current_salary, 
            $new_salary, 
            $incrementation_amount, 
            $increment_percentage, 
            $effective_date, 
            $user_id
        );
        mysqli_stmt_execute($history_stmt);
        
        $success_message = "Salary increment created successfully! Increment ID: #" . $increment_id;
    } else {
        $error_message = "Error creating salary increment: " . mysqli_error($conn);
    }
}

// Get employees for dropdown
$employees_query = "SELECT id, employee_id, first_name, last_name, position, department, employee_type FROM employees WHERE is_active = 1 ORDER BY first_name, last_name";
$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

// Get incrementation templates
$templates_query = "SELECT * FROM incrementation_templates WHERE is_active = 1 ORDER BY template_name";
$templates_result = mysqli_query($conn, $templates_query);
$templates = [];
while ($row = mysqli_fetch_assoc($templates_result)) {
    $templates[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Add Salary Increment</h2>
                <p class="opacity-90">Create new salary increment with specific incrementation criteria</p>
            </div>
            <div class="text-right">
                <a href="salary-incrementation.php" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Increments
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success_message; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Form -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Incrementation Details</h3>
            
            <form method="POST" class="space-y-6">
                <!-- Employee Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee *</label>
                        <select name="employee_id" id="employee_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">Choose Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" 
                                        data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                        data-department="<?php echo htmlspecialchars($employee['department']); ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Salary *</label>
                        <input type="number" name="current_salary" id="current_salary" step="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter current salary">
                    </div>
                </div>
                
                <!-- Incrementation Criteria -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-md font-medium text-gray-900 mb-4">Incrementation Criteria</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Incrementation Name *</label>
                            <input type="text" name="incrementation_name" id="incrementation_name" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                   placeholder="e.g., Annual Performance Increment">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">After Every (Years) *</label>
                            <select name="frequency_years" id="frequency_years" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="1">1 Year</option>
                                <option value="2">2 Years</option>
                                <option value="3">3 Years</option>
                                <option value="5">5 Years</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea name="incrementation_description" id="incrementation_description" rows="3" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                  placeholder="Describe the incrementation criteria and conditions..."></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Incrementation Amount *</label>
                        <input type="number" name="incrementation_amount" id="incrementation_amount" step="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter incrementation amount">
                    </div>
                </div>
                
                <!-- Additional Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
                        <input type="date" name="effective_date" id="effective_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <input type="text" name="reason" id="reason" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Reason for this increment">
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="salary-incrementation.php" 
                       class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-plus mr-2"></i>Create Increment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Section -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Increment Preview</h3>
            
            <div class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Employee Information</h4>
                    <div id="employee-preview" class="text-sm text-gray-600">
                        Select an employee to see details
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Salary Calculation</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Salary:</span>
                            <span id="current-salary-preview" class="font-medium">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Increment Amount:</span>
                            <span id="increment-amount-preview" class="font-medium text-green-600">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">New Salary:</span>
                            <span id="new-salary-preview" class="font-medium text-blue-600">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Increment %:</span>
                            <span id="increment-percentage-preview" class="font-medium">0.00%</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Incrementation Details</h4>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-600">Name:</span>
                            <div id="incrementation-name-preview" class="font-medium">-</div>
                        </div>
                        <div>
                            <span class="text-gray-600">Frequency:</span>
                            <div id="frequency-preview" class="font-medium">-</div>
                        </div>
                        <div>
                            <span class="text-gray-600">Description:</span>
                            <div id="description-preview" class="font-medium text-gray-500">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Templates -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Templates</h3>
            <div class="space-y-2">
                <?php foreach ($templates as $template): ?>
                    <button type="button" onclick="applyTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)" 
                            class="w-full text-left p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="font-medium text-sm"><?php echo htmlspecialchars($template['template_name']); ?></div>
                        <div class="text-xs text-gray-500">₱<?php echo number_format($template['incrementation_amount'], 2); ?> every <?php echo $template['frequency_years']; ?> year(s)</div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>


<script>
// Set default effective date to next month
document.addEventListener('DOMContentLoaded', function() {
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('effective_date').value = nextMonth.toISOString().split('T')[0];
});

// Employee selection handler
document.getElementById('employee_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const position = selectedOption.getAttribute('data-position');
        const department = selectedOption.getAttribute('data-department');
        const employeeName = selectedOption.textContent;
        
        document.getElementById('employee-preview').innerHTML = `
            <div class="font-medium">${employeeName}</div>
            <div class="text-xs text-gray-500">${position}</div>
            <div class="text-xs text-gray-500">${department}</div>
        `;
    } else {
        document.getElementById('employee-preview').innerHTML = 'Select an employee to see details';
    }
});

// Real-time calculation
function updatePreview() {
    const currentSalary = parseFloat(document.getElementById('current_salary').value) || 0;
    const incrementAmount = parseFloat(document.getElementById('incrementation_amount').value) || 0;
    const newSalary = currentSalary + incrementAmount;
    const incrementPercentage = currentSalary > 0 ? (incrementAmount / currentSalary) * 100 : 0;
    
    document.getElementById('current-salary-preview').textContent = '₱' + currentSalary.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('increment-amount-preview').textContent = '₱' + incrementAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('new-salary-preview').textContent = '₱' + newSalary.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('increment-percentage-preview').textContent = incrementPercentage.toFixed(2) + '%';
    
    // Update incrementation details
    document.getElementById('incrementation-name-preview').textContent = document.getElementById('incrementation_name').value || '-';
    document.getElementById('frequency-preview').textContent = document.getElementById('frequency_years').value + ' year(s)';
    document.getElementById('description-preview').textContent = document.getElementById('incrementation_description').value || '-';
}

// Add event listeners for real-time updates
document.getElementById('current_salary').addEventListener('input', updatePreview);
document.getElementById('incrementation_amount').addEventListener('input', updatePreview);
document.getElementById('incrementation_name').addEventListener('input', updatePreview);
document.getElementById('frequency_years').addEventListener('change', updatePreview);
document.getElementById('incrementation_description').addEventListener('input', updatePreview);

// Apply template function
function applyTemplate(template) {
    document.getElementById('incrementation_name').value = template.template_name;
    document.getElementById('incrementation_description').value = template.description;
    document.getElementById('incrementation_amount').value = template.incrementation_amount;
    document.getElementById('frequency_years').value = template.frequency_years;
    
    updatePreview();
    
    // Show success message
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-green-600"></i> Applied';
    button.classList.add('bg-green-50', 'border-green-200');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('bg-green-50', 'border-green-200');
    }, 2000);
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = ['employee_id', 'current_salary', 'incrementation_name', 'incrementation_description', 'incrementation_amount', 'effective_date'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});

// Auto-hide success/error messages
setTimeout(function() {
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 5000);
</script>

<style>
/* Custom styles for the increment form */
.increment-form {
    transition: all 0.3s ease;
}

.preview-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
}

.template-button {
    transition: all 0.2s ease;
}

.template-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Animation for form validation */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.border-red-500 {
    animation: shake 0.5s ease-in-out;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .lg\\:col-span-2 {
        grid-column: span 1;
    }
    
    .lg\\:col-span-1 {
        grid-column: span 1;
    }
}
</style>
