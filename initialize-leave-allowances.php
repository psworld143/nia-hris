<?php
session_start();
require_once 'config/database.php';
require_once 'includes/leave_allowance_calculator.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: /seait/index.php?login=required&redirect=initialize-leave-allowances');
    exit();
}

$page_title = 'Initialize Leave Allowances';

// Initialize calculator
$calculator = new LeaveAllowanceCalculator($conn);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = $_POST['year'] ?? date('Y');
    
    try {
        $result = $calculator->initializeYearlyLeaveBalances($year);
        $message = "Successfully initialized leave balances for {$year}. Processed: {$result['total_processed']}, Success: {$result['success_count']}, Errors: {$result['error_count']}";
    } catch (Exception $e) {
        $error = "Error initializing leave balances: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Initialize Leave Allowances</h1>
            <p class="text-gray-600">Set up leave allowances for all employees and faculty</p>
        </div>
        <div class="flex space-x-3">
            <a href="leave-allowance-management.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Back to Management
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Leave Allowance Rules Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">
            <i class="fas fa-info-circle mr-2"></i>Leave Allowance Rules
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <h4 class="font-semibold text-blue-800 mb-2">Admin Employees:</h4>
                <ul class="space-y-1 text-blue-700">
                    <li>• 5 days leave with pay regardless of employment status</li>
                    <li>• Entitled for regularization after 6 months</li>
                    <li>• Regular employees can accumulate unused leave</li>
                    <li>• Non-regular employees: leave resets every year</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold text-blue-800 mb-2">Faculty:</h4>
                <ul class="space-y-1 text-blue-700">
                    <li>• 5 days leave with pay regardless of employment status</li>
                    <li>• Entitled for regularization after 3 years</li>
                    <li>• Can only enjoy accumulated leave after 3 years (regularization)</li>
                    <li>• Non-regular faculty: leave resets every year</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Current Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php
        $current_year = date('Y');
        $stats = $calculator->getLeaveBalanceSummary($current_year);
        
        // Get employee counts
        $employee_count_query = "SELECT COUNT(*) as count FROM employees WHERE is_active = 1";
        $employee_count_result = mysqli_query($conn, $employee_count_query);
        $employee_count = mysqli_fetch_assoc($employee_count_result)['count'];
        
        $faculty_count_query = "SELECT COUNT(*) as count FROM employees WHERE is_active = 1";
        $faculty_count_result = mysqli_query($conn, $faculty_count_query);
        $faculty_count = mysqli_fetch_assoc($faculty_count_result)['count'];
        ?>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $employee_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-chalkboard-teacher text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Faculty</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $faculty_count; ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Initialized <?php echo $current_year; ?></p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo ($stats['employee']['total_employees'] ?? 0) + ($stats['faculty']['total_employees'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Initialization</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo ($employee_count + $faculty_count) - (($stats['employee']['total_employees'] ?? 0) + ($stats['faculty']['total_employees'] ?? 0)); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize Form -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Initialize Leave Allowances</h3>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Year to Initialize</label>
                    <select name="year" id="year" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-seait-orange focus:border-transparent">
                        <?php for ($year = $current_year; $year <= $current_year + 2; $year++): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>This will calculate and initialize leave balances for all active employees and faculty</li>
                                    <li>The system will automatically determine regularization status and accumulation eligibility</li>
                                    <li>Existing leave balances for the selected year will be recalculated</li>
                                    <li>This process may take a few minutes depending on the number of employees</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2">
                        <i class="fas fa-play"></i>
                        Initialize Leave Allowances
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

