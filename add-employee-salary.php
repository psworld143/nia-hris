<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
require_once 'includes/roles.php';

// Check if user is logged in and can manage salary
if (!isset($_SESSION['user_id']) || !canManageSalary()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Add Employee Salary';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $basic_salary = isset($_POST['basic_salary']) && $_POST['basic_salary'] ? (float)$_POST['basic_salary'] : null;
    $salary_grade = sanitize_input($_POST['salary_grade'] ?? '');
    $step_increment = isset($_POST['step_increment']) ? (int)$_POST['step_increment'] : 1;
    $allowances = isset($_POST['allowances']) && $_POST['allowances'] ? (float)$_POST['allowances'] : 0.00;
    
    if ($employee_id <= 0) {
        $error = 'Please select an employee';
    } elseif ($basic_salary === null || $basic_salary <= 0) {
        $error = 'Please enter a valid salary amount';
    } else {
        // Check if employee exists
        $check_query = "SELECT id, first_name, last_name FROM employees WHERE id = ? AND is_active = 1";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $employee_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) === 0) {
            $error = 'Employee not found';
        } else {
            $employee = mysqli_fetch_assoc($check_result);
            
            // Insert or update salary in employee_details
            $salary_query = "INSERT INTO employee_details (
                employee_id, basic_salary, salary_grade, step_increment, allowances, updated_by, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                basic_salary = VALUES(basic_salary),
                salary_grade = VALUES(salary_grade),
                step_increment = VALUES(step_increment),
                allowances = VALUES(allowances),
                updated_by = VALUES(updated_by),
                updated_at = NOW()";
            
            $salary_stmt = mysqli_prepare($conn, $salary_query);
            mysqli_stmt_bind_param($salary_stmt, "idsidi", 
                $employee_id, 
                $basic_salary, 
                $salary_grade, 
                $step_increment, 
                $allowances, 
                $_SESSION['user_id']
            );
            
            if (mysqli_stmt_execute($salary_stmt)) {
                // Also insert into employee_salaries table for salary history
                $history_query = "INSERT INTO employee_salaries (
                    employee_id, base_salary, allowances, effective_date, created_by, created_at
                ) VALUES (?, ?, ?, CURDATE(), ?, NOW())
                ON DUPLICATE KEY UPDATE
                    base_salary = VALUES(base_salary),
                    allowances = VALUES(allowances),
                    effective_date = VALUES(effective_date),
                    updated_by = VALUES(created_by),
                    updated_at = NOW()";
                
                $history_stmt = mysqli_prepare($conn, $history_query);
                mysqli_stmt_bind_param($history_stmt, "iddi", 
                    $employee_id, 
                    $basic_salary, 
                    $allowances, 
                    $_SESSION['user_id']
                );
                mysqli_stmt_execute($history_stmt);
                
                // Log activity
                logActivity('add_salary', "Added salary ₱" . number_format($basic_salary, 2) . " for employee: " . $employee['first_name'] . " " . $employee['last_name'], $conn);
                
                $message = "Salary of ₱" . number_format($basic_salary, 2) . " added successfully for " . $employee['first_name'] . " " . $employee['last_name'];
                
                // Reset form
                $_POST = [];
            } else {
                $error = 'Error adding salary: ' . mysqli_error($conn);
            }
        }
    }
}

// Get all active employees with their current salary status
$employees_query = "SELECT 
    e.id,
    e.employee_id,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    COALESCE(ed.basic_salary, 0) as current_salary,
    ed.salary_grade,
    ed.step_increment
FROM employees e
LEFT JOIN employee_details ed ON e.id = ed.employee_id
WHERE e.is_active = 1
ORDER BY e.last_name, e.first_name";

$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-money-bill-wave mr-2"></i>Add Employee Salary
                </h2>
                <p class="opacity-90">Set initial salary for employees to enable auto-increment testing</p>
            </div>
            <a href="admin-employee.php" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Employees
            </a>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Salary Information</h3>
            
            <form method="POST" class="space-y-6">
                <!-- Employee Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-green-600"></i>Select Employee *
                    </label>
                    <select name="employee_id" id="employee_id" required 
                            class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
                            onchange="updateEmployeeInfo()">
                        <option value="">Choose Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                    data-salary="<?php echo $emp['current_salary']; ?>"
                                    data-grade="<?php echo htmlspecialchars($emp['salary_grade']); ?>"
                                    data-step="<?php echo $emp['step_increment']; ?>"
                                    data-name="<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>">
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                <?php if ($emp['current_salary'] > 0): ?>
                                    - Current: ₱<?php echo number_format($emp['current_salary'], 2); ?>
                                <?php else: ?>
                                    <span class="text-red-500"> - No Salary Set</span>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1" id="employee-info"></p>
                </div>
                
                <!-- Basic Salary -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>Basic Salary (Monthly) *
                    </label>
                    <input type="number" name="basic_salary" id="basic_salary" step="0.01" min="0" required
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
                           placeholder="Enter monthly basic salary">
                    <p class="text-xs text-gray-500 mt-1">Enter the employee's monthly basic salary in PHP</p>
                </div>
                
                <!-- Salary Grade -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-layer-group mr-2 text-green-600"></i>Salary Grade
                    </label>
                    <input type="text" name="salary_grade" id="salary_grade"
                           class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
                           placeholder="e.g., SG-11">
                    <p class="text-xs text-gray-500 mt-1">Optional: Government salary grade (e.g., SG-11, SG-18)</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Step Increment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-arrow-up mr-2 text-green-600"></i>Step Increment
                        </label>
                        <input type="number" name="step_increment" id="step_increment" min="1" max="8" value="1"
                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        <p class="text-xs text-gray-500 mt-1">Step level (1-8)</p>
                    </div>
                    
                    <!-- Allowances -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-coins mr-2 text-green-600"></i>Monthly Allowances
                        </label>
                        <input type="number" name="allowances" id="allowances" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
                        <p class="text-xs text-gray-500 mt-1">Additional monthly allowances</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="admin-employee.php" class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Add Salary
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-info-circle text-green-600 mr-2"></i>Information
            </h3>
            
            <div class="space-y-4 text-sm text-gray-600">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">Purpose</h4>
                    <p>Use this page to add or update employee salary information. This is required to test the auto-increment functionality.</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">What gets updated:</h4>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Employee Details (basic_salary)</li>
                        <li>Employee Salaries (history record)</li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-900 mb-2">After adding salary:</h4>
                    <p>You can then test auto-increment features in the Salary Incrementation section.</p>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-3">Quick Stats</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Employees:</span>
                        <span class="font-medium"><?php echo count($employees); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">With Salary:</span>
                        <span class="font-medium text-green-600">
                            <?php echo count(array_filter($employees, fn($e) => $e['current_salary'] > 0)); ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">No Salary:</span>
                        <span class="font-medium text-red-600">
                            <?php echo count(array_filter($employees, fn($e) => $e['current_salary'] <= 0)); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateEmployeeInfo() {
    const select = document.getElementById('employee_id');
    const selectedOption = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('employee-info');
    
    if (selectedOption.value) {
        const currentSalary = selectedOption.getAttribute('data-salary');
        const name = selectedOption.getAttribute('data-name');
        const grade = selectedOption.getAttribute('data-grade');
        const step = selectedOption.getAttribute('data-step');
        
        let info = '<strong>' + name + '</strong><br>';
        
        if (currentSalary && parseFloat(currentSalary) > 0) {
            info += 'Current Salary: <span class="text-green-600 font-semibold">₱' + parseFloat(currentSalary).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span><br>';
            info += '<span class="text-yellow-600 text-xs">(This will update the existing salary)</span>';
        } else {
            info += '<span class="text-red-600 font-semibold">No salary set yet</span>';
        }
        
        if (grade) {
            document.getElementById('salary_grade').value = grade;
        }
        if (step) {
            document.getElementById('step_increment').value = step;
        }
        
        infoDiv.innerHTML = info;
    } else {
        infoDiv.innerHTML = '';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

