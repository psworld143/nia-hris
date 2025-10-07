<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: ../index.php');
    exit();
}

// Redirect to new increment requests system
header('Location: increment-requests.php');
exit();

// Set page title
$page_title = 'Salary Incrementation Management';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_increment':
                $increment_id = intval($_POST['increment_id']);
                $query = "UPDATE salary_increments SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $increment_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Salary increment approved successfully!";
                } else {
                    $error_message = "Error approving salary increment: " . mysqli_error($conn);
                }
                break;
                
            case 'reject_increment':
                $increment_id = intval($_POST['increment_id']);
                $rejection_reason = $_POST['rejection_reason'];
                $query = "UPDATE salary_increments SET status = 'rejected', reason = CONCAT(IFNULL(reason, ''), ' | Rejection: ', ?) WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $rejection_reason, $increment_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Salary increment rejected successfully!";
                } else {
                    $error_message = "Error rejecting salary increment: " . mysqli_error($conn);
                }
                break;
        }
    }
}

// Get statistics
$stats = [];

// Get pending increments count
$pending_query = "SELECT COUNT(*) as total FROM salary_increments WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_increments'] = mysqli_fetch_assoc($pending_result)['total'];

// Get approved increments this month
$approved_query = "SELECT COUNT(*) as total FROM salary_increments WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$approved_result = mysqli_query($conn, $approved_query);
$stats['approved_this_month'] = mysqli_fetch_assoc($approved_result)['total'];

// Get total increments this year
$total_query = "SELECT COUNT(*) as total FROM salary_increments WHERE YEAR(created_at) = YEAR(CURRENT_DATE())";
$total_result = mysqli_query($conn, $total_query);
$stats['total_this_year'] = mysqli_fetch_assoc($total_result)['total'];

// Get average increment percentage
$avg_query = "SELECT AVG(increment_percentage) as avg_percentage FROM salary_increments WHERE status = 'approved' AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$avg_result = mysqli_query($conn, $avg_query);
$stats['avg_increment_percentage'] = round(mysqli_fetch_assoc($avg_result)['avg_percentage'], 2);

// Get salary increments with incrementation details
$increments_query = "SELECT 
    si.*,
    e.first_name,
    e.last_name,
    e.employee_id as emp_id,
    e.position,
    e.department,
    ss.position_title,
    ss.grade_level,
    approver.first_name as approver_first_name,
    approver.last_name as approver_last_name
    FROM salary_increments si
    LEFT JOIN employees e ON si.employee_id = e.id
    LEFT JOIN salary_structures ss ON si.salary_structure_id = ss.id
    LEFT JOIN employees approver ON si.approved_by = approver.id
    ORDER BY si.created_at DESC
    LIMIT 50";

$increments_result = mysqli_query($conn, $increments_query);
$increments = [];
while ($row = mysqli_fetch_assoc($increments_result)) {
    $increments[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Salary Incrementation Management</h2>
                <p class="opacity-90">Manage employee salary increments with incrementation criteria</p>
            </div>
            <div class="text-right">
                <a href="add-salary-increment.php" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Increment
                </a>
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
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_increments']; ?></p>
                <p class="text-xs text-yellow-600 mt-1">Awaiting review</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Approved This Month</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['approved_this_month']; ?></p>
                <p class="text-xs text-green-600 mt-1">Successfully processed</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total This Year</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_this_year']; ?></p>
                <p class="text-xs text-blue-600 mt-1">All increments</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Avg. Increment</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['avg_increment_percentage']; ?>%</p>
                <p class="text-xs text-purple-600 mt-1">This year</p>
            </div>
        </div>
    </div>
</div>

<!-- Salary Increments Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Salary Increment Requests</h3>
        <div class="flex space-x-2">
            <button class="px-4 py-2 text-sm bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                <i class="fas fa-download mr-1"></i>Export
            </button>
            <button class="px-4 py-2 text-sm bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition-colors">
                <i class="fas fa-print mr-1"></i>Print
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Current Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Increment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">New Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Incrementation Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Frequency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($increments)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p>No salary increment requests found.</p>
                            <a href="add-salary-increment.php" class="text-green-600 hover:text-green-800 font-medium">Create your first increment</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($increments as $increment): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <?php echo strtoupper(substr($increment['first_name'], 0, 1) . substr($increment['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo $increment['first_name'] . ' ' . $increment['last_name']; ?>
                                        </div>
                                        <div class="text-sm text-gray-500"><?php echo $increment['emp_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $increment['position']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $increment['department']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₱<?php echo number_format($increment['current_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">₱<?php echo number_format($increment['increment_amount'], 2); ?></div>
                                <div class="text-sm text-green-600"><?php echo $increment['increment_percentage']; ?>%</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₱<?php echo number_format($increment['new_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo $increment['incrementation_name'] ?? 'N/A'; ?></div>
                                <div class="text-xs text-gray-500"><?php echo substr($increment['incrementation_description'] ?? 'No description', 0, 50) . '...'; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo ($increment['incrementation_frequency_years'] ?? 1) . ' year(s)'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($increment['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        case 'implemented': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($increment['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <?php if ($increment['status'] === 'pending'): ?>
                                        <button onclick="approveIncrement(<?php echo $increment['id']; ?>)" 
                                                class="text-green-600 hover:text-green-900 transition-colors" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectIncrement(<?php echo $increment['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900 transition-colors" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="viewIncrementDetails(<?php echo $increment['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 transition-colors" title="View Details">
                                        <i class="fas fa-eye"></i>
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


<script>
// Action functions
function approveIncrement(incrementId) {
    if (confirm('Are you sure you want to approve this salary increment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_increment">
            <input type="hidden" name="increment_id" value="${incrementId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectIncrement(incrementId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="reject_increment">
            <input type="hidden" name="increment_id" value="${incrementId}">
            <input type="hidden" name="rejection_reason" value="${reason}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewIncrementDetails(incrementId) {
    // Implement view details functionality
    alert('View details functionality will be implemented');
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
/* Custom styles for salary incrementation */
.increment-card {
    transition: all 0.3s ease;
}

.increment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
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
