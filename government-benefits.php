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
                $person_type = $_POST['person_type']; // 'employee' or 'faculty'
                $sss_number = $_POST['sss_number'];
                $hdmf_number = $_POST['hdmf_number'];
                $bir_tin = $_POST['bir_tin'];
                $phic_number = $_POST['phic_number'];
                
                // Determine which table to update
                $table_name = ($person_type === 'faculty') ? 'faculty' : 'employees';
                
                $query = "UPDATE $table_name SET 
                         sss_number = ?, 
                         pagibig_number = ?, 
                         tin_number = ?, 
                         philhealth_number = ?
                         WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssssi", $sss_number, $hdmf_number, $bir_tin, $phic_number, $employee_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $person_label = ($person_type === 'faculty') ? 'Faculty member' : 'Employee';
                    $success_message = "$person_label government benefits updated successfully!";
                } else {
                    $error_message = "Error updating benefits: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get employees with their government benefits
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
                    'employee' as type
                    FROM employees e 
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
                <h2 class="text-2xl font-bold mb-2">Government Benefits Management</h2>
                <p class="opacity-90">Manage employee government benefits including SSS, HDMF, BIR, and PhilHealth</p>
            </div>
            <div class="text-right">
                <button onclick="exportBenefits()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-download mr-2"></i>Export Data
                </button>
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
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['hdmf_number']) ? htmlspecialchars($employee['hdmf_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['bir_tin']) ? htmlspecialchars($employee['bir_tin']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo !empty($employee['phic_number']) ? htmlspecialchars($employee['phic_number']) : '<span class="text-gray-400 italic">Not set</span>'; ?>
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
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SSS Number</label>
                        <input type="text" name="sss_number" id="sss_number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="xx-xxxxxxx-x">
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth Number</label>
                        <input type="text" name="phic_number" id="phic_number"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="xxxx-xxxx-xxxx">
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
