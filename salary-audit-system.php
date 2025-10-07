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
$page_title = 'Salary Audit System';

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
            case 'create_salary_audit':
                // Create a comprehensive salary audit
                $audit_query = "INSERT INTO salary_audit_log (
                    audit_type, 
                    audit_date, 
                    total_employees_checked, 
                    issues_found, 
                    audit_details, 
                    created_by
                ) VALUES ('comprehensive', NOW(), 0, 0, '', ?)";
                
                $stmt = mysqli_prepare($conn, $audit_query);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $audit_id = mysqli_insert_id($conn);
                    
                    // Perform comprehensive audit
                    $audit_results = performSalaryAudit($conn, $audit_id, $user_id);
                    
                    $success_message = "Salary audit completed! Found " . $audit_results['issues'] . " issues out of " . $audit_results['total_employees'] . " employees.";
                } else {
                    $error_message = "Error creating salary audit: " . mysqli_error($conn);
                }
                break;
                
            case 'fix_unauthorized_changes':
                $employee_id = intval($_POST['employee_id']);
                $corrected_salary = floatval($_POST['corrected_salary']);
                $reason = trim($_POST['reason']);
                
                // Log the correction
                $correction_query = "INSERT INTO salary_corrections (
                    employee_id, 
                    old_salary, 
                    new_salary, 
                    correction_reason, 
                    corrected_by, 
                    correction_date
                ) VALUES (?, (SELECT basic_salary FROM employee_details WHERE employee_id = ?), ?, ?, ?, NOW())";
                
                $stmt = mysqli_prepare($conn, $correction_query);
                mysqli_stmt_bind_param($stmt, "iidsi", $employee_id, $employee_id, $corrected_salary, $reason, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update the salary
                    $update_query = "UPDATE employee_details SET basic_salary = ?, updated_by = ?, updated_at = NOW() WHERE employee_id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "dii", $corrected_salary, $user_id, $employee_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_message = "Salary corrected successfully for employee ID: " . $employee_id;
                    } else {
                        $error_message = "Error updating salary: " . mysqli_error($conn);
                    }
                } else {
                    $error_message = "Error logging correction: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Function to perform comprehensive salary audit
function performSalaryAudit($conn, $audit_id, $user_id) {
    $issues_found = 0;
    $total_employees = 0;
    $audit_details = [];
    
    // Get all employees with salary data
    $query = "SELECT ed.employee_id, ed.basic_salary, ed.step_increment, ed.updated_at, 
                     e.first_name, e.last_name, e.position, e.hire_date
              FROM employee_details ed 
              JOIN employees e ON ed.employee_id = e.id 
              WHERE ed.basic_salary > 0
              ORDER BY ed.employee_id";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $total_employees++;
            $employee_issues = [];
            
            // Check for unauthorized increments (no corresponding salary_increments record)
            $increment_query = "SELECT COUNT(*) as count FROM salary_increments WHERE employee_id = ? AND status = 'approved'";
            $increment_stmt = mysqli_prepare($conn, $increment_query);
            mysqli_stmt_bind_param($increment_stmt, "i", $row['employee_id']);
            mysqli_stmt_execute($increment_stmt);
            $increment_result = mysqli_stmt_get_result($increment_stmt);
            $increment_count = mysqli_fetch_assoc($increment_result)['count'];
            
            if ($increment_count == 0 && $row['basic_salary'] > 0) {
                $employee_issues[] = "Salary set without approved increment record";
                $issues_found++;
            }
            
            // Check for recent unauthorized changes (within last 30 days without proper approval)
            $recent_change_query = "SELECT COUNT(*) as count FROM salary_increments 
                                   WHERE employee_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $recent_stmt = mysqli_prepare($conn, $recent_change_query);
            mysqli_stmt_bind_param($recent_stmt, "i", $row['employee_id']);
            mysqli_stmt_execute($recent_stmt);
            $recent_result = mysqli_stmt_get_result($recent_stmt);
            $recent_count = mysqli_fetch_assoc($recent_result)['count'];
            
            if ($recent_count == 0 && strtotime($row['updated_at']) > strtotime('-30 days')) {
                $employee_issues[] = "Recent salary change without increment record";
                $issues_found++;
            }
            
            // Check for increments before 3 years of service
            if ($row['hire_date']) {
                $hire_date = new DateTime($row['hire_date']);
                $current_date = new DateTime();
                $years_of_service = $current_date->diff($hire_date)->y;
                
                if ($years_of_service < 3 && $increment_count > 0) {
                    $employee_issues[] = "Increment before 3 years of service (Current: {$years_of_service} years)";
                    $issues_found++;
                }
            }
            
            if (!empty($employee_issues)) {
                $audit_details[] = [
                    'employee_id' => $row['employee_id'],
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'position' => $row['position'],
                    'issues' => $employee_issues
                ];
            }
        }
    }
    
    // Update audit record with results
    $update_audit = "UPDATE salary_audit_log SET 
                     total_employees_checked = ?, 
                     issues_found = ?, 
                     audit_details = ?
                     WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_audit);
    $audit_details_json = json_encode($audit_details);
    mysqli_stmt_bind_param($update_stmt, "iisi", $total_employees, $issues_found, $audit_details_json, $audit_id);
    mysqli_stmt_execute($update_stmt);
    
    return [
        'total_employees' => $total_employees,
        'issues' => $issues_found,
        'details' => $audit_details
    ];
}

// Get recent audit logs
$audit_logs_query = "SELECT sal.*, e.first_name, e.last_name 
                     FROM salary_audit_log sal
                     LEFT JOIN employees e ON sal.created_by = e.id
                     ORDER BY sal.audit_date DESC
                     LIMIT 10";
$audit_logs_result = mysqli_query($conn, $audit_logs_query);
$audit_logs = [];
while ($row = mysqli_fetch_assoc($audit_logs_result)) {
    $audit_logs[] = $row;
}

// Get employees with potential issues
$issues_query = "SELECT ed.employee_id, ed.basic_salary, ed.step_increment, ed.updated_at,
                        e.first_name, e.last_name, e.position, e.hire_date
                 FROM employee_details ed 
                 JOIN employees e ON ed.employee_id = e.id 
                 WHERE ed.basic_salary > 0
                 ORDER BY ed.updated_at DESC";
$issues_result = mysqli_query($conn, $issues_query);
$employees_with_issues = [];
while ($row = mysqli_fetch_assoc($issues_result)) {
    $employees_with_issues[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Salary Audit System</h2>
                <p class="opacity-90">Monitor and prevent unauthorized salary changes</p>
            </div>
            <div class="text-right">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="create_salary_audit">
                    <button type="submit" class="bg-white text-red-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                        <i class="fas fa-search mr-2"></i>Run Full Audit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success_message; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($employees_with_issues); ?></p>
                <p class="text-xs text-blue-600 mt-1">With salary data</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Potential Issues</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($employees_with_issues); ?></p>
                <p class="text-xs text-yellow-600 mt-1">Need investigation</p>
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
                <p class="text-sm font-medium text-gray-500">Audit Runs</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($audit_logs); ?></p>
                <p class="text-xs text-green-600 mt-1">Completed audits</p>
            </div>
        </div>
    </div>
</div>

<!-- Employees with Potential Issues -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Employees with Salary Data</h3>
        <div class="text-sm text-gray-500">
            Last updated: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-red-600 to-red-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Basic Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Step Increment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Last Updated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($employees_with_issues)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                            <p>No employees with salary data found.</p>
                            <p class="text-sm mt-2">All employees have no basic salary set.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees_with_issues as $employee): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">ID: <?php echo $employee['employee_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $employee['position']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₱<?php echo number_format($employee['basic_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    Step <?php echo $employee['step_increment']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($employee['updated_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="investigateEmployee(<?php echo $employee['employee_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors" title="Investigate">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button onclick="correctSalary(<?php echo $employee['employee_id']; ?>, <?php echo $employee['basic_salary']; ?>)" 
                                            class="text-green-600 hover:text-green-900 transition-colors" title="Correct Salary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Audit Logs -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-6">Recent Audit Logs</h3>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audit Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees Checked</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues Found</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($audit_logs)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-history text-4xl mb-4"></i>
                            <p>No audit logs found.</p>
                            <p class="text-sm mt-2">Run your first audit to start monitoring.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($audit_logs as $log): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y H:i', strtotime($log['audit_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo ucfirst($log['audit_type']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $log['total_employees_checked']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium <?php echo $log['issues_found'] > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?> rounded-full">
                                    <?php echo $log['issues_found']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $log['first_name'] . ' ' . $log['last_name']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Salary Correction Modal -->
<div id="correctionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Correct Salary</h3>
                <button onclick="closeCorrectionModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="correctionForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="fix_unauthorized_changes">
                <input type="hidden" name="employee_id" id="correction_employee_id" value="">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Corrected Salary Amount</label>
                    <input type="number" name="corrected_salary" id="corrected_salary" step="0.01" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Correction</label>
                    <textarea name="reason" id="correction_reason" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                              placeholder="Explain why this salary correction is necessary..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeCorrectionModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Correct Salary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function investigateEmployee(employeeId) {
    // Redirect to detailed investigation page
    window.location.href = 'salary-investigation.php?employee_id=' + employeeId;
}

function correctSalary(employeeId, currentSalary) {
    document.getElementById('correction_employee_id').value = employeeId;
    document.getElementById('corrected_salary').value = currentSalary;
    document.getElementById('correctionModal').classList.remove('hidden');
}

function closeCorrectionModal() {
    document.getElementById('correctionModal').classList.add('hidden');
    document.getElementById('correctionForm').reset();
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
/* Custom styles for salary audit */
.audit-card {
    transition: all 0.3s ease;
}

.audit-card:hover {
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
</style>
