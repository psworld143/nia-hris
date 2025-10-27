<?php
/**
 * Check Database Users and Credentials
 * This script displays all users in the database with their roles and login information
 */

session_start();
require_once 'config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Database Users Check';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$page_title</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .role-super_admin { background: #dc3545; color: white; }
        .role-admin { background: #28a745; color: white; }
        .role-hr_manager { background: #007bff; color: white; }
        .role-human_resource { background: #17a2b8; color: white; }
        .role-nurse { background: #6f42c1; color: white; }
        .role-employee { background: #fd7e14; color: white; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .credentials { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
        code { background: #e9ecef; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>👥 Database Users Check</h1>";

// Get all users from database
$users_query = "SELECT id, username, first_name, last_name, email, role, status, created_at FROM users ORDER BY role, username";
$users_result = mysqli_query($conn, $users_query);

if (!$users_result) {
    echo "<div class='error'>
            ❌ <strong>Error querying users:</strong><br>
            " . mysqli_error($conn) . "
          </div>";
} else {
    $users = mysqli_fetch_all($users_result, MYSQLI_ASSOC);
    
    if (empty($users)) {
        echo "<div class='error'>
                ❌ <strong>No users found in database!</strong><br>
                The users table appears to be empty.
              </div>";
    } else {
        echo "<div class='success'>
                ✅ <strong>Found " . count($users) . " users in database</strong>
              </div>";
        
        // Group users by role
        $users_by_role = [];
        foreach ($users as $user) {
            $users_by_role[$user['role']][] = $user;
        }
        
        // Display users by role
        foreach ($users_by_role as $role => $role_users) {
            $role_display = ucfirst(str_replace('_', ' ', $role));
            echo "<h2>🔹 $role_display Users (" . count($role_users) . ")</h2>";
            
            echo "<table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Login Credentials</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($role_users as $user) {
                $role_class = 'role-' . $user['role'];
                $status_class = $user['status'] === 'active' ? 'status-active' : 'status-inactive';
                
                echo "<tr>
                        <td>{$user['id']}</td>
                        <td><strong>{$user['username']}</strong></td>
                        <td>{$user['first_name']} {$user['last_name']}</td>
                        <td>{$user['email']}</td>
                        <td><span class='role-badge $role_class'>$role_display</span></td>
                        <td><span class='$status_class'>" . ucfirst($user['status']) . "</span></td>
                        <td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>
                        <td>";
                
                // Display known credentials for common accounts
                $known_passwords = [
                    'admin' => 'admin123',
                    'superadmin' => 'Super@2024',
                    'hrmanager' => 'HRM@2024',
                    'hrstaff' => 'HRStaff@2024',
                    'nurse1' => 'Nurse@2024',
                    'employee1' => 'Employee@2024'
                ];
                
                if (isset($known_passwords[$user['username']])) {
                    echo "<div class='credentials'>
                            <strong>Username:</strong> <code>{$user['username']}</code><br>
                            <strong>Password:</strong> <code>{$known_passwords[$user['username']]}</code>
                          </div>";
                } else {
                    echo "<em>Password not in known list</em>";
                }
                
                echo "</td>
                      </tr>";
            }
            
            echo "</tbody>
                  </table>";
        }
        
        // Summary of available accounts for testing
        echo "<div class='info'>
                <h3>📋 Available Test Accounts</h3>
                <p><strong>You can use these accounts to test different user roles:</strong></p>
                <ul>";
        
        foreach ($users_by_role as $role => $role_users) {
            $role_display = ucfirst(str_replace('_', ' ', $role));
            echo "<li><strong>$role_display:</strong> ";
            $usernames = array_column($role_users, 'username');
            echo implode(', ', $usernames);
            echo "</li>";
        }
        
        echo "</ul>
              </div>";
        
        // Check if employee role exists
        if (isset($users_by_role['employee'])) {
            echo "<div class='success'>
                    ✅ <strong>Employee users found!</strong><br>
                    You can test the employee dashboard with these accounts.
                  </div>";
        } else {
            echo "<div class='info'>
                    ℹ️ <strong>No employee users found.</strong><br>
                    You may want to create an employee user using <a href='create-sample-employee.php'>create-sample-employee.php</a>
                  </div>";
        }
        
        // Check for employees table records
        echo "<h2>👤 Employee Records Check</h2>";
        $employees_query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.position, e.department_id, d.department_name, u.username, u.role
                           FROM employees e 
                           LEFT JOIN departments d ON e.department_id = d.id 
                           LEFT JOIN users u ON e.user_id = u.id 
                           WHERE e.is_active = 1
                           ORDER BY e.id";
        $employees_result = mysqli_query($conn, $employees_query);
        
        if ($employees_result && mysqli_num_rows($employees_result) > 0) {
            $employees = mysqli_fetch_all($employees_result, MYSQLI_ASSOC);
            echo "<div class='success'>
                    ✅ <strong>Found " . count($employees) . " active employee records</strong>
                  </div>";
            
            echo "<table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Linked User</th>
                            <th>User Role</th>
                        </tr>
                    </thead>
                    <tbody>";
            
            foreach ($employees as $emp) {
                $role_class = 'role-' . $emp['role'];
                echo "<tr>
                        <td><strong>{$emp['employee_id']}</strong></td>
                        <td>{$emp['first_name']} {$emp['last_name']}</td>
                        <td>{$emp['position']}</td>
                        <td>{$emp['department_name']}</td>
                        <td>{$emp['username']}</td>
                        <td><span class='role-badge $role_class'>" . ucfirst(str_replace('_', ' ', $emp['role'])) . "</span></td>
                      </tr>";
            }
            
            echo "</tbody>
                  </table>";
        } else {
            echo "<div class='error'>
                    ❌ <strong>No active employee records found!</strong><br>
                    Employee records are needed for the employee dashboard to display data properly.
                  </div>";
        }
    }
}

echo "
        <hr>
        <h3>🚀 Quick Actions</h3>
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn'>← Back to Dashboard</a>
            <a href='create-sample-employee.php' class='btn btn-success'>Create Employee User →</a>
            <a href='add-employee-role.php' class='btn'>Add Employee Role</a>
        </div>
        
        <div class='info' style='margin-top: 20px;'>
            <h4>💡 Testing Tips:</h4>
            <ul>
                <li>Use the credentials above to test different user roles</li>
                <li>Employee users should be redirected to employee-dashboard.php</li>
                <li>HR users should see the main dashboard with HR functions</li>
                <li>Check that role-based access control is working properly</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
