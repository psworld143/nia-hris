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
$page_title = 'Enhanced Salary Increment';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submission
$success_message = '';
$error_message = '';
$validation_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_enhanced_increment') {
        $employee_id = intval($_POST['employee_id']);
        $increment_amount = floatval($_POST['increment_amount']);
        $increment_reason = trim($_POST['increment_reason']);
        $effective_date = $_POST['effective_date'];
        
        // Validate increment
        $validation_result = validateIncrement($conn, $employee_id, $increment_amount);
        
        if ($validation_result['valid']) {
            // Get current salary
            $current_salary_query = "SELECT basic_salary FROM employee_details WHERE employee_id = ?";
            $current_stmt = mysqli_prepare($conn, $current_salary_query);
            mysqli_stmt_bind_param($current_stmt, "i", $employee_id);
            mysqli_stmt_execute($current_stmt);
            $current_result = mysqli_stmt_get_result($current_stmt);
            $current_salary = mysqli_fetch_assoc($current_result)['basic_salary'] ?? 0;
            
            $new_salary = $current_salary + $increment_amount;
            $increment_percentage = $current_salary > 0 ? ($increment_amount / $current_salary) * 100 : 0;
            
            // Get employee's salary structure
            $structure_query = "SELECT id FROM salary_structures WHERE position_title = (SELECT position FROM employees WHERE id = ?) LIMIT 1";
            $structure_stmt = mysqli_prepare($conn, $structure_query);
            mysqli_stmt_bind_param($structure_stmt, "i", $employee_id);
            mysqli_stmt_execute($structure_stmt);
            $structure_result = mysqli_stmt_get_result($structure_stmt);
            $salary_structure_id = mysqli_fetch_assoc($structure_result)['id'] ?? 1;
            
            // Insert salary increment with enhanced validation
            $query = "INSERT INTO salary_increments (
                employee_id, 
                salary_structure_id, 
                current_salary, 
                increment_amount, 
                new_salary, 
                increment_percentage, 
                increment_type, 
                effective_date, 
                reason, 
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 'regular', ?, ?, 'pending', ?)";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iidddsssi", 
                $employee_id, 
                $salary_structure_id, 
                $current_salary, 
                $increment_amount, 
                $new_salary, 
                $increment_percentage,
                $effective_date, 
                $increment_reason, 
                $user_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                $increment_id = mysqli_insert_id($conn);
                
                // Log the increment creation
                $log_query = "INSERT INTO salary_change_monitor (
                    employee_id, 
                    field_changed, 
                    old_value, 
                    new_value, 
                    change_reason, 
                    changed_by,
                    change_source, 
                    is_authorized, 
                    authorization_reference
                ) VALUES (?, 'salary_increment', ?, ?, ?, ?, 'manual', 1, ?)";
                
                $log_stmt = mysqli_prepare($conn, $log_query);
                $reference = "INCREMENT_" . $increment_id;
                mysqli_stmt_bind_param($log_stmt, "issis", 
                    $employee_id, 
                    $current_salary, 
                    $new_salary, 
                    $increment_reason, 
                    $user_id, 
                    $reference
                );
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Salary increment created successfully! Increment ID: #" . $increment_id . ". Awaiting approval.";
            } else {
                $error_message = "Error creating salary increment: " . mysqli_error($conn);
            }
        } else {
            $validation_errors = $validation_result['errors'];
        }
    }
}

// Function to validate increment
function validateIncrement($conn, $employee_id, $increment_amount) {
    $errors = [];
    
    // Check if employee exists and has hire date
    $employee_query = "SELECT e.hire_date, e.position, e.department, ed.basic_salary 
                       FROM employees e 
                       LEFT JOIN employee_details ed ON e.id = ed.employee_id 
                       WHERE e.id = ?";
    $employee_stmt = mysqli_prepare($conn, $employee_query);
    mysqli_stmt_bind_param($employee_stmt, "i", $employee_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    $employee_data = mysqli_fetch_assoc($employee_result);
    
    if (!$employee_data) {
        $errors[] = "Employee not found";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check years of service (3-year rule)
    if ($employee_data['hire_date']) {
        $hire_date = new DateTime($employee_data['hire_date']);
        $current_date = new DateTime();
        $years_of_service = $current_date->diff($hire_date)->y;
        
        if ($years_of_service < 3) {
            $errors[] = "Employee has not completed 3 years of service (Current: {$years_of_service} years)";
        }
    } else {
        $errors[] = "Employee hire date not set";
    }
    
    // Check increment amount (1000 limit)
    if ($increment_amount > 1000) {
        $errors[] = "Increment amount exceeds maximum limit of 1000 (Current: {$increment_amount})";
    }
    
    if ($increment_amount <= 0) {
        $errors[] = "Increment amount must be greater than 0";
    }
    
    // Check for recent increments (within 3 years)
    $recent_increment_query = "SELECT created_at FROM salary_increments 
                              WHERE employee_id = ? AND status = 'approved' 
                              ORDER BY created_at DESC LIMIT 1";
    $recent_stmt = mysqli_prepare($conn, $recent_increment_query);
    mysqli_stmt_bind_param($recent_stmt, "i", $employee_id);
    mysqli_stmt_execute($recent_stmt);
    $recent_result = mysqli_stmt_get_result($recent_stmt);
    $recent_data = mysqli_fetch_assoc($recent_result);
    
    if ($recent_data) {
        $last_increment = new DateTime($recent_data['created_at']);
        $time_since_increment = $current_date->diff($last_increment)->y;
        
        if ($time_since_increment < 3) {
            $errors[] = "Last increment was less than 3 years ago (Last increment: {$time_since_increment} years ago)";
        }
    }
    
    // Check if employee has basic salary set
    if (!$employee_data['basic_salary'] || $employee_data['basic_salary'] <= 0) {
        $errors[] = "Employee basic salary not set or invalid";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'employee_data' => $employee_data
    ];
}

// Get employees for dropdown
$employees_query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.position, e.department, 
                           ed.basic_salary, e.hire_date
                    FROM employees e 
                    LEFT JOIN employee_details ed ON e.id = ed.employee_id 
                    WHERE e.is_active = 1 
                    ORDER BY e.first_name, e.last_name";
$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

// Get validation rules
$rules_query = "SELECT * FROM increment_validation_rules WHERE is_active = 1 ORDER BY minimum_years_of_service";
$rules_result = mysqli_query($conn, $rules_query);
$validation_rules = [];
while ($row = mysqli_fetch_assoc($rules_result)) {
    $validation_rules[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Enhanced Salary Increment</h2>
                <p class="opacity-90">Create salary increments with comprehensive validation</p>
            </div>
            <div class="text-right">
                <a href="salary-audit-system.php" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-shield-alt mr-2"></i>Audit System
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

<?php if (!empty($validation_errors)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-times-circle mr-2"></i>
            <div>
                <strong>Validation Errors:</strong>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($validation_errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Create Salary Increment</h3>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="create_enhanced_increment">
                
                <!-- Employee Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Employee *</label>
                    <select name="employee_id" id="employee_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Choose Employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>" 
                                    data-salary="<?php echo $employee['basic_salary'] ?? 0; ?>"
                                    data-hire-date="<?php echo $employee['hire_date'] ?? ''; ?>"
                                    data-position="<?php echo htmlspecialchars($employee['position']); ?>">
                                <?php echo $employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ') - ' . $employee['position']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Current Salary Display -->
                <div id="current-salary-display" class="hidden bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Employee Information</h4>
                    <div id="employee-info" class="text-sm text-gray-600"></div>
                </div>
                
                <!-- Increment Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Increment Amount *</label>
                    <input type="number" name="increment_amount" id="increment_amount" step="0.01" min="0" max="1000" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                           placeholder="Maximum: 1000">
                    <p class="text-xs text-gray-500 mt-1">Maximum increment amount is 1000</p>
                </div>
                
                <!-- Effective Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date *</label>
                    <input type="date" name="effective_date" id="effective_date" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Increment *</label>
                    <textarea name="increment_reason" id="increment_reason" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="Explain the reason for this salary increment..."></textarea>
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
    
    <!-- Validation Rules Section -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Validation Rules</h3>
            
            <div class="space-y-4">
                <?php foreach ($validation_rules as $rule): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-2"><?php echo $rule['rule_name']; ?></h4>
                        <p class="text-xs text-gray-600 mb-2"><?php echo $rule['rule_description']; ?></p>
                        <div class="text-xs text-gray-500">
                            <div>Minimum Years: <?php echo $rule['minimum_years_of_service']; ?></div>
                            <div>Max Amount: ₱<?php echo number_format($rule['maximum_increment_amount'], 2); ?></div>
                            <div>Frequency: <?php echo $rule['increment_frequency_years']; ?> years</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Increment Preview -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Increment Preview</h3>
            
            <div class="space-y-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Salary Calculation</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Salary:</span>
                            <span id="preview-current-salary" class="font-medium">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Increment Amount:</span>
                            <span id="preview-increment" class="font-medium text-green-600">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">New Salary:</span>
                            <span id="preview-new-salary" class="font-medium text-blue-600">₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Increment %:</span>
                            <span id="preview-percentage" class="font-medium">0.00%</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Validation Status</h4>
                    <div id="validation-status" class="text-sm">
                        <div class="text-gray-500">Select an employee to validate</div>
                    </div>
                </div>
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
    const currentSalaryDisplay = document.getElementById('current-salary-display');
    const employeeInfo = document.getElementById('employee-info');
    
    if (selectedOption.value) {
        const salary = selectedOption.getAttribute('data-salary') || 0;
        const hireDate = selectedOption.getAttribute('data-hire-date');
        const position = selectedOption.getAttribute('data-position');
        const employeeName = selectedOption.textContent;
        
        currentSalaryDisplay.classList.remove('hidden');
        employeeInfo.innerHTML = `
            <div class="font-medium">${employeeName}</div>
            <div class="text-xs text-gray-500">Position: ${position}</div>
            <div class="text-xs text-gray-500">Current Salary: ₱${parseFloat(salary).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            ${hireDate ? `<div class="text-xs text-gray-500">Hire Date: ${hireDate}</div>` : ''}
        `;
        
        updatePreview();
        validateEmployee(selectedOption.value);
    } else {
        currentSalaryDisplay.classList.add('hidden');
        document.getElementById('validation-status').innerHTML = '<div class="text-gray-500">Select an employee to validate</div>';
    }
});

// Real-time calculation
function updatePreview() {
    const currentSalary = parseFloat(document.getElementById('employee_id').selectedOptions[0]?.getAttribute('data-salary')) || 0;
    const incrementAmount = parseFloat(document.getElementById('increment_amount').value) || 0;
    const newSalary = currentSalary + incrementAmount;
    const incrementPercentage = currentSalary > 0 ? (incrementAmount / currentSalary) * 100 : 0;
    
    document.getElementById('preview-current-salary').textContent = '₱' + currentSalary.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('preview-increment').textContent = '₱' + incrementAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('preview-new-salary').textContent = '₱' + newSalary.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('preview-percentage').textContent = incrementPercentage.toFixed(2) + '%';
}

// Add event listeners for real-time updates
document.getElementById('increment_amount').addEventListener('input', updatePreview);

// Validate employee function
function validateEmployee(employeeId) {
    // This would typically make an AJAX call to validate the employee
    // For now, we'll show a placeholder
    const validationStatus = document.getElementById('validation-status');
    validationStatus.innerHTML = '<div class="text-blue-600">Validation in progress...</div>';
    
    // Simulate validation (replace with actual AJAX call)
    setTimeout(() => {
        validationStatus.innerHTML = '<div class="text-green-600">✓ Employee validated</div>';
    }, 1000);
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = ['employee_id', 'increment_amount', 'effective_date', 'increment_reason'];
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
/* Custom styles for enhanced increment form */
.increment-form {
    transition: all 0.3s ease;
}

.validation-rule {
    transition: all 0.2s ease;
}

.validation-rule:hover {
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
</style>
