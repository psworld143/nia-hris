<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Install Performance Reviews Database';

$message = '';
$message_type = '';

// Handle migration from SEAIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    header('Location: migrate-performance-reviews.php');
    exit();
}

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    // Read the SQL file
    $sql_file = __DIR__ . '/database/performance_reviews_database.sql';
    
    if (!file_exists($sql_file)) {
        $message = 'SQL file not found: ' . $sql_file;
        $message_type = 'error';
    } else {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (trim($statement)) {
                if (mysqli_query($conn, $statement)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = mysqli_error($conn);
                }
            }
        }
        
        if ($error_count === 0) {
            $message = "Database installation completed successfully! {$success_count} statements executed.";
            $message_type = 'success';
        } else {
            $message = "Installation completed with {$error_count} errors. {$success_count} statements executed successfully.";
            $message_type = 'warning';
        }
    }
}

// Check if tables exist
$tables_to_check = [
    'performance_review_categories',
    'performance_review_criteria', 
    'performance_reviews',
    'performance_review_scores',
    'performance_review_goals',
    'performance_review_attachments'
];

$existing_tables = [];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $check_query = "SHOW TABLES LIKE '{$table}'";
    $result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($result) > 0) {
        $existing_tables[] = $table;
    } else {
        $missing_tables[] = $table;
    }
}

$is_installed = empty($missing_tables);

include 'includes/header.php';
?>

<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Install Performance Reviews Database</h1>
                            <p class="text-gray-600 mt-2">Set up the database structure for the performance reviews system</p>
                        </div>
                        <a href="performance-reviews.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                            <i class="fas fa-arrow-left"></i> Back to Reviews
                        </a>
                    </div>
                </div>

                <!-- Message Display -->
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : ($message_type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 'bg-red-100 text-red-800 border border-red-200'); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Installation Status -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Installation Status</h3>
                    
                    <?php if ($is_installed): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-green-900">Performance Reviews Database is Installed</h4>
                                <p class="text-green-700 text-sm">All required tables are present and ready to use.</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3"></i>
                            <div>
                                <h4 class="font-semibold text-yellow-900">Database Installation Required</h4>
                                <p class="text-yellow-700 text-sm">Some tables are missing. Please install the database structure.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Table Status -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Table Status</h3>
                    
                    <div class="space-y-3">
                        <?php foreach ($tables_to_check as $table): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="font-medium text-gray-900"><?php echo str_replace('_', ' ', ucwords($table)); ?></span>
                            <div class="flex items-center">
                                <?php if (in_array($table, $existing_tables)): ?>
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-green-600 text-sm font-medium">Exists</span>
                                <?php else: ?>
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span class="text-red-600 text-sm font-medium">Missing</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Database Schema Preview -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Database Schema</h3>
                    
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-2">Core Tables</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• <strong>performance_review_categories</strong> - Review categories and their weights</li>
                                <li>• <strong>performance_review_criteria</strong> - Specific criteria within each category</li>
                                <li>• <strong>performance_reviews</strong> - Main review records</li>
                                <li>• <strong>performance_review_scores</strong> - Individual criteria scores and comments</li>
                                <li>• <strong>performance_review_goals</strong> - Performance goals and their status</li>
                                <li>• <strong>performance_review_attachments</strong> - File attachments for reviews</li>
                            </ul>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-2">Sample Data Included</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• 6 performance categories (Job Performance, Communication, Teamwork, etc.)</li>
                                <li>• 15+ performance criteria with descriptions and weights</li>
                                <li>• Pre-configured rating scales and evaluation templates</li>
                                <li>• Database views for reporting and analytics</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Installation Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Installation Actions</h3>
                    
                    <?php if ($is_installed): ?>
                    <div class="space-y-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-green-600 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-green-900">Database Ready</h4>
                                    <p class="text-green-700 text-sm">The performance reviews database is already installed and ready to use.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <a href="performance-reviews.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                                <i class="fas fa-arrow-right"></i> Go to Performance Reviews
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-yellow-900">Installation Required</h4>
                                    <p class="text-yellow-700 text-sm">Click the button below to install the performance reviews database structure.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
                                <h4 class="font-semibold text-green-900 mb-2">✨ Recommended: Migrate from SEAIT</h4>
                                <p class="text-green-800 text-sm mb-3">
                                    Copy all performance review tables and data from the SEAIT website database. This includes:
                                </p>
                                <ul class="text-green-800 text-sm space-y-1 ml-4 mb-3">
                                    <li>✓ Complete table structures</li>
                                    <li>✓ All existing data and records</li>
                                    <li>✓ Categories, criteria, and goals</li>
                                    <li>✓ Review scores and attachments</li>
                                </ul>
                                <form method="POST" onsubmit="return confirm('This will copy all performance review tables from SEAIT website. Existing tables will be replaced. Continue?')">
                                    <input type="hidden" name="action" value="migrate">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-database"></i> Migrate from SEAIT Website
                                    </button>
                                </form>
                            </div>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Alternative: Fresh Installation</h4>
                                <p class="text-blue-800 text-sm mb-3">
                                    Install fresh performance review tables with sample data only.
                                </p>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to install the performance reviews database? This will create new tables in your database.')">
                                    <input type="hidden" name="action" value="install">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                                        <i class="fas fa-download"></i> Install Fresh Database
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- System Requirements -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Requirements</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Database Requirements</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• MySQL 5.7+ or MariaDB 10.2+</li>
                                <li>• InnoDB storage engine</li>
                                <li>• UTF-8 character set support</li>
                                <li>• Foreign key constraint support</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">PHP Requirements</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• PHP 7.4+ recommended</li>
                                <li>• MySQLi extension enabled</li>
                                <li>• File read permissions for SQL file</li>
                                <li>• Database write permissions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>

