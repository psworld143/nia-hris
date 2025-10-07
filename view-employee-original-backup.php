<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
require_once '../includes/error_handler.php';

// Check database connection
if (!checkDatabaseConnection($conn)) {
    // If we can't redirect due to headers already sent, show a user-friendly error
    if (headers_sent()) {
        echo '<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                <h2>Database Connection Error</h2>
                <p>Unable to connect to the database. Please try refreshing the page or contact support if the problem persists.</p>
              </div>';
        exit();
    }
}

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Get and validate employee ID
$encrypted_id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($encrypted_id)) {
    header('Location: admin-employee.php');
    exit();
}

// Decrypt the employee ID
$employee_id = decrypt_id($encrypted_id);
if (!$employee_id) {
    header('Location: admin-employee.php');
    exit();
}

// Get employee details with comprehensive HR information
$employee_query = "SELECT e.*, 
                          ed.*,
                          d.name as department_name, 
                          d.icon as department_icon, 
                          d.color_theme as department_color, 
                          d.description as department_description,
                          rs.name as regularization_status,
                          rs.color as status_color
                   FROM employees e 
                   LEFT JOIN employee_details ed ON e.id = ed.employee_id
                   LEFT JOIN departments d ON e.department = d.name 
                   LEFT JOIN employee_regularization er ON e.id = er.employee_id
                   LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                   WHERE e.id = ?";

$employee_stmt = mysqli_prepare($conn, $employee_query);
if ($employee_stmt) {
    mysqli_stmt_bind_param($employee_stmt, "i", $employee_id);
    if (!checkDatabaseStatement($employee_stmt, "employee_details")) {
        // If we can't redirect due to headers already sent, show a user-friendly error
        if (headers_sent()) {
            echo '<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                    <h2>Database Error</h2>
                    <p>Unable to retrieve employee information. Please try refreshing the page or contact support if the problem persists.</p>
                  </div>';
            exit();
        }
    }
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if ($employee_result && $employee = mysqli_fetch_assoc($employee_result)) {
        // Employee found
        $page_title = 'Employee Profile - ' . $employee['first_name'] . ' ' . $employee['last_name'];
    } else {
        // Employee not found
        header('Location: admin-employee.php');
        exit();
    }
    mysqli_stmt_close($employee_stmt);
} else {
    header('Location: admin-employee.php');
    exit();
}

// Get employee activity logs (if table exists)
$activity_logs = [];
$logs_query = "SHOW TABLES LIKE 'employee_activity_logs'";
$logs_table_exists = mysqli_query($conn, $logs_query);

if ($logs_table_exists && mysqli_num_rows($logs_table_exists) > 0) {
    $activity_query = "SELECT * FROM employee_activity_logs WHERE employee_id = ? ORDER BY created_at DESC LIMIT 10";
    $activity_stmt = mysqli_prepare($conn, $activity_query);
    if ($activity_stmt) {
        mysqli_stmt_bind_param($activity_stmt, "i", $employee_id);
        if (mysqli_stmt_execute($activity_stmt)) {
            $activity_result = mysqli_stmt_get_result($activity_stmt);
            while ($log = mysqli_fetch_assoc($activity_result)) {
                $activity_logs[] = $log;
            }
        }
        mysqli_stmt_close($activity_stmt);
    }
}

// Include the header
include 'includes/header.php';
?>

<!-- Profile Header with Cover and Photo -->
<div class="relative bg-gradient-to-r from-green-500 via-green-600 to-green-700 rounded-xl shadow-lg overflow-hidden mb-8">
    <!-- Cover Background -->
    <div class="h-48 sm:h-64 relative">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-gradient-to-br from-green-500/20 to-green-800/30"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Cg fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="50" cy="50" r="4"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
        
        <!-- Back Button -->
        <div class="absolute top-4 left-4">
            <a href="admin-employee.php" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg hover:bg-white/30 transform transition-all hover:scale-105 font-medium border border-white/20">
                <i class="fas fa-arrow-left mr-2"></i>Back to Employees
            </a>
        </div>
        
        <!-- Edit Button -->
        <div class="absolute top-4 right-4">
            <a href="edit-employee.php?id=<?php echo urlencode($encrypted_id); ?>" class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-lg hover:bg-white/30 transform transition-all hover:scale-105 font-medium border border-white/20">
                <i class="fas fa-edit mr-2"></i>Edit Profile
            </a>
        </div>
    </div>
    
    <!-- Profile Photo and Basic Info -->
    <div class="relative -mt-20 pb-6">
        <div class="text-center">
            <!-- Large Profile Photo -->
            <div class="relative inline-block">
                <div class="w-32 h-32 mx-auto rounded-full border-4 border-white shadow-xl overflow-hidden bg-white">
                    <?php if (!empty($employee['profile_photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/seait/' . $employee['profile_photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" 
                             alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-bold text-3xl">
                            <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Status Indicator -->
                <div class="absolute bottom-2 right-2 w-6 h-6 <?php echo $employee['is_active'] ? 'bg-green-400' : 'bg-red-400'; ?> border-2 border-white rounded-full flex items-center justify-center">
                    <i class="fas <?php echo $employee['is_active'] ? 'fa-check' : 'fa-times'; ?> text-white text-xs"></i>
                </div>
            </div>
            
            <!-- Name and Position -->
            <div class="mt-4 text-white">
                <h1 class="text-3xl font-bold mb-2">
                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . $employee['last_name']); ?>
                </h1>
                <p class="text-xl text-green-100 mb-2">
                    <?php echo htmlspecialchars($employee['position'] ?? 'Employee'); ?>
                </p>
                <p class="text-green-200 flex items-center justify-center">
                    <i class="fas fa-building mr-2"></i>
                    <?php echo htmlspecialchars($employee['department'] ?? 'No Department'); ?>
                </p>
                
                <!-- Status Badges -->
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $employee['is_active'] ? 'bg-green-400 text-green-900' : 'bg-red-400 text-red-900'; ?>">
                        <i class="fas fa-circle mr-2 text-xs"></i>
                        <?php echo $employee['is_active'] ? 'Active Employee' : 'Inactive Employee'; ?>
                    </span>
                    
                    <?php if ($employee['regularization_status']): ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium" 
                          style="background-color: <?php echo $employee['status_color']; ?>20; color: <?php echo $employee['status_color']; ?>;">
                        <i class="fas fa-user-check mr-2 text-xs"></i>
                        <?php echo htmlspecialchars($employee['regularization_status']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Profile Card -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
        <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center text-white text-3xl font-bold backdrop-blur-sm">
                    <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?></h2>
                <p class="text-xl opacity-90 mb-1"><?php echo htmlspecialchars($employee['position'] ?? ''); ?></p>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <?php if ($employee['department_icon'] && $employee['department_color']): ?>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm mr-2" 
                                 style="background-color: <?php echo $employee['department_color']; ?>">
                                <i class="<?php echo $employee['department_icon']; ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="text-lg"><?php echo htmlspecialchars(($employee['department_name'] ?: $employee['department']) ?? ''); ?></span>
                    </div>
                    <span class="px-3 py-1 text-sm rounded-full font-semibold <?php echo $employee['is_active'] ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100'; ?>">
                        <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-75">Employee ID</p>
                <p class="text-xl font-mono font-bold"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Employee Information Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Personal Information -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user text-blue-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Personal Information</h3>
                <p class="text-gray-600 text-sm">Basic personal details</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Full Name</span>
                <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Email Address</span>
                <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars($employee['email'] ?? ''); ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Phone Number</span>
                <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars($employee['phone'] ?? ''); ?></span>
            </div>
            
            <div class="flex justify-between items-start py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Address</span>
                <span class="text-gray-900 font-semibold text-right max-w-xs"><?php echo nl2br(htmlspecialchars($employee['address'] ?? '')); ?></span>
            </div>
        </div>
    </div>

    <!-- Employment Information -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-briefcase text-purple-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Employment Information</h3>
                <p class="text-gray-600 text-sm">Job and employment details</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Employee ID</span>
                <span class="text-gray-900 font-semibold font-mono"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Position</span>
                <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars($employee['position'] ?? ''); ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Employee Type</span>
                <span class="px-3 py-1 text-sm rounded-full font-semibold bg-blue-100 text-blue-800">
                    <?php echo ucfirst(htmlspecialchars($employee['employee_type'] ?? '')); ?>
                </span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Date of Hire</span>
                <span class="text-gray-900 font-semibold"><?php echo $employee['hire_date'] ? date('F j, Y', strtotime($employee['hire_date'])) : 'Not set'; ?></span>
            </div>
            
            <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600 font-medium">Employment Status</span>
                <span class="px-3 py-1 text-sm rounded-full font-semibold <?php echo $employee['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Department Information -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
            <i class="fas fa-building text-green-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">Department Information</h3>
            <p class="text-gray-600 text-sm">Department and organizational details</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <?php if ($employee['department_icon'] && $employee['department_color']): ?>
                <div class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-2xl" 
                     style="background-color: <?php echo $employee['department_color']; ?>">
                    <i class="<?php echo $employee['department_icon']; ?>"></i>
                </div>
            <?php else: ?>
                <div class="w-16 h-16 bg-green-500 rounded-xl flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-building"></i>
                </div>
            <?php endif; ?>
            <div>
                <h4 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars(($employee['department_name'] ?: $employee['department']) ?? ''); ?></h4>
                <?php if ($employee['department_description']): ?>
                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($employee['department_description'] ?? ''); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Department Name</span>
                <span class="text-gray-900 font-semibold"><?php echo htmlspecialchars(($employee['department_name'] ?: $employee['department']) ?? ''); ?></span>
            </div>
            
            <?php if ($employee['department_description']): ?>
            <div class="flex justify-between items-start py-2">
                <span class="text-gray-600 font-medium">Description</span>
                <span class="text-gray-900 font-semibold text-right max-w-xs"><?php echo htmlspecialchars($employee['department_description'] ?? ''); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Color Theme</span>
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 rounded-full border-2 border-gray-300" 
                         style="background-color: <?php echo $employee['department_color'] ?: '#10B981'; ?>"></div>
                    <span class="text-gray-900 font-semibold font-mono"><?php echo $employee['department_color'] ?: '#10B981'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-4">
            <i class="fas fa-cog text-gray-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">System Information</h3>
            <p class="text-gray-600 text-sm">Account and system details</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Record ID</span>
                <span class="text-gray-900 font-semibold font-mono"><?php echo $employee['id']; ?></span>
            </div>
            
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Created</span>
                <span class="text-gray-900 font-semibold"><?php echo $employee['created_at'] ? date('M j, Y g:i A', strtotime($employee['created_at'])) : 'Not set'; ?></span>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Last Updated</span>
                <span class="text-gray-900 font-semibold">
                    <?php echo $employee['updated_at'] ? date('M j, Y g:i A', strtotime($employee['updated_at'])) : 'Never'; ?>
                </span>
            </div>
            
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Account Status</span>
                <span class="px-3 py-1 text-sm rounded-full font-semibold <?php echo $employee['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Tenure</span>
                <span class="text-gray-900 font-semibold">
                    <?php 
                    if ($employee['hire_date']) {
                        $hire_date = new DateTime($employee['hire_date']);
                        $now = new DateTime();
                        $tenure = $hire_date->diff($now);
                        echo $tenure->y . ' years, ' . $tenure->m . ' months';
                    } else {
                        echo 'Not available';
                    }
                    ?>
                </span>
            </div>
            
            <div class="flex justify-between items-center py-2">
                <span class="text-gray-600 font-medium">Days Employed</span>
                <span class="text-gray-900 font-semibold">
                    <?php 
                    if ($employee['hire_date']) {
                        $hire_date = new DateTime($employee['hire_date']);
                        $now = new DateTime();
                        $tenure = $hire_date->diff($now);
                        echo $tenure->days;
                    } else {
                        echo '0';
                    }
                    ?> days
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Activity Logs (if available) -->
<?php if (!empty($activity_logs)): ?>
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
            <i class="fas fa-history text-yellow-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">Recent Activity</h3>
            <p class="text-gray-600 text-sm">Latest employee activities</p>
        </div>
    </div>
    
    <div class="space-y-4">
        <?php foreach ($activity_logs as $log): ?>
        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white">
                <i class="fas fa-<?php echo $log['action_type'] === 'login' ? 'sign-in-alt' : 'edit'; ?>"></i>
            </div>
            <div class="flex-1">
                <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($log['action']); ?></p>
                <p class="text-gray-600 text-sm"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 text-sm rounded-full font-semibold bg-blue-100 text-blue-800">
                    <?php echo ucfirst(htmlspecialchars($log['action_type'])); ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
    <a href="admin-employee.php" 
       class="w-full sm:w-auto bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium text-center">
        <i class="fas fa-arrow-left mr-2"></i>Back to Employees
    </a>
    
    <a href="edit-employee.php?id=<?php echo $encrypted_id; ?>" 
       class="w-full sm:w-auto bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium text-center">
        <i class="fas fa-edit mr-2"></i>Edit Employee
    </a>
    
    <button onclick="printEmployeeDetails()" 
            class="w-full sm:w-auto bg-seait-dark text-white px-8 py-3 rounded-lg hover:bg-gray-800 transform transition-all hover:scale-105 font-medium">
        <i class="fas fa-print mr-2"></i>Print Details
    </button>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Print employee details function
function printEmployeeDetails() {
    window.print();
}

// Add some interactive features
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to information cards
    const cards = document.querySelectorAll('.bg-white.rounded-xl.shadow-lg');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Add copy functionality for email
    const emailElement = document.querySelector('.text-gray-900.font-semibold');
    if (emailElement && emailElement.textContent.includes('@')) {
        emailElement.style.cursor = 'pointer';
        emailElement.title = 'Click to copy email';
        emailElement.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent).then(() => {
                // Show a temporary notification
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                notification.textContent = 'Email copied to clipboard!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 2000);
            });
        });
    }
});

// Add print styles
const printStyles = `
    @media print {
        .bg-gradient-to-r, .bg-seait-orange, .bg-seait-dark, .bg-gray-500 {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .shadow-lg {
            box-shadow: none !important;
        }
        
        .transform, .hover\\:scale-105 {
            transform: none !important;
        }
        
        .flex.space-x-3, .flex.flex-col.sm\\:flex-row.gap-4 {
            display: none !important;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = printStyles;
document.head.appendChild(styleSheet);
</script>
