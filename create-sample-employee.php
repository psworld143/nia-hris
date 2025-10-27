<?php
/**
 * Create Sample Employee User Account
 * This script creates a sample employee user for testing the employee dashboard
 */

session_start();
require_once 'config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Create Sample Employee User';

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
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👤 Create Sample Employee User</h1>";

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

    if (isset($_POST['create_employee'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        
        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        $existing_user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
        
        if ($existing_user) {
            echo "<div class='error'>
                    ❌ <strong>Username already exists!</strong><br>
                    Please choose a different username.
                  </div>";
        } else {
            // Create employee user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, password, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, 'employee', 'active')";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssss", $username, $hashed_password, $first_name, $last_name, $email);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                echo "<div class='success'>
                        ✅ <strong>Employee user created successfully!</strong><br>
                        User ID: $user_id<br>
                        Username: $username<br>
                        Role: employee<br>
                        <strong>Login Credentials:</strong><br>
                        Username: <code>$username</code><br>
                        Password: <code>$password</code>
                      </div>";
                
                // Create corresponding employee record
                $employee_query = "INSERT INTO employees (user_id, employee_id, first_name, last_name, email, position, department_id, hire_date, is_active) 
                                  VALUES (?, ?, ?, ?, ?, 'Employee', 1, CURDATE(), 1)";
                $employee_stmt = mysqli_prepare($conn, $employee_query);
                $employee_id = 'EMP' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                mysqli_stmt_bind_param($employee_stmt, "issss", $user_id, $employee_id, $first_name, $last_name, $email);
                
                if (mysqli_stmt_execute($employee_stmt)) {
                    $emp_id = mysqli_insert_id($conn);
                    echo "<div class='info'>
                            📋 <strong>Employee record created!</strong><br>
                            Employee ID: $employee_id<br>
                            Database ID: $emp_id
                          </div>";
                }
                
                echo "<div class='info'>
                        🔗 <strong>Next Steps:</strong><br>
                        1. Login with the credentials above<br>
                        2. You'll be redirected to the employee dashboard<br>
                        3. Test the employee-specific features
                      </div>";
            } else {
                echo "<div class='error'>
                        ❌ <strong>Error creating user:</strong><br>
                        " . mysqli_error($conn) . "
                      </div>";
            }
        }
    } else {
        echo "<form method='POST'>
                <div class='form-group'>
                    <label for='username'>Username:</label>
                    <input type='text' id='username' name='username' value='employee1' required>
                </div>
                
                <div class='form-group'>
                    <label for='password'>Password:</label>
                    <input type='password' id='password' name='password' value='Employee@2024' required>
                </div>
                
                <div class='form-group'>
                    <label for='first_name'>First Name:</label>
                    <input type='text' id='first_name' name='first_name' value='John' required>
                </div>
                
                <div class='form-group'>
                    <label for='last_name'>Last Name:</label>
                    <input type='text' id='last_name' name='last_name' value='Doe' required>
                </div>
                
                <div class='form-group'>
                    <label for='email'>Email:</label>
                    <input type='email' id='email' name='email' value='employee1@nia.gov.ph' required>
                </div>
                
                <button type='submit' name='create_employee' class='btn btn-success'>Create Employee User</button>
              </form>";
    }
}

echo "
        <hr>
        <h3>📝 Employee Dashboard Features</h3>
        <ul>
            <li>✅ Personal dashboard with leave balances</li>
            <li>✅ View own profile information</li>
            <li>✅ Request leave</li>
            <li>✅ View leave history</li>
            <li>✅ View payslips</li>
            <li>✅ Employee-specific navigation</li>
        </ul>
        
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn'>← Back to Dashboard</a>
            <a href='employee-dashboard.php' class='btn'>View Employee Dashboard →</a>
        </div>
    </div>
</body>
</html>";
?>
