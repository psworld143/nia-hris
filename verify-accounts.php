<?php
/**
 * Verify User Accounts - Check if accounts exist and test passwords
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Verify Accounts</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; color: white; }
    .badge-super { background: #ff6b6b; }
    .badge-admin { background: #4CAF50; }
    .badge-hr { background: #2196F3; }
    .badge-nurse { background: #9c27b0; }
    code { background: #f5f5f5; padding: 2px 8px; border-radius: 4px; font-family: monospace; color: #d63384; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3; }
    .warning { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
</style></head><body><div class='container'>";

echo "<h2>🔍 Account Verification</h2>";

// Test accounts
$test_accounts = [
    ['username' => 'superadmin', 'password' => 'Super@2024', 'role' => 'super_admin', 'badge' => 'badge-super'],
    ['username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'badge' => 'badge-admin'],
    ['username' => 'hrmanager', 'password' => 'HRM@2024', 'role' => 'hr_manager', 'badge' => 'badge-hr'],
    ['username' => 'hrstaff', 'password' => 'HRStaff@2024', 'role' => 'human_resource', 'badge' => 'badge-hr'],
    ['username' => 'nurse1', 'password' => 'Nurse@2024', 'role' => 'nurse', 'badge' => 'badge-nurse']
];

echo "<table>";
echo "<tr>
        <th>Username</th>
        <th>Password</th>
        <th>Expected Role</th>
        <th>Account Status</th>
        <th>Password Test</th>
        <th>Action</th>
      </tr>";

$working_accounts = 0;
$total_accounts = count($test_accounts);

foreach ($test_accounts as $account) {
    echo "<tr>";
    echo "<td><strong>{$account['username']}</strong></td>";
    echo "<td><code>{$account['password']}</code></td>";
    echo "<td><span class='badge {$account['badge']}'>" . strtoupper(str_replace('_', ' ', $account['role'])) . "</span></td>";
    
    // Check if account exists
    $query = "SELECT id, username, password, role, status FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $account['username']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Account exists
        $status_color = ($user['status'] === 'active') ? 'success' : 'error';
        echo "<td><span class='{$status_color}'>✓ Exists ({$user['status']})</span></td>";
        
        // Test password
        if (password_verify($account['password'], $user['password'])) {
            echo "<td><span class='success'>✓ Password Valid</span></td>";
            $working_accounts++;
            
            // Create test login form
            echo "<td>
                <form method='POST' action='login.php' style='margin:0;'>
                    <input type='hidden' name='username' value='{$account['username']}'>
                    <input type='hidden' name='password' value='{$account['password']}'>
                    <button type='submit' style='background:#4CAF50; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;'>
                        Test Login
                    </button>
                </form>
            </td>";
        } else {
            echo "<td><span class='error'>✗ Password Invalid</span></td>";
            echo "<td>-</td>";
        }
        
        // Check role mismatch
        if ($user['role'] !== $account['role']) {
            echo "</tr><tr><td colspan='6' style='background:#fff3cd;'>
                ⚠️ <strong>Role Mismatch:</strong> Expected <code>{$account['role']}</code> but found <code>{$user['role']}</code>
            </td>";
        }
    } else {
        // Account doesn't exist
        echo "<td><span class='error'>✗ Not Found</span></td>";
        echo "<td>-</td>";
        echo "<td><a href='run-all-setup.php' style='color:#2196F3;'>Create Account</a></td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p><strong>Working Accounts:</strong> {$working_accounts} / {$total_accounts}</p>";

if ($working_accounts === $total_accounts) {
    echo "<div class='info'>
        <h4 style='margin-top:0;'>✅ All Accounts Working!</h4>
        <p>All {$total_accounts} accounts exist and passwords are valid. You can now login with any account.</p>
        <p><strong>Try logging in:</strong></p>
        <ul>
            <li>Username: <code>superadmin</code> | Password: <code>Super@2024</code></li>
            <li>Username: <code>nurse1</code> | Password: <code>Nurse@2024</code></li>
        </ul>
    </div>";
} else {
    echo "<div class='warning'>
        <h4 style='margin-top:0;'>⚠️ Some Accounts Have Issues</h4>
        <p>Please run the setup script to create missing accounts:</p>
        <a href='run-all-setup.php' style='display:inline-block; padding:10px 20px; background:#FF9800; color:white; text-decoration:none; border-radius:5px; margin-top:10px;'>
            Run Setup Script
        </a>
    </div>";
}

// Show current role enum
echo "<h3>Database Role Configuration</h3>";
$role_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$role_info = mysqli_fetch_assoc($role_check);
echo "<p><strong>Current role enum:</strong> <code>" . htmlspecialchars($role_info['Type']) . "</code></p>";

if (strpos($role_info['Type'], 'super_admin') !== false && strpos($role_info['Type'], 'nurse') !== false) {
    echo "<p class='success'>✓ Role enum includes super_admin and nurse</p>";
} else {
    echo "<p class='error'>✗ Role enum needs to be updated. <a href='setup-user-roles.php'>Run role setup</a></p>";
}

echo "<hr>";
echo "<h3>Quick Actions</h3>";
echo "<div>";
echo "<a href='login.php' style='display:inline-block; padding:12px 24px; background:#4CAF50; color:white; text-decoration:none; border-radius:8px; margin:5px; font-weight:600;'>
    Go to Login
</a>";
echo "<a href='run-all-setup.php' style='display:inline-block; padding:12px 24px; background:#2196F3; color:white; text-decoration:none; border-radius:8px; margin:5px; font-weight:600;'>
    Run Full Setup
</a>";
echo "</div>";

echo "</div></body></html>";
mysqli_close($conn);
?>

