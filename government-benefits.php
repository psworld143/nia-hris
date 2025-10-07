<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Set page title
$page_title = 'Government Benefits Management';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_benefits':
                $employee_id = intval($_POST['employee_id']);
                $person_type = $_POST['person_type'];
                $sss_number = $_POST['sss_number'];
                $hdmf_number = $_POST['hdmf_number'];
                $bir_tin = $_POST['bir_tin'];
                $phic_number = $_POST['phic_number'];
                
                // Update ID numbers in employees table
                $query = "UPDATE employees SET 
                         sss_number = ?, 
                         pagibig_number = ?, 
                         tin_number = ?, 
                         philhealth_number = ?
                         WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssi", $sss_number, $hdmf_number, $bir_tin, $phic_number, $employee_id);
                mysqli_stmt_execute($stmt);
                
                // Save benefit configuration
                $sss_type = $_POST['sss_type'];
                $sss_fixed = floatval($_POST['sss_fixed'] ?? 0);
                $sss_percentage = floatval($_POST['sss_percentage'] ?? 0);
                
                $philhealth_type = $_POST['philhealth_type'];
                $philhealth_fixed = floatval($_POST['philhealth_fixed'] ?? 0);
                $philhealth_percentage = floatval($_POST['philhealth_percentage'] ?? 0);
                
                $pagibig_type = $_POST['pagibig_type'];
                $pagibig_fixed = floatval($_POST['pagibig_fixed'] ?? 0);
                $pagibig_percentage = floatval($_POST['pagibig_percentage'] ?? 0);
                
                $tax_type = $_POST['tax_type'];
                $tax_fixed = floatval($_POST['tax_fixed'] ?? 0);
                $tax_percentage = floatval($_POST['tax_percentage'] ?? 0);
                
                $config_query = "INSERT INTO employee_benefit_configurations (
                                    employee_id,
                                    sss_deduction_type, sss_fixed_amount, sss_percentage,
                                    philhealth_deduction_type, philhealth_fixed_amount, philhealth_percentage,
                                    pagibig_deduction_type, pagibig_fixed_amount, pagibig_percentage,
                                    tax_deduction_type, tax_fixed_amount, tax_percentage
                                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                    sss_deduction_type = VALUES(sss_deduction_type),
                                    sss_fixed_amount = VALUES(sss_fixed_amount),
                                    sss_percentage = VALUES(sss_percentage),
                                    philhealth_deduction_type = VALUES(philhealth_deduction_type),
                                    philhealth_fixed_amount = VALUES(philhealth_fixed_amount),
                                    philhealth_percentage = VALUES(philhealth_percentage),
                                    pagibig_deduction_type = VALUES(pagibig_deduction_type),
                                    pagibig_fixed_amount = VALUES(pagibig_fixed_amount),
                                    pagibig_percentage = VALUES(pagibig_percentage),
                                    tax_deduction_type = VALUES(tax_deduction_type),
                                    tax_fixed_amount = VALUES(tax_fixed_amount),
                                    tax_percentage = VALUES(tax_percentage)";
                
                $config_stmt = mysqli_prepare($conn, $config_query);
                mysqli_stmt_bind_param($config_stmt, "isddsddsddsdd", 
                    $employee_id,
                    $sss_type, $sss_fixed, $sss_percentage,
                    $philhealth_type, $philhealth_fixed, $philhealth_percentage,
                    $pagibig_type, $pagibig_fixed, $pagibig_percentage,
                    $tax_type, $tax_fixed, $tax_percentage
                );
                
                if (mysqli_stmt_execute($config_stmt)) {
                    $success_message = "Government benefits and deduction configuration updated successfully!";
                } else {
                    $error_message = "Error updating configuration: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get employees with their government benefits and deduction configurations
$employees_query = "SELECT 
                    e.id, 
                    e.employee_id, 
                    e.first_name, 
                    e.last_name, 
                    e.position, 
                    e.department,
                    e.sss_number,
                    e.pagibig_number as hdmf_number,
                    e.tin_number as bir_tin,
                    e.philhealth_number as phic_number,
                    e.is_active,
                    'employee' as type,
                    ebc.sss_deduction_type,
                    ebc.sss_fixed_amount,
                    ebc.sss_percentage,
                    ebc.philhealth_deduction_type,
                    ebc.philhealth_fixed_amount,
                    ebc.philhealth_percentage,
                    ebc.pagibig_deduction_type,
                    ebc.pagibig_fixed_amount,
                    ebc.pagibig_percentage,
                    ebc.tax_deduction_type,
                    ebc.tax_fixed_amount,
                    ebc.tax_percentage
                    FROM employees e 
                    LEFT JOIN employee_benefit_configurations ebc ON e.id = ebc.employee_id
                    WHERE e.is_active = 1 
                    ORDER BY e.last_name, e.first_name";

$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
if ($employees_result) {
    while ($row = mysqli_fetch_assoc($employees_result)) {
        $employees[] = $row;
    }
}

// Faculty has been removed - all personnel are now employees
// Keeping this for backward compatibility but using empty array
$faculty = [];

// Combine all personnel for total count
$all_personnel = array_merge($employees, $faculty);

// Helper function to safely escape strings
function safe_htmlspecialchars($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-id-card mr-2"></i>Government Benefits - ID Numbers
                </h2>
                <p class="opacity-90">Manage employee government benefit ID numbers (SSS, PhilHealth, Pag-IBIG, TIN)</p>
                <p class="text-sm opacity-75 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    These ID numbers are used in payroll to automatically apply government deductions
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="manage-benefit-rates.php" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-percent mr-2"></i>Contribution Rates
                </a>
                <button onclick="exportBenefits()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Information Notice -->
<div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
        </div>
        <div class="ml-4">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">About Government Benefits & Deductions</h3>
            <div class="text-blue-800 space-y-2 text-sm">
                <p>
                    <strong>This page manages:</strong> Employee government benefit ID numbers (SSS #, PhilHealth #, Pag-IBIG #, TIN)
                </p>
                <p>
                    <strong>Contribution rates are managed at:</strong> 
                    <a href="manage-benefit-rates.php" class="underline font-semibold hover:text-blue-600">
                        Manage Contribution Rates →
                    </a>
                </p>
                <p>
                    <strong>How it works in payroll:</strong>
                </p>
                <ul class="list-disc ml-6 space-y-1">
                    <li>When processing payroll, if an employee has an SSS Number → SSS deduction is auto-checked</li>
                    <li>The deduction amount is automatically calculated based on salary and contribution rate table</li>
                    <li>Same applies for PhilHealth, Pag-IBIG, and Withholding Tax</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Personnel</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($all_personnel); ?></p>
                <p class="text-xs text-gray-400"><?php echo count($employees); ?> Employees</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">SSS Coverage</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($all_personnel, function($person) { return !empty($person['sss_number']); })); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-home"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">HDMF Coverage</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($all_personnel, function($person) { return !empty($person['hdmf_number']); })); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-heartbeat"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">PhilHealth Coverage</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($all_personnel, function($person) { return !empty($person['phic_number']); })); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success_message)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success_message; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Government Benefits Table with Tabs -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Government Benefits Management</h3>
        <div class="flex space-x-2">
            <button onclick="refreshTable()" class="px-4 py-2 text-sm bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                <i class="fas fa-sync-alt mr-1"></i>Refresh
            </button>
            <button onclick="exportBenefits()" class="px-4 py-2 text-sm bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                <i class="fas fa-download mr-1"></i>Export
            </button>
        </div>
    </div>

    <!-- Section Header -->
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">
            <i class="fas fa-users mr-2"></i>Employees (<?php echo count($employees); ?>)
        </h2>
    </div>

    <!-- Employees Tab Content -->
    <div id="employees-content" class="tab-content">
        <?php if (empty($employees)): ?>
            <div class="text-center py-12">
                <i class="fas fa-users text-4xl mb-4 text-gray-400"></i>
                <p class="text-gray-500">No employees found.</p>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-green-600 to-green-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">SSS Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">HDMF Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">BIR TIN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">PhilHealth</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($employees as $employee): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white font-semibold">
                                                <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo safe_htmlspecialchars($employee['first_name']) . ' ' . safe_htmlspecialchars($employee['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                ID: <?php echo safe_htmlspecialchars($employee['employee_id']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo safe_htmlspecialchars($employee['position']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['sss_number']) ? htmlspecialchars($employee['sss_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                        <?php
                                        $sss_type = $employee['sss_deduction_type'] ?? 'auto';
                                        $badge_color = ['auto'=>'gray', 'fixed'=>'blue', 'percentage'=>'purple', 'none'=>'red'][$sss_type];
                                        $badge_text = ['auto'=>'Auto', 'fixed'=>'Fixed', 'percentage'=>'%', 'none'=>'None'][$sss_type];
                                        ?>
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-<?php echo $badge_color; ?>-100 text-<?php echo $badge_color; ?>-700 font-medium"><?php echo $badge_text; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['hdmf_number']) ? htmlspecialchars($employee['hdmf_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                        <?php
                                        $pagibig_type = $employee['pagibig_deduction_type'] ?? 'auto';
                                        $badge_color = ['auto'=>'gray', 'fixed'=>'blue', 'percentage'=>'purple', 'none'=>'red'][$pagibig_type];
                                        $badge_text = ['auto'=>'Auto', 'fixed'=>'Fixed', 'percentage'=>'%', 'none'=>'None'][$pagibig_type];
                                        ?>
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-<?php echo $badge_color; ?>-100 text-<?php echo $badge_color; ?>-700 font-medium"><?php echo $badge_text; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['bir_tin']) ? htmlspecialchars($employee['bir_tin']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                        <?php
                                        $tax_type = $employee['tax_deduction_type'] ?? 'auto';
                                        $badge_color = ['auto'=>'gray', 'fixed'=>'blue', 'percentage'=>'purple', 'none'=>'red'][$tax_type];
                                        $badge_text = ['auto'=>'Auto', 'fixed'=>'Fixed', 'percentage'=>'%', 'none'=>'None'][$tax_type];
                                        ?>
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-<?php echo $badge_color; ?>-100 text-<?php echo $badge_color; ?>-700 font-medium"><?php echo $badge_text; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['phic_number']) ? htmlspecialchars($employee['phic_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                        <?php
                                        $philhealth_type = $employee['philhealth_deduction_type'] ?? 'auto';
                                        $badge_color = ['auto'=>'gray', 'fixed'=>'blue', 'percentage'=>'purple', 'none'=>'red'][$philhealth_type];
                                        $badge_text = ['auto'=>'Auto', 'fixed'=>'Fixed', 'percentage'=>'%', 'none'=>'None'][$philhealth_type];
                                        ?>
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-<?php echo $badge_color; ?>-100 text-<?php echo $badge_color; ?>-700 font-medium"><?php echo $badge_text; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editBenefits(<?php echo htmlspecialchars(json_encode($employee)); ?>)" 
                                            class="text-green-600 hover:text-green-900 transition-colors" title="Edit Benefits">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="lg:hidden space-y-4">
                <?php foreach ($employees as $employee): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white font-semibold mr-3">
                                    <?php echo strtoupper(substr($employee['first_name'] ?? '', 0, 1) . substr($employee['last_name'] ?? '', 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">
                                        <?php echo safe_htmlspecialchars($employee['first_name']) . ' ' . safe_htmlspecialchars($employee['last_name']); ?>
                                    </h3>
                                    <p class="text-xs text-gray-500">ID: <?php echo safe_htmlspecialchars($employee['employee_id']); ?></p>
                                </div>
                            </div>
                            <button onclick="editBenefits(<?php echo htmlspecialchars(json_encode($employee)); ?>)" 
                                    class="text-green-600 hover:text-green-900 transition-colors p-2" title="Edit Benefits">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="font-medium text-gray-500">Position:</span>
                                <p class="text-gray-900"><?php echo safe_htmlspecialchars($employee['position']); ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500">SSS Number:</span>
                                <p class="text-gray-900"><?php echo !empty($employee['sss_number']) ? htmlspecialchars($employee['sss_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500">HDMF Number:</span>
                                <p class="text-gray-900"><?php echo !empty($employee['hdmf_number']) ? htmlspecialchars($employee['hdmf_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500">BIR TIN:</span>
                                <p class="text-gray-900"><?php echo !empty($employee['bir_tin']) ? htmlspecialchars($employee['bir_tin']) : '<span class="text-gray-400 italic">Not set</span>'; ?></p>
                            </div>
                            <div>
                                <span class="font-medium text-gray-500">PhilHealth:</span>
                                <p class="text-gray-900"><?php echo !empty($employee['phic_number']) ? htmlspecialchars($employee['phic_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Benefits Modal -->
<div id="benefitsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Government Benefits</h3>
                <button onclick="closeBenefitsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="benefitsForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_benefits">
                <input type="hidden" name="employee_id" id="employee_id" value="">
                <input type="hidden" name="person_type" id="person_type" value="">
                
                <div id="employeeInfo" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <!-- Employee info will be populated here -->
                </div>
                
                <!-- Government ID Numbers -->
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Government ID Numbers</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SSS Number</label>
                            <input type="text" name="sss_number" id="sss_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="xx-xxxxxxx-x">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Number</label>
                            <input type="text" name="phic_number" id="phic_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="xxxx-xxxx-xxxx">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">HDMF (Pag-IBIG) Number</label>
                            <input type="text" name="hdmf_number" id="hdmf_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="xxxx-xxxx-xxxx">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">BIR TIN</label>
                            <input type="text" name="bir_tin" id="bir_tin"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="xxx-xxx-xxx-xxx">
                        </div>
                    </div>
                </div>
                
                <!-- Deduction Configuration -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-900 mb-3">
                        <i class="fas fa-calculator mr-2"></i>Payroll Deduction Configuration
                    </h4>
                    <p class="text-xs text-blue-700 mb-4">Configure how government deductions are calculated in payroll</p>
                    
                    <!-- SSS Deduction Config -->
                    <div class="mb-4 bg-white p-3 rounded border border-blue-200">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">SSS Deduction</label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="sss_type" value="auto" checked class="mr-2">
                                Auto (from table)
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="sss_type" value="fixed" class="mr-2">
                                Fixed Amount
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="sss_type" value="percentage" class="mr-2">
                                Percentage
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="sss_fixed" id="sss_fixed" placeholder="Fixed amount" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                            <input type="number" name="sss_percentage" id="sss_percentage" placeholder="Percentage %" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                        </div>
                    </div>
                    
                    <!-- PhilHealth Deduction Config -->
                    <div class="mb-4 bg-white p-3 rounded border border-blue-200">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">PhilHealth Deduction</label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="philhealth_type" value="auto" checked class="mr-2">
                                Auto
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="philhealth_type" value="fixed" class="mr-2">
                                Fixed
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="philhealth_type" value="percentage" class="mr-2">
                                Percentage
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="philhealth_fixed" id="philhealth_fixed" placeholder="Fixed amount" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                            <input type="number" name="philhealth_percentage" id="philhealth_percentage" placeholder="Percentage %" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                        </div>
                    </div>
                    
                    <!-- Pag-IBIG Deduction Config -->
                    <div class="mb-4 bg-white p-3 rounded border border-blue-200">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Pag-IBIG Deduction</label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="pagibig_type" value="auto" checked class="mr-2">
                                Auto
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="pagibig_type" value="fixed" class="mr-2">
                                Fixed
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="pagibig_type" value="percentage" class="mr-2">
                                Percentage
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="pagibig_fixed" id="pagibig_fixed" placeholder="Fixed amount" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                            <input type="number" name="pagibig_percentage" id="pagibig_percentage" placeholder="Percentage %" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                        </div>
                    </div>
                    
                    <!-- Tax Deduction Config -->
                    <div class="mb-4 bg-white p-3 rounded border border-blue-200">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Withholding Tax</label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <label class="flex items-center text-sm">
                                <input type="radio" name="tax_type" value="auto" checked class="mr-2">
                                Auto
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="tax_type" value="fixed" class="mr-2">
                                Fixed
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="radio" name="tax_type" value="percentage" class="mr-2">
                                Percentage
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="tax_fixed" id="tax_fixed" placeholder="Fixed amount" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                            <input type="number" name="tax_percentage" id="tax_percentage" placeholder="Percentage %" step="0.01" 
                                   class="px-3 py-2 border rounded text-sm">
                        </div>
                    </div>
                    
                    <div class="text-xs text-blue-700">
                        <strong>Note:</strong> "Auto" uses rates from the contribution rate table based on employee salary. 
                        "Fixed" and "Percentage" override automatic calculation.
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeBenefitsModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Update Benefits
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// Modal functions
function editBenefits(person) {
    document.getElementById('employee_id').value = person.id;
    document.getElementById('person_type').value = person.type;
    document.getElementById('sss_number').value = person.sss_number || '';
    document.getElementById('hdmf_number').value = person.hdmf_number || '';
    document.getElementById('bir_tin').value = person.bir_tin || '';
    document.getElementById('phic_number').value = person.phic_number || '';
    
    // Populate benefit configuration
    // SSS
    const sssType = person.sss_deduction_type || 'auto';
    document.querySelector(`input[name="sss_type"][value="${sssType}"]`).checked = true;
    document.getElementById('sss_fixed').value = person.sss_fixed_amount || '';
    document.getElementById('sss_percentage').value = person.sss_percentage || '';
    
    // PhilHealth
    const philhealthType = person.philhealth_deduction_type || 'auto';
    document.querySelector(`input[name="philhealth_type"][value="${philhealthType}"]`).checked = true;
    document.getElementById('philhealth_fixed').value = person.philhealth_fixed_amount || '';
    document.getElementById('philhealth_percentage').value = person.philhealth_percentage || '';
    
    // Pag-IBIG
    const pagibigType = person.pagibig_deduction_type || 'auto';
    document.querySelector(`input[name="pagibig_type"][value="${pagibigType}"]`).checked = true;
    document.getElementById('pagibig_fixed').value = person.pagibig_fixed_amount || '';
    document.getElementById('pagibig_percentage').value = person.pagibig_percentage || '';
    
    // Tax
    const taxType = person.tax_deduction_type || 'auto';
    document.querySelector(`input[name="tax_type"][value="${taxType}"]`).checked = true;
    document.getElementById('tax_fixed').value = person.tax_fixed_amount || '';
    document.getElementById('tax_percentage').value = person.tax_percentage || '';
    
    // Determine colors based on type
    const bgColor = person.type === 'faculty' ? 'from-green-500 to-green-600' : 'from-green-500 to-green-600';
    const personType = person.type === 'faculty' ? 'Faculty' : 'Employee';
    
    // Populate person info
    document.getElementById('employeeInfo').innerHTML = `
        <div class="flex items-center">
            <div class="h-12 w-12 rounded-full bg-gradient-to-r ${bgColor} flex items-center justify-center text-white font-semibold mr-4">
                ${person.first_name.charAt(0).toUpperCase()}${person.last_name.charAt(0).toUpperCase()}
            </div>
            <div>
                <h4 class="text-lg font-medium text-gray-900">${person.first_name} ${person.last_name}</h4>
                <p class="text-sm text-gray-500">${person.position}</p>
                <p class="text-xs text-gray-400">ID: ${person.employee_id} (${personType})</p>
            </div>
        </div>
    `;
    
    document.getElementById('benefitsModal').classList.remove('hidden');
}

function closeBenefitsModal() {
    document.getElementById('benefitsModal').classList.add('hidden');
    document.getElementById('benefitsForm').reset();
}

function refreshTable() {
    window.location.reload();
}

function exportBenefits() {
    // Create CSV content
    let csvContent = "Employee ID,Name,Position,SSS Number,HDMF Number,BIR TIN,PhilHealth\n";
    
    <?php foreach ($employees as $employee): ?>
    csvContent += `<?php echo $employee['employee_id'] ?? 'N/A'; ?>,<?php echo ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''); ?>,<?php echo $employee['position'] ?? 'N/A'; ?>,<?php echo $employee['sss_number'] ?: 'N/A'; ?>,<?php echo $employee['hdmf_number'] ?: 'N/A'; ?>,<?php echo $employee['bir_tin'] ?: 'N/A'; ?>,<?php echo $employee['phic_number'] ?: 'N/A'; ?>\n`;
    <?php endforeach; ?>
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'government-benefits-<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Auto-hide success/error messages
setTimeout(function() {
    const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 5000);
</script>

<style>
/* Custom styles for government benefits */
.benefits-card {
    transition: all 0.3s ease;
}

.benefits-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Animation for modal */
.modal-enter {
    animation: modalEnter 0.3s ease-out;
}

@keyframes modalEnter {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Fix overlapping tables and ensure proper spacing */
.tab-content {
    position: relative;
    z-index: 1;
    width: 100%;
    clear: both;
}

.tab-content.hidden {
    display: none !important;
    visibility: hidden;
    opacity: 0;
    height: 0;
    overflow: hidden;
}

.tab-content:not(.hidden) {
    display: block !important;
    visibility: visible;
    opacity: 1;
    height: auto;
    overflow: visible;
}

/* Responsive table improvements */
@media (max-width: 1024px) {
    .tab-content {
        padding: 0;
        margin-top: 1rem;
    }
    
    /* Ensure mobile cards have proper spacing */
    .lg\:hidden .space-y-4 > * + * {
        margin-top: 1rem;
    }
    
    /* Improve mobile card readability */
    .lg\:hidden .text-xs {
        font-size: 0.75rem;
        line-height: 1.25rem;
    }
    
    /* Better button sizing on mobile */
    .lg\:hidden button {
        min-height: 2.5rem;
        min-width: 2.5rem;
    }
}

/* Tablet optimizations */
@media (min-width: 768px) and (max-width: 1023px) {
    .sm\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Ensure table doesn't cause horizontal scroll on large screens */
@media (min-width: 1024px) {
    .overflow-x-auto {
        overflow-x: visible;
    }
    
    .min-w-full {
        min-width: auto;
    }
    
    /* Ensure proper table spacing */
    .hidden.lg\:block {
        margin-top: 0;
        padding-top: 0;
    }
}

/* Fix any potential overlapping issues */
#employees-content {
    clear: both;
    position: relative;
    width: 100%;
    min-height: 200px;
}

/* Ensure tables don't overlap */
table {
    width: 100%;
    table-layout: auto;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0;
    padding: 0;
}

/* Fix table container responsiveness */
.hidden.lg\:block {
    width: 100%;
    overflow-x: auto;
    overflow-y: visible;
}

/* Ensure mobile cards are properly spaced */
.lg\:hidden.space-y-4 {
    margin-top: 0;
    padding-top: 0;
}

/* Fix any remaining overlap issues */
.overflow-x-auto {
    position: relative;
    z-index: 1;
}

/* Ensure proper table width on all screen sizes */
@media (max-width: 1023px) {
    .hidden.lg\:block {
        display: none !important;
    }
    
    .lg\:hidden {
        display: block !important;
    }
}

@media (min-width: 1024px) {
    .hidden.lg\:block {
        display: block !important;
    }
    
    .lg\:hidden {
        display: none !important;
    }
}
</style>
