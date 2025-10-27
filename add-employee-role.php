<?php
/**
 * Add Employee Role to Database
 * This script adds the 'employee' role to the users table enum
 */

session_start();
require_once 'config/database.php';

// Check if user is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit();
}

$page_title = 'Add Employee Role';

// Check current role enum
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Add Employee Role to Database</h1>";

// Check if employee role already exists
if (strpos($current_enum, 'employee') !== false) {
    echo "<div class='success'>
            ✅ <strong>Employee role already exists!</strong><br>
            Current enum: <code>$current_enum</code>
          </div>";
} else {
    echo "<div class='info'>
            📋 <strong>Current role enum:</strong> <code>$current_enum</code><br>
            The 'employee' role is not present and needs to be added.
          </div>";

    if (isset($_POST['add_role'])) {
        // Add employee role to enum
        $alter_query = "ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','admin','hr_manager','human_resource','nurse','employee') NOT NULL DEFAULT 'human_resource'";
        
        if (mysqli_query($conn, $alter_query)) {
            echo "<div class='success'>
                    ✅ <strong>Success!</strong> Employee role has been added to the database.<br>
                    New enum: <code>enum('super_admin','admin','hr_manager','human_resource','nurse','employee')</code>
                  </div>";
            
            // Verify the change
            $verify_query = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
            $verify_column = mysqli_fetch_assoc($verify_query);
            echo "<div class='info'>
                    🔍 <strong>Verification:</strong> <code>" . $verify_column['Type'] . "</code>
                  </div>";
        } else {
            echo "<div class='error'>
                    ❌ <strong>Error:</strong> Failed to add employee role.<br>
                    Error: " . mysqli_error($conn) . "
                  </div>";
        }
    } else {
        echo "<form method='POST'>
                <p>Click the button below to add the 'employee' role to the users table:</p>
                <button type='submit' name='add_role' class='btn btn-success'>Add Employee Role</button>
              </form>";
    }
}

echo "
        <hr>
        <h3>📝 Next Steps</h3>
        <ol>
            <li>✅ Add employee role to database (this page)</li>
            <li>🔧 Update role definitions in <code>includes/roles.php</code></li>
            <li>🏠 Create employee dashboard page</li>
            <li>🔐 Update dashboard access controls</li>
            <li>📱 Create employee-specific navigation</li>
            <li>👤 Create sample employee user account</li>
        </ol>
        
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn'>← Back to Dashboard</a>
            <a href='update-role-definitions.php' class='btn'>Next: Update Role Definitions →</a>
        </div>
    </div>
</body>
</html>";
?>
