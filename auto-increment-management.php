<?php
session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Automatic Increment Management';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'run_auto_increment':
                // Run the automatic increment processor
                $output = [];
                $return_var = 0;
                exec('/Applications/XAMPP/xamppfiles/bin/php ' . __DIR__ . '/auto-increment-processor.php 2>&1', $output, $return_var);
                
                if ($return_var === 0) {
                    // Extract just the summary from the output
                    $summary_line = '';
                    foreach ($output as $line) {
                        if (strpos($line, 'Total eligible employees:') !== false || 
                            strpos($line, 'Pending increments created:') !== false ||
                            strpos($line, 'No employees were eligible') !== false) {
                            $summary_line = $line;
                            break;
                        }
                    }
                    $success_message = "Auto increment process completed successfully!" . 
                                     ($summary_line ? "<br><span class='text-sm text-gray-600'>" . htmlspecialchars($summary_line) . "</span>" : "");
                } else {
                    $error_message = "Error running automatic increment processor:<br><pre>" . implode("\n", $output) . "</pre>";
                }
                break;
                
            case 'run_auto_increment_faculty':
                // Run the automatic increment processor for faculty
                $output = [];
                $return_var = 0;
                exec('/Applications/XAMPP/xamppfiles/bin/php ' . __DIR__ . '/auto-increment-processor.php 2>&1', $output, $return_var);
                
                if ($return_var === 0) {
                    // Extract just the summary from the output
                    $summary_line = '';
                    foreach ($output as $line) {
                        if (strpos($line, 'Total eligible employees:') !== false || 
                            strpos($line, 'Pending increments created:') !== false ||
                            strpos($line, 'No employees were eligible') !== false) {
                            $summary_line = $line;
                            break;
                        }
                    }
                    $success_message = "Faculty auto increment process completed successfully!" . 
                                     ($summary_line ? "<br><span class='text-sm text-gray-600'>" . htmlspecialchars($summary_line) . "</span>" : "");
                } else {
                    $error_message = "Error running faculty auto increment processor:<br><pre>" . implode("\n", $output) . "</pre>";
                }
                break;
                
            case 'preview_eligible':
                // This will be handled in the display section
                break;
        }
    }
}

// Get eligible employees for preview based on salary structure rules
$eligible_employees = [];
// All staff are employees now

// Query for regular employees
$employee_query = "SELECT 
    e.id as employee_id,
    e.employee_id as emp_id,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    e.employee_type,
    e.hire_date,
    ed.basic_salary,
    DATEDIFF(NOW(), e.hire_date) / 365.25 as years_service,
    COALESCE(last_inc.last_increment_date, e.hire_date) as reference_date,
    DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, e.hire_date)) / 365.25 as years_since_last_increment,
    last_inc.last_increment_date,
    pending_inc.id as pending_increment_id,
    pending_inc.increment_amount as pending_increment_amount,
    pending_inc.effective_date as pending_effective_date,
    pending_inc.status as pending_status,
    ss.id as salary_structure_id,
    ss.increment_percentage,
    ss.incrementation_amount,
    ss.incrementation_frequency_years,
    ss.minimum_salary,
    ss.maximum_salary,
    'employee' as person_type
FROM employees e 
LEFT JOIN employee_details ed ON e.id = ed.employee_id 
LEFT JOIN salary_structures ss ON e.position COLLATE utf8mb4_unicode_ci = ss.position_title COLLATE utf8mb4_unicode_ci AND ss.is_active = 1
LEFT JOIN (
    SELECT employee_id, MAX(effective_date) as last_increment_date
    FROM salary_increments 
    WHERE status IN ('approved', 'implemented')
    GROUP BY employee_id
) last_inc ON e.id = last_inc.employee_id
LEFT JOIN (
    SELECT employee_id, id, increment_amount, effective_date, status
    FROM salary_increments 
    WHERE status = 'pending'
    ORDER BY effective_date DESC
) pending_inc ON e.id = pending_inc.employee_id
WHERE e.is_active = 1 
AND e.hire_date IS NOT NULL
ORDER BY 
    CASE 
        WHEN ss.id IS NOT NULL AND DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, e.hire_date)) >= (ss.incrementation_frequency_years * 365.25) THEN 0 
        ELSE 1 
    END,
    e.hire_date";

// All staff are employees - no separate faculty query needed

// Process employee data
$employee_result = mysqli_query($conn, $employee_query);
while ($row = mysqli_fetch_assoc($employee_result)) {
    // Check eligibility based on salary structure rules
    $frequency_years = $row['incrementation_frequency_years'] ?? 3;
    $has_salary_structure = !empty($row['salary_structure_id']);
    $has_pending_increment = !empty($row['pending_increment_id']);
    
    $row['is_eligible'] = $has_salary_structure && 
                         $row['years_service'] >= $frequency_years && 
                         $row['years_since_last_increment'] >= $frequency_years &&
                         !$has_pending_increment; // Not eligible if already has pending increment
    
    // Calculate expected increment amount
    $row['expected_increment'] = 0;
    if ($has_salary_structure) {
        if (!empty($row['incrementation_amount']) && $row['incrementation_amount'] > 0) {
            $row['expected_increment'] = $row['incrementation_amount'];
        } elseif (!empty($row['increment_percentage']) && $row['increment_percentage'] > 0) {
            $row['expected_increment'] = (($row['basic_salary'] ?? 0) * $row['increment_percentage']) / 100;
        } else {
            $row['expected_increment'] = 1000; // Default fallback
        }
    }
    
    $eligible_employees[] = $row;
}

// All employees processed above - no separate faculty processing needed

// Get recent automatic increments (both employees and faculty)
$recent_increments_query = "SELECT 
    si.*,
    COALESCE(e.first_name, e.first_name) as first_name,
    COALESCE(e.last_name, e.last_name) as last_name,
    COALESCE(e.employee_id, e.employee_id) as emp_id,
    COALESCE(e.position, f.position) as position,
    CASE 
        WHEN e.id IS NOT NULL THEN 'employee'
        WHEN f.id IS NOT NULL THEN 'faculty'
    END as person_type
FROM salary_increments si
LEFT JOIN employees e ON si.employee_id = e.id
LEFT JOIN employees f ON si.employee_id = f.id
WHERE si.incrementation_name LIKE '%Automatic%' OR si.incrementation_name LIKE '%Increment%' OR si.incrementation_name = ''
ORDER BY si.created_at DESC
LIMIT 20";

$recent_increments_result = mysqli_query($conn, $recent_increments_query);
$recent_increments = [];
while ($row = mysqli_fetch_assoc($recent_increments_result)) {
    $recent_increments[] = $row;
}

// Get statistics
$stats = [];

// Count eligible employees
$eligible_employee_count = 0;
foreach ($eligible_employees as $emp) {
    if ($emp['is_eligible']) $eligible_employee_count++;
}

// Count eligible faculty
$stats['eligible_count'] = $eligible_employee_count;
$stats['eligible_employee_count'] = $eligible_employee_count;

// Count total automatic increments this year
$auto_increments_query = "SELECT COUNT(*) as count FROM salary_increments 
                         WHERE incrementation_name = 'Automatic 3-Year Increment' 
                         AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$auto_result = mysqli_query($conn, $auto_increments_query);
$stats['auto_increments_this_year'] = mysqli_fetch_assoc($auto_result)['count'];

// Total amount distributed this year
$amount_query = "SELECT SUM(increment_amount) as total FROM salary_increments 
                WHERE incrementation_name = 'Automatic 3-Year Increment' 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$amount_result = mysqli_query($conn, $amount_query);
$stats['total_amount_this_year'] = mysqli_fetch_assoc($amount_result)['total'] ?? 0;

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-4 sm:mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1 min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2 truncate">Automatic Increment Management</h2>
                <p class="opacity-90 text-sm sm:text-base">Manage automatic salary increments based on salary structure rules</p>
            </div>
            <div class="flex-shrink-0">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="run_auto_increment">
                    <button type="submit" class="w-full sm:w-auto bg-white text-green-600 px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:bg-green-50 transition-colors text-sm sm:text-base">
                        <i class="fas fa-play mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline">Run Auto Increment</span>
                        <span class="sm:hidden">Run Auto</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="mb-4 sm:mb-6 bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-3 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
            <div class="text-sm sm:text-base"><?php echo $success_message; ?></div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="mb-4 sm:mb-6 bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-3 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-circle mr-2 mt-0.5 flex-shrink-0"></i>
            <div class="text-sm sm:text-base"><?php echo $error_message; ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-lg sm:text-xl lg:text-2xl">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Eligible Now</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $stats['eligible_count']; ?></p>
                <p class="text-xs text-green-600 mt-1 truncate">Ready for increment</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-lg sm:text-xl lg:text-2xl">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Auto Increments This Year</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900"><?php echo $stats['auto_increments_this_year']; ?></p>
                <p class="text-xs text-green-600 mt-1 truncate">Processed automatically</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6  sm:col-span-2 lg:col-span-1">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-center text-white text-lg sm:text-xl lg:text-2xl">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
            <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Total Amount This Year</p>
                <p class="text-xl sm:text-2xl font-bold text-gray-900">₱<?php echo number_format($stats['total_amount_this_year'], 0); ?></p>
                <p class="text-xs text-green-600 mt-1 truncate">Auto increments</p>
            </div>
        </div>
    </div>
</div>

<!-- Eligibility Status Tabs -->
<div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6 lg:mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6 gap-2">
        <h3 class="text-base sm:text-lg font-medium text-gray-900">Eligibility Status</h3>
        <div class="text-xs sm:text-sm text-gray-500">
            Last updated: <span class="hidden sm:inline"><?php echo date('Y-m-d H:i:s'); ?></span>
            <span class="sm:hidden"><?php echo date('M d, H:i'); ?></span>
        </div>
    </div>

    <!-- Employee Auto-Increment Management -->
    <div class="border-b border-gray-200 mb-4 sm:mb-6 pb-2">
        <h2 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-users text-green-600 mr-2"></i>Eligible Employees for Increment
            <span class="ml-3 bg-green-100 text-green-800 text-sm font-medium px-3 py-1 rounded-full">
                <?php echo count($eligible_employees); ?> Employees
            </span>
        </h2>
    </div>

    <!-- Tab Content -->
    <div id="tab-content-employees" class="tab-content">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                    <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                    <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Hire Date</th>
                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Years</th>
                    <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Last Inc.</th>
                    <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Salary</th>
                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($eligible_employees)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>No employees found.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eligible_employees as $employee): ?>
                            <?php include 'employee-row-template.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
                                    </div>
                                        </div>

    <!-- Faculty section removed - employee-only system -->
    <div id="tab-content-faculty" class="tab-content" style="display:none;">
        <!-- Faculty Auto Increment Button -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div class="flex-1 min-w-0">
                <h4 class="text-base sm:text-lg font-semibold text-gray-900">Faculty Salary Increments</h4>
                <p class="text-xs sm:text-sm text-gray-600">Apply automatic salary increments to eligible faculty members</p>
                                    </div>
            <div class="flex-shrink-0">
                <form method="POST" class="inline" id="faculty-auto-increment-form">
                    <input type="hidden" name="action" value="run_auto_increment_faculty">
                    <button type="submit" class="w-full sm:w-auto bg-white text-green-600 px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:bg-green-50 transition-colors border border-green-200 text-sm sm:text-base">
                        <i class="fas fa-play mr-1 sm:mr-2"></i>
                        <span class="hidden sm:inline">Run Auto Increment</span>
                        <span class="sm:hidden">Run Auto</span>
                    </button>
                </form>
                                </div>
                                    </div>
        
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gradient-to-r from-green-600 to-green-700">
                    <tr>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Faculty</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Hire Date</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Years</th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Last Inc.</th>
                        <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Salary</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($eligible_faculty)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-chalkboard-teacher text-4xl mb-4"></i>
                                <p>No employees eound.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eligible_faculty as $faculty): ?>
                            <?php include 'faculty-row-template.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Recent Automatic Increments -->
<div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6">
    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-4 sm:mb-6">Recent Automatic Increments</h3>
    
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gradient-to-r from-green-100 to-green-200">
                <tr>
                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Person</th>
                    <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Position</th>
                    <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Previous</th>
                    <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">New</th>
                    <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Effective</th>
                    <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recent_increments)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-4"></i>
                            <p>No automatic increments processed yet.</p>
                            <p class="text-sm mt-2">Run the automatic increment processor to begin.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_increments as $increment): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo $increment['first_name'] . ' ' . $increment['last_name']; ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: <?php echo $increment['emp_id']; ?>
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full <?php echo $increment['person_type'] === 'faculty' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ucfirst($increment['person_type']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $increment['position']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₱<?php echo number_format($increment['current_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                ₱<?php echo number_format($increment['new_salary'], 2); ?>
                                <div class="text-xs text-gray-500">+₱<?php echo number_format($increment['increment_amount'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($increment['effective_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    <?php echo ucfirst($increment['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Password Confirmation Modal -->
<div id="passwordConfirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-4 sm:top-20 mx-auto p-3 sm:p-5 border w-11/12 sm:w-96 shadow-lg rounded-md bg-white modal-content">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4 modal-header">
                <h3 id="confirmationTitle" class="text-base sm:text-lg font-medium text-gray-900">Confirm Salary Increase</h3>
                <button onclick="closePasswordModal()" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>
            
            <div id="confirmationInfo" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-user text-green-600 mr-2"></i>
                    <div class="min-w-0 flex-1">
                        <h4 id="confirmationEmployeeName" class="text-sm font-medium text-green-900 truncate"></h4>
                        <p id="confirmationDetails" class="text-xs text-green-700 truncate"></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Enter your password to confirm
                </label>
                <input type="password" id="confirmationPassword" 
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="Enter your password">
                <div id="passwordError" class="text-red-500 text-xs mt-1 hidden"></div>
            </div>
            
            <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 modal-footer">
                <button onclick="closePasswordModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button onclick="processConfirmation()" id="confirmActionButton"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <i class="fas fa-check mr-1"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Salary History Modal -->
<div id="salaryHistoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-4 sm:top-20 mx-auto p-3 sm:p-5 border w-11/12 sm:w-10/12 md:w-3/4 lg:w-2/3 xl:w-1/2 shadow-lg rounded-md bg-white modal-content">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4 modal-header">
                <h3 id="modalTitle" class="text-base sm:text-lg font-medium text-gray-900">Salary Increase History</h3>
                <button onclick="closeSalaryHistoryModal()" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>
            
            <div id="employeeInfo" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-user text-green-600 mr-2"></i>
                    <div class="min-w-0 flex-1">
                        <h4 id="employeeName" class="text-sm font-medium text-green-900 truncate"></h4>
                        <p id="employeeDetails" class="text-xs text-green-700 truncate"></p>
                    </div>
                </div>
            </div>
            
            <div id="historyLoading" class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
                <p class="text-gray-500">Loading salary history...</p>
            </div>
            
            <div id="historyContent" class="hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-green-100 to-green-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Previous Salary</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Increase</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">New Salary</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Reason</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- History data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <div id="noHistoryMessage" class="hidden text-center py-8">
                    <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No salary increase history found for this employee.</p>
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button onclick="closeSalaryHistoryModal()" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Tab functionality
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-green-500', 'text-green-600');
        button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
    });
    
    // Show selected tab content
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
    activeButton.classList.add('border-green-500', 'text-green-600');
}

// Initialize tabs on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set employees tab as active by default
    switchTab('employees');
});

// Debug: Monitor Years of Service column visibility
let debugInterval = setInterval(function() {
    const yearsServiceCells = document.querySelectorAll('td:nth-child(4)');
    const visibleCount = Array.from(yearsServiceCells).filter(cell => 
        cell.offsetWidth > 0 && cell.offsetHeight > 0 && cell.style.display !== 'none'
    ).length;
    
    if (yearsServiceCells.length > 0 && visibleCount < yearsServiceCells.length) {
        console.warn('Years of Service column visibility issue detected!', {
            total: yearsServiceCells.length,
            visible: visibleCount,
            time: new Date().toLocaleTimeString()
        });
    }
}, 1000);

// Stop debugging after 30 seconds
setTimeout(() => clearInterval(debugInterval), 30000);

// Auto-refresh page every 5 minutes to update eligibility status
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 minutes

// Auto-hide success/error messages (but not table content)
setTimeout(function() {
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    messages.forEach(message => {
        // Only hide if it's not part of a table row
        if (!message.closest('table')) {
        message.style.display = 'none';
        }
    });
}, 10000);

// Prevent any accidental hiding of table content
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auto-increment management page loaded');
    const table = document.querySelector('table');
    if (table) {
        console.log('Table found with', table.querySelectorAll('tr').length, 'rows');
    }
});

// Password Confirmation Modal Functions
let currentAction = null;
let currentIncrementId = null;

function confirmIncrement(incrementId, employeeName, incrementAmount) {
    currentAction = 'confirm';
    currentIncrementId = incrementId;
    
    document.getElementById('confirmationTitle').textContent = 'Confirm Salary Increase';
    document.getElementById('confirmationEmployeeName').textContent = employeeName;
    document.getElementById('confirmationDetails').textContent = `Increase Amount: ₱${incrementAmount.toLocaleString()}`;
    document.getElementById('confirmActionButton').innerHTML = '<i class="fas fa-check mr-1"></i>Confirm';
    document.getElementById('confirmActionButton').className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500';
    
    document.getElementById('passwordConfirmationModal').classList.remove('hidden');
    document.getElementById('confirmationPassword').focus();
}

function rejectIncrement(incrementId, employeeName) {
    currentAction = 'reject';
    currentIncrementId = incrementId;
    
    document.getElementById('confirmationTitle').textContent = 'Reject Salary Increase';
    document.getElementById('confirmationEmployeeName').textContent = employeeName;
    document.getElementById('confirmationDetails').textContent = 'This will permanently reject the pending increment';
    document.getElementById('confirmActionButton').innerHTML = '<i class="fas fa-times mr-1"></i>Reject';
    document.getElementById('confirmActionButton').className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500';
    
    document.getElementById('passwordConfirmationModal').classList.remove('hidden');
    document.getElementById('confirmationPassword').focus();
}

function closePasswordModal() {
    document.getElementById('passwordConfirmationModal').classList.add('hidden');
    document.getElementById('confirmationPassword').value = '';
    document.getElementById('passwordError').classList.add('hidden');
    currentAction = null;
    currentIncrementId = null;
}

function processConfirmation() {
    const password = document.getElementById('confirmationPassword').value;
    const errorDiv = document.getElementById('passwordError');
    
    if (!password) {
        errorDiv.textContent = 'Password is required';
        errorDiv.classList.remove('hidden');
        return;
    }
    
    // Show loading state
    const button = document.getElementById('confirmActionButton');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...';
    button.disabled = true;
    
    // Send AJAX request
    fetch('confirm-increment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            increment_id: currentIncrementId,
            action: currentAction,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePasswordModal();
            showToast(data.message, 'success');
            // Reload page to update the display
            setTimeout(() => window.location.reload(), 1000);
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Close modal when clicking outside
document.getElementById('passwordConfirmationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordModal();
    }
});

// Salary History Modal Functions
function viewSalaryHistory(employeeId, employeeName) {
    // Show modal
    document.getElementById('salaryHistoryModal').classList.remove('hidden');
    
    // Set employee name
    document.getElementById('employeeName').textContent = employeeName;
    
    // Show loading state
    document.getElementById('historyLoading').classList.remove('hidden');
    document.getElementById('historyContent').classList.add('hidden');
    
    // Fetch salary history
    fetch(`get-salary-history.php?employee_id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('historyLoading').classList.add('hidden');
            document.getElementById('historyContent').classList.remove('hidden');
            
            if (data.success && data.history.length > 0) {
                displaySalaryHistory(data.history, data.employee_info);
            } else {
                document.getElementById('noHistoryMessage').classList.remove('hidden');
                document.getElementById('historyTableBody').innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error fetching salary history:', error);
            document.getElementById('historyLoading').classList.add('hidden');
            document.getElementById('historyContent').classList.remove('hidden');
            document.getElementById('noHistoryMessage').classList.remove('hidden');
            document.getElementById('historyTableBody').innerHTML = '';
        });
}

function displaySalaryHistory(history, employeeInfo) {
    // Set employee details
    if (employeeInfo) {
        document.getElementById('employeeDetails').textContent = 
            `${employeeInfo.position} • ${employeeInfo.department} • Current: ₱${employeeInfo.current_salary.toLocaleString()}`;
    }
    
    // Populate history table
    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '';
    
    history.forEach(increment => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        
        const statusColor = increment.status === 'approved' || increment.status === 'implemented' 
            ? 'bg-green-100 text-green-800' 
            : increment.status === 'pending' 
            ? 'bg-yellow-100 text-yellow-800' 
            : 'bg-red-100 text-red-800';
        
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900">
                ${new Date(increment.effective_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                })}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">
                ${increment.incrementation_name || 'Regular Increment'}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">
                ₱${parseFloat(increment.current_salary).toLocaleString()}
            </td>
            <td class="px-4 py-3 text-sm font-medium text-green-600">
                +₱${parseFloat(increment.increment_amount).toLocaleString()}
                ${increment.increment_percentage ? ` (${increment.increment_percentage}%)` : ''}
            </td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                ₱${parseFloat(increment.new_salary).toLocaleString()}
            </td>
            <td class="px-4 py-3 text-sm">
                <span class="px-2 py-1 text-xs font-medium ${statusColor} rounded-full">
                    ${increment.status.charAt(0).toUpperCase() + increment.status.slice(1)}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">
                ${increment.reason || 'N/A'}
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Hide no history message if we have history
    document.getElementById('noHistoryMessage').classList.add('hidden');
}

function closeSalaryHistoryModal() {
    document.getElementById('salaryHistoryModal').classList.add('hidden');
    // Reset modal state
    document.getElementById('historyLoading').classList.remove('hidden');
    document.getElementById('historyContent').classList.add('hidden');
    document.getElementById('noHistoryMessage').classList.add('hidden');
    document.getElementById('historyTableBody').innerHTML = '';
}

// Close modal when clicking outside
document.getElementById('salaryHistoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSalaryHistoryModal();
    }
});

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-green-600 text-white'
    }`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>

<style>
/* Custom styles for auto increment management */
.eligible-row {
    background-color: #f0fdf4;
}

.increment-preview {
    font-size: 0.75rem;
    color: #059669;
}

/* Animation for eligible rows */
@keyframes highlight {
    0% { background-color: #f0fdf4; }
    50% { background-color: #dcfce7; }
    100% { background-color: #f0fdf4; }
}

.bg-green-50 {
    /* Removed infinite animation that might cause display issues */
    background-color: #f0fdf4 !important;
}

/* Green theme enhancements */
.pending-increment {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #22c55e;
    border-radius: 8px;
}

.confirm-button {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
    transition: all 0.2s ease;
}

.confirm-button:hover {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.status-pending {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    color: #15803d;
    border: 1px solid #22c55e;
}

.status-eligible {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #15803d;
}

/* Mobile Responsive Styles */
@media (max-width: 640px) {
    /* Ensure tables are fully responsive */
    .responsive-table {
        font-size: 0.75rem;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 0.5rem 0.75rem;
    }
    
    /* Make buttons more touch-friendly on mobile */
    .confirm-button,
    .reject-button {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
    
    /* Stack action buttons vertically on very small screens */
    @media (max-width: 480px) {
        .action-buttons {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .action-buttons button {
            width: 100%;
            justify-content: center;
        }
    }
}

/* Extra small screens */
@media (max-width: 375px) {
    .responsive-table {
        font-size: 0.7rem;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 0.375rem 0.5rem;
    }
    
    /* Make text more compact */
    .text-xs {
        font-size: 0.65rem;
    }
}

/* Tablet styles */
@media (min-width: 641px) and (max-width: 1024px) {
    .responsive-table {
        font-size: 0.875rem;
    }
}

/* Large screen optimizations */
@media (min-width: 1025px) {
    .responsive-table {
        font-size: 0.875rem;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 0.75rem 1.5rem;
    }
}

/* Tab button responsive styles */
.tab-button {
    transition: all 0.2s ease;
}

.tab-button.active {
    border-color: #22c55e;
    color: #15803d;
}

/* Modal responsive styles */
@media (max-width: 640px) {
    .modal-content {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .modal-footer button {
        width: 100%;
    }
}

/* Toast notification responsive */
@media (max-width: 640px) {
    .toast {
        left: 1rem;
        right: 1rem;
        transform: translateX(0);
    }
}

/* Statistics cards responsive adjustments */
@media (max-width: 480px) {
    .stats-grid {
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 2.5rem;
        height: 2.5rem;
    }
}

/* Ensure horizontal scroll works properly */
.overflow-x-auto {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Custom xs breakpoint for very small screens */
@media (min-width: 475px) {
    .xs\:inline {
        display: inline;
    }
}

@media (max-width: 474px) {
    .xs\:hidden {
        display: none;
    }
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .responsive-table {
        font-size: 10pt;
    }
    
    .bg-gradient-to-r {
        background: #e5e7eb !important;
        color: #374151 !important;
    }
}

/* Table header enhancements */
.table-header-green {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.1);
}

.table-header-light-green {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    border-bottom: 2px solid #22c55e;
}

.table-header-light-green th {
    color: #15803d;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(34, 197, 94, 0.1);
}

/* Tab styles */
.tab-button {
    transition: all 0.2s ease-in-out;
    border-bottom: 2px solid transparent;
}

.tab-button:hover {
    background-color: rgba(34, 197, 94, 0.05);
    border-radius: 4px 4px 0 0;
}

.tab-button.border-green-500 {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 4px 4px 0 0;
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.1);
}

.tab-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Faculty avatar styling */
.faculty-avatar {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

/* Employee avatar styling */
.employee-avatar {
    background: linear-gradient(135deg, #6b7280 0%, #374151 100%);
}

/* Responsive table styling */
.responsive-table {
    width: 100%;
    table-layout: auto;
}

/* Mobile-first responsive design */
@media (max-width: 640px) {
    .responsive-table th,
    .responsive-table td {
        padding: 8px 6px;
        font-size: 12px;
    }
    
    .responsive-table th {
        font-size: 10px;
        padding: 6px 6px;
    }
    
    /* Hide less important columns on mobile */
    .responsive-table th:nth-child(3),
    .responsive-table td:nth-child(3),
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        display: none;
    }
    
    /* Make name column wider on mobile */
    .responsive-table th:first-child,
    .responsive-table td:first-child {
        min-width: 120px;
    }
    
    /* Adjust status column for mobile */
    .responsive-table th:last-child,
    .responsive-table td:last-child {
        min-width: 100px;
    }
    
    /* Ensure tables go edge-to-edge on mobile */
    .overflow-x-auto {
        margin-left: -16px;
        margin-right: -16px;
        width: calc(100% + 32px);
    }
}

@media (max-width: 480px) {
    /* Further hide columns on very small screens */
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2),
    .responsive-table th:nth-child(4),
    .responsive-table td:nth-child(4),
    .responsive-table th:nth-child(6),
    .responsive-table td:nth-child(6) {
        display: none;
    }
    
    /* Make remaining columns wider */
    .responsive-table th:first-child,
    .responsive-table td:first-child {
        min-width: 150px;
    }
    
    .responsive-table th:last-child,
    .responsive-table td:last-child {
        min-width: 120px;
    }
}

@media (min-width: 768px) {
    /* Show all columns on tablet and desktop */
    .responsive-table th,
    .responsive-table td {
        display: table-cell;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 16px;
    }
    
    /* Ensure tables go edge-to-edge on desktop */
    .overflow-x-auto {
        margin-left: -24px;
        margin-right: -24px;
        width: calc(100% + 48px);
    }
}

@media (min-width: 1024px) {
    /* Full padding on desktop */
    .responsive-table th,
    .responsive-table td {
        padding: 16px 24px;
    }
}

/* Ensure table container fits device width */
.overflow-x-auto {
    width: 100%;
    max-width: 100vw;
}

/* Remove padding from main container to fit full width */
.bg-white.rounded-xl.shadow-lg.p-6 {
    padding: 0;
    margin: 0;
    border-radius: 0;
    box-shadow: none;
}

/* Add padding back to header and tabs only */
.bg-white.rounded-xl.shadow-lg.p-6 > h3,
.bg-white.rounded-xl.shadow-lg.p-6 > .flex,
.bg-white.rounded-xl.shadow-lg.p-6 > .border-b {
    padding-left: 24px;
    padding-right: 24px;
    padding-top: 24px;
}

/* Tab content should have no padding */
.tab-content {
    padding: 0;
}

/* Mobile-specific adjustments for the main container */
@media (max-width: 640px) {
    .bg-white.rounded-xl.shadow-lg.p-6 > h3,
    .bg-white.rounded-xl.shadow-lg.p-6 > .flex,
    .bg-white.rounded-xl.shadow-lg.p-6 > .border-b {
        padding-left: 16px;
        padding-right: 16px;
        padding-top: 16px;
    }
    
    /* Adjust tab navigation for mobile */
    .tab-button {
        padding: 8px 4px;
        font-size: 12px;
    }
    
    .tab-button span {
        display: none;
    }
}

/* Tablet adjustments */
@media (min-width: 641px) and (max-width: 1023px) {
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        display: none;
    }
}

/* Ensure proper spacing on all devices */
.responsive-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
}

.responsive-table tbody tr:last-child {
    border-bottom: none;
}
</style>
