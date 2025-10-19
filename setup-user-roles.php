<?php
/**
 * Setup User Roles System
 * Updates the users table to support Super Admin and Nurse roles
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>User Roles Setup</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; margin-top: 0; }
    h3 { color: #555; background: linear-gradient(to right, #f0f8ff, #fff); padding: 12px; border-left: 4px solid #2196F3; margin-top: 25px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 0 0; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge-super { background: #ff6b6b; color: white; }
    .badge-admin { background: #4CAF50; color: white; }
    .badge-hr { background: #2196F3; color: white; }
    .badge-nurse { background: #9c27b0; color: white; }
</style></head><body><div class='container'>";

echo "<h2>🔐 User Roles Setup</h2>";
echo "<div class='info'>
    <strong>📋 Setting up roles:</strong><br>
    • <strong>super_admin</strong> - Full system access, all functions<br>
    • <strong>admin</strong> - Administrative functions<br>
    • <strong>hr_manager</strong> - HR management functions<br>
    • <strong>human_resource</strong> - HR staff functions<br>
    • <strong>nurse</strong> - View employees and update medical records
</div>";

// Step 1: Check current role enum
echo "<h3>Step 1: Checking Current Role Enum</h3>";
$check_query = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $check_query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "Current role enum: <code>" . htmlspecialchars($row['Type']) . "</code><br>";
    echo "Current default: <code>" . htmlspecialchars($row['Default']) . "</code><br><br>";
}

// Step 2: Update role enum
echo "<h3>Step 2: Updating Role Enum</h3>";
$alter_query = "ALTER TABLE users 
                MODIFY COLUMN role ENUM('super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse') 
                NOT NULL DEFAULT 'human_resource'";

if (mysqli_query($conn, $alter_query)) {
    echo "<span class='success'>✓ Successfully updated role enum to include Super Admin and Nurse</span><br><br>";
} else {
    echo "<span class='error'>✗ Error updating enum: " . mysqli_error($conn) . "</span><br><br>";
}

// Step 3: Verify the change
echo "<h3>Step 3: Verifying Changes</h3>";
$verify_query = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $verify_query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    echo "New role enum: <code>" . htmlspecialchars($row['Type']) . "</code><br>";
    echo "New default: <code>" . htmlspecialchars($row['Default']) . "</code><br><br>";
}

// Step 4: Update existing admin to super_admin (optional)
echo "<h3>Step 4: Update Existing Admin User</h3>";
$update_admin = "UPDATE users SET role = 'super_admin' WHERE role = 'admin' AND username = 'admin'";
if (mysqli_query($conn, $update_admin)) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "<span class='success'>✓ Updated {$affected} admin user(s) to super_admin role</span><br>";
    } else {
        echo "<span class='info'>ℹ No admin users found to update</span><br>";
    }
} else {
    echo "<span class='error'>✗ Error: " . mysqli_error($conn) . "</span><br>";
}
echo "<br>";

// Step 5: Show current user distribution
echo "<h3>Step 5: Current User Role Distribution</h3>";
$count_query = "SELECT role, COUNT(*) as count, status FROM users GROUP BY role, status ORDER BY role";
$result = mysqli_query($conn, $count_query);

if ($result) {
    echo "<table>";
    echo "<tr><th>Role</th><th>Count</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        $badge_class = '';
        switch($row['role']) {
            case 'super_admin': $badge_class = 'badge-super'; break;
            case 'admin': $badge_class = 'badge-admin'; break;
            case 'hr_manager': $badge_class = 'badge-hr'; break;
            case 'human_resource': $badge_class = 'badge-hr'; break;
            case 'nurse': $badge_class = 'badge-nurse'; break;
        }
        echo "<tr>";
        echo "<td><span class='badge {$badge_class}'>" . strtoupper(str_replace('_', ' ', $row['role'])) . "</span></td>";
        echo "<td><strong>{$row['count']}</strong></td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Step 6: Role Permissions Matrix
echo "<h3>Step 6: Role Permissions Matrix</h3>";
echo "<table>";
echo "<tr><th>Permission</th><th>Super Admin</th><th>Admin</th><th>HR Manager</th><th>HR Staff</th><th>Nurse</th></tr>";

$permissions = [
    ['View Employees', '✓', '✓', '✓', '✓', '✓'],
    ['Add Employees', '✓', '✓', '✓', '✓', '✗'],
    ['Edit Employees', '✓', '✓', '✓', '✓', '✗'],
    ['Delete Employees', '✓', '✓', '✓', '✗', '✗'],
    ['View Medical Records', '✓', '✓', '✓', '✓', '✓'],
    ['Update Medical Records', '✓', '✗', '✗', '✗', '✓'],
    ['Manage Salary', '✓', '✓', '✓', '✗', '✗'],
    ['Payroll Processing', '✓', '✓', '✓', '✗', '✗'],
    ['Manage Users', '✓', '✗', '✗', '✗', '✗'],
    ['System Settings', '✓', '✗', '✗', '✗', '✗'],
    ['View Reports', '✓', '✓', '✓', '✓', '✓'],
    ['Manage Departments', '✓', '✓', '✓', '✗', '✗'],
    ['Leave Management', '✓', '✓', '✓', '✓', '✗'],
    ['Performance Reviews', '✓', '✓', '✓', '✗', '✗'],
];

foreach ($permissions as $perm) {
    echo "<tr>";
    foreach ($perm as $index => $val) {
        if ($index === 0) {
            echo "<td><strong>{$val}</strong></td>";
        } else {
            $color = ($val === '✓') ? 'color: #4CAF50;' : 'color: #f44336;';
            echo "<td style='{$color} font-weight: bold; text-align: center;'>{$val}</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3 class='success'>✓ User Roles Setup Complete!</h3>";
echo "<div class='info'>
    <strong>✅ Next Steps:</strong><br>
    1. Create nurse accounts for medical staff<br>
    2. Ensure super_admin account is secure<br>
    3. Test role-based access for each user type<br>
    4. Review and customize permissions as needed
</div>";

echo "<a href='login.php' class='btn'>Go to Login</a>";
echo "<a href='settings.php' class='btn' style='background: #2196F3;'>Settings</a>";

echo "</div></body></html>";
mysqli_close($conn);
?>

