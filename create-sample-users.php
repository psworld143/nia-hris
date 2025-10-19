<?php
/**
 * Create Sample User Accounts for Each Role
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Create Sample Users</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; margin-top: 0; }
    h3 { color: #555; background: linear-gradient(to right, #f0f8ff, #fff); padding: 12px; border-left: 4px solid #2196F3; margin-top: 25px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white; }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; color: white; }
    .badge-super { background: #ff6b6b; }
    .badge-admin { background: #4CAF50; }
    .badge-hr { background: #2196F3; }
    .badge-nurse { background: #9c27b0; }
    .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0; }
    .info { background: #e3f2fd; border: 1px solid #2196F3; padding: 15px; border-radius: 8px; margin: 20px 0; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
</style></head><body><div class='container'>";

echo "<h2>👥 Create Sample User Accounts</h2>";

// Check if roles have been updated
echo "<h3>Step 1: Checking Role System</h3>";
$check_roles = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_column = mysqli_fetch_assoc($check_roles);
$current_enum = $role_column['Type'];

if (strpos($current_enum, 'super_admin') === false || strpos($current_enum, 'nurse') === false) {
    echo "<div style='background:#ffebee; border:2px solid #f44336; padding:20px; border-radius:10px; margin:20px 0;'>
        <h3 style='color:#d32f2f; margin-top:0;'>❌ Role System Not Updated!</h3>
        <p><strong>Current role enum:</strong> <code>{$current_enum}</code></p>
        <p><strong>Problem:</strong> The user roles table doesn't include 'super_admin' and 'nurse' roles yet.</p>
        <p><strong>Solution:</strong> You must run the role setup script FIRST before creating users.</p>
        <hr style='margin:20px 0;'>
        <h4>🔧 Quick Fix:</h4>
        <ol style='margin:10px 0; padding-left:20px;'>
            <li><strong>Step 1:</strong> Go to <a href='setup-user-roles.php' style='color:#2196F3; font-weight:bold;'>setup-user-roles.php</a></li>
            <li><strong>Step 2:</strong> Run the role setup (takes 1 minute)</li>
            <li><strong>Step 3:</strong> Come back here and refresh this page</li>
        </ol>
        <a href='setup-user-roles.php' style='display:inline-block; padding:15px 30px; background:#f44336; color:white; text-decoration:none; border-radius:8px; margin-top:15px; font-weight:bold; font-size:16px;'>
            → Run Role Setup First
        </a>
    </div>";
    echo "</div></body></html>";
    exit();
}

echo "<span class='success'>✓ Role system is properly configured</span><br>";
echo "<p>Current roles: <code>{$current_enum}</code></p><br>";

echo "<div class='warning'>
    <strong>⚠️ Important Security Notice:</strong><br>
    These are SAMPLE accounts with DEFAULT passwords. You MUST change all passwords after creation!<br>
    Default passwords are NOT secure and should only be used for initial testing.
</div>";

// Sample users to create
$sample_users = [
    [
        'username' => 'superadmin',
        'password' => 'Super@2024',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'superadmin@nia.gov.ph',
        'role' => 'super_admin',
        'description' => 'System administrator with full access'
    ],
    [
        'username' => 'admin',
        'password' => 'admin123', // This already exists, will skip
        'first_name' => 'System',
        'last_name' => 'Administrator',
        'email' => 'admin@nia-hris.com',
        'role' => 'admin',
        'description' => 'Default admin (already exists)'
    ],
    [
        'username' => 'hrmanager',
        'password' => 'HRM@2024',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'email' => 'hrmanager@nia.gov.ph',
        'role' => 'hr_manager',
        'description' => 'HR Manager for staff management'
    ],
    [
        'username' => 'hrstaff',
        'password' => 'HRStaff@2024',
        'first_name' => 'Pedro',
        'last_name' => 'Santos',
        'email' => 'hrstaff@nia.gov.ph',
        'role' => 'human_resource',
        'description' => 'HR Staff for daily operations'
    ],
    [
        'username' => 'nurse1',
        'password' => 'Nurse@2024',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'email' => 'nurse@nia.gov.ph',
        'role' => 'nurse',
        'description' => 'Nurse for medical records management'
    ]
];

echo "<h3>Creating User Accounts</h3>";

$created_count = 0;
$skipped_count = 0;
$results = [];

foreach ($sample_users as $user) {
    // Check if user already exists
    $check_query = "SELECT id, username FROM users WHERE username = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $user['username']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        $results[] = [
            'username' => $user['username'],
            'status' => 'exists',
            'message' => 'Already exists',
            'password' => $user['password'],
            'role' => $user['role']
        ];
        $skipped_count++;
    } else {
        // Create the user
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (username, password, first_name, last_name, email, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssssss", 
            $user['username'], $hashed_password, $user['first_name'], 
            $user['last_name'], $user['email'], $user['role']
        );
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $results[] = [
                'username' => $user['username'],
                'status' => 'created',
                'message' => 'Successfully created',
                'password' => $user['password'],
                'role' => $user['role']
            ];
            $created_count++;
        } else {
            $results[] = [
                'username' => $user['username'],
                'status' => 'error',
                'message' => mysqli_error($conn),
                'password' => $user['password'],
                'role' => $user['role']
            ];
        }
    }
}

// Display results table
echo "<table>";
echo "<tr>
        <th>Username</th>
        <th>Password</th>
        <th>Role</th>
        <th>Status</th>
        <th>Message</th>
      </tr>";

foreach ($results as $result) {
    $status_color = '';
    $badge_class = '';
    
    switch ($result['status']) {
        case 'created':
            $status_color = 'color: #4CAF50;';
            break;
        case 'exists':
            $status_color = 'color: #FF9800;';
            break;
        case 'error':
            $status_color = 'color: #f44336;';
            break;
    }
    
    switch ($result['role']) {
        case 'super_admin': $badge_class = 'badge-super'; break;
        case 'admin': $badge_class = 'badge-admin'; break;
        case 'hr_manager': $badge_class = 'badge-hr'; break;
        case 'human_resource': $badge_class = 'badge-hr'; break;
        case 'nurse': $badge_class = 'badge-nurse'; break;
    }
    
    echo "<tr>";
    echo "<td><strong>{$result['username']}</strong></td>";
    echo "<td><code>{$result['password']}</code></td>";
    echo "<td><span class='badge {$badge_class}'>" . strtoupper(str_replace('_', ' ', $result['role'])) . "</span></td>";
    echo "<td style='{$status_color}'><strong>{$result['status']}</strong></td>";
    echo "<td>{$result['message']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p><strong>✓ Created:</strong> {$created_count} new account(s)</p>";
echo "<p><strong>⊗ Skipped:</strong> {$skipped_count} existing account(s)</p>";

echo "<div class='info'>
    <h4 style='margin-top:0;'>📋 Account Credentials Summary</h4>
    <table style='margin:0;'>
        <tr>
            <th>Role</th>
            <th>Username</th>
            <th>Password</th>
            <th>Purpose</th>
        </tr>
        <tr>
            <td><span class='badge badge-super'>SUPER ADMIN</span></td>
            <td><code>superadmin</code></td>
            <td><code>Super@2024</code></td>
            <td>Full system access</td>
        </tr>
        <tr>
            <td><span class='badge badge-admin'>ADMIN</span></td>
            <td><code>admin</code></td>
            <td><code>admin123</code></td>
            <td>Administrative access (existing)</td>
        </tr>
        <tr>
            <td><span class='badge badge-hr'>HR MANAGER</span></td>
            <td><code>hrmanager</code></td>
            <td><code>HRM@2024</code></td>
            <td>HR management functions</td>
        </tr>
        <tr>
            <td><span class='badge badge-hr'>HR STAFF</span></td>
            <td><code>hrstaff</code></td>
            <td><code>HRStaff@2024</code></td>
            <td>HR daily operations</td>
        </tr>
        <tr>
            <td><span class='badge badge-nurse'>NURSE</span></td>
            <td><code>nurse1</code></td>
            <td><code>Nurse@2024</code></td>
            <td>Medical records management</td>
        </tr>
    </table>
</div>";

echo "<div class='warning'>
    <h4 style='margin-top:0;'>🔒 Security Recommendations</h4>
    <ol style='margin:10px 0;'>
        <li><strong>Change all passwords immediately</strong> after first login</li>
        <li>Use strong passwords (8+ characters, mixed case, numbers, symbols)</li>
        <li>Never share passwords between users</li>
        <li>Enable two-factor authentication if available</li>
        <li>Review user access quarterly</li>
        <li>Remove inactive accounts promptly</li>
    </ol>
</div>";

echo "<hr>";
echo "<h3 style='color: #4CAF50;'>✓ User Creation Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>
        <li>Login with each account to verify access</li>
        <li>Change all default passwords</li>
        <li>Test role permissions</li>
        <li>Document new passwords securely</li>
      </ol>";

echo "<a href='login.php' style='display:inline-block; padding:12px 24px; background:#4CAF50; color:white; text-decoration:none; border-radius:8px; margin-top:10px; font-weight:600;'>
        <i class='fas fa-sign-in-alt'></i> Go to Login
      </a>";

echo "<a href='medical-records.php' style='display:inline-block; padding:12px 24px; background:#9c27b0; color:white; text-decoration:none; border-radius:8px; margin-top:10px; margin-left:10px; font-weight:600;'>
        <i class='fas fa-heartbeat'></i> Medical Records (Nurse)
      </a>";

echo "</div></body></html>";
mysqli_close($conn);
?>

