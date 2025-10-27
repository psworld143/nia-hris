<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'Database Export';

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_database'])) {
    // Redirect to export script
    header('Location: export-database.php');
    exit();
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-database mr-2"></i>Database Export
                </h2>
                <p class="opacity-90">Export the complete NIA HRIS database with proper constraint handling</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Export Information -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-500"></i>Export Information
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="font-medium text-gray-700 mb-2">What's Included:</h4>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><i class="fas fa-check text-green-500 mr-2"></i>Complete database structure</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i>All table data</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i>Proper constraint handling</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i>Cross-platform compatibility</li>
                <li><i class="fas fa-check text-green-500 mr-2"></i>Foreign key management</li>
            </ul>
        </div>
        
        <div>
            <h4 class="font-medium text-gray-700 mb-2">Export Features:</h4>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><i class="fas fa-shield-alt text-blue-500 mr-2"></i>Constraint-safe import</li>
                <li><i class="fas fa-sort text-blue-500 mr-2"></i>Dependency-ordered tables</li>
                <li><i class="fas fa-compress text-blue-500 mr-2"></i>Batch data export</li>
                <li><i class="fas fa-code text-blue-500 mr-2"></i>Proper SQL escaping</li>
                <li><i class="fas fa-clock text-blue-500 mr-2"></i>Timestamped filename</li>
            </ul>
        </div>
    </div>
</div>

<!-- Database Statistics -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-chart-bar mr-2 text-green-500"></i>Database Statistics
    </h3>
    
    <?php
    // Get database statistics
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM users) as users_count,
        (SELECT COUNT(*) FROM employees) as employees_count,
        (SELECT COUNT(*) FROM departments) as departments_count,
        (SELECT COUNT(*) FROM leave_types) as leave_types_count,
        (SELECT COUNT(*) FROM salary_structures) as salary_structures_count,
        (SELECT COUNT(*) FROM payroll_periods) as payroll_periods_count,
        (SELECT COUNT(*) FROM payroll_records) as payroll_records_count";
    
    $stats_result = mysqli_query($conn, $stats_query);
    $stats = mysqli_fetch_assoc($stats_result);
    
    // Get total tables count
    $tables_query = "SHOW TABLES FROM nia_hris";
    $tables_result = mysqli_query($conn, $tables_query);
    $total_tables = mysqli_num_rows($tables_result);
    ?>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600"><?php echo $total_tables; ?></div>
            <div class="text-sm text-gray-600">Total Tables</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $stats['users_count']; ?></div>
            <div class="text-sm text-gray-600">Users</div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-purple-600"><?php echo $stats['employees_count']; ?></div>
            <div class="text-sm text-gray-600">Employees</div>
        </div>
        <div class="bg-orange-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-orange-600"><?php echo $stats['payroll_records_count']; ?></div>
            <div class="text-sm text-gray-600">Payroll Records</div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-download mr-2 text-red-500"></i>Export Options
    </h3>
    
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h4 class="font-medium text-gray-800">Web Export</h4>
                <p class="text-sm text-gray-600">Download directly through your browser</p>
            </div>
            <form method="POST" class="inline">
                <button type="submit" name="export_database" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-download mr-2"></i>Export Now
                </button>
            </form>
        </div>
        
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
                <h4 class="font-medium text-gray-800">Command Line Export</h4>
                <p class="text-sm text-gray-600">Use CLI script for server-side export</p>
            </div>
            <div class="text-sm text-gray-600 font-mono bg-gray-100 px-3 py-2 rounded">
                php export-database-cli.php
            </div>
        </div>
    </div>
</div>

<!-- Import Instructions -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-upload mr-2 text-green-500"></i>Import Instructions
    </h3>
    
    <div class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-medium text-blue-800 mb-2">Method 1: Command Line</h4>
            <div class="bg-gray-100 p-3 rounded font-mono text-sm">
                <div># Create new database</div>
                <div>mysql -u root -p -e "CREATE DATABASE nia_hris;"</div>
                <div class="mt-2"># Import the SQL file</div>
                <div>mysql -u root -p nia_hris &lt; exported_file.sql</div>
            </div>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
            <h4 class="font-medium text-green-800 mb-2">Method 2: phpMyAdmin</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
                <li>Open phpMyAdmin</li>
                <li>Create a new database named "nia_hris"</li>
                <li>Select the database</li>
                <li>Go to "Import" tab</li>
                <li>Choose the exported SQL file</li>
                <li>Click "Go" to import</li>
            </ol>
        </div>
        
        <div class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="font-medium text-yellow-800 mb-2">Important Notes</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
                <li>The export includes proper constraint handling to avoid import errors</li>
                <li>Foreign key checks are disabled during import and re-enabled after</li>
                <li>Tables are exported in dependency order (parent tables first)</li>
                <li>All data is properly escaped for cross-platform compatibility</li>
                <li>The export file is timestamped for easy identification</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
