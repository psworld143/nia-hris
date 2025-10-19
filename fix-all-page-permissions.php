<?php
/**
 * Fix All Page Permissions - Add super_admin to role checks
 * This script updates all pages to include super_admin in their access controls
 */

echo "<!DOCTYPE html><html><head><title>Fix Page Permissions</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1100px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .warning { color: #FF9800; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
</style></head><body><div class='container'>";

echo "<h2>🔧 Fix All Page Permissions</h2>";
echo "<p>Adding 'super_admin' to role checks in all pages...</p><hr>";

// List of files that need updating with their expected role checks
$files_to_update = [
    'admin-employee.php' => ['super_admin', 'admin', 'hr_manager', 'human_resource'],
    'salary-structures.php' => ['super_admin', 'admin', 'hr_manager'],
    'leave-management.php' => ['super_admin', 'admin', 'hr_manager', 'human_resource'],
    'leave-allowance-management.php' => ['super_admin', 'admin', 'hr_manager', 'human_resource'],
    'leave-reports.php' => ['super_admin', 'admin', 'hr_manager', 'human_resource'],
    'auto-increment-management.php' => ['super_admin', 'admin', 'hr_manager'],
    'regularization-criteria.php' => ['super_admin', 'admin', 'hr_manager'],
    'manage-regularization.php' => ['super_admin', 'admin', 'hr_manager'],
    'manage-degrees.php' => ['super_admin', 'admin', 'hr_manager'],
    'government-benefits.php' => ['super_admin', 'admin', 'hr_manager'],
    'payroll-management.php' => ['super_admin', 'admin', 'hr_manager'],
    'payroll-process.php' => ['super_admin', 'admin', 'hr_manager'],
    'performance-reviews.php' => ['super_admin', 'admin', 'hr_manager'],
    'training-programs.php' => ['super_admin', 'admin', 'hr_manager'],
];

echo "<table>";
echo "<tr><th>File</th><th>Status</th><th>Details</th></tr>";

$updated = 0;
$already_ok = 0;
$errors = 0;

foreach ($files_to_update as $filename => $roles) {
    $filepath = __DIR__ . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "<tr><td>{$filename}</td><td><span class='warning'>⊗ Not Found</span></td><td>File doesn't exist</td></tr>";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if file already has super_admin in role check
    if (strpos($content, "'super_admin'") !== false) {
        echo "<tr><td>{$filename}</td><td><span class='success'>✓ OK</span></td><td>Already includes super_admin</td></tr>";
        $already_ok++;
        continue;
    }
    
    // Find and replace the role check pattern
    $patterns = [
        // Pattern 1: in_array with specific roles
        "/if\s*\(\s*!\s*isset\(\s*\\\$_SESSION\['user_id'\]\s*\)\s*\|\|\s*!\s*in_array\(\s*\\\$_SESSION\['role'\],\s*\[\s*'admin',\s*'human_resource',\s*'hr_manager'\s*\]\s*\)\)/i",
        "/if\s*\(\s*!\s*isset\(\s*\\\$_SESSION\['user_id'\]\s*\)\s*\|\|\s*!\s*in_array\(\s*\\\$_SESSION\['role'\],\s*\[\s*'admin',\s*'hr_manager'\s*\]\s*\)\)/i",
    ];
    
    $roles_string = "'" . implode("', '", $roles) . "'";
    $replacement = "if (!isset(\$_SESSION['user_id']) || !in_array(\$_SESSION['role'], [{$roles_string}]))";
    
    $updated_content = $content;
    $was_updated = false;
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $updated_content = preg_replace($pattern, $replacement, $content);
            $was_updated = true;
            break;
        }
    }
    
    if ($was_updated) {
        if (file_put_contents($filepath, $updated_content)) {
            echo "<tr><td>{$filename}</td><td><span class='success'>✓ Updated</span></td><td>Added super_admin to roles</td></tr>";
            $updated++;
        } else {
            echo "<tr><td>{$filename}</td><td><span class='error'>✗ Error</span></td><td>Could not write file</td></tr>";
            $errors++;
        }
    } else {
        echo "<tr><td>{$filename}</td><td><span class='warning'>⊗ Skip</span></td><td>No standard role check pattern found</td></tr>";
    }
}

echo "</table>";

echo "<h3>Summary</h3>";
echo "<p><strong>Updated:</strong> {$updated} files</p>";
echo "<p><strong>Already OK:</strong> {$already_ok} files</p>";
echo "<p><strong>Errors:</strong> {$errors} files</p>";

if ($updated > 0) {
    echo "<div style='background: #e8f5e9; border: 2px solid #4CAF50; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #4CAF50; margin-top: 0;'>✅ Success!</h3>";
    echo "<p>{$updated} files were updated to include super_admin role.</p>";
    echo "<p><strong>Next:</strong> Try accessing the Departments page again as Super Admin.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<a href='manage-departments.php' class='btn'>Go to Departments</a>";
echo "<a href='index.php' class='btn' style='background: #2196F3;'>Dashboard</a>";

echo "</div></body></html>";
?>

