<?php
/**
 * Test Employee Dashboard Query
 * This script tests the employee dashboard query to make sure it works
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Employee Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test Employee Dashboard Query</h1>";

// Test the employee dashboard query for each employee user
$employee_users = ['maria.santos@nia.gov.ph', 'jose.garcia@nia.gov.ph', 'ana.reyes@nia.gov.ph'];

foreach ($employee_users as $email) {
    echo "<h3>Testing for: $email</h3>";
    
    // Get user information
    $user_query = "SELECT id, username, first_name, last_name, email FROM users WHERE email = ? AND role = 'employee'";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, "s", $email);
    mysqli_stmt_execute($user_stmt);
    $user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
    
    if ($user_info) {
        echo "<div class='success'>✅ User found: {$user_info['username']} ({$user_info['first_name']} {$user_info['last_name']})</div>";
        
        // Test employee query
        $employee_query = "SELECT e.*, d.name as department_name 
                          FROM employees e 
                          LEFT JOIN departments d ON e.department_id = d.id 
                          WHERE e.email = ? AND e.is_active = 1";
        $employee_stmt = mysqli_prepare($conn, $employee_query);
        mysqli_stmt_bind_param($employee_stmt, "s", $email);
        mysqli_stmt_execute($employee_stmt);
        $employee = mysqli_fetch_assoc(mysqli_stmt_get_result($employee_stmt));
        
        if ($employee) {
            echo "<div class='success'>✅ Employee record found!</div>";
            echo "<table>
                    <tr><th>Field</th><th>Value</th></tr>
                    <tr><td>Employee ID</td><td>{$employee['employee_id']}</td></tr>
                    <tr><td>Name</td><td>{$employee['first_name']} {$employee['last_name']}</td></tr>
                    <tr><td>Position</td><td>{$employee['position']}</td></tr>
                    <tr><td>Department</td><td>{$employee['department_name']}</td></tr>
                    <tr><td>Email</td><td>{$employee['email']}</td></tr>
                    <tr><td>Hire Date</td><td>{$employee['hire_date']}</td></tr>
                    <tr><td>Is Active</td><td>{$employee['is_active']}</td></tr>
                  </table>";
        } else {
            echo "<div class='error'>❌ Employee record not found for email: $email</div>";
        }
    } else {
        echo "<div class='error'>❌ User not found for email: $email</div>";
    }
    
    echo "<hr>";
}

echo "
        <div class='info'>
            <h3>🎯 Next Steps:</h3>
            <ol>
                <li>If all tests pass above, the employee dashboard should work</li>
                <li>Try logging in with: <strong>maria.santos</strong> / <strong>Maria@2024</strong></li>
                <li>You should be redirected to employee-dashboard.php</li>
                <li>Check that all employee information displays correctly</li>
            </ol>
        </div>
        
        <div style='margin-top: 20px;'>
            <a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Login</a>
            <a href='employee-dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>View Dashboard</a>
        </div>
    </div>
</body>
</html>";
?>
