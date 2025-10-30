<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // Validate and sanitize input data
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $employee_id = sanitize_input($_POST['employee_id'] ?? '');
    $hire_date = sanitize_input($_POST['hire_date'] ?? '');
    $position = sanitize_input($_POST['position'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $employee_type = sanitize_input($_POST['employee_type'] ?? '');
    $is_active = (int)($_POST['is_active'] ?? 1);
    $password = $_POST['password'] ?? '';

    // Validate required fields
    $required_fields = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'address' => 'Address',
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
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit();
    }

    // Validate password length
    if (strlen($password) < 8) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 8 characters long'
        ]);
        exit();
    }

    // Validate employee ID format (YYYY-XXXX)
    if (!preg_match('/^\d{4}-\d{4}$/', $employee_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee ID must be in format YYYY-XXXX'
        ]);
        exit();
    }

    // Validate employee type
    $valid_employee_types = ['Staff', 'Admin', 'Nurse'];
    if (!in_array($employee_type, $valid_employee_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid employee type'
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
    $check_salary_structure_query = "SELECT id FROM salary_structures WHERE position_title = ? AND is_active = 1";
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
        mysqli_stmt_bind_param($create_stmt, 'sii', $position, $department, $_SESSION['user_id']);
        
        if (!mysqli_stmt_execute($create_stmt)) {
            echo json_encode([
                'success' => false,
                'message' => 'Position "' . $position . '" does not have a salary structure. Please create one first in Salary Structures Management.'
            ]);
            exit();
        }
        
        // Log the automatic creation
        error_log("Auto-created salary structure for employee position: {$position} by user: {$_SESSION['user_id']}");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new employee
    $insert_query = "INSERT INTO employees (employee_id, first_name, last_name, email, password, position, department, employee_type, hire_date, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    if (!$insert_stmt) {
        throw new Exception('Database prepare error: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($insert_stmt, 'sssssssssssi', 
        $employee_id, 
        $first_name, 
        $last_name, 
        $email, 
        $hashed_password, 
        $position, 
        $department, 
        $employee_type, 
        $hire_date, 
        $phone, 
        $address, 
        $is_active
    );

    if (!mysqli_stmt_execute($insert_stmt)) {
        throw new Exception('Database execute error: ' . mysqli_stmt_error($insert_stmt));
    }

    $new_employee_id = mysqli_insert_id($conn);

    // Return success response
    send_json([
        'success' => true,
        'message' => 'Employee added successfully',
        'employee_id' => $new_employee_id,
        'employee_data' => [
            'id' => $new_employee_id,
            'employee_id' => $employee_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'position' => $position,
            'department' => $department,
            'employee_type' => $employee_type,
            'is_active' => $is_active
        ]
    ], 200);

} catch (Exception $e) {
    // Log error
    error_log("Error adding employee: " . $e->getMessage());
    
    // Ensure we return JSON even on error
    send_json_error('An error occurred while adding the employee: ' . $e->getMessage(), 500);
}

mysqli_close($conn);
?>
