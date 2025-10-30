<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user is logged in and can add employees
if (!isset($_SESSION['user_id']) || !canAddEmployees()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Ensure JSON headers for all responses
    set_json_response_headers(200);
    // Validate and sanitize basic employee data
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $middle_name = sanitize_input($_POST['middle_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $employee_id = sanitize_input($_POST['employee_id'] ?? '');
    $hire_date_raw = sanitize_input($_POST['hire_date'] ?? '');
    $hire_date = null;
    if (!empty($hire_date_raw)) {
        $hire_date = date('Y-m-d', strtotime($hire_date_raw));
        if ($hire_date === '1970-01-01') {
            $hire_date = null; // Invalid date
        }
    }
    $position = sanitize_input($_POST['position'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $employee_type_raw = sanitize_input($_POST['employee_type'] ?? '');
    // Map form employee types to database enum values
    $employee_type_mapping = [
        'Regular' => 'staff',
        'Permanent' => 'staff',
        'Casual' => 'staff',
        'Contract' => 'staff',
        'Staff' => 'staff',
        'Admin' => 'admin',
        'Nurse' => 'staff'
    ];
    $employee_type = $employee_type_mapping[$employee_type_raw] ?? 'staff';
    $is_active = (int)($_POST['is_active'] ?? 1);
    $password = $_POST['password'] ?? '';

    // Personal Information
    $date_of_birth_raw = sanitize_input($_POST['date_of_birth'] ?? '');
    $date_of_birth = null;
    if (!empty($date_of_birth_raw)) {
        $date_of_birth = date('Y-m-d', strtotime($date_of_birth_raw));
        if ($date_of_birth === '1970-01-01') {
            $date_of_birth = null; // Invalid date
        }
    }
    $gender = sanitize_input($_POST['gender'] ?? '');
    $civil_status = sanitize_input($_POST['civil_status'] ?? '');
    $nationality = sanitize_input($_POST['nationality'] ?? 'Filipino');
    $religion = sanitize_input($_POST['religion'] ?? '');
    $blood_type = sanitize_input($_POST['blood_type'] ?? '');

    // Emergency Contact
    $emergency_contact_name = sanitize_input($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = sanitize_input($_POST['emergency_contact_number'] ?? '');
    $emergency_contact_relationship = sanitize_input($_POST['emergency_contact_relationship'] ?? '');

    // Employment Details
    $employment_type_raw = sanitize_input($_POST['employment_type'] ?? '');
    // Map form employment types to database enum values
    $employment_type_mapping = [
        'Permanent' => 'Full-time',
        'Casual/Project' => 'Casual',
        'Casual Subsidy' => 'Casual',
        'Job Order' => 'Contract',
        'Contract of Service' => 'Contract'
    ];
    $employment_type = $employment_type_mapping[$employment_type_raw] ?? 'Probationary';
    $employment_status = sanitize_input($_POST['employment_status'] ?? 'Full Time');
    $job_level = sanitize_input($_POST['job_level'] ?? 'Entry Level');
    $immediate_supervisor = sanitize_input($_POST['immediate_supervisor'] ?? '');
    $work_schedule = sanitize_input($_POST['work_schedule'] ?? 'Regular');
    $probation_period_months = (int)($_POST['probation_period_months'] ?? 6);

    // Compensation Details
    $basic_salary = isset($_POST['basic_salary']) && $_POST['basic_salary'] ? (float)$_POST['basic_salary'] : null;
    $salary_grade = sanitize_input($_POST['salary_grade'] ?? '');
    $step_increment = (int)($_POST['step_increment'] ?? 1);
    $allowances = isset($_POST['allowances']) && $_POST['allowances'] ? (float)$_POST['allowances'] : 0.00;
    $overtime_rate = isset($_POST['overtime_rate']) && $_POST['overtime_rate'] ? (float)$_POST['overtime_rate'] : null;
    $night_differential_rate = isset($_POST['night_differential_rate']) && $_POST['night_differential_rate'] ? (float)$_POST['night_differential_rate'] : 0.10;
    $hazard_pay = isset($_POST['hazard_pay']) && $_POST['hazard_pay'] ? (float)$_POST['hazard_pay'] : 0.00;
    $pay_schedule = sanitize_input($_POST['pay_schedule'] ?? 'Monthly');
    $salary_structure_id = isset($_POST['salary_structure_id']) && $_POST['salary_structure_id'] ? (int)$_POST['salary_structure_id'] : null;
    $bank_account_number = sanitize_input($_POST['bank_account_number'] ?? '');
    $bank_name = sanitize_input($_POST['bank_name'] ?? '');

    // Government IDs and Benefits
    $tin_number = sanitize_input($_POST['tin_number'] ?? '');
    $sss_number = sanitize_input($_POST['sss_number'] ?? '');
    $philhealth_number = sanitize_input($_POST['philhealth_number'] ?? '');
    $pagibig_number = sanitize_input($_POST['pagibig_number'] ?? '');
    $umid_number = sanitize_input($_POST['umid_number'] ?? '');
    $postal_id = sanitize_input($_POST['postal_id'] ?? '');
    $voters_id = sanitize_input($_POST['voters_id'] ?? '');
    $drivers_license = sanitize_input($_POST['drivers_license'] ?? '');
    $passport_number = sanitize_input($_POST['passport_number'] ?? '');
    $passport_expiry_raw = sanitize_input($_POST['passport_expiry'] ?? '');
    $passport_expiry = null;
    if (!empty($passport_expiry_raw)) {
        $passport_expiry = date('Y-m-d', strtotime($passport_expiry_raw));
        if ($passport_expiry === '1970-01-01') {
            $passport_expiry = null; // Invalid date
        }
    }

    // Professional Licenses
    $prc_license_number = sanitize_input($_POST['prc_license_number'] ?? '');
    $prc_license_expiry_raw = sanitize_input($_POST['prc_license_expiry'] ?? '');
    $prc_license_expiry = null;
    if (!empty($prc_license_expiry_raw)) {
        $prc_license_expiry = date('Y-m-d', strtotime($prc_license_expiry_raw));
        if ($prc_license_expiry === '1970-01-01') {
            $prc_license_expiry = null; // Invalid date
        }
    }
    $prc_profession = sanitize_input($_POST['prc_profession'] ?? '');

    // Educational Background
    $highest_education = sanitize_input($_POST['highest_education'] ?? '');
    $field_of_study = sanitize_input($_POST['field_of_study'] ?? '');
    $school_university = sanitize_input($_POST['school_university'] ?? '');
    $year_graduated = isset($_POST['year_graduated']) && $_POST['year_graduated'] ? (int)$_POST['year_graduated'] : null;
    $honors_awards = sanitize_input($_POST['honors_awards'] ?? '');

    // Additional Information
    $languages_spoken = sanitize_input($_POST['languages_spoken'] ?? 'Filipino, English');
    $skills_competencies = sanitize_input($_POST['skills_competencies'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    // Additional Professional Information
    $other_licenses = sanitize_input($_POST['other_licenses'] ?? '');
    $certifications = sanitize_input($_POST['certifications'] ?? '');
    
    // Medical Information
    $medical_conditions = sanitize_input($_POST['medical_conditions'] ?? '');
    $fitness_for_duty = sanitize_input($_POST['fitness_for_duty'] ?? 'Fit');
    $last_medical_exam_raw = sanitize_input($_POST['last_medical_exam'] ?? '');
    $last_medical_exam = null;
    if (!empty($last_medical_exam_raw)) {
        $last_medical_exam = date('Y-m-d', strtotime($last_medical_exam_raw));
        if ($last_medical_exam === '1970-01-01') {
            $last_medical_exam = null; // Invalid date
        }
    }
    
    $next_medical_exam_raw = sanitize_input($_POST['next_medical_exam'] ?? '');
    $next_medical_exam = null;
    if (!empty($next_medical_exam_raw)) {
        $next_medical_exam = date('Y-m-d', strtotime($next_medical_exam_raw));
        if ($next_medical_exam === '1970-01-01') {
            $next_medical_exam = null; // Invalid date
        }
    }
    
    // Professional References
    $references = sanitize_input($_POST['references'] ?? '');
    
    // Regularization Information
    $regularization_date_raw = sanitize_input($_POST['regularization_date'] ?? '');
    // Ensure regularization_date is properly formatted as a date or null
    $regularization_date = null;
    if (!empty($regularization_date_raw)) {
        $regularization_date = date('Y-m-d', strtotime($regularization_date_raw));
        if ($regularization_date === '1970-01-01') {
            $regularization_date = null; // Invalid date
        }
    }
    
    // Handle photo upload
    $profile_photo = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
        }
        
        // Validate file size (2MB limit)
        $max_size = 2 * 1024 * 1024; // 2MB in bytes
        if ($file['size'] > $max_size) {
            throw new Exception('File size must be less than 2MB');
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = 'employee_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_extension;
        
        // Set upload directory
        $upload_dir = '../uploads/employee_photos/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        $upload_path = $upload_dir . $unique_filename;
        $profile_photo = 'uploads/employee_photos/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload photo');
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
        'employee_type' => 'Employee Type',
        'password' => 'Password'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field => $label) {
        if (empty($$field)) {
            $missing_fields[] = $label;
        }
    }

    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false,
            'message' => 'The following fields are required: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address'
        ]);
        exit();
    }

    // Check if email already exists
    $check_email_query = "SELECT id FROM employees WHERE email = ?";
    $check_email_stmt = mysqli_prepare($conn, $check_email_query);
    mysqli_stmt_bind_param($check_email_stmt, 's', $email);
    mysqli_stmt_execute($check_email_stmt);
    $check_email_result = mysqli_stmt_get_result($check_email_stmt);

    if (mysqli_num_rows($check_email_result) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email address already exists'
        ]);
        exit();
    }

    // Check if employee ID already exists
    $check_employee_id_query = "SELECT id FROM employees WHERE employee_id = ?";
    $check_employee_id_stmt = mysqli_prepare($conn, $check_employee_id_query);
    mysqli_stmt_bind_param($check_employee_id_stmt, 's', $employee_id);
    mysqli_stmt_execute($check_employee_id_stmt);
    $check_employee_id_result = mysqli_stmt_get_result($check_employee_id_stmt);

    if (mysqli_num_rows($check_employee_id_result) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee ID already exists in the system'
        ]);
        exit();
    }

    // Validate salary structure exists for the position
    $check_salary_structure_query = "SELECT id, incrementation_amount, incrementation_frequency_years FROM salary_structures WHERE position_title = ? AND is_active = 1";
    $check_salary_structure_stmt = mysqli_prepare($conn, $check_salary_structure_query);
    mysqli_stmt_bind_param($check_salary_structure_stmt, 's', $position);
    mysqli_stmt_execute($check_salary_structure_stmt);
    $salary_structure_result = mysqli_stmt_get_result($check_salary_structure_stmt);
    
    if (mysqli_num_rows($salary_structure_result) == 0) {
        // Automatically create salary structure for the position
        $create_structure_query = "INSERT INTO salary_structures (
            position_title, department, grade_level, base_salary, minimum_salary, maximum_salary,
            incrementation_amount, incrementation_frequency_years, is_active, created_by
        ) VALUES (?, ?, 'Grade 3', 30000.00, 25000.00, 50000.00, 1000.00, 3, 1, ?)";
        
        $create_stmt = mysqli_prepare($conn, $create_structure_query);
        $user_id = $_SESSION['user_id'] ?? 1;
        mysqli_stmt_bind_param($create_stmt, 'sii', $position, $department, $user_id);
        
        if (!mysqli_stmt_execute($create_stmt)) {
            echo json_encode([
                'success' => false,
                'message' => 'Position "' . $position . '" does not have a salary structure. Please create one first in Salary Structures Management.'
            ]);
            exit();
        }
        
        // Log the automatic creation
        error_log("Auto-created salary structure for position: {$position} by user: {$user_id}");
    }

    // Normalize numeric inputs (coerce numeric strings -> proper numerics, empty -> NULL)
    $intOrNull = function($value) {
        if ($value === '' || $value === null) return null;
        return is_numeric($value) ? (int)$value : null;
    };
    $floatOrNull = function($value) {
        if ($value === '' || $value === null) return null;
        return is_numeric($value) ? (float)$value : null;
    };

    $probation_period_months = $intOrNull($probation_period_months);
    $step_increment = $intOrNull($step_increment);
    $year_graduated = $intOrNull($year_graduated);
    $salary_structure_id = $intOrNull($salary_structure_id);
    $basic_salary = $floatOrNull($basic_salary);
    $allowances = $floatOrNull($allowances);
    $overtime_rate = $floatOrNull($overtime_rate);
    $night_differential_rate = $floatOrNull($night_differential_rate);
    $hazard_pay = $floatOrNull($hazard_pay);

    // Start transaction
    mysqli_autocommit($conn, false);

    try {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into employees table (basic information)
        $insert_employee_query = "INSERT INTO employees (employee_id, first_name, last_name, email, password, position, department, employee_type, hire_date, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_employee_stmt = mysqli_prepare($conn, $insert_employee_query);
        if (!$insert_employee_stmt) {
            throw new Exception('Database prepare error for employees: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($insert_employee_stmt, 'sssssssssssi', 
            $employee_id, $first_name, $last_name, $email, $hashed_password,
            $position, $department, $employee_type, $hire_date, $phone, $address, $is_active);

        if (!mysqli_stmt_execute($insert_employee_stmt)) {
            throw new Exception('Error inserting employee: ' . mysqli_stmt_error($insert_employee_stmt));
        }

        // Get the inserted employee ID
        $new_employee_id = mysqli_insert_id($conn);

        // Insert into employee_details table (comprehensive HR information)
        $insert_details_query = "INSERT INTO employee_details (
            employee_id, middle_name, date_of_birth, gender, civil_status, nationality, religion,
            emergency_contact_name, emergency_contact_number, emergency_contact_relationship,
            employment_type, employment_status, job_level, immediate_supervisor, work_schedule, probation_period_months,
            regularization_date, basic_salary, salary_grade, step_increment, allowances, overtime_rate, 
            night_differential_rate, hazard_pay, pay_schedule, salary_structure_id, bank_account_number, bank_name,
            tin_number, sss_number, philhealth_number, pagibig_number, umid_number,
            postal_id, voters_id, drivers_license, passport_number, passport_expiry,
            prc_license_number, prc_license_expiry, prc_profession, other_licenses, certifications,
            highest_education, field_of_study, school_university, year_graduated, honors_awards,
            blood_type, medical_conditions, fitness_for_duty, last_medical_exam, next_medical_exam,
            skills_competencies, languages_spoken, `references`, notes, profile_photo, created_by, updated_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?
        )";

        $insert_details_stmt = mysqli_prepare($conn, $insert_details_query);
        if (!$insert_details_stmt) {
            throw new Exception('Database prepare error for employee_details: ' . mysqli_error($conn));
        }

        // Debug: Check session user_id
        $created_by = $_SESSION['user_id'] ?? null;
        $updated_by = $_SESSION['user_id'] ?? null;
        
        // If session user_id is not available, use a default admin user
        if (!$created_by) {
            $created_by = 1; // Default admin user
            $updated_by = 1;
        }

        // Use 60 string types to accommodate nullable numerics and ensure count matches
        $types = str_repeat('s', 60);

        mysqli_stmt_bind_param(
            $insert_details_stmt,
            $types,
            $new_employee_id, $middle_name, $date_of_birth, $gender, $civil_status, $nationality, $religion,
            $emergency_contact_name, $emergency_contact_number, $emergency_contact_relationship,
            $employment_type, $employment_status, $job_level, $immediate_supervisor, $work_schedule, $probation_period_months,
            $regularization_date, $basic_salary, $salary_grade, $step_increment, $allowances, $overtime_rate,
            $night_differential_rate, $hazard_pay, $pay_schedule, $salary_structure_id, $bank_account_number, $bank_name,
            $tin_number, $sss_number, $philhealth_number, $pagibig_number, $umid_number,
            $postal_id, $voters_id, $drivers_license, $passport_number, $passport_expiry,
            $prc_license_number, $prc_license_expiry, $prc_profession, $other_licenses, $certifications,
            $highest_education, $field_of_study, $school_university, $year_graduated, $honors_awards,
            $blood_type, $medical_conditions, $fitness_for_duty, $last_medical_exam, $next_medical_exam,
            $skills_competencies, $languages_spoken, $references, $notes, $profile_photo, $created_by, $updated_by
        );

        if (!mysqli_stmt_execute($insert_details_stmt)) {
            throw new Exception('Error inserting employee details: ' . mysqli_stmt_error($insert_details_stmt));
        }

        // Commit transaction
        mysqli_commit($conn);

        send_json([
            'success' => true,
            'message' => 'Employee added successfully with comprehensive HR details!',
            'employee_id' => $new_employee_id
        ], 200);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in add-employee-comprehensive.php: ' . $e->getMessage());
    send_json_error('An error occurred while adding the employee: ' . $e->getMessage(), 500);
} finally {
    // Restore autocommit
    mysqli_autocommit($conn, true);
}
?>
