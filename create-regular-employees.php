<?php
/**
 * Create Multiple Regular Employee Users
 * This script creates several regular employees for testing the employee dashboard
 */

session_start();
require_once 'config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Create Regular Employees';

// Check if employee role exists
$role_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_column = mysqli_fetch_assoc($role_check);
$current_enum = $role_column['Type'];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$page_title</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .credentials { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👥 Create Regular Employee Users</h1>";

// Check if employee role exists
if (strpos($current_enum, 'employee') === false) {
    echo "<div class='error'>
            ❌ <strong>Employee role not found!</strong><br>
            You must first add the employee role to the database.<br>
            <a href='add-employee-role.php' class='btn'>Add Employee Role First</a>
          </div>";
} else {
    echo "<div class='success'>
            ✅ <strong>Employee role exists!</strong><br>
            Current enum: <code>$current_enum</code>
          </div>";

    // Sample employees data
    $sample_employees = [
        [
            'username' => 'maria.santos',
            'password' => 'Maria@2024',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@nia.gov.ph',
            'position' => 'Administrative Assistant',
            'department' => 'Administration'
        ],
        [
            'username' => 'jose.garcia',
            'password' => 'Jose@2024',
            'first_name' => 'Jose',
            'last_name' => 'Garcia',
            'email' => 'jose.garcia@nia.gov.ph',
            'position' => 'Office Clerk',
            'department' => 'Administration'
        ],
        [
            'username' => 'ana.reyes',
            'password' => 'Ana@2024',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana.reyes@nia.gov.ph',
            'position' => 'Data Entry Clerk',
            'department' => 'IT Department'
        ],
        [
            'username' => 'carlos.lopez',
            'password' => 'Carlos@2024',
            'first_name' => 'Carlos',
            'last_name' => 'Lopez',
            'email' => 'carlos.lopez@nia.gov.ph',
            'position' => 'Maintenance Staff',
            'department' => 'Maintenance'
        ],
        [
            'username' => 'lisa.mendoza',
            'password' => 'Lisa@2024',
            'first_name' => 'Lisa',
            'last_name' => 'Mendoza',
            'email' => 'lisa.mendoza@nia.gov.ph',
            'position' => 'Receptionist',
            'department' => 'Administration'
        ]
    ];

    if (isset($_POST['create_employees'])) {
        $created_users = [];
        $errors = [];
        
        foreach ($sample_employees as $emp) {
            // Check if username or email already exists
            $check_query = "SELECT id, username, email FROM users WHERE username = ? OR email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "ss", $emp['username'], $emp['email']);
            mysqli_stmt_execute($check_stmt);
            $existing_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
            
            if ($existing_user) {
                if ($existing_user['username'] === $emp['username']) {
                    $errors[] = "Username '{$emp['username']}' already exists";
                } else {
                    $errors[] = "Email '{$emp['email']}' already exists";
                }
                continue;
            }
            
            // Create user
            $hashed_password = password_hash($emp['password'], PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, password, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, 'employee', 'active')";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssss", $emp['username'], $hashed_password, $emp['first_name'], $emp['last_name'], $emp['email']);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Create employee record
                $employee_id = 'EMP' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                $employee_query = "INSERT INTO employees (employee_id, first_name, last_name, email, position, department, department_id, hire_date, is_active) 
                                  VALUES (?, ?, ?, ?, ?, ?, 1, CURDATE(), 1)";
                $employee_stmt = mysqli_prepare($conn, $employee_query);
                mysqli_stmt_bind_param($employee_stmt, "ssssss", $employee_id, $emp['first_name'], $emp['last_name'], $emp['email'], $emp['position'], $emp['department']);
                
                if (mysqli_stmt_execute($employee_stmt)) {
                    $created_users[] = [
                        'user_id' => $user_id,
                        'username' => $emp['username'],
                        'password' => $emp['password'],
                        'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                        'employee_id' => $employee_id,
                        'position' => $emp['position'],
                        'department' => $emp['department']
                    ];
                } else {
                    $errors[] = "Failed to create employee record for {$emp['username']}: " . mysqli_error($conn);
                }
            } else {
                $errors[] = "Failed to create user {$emp['username']}: " . mysqli_error($conn);
            }
        }
        
        // Display results
        if (!empty($created_users)) {
            echo "<div class='success'>
                    ✅ <strong>Successfully created " . count($created_users) . " employee users!</strong>
                  </div>";
            
            echo "<h3>📋 Created Employee Accounts</h3>
                  <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Position</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($created_users as $user) {
                echo "<tr>
                        <td><strong>{$user['username']}</strong></td>
                        <td><code>{$user['password']}</code></td>
                        <td>{$user['name']}</td>
                        <td>{$user['employee_id']}</td>
                        <td>{$user['position']}</td>
                        <td>{$user['department']}</td>
                      </tr>";
            }
            
            echo "</tbody>
                  </table>";
            
            echo "<div class='info'>
                    <h4>🎯 Test Instructions:</h4>
                    <ol>
                        <li><strong>Logout</strong> from your current session</li>
                        <li><strong>Login</strong> with any of the employee credentials above</li>
                        <li><strong>Verify</strong> you're redirected to the employee dashboard</li>
                        <li><strong>Test</strong> the employee-specific features</li>
                    </ol>
                  </div>";
        }
        
        if (!empty($errors)) {
            echo "<div class='error'>
                    ❌ <strong>Errors encountered:</strong><br>
                    " . implode('<br>', $errors) . "
                  </div>";
        }
        
    } else {
        // Show existing employee users first
        echo "<h3>👤 Existing Employee Users</h3>";
        $existing_query = "SELECT username, first_name, last_name, email, role FROM users WHERE role = 'employee' ORDER BY username";
        $existing_result = mysqli_query($conn, $existing_query);
        
        if ($existing_result && mysqli_num_rows($existing_result) > 0) {
            echo "<div class='info'>
                    ✅ <strong>Found " . mysqli_num_rows($existing_result) . " existing employee users:</strong>
                  </div>";
            
            echo "<table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Login Credentials</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            while ($row = mysqli_fetch_assoc($existing_result)) {
                echo "<tr>
                        <td><strong>{$row['username']}</strong></td>
                        <td>{$row['first_name']} {$row['last_name']}</td>
                        <td>{$row['email']}</td>
                        <td>
                            <div class='credentials'>
                                <strong>Username:</strong> <code>{$row['username']}</code><br>
                                <strong>Password:</strong> <em>Check below for known passwords</em>
                            </div>
                        </td>
                      </tr>";
            }
            
            echo "</tbody>
                  </table>";
            
            echo "<div class='success'>
                    💡 <strong>You can test the employee dashboard with these existing accounts!</strong><br>
                    Try logging in with any of the usernames above.
                  </div>";
        } else {
            echo "<div class='info'>
                    ℹ️ <strong>No existing employee users found.</strong><br>
                    You can create new employee users below.
                  </div>";
        }
        
        echo "<hr>
              <h3>👥 Sample Employees to Create</h3>
              <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($sample_employees as $emp) {
            echo "<tr>
                    <td><strong>{$emp['username']}</strong></td>
                    <td><code>{$emp['password']}</code></td>
                    <td>{$emp['first_name']} {$emp['last_name']}</td>
                    <td>{$emp['email']}</td>
                    <td>{$emp['position']}</td>
                    <td>{$emp['department']}</td>
                  </tr>";
        }
        
        echo "</tbody>
              </table>";
        
        echo "<form method='POST'>
                <p><strong>Click the button below to create all " . count($sample_employees) . " employee users:</strong></p>
                <button type='submit' name='create_employees' class='btn btn-success'>Create All Employee Users</button>
              </form>";
    }
}

echo "
        <hr>
        <h3>🚀 Quick Actions</h3>
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn'>← Back to Dashboard</a>
            <a href='check-database-users.php' class='btn'>Check All Users</a>
            <a href='employee-dashboard.php' class='btn'>View Employee Dashboard</a>
        </div>
        
        <div class='info' style='margin-top: 20px;'>
            <h4>💡 What Happens After Creation:</h4>
            <ul>
                <li>✅ Employee users will be created in the users table</li>
                <li>✅ Employee records will be created in the employees table</li>
                <li>✅ Users will have 'employee' role with limited permissions</li>
                <li>✅ Login will redirect to employee-dashboard.php</li>
                <li>✅ Access to personal info, leave requests, and payslips</li>
                <li>❌ No access to HR management functions</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
