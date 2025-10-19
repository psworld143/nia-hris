<?php
/**
 * Complete Setup Wizard - All-in-One Setup Script
 * Runs all setup scripts and verifies the system is ready
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Complete Setup Wizard</title>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 30px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    h2 { color: #333; border-bottom: 4px solid #4CAF50; padding-bottom: 15px; margin-top: 0; }
    .wizard-step { background: #f9f9f9; padding: 25px; margin: 25px 0; border-radius: 10px; border-left: 6px solid #2196F3; position: relative; }
    .wizard-step.complete { border-left-color: #4CAF50; background: #f1f8f4; }
    .wizard-step.error { border-left-color: #f44336; background: #ffebee; }
    .step-number { position: absolute; top: -15px; left: 20px; background: #2196F3; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .step-number.complete { background: #4CAF50; }
    .step-number.error { background: #f44336; }
    h3 { color: #555; margin: 0 0 15px 0; padding-top: 10px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin: 15px 0; }
    .info { background: #e3f2fd; border: 2px solid #2196F3; padding: 15px; border-radius: 8px; margin: 15px 0; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; color: white; }
    .badge-super { background: #ff6b6b; }
    .badge-admin { background: #4CAF50; }
    .badge-hr { background: #2196F3; }
    .badge-nurse { background: #9c27b0; }
    .btn { display: inline-block; padding: 12px 24px; margin: 5px; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
    .btn-primary { background: #4CAF50; } .btn-primary:hover { background: #45a049; transform: translateY(-2px); }
    .btn-secondary { background: #2196F3; } .btn-secondary:hover { background: #0b7dda; transform: translateY(-2px); }
    .btn-danger { background: #f44336; } .btn-danger:hover { background: #da190b; transform: translateY(-2px); }
    .progress { background: #e0e0e0; height: 40px; border-radius: 20px; overflow: hidden; margin: 25px 0; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
    .progress-bar { background: linear-gradient(90deg, #4CAF50, #45a049); height: 100%; display: flex; align-items: center; padding-left: 20px; color: white; font-weight: bold; font-size: 16px; transition: width 0.5s ease; }
    code { background: #f5f5f5; padding: 3px 8px; border-radius: 4px; font-family: 'Courier New', monospace; color: #d63384; font-size: 13px; }
    .checkmark { color: #4CAF50; font-size: 24px; }
    .xmark { color: #f44336; font-size: 24px; }
</style></head><body><div class='container'>";

echo "<h2>🧙‍♂️ Complete Setup Wizard</h2>";
echo "<p style='font-size: 16px; color: #666;'>This wizard will configure your NIA-HRIS system with role-based access control.</p>";

$total_steps = 4;
$completed_steps = 0;
$has_errors = false;

// ============================================================================
// STEP 1: Check and Update User Roles
// ============================================================================
echo "<div class='wizard-step' id='step1'>";
echo "<div class='step-number'>1</div>";
echo "<h3>🔐 User Roles System</h3>";

$check_roles = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_column = mysqli_fetch_assoc($check_roles);
$current_enum = $role_column['Type'];

echo "<p><strong>Current:</strong> <code>{$current_enum}</code></p>";

if (strpos($current_enum, 'super_admin') !== false && strpos($current_enum, 'nurse') !== false) {
    echo "<p class='success'>✓ Role enum already includes super_admin and nurse</p>";
    $completed_steps++;
    echo "<script>document.getElementById('step1').classList.add('complete'); document.getElementById('step1').querySelector('.step-number').classList.add('complete');</script>";
} else {
    echo "<p style='color: #FF9800;'>⚠ Updating role enum...</p>";
    
    $alter_query = "ALTER TABLE users 
                    MODIFY COLUMN role ENUM('super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse') 
                    NOT NULL DEFAULT 'human_resource'";
    
    if (mysqli_query($conn, $alter_query)) {
        echo "<p class='success'>✓ Successfully updated role enum</p>";
        
        // Update admin to super_admin
        mysqli_query($conn, "UPDATE users SET role = 'super_admin' WHERE username = 'admin'");
        
        $completed_steps++;
        echo "<script>document.getElementById('step1').classList.add('complete'); document.getElementById('step1').querySelector('.step-number').classList.add('complete');</script>";
    } else {
        echo "<p class='error'>✗ Error: " . mysqli_error($conn) . "</p>";
        $has_errors = true;
        echo "<script>document.getElementById('step1').classList.add('error'); document.getElementById('step1').querySelector('.step-number').classList.add('error');</script>";
    }
}
echo "</div>";

// ============================================================================
// STEP 2: Add Medical Record Fields
// ============================================================================
echo "<div class='wizard-step' id='step2'>";
echo "<div class='step-number'>2</div>";
echo "<h3>🏥 Medical Record Fields</h3>";

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

$existing_columns = [];
$columns_result = mysqli_query($conn, "SHOW COLUMNS FROM employees");
while ($col = mysqli_fetch_assoc($columns_result)) {
    $existing_columns[] = $col['Field'];
}

$added = 0;
$existing = 0;
$step2_errors = 0;

foreach ($fields_to_add as $field) {
    $field_name = explode(' ', $field)[0];
    
    if (in_array($field_name, $existing_columns)) {
        $existing++;
    } else {
        $alter_query = "ALTER TABLE employees ADD COLUMN $field";
        if (mysqli_query($conn, $alter_query)) {
            echo "✓ Added: <strong>{$field_name}</strong><br>";
            $added++;
        } else {
            echo "✗ Error adding {$field_name}<br>";
            $step2_errors++;
        }
    }
}

if ($existing === count($fields_to_add)) {
    echo "<p class='success'>✓ All medical fields already exist ({$existing})</p>";
    $completed_steps++;
    echo "<script>document.getElementById('step2').classList.add('complete'); document.getElementById('step2').querySelector('.step-number').classList.add('complete');</script>";
} elseif ($step2_errors === 0) {
    echo "<p class='success'>✓ Added {$added} fields, {$existing} already existed</p>";
    $completed_steps++;
    echo "<script>document.getElementById('step2').classList.add('complete'); document.getElementById('step2').querySelector('.step-number').classList.add('complete');</script>";
} else {
    echo "<p class='error'>✗ {$step2_errors} error(s) occurred</p>";
    $has_errors = true;
    echo "<script>document.getElementById('step2').classList.add('error'); document.getElementById('step2').querySelector('.step-number').classList.add('error');</script>";
}
echo "</div>";

// ============================================================================
// STEP 3: Create User Accounts
// ============================================================================
echo "<div class='wizard-step' id='step3'>";
echo "<div class='step-number'>3</div>";
echo "<h3>👥 User Accounts</h3>";

$sample_users = [
    ['username' => 'superadmin', 'password' => 'Super@2024', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'superadmin@nia.gov.ph', 'role' => 'super_admin', 'badge' => 'badge-super'],
    ['username' => 'hrmanager', 'password' => 'HRM@2024', 'first_name' => 'Maria', 'last_name' => 'Garcia', 'email' => 'hrmanager@nia.gov.ph', 'role' => 'hr_manager', 'badge' => 'badge-hr'],
    ['username' => 'hrstaff', 'password' => 'HRStaff@2024', 'first_name' => 'Pedro', 'last_name' => 'Santos', 'email' => 'hrstaff@nia.gov.ph', 'role' => 'human_resource', 'badge' => 'badge-hr'],
    ['username' => 'nurse1', 'password' => 'Nurse@2024', 'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'nurse@nia.gov.ph', 'role' => 'nurse', 'badge' => 'badge-nurse']
];

echo "<table>";
echo "<tr><th>Username</th><th>Password</th><th>Role</th><th>Status</th></tr>";

$created = 0;
$skipped = 0;

foreach ($sample_users as $user) {
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($check, "s", $user['username']);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    
    echo "<tr>";
    echo "<td><strong>{$user['username']}</strong></td>";
    echo "<td><code>{$user['password']}</code></td>";
    echo "<td><span class='badge {$user['badge']}'>" . strtoupper(str_replace('_', ' ', $user['role'])) . "</span></td>";
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<td><span style='color: #FF9800;'>Already Exists</span></td>";
        $skipped++;
    } else {
        $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
        $insert = mysqli_prepare($conn, "INSERT INTO users (username, password, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        mysqli_stmt_bind_param($insert, "ssssss", $user['username'], $hashed, $user['first_name'], $user['last_name'], $user['email'], $user['role']);
        
        if (mysqli_stmt_execute($insert)) {
            echo "<td><span class='success'>✓ Created</span></td>";
            $created++;
        } else {
            echo "<td><span class='error'>✗ Error</span></td>";
        }
    }
    echo "</tr>";
}

echo "</table>";
echo "<p>Created: {$created} | Existing: {$skipped}</p>";

if ($created > 0 || $skipped === count($sample_users)) {
    $completed_steps++;
    echo "<script>document.getElementById('step3').classList.add('complete'); document.getElementById('step3').querySelector('.step-number').classList.add('complete');</script>";
}
echo "</div>";

// ============================================================================
// STEP 4: Verify System
// ============================================================================
echo "<div class='wizard-step' id='step4'>";
echo "<div class='step-number'>4</div>";
echo "<h3>✅ System Verification</h3>";

$all_good = true;

// Check roles
$role_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_info = mysqli_fetch_assoc($role_check);
if (strpos($role_info['Type'], 'super_admin') !== false && strpos($role_info['Type'], 'nurse') !== false) {
    echo "<span class='checkmark'>✓</span> Role enum configured correctly<br>";
} else {
    echo "<span class='xmark'>✗</span> Role enum missing super_admin or nurse<br>";
    $all_good = false;
}

// Check medical fields
$req_fields = ['blood_type', 'medical_conditions', 'allergies', 'medications', 'last_medical_checkup', 'medical_notes'];
$emp_columns = [];
$col_result = mysqli_query($conn, "SHOW COLUMNS FROM employees");
while ($c = mysqli_fetch_assoc($col_result)) {
    $emp_columns[] = $c['Field'];
}

$missing_fields = array_diff($req_fields, $emp_columns);
if (empty($missing_fields)) {
    echo "<span class='checkmark'>✓</span> Medical record fields exist<br>";
} else {
    echo "<span class='xmark'>✗</span> Missing fields: " . implode(', ', $missing_fields) . "<br>";
    $all_good = false;
}

// Check accounts
$user_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role IN ('super_admin', 'nurse')")->fetch_assoc()['count'];
if ($user_count >= 2) {
    echo "<span class='checkmark'>✓</span> Super Admin and Nurse accounts exist<br>";
} else {
    echo "<span class='xmark'>✗</span> Missing required accounts<br>";
    $all_good = false;
}

if ($all_good) {
    $completed_steps++;
    echo "<script>document.getElementById('step4').classList.add('complete'); document.getElementById('step4').querySelector('.step-number').classList.add('complete');</script>";
}
echo "</div>";

// ============================================================================
// PROGRESS AND SUMMARY
// ============================================================================
$progress = ($completed_steps / $total_steps) * 100;

echo "<div class='progress'>";
echo "<div class='progress-bar' style='width: {$progress}%;'>{$completed_steps}/{$total_steps} Steps Complete - " . round($progress) . "%</div>";
echo "</div>";

if ($completed_steps === $total_steps) {
    echo "<div class='info' style='border-color: #4CAF50; background: #f1f8f4;'>";
    echo "<h3 style='color: #4CAF50; margin-top: 0;'>🎉 Setup Complete!</h3>";
    echo "<p>Your NIA-HRIS system is fully configured with role-based access control.</p>";
    
    echo "<h4>📋 Account Credentials:</h4>";
    echo "<table>";
    echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
    echo "<tr><td><code>superadmin</code></td><td><code>Super@2024</code></td><td><span class='badge badge-super'>SUPER ADMIN</span></td></tr>";
    echo "<tr><td><code>admin</code></td><td><code>admin123</code></td><td><span class='badge badge-admin'>ADMIN</span></td></tr>";
    echo "<tr><td><code>hrmanager</code></td><td><code>HRM@2024</code></td><td><span class='badge badge-hr'>HR MANAGER</span></td></tr>";
    echo "<tr><td><code>hrstaff</code></td><td><code>HRStaff@2024</code></td><td><span class='badge badge-hr'>HR STAFF</span></td></tr>";
    echo "<tr><td><code>nurse1</code></td><td><code>Nurse@2024</code></td><td><span class='badge badge-nurse'>NURSE</span></td></tr>";
    echo "</table>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ SECURITY:</strong> Change all default passwords immediately after first login!";
    echo "</div>";
    
    echo "<h4>🚀 Quick Start:</h4>";
    echo "<div>";
    echo "<a href='login.php' class='btn btn-primary'><i class='fas fa-sign-in-alt'></i> Login Now</a>";
    echo "<a href='medical-records.php' class='btn btn-secondary'><i class='fas fa-heartbeat'></i> Medical Records</a>";
    echo "<a href='verify-accounts.php' class='btn btn-secondary'><i class='fas fa-check-circle'></i> Verify Accounts</a>";
    echo "</div>";
    
    echo "</div>";
} else {
    echo "<div class='warning'>";
    echo "<h3 style='margin-top: 0;'>⚠️ Setup Incomplete</h3>";
    echo "<p>Completed {$completed_steps} of {$total_steps} steps. Please review errors above.</p>";
    echo "</div>";
}

echo "</div></body></html>";
mysqli_close($conn);
?>

