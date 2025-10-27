<?php
session_start();
require_once 'config/database.php';
require_once 'includes/roles.php';

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get user information including email
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
$user_email = $user_info['email'] ?? '';

// Get employee information by matching email
$employee_query = "SELECT e.*, d.name as department_name 
                   FROM employees e 
                   LEFT JOIN departments d ON e.department_id = d.id 
                   WHERE e.email = ? AND e.is_active = 1";
$employee_stmt = mysqli_prepare($conn, $employee_query);
mysqli_stmt_bind_param($employee_stmt, "s", $user_email);
mysqli_stmt_execute($employee_stmt);
$employee = mysqli_fetch_assoc(mysqli_stmt_get_result($employee_stmt));

if (!$employee) {
    die('Employee record not found');
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">My Profile</h1>
                <p class="text-blue-100">Personal Information - NIA Human Resource Information System</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-blue-100">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <p class="text-sm text-blue-100">Role: Employee</p>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Personal Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-user mr-2 text-blue-600"></i>Personal Information
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">First Name</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['first_name']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Last Name</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['last_name']); ?></p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Email Address</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['email']); ?></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Phone Number</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Blood Type</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['blood_type'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-briefcase mr-2 text-green-600"></i>Employment Information
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Employee ID</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['employee_id']); ?></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Department</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Position</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['position'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Employment Type</label>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['employee_type'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($employee['is_active'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ($employee['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Hire Date</label>
                    <p class="text-lg font-semibold text-gray-900"><?php echo (!empty($employee['hire_date']) && $employee['hire_date'] !== '0000-00-00') ? date('M d, Y', strtotime($employee['hire_date'])) : 'Not specified'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-map-marker-alt mr-2 text-purple-600"></i>Address Information
        </h3>
        <div>
            <label class="text-sm font-medium text-gray-600">Address</label>
            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['address'] ?? 'Not specified'); ?></p>
        </div>
    </div>

    <!-- Government IDs -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-id-card mr-2 text-indigo-600"></i>Government IDs
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm font-medium text-gray-600">SSS Number</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['sss_number'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Pag-IBIG Number</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['pagibig_number'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">TIN Number</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['tin_number'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">PhilHealth Number</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['philhealth_number'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-phone mr-2 text-red-600"></i>Emergency Contact
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm font-medium text-gray-600">Emergency Contact Name</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['emergency_contact_name'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Emergency Contact Number</label>
                <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($employee['emergency_contact_number'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex justify-end">
        <a href="employee-dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>
</div>
