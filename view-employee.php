<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check database connection
if (!$conn || mysqli_connect_errno()) {
    die('<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
            <h2>Database Connection Error</h2>
            <p>Unable to connect to the database. Please try refreshing the page or contact support if the problem persists.</p>
          </div>');
}

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager', 'nurse'])) {
    header('Location: index.php');
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
$employee_query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email, e.position, 
                          e.department, e.employee_type, e.hire_date, e.phone, e.address, 
                          e.is_active, e.created_at, e.updated_at,
                          e.blood_type, e.medical_conditions, e.allergies, e.medications,
                          e.last_medical_checkup, e.medical_notes,
                          e.emergency_contact_name, e.emergency_contact_number,
                          ed.middle_name, ed.date_of_birth, ed.gender, ed.civil_status, 
                          ed.nationality, ed.religion, 
                          ed.emergency_contact_relationship,
                          ed.employment_type, ed.employment_status, ed.job_level, ed.immediate_supervisor, 
                          ed.work_schedule, ed.basic_salary, ed.salary_grade, 
                          ed.step_increment, ed.allowances, ed.pay_schedule,
                          ed.bank_account_number, ed.bank_name,
                          ed.tin_number, ed.sss_number, ed.philhealth_number, 
                          ed.pagibig_number, ed.umid_number, ed.drivers_license,
                          ed.prc_license_number, ed.prc_license_expiry, ed.prc_profession,
                          ed.highest_education, ed.field_of_study, ed.school_university, 
                          ed.year_graduated, ed.honors_awards,
                          ed.languages_spoken, ed.skills_competencies, 
                          ed.notes, ed.profile_photo,
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
    
    // Execute the statement
    if (!mysqli_stmt_execute($employee_stmt)) {
        // If we can't redirect due to headers already sent, show a user-friendly error
        if (headers_sent()) {
            echo '<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                    <h2>Database Error</h2>
                    <p>Unable to retrieve employee information. Please try refreshing the page or contact support if the problem persists.</p>
                  </div>';
            exit();
        } else {
            header('Location: admin-employee.php');
            exit();
        }
    }
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if ($employee_result && $employee = mysqli_fetch_assoc($employee_result)) {
        // Employee found
        $page_title = 'Employee Profile - ' . $employee['first_name'] . ' ' . $employee['last_name'];
        
        // Get medical history records
        $medical_history_query = "SELECT * FROM employee_medical_history 
                                  WHERE employee_id = ? 
                                  ORDER BY record_date DESC, created_at DESC 
                                  LIMIT 20";
        $medical_stmt = mysqli_prepare($conn, $medical_history_query);
        if ($medical_stmt) {
            mysqli_stmt_bind_param($medical_stmt, "i", $employee['id']);
            mysqli_stmt_execute($medical_stmt);
            $medical_history_result = mysqli_stmt_get_result($medical_stmt);
            $medical_history = [];
            while ($record = mysqli_fetch_assoc($medical_history_result)) {
                $medical_history[] = $record;
            }
            mysqli_stmt_close($medical_stmt);
        } else {
            $medical_history = [];
        }
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
                <?php 
                $status_color = 'bg-green-400';
                $status_icon = 'fa-check';
                
                if ($employee['employment_type']) {
                    switch ($employee['employment_type']) {
                        case 'Regular':
                            $status_color = 'bg-green-500';
                            $status_icon = 'fa-check-circle';
                            break;
                        case 'Probationary':
                            $status_color = 'bg-blue-500';
                            $status_icon = 'fa-clock';
                            break;
                        case 'Contractual':
                            $status_color = 'bg-orange-500';
                            $status_icon = 'fa-file-contract';
                            break;
                        case 'Part Time':
                            $status_color = 'bg-purple-500';
                            $status_icon = 'fa-user-clock';
                            break;
                        default:
                            $status_color = $employee['is_active'] ? 'bg-green-400' : 'bg-red-400';
                            $status_icon = $employee['is_active'] ? 'fa-check' : 'fa-times';
                    }
                } else {
                    $status_color = $employee['is_active'] ? 'bg-green-400' : 'bg-red-400';
                    $status_icon = $employee['is_active'] ? 'fa-check' : 'fa-times';
                }
                ?>
                <div class="absolute bottom-2 right-2 w-6 h-6 <?php echo $status_color; ?> border-2 border-white rounded-full flex items-center justify-center">
                    <i class="fas <?php echo $status_icon; ?> text-white text-xs"></i>
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
                    <?php 
                    $employment_display = '';
                    $badge_class = 'bg-green-400 text-green-900';
                    
                    if ($employee['employment_status'] && $employee['employment_type']) {
                        // Format as "Full-time Regular" instead of "Full Time Regular"
                        $status = str_replace(' ', '-', strtolower($employee['employment_status']));
                        $employment_display = ucfirst($status) . ' ' . $employee['employment_type'];
                    } else if ($employee['employment_type']) {
                        $employment_display = $employee['employment_type'];
                    } else if ($employee['employment_status']) {
                        $employment_display = $employee['employment_status'];
                    } else {
                        $employment_display = $employee['is_active'] ? 'Active Employee' : 'Inactive Employee';
                    }
                    
                    // Set badge color based on employment type
                    if (strpos($employment_display, 'Regular') !== false) {
                        $badge_class = 'bg-green-500 text-white';
                    } else if (strpos($employment_display, 'Probationary') !== false) {
                        $badge_class = 'bg-blue-500 text-white';
                    } else if (strpos($employment_display, 'Contractual') !== false) {
                        $badge_class = 'bg-orange-500 text-white';
                    } else if (strpos($employment_display, 'Part Time') !== false) {
                        $badge_class = 'bg-purple-500 text-white';
                    } else if (strpos($employment_display, 'Visiting') !== false) {
                        $badge_class = 'bg-yellow-500 text-yellow-900';
                    }
                    ?>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium <?php echo $badge_class; ?>">
                        <i class="fas fa-circle mr-2 text-xs"></i>
                        <?php echo htmlspecialchars($employment_display); ?>
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

<!-- Profile Details Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - Main Information -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Contact Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-address-card text-blue-600 text-lg"></i>
            </div>
            <div>
                    <h3 class="text-xl font-bold text-gray-900">Contact Information</h3>
                    <p class="text-gray-600 text-sm">Communication details</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-envelope text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['email']); ?></p>
            </div>
        </div>
        
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-phone text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['phone'] ?? 'Not provided'); ?></p>
                    </div>
            </div>
            
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-id-badge text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Employee ID</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['employee_id'] ?? 'Not assigned'); ?></p>
                    </div>
            </div>
            
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Date of Hire</label>
                        <p class="text-gray-900 font-medium">
                            <?php echo $employee['hire_date'] ? date('F j, Y', strtotime($employee['hire_date'])) : 'Not specified'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <?php if ($employee['address']): ?>
            <div class="mt-6 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                <div class="flex items-start">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-map-marker-alt text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Address</label>
                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($employee['address'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
    </div>

        <!-- Personal Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user text-purple-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Personal Information</h3>
                    <p class="text-gray-600 text-sm">Personal details and demographics</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($employee['date_of_birth']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-birthday-cake text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Date of Birth</label>
                        <p class="text-gray-900 font-medium"><?php echo date('F j, Y', strtotime($employee['date_of_birth'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['gender']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-venus-mars text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Gender</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['gender']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['civil_status']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-heart text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Civil Status</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['civil_status']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['nationality']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-flag text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Nationality</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['nationality']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['blood_type']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-tint text-green-600"></i>
            </div>
            <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Blood Type</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['blood_type']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Employment Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-briefcase text-green-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Employment Information</h3>
                    <p class="text-gray-600 text-sm">Job details and employment status</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-tie text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Employment Type</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['employment_type'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-briefcase text-blue-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Employment Status</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['employment_status'] ?? 'Not specified'); ?></p>
                    </div>
            </div>
            
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-layer-group text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Job Level</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['job_level'] ?? 'Not specified'); ?></p>
                    </div>
            </div>
            
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-clock text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Work Schedule</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['work_schedule'] ?? 'Not specified'); ?></p>
                    </div>
            </div>
            
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-friends text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Immediate Supervisor</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['immediate_supervisor'] ?? 'Not assigned'); ?></p>
            </div>
        </div>
    </div>
</div>

        <!-- Compensation Information Card -->
        <?php if ($employee['basic_salary'] || $employee['salary_grade'] || $employee['allowances']): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-money-bill-wave text-green-600 text-lg"></i>
        </div>
        <div>
                    <h3 class="text-xl font-bold text-gray-900">Compensation Details</h3>
                    <p class="text-gray-600 text-sm">Salary and benefits information</p>
        </div>
    </div>
    
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($employee['basic_salary']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-peso-sign text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Basic Salary</label>
                        <p class="text-gray-900 font-medium">₱<?php echo number_format($employee['basic_salary'], 2); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['salary_grade']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Salary Grade</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['salary_grade']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['allowances'] && $employee['allowances'] > 0): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-plus-circle text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Monthly Allowances</label>
                        <p class="text-gray-900 font-medium">₱<?php echo number_format($employee['allowances'], 2); ?></p>
                    </div>
                </div>
            <?php endif; ?>
                
                <?php if ($employee['pay_schedule']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-alt text-green-600"></i>
                    </div>
            <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Pay Schedule</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['pay_schedule']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Medical History Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-heartbeat text-red-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Medical History</h3>
                    <p class="text-gray-600 text-sm">Health records and medical information</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Blood Type -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-tint text-red-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Blood Type</label>
                        <p class="text-gray-900 font-medium">
                            <?php echo $employee['blood_type'] ? htmlspecialchars($employee['blood_type']) : '<span class="text-gray-400">Not specified</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Last Medical Checkup -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-check text-blue-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Last Medical Checkup</label>
                        <p class="text-gray-900 font-medium">
                            <?php 
                            if ($employee['last_medical_checkup']) {
                                echo date('F j, Y', strtotime($employee['last_medical_checkup']));
                            } else {
                                echo '<span class="text-gray-400">No record</span>';
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Medical Conditions -->
                <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors md:col-span-2">
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-file-medical text-yellow-600"></i>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-500 mb-1">Medical Conditions</label>
                        <p class="text-gray-900">
                            <?php echo $employee['medical_conditions'] ? nl2br(htmlspecialchars($employee['medical_conditions'])) : '<span class="text-gray-400">None reported</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Allergies -->
                <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors md:col-span-2">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-allergies text-orange-600"></i>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-500 mb-1">Allergies</label>
                        <p class="text-gray-900">
                            <?php echo $employee['allergies'] ? nl2br(htmlspecialchars($employee['allergies'])) : '<span class="text-gray-400">None reported</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Medications -->
                <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors md:col-span-2">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-pills text-purple-600"></i>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-500 mb-1">Current Medications</label>
                        <p class="text-gray-900">
                            <?php echo $employee['medications'] ? nl2br(htmlspecialchars($employee['medications'])) : '<span class="text-gray-400">None reported</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Emergency Contact Name -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user-shield text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Emergency Contact</label>
                        <p class="text-gray-900 font-medium">
                            <?php echo $employee['emergency_contact_name'] ? htmlspecialchars($employee['emergency_contact_name']) : '<span class="text-gray-400">Not specified</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Emergency Contact Number -->
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-phone-alt text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Emergency Number</label>
                        <p class="text-gray-900 font-medium">
                            <?php echo $employee['emergency_contact_number'] ? htmlspecialchars($employee['emergency_contact_number']) : '<span class="text-gray-400">Not specified</span>'; ?>
                        </p>
                    </div>
                </div>

                <!-- Medical Notes -->
                <?php if ($employee['medical_notes']): ?>
                <div class="flex items-start p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500 md:col-span-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-notes-medical text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Medical Notes</label>
                        <p class="text-blue-900 text-sm">
                            <?php echo nl2br(htmlspecialchars($employee['medical_notes'])); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (in_array($_SESSION['role'], ['super_admin', 'nurse'])): ?>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <a href="medical-records.php" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    Update Medical Records
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Medical History Timeline -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-history text-red-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Medical History Timeline</h3>
                        <p class="text-gray-600 text-sm">Past medical records and health events</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                        <?php echo count($medical_history); ?> Records
                    </span>
                    <?php if (in_array($_SESSION['role'], ['super_admin', 'nurse'])): ?>
                    <button onclick="openAddMedicalHistoryModal()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-semibold">
                        <i class="fas fa-plus mr-2"></i>Add Record
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($medical_history)): ?>
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-notes-medical text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500 mb-4">No medical history records yet</p>
                <?php if (in_array($_SESSION['role'], ['super_admin', 'nurse'])): ?>
                <button onclick="openAddMedicalHistoryModal()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add First Record
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            
            <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($medical_history as $record): ?>
                <div class="border-l-4 <?php 
                    $type_colors = [
                        'checkup' => 'border-blue-500 bg-blue-50',
                        'diagnosis' => 'border-red-500 bg-red-50',
                        'treatment' => 'border-green-500 bg-green-50',
                        'vaccination' => 'border-purple-500 bg-purple-50',
                        'lab_test' => 'border-yellow-500 bg-yellow-50',
                        'consultation' => 'border-teal-500 bg-teal-50',
                        'emergency' => 'border-orange-500 bg-orange-50',
                        'follow_up' => 'border-indigo-500 bg-indigo-50'
                    ];
                    echo $type_colors[$record['record_type']] ?? 'border-gray-500 bg-gray-50';
                ?> p-4 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold uppercase <?php
                                $badge_colors = [
                                    'checkup' => 'bg-blue-200 text-blue-800',
                                    'diagnosis' => 'bg-red-200 text-red-800',
                                    'treatment' => 'bg-green-200 text-green-800',
                                    'vaccination' => 'bg-purple-200 text-purple-800',
                                    'lab_test' => 'bg-yellow-200 text-yellow-800',
                                    'consultation' => 'bg-teal-200 text-teal-800',
                                    'emergency' => 'bg-orange-200 text-orange-800',
                                    'follow_up' => 'bg-indigo-200 text-indigo-800'
                                ];
                                echo $badge_colors[$record['record_type']] ?? 'bg-gray-200 text-gray-800';
                            ?>">
                                <?php echo str_replace('_', ' ', $record['record_type']); ?>
                            </span>
                        </div>
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('F j, Y', strtotime($record['record_date'])); ?>
                        </span>
                    </div>
                    
                    <?php if ($record['chief_complaint']): ?>
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-gray-600">Chief Complaint:</span>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($record['chief_complaint']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($record['diagnosis']): ?>
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-gray-600">Diagnosis:</span>
                        <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($record['treatment']): ?>
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-gray-600">Treatment:</span>
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($record['treatment']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($record['medication_prescribed']): ?>
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-gray-600">Medication:</span>
                        <p class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($record['medication_prescribed'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($record['vital_signs']): ?>
                    <div class="mb-2">
                        <span class="text-xs font-semibold text-gray-600">Vital Signs:</span>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <?php 
                            $vitals = json_decode($record['vital_signs'], true);
                            if ($vitals):
                                if (isset($vitals['blood_pressure'])): ?>
                                    <span class="px-2 py-1 bg-white rounded text-xs border">
                                        <i class="fas fa-heartbeat text-red-500 mr-1"></i>BP: <?php echo $vitals['blood_pressure']; ?>
                                    </span>
                                <?php endif;
                                if (isset($vitals['heart_rate'])): ?>
                                    <span class="px-2 py-1 bg-white rounded text-xs border">
                                        <i class="fas fa-heart text-pink-500 mr-1"></i>HR: <?php echo $vitals['heart_rate']; ?> bpm
                                    </span>
                                <?php endif;
                                if (isset($vitals['temperature'])): ?>
                                    <span class="px-2 py-1 bg-white rounded text-xs border">
                                        <i class="fas fa-thermometer-half text-orange-500 mr-1"></i>Temp: <?php echo $vitals['temperature']; ?>°C
                                    </span>
                                <?php endif;
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500">
                        <div>
                            <?php if ($record['doctor_name']): ?>
                                <i class="fas fa-user-md mr-1"></i><?php echo htmlspecialchars($record['doctor_name']); ?>
                            <?php endif; ?>
                            <?php if ($record['clinic_hospital']): ?>
                                <span class="ml-3"><i class="fas fa-hospital mr-1"></i><?php echo htmlspecialchars($record['clinic_hospital']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($record['follow_up_date']): ?>
                        <span class="text-orange-600">
                            <i class="fas fa-calendar-check mr-1"></i>Follow-up: <?php echo date('M j, Y', strtotime($record['follow_up_date'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Educational Background Card -->
        <?php if ($employee['highest_education'] || $employee['field_of_study'] || $employee['school_university']): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-graduation-cap text-teal-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Educational Background</h3>
                    <p class="text-gray-600 text-sm">Academic qualifications and achievements</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($employee['highest_education']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-certificate text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Highest Education</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['highest_education']); ?></p>
                    </div>
            </div>
            <?php endif; ?>
            
                <?php if ($employee['field_of_study']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-book text-green-600"></i>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Field of Study</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['field_of_study']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['school_university']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-university text-green-600"></i>
            </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">School/University</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['school_university']); ?></p>
        </div>
    </div>
                <?php endif; ?>
                
                <?php if ($employee['year_graduated']): ?>
                <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-calendar-check text-green-600"></i>
        </div>
        <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Year Graduated</label>
                        <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($employee['year_graduated']); ?></p>
        </div>
    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        </div>
        
    <!-- Right Column - Quick Info & Actions -->
    <div class="space-y-6">
        <!-- Quick Info Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-info-circle text-indigo-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Quick Info</h3>
                    <p class="text-gray-600 text-sm">Key details at a glance</p>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Employee ID</span>
                    <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($employee['id']); ?></span>
        </div>
        
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Employment Status</span>
                    <?php 
                    $employment_display = '';
                    $status_badge_class = 'bg-green-100 text-green-800';
                    
                    if ($employee['employment_status'] && $employee['employment_type']) {
                        // Format as "Full-time Regular" instead of "Full Time Regular"
                        $status = str_replace(' ', '-', strtolower($employee['employment_status']));
                        $employment_display = ucfirst($status) . ' ' . $employee['employment_type'];
                    } else if ($employee['employment_type']) {
                        $employment_display = $employee['employment_type'];
                    } else if ($employee['employment_status']) {
                        $employment_display = $employee['employment_status'];
                    } else {
                        $employment_display = $employee['is_active'] ? 'Active' : 'Inactive';
                    }
                    
                    // Set badge color based on employment type
                    if (strpos($employment_display, 'Regular') !== false) {
                        $status_badge_class = 'bg-green-100 text-green-800';
                    } else if (strpos($employment_display, 'Probationary') !== false) {
                        $status_badge_class = 'bg-blue-100 text-blue-800';
                    } else if (strpos($employment_display, 'Contractual') !== false) {
                        $status_badge_class = 'bg-orange-100 text-orange-800';
                    } else if (strpos($employment_display, 'Part Time') !== false) {
                        $status_badge_class = 'bg-purple-100 text-purple-800';
                    } else if (strpos($employment_display, 'Visiting') !== false) {
                        $status_badge_class = 'bg-yellow-100 text-yellow-800';
                    }
                    ?>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $status_badge_class; ?>">
                        <?php echo htmlspecialchars($employment_display); ?>
                </span>
            </div>
            
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Member Since</span>
                    <span class="text-sm font-bold text-gray-900">
                        <?php echo date('M Y', strtotime($employee['created_at'])); ?>
                </span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Employee Type</span>
                    <span class="text-sm font-bold text-gray-900"><?php echo ucfirst($employee['employee_type']); ?></span>
                </div>
                
                <?php if ($employee['employment_type'] || $employee['employment_status']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Employment Type</span>
                    <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($employee['employment_type'] ?? 'Not specified'); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['employment_status']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Employment Status</span>
                    <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($employee['employment_status']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Government IDs Card -->
        <?php if ($employee['tin_number'] || $employee['sss_number'] || $employee['philhealth_number'] || $employee['pagibig_number']): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-id-card text-yellow-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Government IDs</h3>
                    <p class="text-gray-600 text-sm">Official identification numbers</p>
    </div>
</div>

            <div class="space-y-3">
                <?php if ($employee['tin_number']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">TIN</span>
                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($employee['tin_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['sss_number']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">SSS</span>
                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($employee['sss_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['philhealth_number']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">PhilHealth</span>
                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($employee['philhealth_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['pagibig_number']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">PAG-IBIG</span>
                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($employee['pagibig_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Emergency Contact Card -->
        <?php if ($employee['emergency_contact_name'] || $employee['emergency_contact_number']): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-phone text-red-600 text-lg"></i>
        </div>
        <div>
                    <h3 class="text-xl font-bold text-gray-900">Emergency Contact</h3>
                    <p class="text-gray-600 text-sm">Emergency contact information</p>
        </div>
    </div>
    
            <div class="space-y-3">
                <?php if ($employee['emergency_contact_name']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Contact Name</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['emergency_contact_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['emergency_contact_number']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Contact Number</span>
                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($employee['emergency_contact_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['emergency_contact_relationship']): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Relationship</span>
                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['emergency_contact_relationship']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-cogs text-gray-600 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Actions</h3>
                    <p class="text-gray-600 text-sm">Available actions for this employee</p>
                </div>
            </div>
            
            <div class="space-y-3">
                <a href="edit-employee.php?id=<?php echo urlencode($encrypted_id); ?>" 
                   class="w-full flex items-center justify-center px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                    <i class="fas fa-edit mr-2"></i>Edit Employee Details
                </a>
                
                <button onclick="window.print()" 
                        class="w-full flex items-center justify-center px-4 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
                    <i class="fas fa-print mr-2"></i>Print Profile
                </button>
                
                <a href="admin-employee.php" 
                   class="w-full flex items-center justify-center px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-medium">
                    <i class="fas fa-list mr-2"></i>Back to Employee List
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add Medical History Modal -->
<div id="addMedicalHistoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-plus-circle text-red-600 mr-2"></i>Add Medical History Record
            </h3>
            <button onclick="closeAddMedicalHistoryModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="addMedicalHistoryForm" class="space-y-4">
            <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Record Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar text-red-500 mr-1"></i>Record Date *
                    </label>
                    <input type="date" name="record_date" required 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Record Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tags text-red-500 mr-1"></i>Record Type *
                    </label>
                    <select name="record_type" required 
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="checkup">Checkup</option>
                        <option value="diagnosis">Diagnosis</option>
                        <option value="treatment">Treatment</option>
                        <option value="vaccination">Vaccination</option>
                        <option value="lab_test">Lab Test</option>
                        <option value="consultation">Consultation</option>
                        <option value="emergency">Emergency</option>
                        <option value="follow_up">Follow-up</option>
                    </select>
                </div>

                <!-- Chief Complaint -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment-medical text-red-500 mr-1"></i>Chief Complaint
                    </label>
                    <input type="text" name="chief_complaint" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Main reason for visit">
                </div>

                <!-- Diagnosis -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-stethoscope text-red-500 mr-1"></i>Diagnosis
                    </label>
                    <textarea name="diagnosis" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Medical diagnosis"></textarea>
                </div>

                <!-- Treatment -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-procedures text-red-500 mr-1"></i>Treatment
                    </label>
                    <textarea name="treatment" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Treatment provided"></textarea>
                </div>

                <!-- Medication -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-pills text-red-500 mr-1"></i>Medication Prescribed
                    </label>
                    <textarea name="medication_prescribed" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Medications and dosage"></textarea>
                </div>

                <!-- Vital Signs Section -->
                <div class="md:col-span-2 border-t pt-4">
                    <h4 class="font-semibold text-gray-700 mb-3">
                        <i class="fas fa-heartbeat text-red-500 mr-2"></i>Vital Signs
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Blood Pressure</label>
                            <input type="text" name="blood_pressure" placeholder="120/80"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" placeholder="72"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" placeholder="36.5"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Respiratory Rate</label>
                            <input type="number" name="respiratory_rate" placeholder="16"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" placeholder="65"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Height (cm)</label>
                            <input type="number" name="height" placeholder="165"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                    </div>
                </div>

                <!-- Doctor Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-md text-red-500 mr-1"></i>Doctor Name
                    </label>
                    <input type="text" name="doctor_name" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Dr. Juan Dela Cruz">
                </div>

                <!-- Clinic/Hospital -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-hospital text-red-500 mr-1"></i>Clinic/Hospital
                    </label>
                    <input type="text" name="clinic_hospital" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="NIA Health Center">
                </div>

                <!-- Lab Results -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-vial text-red-500 mr-1"></i>Lab Results
                    </label>
                    <textarea name="lab_results" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Laboratory test results"></textarea>
                </div>

                <!-- Follow-up Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-check text-red-500 mr-1"></i>Follow-up Date
                    </label>
                    <input type="date" name="follow_up_date" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-notes-medical text-red-500 mr-1"></i>Additional Notes
                    </label>
                    <textarea name="notes" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Any additional observations or notes"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="closeAddMedicalHistoryModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Open Add Medical History Modal
function openAddMedicalHistoryModal() {
    document.getElementById('addMedicalHistoryModal').classList.remove('hidden');
    // Set default date to today
    document.querySelector('[name="record_date"]').value = new Date().toISOString().split('T')[0];
}

// Close Add Medical History Modal
function closeAddMedicalHistoryModal() {
    document.getElementById('addMedicalHistoryModal').classList.add('hidden');
    document.getElementById('addMedicalHistoryForm').reset();
}

// Handle form submission
document.getElementById('addMedicalHistoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    fetch('add-medical-history.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Medical history record added successfully!');
            // Reload page to show new record
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error adding medical history record. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});
</script>

</body>
</html>
