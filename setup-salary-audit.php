<?php
/**
 * Salary Audit System Setup Script
 * 
 * This script sets up the salary audit system by creating necessary database tables
 * and performing initial configuration.
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Salary Audit System Setup';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

$setup_status = [];
$setup_errors = [];

// Handle setup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup_audit_system') {
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Read and execute the SQL file
        $sql_file = __DIR__ . '/database/salary_audit_system.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found: " . $sql_file);
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL content into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $result = mysqli_query($conn, $statement);
                if (!$result) {
                    throw new Exception("Error executing SQL: " . mysqli_error($conn) . "\nStatement: " . $statement);
                }
            }
        }
        
        // Create initial audit log entry
        $initial_audit = "INSERT INTO salary_audit_log (
            audit_type, 
            audit_date, 
            total_employees_checked, 
            issues_found, 
            audit_details, 
            created_by
        ) VALUES ('setup', NOW(), 0, 0, 'Initial system setup completed', ?)";
        
        $stmt = mysqli_prepare($conn, $initial_audit);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        // Commit transaction
        mysqli_commit($conn);
        
        $setup_status[] = "✓ Database tables created successfully";
        $setup_status[] = "✓ Validation rules configured";
        $setup_status[] = "✓ Initial audit log created";
        $setup_status[] = "✓ System ready for use";
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $setup_errors[] = "Error: " . $e->getMessage();
    }
}

// Check if tables already exist
$existing_tables = [];
$table_checks = [
    'salary_audit_log' => 'Salary Audit Log',
    'salary_corrections' => 'Salary Corrections',
    'salary_change_monitor' => 'Salary Change Monitor',
    'increment_validation_rules' => 'Increment Validation Rules'
];

foreach ($table_checks as $table => $description) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_query);
    if ($result && mysqli_num_rows($result) > 0) {
        $existing_tables[] = $description;
    }
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Salary Audit System Setup</h2>
                <p class="opacity-90">Configure the salary monitoring and audit system</p>
            </div>
            <div class="text-right">
                <a href="salary-audit-system.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-shield-alt mr-2"></i>Audit System
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Setup Status Messages -->
<?php if (!empty($setup_status)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <div>
                <strong>Setup Completed Successfully!</strong>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($setup_status as $status): ?>
                        <li><?php echo $status; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($setup_errors)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <div>
                <strong>Setup Errors:</strong>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($setup_errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- System Status -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Status</h3>
        
        <div class="space-y-3">
            <?php foreach ($table_checks as $table => $description): ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600"><?php echo $description; ?></span>
                    <?php if (in_array($description, $existing_tables)): ?>
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                            <i class="fas fa-check mr-1"></i>Installed
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                            <i class="fas fa-clock mr-1"></i>Not Installed
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Features</h3>
        
        <div class="space-y-3">
            <div class="flex items-center">
                <i class="fas fa-shield-alt text-green-600 mr-3"></i>
                <span class="text-sm text-gray-600">Real-time salary change monitoring</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-clock text-green-600 mr-3"></i>
                <span class="text-sm text-gray-600">3-year increment rule validation</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-dollar-sign text-green-600 mr-3"></i>
                <span class="text-sm text-gray-600">1000 maximum increment limit</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-history text-green-600 mr-3"></i>
                <span class="text-sm text-gray-600">Complete audit trail</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-bell text-green-600 mr-3"></i>
                <span class="text-sm text-gray-600">Automatic alerts and notifications</span>
            </div>
        </div>
    </div>
</div>

<!-- Setup Form -->
<?php if (count($existing_tables) < count($table_checks)): ?>
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Install System Components</h3>
        <p class="text-gray-600 mb-6">
            The salary audit system requires additional database tables to function properly. 
            Click the button below to install all necessary components.
        </p>
        
        <form method="POST">
            <input type="hidden" name="action" value="setup_audit_system">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Install Salary Audit System
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Ready</h3>
        <div class="flex items-center text-green-600 mb-4">
            <i class="fas fa-check-circle text-2xl mr-3"></i>
            <span class="text-lg">All components are installed and ready to use!</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="salary-audit-system.php" class="bg-green-600 text-white px-4 py-2 rounded-lg text-center hover:bg-green-700 transition-colors">
                <i class="fas fa-shield-alt mr-2"></i>Audit System
            </a>
            <a href="enhanced-salary-increment.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-center hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Create Increment
            </a>
            <a href="salary-monitor.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-center hover:bg-purple-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Run Monitor
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Configuration Guide -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration Guide</h3>
    
    <div class="space-y-4">
        <div class="border-l-4 border-blue-500 pl-4">
            <h4 class="font-medium text-gray-900">1. Automatic Monitoring</h4>
            <p class="text-sm text-gray-600 mt-1">
                Set up a cron job to run the salary monitor script every 6 hours:
            </p>
            <code class="block bg-gray-100 p-2 rounded mt-2 text-sm">
                0 */6 * * * php /path/to/salary-monitor.php
            </code>
        </div>
        
        <div class="border-l-4 border-green-500 pl-4">
            <h4 class="font-medium text-gray-900">2. Validation Rules</h4>
            <p class="text-sm text-gray-600 mt-1">
                The system enforces a 3-year service requirement and 1000 maximum increment amount.
                These rules can be customized in the validation rules table.
            </p>
        </div>
        
        <div class="border-l-4 border-yellow-500 pl-4">
            <h4 class="font-medium text-gray-900">3. Audit Reports</h4>
            <p class="text-sm text-gray-600 mt-1">
                Regular audits help identify unauthorized changes. Run comprehensive audits monthly
                and targeted audits when issues are suspected.
            </p>
        </div>
        
        <div class="border-l-4 border-red-500 pl-4">
            <h4 class="font-medium text-gray-900">4. Alert Configuration</h4>
            <p class="text-sm text-gray-600 mt-1">
                Configure email notifications or system alerts when unauthorized changes are detected.
                Check the logs regularly for suspicious activity.
            </p>
        </div>
    </div>
</div>


<script>
// Auto-hide success/error messages
setTimeout(function() {
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 10000);
</script>

<style>
/* Custom styles for setup page */
.setup-card {
    transition: all 0.3s ease;
}

.setup-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Animation for status indicators */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.status-indicator {
    animation: pulse 2s infinite;
}

/* Code block styling */
code {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}
</style>
