<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: index.php');
    exit();
}

$page_title = 'Create Increment Request';
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $employee_type = $_POST['employee_type'];
    $increment_type_id = intval($_POST['increment_type_id']);
    $current_salary = floatval($_POST['current_salary']);
    $requested_amount = floatval($_POST['requested_amount']);
    $effective_date = $_POST['effective_date'];
    $justification = trim($_POST['justification']);
    $priority = $_POST['priority'];
    
    // Calculate new salary and percentage
    $new_salary = $current_salary + $requested_amount;
    $requested_percentage = ($requested_amount / $current_salary) * 100;
    
    // Generate request number
    $year = date('Y');
    $sequence_query = "SELECT COALESCE(MAX(CAST(SUBSTRING(request_number, 6) AS UNSIGNED)), 0) + 1 as next_seq
                       FROM increment_requests WHERE request_number LIKE 'INC{$year}%'";
    $sequence_result = mysqli_query($conn, $sequence_query);
    $next_sequence = mysqli_fetch_assoc($sequence_result)['next_seq'];
    $request_number = 'INC' . $year . str_pad($next_sequence, 6, '0', STR_PAD_LEFT);
    
    // Get employee details
    if ($employee_type === 'employee') {
        $emp_query = "SELECT department, position FROM employees WHERE id = ?";
    } else {
        $emp_query = "SELECT department, position FROM employees WHERE id = ?";
    }
    $emp_stmt = mysqli_prepare($conn, $emp_query);
    mysqli_stmt_bind_param($emp_stmt, "i", $employee_id);
    mysqli_stmt_execute($emp_stmt);
    $emp_result = mysqli_stmt_get_result($emp_stmt);
    $emp_data = mysqli_fetch_assoc($emp_result);
    
    // Insert increment request
    $insert_query = "INSERT INTO increment_requests (
        request_number, employee_id, employee_type, increment_type_id, 
        current_salary, requested_amount, requested_percentage, new_salary, 
        effective_date, justification, priority, status, created_by, 
        department, position, fiscal_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?)";
    
    $fiscal_year = date('Y');
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "sisiddddsssissi", 
        $request_number, $employee_id, $employee_type, $increment_type_id,
        $current_salary, $requested_amount, $requested_percentage, $new_salary,
        $effective_date, $justification, $priority, $user_id,
        $emp_data['department'], $emp_data['position'], $fiscal_year
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $request_id = mysqli_insert_id($conn);
        
        // Log history
        $history_query = "INSERT INTO increment_request_history (request_id, action, new_status, performed_by)
                         VALUES (?, 'created', 'draft', ?)";
        $history_stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($history_stmt, "ii", $request_id, $user_id);
        mysqli_stmt_execute($history_stmt);
        
        $success_message = "Increment request created successfully! Request Number: " . $request_number;
    } else {
        $error_message = "Error creating increment request: " . mysqli_error($conn);
    }
}

// Get increment types
$types_query = "SELECT * FROM increment_types WHERE is_active = 1 ORDER BY type_name";
$types_result = mysqli_query($conn, $types_query);
$increment_types = mysqli_fetch_all($types_result, MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Increment Request</h1>
                <p class="text-gray-600">Submit a new salary increment request</p>
            </div>
            <a href="increment-requests.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to Requests
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-xl shadow-lg p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Employee Information</h3>
                
                <!-- Employee Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type</label>
                    <select name="employee_type" id="employeeType" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Employee Type</option>
                        <option value="employee">Staff Employee</option>
                        <option value="faculty">Faculty Member</option>
                    </select>
                </div>

                <!-- Employee Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select name="employee_id" id="employeeSelect" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Employee</option>
                    </select>
                </div>

                <!-- Current Salary -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Salary</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">₱</span>
                        <input type="number" name="current_salary" id="currentSalary" step="0.01" required
                               class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>

                <!-- Increment Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Increment Type</label>
                    <select name="increment_type_id" id="incrementType" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Increment Type</option>
                        <?php foreach ($increment_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    data-percentage="<?php echo $type['base_percentage']; ?>"
                                    data-min="<?php echo $type['min_amount']; ?>"
                                    data-max="<?php echo $type['max_amount']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                                <?php if ($type['base_percentage']): ?>
                                    (<?php echo $type['base_percentage']; ?>%)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Requested Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Requested Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">₱</span>
                        <input type="number" name="requested_amount" id="requestedAmount" step="0.01" required
                               class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <span id="percentageDisplay"></span>
                        <span id="newSalaryDisplay"></span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Request Details</h3>

                <!-- Effective Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date</label>
                    <input type="date" name="effective_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <select name="priority" required 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <!-- Justification -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Justification</label>
                    <textarea name="justification" rows="8" required
                              placeholder="Provide detailed justification for this increment request..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="mt-8 flex justify-end space-x-4">
            <button type="button" onclick="window.location.href='increment-requests.php'" 
                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit" name="action" value="save_draft"
                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Save as Draft
            </button>
            <button type="submit" name="action" value="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Create Request
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeType = document.getElementById('employeeType');
    const employeeSelect = document.getElementById('employeeSelect');
    const currentSalary = document.getElementById('currentSalary');
    const incrementType = document.getElementById('incrementType');
    const requestedAmount = document.getElementById('requestedAmount');
    
    // Load employees when type changes
    employeeType.addEventListener('change', function() {
        loadEmployees(this.value);
    });
    
    // Load salary when employee changes
    employeeSelect.addEventListener('change', function() {
        if (this.value) {
            loadEmployeeSalary(employeeType.value, this.value);
        }
    });
    
    // Calculate percentage and new salary
    function updateCalculations() {
        const current = parseFloat(currentSalary.value) || 0;
        const requested = parseFloat(requestedAmount.value) || 0;
        
        if (current > 0 && requested > 0) {
            const percentage = (requested / current * 100).toFixed(2);
            const newSalary = current + requested;
            
            document.getElementById('percentageDisplay').textContent = `${percentage}% increase | `;
            document.getElementById('newSalaryDisplay').textContent = `New Salary: ₱${newSalary.toLocaleString()}`;
        } else {
            document.getElementById('percentageDisplay').textContent = '';
            document.getElementById('newSalaryDisplay').textContent = '';
        }
    }
    
    currentSalary.addEventListener('input', updateCalculations);
    requestedAmount.addEventListener('input', updateCalculations);
    
    // Auto-calculate based on increment type
    incrementType.addEventListener('change', function() {
        const option = this.selectedOptions[0];
        const percentage = parseFloat(option.dataset.percentage);
        const current = parseFloat(currentSalary.value);
        
        if (percentage && current) {
            const suggested = current * (percentage / 100);
            requestedAmount.value = suggested.toFixed(2);
            updateCalculations();
        }
    });
});

function loadEmployees(type) {
    const select = document.getElementById('employeeSelect');
    select.innerHTML = '<option value="">Loading...</option>';
    
    if (!type) {
        select.innerHTML = '<option value="">Select Employee</option>';
        return;
    }
    
    fetch(`api/get-employees.php?type=${type}`)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '<option value="">Select Employee</option>';
            data.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.textContent = `${emp.name} - ${emp.department}`;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading employees:', error);
            select.innerHTML = '<option value="">Error loading employees</option>';
        });
}

function loadEmployeeSalary(type, id) {
    fetch(`api/get-employee-salary.php?type=${type}&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.salary) {
                document.getElementById('currentSalary').value = data.salary;
                updateCalculations();
            }
        })
        .catch(error => {
            console.error('Error loading salary:', error);
        });
}
</script>

