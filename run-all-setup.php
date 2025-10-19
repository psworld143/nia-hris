<?php
/**
 * Master Setup Script - Run All RBAC Setup Scripts in Order
 * This script executes all necessary setup scripts automatically
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Master Setup - RBAC System</title>";
echo "<style>
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        max-width: 1200px; 
        margin: 30px auto; 
        padding: 20px; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .container { 
        background: white; 
        padding: 40px; 
        border-radius: 15px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
    }
    h2 { 
        color: #333; 
        border-bottom: 3px solid #4CAF50; 
        padding-bottom: 15px; 
        margin-top: 0; 
    }
    .step { 
        background: #f9f9f9; 
        padding: 20px; 
        margin: 20px 0; 
        border-radius: 10px; 
        border-left: 5px solid #2196F3; 
    }
    .step h3 { 
        margin-top: 0; 
        color: #2196F3; 
    }
    .success { 
        color: #4CAF50; 
        font-weight: bold; 
    }
    .error { 
        color: #f44336; 
        font-weight: bold; 
    }
    .warning { 
        background: #fff3cd; 
        border: 2px solid #ffc107; 
        padding: 15px; 
        border-radius: 8px; 
        margin: 20px 0; 
    }
    .info { 
        background: #e3f2fd; 
        border: 2px solid #2196F3; 
        padding: 15px; 
        border-radius: 8px; 
        margin: 20px 0; 
    }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 20px 0; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
    }
    th { 
        background: linear-gradient(135deg, #4CAF50, #45a049); 
        color: white; 
        padding: 12px; 
        text-align: left; 
    }
    td { 
        padding: 10px; 
        border-bottom: 1px solid #e0e0e0; 
    }
    tr:hover { 
        background: #f5f9ff; 
    }
    .badge { 
        padding: 4px 12px; 
        border-radius: 12px; 
        font-size: 12px; 
        font-weight: 600; 
        color: white; 
    }
    .badge-super { background: #ff6b6b; }
    .badge-admin { background: #4CAF50; }
    .badge-hr { background: #2196F3; }
    .badge-nurse { background: #9c27b0; }
    .progress { 
        background: #e0e0e0; 
        height: 30px; 
        border-radius: 15px; 
        overflow: hidden; 
        margin: 20px 0; 
    }
    .progress-bar { 
        background: linear-gradient(90deg, #4CAF50, #45a049); 
        height: 100%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        color: white; 
        font-weight: bold; 
        transition: width 0.5s ease; 
    }
    code { 
        background: #f5f5f5; 
        padding: 2px 8px; 
        border-radius: 4px; 
        font-family: 'Courier New', monospace; 
        color: #d63384; 
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        margin: 10px 5px;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-primary { background: #4CAF50; }
    .btn-primary:hover { background: #45a049; transform: translateY(-2px); }
    .btn-secondary { background: #2196F3; }
    .btn-secondary:hover { background: #0b7dda; transform: translateY(-2px); }
    .btn-warning { background: #FF9800; }
    .btn-warning:hover { background: #e68900; transform: translateY(-2px); }
</style></head><body><div class='container'>";

echo "<h2>🚀 Master Setup Script - RBAC System</h2>";
echo "<p>This script will run all setup scripts automatically in the correct order.</p>";

// Progress tracking
$total_steps = 4;
$current_step = 0;
$progress_percent = 0;

echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width: 0%;'>0%</div></div>";

// ============================================================================
// STEP 1: Setup User Roles
// ============================================================================
$current_step = 1;
$progress_percent = ($current_step / $total_steps) * 100;

echo "<script>document.getElementById('progressBar').style.width = '{$progress_percent}%'; 
      document.getElementById('progressBar').textContent = 'Step {$current_step}/{$total_steps} - " . round($progress_percent) . "%';</script>";

echo "<div class='step'>";
echo "<h3>📋 Step 1/{$total_steps}: Setup User Roles</h3>";

// Check current role enum
$check_roles = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_column = mysqli_fetch_assoc($check_roles);
$current_enum = $role_column['Type'];
echo "<p><strong>Current roles:</strong> <code>{$current_enum}</code></p>";

// Update role enum
$alter_query = "ALTER TABLE users 
                MODIFY COLUMN role ENUM('super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse') 
                NOT NULL DEFAULT 'human_resource'";

if (mysqli_query($conn, $alter_query)) {
    echo "<span class='success'>✓ Successfully updated role enum</span><br>";
    
    // Update existing admin to super_admin
    $update_admin = "UPDATE users SET role = 'super_admin' WHERE role = 'admin' AND username = 'admin'";
    if (mysqli_query($conn, $update_admin)) {
        $affected = mysqli_affected_rows($conn);
        if ($affected > 0) {
            echo "<span class='success'>✓ Updated {$affected} admin user(s) to super_admin</span><br>";
        }
    }
    
    // Verify
    $verify = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    $new_role = mysqli_fetch_assoc($verify);
    echo "<p><strong>New roles:</strong> <code>{$new_role['Type']}</code></p>";
} else {
    echo "<span class='error'>✗ Error: " . mysqli_error($conn) . "</span><br>";
}
echo "</div>";

// ============================================================================
// STEP 2: Add Medical Record Fields
// ============================================================================
$current_step = 2;
$progress_percent = ($current_step / $total_steps) * 100;

echo "<script>document.getElementById('progressBar').style.width = '{$progress_percent}%'; 
      document.getElementById('progressBar').textContent = 'Step {$current_step}/{$total_steps} - " . round($progress_percent) . "%';</script>";

echo "<div class='step'>";
echo "<h3>🏥 Step 2/{$total_steps}: Add Medical Record Fields</h3>";

$fields_to_add = [
    "blood_type VARCHAR(10) DEFAULT NULL",
    "medical_conditions TEXT DEFAULT NULL",
    "allergies TEXT DEFAULT NULL",
    "medications TEXT DEFAULT NULL",
    "last_medical_checkup DATE DEFAULT NULL",
    "medical_notes TEXT DEFAULT NULL",
    "emergency_contact_name VARCHAR(100) DEFAULT NULL",
    "emergency_contact_number VARCHAR(20) DEFAULT NULL",
];

// Check existing columns
$existing_columns = [];
$columns_result = mysqli_query($conn, "SHOW COLUMNS FROM employees");
while ($col = mysqli_fetch_assoc($columns_result)) {
    $existing_columns[] = $col['Field'];
}

$added_fields = 0;
$existing_fields = 0;

foreach ($fields_to_add as $field) {
    $field_name = explode(' ', $field)[0];
    
    if (in_array($field_name, $existing_columns)) {
        $existing_fields++;
        echo "⊗ Field <strong>{$field_name}</strong> already exists<br>";
    } else {
        $alter_query = "ALTER TABLE employees ADD COLUMN $field";
        if (mysqli_query($conn, $alter_query)) {
            $added_fields++;
            echo "<span class='success'>✓ Added field: <strong>{$field_name}</strong></span><br>";
        } else {
            echo "<span class='error'>✗ Error adding {$field_name}: " . mysqli_error($conn) . "</span><br>";
        }
    }
}

echo "<p><strong>Summary:</strong> Added {$added_fields} new fields, {$existing_fields} already existed</p>";
echo "</div>";

// ============================================================================
// STEP 3: Create Sample User Accounts
// ============================================================================
$current_step = 3;
$progress_percent = ($current_step / $total_steps) * 100;

echo "<script>document.getElementById('progressBar').style.width = '{$progress_percent}%'; 
      document.getElementById('progressBar').textContent = 'Step {$current_step}/{$total_steps} - " . round($progress_percent) . "%';</script>";

echo "<div class='step'>";
echo "<h3>👥 Step 3/{$total_steps}: Create Sample User Accounts</h3>";

$sample_users = [
    ['username' => 'superadmin', 'password' => 'Super@2024', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'superadmin@nia.gov.ph', 'role' => 'super_admin'],
    ['username' => 'hrmanager', 'password' => 'HRM@2024', 'first_name' => 'Maria', 'last_name' => 'Garcia', 'email' => 'hrmanager@nia.gov.ph', 'role' => 'hr_manager'],
    ['username' => 'hrstaff', 'password' => 'HRStaff@2024', 'first_name' => 'Pedro', 'last_name' => 'Santos', 'email' => 'hrstaff@nia.gov.ph', 'role' => 'human_resource'],
    ['username' => 'nurse1', 'password' => 'Nurse@2024', 'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'nurse@nia.gov.ph', 'role' => 'nurse']
];

$created_count = 0;
$skipped_count = 0;

echo "<table>";
echo "<tr><th>Username</th><th>Password</th><th>Role</th><th>Status</th></tr>";

foreach ($sample_users as $user) {
    // Check if user exists
    $check_query = "SELECT id FROM users WHERE username = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $user['username']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    $badge_class = '';
    switch($user['role']) {
        case 'super_admin': $badge_class = 'badge-super'; break;
        case 'admin': $badge_class = 'badge-admin'; break;
        case 'hr_manager': $badge_class = 'badge-hr'; break;
        case 'human_resource': $badge_class = 'badge-hr'; break;
        case 'nurse': $badge_class = 'badge-nurse'; break;
    }
    
    echo "<tr>";
    echo "<td><strong>{$user['username']}</strong></td>";
    echo "<td><code>{$user['password']}</code></td>";
    echo "<td><span class='badge {$badge_class}'>" . strtoupper(str_replace('_', ' ', $user['role'])) . "</span></td>";
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<td><span style='color: #FF9800;'>Already Exists</span></td>";
        $skipped_count++;
    } else {
        // Create user
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (username, password, first_name, last_name, email, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssssss", 
            $user['username'], $hashed_password, $user['first_name'], 
            $user['last_name'], $user['email'], $user['role']
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            echo "<td><span class='success'>✓ Created</span></td>";
            $created_count++;
        } else {
            echo "<td><span class='error'>✗ Error</span></td>";
        }
    }
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Created:</strong> {$created_count} new accounts | <strong>Skipped:</strong> {$skipped_count} existing accounts</p>";
echo "</div>";

// ============================================================================
// STEP 4: Final Summary
// ============================================================================
$current_step = 4;
$progress_percent = 100;

echo "<script>document.getElementById('progressBar').style.width = '100%'; 
      document.getElementById('progressBar').textContent = 'Complete - 100%';</script>";

echo "<div class='step' style='border-left-color: #4CAF50;'>";
echo "<h3 style='color: #4CAF50;'>✅ Step 4/{$total_steps}: Setup Complete!</h3>";

echo "<div class='info'>
    <h4 style='margin-top:0;'>📋 What Was Configured:</h4>
    <ul>
        <li>✅ User roles updated to include Super Admin and Nurse</li>
        <li>✅ Medical record fields added to employees table</li>
        <li>✅ Sample user accounts created for all roles</li>
        <li>✅ System ready for role-based access control</li>
    </ul>
</div>";

// Show all accounts
echo "<h4>🔐 User Account Credentials:</h4>";
echo "<table>";
echo "<tr><th>Role</th><th>Username</th><th>Password</th><th>Access</th></tr>";

$all_accounts = [
    ['role' => 'Super Admin', 'username' => 'superadmin', 'password' => 'Super@2024', 'badge' => 'badge-super', 'access' => 'Full System'],
    ['role' => 'Admin', 'username' => 'admin', 'password' => 'admin123', 'badge' => 'badge-admin', 'access' => 'HR & Payroll'],
    ['role' => 'HR Manager', 'username' => 'hrmanager', 'password' => 'HRM@2024', 'badge' => 'badge-hr', 'access' => 'HR Management'],
    ['role' => 'HR Staff', 'username' => 'hrstaff', 'password' => 'HRStaff@2024', 'badge' => 'badge-hr', 'access' => 'HR Operations'],
    ['role' => 'Nurse', 'username' => 'nurse1', 'password' => 'Nurse@2024', 'badge' => 'badge-nurse', 'access' => 'Medical Records'],
];

foreach ($all_accounts as $acc) {
    echo "<tr>";
    echo "<td><span class='badge {$acc['badge']}'>{$acc['role']}</span></td>";
    echo "<td><code>{$acc['username']}</code></td>";
    echo "<td><code>{$acc['password']}</code></td>";
    echo "<td>{$acc['access']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='warning'>
    <strong>⚠️ IMPORTANT SECURITY NOTICE:</strong><br>
    These are DEFAULT passwords for initial setup only. You MUST change all passwords immediately after first login!
</div>";

echo "<h4>🎯 Quick Access Links:</h4>";
echo "<div>";
echo "<a href='login.php' class='btn btn-primary'><i class='fas fa-sign-in-alt'></i> Login Page</a>";
echo "<a href='medical-records.php' class='btn btn-secondary'><i class='fas fa-heartbeat'></i> Medical Records</a>";
echo "<a href='admin-employee.php' class='btn btn-warning'><i class='fas fa-users'></i> Employee Management</a>";
echo "</div>";

echo "<h4>📚 Documentation:</h4>";
echo "<ul>
    <li><a href='ROLE_BASED_ACCESS_GUIDE.md' target='_blank'>Complete RBAC Guide</a></li>
    <li><a href='RBAC_QUICK_SETUP.md' target='_blank'>Quick Setup Guide</a></li>
    <li><a href='USER_ACCOUNTS_REFERENCE.md' target='_blank'>User Accounts Reference</a></li>
</ul>";

echo "</div>";

echo "</div></body></html>";
mysqli_close($conn);
?>

