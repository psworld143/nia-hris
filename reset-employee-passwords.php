<?php
/**
 * Reset Employee Passwords
 * This script resets passwords for employee users to known values
 */

session_start();
require_once 'config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Reset Employee Passwords';

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
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Reset Employee Passwords</h1>";

// Known passwords for employee users
$employee_passwords = [
    'maria.santos' => 'Maria@2024',
    'jose.garcia' => 'Jose@2024',
    'ana.reyes' => 'Ana@2024',
    'carlos.lopez' => 'Carlos@2024',
    'lisa.mendoza' => 'Lisa@2024'
];

if (isset($_POST['reset_passwords'])) {
    $updated_users = [];
    $errors = [];
    
    foreach ($employee_passwords as $username => $password) {
        // Check if user exists
        $check_query = "SELECT id, username FROM users WHERE username = ? AND role = 'employee'";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));
        
        if ($user) {
            // Reset password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ? WHERE username = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $username);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $updated_users[] = [
                    'username' => $username,
                    'password' => $password
                ];
            } else {
                $errors[] = "Failed to update password for $username: " . mysqli_error($conn);
            }
        } else {
            $errors[] = "User $username not found";
        }
    }
    
    // Display results
    if (!empty($updated_users)) {
        echo "<div class='success'>
                ✅ <strong>Successfully reset " . count($updated_users) . " employee passwords!</strong>
              </div>";
        
        echo "<h3>📋 Updated Employee Credentials</h3>
              <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($updated_users as $user) {
            echo "<tr>
                    <td><strong>{$user['username']}</strong></td>
                    <td><code>{$user['password']}</code></td>
                    <td>✅ Updated</td>
                  </tr>";
        }
        
        echo "</tbody>
              </table>";
        
        echo "<div class='info'>
                <h4>🎯 Test Instructions:</h4>
                <ol>
                    <li><strong>Logout</strong> from your current session</li>
                    <li><strong>Login</strong> with any of the credentials above</li>
                    <li><strong>Verify</strong> you're redirected to the employee dashboard</li>
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
    // Show current employee users
    echo "<h3>👥 Current Employee Users</h3>";
    $query = "SELECT username, first_name, last_name, role, status FROM users WHERE role = 'employee' ORDER BY username";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<div class='info'>
                ✅ <strong>Found " . mysqli_num_rows($result) . " employee users</strong>
              </div>";
        
        echo "<table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>New Password</th>
                    </tr>
                </thead>
                <tbody>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            $new_password = $employee_passwords[$row['username']] ?? 'Unknown';
            echo "<tr>
                    <td><strong>{$row['username']}</strong></td>
                    <td>{$row['first_name']} {$row['last_name']}</td>
                    <td>{$row['role']}</td>
                    <td>{$row['status']}</td>
                    <td><code>$new_password</code></td>
                  </tr>";
        }
        
        echo "</tbody>
              </table>";
        
        echo "<form method='POST'>
                <p><strong>Click the button below to reset all employee passwords:</strong></p>
                <button type='submit' name='reset_passwords' class='btn btn-success'>Reset All Employee Passwords</button>
              </form>";
    } else {
        echo "<div class='error'>
                ❌ <strong>No employee users found!</strong><br>
                Create employee users first using <a href='create-regular-employees.php'>create-regular-employees.php</a>
              </div>";
    }
}

echo "
        <hr>
        <h3>🚀 Quick Actions</h3>
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn'>← Back to Dashboard</a>
            <a href='login.php' class='btn'>Test Login</a>
            <a href='employee-dashboard.php' class='btn'>View Employee Dashboard</a>
        </div>
        
        <div class='info' style='margin-top: 20px;'>
            <h4>💡 What This Does:</h4>
            <ul>
                <li>✅ Resets passwords for all employee users</li>
                <li>✅ Uses known passwords for easy testing</li>
                <li>✅ Ensures all employee accounts are accessible</li>
                <li>✅ Fixes any password hash issues</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
