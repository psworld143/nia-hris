<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Set page title
$page_title = 'Add New Employee - Comprehensive Form';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This will be handled by add-employee-comprehensive.php via AJAX
    // But we can also handle it here for direct form submission
    header('Location: add-employee-comprehensive.php');
    exit();
}

// Get departments for dropdown
$departments_query = "SELECT name FROM departments WHERE is_active = 1 ORDER BY sort_order, name";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
if ($departments_result) {
    while ($row = mysqli_fetch_assoc($departments_result)) {
        $departments[] = $row['name'];
    }
}

// Get salary structures for dropdown
$salary_structures_query = "SELECT * FROM salary_structures WHERE is_active = 1 ORDER BY position_title, grade_level";
$salary_structures_result = mysqli_query($conn, $salary_structures_query);
$salary_structures = [];
if ($salary_structures_result) {
    while ($row = mysqli_fetch_assoc($salary_structures_result)) {
        $salary_structures[] = $row;
    }
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add New Employee</h1>
            <p class="text-gray-600">Complete employee information form with comprehensive HR details</p>
        </div>
        <div class="flex space-x-3">
            <a href="admin-employee.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Employees
            </a>
        </div>
    </div>
</div>

<!-- Progress Indicator -->
<div class="mb-6 bg-white rounded-xl shadow-lg p-4">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Form Completion Progress</span>
        <span id="formProgress" class="text-sm font-bold text-green-600">0%</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div id="progressBar" class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
    </div>
</div>

<!-- Success/Error Messages -->
<div id="messageContainer"></div>

<!-- Comprehensive Employee Form -->
<form id="comprehensiveEmployeeForm" class="space-y-8" enctype="multipart/form-data">
    <!-- Personal Information Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user text-blue-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Personal Information</h3>
                <p class="text-gray-600 text-sm">Basic personal details</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter first name">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                <input type="text" name="middle_name"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter middle name">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="last_name" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter last name">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                <input type="date" name="date_of_birth"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                <select name="gender" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                <select name="civil_status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Separated">Separated</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter email address">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" name="phone" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter phone number">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type</label>
                <select name="blood_type" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Blood Type</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>
            
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Complete Address</label>
                <textarea name="address" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="Enter complete address"></textarea>
            </div>
        </div>
    </div>

    <!-- Employment Information Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-briefcase text-purple-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Employment Information</h3>
                <p class="text-gray-600 text-sm">Job and employment details</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID <span class="text-red-500">*</span></label>
                <div class="flex space-x-2">
                    <input type="text" name="employee_id" id="employee_id_input" required
                           class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                           placeholder="YYYY-XXXX (e.g., 2025-0001)" pattern="\d{4}-\d{4}">
                    <button type="button" onclick="generateEmployeeID()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-magic mr-1"></i>Auto
                    </button>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position <span class="text-red-500">*</span></label>
                <select name="position" id="position_select" required
                        class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                        onchange="filterSalaryStructures()">
                    <option value="">Select Position</option>
                    <?php 
                    $unique_positions = array_unique(array_column($salary_structures, 'position_title'));
                    foreach ($unique_positions as $position): ?>
                        <option value="<?php echo htmlspecialchars($position); ?>"><?php echo htmlspecialchars($position); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">Custom Position (Manual Entry)</option>
                </select>
                <input type="text" name="position_custom" id="position_custom" 
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all mt-2 hidden"
                       placeholder="Enter custom position">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Hire <span class="text-red-500">*</span></label>
                <input type="date" name="hire_date" id="hire_date" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       onchange="calculateStepIncrement()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department <span class="text-red-500">*</span></label>
                <select name="department" required class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type <span class="text-red-500">*</span></label>
                <select name="employee_type" required class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Type</option>
                    <option value="Staff">Staff</option>
                    <option value="Admin">Admin</option>
                    <option value="Nurse">Nurse</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                <select name="employment_type" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Employment Type</option>
                    <option value="Permanent">Permanent</option>
                    <option value="Casual/Project">Casual/Project</option>
                    <option value="Casual Subsidy">Casual Subsidy</option>
                    <option value="Job Order">Job Order</option>
                    <option value="Contract of Service">Contract of Service</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Status</label>
                <select name="employment_status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Employment Status</option>
                    <option value="Full Time">Full Time</option>
                    <option value="Visiting">Visiting</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Active Status</label>
                <select name="is_active" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Structure</label>
                <select name="salary_structure_id" id="salary_structure_select"
                        class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                        onchange="populateSalaryDetails()">
                    <option value="">Select Salary Structure</option>
                    <?php foreach ($salary_structures as $structure): ?>
                        <option value="<?php echo $structure['id']; ?>" 
                                data-position="<?php echo htmlspecialchars($structure['position_title']); ?>"
                                data-grade="<?php echo htmlspecialchars($structure['grade_level']); ?>"
                                data-base-salary="<?php echo $structure['base_salary']; ?>"
                                data-min-salary="<?php echo $structure['minimum_salary']; ?>"
                                data-max-salary="<?php echo $structure['maximum_salary']; ?>"
                                data-increment-percentage="<?php echo $structure['increment_percentage']; ?>"
                                data-increment-amount="<?php echo $structure['incrementation_amount']; ?>"
                                data-increment-frequency="<?php echo $structure['incrementation_frequency_years']; ?>">
                            <?php echo htmlspecialchars($structure['position_title']); ?> - <?php echo htmlspecialchars($structure['grade_level']); ?> (₱<?php echo number_format($structure['base_salary'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Select a salary structure to auto-populate compensation details below</p>
                <div id="salary_structure_info" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <div>
                            <h4 class="text-sm font-medium text-blue-900">Salary Structure Details</h4>
                            <div id="salary_structure_details" class="text-xs text-blue-700 mt-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compensation Details Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-money-bill-wave text-green-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Compensation Details</h3>
                <p class="text-gray-600 text-sm">Salary and benefits information</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Basic Salary (Monthly)</label>
                <input type="number" name="basic_salary" id="basic_salary" step="0.01" min="0"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter basic salary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Grade</label>
                <input type="text" name="salary_grade" id="salary_grade"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., SG-11" readonly>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Step Increment</label>
                <input type="number" name="step_increment" id="step_increment" min="1" max="8" placeholder="1"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Allowances</label>
                <input type="number" name="allowances" step="0.01" min="0" value="0"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter allowances">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate (Hourly)</label>
                <input type="number" name="overtime_rate" step="0.01" min="0"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter overtime rate">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pay Schedule</label>
                <select name="pay_schedule" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Pay Schedule</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Bi-monthly">Bi-monthly</option>
                    <option value="Quincena">Quincena</option>
                    <option value="Weekly">Weekly</option>
                    <option value="Daily">Daily</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                <input type="text" name="bank_name"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter bank name">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Account Number</label>
                <input type="text" name="bank_account_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter bank account number">
            </div>
        </div>
    </div>

    <!-- Government IDs Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-id-card text-yellow-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Government IDs & Benefits</h3>
                <p class="text-gray-600 text-sm">SSS, PhilHealth, PAG-IBIG, TIN, and other government IDs</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">TIN Number</label>
                <input type="text" name="tin_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxx-xxx-xxx-xxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SSS Number</label>
                <input type="text" name="sss_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xx-xxxxxxx-x">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Number</label>
                <input type="text" name="philhealth_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxxx-xxxx-xxxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PAG-IBIG (HDMF) Number</label>
                <input type="text" name="pagibig_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxxx-xxxx-xxxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">UMID Number</label>
                <input type="text" name="umid_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter UMID number">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Driver's License</label>
                <input type="text" name="drivers_license"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter license number">
            </div>
        </div>
    </div>

    <!-- Professional Licenses Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-certificate text-indigo-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Professional Licenses</h3>
                <p class="text-gray-600 text-sm">PRC and other professional certifications</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PRC License Number</label>
                <input type="text" name="prc_license_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter PRC license number">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PRC Profession</label>
                <input type="text" name="prc_profession"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Licensed Engineer">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PRC License Expiry</label>
                <input type="date" name="prc_license_expiry"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
        </div>
    </div>

    <!-- Educational Background Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-graduation-cap text-teal-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Educational Background</h3>
                <p class="text-gray-600 text-sm">Educational qualifications and achievements</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Highest Education</label>
                <select name="highest_education" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Education Level</option>
                    <option value="Elementary">Elementary</option>
                    <option value="High School">High School</option>
                    <option value="Vocational">Vocational</option>
                    <option value="Associate Degree">Associate Degree</option>
                    <option value="Bachelor's Degree">Bachelor's Degree</option>
                    <option value="Master's Degree">Master's Degree</option>
                    <option value="Doctorate">Doctorate</option>
                    <option value="Post-Doctorate">Post-Doctorate</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Field of Study</label>
                <input type="text" name="field_of_study"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Computer Science">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">School/University</label>
                <input type="text" name="school_university"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter school/university">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year Graduated</label>
                <input type="number" name="year_graduated" min="1950" max="2030"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., 2020">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Honors & Awards</label>
                <input type="text" name="honors_awards"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Academic honors, awards, recognitions">
            </div>
        </div>
    </div>

    <!-- Emergency Contact Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-phone text-orange-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Emergency Contact</h3>
                <p class="text-gray-600 text-sm">Emergency contact information</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                <input type="text" name="emergency_contact_name"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter contact name">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                <input type="tel" name="emergency_contact_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter contact number">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Relationship</label>
                <input type="text" name="emergency_contact_relationship"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Spouse, Parent, Sibling">
            </div>
        </div>
    </div>

    <!-- Security Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-lock text-red-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Security Information</h3>
                <p class="text-gray-600 text-sm">Login credentials</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter password">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Confirm password">
            </div>
        </div>
    </div>

    <!-- Profile Photo Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-camera text-purple-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Profile Photo</h3>
                <p class="text-gray-600 text-sm">Upload employee profile picture</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center border-2 border-gray-200" id="photo-preview">
                        <i class="fas fa-user text-gray-400 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               onchange="previewPhoto(this)">
                        <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, PNG, GIF (Max: 2MB)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Information Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-info-circle text-gray-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Additional Information</h3>
                <p class="text-gray-600 text-sm">Skills, languages, and other details</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Languages Spoken</label>
                <input type="text" name="languages_spoken"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Filipino, English, Spanish">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Skills & Competencies</label>
                <textarea name="skills_competencies" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="List key skills and competencies"></textarea>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">HR Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="Additional notes or comments"></textarea>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex flex-col sm:flex-row gap-4 justify-end">
            <a href="admin-employee.php" 
               class="w-full sm:w-auto bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium text-center">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            
            <button type="submit" 
                    class="w-full sm:w-auto bg-gradient-to-r from-green-500 to-green-600 text-white px-8 py-3 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-plus mr-2"></i>Add Employee
            </button>
        </div>
    </div>
</form>

<script>
// Salary structure integration functions
function filterSalaryStructures() {
    const positionSelect = document.getElementById('position_select');
    const salaryStructureSelect = document.getElementById('salary_structure_select');
    const customPositionInput = document.getElementById('position_custom');
    
    // Show/hide custom position input
    if (positionSelect.value === 'custom') {
        customPositionInput.classList.remove('hidden');
        customPositionInput.required = true;
        // Clear salary structure options
        salaryStructureSelect.innerHTML = '<option value="">Select Salary Structure</option>';
    } else {
        customPositionInput.classList.add('hidden');
        customPositionInput.required = false;
        customPositionInput.value = '';
        
        // Filter salary structures by position
        const allOptions = salaryStructureSelect.querySelectorAll('option[data-position]');
        salaryStructureSelect.innerHTML = '<option value="">Select Salary Structure</option>';
        
        allOptions.forEach(option => {
            if (option.getAttribute('data-position') === positionSelect.value) {
                salaryStructureSelect.appendChild(option.cloneNode(true));
            }
        });
    }
    
    // Clear populated salary details
    clearSalaryDetails();
    updateFormProgress();
}

function populateSalaryDetails() {
    const salaryStructureSelect = document.getElementById('salary_structure_select');
    const selectedOption = salaryStructureSelect.options[salaryStructureSelect.selectedIndex];
    
    if (selectedOption.value) {
        // Populate salary fields
        document.getElementById('basic_salary').value = selectedOption.getAttribute('data-base-salary');
        document.getElementById('salary_grade').value = selectedOption.getAttribute('data-grade');
        
        // Calculate and populate step increment
        calculateStepIncrement();
        
        // Add visual indicator that fields are auto-populated
        const basicSalaryField = document.getElementById('basic_salary');
        const salaryGradeField = document.getElementById('salary_grade');
        const stepIncrementField = document.getElementById('step_increment');
        
        basicSalaryField.classList.add('bg-green-50', 'border-green-300');
        salaryGradeField.classList.add('bg-green-50', 'border-green-300');
        stepIncrementField.classList.add('bg-green-50', 'border-green-300');
        
        // Show salary structure details
        const minSalary = parseFloat(selectedOption.getAttribute('data-min-salary'));
        const maxSalary = parseFloat(selectedOption.getAttribute('data-max-salary'));
        const incrementPercentage = selectedOption.getAttribute('data-increment-percentage');
        const incrementAmount = selectedOption.getAttribute('data-increment-amount');
        const incrementFrequency = selectedOption.getAttribute('data-increment-frequency');
        
        let incrementText = '';
        if (incrementPercentage && incrementPercentage > 0) {
            incrementText = `${incrementPercentage}% every ${incrementFrequency} year(s)`;
        } else if (incrementAmount && incrementAmount > 0) {
            incrementText = `₱${parseFloat(incrementAmount).toLocaleString()} every ${incrementFrequency} year(s)`;
        }
        
        const hireDate = document.getElementById('hire_date').value;
        let stepIncrementInfo = '';
        if (hireDate) {
            const incrementFrequency = parseInt(selectedOption.getAttribute('data-increment-frequency')) || 1;
            const hireDateObj = new Date(hireDate);
            const currentDate = new Date();
            const yearsOfService = Math.floor((currentDate - hireDateObj) / (365.25 * 24 * 60 * 60 * 1000));
            const calculatedStep = Math.min(Math.floor(yearsOfService / incrementFrequency) + 1, 8);
            stepIncrementInfo = ` | Step: ${calculatedStep}`;
        }
        
        const detailsHtml = `
            <div class="grid grid-cols-2 gap-2">
                <div>Salary Range: ₱${minSalary.toLocaleString()} - ₱${maxSalary.toLocaleString()}</div>
                <div>Increment: ${incrementText || 'None'}${stepIncrementInfo}</div>
            </div>
        `;
        
        document.getElementById('salary_structure_details').innerHTML = detailsHtml;
        document.getElementById('salary_structure_info').classList.remove('hidden');
        
        // Update form progress
        updateFormProgress();
        
        // Show success message
        showMessage('Salary details populated from selected structure!', 'success');
    } else {
        clearSalaryDetails();
    }
}

function calculateStepIncrement() {
    const hireDate = document.getElementById('hire_date').value;
    const salaryStructureSelect = document.getElementById('salary_structure_select');
    const selectedOption = salaryStructureSelect.options[salaryStructureSelect.selectedIndex];
    
    if (!hireDate || !selectedOption.value) {
        return;
    }
    
    const incrementFrequency = parseInt(selectedOption.getAttribute('data-increment-frequency')) || 1;
    const hireDateObj = new Date(hireDate);
    const currentDate = new Date();
    const yearsOfService = Math.floor((currentDate - hireDateObj) / (365.25 * 24 * 60 * 60 * 1000));
    
    // Calculate step increment based on years of service and increment frequency
    const stepIncrement = Math.min(Math.floor(yearsOfService / incrementFrequency) + 1, 8);
    
    document.getElementById('step_increment').value = stepIncrement;
    
    // Update visual indicator if salary structure is selected
    if (selectedOption.value) {
        const stepIncrementField = document.getElementById('step_increment');
        stepIncrementField.classList.add('bg-green-50', 'border-green-300');
    }
}

function clearSalaryDetails() {
    const basicSalaryField = document.getElementById('basic_salary');
    const salaryGradeField = document.getElementById('salary_grade');
    const stepIncrementField = document.getElementById('step_increment');
    
    basicSalaryField.value = '';
    salaryGradeField.value = '';
    stepIncrementField.value = '';
    
    // Remove visual indicators
    basicSalaryField.classList.remove('bg-green-50', 'border-green-300');
    salaryGradeField.classList.remove('bg-green-50', 'border-green-300');
    stepIncrementField.classList.remove('bg-green-50', 'border-green-300');
    
    // Hide salary structure info
    document.getElementById('salary_structure_info').classList.add('hidden');
    
    updateFormProgress();
}

// Form progress tracking
function updateFormProgress() {
    const form = document.getElementById('comprehensiveEmployeeForm');
    
    // Only count required fields for progress calculation
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
    const filledRequiredFields = Array.from(requiredFields).filter(field => field.value.trim() !== '');
    const progress = requiredFields.length > 0 ? Math.round((filledRequiredFields.length / requiredFields.length) * 100) : 0;
    
    document.getElementById('formProgress').textContent = progress + '%';
    document.getElementById('progressBar').style.width = progress + '%';
    
    // Change color based on progress
    const progressBar = document.getElementById('progressBar');
    if (progress < 30) {
        progressBar.className = 'bg-gradient-to-r from-red-500 to-red-600 h-2 rounded-full transition-all duration-500';
    } else if (progress < 70) {
        progressBar.className = 'bg-gradient-to-r from-yellow-500 to-yellow-600 h-2 rounded-full transition-all duration-500';
    } else {
        progressBar.className = 'bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full transition-all duration-500';
    }
}

// Auto-generate Employee ID
function generateEmployeeID() {
    const currentYear = new Date().getFullYear();
    const randomSuffix = Math.floor(Math.random() * 9999).toString().padStart(4, '0');
    const employeeId = `${currentYear}-${randomSuffix}`;
    document.getElementById('employee_id_input').value = employeeId;
    updateFormProgress();
}

// Preview uploaded photo
function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Check file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            input.value = '';
            return;
        }
        
        // Check file type
        if (!file.type.match('image.*')) {
            alert('Please select a valid image file');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover rounded-full">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '<i class="fas fa-user text-gray-400 text-lg"></i>';
    }
    
    updateFormProgress();
}

// Form submission with comprehensive validation
document.getElementById('comprehensiveEmployeeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate passwords match
    const password = this.querySelector('input[name="password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Employee...';
    
    // Submit form via AJAX
    const formData = new FormData(this);
    
    // Handle custom position
    const positionSelect = document.getElementById('position_select');
    if (positionSelect.value === 'custom') {
        const customPosition = document.getElementById('position_custom').value;
        formData.set('position', customPosition);
    }
    
    fetch('add-employee-comprehensive.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Reset form
            this.reset();
            updateFormProgress();
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'admin-employee.php';
            }, 2000);
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Network error. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Show message function
function showMessage(message, type) {
    const container = document.getElementById('messageContainer');
    const alertClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
    const iconClass = type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-circle text-red-400';
    
    container.innerHTML = `
        <div class="mb-6 ${alertClass} border rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="${iconClass}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        </div>
    `;
}

// Initialize form tracking
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('comprehensiveEmployeeForm');
    const fields = form.querySelectorAll('input, select, textarea');
    
    fields.forEach(field => {
        field.addEventListener('input', updateFormProgress);
        field.addEventListener('change', updateFormProgress);
    });
    
    // Initial progress update
    updateFormProgress();
});
</script>

