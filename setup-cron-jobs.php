<?php
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
$page_title = 'Cron Jobs Setup';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Cron Jobs Setup</h2>
                <p class="opacity-90">Configure automated tasks for salary increment system</p>
            </div>
            <div class="text-right">
                <a href="auto-increment-management.php" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-cogs mr-2"></i>Auto Increment
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Cron Jobs Configuration -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Monthly Auto Increment -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-xl mr-4">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Monthly Auto Increment</h3>
                <p class="text-sm text-gray-600">Process automatic salary increments</p>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="font-medium text-gray-900 mb-2">Cron Job Command:</h4>
            <code class="block bg-gray-800 text-green-400 p-3 rounded text-sm font-mono">
                0 0 1 * * php <?php echo realpath(__DIR__ . '/auto-increment-processor.php'); ?>
            </code>
        </div>
        
        <div class="space-y-2 text-sm text-gray-600">
            <div><strong>Schedule:</strong> 1st day of every month at midnight</div>
            <div><strong>Purpose:</strong> Apply 1000 increment to eligible employees</div>
            <div><strong>Eligibility:</strong> 3+ years of service, 3+ years since last increment</div>
        </div>
        
        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <span class="text-sm text-blue-800">This job processes all eligible employees automatically</span>
            </div>
        </div>
    </div>
    
    <!-- Hourly Monitoring -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center text-white text-xl mr-4">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Hourly Monitoring</h3>
                <p class="text-sm text-gray-600">Monitor for unauthorized changes</p>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="font-medium text-gray-900 mb-2">Cron Job Command:</h4>
            <code class="block bg-gray-800 text-green-400 p-3 rounded text-sm font-mono">
                0 */6 * * * php <?php echo realpath(__DIR__ . '/salary-monitor.php'); ?>
            </code>
        </div>
        
        <div class="space-y-2 text-sm text-gray-600">
            <div><strong>Schedule:</strong> Every 6 hours</div>
            <div><strong>Purpose:</strong> Detect unauthorized salary changes</div>
            <div><strong>Alerts:</strong> Logs issues to system error log</div>
        </div>
        
        <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                <span class="text-sm text-yellow-800">Monitors compliance with 3-year rule and 1000 limit</span>
            </div>
        </div>
    </div>
</div>

<!-- Setup Instructions -->
<div class="bg-white rounded-xl shadow-lg p-6 mt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Setup Instructions</h3>
    
    <div class="space-y-6">
        <!-- Step 1 -->
        <div class="border-l-4 border-blue-500 pl-6">
            <h4 class="font-medium text-gray-900 mb-2">Step 1: Access Server Crontab</h4>
            <p class="text-sm text-gray-600 mb-3">Log into your server and edit the crontab file:</p>
            <code class="block bg-gray-800 text-green-400 p-3 rounded text-sm font-mono">
                crontab -e
            </code>
        </div>
        
        <!-- Step 2 -->
        <div class="border-l-4 border-green-500 pl-6">
            <h4 class="font-medium text-gray-900 mb-2">Step 2: Add Cron Jobs</h4>
            <p class="text-sm text-gray-600 mb-3">Add these lines to your crontab file:</p>
            <div class="bg-gray-800 text-green-400 p-3 rounded text-sm font-mono space-y-1">
                <div># Monthly automatic salary increments</div>
                <div>0 0 1 * * php <?php echo realpath(__DIR__ . '/auto-increment-processor.php'); ?></div>
                <div></div>
                <div># Hourly salary monitoring</div>
                <div>0 */6 * * * php <?php echo realpath(__DIR__ . '/salary-monitor.php'); ?></div>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="border-l-4 border-purple-500 pl-6">
            <h4 class="font-medium text-gray-900 mb-2">Step 3: Verify Setup</h4>
            <p class="text-sm text-gray-600 mb-3">Check that cron jobs are installed:</p>
            <code class="block bg-gray-800 text-green-400 p-3 rounded text-sm font-mono">
                crontab -l
            </code>
        </div>
        
        <!-- Step 4 -->
        <div class="border-l-4 border-red-500 pl-6">
            <h4 class="font-medium text-gray-900 mb-2">Step 4: Test Execution</h4>
            <p class="text-sm text-gray-600 mb-3">Test the scripts manually first:</p>
            <div class="bg-gray-800 text-green-400 p-3 rounded text-sm font-mono space-y-1">
                <div>php <?php echo realpath(__DIR__ . '/auto-increment-processor.php'); ?></div>
                <div>php <?php echo realpath(__DIR__ . '/salary-monitor.php'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Alternative Setup (Windows) -->
<div class="bg-white rounded-xl shadow-lg p-6 mt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Alternative Setup (Windows Task Scheduler)</h3>
    
    <div class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-900 mb-2">For Windows Servers:</h4>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                <li>Open Task Scheduler</li>
                <li>Create Basic Task</li>
                <li>Set trigger to "Monthly" for auto-increment</li>
                <li>Set action to start program: <code>php.exe</code></li>
                <li>Add arguments: <code><?php echo realpath(__DIR__ . '/auto-increment-processor.php'); ?></code></li>
                <li>Repeat for monitoring script with "Daily" trigger</li>
            </ol>
        </div>
    </div>
</div>

<!-- Current Status -->
<div class="bg-white rounded-xl shadow-lg p-6 mt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Current System Status</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <h4 class="font-medium text-gray-900">System Files</h4>
            <div class="space-y-2">
                <?php
                $files = [
                    'auto-increment-processor.php' => 'Automatic Increment Processor',
                    'salary-monitor.php' => 'Salary Monitor',
                    'auto-increment-management.php' => 'Management Interface'
                ];
                
                foreach ($files as $file => $description) {
                    $exists = file_exists(__DIR__ . '/' . $file);
                    echo '<div class="flex items-center">';
                    if ($exists) {
                        echo '<i class="fas fa-check-circle text-green-600 mr-2"></i>';
                        echo '<span class="text-sm text-gray-900">' . $description . '</span>';
                    } else {
                        echo '<i class="fas fa-times-circle text-red-600 mr-2"></i>';
                        echo '<span class="text-sm text-gray-900">' . $description . ' (Missing)</span>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Database Tables</h4>
            <div class="space-y-2">
                <?php
                $tables = [
                    'salary_audit_log' => 'Audit Logs',
                    'salary_change_monitor' => 'Change Monitor',
                    'increment_validation_rules' => 'Validation Rules'
                ];
                
                foreach ($tables as $table => $description) {
                    $check_query = "SHOW TABLES LIKE '$table'";
                    $result = mysqli_query($conn, $check_query);
                    $exists = $result && mysqli_num_rows($result) > 0;
                    
                    echo '<div class="flex items-center">';
                    if ($exists) {
                        echo '<i class="fas fa-check-circle text-green-600 mr-2"></i>';
                        echo '<span class="text-sm text-gray-900">' . $description . '</span>';
                    } else {
                        echo '<i class="fas fa-times-circle text-red-600 mr-2"></i>';
                        echo '<span class="text-sm text-gray-900">' . $description . ' (Missing)</span>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Manual Testing -->
<div class="bg-white rounded-xl shadow-lg p-6 mt-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Manual Testing</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Test Auto Increment</h4>
            <p class="text-sm text-gray-600">Run the automatic increment processor to test:</p>
            <a href="auto-increment-management.php" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-play mr-2"></i>Test Auto Increment
            </a>
        </div>
        
        <div class="space-y-4">
            <h4 class="font-medium text-gray-900">Test Monitoring</h4>
            <p class="text-sm text-gray-600">Run the salary monitoring system:</p>
            <a href="salary-audit-system.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-shield-alt mr-2"></i>Test Monitoring
            </a>
        </div>
    </div>
</div>


<style>
/* Custom styles for cron setup page */
code {
    font-family: 'Courier New', monospace;
    word-break: break-all;
}

.cron-card {
    transition: all 0.3s ease;
}

.cron-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
</style>
