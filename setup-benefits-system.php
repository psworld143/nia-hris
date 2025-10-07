<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Read and execute SQL file
        $sql_file = file_get_contents('database/benefit_types_schema.sql');
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql_file)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                if (!mysqli_query($conn, $statement)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
        }
        
        $message = "✅ Benefits system installed successfully!";
        header('Location: manage-benefits.php?installed=1');
        exit;
        
    } catch (Exception $e) {
        $error = "❌ Installation error: " . $e->getMessage();
    }
}

$page_title = 'Setup Benefits System';
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-gift text-blue-600 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Setup Benefits Management System</h1>
            <p class="text-gray-600">Install the comprehensive benefits management database</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3"></i>
                    <p class="text-red-700"><?php echo $error; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="font-semibold text-blue-900 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>What will be installed:
                </h3>
                <ul class="space-y-2 text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mt-1 mr-2"></i>
                        <span><strong>benefit_types</strong> - Define all benefit types (mandatory, optional, loans)</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mt-1 mr-2"></i>
                        <span><strong>benefit_rate_tables</strong> - Salary-based rate tables for complex benefits</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-blue-600 mt-1 mr-2"></i>
                        <span><strong>Default benefits</strong> - SSS, PhilHealth, Pag-IBIG, Tax pre-configured</span>
                    </li>
                </ul>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="font-semibold text-green-900 mb-3">
                    <i class="fas fa-star mr-2"></i>Features:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-green-800">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Add custom benefit types</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Fixed amount or percentage rates</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Salary-based contribution tables</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Employer share configuration</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Category management</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span>Activate/deactivate benefits</span>
                    </div>
                </div>
            </div>

            <form method="POST" class="text-center">
                <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 transition font-semibold text-lg shadow-lg">
                    <i class="fas fa-download mr-2"></i>Install Benefits System Now
                </button>
            </form>

            <div class="text-center text-sm text-gray-500">
                <i class="fas fa-shield-alt mr-1"></i>
                Safe to run - won't affect existing data
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

