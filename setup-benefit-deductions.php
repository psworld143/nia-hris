<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hr'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';
$table_exists = false;

// Check if table exists
$check_query = "SHOW TABLES LIKE 'employee_benefit_configurations'";
$check_result = mysqli_query($conn, $check_query);
$table_exists = mysqli_num_rows($check_result) > 0;

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'install') {
        // Drop existing table if reinstall
        if (isset($_POST['reinstall']) && $_POST['reinstall'] === 'yes') {
            $drop_query = "DROP TABLE IF EXISTS `employee_benefit_configurations`";
            mysqli_query($conn, $drop_query);
        }
        
        // Create table
        $create_query = "CREATE TABLE IF NOT EXISTS `employee_benefit_configurations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NOT NULL,
            
            -- SSS Configuration
            `sss_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
            `sss_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
            `sss_percentage` DECIMAL(5,2) DEFAULT 0.00,
            
            -- PhilHealth Configuration
            `philhealth_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
            `philhealth_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
            `philhealth_percentage` DECIMAL(5,2) DEFAULT 0.00,
            
            -- Pag-IBIG Configuration
            `pagibig_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
            `pagibig_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
            `pagibig_percentage` DECIMAL(5,2) DEFAULT 0.00,
            
            -- Tax Configuration
            `tax_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
            `tax_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
            `tax_percentage` DECIMAL(5,2) DEFAULT 0.00,
            
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_employee` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (mysqli_query($conn, $create_query)) {
            $message = "✅ Employee Benefit Configurations table installed successfully!";
            $table_exists = true;
        } else {
            $error = "❌ Error creating table: " . mysqli_error($conn);
        }
    }
}

// Get table info if exists
$table_info = [];
if ($table_exists) {
    $info_query = "SELECT 
                    COUNT(*) as total_configs,
                    SUM(CASE WHEN sss_deduction_type != 'auto' THEN 1 ELSE 0 END) as custom_sss,
                    SUM(CASE WHEN philhealth_deduction_type != 'auto' THEN 1 ELSE 0 END) as custom_philhealth,
                    SUM(CASE WHEN pagibig_deduction_type != 'auto' THEN 1 ELSE 0 END) as custom_pagibig,
                    SUM(CASE WHEN tax_deduction_type != 'auto' THEN 1 ELSE 0 END) as custom_tax
                   FROM employee_benefit_configurations";
    $info_result = mysqli_query($conn, $info_query);
    $table_info = mysqli_fetch_assoc($info_result);
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-database mr-3 text-blue-600"></i>Benefit Deductions Setup
        </h1>
        <p class="mt-2 text-gray-600">Install or verify the Employee Benefit Configurations table</p>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <div class="flex">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <p class="text-green-700"><?php echo $message; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                <p class="text-red-700"><?php echo $error; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Card -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600">
            <h2 class="text-xl font-semibold text-white">
                <i class="fas fa-info-circle mr-2"></i>Installation Status
            </h2>
        </div>
        <div class="p-6">
            <?php if ($table_exists): ?>
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-check text-2xl text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Table Installed</h3>
                        <p class="text-gray-600">The employee_benefit_configurations table is installed and ready.</p>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $table_info['total_configs']; ?></div>
                        <div class="text-sm text-gray-600">Configured Employees</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $table_info['custom_sss']; ?></div>
                        <div class="text-sm text-gray-600">Custom SSS</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-green-600"><?php echo $table_info['custom_philhealth']; ?></div>
                        <div class="text-sm text-gray-600">Custom PhilHealth</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $table_info['custom_pagibig']; ?></div>
                        <div class="text-sm text-gray-600">Custom Pag-IBIG</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-2xl font-bold text-red-600"><?php echo $table_info['custom_tax']; ?></div>
                        <div class="text-sm text-gray-600">Custom Tax</div>
                    </div>
                </div>

                <!-- Reinstall Option -->
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <h4 class="font-semibold text-yellow-900 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Reinstall Table
                    </h4>
                    <p class="text-sm text-yellow-700 mb-3">
                        Warning: This will delete all existing benefit configurations!
                    </p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will delete all benefit configurations!');">
                        <input type="hidden" name="action" value="install">
                        <input type="hidden" name="reinstall" value="yes">
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                            <i class="fas fa-redo mr-2"></i>Reinstall Table
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-exclamation text-2xl text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Table Not Found</h3>
                        <p class="text-gray-600">The employee_benefit_configurations table needs to be installed.</p>
                    </div>
                </div>

                <!-- Install Button -->
                <form method="POST" class="mt-6">
                    <input type="hidden" name="action" value="install">
                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        <i class="fas fa-download mr-2"></i>Install Table Now
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feature Information -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-purple-600">
            <h2 class="text-xl font-semibold text-white">
                <i class="fas fa-info-circle mr-2"></i>About This Feature
            </h2>
        </div>
        <div class="p-6">
            <h3 class="font-semibold text-gray-900 mb-3">What does this table do?</h3>
            <p class="text-gray-600 mb-4">
                The <code class="bg-gray-100 px-2 py-1 rounded">employee_benefit_configurations</code> table allows you to configure 
                how government benefit deductions are calculated for each employee in the payroll system.
            </p>

            <h3 class="font-semibold text-gray-900 mb-3">Deduction Types:</h3>
            <ul class="space-y-2 mb-4">
                <li class="flex items-start">
                    <span class="inline-block px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs mr-2 mt-0.5">Auto</span>
                    <span class="text-gray-600">Uses standard government contribution tables (recommended)</span>
                </li>
                <li class="flex items-start">
                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs mr-2 mt-0.5">Fixed</span>
                    <span class="text-gray-600">Deducts a specific fixed amount</span>
                </li>
                <li class="flex items-start">
                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs mr-2 mt-0.5">%</span>
                    <span class="text-gray-600">Deducts a percentage of gross pay</span>
                </li>
                <li class="flex items-start">
                    <span class="inline-block px-2 py-1 bg-red-100 text-red-700 rounded text-xs mr-2 mt-0.5">None</span>
                    <span class="text-gray-600">No deduction applied</span>
                </li>
            </ul>

            <h3 class="font-semibold text-gray-900 mb-3">Configurable Benefits:</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="text-center p-3 bg-blue-50 rounded">
                    <i class="fas fa-shield-alt text-2xl text-blue-600 mb-2"></i>
                    <div class="text-sm font-medium">SSS</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded">
                    <i class="fas fa-heartbeat text-2xl text-green-600 mb-2"></i>
                    <div class="text-sm font-medium">PhilHealth</div>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded">
                    <i class="fas fa-home text-2xl text-yellow-600 mb-2"></i>
                    <div class="text-sm font-medium">Pag-IBIG</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded">
                    <i class="fas fa-receipt text-2xl text-red-600 mb-2"></i>
                    <div class="text-sm font-medium">Tax</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="mt-8 flex justify-between">
        <a href="government-benefits.php" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Government Benefits
        </a>
        
        <?php if ($table_exists): ?>
            <a href="QUICK_START_BENEFIT_CONFIG.md" target="_blank" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-book mr-2"></i>View Documentation
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

