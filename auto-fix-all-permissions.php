<?php
/**
 * Auto-Fix All Page Permissions
 * Automatically adds 'super_admin' to ALL pages that check for admin roles
 */

echo "<!DOCTYPE html><html><head><title>Auto-Fix Permissions</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 30px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3; }
    .warning { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 10px; text-align: left; position: sticky; top: 0; }
    td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
</style></head><body><div class='container'>";

echo "<h2>🔧 Auto-Fix All Permissions</h2>";
echo "<p>Scanning and fixing all PHP files to add super_admin to role checks...</p>";

$dir = __DIR__;
$files = glob($dir . '/*.php');

$updated = 0;
$already_ok = 0;
$skipped = 0;
$errors = 0;

echo "<table>";
echo "<tr><th style='width:40%;'>File</th><th>Before</th><th>After</th><th>Status</th></tr>";

foreach ($files as $filepath) {
    $filename = basename($filepath);
    
    // Skip this script and setup scripts
    if (in_array($filename, ['auto-fix-all-permissions.php', 'fix-all-page-permissions.php', 'complete-setup-wizard.php'])) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Skip if already has super_admin
    if (strpos($content, "'super_admin'") !== false) {
        continue;
    }
    
    // Pattern to find role checks without super_admin
    $patterns = [
        // Pattern 1: ['admin', 'human_resource', 'hr_manager']
        [
            'search' => "/in_array\(\s*\\\$_SESSION\['role'\],\s*\['admin',\s*'human_resource',\s*'hr_manager'\]\)/",
            'replace' => "in_array(\$_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])",
            'before' => "['admin', 'human_resource', 'hr_manager']",
            'after' => "['super_admin', 'admin', 'human_resource', 'hr_manager']"
        ],
        // Pattern 2: ['admin', 'hr_manager']
        [
            'search' => "/in_array\(\s*\\\$_SESSION\['role'\],\s*\['admin',\s*'hr_manager'\]\)/",
            'replace' => "in_array(\$_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])",
            'before' => "['admin', 'hr_manager']",
            'after' => "['super_admin', 'admin', 'hr_manager']"
        ],
        // Pattern 3: ['human_resource', 'hr_manager', 'admin'] (different order)
        [
            'search' => "/in_array\(\s*\\\$_SESSION\['role'\],\s*\['human_resource',\s*'hr_manager',\s*'admin'\]\)/",
            'replace' => "in_array(\$_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])",
            'before' => "['human_resource', 'hr_manager', 'admin']",
            'after' => "['super_admin', 'admin', 'human_resource', 'hr_manager']"
        ],
    ];
    
    $updated_content = $content;
    $was_updated = false;
    $before_text = '';
    $after_text = '';
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern['search'], $content)) {
            $updated_content = preg_replace($pattern['search'], $pattern['replace'], $content);
            $was_updated = true;
            $before_text = $pattern['before'];
            $after_text = $pattern['after'];
            break;
        }
    }
    
    if ($was_updated) {
        // Also fix redirect paths
        $updated_content = preg_replace("/'Location: \.\.\/index\.php'/", "'Location: index.php'", $updated_content);
        $updated_content = preg_replace("/'Location: \/seait\/index\.php[^']*'/", "'Location: index.php'", $updated_content);
        
        if (file_put_contents($filepath, $updated_content)) {
            echo "<tr>";
            echo "<td><strong>{$filename}</strong></td>";
            echo "<td style='font-size:11px;'><code>{$before_text}</code></td>";
            echo "<td style='font-size:11px;'><code>{$after_text}</code></td>";
            echo "<td><span class='success'>✓ Updated</span></td>";
            echo "</tr>";
            $updated++;
        } else {
            echo "<tr>";
            echo "<td>{$filename}</td>";
            echo "<td colspan='2'>-</td>";
            echo "<td><span class='error'>✗ Write Error</span></td>";
            echo "</tr>";
            $errors++;
        }
    }
}

echo "</table>";

echo "<div class='info'>";
echo "<h3 style='margin-top:0;'>📊 Summary</h3>";
echo "<p><strong>✓ Updated:</strong> {$updated} files</p>";
echo "<p><strong>✗ Errors:</strong> {$errors} files</p>";
echo "</div>";

if ($updated > 0) {
    echo "<div class='info' style='border-color: #4CAF50; background: #f1f8f4;'>";
    echo "<h3 style='color: #4CAF50; margin-top: 0;'>✅ Success!</h3>";
    echo "<p>{$updated} files were updated to include super_admin role.</p>";
    echo "<p><strong>What was changed:</strong></p>";
    echo "<ul>
        <li>✅ Added 'super_admin' to all role checks</li>
        <li>✅ Fixed redirect paths (removed '../' and '/seait/')</li>
        <li>✅ Standardized all redirects to 'index.php'</li>
    </ul>";
    echo "<div class='warning'>";
    echo "<strong>⚠️ Important:</strong> Logout and login again as Super Admin for changes to take effect.";
    echo "</div>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps</h3>";
echo "<ol>
    <li>Logout from your current session</li>
    <li>Login as Super Admin (superadmin / Super@2024)</li>
    <li>Test all sidebar menu items</li>
    <li>All pages should now work correctly</li>
</ol>";

echo "<a href='logout.php' class='btn' style='background:#f44336;'>Logout Now</a>";
echo "<a href='login.php' class='btn'>Go to Login</a>";
echo "<a href='salary-structures.php' class='btn' style='background:#2196F3;'>Test Salary Structures</a>";

echo "</div></body></html>";
?>

