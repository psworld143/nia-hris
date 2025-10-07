<?php
/**
 * Fix all role checks in the system
 * Updates all files to accept admin, human_resource, and hr_manager roles
 */

$root_dir = __DIR__;
$files_fixed = 0;
$files_scanned = 0;

echo "<h1>Fixing Role Checks Across All Files</h1><hr>";

// Get all PHP files
$php_files = glob($root_dir . '/*.php');
$api_files = glob($root_dir . '/api/*.php');
$all_files = array_merge($php_files, $api_files);

foreach ($all_files as $file) {
    $basename = basename($file);
    
    // Skip backup files and this script
    if (strpos($basename, '.backup') !== false || 
        strpos($basename, '.bak') !== false ||
        $basename === 'fix_all_role_checks.php') {
        continue;
    }
    
    $files_scanned++;
    $content = file_get_contents($file);
    $original_content = $content;
    $changes_made = false;
    
    // Pattern 1: $_SESSION['role'] !== 'human_resource'
    if (preg_match("/\\\$_SESSION\['role'\]\s*!==\s*'human_resource'/", $content)) {
        $content = preg_replace(
            "/\\\$_SESSION\['role'\]\s*!==\s*'human_resource'/",
            "!in_array(\$_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])",
            $content
        );
        $changes_made = true;
    }
    
    // Pattern 2: ($_SESSION['role'] !== 'human_resource' && $_SESSION['role'] !== 'hr_manager')
    if (preg_match("/\(\\\$_SESSION\['role'\]\s*!==\s*'human_resource'\s*&&\s*\\\$_SESSION\['role'\]\s*!==\s*'hr_manager'\)/", $content)) {
        $content = preg_replace(
            "/\(\\\$_SESSION\['role'\]\s*!==\s*'human_resource'\s*&&\s*\\\$_SESSION\['role'\]\s*!==\s*'hr_manager'\)/",
            "!in_array(\$_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])",
            $content
        );
        $changes_made = true;
    }
    
    // Pattern 3: || $_SESSION['role'] === 'human_resource' (allow pattern in login checks)
    // This is typically part of a larger OR condition, so we skip it
    
    if ($changes_made && $content !== $original_content) {
        file_put_contents($file, $content);
        echo "✓ Fixed: $basename<br>";
        $files_fixed++;
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Files Scanned:</strong> $files_scanned</p>";
echo "<p><strong>Files Fixed:</strong> $files_fixed</p>";
echo "<p><strong>Status:</strong> " . ($files_fixed > 0 ? "✅ Role checks updated" : "✅ All files already correct") . "</p>";

echo "<h3>Allowed Roles:</h3>";
echo "<ul>";
echo "<li><strong>admin</strong> - System Administrator (full access)</li>";
echo "<li><strong>human_resource</strong> - HR Staff</li>";
echo "<li><strong>hr_manager</strong> - HR Manager</li>";
echo "</ul>";

echo "<p><a href='index.php'>Back to Dashboard</a></p>";
?>

