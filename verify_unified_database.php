<?php
/**
 * Verify that all PHP files use unified database connection
 */

echo "<h1>Database Connection Verification</h1>";
echo "<p>Checking that all files use config/database.php...</p><hr>";

$root_dir = __DIR__;
$files = glob($root_dir . '/*.php');

$using_config = [];
$standalone_connections = [];
$no_db_needed = [];

foreach ($files as $file) {
    $basename = basename($file);
    
    // Skip config itself and template files
    if ($basename === 'database.php' || strpos($basename, 'template') !== false) {
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if file uses database
    $uses_db = (strpos($content, '$conn') !== false || 
                strpos($content, 'mysqli_') !== false ||
                strpos($content, 'SELECT ') !== false);
    
    if (!$uses_db) {
        $no_db_needed[] = $basename;
        continue;
    }
    
    // Check if uses config
    $uses_config = (strpos($content, "require_once 'config/database.php'") !== false ||
                    strpos($content, 'require_once "config/database.php"') !== false ||
                    strpos($content, "require_once __DIR__ . '/config/database.php'") !== false);
    
    // Check for standalone connection
    $has_standalone = (preg_match('/mysqli_connect\s*\([^)]*nia_hris/i', $content) > 0 &&
                      !$uses_config);
    
    if ($uses_config) {
        $using_config[] = $basename;
    } elseif ($has_standalone) {
        $standalone_connections[] = $basename;
    }
}

echo "<h2>Results</h2>";

echo "<h3 style='color: green;'>✓ Using Unified Config (" . count($using_config) . " files)</h3>";
if (!empty($using_config)) {
    echo "<ul>";
    foreach ($using_config as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

echo "<h3 style='color: red;'>✗ Standalone Connections (" . count($standalone_connections) . " files)</h3>";
if (!empty($standalone_connections)) {
    echo "<ul>";
    foreach ($standalone_connections as $file) {
        echo "<li><strong>$file</strong> - NEEDS FIX</li>";
    }
    echo "</ul>";
} else {
    echo "<p><em>None found - All good! ✓</em></p>";
}

echo "<h3>ℹ️ No Database Connection Needed (" . count($no_db_needed) . " files)</h3>";
echo "<p><em>Files like logout.php, templates, etc.</em></p>";

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Database Configuration:</strong> config/database.php → nia_hris</p>";
echo "<p><strong>Status:</strong> " . (empty($standalone_connections) ? "✅ ALL FILES USE UNIFIED CONFIG" : "⚠️ SOME FILES NEED FIXING") . "</p>";

echo "<p><a href='index.php'>Back to Dashboard</a></p>";
?>

