<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
//require_once '../includes/unified-error-handler.php';


// Check database connection
if (!$conn || mysqli_connect_error()) {
    if (headers_sent()) {
        echo '<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                <h2>Database Connection Error</h2>
                <p>Unable to connect to the database. Please try refreshing the page or contact support if the problem persists.</p>
              </div>';
        exit();
    } else {
        die('<div style="background: #fee; border: 1px solid #fcc; padding: 20px; margin: 20px; border-radius: 5px; color: #c33;">
                <h2>Database Connection Error</h2>
                <p>Unable to connect to the database. Please contact support.</p>
             </div>');
    }
}

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
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

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize basic employee data
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $middle_name = sanitize_input($_POST['middle_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $emp_id = sanitize_input($_POST['employee_id'] ?? '');
    $hire_date = sanitize_input($_POST['hire_date'] ?? '');
    $position = sanitize_input($_POST['position'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $employee_type = sanitize_input($_POST['employee_type'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $password = $_POST['password'] ?? '';

    // Personal Information
    $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
    $gender = sanitize_input($_POST['gender'] ?? '');
    $civil_status = sanitize_input($_POST['civil_status'] ?? '');
    $nationality = sanitize_input($_POST['nationality'] ?? 'Filipino');
    $religion = sanitize_input($_POST['religion'] ?? '');
    $blood_type = sanitize_input($_POST['blood_type'] ?? '');

    // Employment Details
    $employment_type = sanitize_input($_POST['employment_type'] ?? 'Full-time');
    $job_level = sanitize_input($_POST['job_level'] ?? 'Entry Level');
    $immediate_supervisor = sanitize_input($_POST['immediate_supervisor'] ?? '');
    $work_schedule = sanitize_input($_POST['work_schedule'] ?? 'Regular');

    // Compensation Details
    $basic_salary = $_POST['basic_salary'] ? (float)$_POST['basic_salary'] : null;
    $salary_grade = sanitize_input($_POST['salary_grade'] ?? '');
    $step_increment = (int)($_POST['step_increment'] ?? 1);
    $allowances = $_POST['allowances'] ? (float)$_POST['allowances'] : 0.00;
    $pay_schedule = sanitize_input($_POST['pay_schedule'] ?? 'Monthly');
    $salary_structure_id = $_POST['salary_structure_id'] ? (int)$_POST['salary_structure_id'] : null;
    $bank_account_number = sanitize_input($_POST['bank_account_number'] ?? '');
    $bank_name = sanitize_input($_POST['bank_name'] ?? '');

    // Government IDs and Benefits
    $tin_number = sanitize_input($_POST['tin_number'] ?? '');
    $sss_number = sanitize_input($_POST['sss_number'] ?? '');
    $philhealth_number = sanitize_input($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitize_input($_POST['pagibig_number'] ?? '');
    $umid_number = sanitize_input($_POST['umid_number'] ?? '');
    $drivers_license = sanitize_input($_POST['drivers_license'] ?? '');

    // Professional Licenses
    $prc_license_number = sanitize_input($_POST['prc_license_number'] ?? '');
    $prc_license_expiry = sanitize_input($_POST['prc_license_expiry'] ?? '');
    $prc_profession = sanitize_input($_POST['prc_profession'] ?? '');

    // Educational Background
    $highest_education = sanitize_input($_POST['highest_education'] ?? '');
    $field_of_study = sanitize_input($_POST['field_of_study'] ?? '');
    $school_university = sanitize_input($_POST['school_university'] ?? '');
    $year_graduated = $_POST['year_graduated'] ? (int)$_POST['year_graduated'] : null;
    $honors_awards = sanitize_input($_POST['honors_awards'] ?? '');

    // Emergency Contact
    $emergency_contact_name = sanitize_input($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = sanitize_input($_POST['emergency_contact_number'] ?? '');
    $emergency_contact_relationship = sanitize_input($_POST['emergency_contact_relationship'] ?? '');

    // Additional Information
    $languages_spoken = sanitize_input($_POST['languages_spoken'] ?? 'Filipino, English');
    $skills_competencies = sanitize_input($_POST['skills_competencies'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Handle photo upload
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Invalid file type. Only JPG, PNG, and GIF files are allowed.';
        } else {
            // Validate file size (2MB limit)
            $max_size = 2 * 1024 * 1024; // 2MB in bytes
            if ($file['size'] > $max_size) {
                $error = 'File size must be less than 2MB';
            } else {
                // Generate unique filename
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $unique_filename = 'employee_' . $employee_id . '_' . time() . '.' . $file_extension;
                
                // Set upload directory
                $upload_dir = '../uploads/employee_photos/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $error = 'Failed to create upload directory';
                    }
                }
                
                if (empty($error)) {
                    $upload_path = $upload_dir . $unique_filename;
                    $profile_photo = 'uploads/employee_photos/' . $unique_filename;
                    
                    // Move uploaded file
                    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $error = 'Failed to upload photo';
                        $profile_photo = null;
                    }
                }
            }
        }
    }

    // Validate required fields
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'employee_id' => 'Employee ID',
        'hire_date' => 'Date of Hire',
        'position' => 'Position',
        'department' => 'Department',
        'employee_type' => 'Employee Type'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($$field)) {
            $missing_fields[] = $label;
        }
    }

    if (!empty($missing_fields)) {
        $error = 'The following fields are required: ' . implode(', ', $missing_fields);
    } else {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if email already exists (excluding current employee)
            $check_email_query = "SELECT id FROM employees WHERE email = ? AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_email_query);
            mysqli_stmt_bind_param($check_stmt, "si", $email, $employee_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $error = 'Email address already exists';
                mysqli_stmt_close($check_stmt);
            } else {
                mysqli_stmt_close($check_stmt);
                // Check if employee ID already exists (excluding current employee)
                $check_emp_id_query = "SELECT id FROM employees WHERE employee_id = ? AND id != ?";
                $check_emp_stmt = mysqli_prepare($conn, $check_emp_id_query);
                mysqli_stmt_bind_param($check_emp_stmt, "si", $emp_id, $employee_id);
                mysqli_stmt_execute($check_emp_stmt);
                $check_emp_result = mysqli_stmt_get_result($check_emp_stmt);

                if (mysqli_num_rows($check_emp_result) > 0) {
                    $error = 'Employee ID already exists';
                    mysqli_stmt_close($check_emp_stmt);
                } else {
                    mysqli_stmt_close($check_emp_stmt);
                    // Start transaction for updating both tables
                    mysqli_autocommit($conn, false);
                    
                    try {
                        // Update employee record (basic information)
                        $update_employee_query = "UPDATE employees SET 
                            first_name = ?, 
                            last_name = ?, 
                            email = ?, 
                            phone = ?, 
                            address = ?, 
                            employee_id = ?, 
                            hire_date = ?, 
                            position = ?, 
                            department = ?, 
                            employee_type = ?, 
                            is_active = ?,
                            updated_at = NOW()";

                        // Add password update if provided
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $update_employee_query .= ", password = ?";
                        }

                        $update_employee_query .= " WHERE id = ?";

                        $update_employee_stmt = mysqli_prepare($conn, $update_employee_query);
                        
                        if (!empty($password)) {
                            mysqli_stmt_bind_param($update_employee_stmt, "ssssssssssissi", 
                                $first_name, $last_name, $email, $phone, $address, $emp_id, 
                                $hire_date, $position, $department, $employee_type, $is_active, 
                                $hashed_password, $employee_id);
                        } else {
                            mysqli_stmt_bind_param($update_employee_stmt, "ssssssssssii", 
                                $first_name, $last_name, $email, $phone, $address, $emp_id, 
                                $hire_date, $position, $department, $employee_type, $is_active, 
                                $employee_id);
                        }

                        if (!mysqli_stmt_execute($update_employee_stmt)) {
                            throw new Exception('Error updating employee basic information');
                        }
                        mysqli_stmt_close($update_employee_stmt);

                        // Update or insert employee_details record (comprehensive HR information)
                        $update_details_query = "INSERT INTO employee_details (
                            employee_id, middle_name, date_of_birth, gender, civil_status, nationality, religion,
                            emergency_contact_name, emergency_contact_number, emergency_contact_relationship,
                            employment_type, job_level, immediate_supervisor, work_schedule,
                            basic_salary, salary_grade, step_increment, allowances, pay_schedule, salary_structure_id,
                            bank_account_number, bank_name,
                            tin_number, sss_number, philhealth_number, pagibig_number, umid_number, drivers_license,
                            prc_license_number, prc_license_expiry, prc_profession,
                            highest_education, field_of_study, school_university, year_graduated, honors_awards,
                            blood_type, languages_spoken, skills_competencies, notes, profile_photo, updated_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            middle_name = VALUES(middle_name),
                            date_of_birth = VALUES(date_of_birth),
                            gender = VALUES(gender),
                            civil_status = VALUES(civil_status),
                            nationality = VALUES(nationality),
                            religion = VALUES(religion),
                            emergency_contact_name = VALUES(emergency_contact_name),
                            emergency_contact_number = VALUES(emergency_contact_number),
                            emergency_contact_relationship = VALUES(emergency_contact_relationship),
                            employment_type = VALUES(employment_type),
                            job_level = VALUES(job_level),
                            immediate_supervisor = VALUES(immediate_supervisor),
                            work_schedule = VALUES(work_schedule),
                            basic_salary = VALUES(basic_salary),
                            salary_grade = VALUES(salary_grade),
                            step_increment = VALUES(step_increment),
                            allowances = VALUES(allowances),
                            pay_schedule = VALUES(pay_schedule),
                            salary_structure_id = VALUES(salary_structure_id),
                            bank_account_number = VALUES(bank_account_number),
                            bank_name = VALUES(bank_name),
                            tin_number = VALUES(tin_number),
                            sss_number = VALUES(sss_number),
                            philhealth_number = VALUES(philhealth_number),
                            pagibig_number = VALUES(pagibig_number),
                            umid_number = VALUES(umid_number),
                            drivers_license = VALUES(drivers_license),
                            prc_license_number = VALUES(prc_license_number),
                            prc_license_expiry = VALUES(prc_license_expiry),
                            prc_profession = VALUES(prc_profession),
                            highest_education = VALUES(highest_education),
                            field_of_study = VALUES(field_of_study),
                            school_university = VALUES(school_university),
                            year_graduated = VALUES(year_graduated),
                            honors_awards = VALUES(honors_awards),
                            blood_type = VALUES(blood_type),
                            languages_spoken = VALUES(languages_spoken),
                            skills_competencies = VALUES(skills_competencies),
                            notes = VALUES(notes),
                            profile_photo = VALUES(profile_photo),
                            updated_by = VALUES(updated_by),
                            updated_at = NOW()";

                        $update_details_stmt = mysqli_prepare($conn, $update_details_query);
                        
                        mysqli_stmt_bind_param($update_details_stmt, 'isssssssssssssdsidsissssssssssssssissssssi',
                            $employee_id, $middle_name, $date_of_birth, $gender, $civil_status, $nationality, $religion,
                            $emergency_contact_name, $emergency_contact_number, $emergency_contact_relationship,
                            $employment_type, $job_level, $immediate_supervisor, $work_schedule,
                            $basic_salary, $salary_grade, $step_increment, $allowances, $pay_schedule, $salary_structure_id,
                            $bank_account_number, $bank_name,
                            $tin_number, $sss_number, $philhealth_number, $pagibig_number, $umid_number, $drivers_license,
                            $prc_license_number, $prc_license_expiry, $prc_profession,
                            $highest_education, $field_of_study, $school_university, $year_graduated, $honors_awards,
                            $blood_type, $languages_spoken, $skills_competencies, $notes, $profile_photo, $_SESSION['user_id']);

                        if (!mysqli_stmt_execute($update_details_stmt)) {
                            throw new Exception('Error updating employee details');
                        }
                        mysqli_stmt_close($update_details_stmt);

                        // Commit transaction
                        mysqli_commit($conn);
                        
                        $message = 'Employee information updated successfully with comprehensive HR details!';
                        
                        // Redirect to prevent form resubmission
                        header("Location: edit-employee.php?id=" . urlencode($encrypted_id) . "&success=1");
                        exit();
                        
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        mysqli_rollback($conn);
                        $error = 'Error updating employee information: ' . $e->getMessage();
                    } finally {
                        // Restore autocommit
                        mysqli_autocommit($conn, true);
                    }
                }
            }
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Employee information updated successfully!';
}

// Get employee details with comprehensive HR information
$employee_query = "SELECT e.*, 
                          ed.*,
                          d.name as department_name, 
                          d.icon as department_icon, 
                          d.color_theme as department_color
                   FROM employees e 
                   LEFT JOIN employee_details ed ON e.id = ed.employee_id
                   LEFT JOIN departments d ON e.department = d.name 
                   WHERE e.id = ?";

$employee_stmt = mysqli_prepare($conn, $employee_query);
if ($employee_stmt) {
    mysqli_stmt_bind_param($employee_stmt, "i", $employee_id);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
    
    if ($employee_result && $employee = mysqli_fetch_assoc($employee_result)) {
        $page_title = 'Edit Employee - ' . $employee['first_name'] . ' ' . $employee['last_name'];
    } else {
        header('Location: admin-employee.php');
        exit();
    }
    mysqli_stmt_close($employee_stmt);
} else {
    header('Location: admin-employee.php');
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
            <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
            <p class="text-gray-600">Update employee information for <?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="admin-employee.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Employees
            </a>
            <a href="view-employee.php?id=<?php echo urlencode($encrypted_id); ?>" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-eye mr-2"></i>View Employee
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($message)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Employee Profile Header -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white">
        <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center text-white text-2xl font-bold backdrop-blur-sm">
                    <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold mb-1"><?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?></h2>
                <p class="text-lg opacity-90"><?php echo htmlspecialchars($employee['position'] ?? ''); ?></p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="text-sm"><?php echo htmlspecialchars($employee['department'] ?? ''); ?></span>
                    <span class="px-2 py-1 text-xs rounded-full font-semibold <?php echo $employee['is_active'] ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100'; ?>">
                        <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-75">Employee ID</p>
                <p class="text-lg font-mono font-bold"><?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Edit Form -->
<form method="POST" class="space-y-6" enctype="multipart/form-data">
    <!-- Personal Information Section -->
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($employee['middle_name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($employee['date_of_birth'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                <select name="gender" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo ($employee['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($employee['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($employee['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                <select name="civil_status" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Status</option>
                    <option value="Single" <?php echo ($employee['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?php echo ($employee['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Widowed" <?php echo ($employee['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    <option value="Divorced" <?php echo ($employee['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                    <option value="Separated" <?php echo ($employee['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type</label>
                <select name="blood_type" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Blood Type</option>
                    <option value="A+" <?php echo ($employee['blood_type'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo ($employee['blood_type'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo ($employee['blood_type'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo ($employee['blood_type'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo ($employee['blood_type'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo ($employee['blood_type'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo ($employee['blood_type'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo ($employee['blood_type'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                </select>
            </div>
            
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Complete Address</label>
                <textarea name="address" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="Complete address"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Employment Information Section -->
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID <span class="text-red-500">*</span></label>
                <input type="text" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position/Title <span class="text-red-500">*</span></label>
                <select name="position" id="position_select" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all bg-white shadow-sm"
                        onchange="filterSalaryStructures()">
                    <option value="">Select Position</option>
                    <?php 
                    // Get unique positions from salary structures
                    $unique_positions = [];
                    foreach ($salary_structures as $structure) {
                        if (!in_array($structure['position_title'], $unique_positions)) {
                            $unique_positions[] = $structure['position_title'];
                        }
                    }
                    foreach ($unique_positions as $position): ?>
                        <option value="<?php echo htmlspecialchars($position); ?>" <?php echo $employee['position'] === $position ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($position); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="custom" <?php echo !in_array($employee['position'] ?? '', $unique_positions) && !empty($employee['position']) ? 'selected' : ''; ?>>Custom Position</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Select from predefined positions or choose custom</p>
                
                <input type="text" name="position_custom" id="position_custom"
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all bg-white shadow-sm mt-2 <?php echo !in_array($employee['position'] ?? '', $unique_positions) && !empty($employee['position']) ? '' : 'hidden'; ?>"
                       placeholder="Enter custom position"
                       value="<?php echo !in_array($employee['position'] ?? '', $unique_positions) && !empty($employee['position']) ? htmlspecialchars($employee['position']) : ''; ?>">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Hire <span class="text-red-500">*</span></label>
                <input type="date" name="hire_date" id="date_of_hire" value="<?php echo htmlspecialchars($employee['hire_date'] ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       onchange="calculateStepIncrement()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department <span class="text-red-500">*</span></label>
                <select name="department" required class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $employee['department'] === $dept ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Structure</label>
                <select name="salary_structure_id" id="salary_structure_select"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all bg-white shadow-sm"
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
                                data-increment-frequency="<?php echo $structure['incrementation_frequency_years']; ?>"
                                <?php echo isset($employee['salary_structure_id']) && $employee['salary_structure_id'] == $structure['id'] ? 'selected' : ''; ?>>
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
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type <span class="text-red-500">*</span></label>
                <select name="employee_type" required class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Type</option>
                    <option value="Staff" <?php echo $employee['employee_type'] === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="Admin" <?php echo $employee['employee_type'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="Nurse" <?php echo $employee['employee_type'] === 'Nurse' ? 'selected' : ''; ?>>Nurse</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                <select name="employment_type" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="Permanent" <?php echo ($employee['employment_type'] ?? 'Permanent') === 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                    <option value="Casual/Project" <?php echo ($employee['employment_type'] ?? '') === 'Casual/Project' ? 'selected' : ''; ?>>Casual/Project</option>
                    <option value="Casual Subsidy" <?php echo ($employee['employment_type'] ?? '') === 'Casual Subsidy' ? 'selected' : ''; ?>>Casual Subsidy</option>
                    <option value="Job Order" <?php echo ($employee['employment_type'] ?? '') === 'Job Order' ? 'selected' : ''; ?>>Job Order</option>
                    <option value="Contract of Service" <?php echo ($employee['employment_type'] ?? '') === 'Contract of Service' ? 'selected' : ''; ?>>Contract of Service</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Job Level</label>
                <select name="job_level" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="Entry Level" <?php echo ($employee['job_level'] ?? 'Entry Level') === 'Entry Level' ? 'selected' : ''; ?>>Entry Level</option>
                    <option value="Associate" <?php echo ($employee['job_level'] ?? '') === 'Associate' ? 'selected' : ''; ?>>Associate</option>
                    <option value="Senior" <?php echo ($employee['job_level'] ?? '') === 'Senior' ? 'selected' : ''; ?>>Senior</option>
                    <option value="Supervisor" <?php echo ($employee['job_level'] ?? '') === 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="Manager" <?php echo ($employee['job_level'] ?? '') === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="Director" <?php echo ($employee['job_level'] ?? '') === 'Director' ? 'selected' : ''; ?>>Director</option>
                    <option value="Executive" <?php echo ($employee['job_level'] ?? '') === 'Executive' ? 'selected' : ''; ?>>Executive</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Work Schedule</label>
                <select name="work_schedule" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="Regular" <?php echo ($employee['work_schedule'] ?? 'Regular') === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                    <option value="Shifting" <?php echo ($employee['work_schedule'] ?? '') === 'Shifting' ? 'selected' : ''; ?>>Shifting</option>
                    <option value="Flexible" <?php echo ($employee['work_schedule'] ?? '') === 'Flexible' ? 'selected' : ''; ?>>Flexible</option>
                    <option value="Remote" <?php echo ($employee['work_schedule'] ?? '') === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                    <option value="Hybrid" <?php echo ($employee['work_schedule'] ?? '') === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Immediate Supervisor</label>
                <input type="text" name="immediate_supervisor" value="<?php echo htmlspecialchars($employee['immediate_supervisor'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="is_active" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="1" <?php echo $employee['is_active'] ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo !$employee['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Compensation Details Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                       placeholder="Enter basic salary" value="<?php echo htmlspecialchars($employee['basic_salary'] ?? ''); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Grade</label>
                <input type="text" name="salary_grade" id="salary_grade"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., SG-11" readonly value="<?php echo htmlspecialchars($employee['salary_grade'] ?? ''); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Step Increment</label>
                <input type="number" name="step_increment" id="step_increment" min="1" max="8" placeholder="1"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       value="<?php echo htmlspecialchars($employee['step_increment'] ?? '1'); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Allowances</label>
                <input type="number" name="allowances" id="allowances" step="0.01" min="0" value="0"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter allowances" value="<?php echo htmlspecialchars($employee['allowances'] ?? '0'); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate (Hourly)</label>
                <input type="number" name="overtime_rate" id="overtime_rate" step="0.01" min="0"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter overtime rate" value="<?php echo htmlspecialchars($employee['overtime_rate'] ?? ''); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pay Schedule</label>
                <select name="pay_schedule" id="pay_schedule" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Pay Schedule</option>
                    <option value="Monthly" <?php echo ($employee['pay_schedule'] ?? 'Monthly') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                    <option value="Bi-monthly" <?php echo ($employee['pay_schedule'] ?? '') === 'Bi-monthly' ? 'selected' : ''; ?>>Bi-monthly</option>
                    <option value="Quincena" <?php echo ($employee['pay_schedule'] ?? '') === 'Quincena' ? 'selected' : ''; ?>>Quincena</option>
                    <option value="Weekly" <?php echo ($employee['pay_schedule'] ?? '') === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                    <option value="Daily" <?php echo ($employee['pay_schedule'] ?? '') === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                </select>
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter bank name" value="<?php echo htmlspecialchars($employee['bank_name'] ?? ''); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Account Number</label>
                <input type="text" name="bank_account_number" id="bank_account_number"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter bank account number" value="<?php echo htmlspecialchars($employee['bank_account_number'] ?? ''); ?>">
                <div class="error-message text-red-500 text-sm mt-1 hidden"></div>
            </div>
        </div>
    </div>

    <!-- Government IDs Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                <input type="text" name="tin_number" value="<?php echo htmlspecialchars($employee['tin_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxx-xxx-xxx-xxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SSS Number</label>
                <input type="text" name="sss_number" value="<?php echo htmlspecialchars($employee['sss_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xx-xxxxxxx-x">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Number</label>
                <input type="text" name="philhealth_number" value="<?php echo htmlspecialchars($employee['philhealth_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxxx-xxxx-xxxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PAG-IBIG (HDMF) Number</label>
                <input type="text" name="pagibig_number" value="<?php echo htmlspecialchars($employee['pagibig_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="xxxx-xxxx-xxxx">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">UMID Number</label>
                <input type="text" name="umid_number" value="<?php echo htmlspecialchars($employee['umid_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Driver's License</label>
                <input type="text" name="drivers_license" value="<?php echo htmlspecialchars($employee['drivers_license'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
        </div>
    </div>

    <!-- Professional Licenses Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                <input type="text" name="prc_license_number" value="<?php echo htmlspecialchars($employee['prc_license_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PRC Profession</label>
                <input type="text" name="prc_profession" value="<?php echo htmlspecialchars($employee['prc_profession'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Licensed Engineer">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PRC License Expiry</label>
                <input type="date" name="prc_license_expiry" value="<?php echo htmlspecialchars($employee['prc_license_expiry'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
        </div>
    </div>

    <!-- Educational Background Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                    <option value="Elementary" <?php echo ($employee['highest_education'] ?? '') === 'Elementary' ? 'selected' : ''; ?>>Elementary</option>
                    <option value="High School" <?php echo ($employee['highest_education'] ?? '') === 'High School' ? 'selected' : ''; ?>>High School</option>
                    <option value="Vocational" <?php echo ($employee['highest_education'] ?? '') === 'Vocational' ? 'selected' : ''; ?>>Vocational</option>
                    <option value="Associate Degree" <?php echo ($employee['highest_education'] ?? '') === 'Associate Degree' ? 'selected' : ''; ?>>Associate Degree</option>
                    <option value="Bachelor's Degree" <?php echo ($employee['highest_education'] ?? '') === "Bachelor's Degree" ? 'selected' : ''; ?>>Bachelor's Degree</option>
                    <option value="Master's Degree" <?php echo ($employee['highest_education'] ?? '') === "Master's Degree" ? 'selected' : ''; ?>>Master's Degree</option>
                    <option value="Doctorate" <?php echo ($employee['highest_education'] ?? '') === 'Doctorate' ? 'selected' : ''; ?>>Doctorate</option>
                    <option value="Post-Doctorate" <?php echo ($employee['highest_education'] ?? '') === 'Post-Doctorate' ? 'selected' : ''; ?>>Post-Doctorate</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Field of Study</label>
                <input type="text" name="field_of_study" value="<?php echo htmlspecialchars($employee['field_of_study'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Computer Science">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">School/University</label>
                <input type="text" name="school_university" value="<?php echo htmlspecialchars($employee['school_university'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year Graduated</label>
                <input type="number" name="year_graduated" min="1950" max="2030" value="<?php echo htmlspecialchars($employee['year_graduated'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., 2020">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Honors & Awards</label>
                <input type="text" name="honors_awards" value="<?php echo htmlspecialchars($employee['honors_awards'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Academic honors, awards, recognitions">
            </div>
        </div>
    </div>

    <!-- Emergency Contact Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                <input type="text" name="emergency_contact_name" value="<?php echo htmlspecialchars($employee['emergency_contact_name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                <input type="tel" name="emergency_contact_number" value="<?php echo htmlspecialchars($employee['emergency_contact_number'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Relationship</label>
                <input type="text" name="emergency_contact_relationship" value="<?php echo htmlspecialchars($employee['emergency_contact_relationship'] ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Spouse, Parent, Sibling">
            </div>
        </div>
    </div>

    <!-- Additional Information Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                <input type="text" name="languages_spoken" value="<?php echo htmlspecialchars($employee['languages_spoken'] ?? 'Filipino, English'); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Filipino, English, Spanish">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Skills & Competencies</label>
                <textarea name="skills_competencies" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="List key skills and competencies"><?php echo htmlspecialchars($employee['skills_competencies'] ?? ''); ?></textarea>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">HR Notes</label>
                <textarea name="notes" rows="3"
                          class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                          placeholder="Additional notes or comments"><?php echo htmlspecialchars($employee['notes'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Profile Photo Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
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
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center border-2 border-gray-200 overflow-hidden" id="photo-preview">
                        <?php if (!empty($employee['profile_photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/seait/' . $employee['profile_photo'])): ?>
                            <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" 
                                 alt="Current Photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-gray-400 text-lg"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                               onchange="previewPhoto(this)">
                        <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, PNG, GIF (Max: 2MB)</p>
                        <?php if (!empty($employee['profile_photo'])): ?>
                            <p class="text-xs text-green-600 mt-1">
                                <i class="fas fa-check-circle mr-1"></i>Current photo will be replaced if new file is selected
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-lock text-red-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Security Information</h3>
                <p class="text-gray-600 text-sm">Password and security settings</p>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Password Update</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Leave the password field empty to keep the current password. Only enter a new password if you want to change it.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (Optional)</label>
            <input type="password" name="password" 
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                   placeholder="Enter new password or leave blank to keep current">
        </div>
    </div>

    <!-- Form Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex flex-col sm:flex-row gap-4 justify-end">
            <a href="admin-employee.php" 
               class="w-full sm:w-auto bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium text-center">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            
            <button type="submit" 
                    class="w-full sm:w-auto bg-gradient-to-r from-green-500 to-green-600 text-white px-8 py-3 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-save mr-2"></i>Update Employee
            </button>
        </div>
    </div>
</form>

<script>
// Ensure jGrowl is available
function ensureJGrowl() {
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery not loaded - using fallback notifications');
        return false;
    }
    if (typeof $.jGrowl === 'undefined') {
        console.warn('jGrowl not loaded - using fallback notifications');
        return false;
    }
    return true;
}

// Show message function with fallback
function showMessage(type, message, duration = 5000) {
    if (!ensureJGrowl()) {
        // Fallback to browser notifications or console
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Try to show a simple alert for errors
        if (type === 'error') {
            alert(`Error: ${message}`);
        }
        return;
    }
    
    const options = {
        life: duration,
        theme: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
        sticky: type === 'error',
        position: 'top-right',
        speed: 'normal'
    };
    
    $.jGrowl(message, options);
}

// Helper function to safely get element
function getElementSafely(elementId) {
    const element = document.getElementById(elementId);
    if (!element) {
        console.warn(`Element with id '${elementId}' not found`);
    }
    return element;
}

// Helper function to safely query selector
function querySelectorSafely(selector, context = document) {
    try {
        const element = context.querySelector(selector);
        if (!element) {
            console.warn(`Element with selector '${selector}' not found`);
        }
        return element;
    } catch (error) {
        console.error(`Error querying selector '${selector}':`, error);
        return null;
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = querySelectorSafely('form');
    if (!form) {
        console.error('Form not found - validation will not work');
        return;
    }
    
    form.addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('input[required], select[required]');
    let hasErrors = false;
    
    // Clear previous error styling
    requiredFields.forEach(field => {
        field.classList.remove('border-red-500');
    });
    
    // Validate required fields
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            hasErrors = true;
        }
    });
    
    // Validate email format
    const emailField = querySelectorSafely('input[name="email"]', this);
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            emailField.classList.add('border-red-500');
            hasErrors = true;
            showMessage('error', 'Please enter a valid email address');
        }
    }
    
    // Validate phone number
    const phoneField = querySelectorSafely('input[name="phone"]', this);
    if (phoneField && phoneField.value) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(phoneField.value)) {
            phoneField.classList.add('border-red-500');
            hasErrors = true;
            showMessage('error', 'Please enter a valid phone number');
        }
    }
    
    // Validate date
    const dateField = querySelectorSafely('input[name="hire_date"]', this);
    if (dateField && dateField.value) {
        const hireDate = new Date(dateField.value);
        const today = new Date();
        if (hireDate > today) {
            dateField.classList.add('border-red-500');
            hasErrors = true;
            showMessage('error', 'Hire date cannot be in the future');
        }
    }
    
    if (hasErrors) {
        e.preventDefault();
        showMessage('error', 'Please correct the highlighted fields before submitting');
        return false;
    }
    
    // Show loading state
    const submitBtn = querySelectorSafely('button[type="submit"]', this);
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
    }
    });

    // Real-time validation
    const formElement = querySelectorSafely('form');
    if (formElement) {
        const inputs = formElement.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                // Remove error styling when user starts typing
                this.classList.remove('border-red-500');
            });
        });
    }
});

function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    
    // Remove previous error styling
    field.classList.remove('border-red-500');
    
    // Check required fields
    if (isRequired && !value) {
        field.classList.add('border-red-500');
        return false;
    }
    
    // Validate email
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            field.classList.add('border-red-500');
            return false;
        }
    }
    
    // Validate phone
    if (field.name === 'phone' && value) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            field.classList.add('border-red-500');
            return false;
        }
    }
    
    // Validate date
    if (field.type === 'date' && value) {
        const inputDate = new Date(value);
        const today = new Date();
        if (inputDate > today) {
            field.classList.add('border-red-500');
            return false;
        }
    }
    
    return true;
}

    // Auto-save draft functionality (optional)
    let autoSaveTimeout;
    function autoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            console.log('Auto-save triggered (placeholder for future implementation)');
            // Could implement auto-save to localStorage here
        }, 30000);
    }

    // Attach auto-save to form inputs
    const allInputs = document.querySelectorAll('input, select, textarea');
    allInputs.forEach(element => {
        element.addEventListener('input', autoSave);
    });

// Salary structure integration functions
function populateSalaryDetails() {
    const salaryStructureSelect = getElementSafely('salary_structure_select');
    if (!salaryStructureSelect) {
        console.error('Salary structure select not found');
        return;
    }
    
    const selectedOption = salaryStructureSelect.options[salaryStructureSelect.selectedIndex];
    
    if (selectedOption.value) {
        // Get salary data from attributes
        const baseSalary = selectedOption.getAttribute('data-base-salary');
        const grade = selectedOption.getAttribute('data-grade');
        
        // Populate salary fields ONLY - don't affect other form fields
        const basicSalaryField = getElementSafely('basic_salary');
        const salaryGradeField = getElementSafely('salary_grade');
        const stepIncrementField = getElementSafely('step_increment');
        
        if (basicSalaryField) {
            basicSalaryField.value = baseSalary;
        }
        
        if (salaryGradeField) {
            salaryGradeField.value = grade;
        }
        
        // Calculate and populate step increment
        calculateStepIncrement();
        
        // Add visual indicator that fields are auto-populated
        if (basicSalaryField) {
            basicSalaryField.classList.add('bg-green-50', 'border-green-300');
        }
        if (salaryGradeField) {
            salaryGradeField.classList.add('bg-green-50', 'border-green-300');
        }
        if (stepIncrementField) {
            stepIncrementField.classList.add('bg-green-50', 'border-green-300');
        }
        
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
        
        const hireDate = getElementSafely('date_of_hire');
        let stepIncrementInfo = '';
        if (hireDate && hireDate.value) {
            const incrementFrequency = parseInt(selectedOption.getAttribute('data-increment-frequency')) || 1;
            const hireDateObj = new Date(hireDate.value);
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
        
        const salaryStructureDetails = getElementSafely('salary_structure_details');
        const salaryStructureInfo = getElementSafely('salary_structure_info');
        
        if (salaryStructureDetails) {
            salaryStructureDetails.innerHTML = detailsHtml;
        }
        if (salaryStructureInfo) {
            salaryStructureInfo.classList.remove('hidden');
        }
        
        // Show success message
        showMessage('success', 'Salary details populated from selected structure!');
    } else {
        // Only clear salary-related fields when no structure is selected
        clearSalaryDetails();
    }
}

function filterSalaryStructures() {
    const positionSelect = getElementSafely('position_select');
    const salaryStructureSelect = getElementSafely('salary_structure_select');
    const customPositionInput = getElementSafely('position_custom');
    
    if (!positionSelect || !salaryStructureSelect) {
        console.error('Required elements not found for salary structure filtering');
        return;
    }
    
    // Show/hide custom position input
    if (positionSelect.value === 'custom') {
        if (customPositionInput) {
            customPositionInput.classList.remove('hidden');
            customPositionInput.required = true;
        }
        // Clear salary structure options
        salaryStructureSelect.innerHTML = '<option value="">Select Salary Structure</option>';
    } else {
        if (customPositionInput) {
            customPositionInput.classList.add('hidden');
            customPositionInput.required = false;
            customPositionInput.value = '';
        }
        
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
}

function calculateStepIncrement() {
    const hireDate = getElementSafely('date_of_hire');
    const salaryStructureSelect = getElementSafely('salary_structure_select');
    const stepIncrementField = getElementSafely('step_increment');
    
    if (!hireDate || !salaryStructureSelect || !stepIncrementField) {
        return;
    }
    
    const selectedOption = salaryStructureSelect.options[salaryStructureSelect.selectedIndex];
    
    if (!hireDate.value || !selectedOption.value) {
        return;
    }
    
    const incrementFrequency = parseInt(selectedOption.getAttribute('data-increment-frequency')) || 1;
    const hireDateObj = new Date(hireDate.value);
    const currentDate = new Date();
    const yearsOfService = Math.floor((currentDate - hireDateObj) / (365.25 * 24 * 60 * 60 * 1000));
    
    // Calculate step increment based on years of service and increment frequency
    const stepIncrement = Math.min(Math.floor(yearsOfService / incrementFrequency) + 1, 8);
    
    stepIncrementField.value = stepIncrement;
    
    // Update visual indicator if salary structure is selected
    if (selectedOption.value) {
        stepIncrementField.classList.add('bg-green-50', 'border-green-300');
    }
}

function clearSalaryDetails() {
    const basicSalaryField = getElementSafely('basic_salary');
    const salaryGradeField = getElementSafely('salary_grade');
    const stepIncrementField = getElementSafely('step_increment');
    
    // Only clear salary-related fields, preserve other form data
    if (basicSalaryField) {
        basicSalaryField.value = '';
        basicSalaryField.classList.remove('bg-green-50', 'border-green-300');
    }
    if (salaryGradeField) {
        salaryGradeField.value = '';
        salaryGradeField.classList.remove('bg-green-50', 'border-green-300');
    }
    if (stepIncrementField) {
        stepIncrementField.value = '';
        stepIncrementField.classList.remove('bg-green-50', 'border-green-300');
    }
    
    // Hide salary structure info
    const salaryStructureInfo = getElementSafely('salary_structure_info');
    if (salaryStructureInfo) {
        salaryStructureInfo.classList.add('hidden');
    }
    
    // Clear salary structure details
    const salaryStructureDetails = getElementSafely('salary_structure_details');
    if (salaryStructureDetails) {
        salaryStructureDetails.innerHTML = '';
    }
}

// Preview uploaded photo
function previewPhoto(input) {
    const preview = getElementSafely('photo-preview');
    if (!preview) {
        console.error('Photo preview element not found');
        return;
    }
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Check file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            showMessage('error', 'File size must be less than 2MB');
            input.value = '';
            return;
        }
        
        // Check file type
        if (!file.type.match('image.*')) {
            showMessage('error', 'Please select a valid image file');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    } else {
        // Reset to default or current photo
        <?php if (!empty($employee['profile_photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/seait/' . $employee['profile_photo'])): ?>
            preview.innerHTML = '<img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" alt="Current Photo" class="w-full h-full object-cover">';
        <?php else: ?>
            preview.innerHTML = '<i class="fas fa-user text-gray-400 text-lg"></i>';
        <?php endif; ?>
    }
}
</script>

