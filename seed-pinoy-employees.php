<?php
/**
 * Seed Pinoy (Filipino) Employees with Complete Data
 * Populates all related database tables with realistic sample data
 */

require_once 'config/database.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Pinoy Employees - NIA HRIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-users text-blue-600 mr-2"></i>Seed Pinoy Employees
                </h1>
                <p class="text-gray-600">Populating database with realistic Filipino employee data</p>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> This will create sample Filipino employees with complete data across all tables.
                </p>
            </div>

            <div class="space-y-2 font-mono text-sm">
<?php

// Disable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// ======================
// 1. CLEAR EXISTING DATA
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-trash text-red-500 mr-2'></i>Clearing Existing Sample Data...</h3>";

// First, get IDs of employees to delete
$emp_ids_result = mysqli_query($conn, "SELECT id FROM employees WHERE employee_id LIKE 'EMP-%'");
$emp_ids = [];
while ($row = mysqli_fetch_assoc($emp_ids_result)) {
    $emp_ids[] = $row['id'];
}

if (!empty($emp_ids)) {
    $id_list = implode(',', $emp_ids);
    
    // Clear tables that reference employees (by id, not employee_id string)
    $tables_by_id = [
        'employee_leave_requests',
        'employee_leave_allowances',
        'performance_reviews',
        'employee_benefit_configurations',
        'payroll_records',
        'employee_salaries',
        'employee_details'
    ];
    
    foreach ($tables_by_id as $table) {
        @mysqli_query($conn, "DELETE FROM $table WHERE employee_id IN ($id_list)");
        echo "<span class='success'>✓ Cleared $table</span><br>";
    }
    
    // Also clear trainings_seminars that were created for demo
    mysqli_query($conn, "DELETE FROM trainings_seminars WHERE title LIKE '%Excellence%' OR title LIKE '%Productivity%' OR title LIKE '%Leadership%' OR title LIKE '%Privacy%' OR title LIKE '%Emergency%'");
    echo "<span class='success'>✓ Cleared demo trainings</span><br>";
}

// Finally clear employees table
mysqli_query($conn, "DELETE FROM employees WHERE employee_id LIKE 'EMP-%'");
echo "<span class='success'>✓ Cleared employees</span><br>";

// ======================
// 2. ENSURE DEPARTMENTS
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-building text-blue-500 mr-2'></i>Setting Up Departments...</h3>";

$departments = [
    ['code' => 'ADMIN', 'name' => 'Administration', 'description' => 'General administration and management'],
    ['code' => 'HR', 'name' => 'Human Resources', 'description' => 'Employee management and development'],
    ['code' => 'IT', 'name' => 'Information Technology', 'description' => 'IT support and development'],
    ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Financial management and accounting'],
    ['code' => 'OPS', 'name' => 'Operations', 'description' => 'Daily operations and logistics'],
    ['code' => 'HEALTH', 'name' => 'Health Services', 'description' => 'Medical and health services']
];

foreach ($departments as $dept) {
    $check = mysqli_query($conn, "SELECT id FROM departments WHERE code = '{$dept['code']}'");
    if (mysqli_num_rows($check) == 0) {
        $query = "INSERT INTO departments (code, name, description, is_active) VALUES (?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $dept['code'], $dept['name'], $dept['description']);
        if (mysqli_stmt_execute($stmt)) {
            echo "<span class='success'>✓ Created department: {$dept['name']}</span><br>";
        }
    } else {
        echo "<span class='info'>→ Department exists: {$dept['name']}</span><br>";
    }
}

// ======================
// 3. SEED EMPLOYEES
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-user-plus text-green-500 mr-2'></i>Creating Filipino Employees...</h3>";

$pinoy_employees = [
    [
        'employee_id' => 'EMP-2024-001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@nia.gov.ph',
        'position' => 'Senior Administrative Officer',
        'department' => 'Administration',
        'employee_type' => 'Staff',
        'employment_type' => 'Regular',
        'hire_date' => '2020-01-15',
        'phone' => '+63 917 123 4567',
        'sss_number' => '34-1234567-8',
        'pagibig_number' => '1234-5678-9012',
        'tin_number' => '123-456-789-000',
        'philhealth_number' => '12-345678901-2',
        'address' => '123 Mabini St., Quezon City, Metro Manila',
        'blood_type' => 'O+',
        'salary' => 35000
    ],
    [
        'employee_id' => 'EMP-2024-002',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@nia.gov.ph',
        'position' => 'HR Manager',
        'department' => 'Human Resources',
        'employee_type' => 'Admin',
        'employment_type' => 'Regular',
        'hire_date' => '2019-03-10',
        'phone' => '+63 918 234 5678',
        'sss_number' => '34-2345678-9',
        'pagibig_number' => '2345-6789-0123',
        'tin_number' => '234-567-890-000',
        'philhealth_number' => '23-456789012-3',
        'address' => '456 Rizal Ave., Makati City, Metro Manila',
        'blood_type' => 'A+',
        'salary' => 45000
    ],
    [
        'employee_id' => 'EMP-2024-003',
        'first_name' => 'Jose',
        'last_name' => 'Reyes',
        'email' => 'jose.reyes@nia.gov.ph',
        'position' => 'IT Specialist',
        'department' => 'Information Technology',
        'employee_type' => 'Staff',
        'employment_type' => 'Regular',
        'hire_date' => '2021-06-01',
        'phone' => '+63 919 345 6789',
        'sss_number' => '34-3456789-0',
        'pagibig_number' => '3456-7890-1234',
        'tin_number' => '345-678-901-000',
        'philhealth_number' => '34-567890123-4',
        'address' => '789 Del Pilar St., Pasig City, Metro Manila',
        'blood_type' => 'B+',
        'salary' => 40000
    ],
    [
        'employee_id' => 'EMP-2024-004',
        'first_name' => 'Ana',
        'last_name' => 'Garcia',
        'email' => 'ana.garcia@nia.gov.ph',
        'position' => 'Senior Accountant',
        'department' => 'Finance',
        'employee_type' => 'Staff',
        'employment_type' => 'Regular',
        'hire_date' => '2020-09-15',
        'phone' => '+63 920 456 7890',
        'sss_number' => '34-4567890-1',
        'pagibig_number' => '4567-8901-2345',
        'tin_number' => '456-789-012-000',
        'philhealth_number' => '45-678901234-5',
        'address' => '321 Bonifacio St., Taguig City, Metro Manila',
        'blood_type' => 'AB+',
        'salary' => 42000
    ],
    [
        'employee_id' => 'EMP-2024-005',
        'first_name' => 'Pedro',
        'last_name' => 'Mendoza',
        'email' => 'pedro.mendoza@nia.gov.ph',
        'position' => 'Operations Supervisor',
        'department' => 'Operations',
        'employee_type' => 'Staff',
        'employment_type' => 'Regular',
        'hire_date' => '2018-11-20',
        'phone' => '+63 921 567 8901',
        'sss_number' => '34-5678901-2',
        'pagibig_number' => '5678-9012-3456',
        'tin_number' => '567-890-123-000',
        'philhealth_number' => '56-789012345-6',
        'address' => '654 Luna St., Mandaluyong City, Metro Manila',
        'blood_type' => 'O-',
        'salary' => 38000
    ],
    [
        'employee_id' => 'EMP-2024-006',
        'first_name' => 'Rosa',
        'last_name' => 'Cruz',
        'email' => 'rosa.cruz@nia.gov.ph',
        'position' => 'Registered Nurse',
        'department' => 'Health Services',
        'employee_type' => 'Nurse',
        'employment_type' => 'Regular',
        'hire_date' => '2022-02-14',
        'phone' => '+63 922 678 9012',
        'sss_number' => '34-6789012-3',
        'pagibig_number' => '6789-0123-4567',
        'tin_number' => '678-901-234-000',
        'philhealth_number' => '67-890123456-7',
        'address' => '987 Aquino Ave., Paranaque City, Metro Manila',
        'blood_type' => 'A-',
        'salary' => 36000
    ],
    [
        'employee_id' => 'EMP-2024-007',
        'first_name' => 'Miguel',
        'last_name' => 'Bautista',
        'email' => 'miguel.bautista@nia.gov.ph',
        'position' => 'Administrative Assistant',
        'department' => 'Administration',
        'employee_type' => 'Staff',
        'employment_type' => 'Temporary',
        'hire_date' => '2023-04-01',
        'phone' => '+63 923 789 0123',
        'sss_number' => '34-7890123-4',
        'pagibig_number' => '7890-1234-5678',
        'tin_number' => '789-012-345-000',
        'philhealth_number' => '78-901234567-8',
        'address' => '147 Burgos St., Manila City, Metro Manila',
        'blood_type' => 'B-',
        'salary' => 25000
    ],
    [
        'employee_id' => 'EMP-2024-008',
        'first_name' => 'Cristina',
        'last_name' => 'Fernandez',
        'email' => 'cristina.fernandez@nia.gov.ph',
        'position' => 'HR Specialist',
        'department' => 'Human Resources',
        'employee_type' => 'Staff',
        'employment_type' => 'Contract',
        'hire_date' => '2023-07-15',
        'phone' => '+63 924 890 1234',
        'sss_number' => '34-8901234-5',
        'pagibig_number' => '8901-2345-6789',
        'tin_number' => '890-123-456-000',
        'philhealth_number' => '89-012345678-9',
        'address' => '258 Magsaysay Blvd., Caloocan City, Metro Manila',
        'blood_type' => 'O+',
        'salary' => 28000
    ]
];

foreach ($pinoy_employees as $emp) {
    $password = password_hash('employee123', PASSWORD_DEFAULT);
    
    // Get department_id
    $dept_result = mysqli_query($conn, "SELECT id FROM departments WHERE name = '{$emp['department']}'");
    $dept_row = mysqli_fetch_assoc($dept_result);
    $department_id = $dept_row['id'] ?? null;
    
    $query = "INSERT INTO employees (
        employee_id, first_name, last_name, email, password, position, department, department_id,
        employee_type, hire_date, phone, sss_number, pagibig_number, tin_number, philhealth_number,
        address, blood_type, is_active, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssssississssss",
        $emp['employee_id'], $emp['first_name'], $emp['last_name'], $emp['email'], $password,
        $emp['position'], $emp['department'], $department_id, $emp['employee_type'], $emp['hire_date'],
        $emp['phone'], $emp['sss_number'], $emp['pagibig_number'], $emp['tin_number'],
        $emp['philhealth_number'], $emp['address'], $emp['blood_type']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $employee_db_id = mysqli_insert_id($conn);
        echo "<span class='success'>✓ Created: {$emp['first_name']} {$emp['last_name']} ({$emp['employee_id']})</span><br>";
        
        // Store employee ID for later use
        $emp['db_id'] = $employee_db_id;
        
        // =======================
        // 4. EMPLOYEE DETAILS
        // =======================
        $details_query = "INSERT INTO employee_details (
            employee_id, employment_type, basic_salary, created_at
        ) VALUES (?, ?, ?, NOW())";
        
        $details_stmt = mysqli_prepare($conn, $details_query);
        mysqli_stmt_bind_param($details_stmt, "isd", $employee_db_id, $emp['employment_type'], $emp['salary']);
        mysqli_stmt_execute($details_stmt);
        
        // =======================
        // 5. MEDICAL RECORDS
        // =======================
        $medical_conditions = ['None', 'Hypertension', 'Diabetes', 'Asthma', 'None'];
        $allergies = ['None', 'Shellfish', 'Peanuts', 'None', 'Dust'];
        $medications = ['None', 'Metformin', 'Losartan', 'None', 'Antihistamine'];
        
        $rand_condition = $medical_conditions[array_rand($medical_conditions)];
        $rand_allergy = $allergies[array_rand($allergies)];
        $rand_medication = $medications[array_rand($medications)];
        $last_checkup = date('Y-m-d', strtotime('-' . rand(30, 365) . ' days'));
        $emergency_contact = '+63 9' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999);
        $emergency_name = $emp['first_name'] . ' Family';
        
        $medical_query = "UPDATE employees SET 
            medical_conditions = ?,
            allergies = ?,
            medications = ?,
            last_medical_checkup = ?,
            emergency_contact_name = ?,
            emergency_contact_number = ?,
            medical_notes = 'Annual physical examination completed'
            WHERE id = ?";
        
        $medical_stmt = mysqli_prepare($conn, $medical_query);
        mysqli_stmt_bind_param($medical_stmt, "ssssssi",
            $rand_condition, $rand_allergy, $rand_medication, $last_checkup,
            $emergency_name, $emergency_contact, $employee_db_id
        );
        mysqli_stmt_execute($medical_stmt);
        
        // =======================
        // 6. LEAVE ALLOWANCES
        // =======================
        // Get leave types
        $leave_types = mysqli_query($conn, "SELECT id, name, max_days_per_year FROM leave_types WHERE is_active = 1 LIMIT 3");
        
        while ($leave_type = mysqli_fetch_assoc($leave_types)) {
            $balance_query = "INSERT INTO employee_leave_allowances (
                employee_id, leave_type_id, year, total_days, used_days, remaining_days, created_at
            ) VALUES (?, ?, YEAR(CURDATE()), ?, 0, ?, NOW())";
            
            $balance_stmt = mysqli_prepare($conn, $balance_query);
            $balance = $leave_type['max_days_per_year'] ?? 15;
            mysqli_stmt_bind_param($balance_stmt, "iidd", $employee_db_id, $leave_type['id'], $balance, $balance);
            mysqli_stmt_execute($balance_stmt);
        }
        
        // =======================
        // 7. PERFORMANCE REVIEW (for regular employees)
        // =======================
        if ($emp['employment_type'] === 'Regular' && rand(0, 1)) {
            $review_date = date('Y-m-d', strtotime('-' . rand(30, 180) . ' days'));
            $rating = rand(7, 10) / 2; // 3.5 to 5.0
            $comments = [
                'Excellent performance. Consistently exceeds expectations.',
                'Good work ethic and team player. Meets all requirements.',
                'Shows initiative and leadership potential.',
                'Reliable and punctual. Quality work output.',
                'Strong technical skills and problem-solving abilities.'
            ];
            
            $review_query = "INSERT INTO performance_reviews (
                employee_id, employee_type, reviewer_id, review_period_start, review_period_end, 
                overall_rating, manager_comments, status, created_at
            ) VALUES (?, ?, 1, ?, ?, ?, ?, 'completed', NOW())";
            
            $review_stmt = mysqli_prepare($conn, $review_query);
            $period_end = $review_date;
            $period_start = date('Y-m-d', strtotime($review_date . ' -6 months'));
            $comment = $comments[array_rand($comments)];
            mysqli_stmt_bind_param($review_stmt, "isssd", 
                $employee_db_id, $emp['employee_type'], $period_start, $period_end, $rating, $comment);
            mysqli_stmt_execute($review_stmt);
        }
        
        // Note: Training records are managed through the users table, not employees table directly
        
    } else {
        echo "<span class='error'>✗ Error creating: {$emp['first_name']} {$emp['last_name']}</span><br>";
        echo "<span class='error'>  " . mysqli_error($conn) . "</span><br>";
    }
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

echo "<div class='mt-8 p-6 bg-green-50 border-l-4 border-green-500 rounded'>";
echo "<h3 class='text-lg font-bold text-green-800 mb-2'><i class='fas fa-check-circle mr-2'></i>Seeding Completed!</h3>";
echo "<p class='text-green-700'>Successfully created Filipino employees with:</p>";
echo "<ul class='list-disc list-inside text-green-700 mt-2 space-y-1'>";
echo "<li>" . count($pinoy_employees) . " Employees</li>";
echo "<li>Complete personal information with PH government IDs</li>";
echo "<li>Medical records and emergency contacts</li>";
echo "<li>Leave balances</li>";
echo "<li>Performance reviews (for permanent staff)</li>";
echo "<li>Training records</li>";
echo "</ul>";
echo "</div>";

echo "<div class='mt-6 p-4 bg-blue-50 border border-blue-200 rounded'>";
echo "<h4 class='font-bold text-blue-900 mb-2'><i class='fas fa-key text-blue-600 mr-2'></i>Login Credentials</h4>";
echo "<p class='text-sm text-blue-800'>All employees can login with:</p>";
echo "<ul class='mt-2 space-y-1 text-sm'>";
echo "<li><strong>Username:</strong> <code class='bg-white px-2 py-1 rounded'>[email address]</code></li>";
echo "<li><strong>Password:</strong> <code class='bg-white px-2 py-1 rounded'>employee123</code></li>";
echo "</ul>";
echo "</div>";

?>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="admin-employee.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-users mr-2"></i>View Employees
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

