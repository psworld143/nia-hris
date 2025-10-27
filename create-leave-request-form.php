<?php
// Don't start session if already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/roles.php';

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get user information including email
$user_query = "SELECT email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
$user_email = $user_info['email'] ?? '';

// Get employee information by matching email
$employee_query = "SELECT e.*, d.name as department_name 
                   FROM employees e 
                   LEFT JOIN departments d ON e.department_id = d.id 
                   WHERE e.email = ? AND e.is_active = 1";
$employee_stmt = mysqli_prepare($conn, $employee_query);
mysqli_stmt_bind_param($employee_stmt, "s", $user_email);
mysqli_stmt_execute($employee_stmt);
$employee = mysqli_fetch_assoc(mysqli_stmt_get_result($employee_stmt));

if (!$employee) {
    die('Employee record not found');
}

// Get available leave types
$leave_types_query = "SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name";
$leave_types_result = mysqli_query($conn, $leave_types_query);
$leave_types = mysqli_fetch_all($leave_types_result, MYSQLI_ASSOC);

// Get employee's leave balances
$employee_id = $employee['id'];
$leave_balance_query = "SELECT 
    COALESCE(SUM(CASE WHEN lt.name = 'Vacation Leave' THEN ela.remaining_days ELSE 0 END), 0) as vacation_balance,
    COALESCE(SUM(CASE WHEN lt.name = 'Sick Leave' THEN ela.remaining_days ELSE 0 END), 0) as sick_balance,
    COALESCE(SUM(CASE WHEN lt.name = 'Emergency Leave' THEN ela.remaining_days ELSE 0 END), 0) as personal_balance
    FROM employee_leave_allowances ela
    LEFT JOIN leave_types lt ON ela.leave_type_id = lt.id
    WHERE ela.employee_id = ? AND ela.year = YEAR(CURDATE())";
$leave_stmt = mysqli_prepare($conn, $leave_balance_query);
mysqli_stmt_bind_param($leave_stmt, "i", $employee_id);
mysqli_stmt_execute($leave_stmt);
$leave_balance = mysqli_fetch_assoc(mysqli_stmt_get_result($leave_stmt));

// Get recent leave requests for this employee
$recent_leaves_query = "SELECT elr.*, lt.name as leave_type_name
                       FROM employee_leave_requests elr
                       LEFT JOIN leave_types lt ON elr.leave_type_id = lt.id
                       WHERE elr.employee_id = ? 
                       ORDER BY elr.created_at DESC LIMIT 10";
$recent_stmt = mysqli_prepare($conn, $recent_leaves_query);
mysqli_stmt_bind_param($recent_stmt, "i", $employee_id);
mysqli_stmt_execute($recent_stmt);
$recent_leaves = mysqli_fetch_all(mysqli_stmt_get_result($recent_stmt), MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">Request Leave</h1>
                <p class="text-green-100">Submit a new leave request - NIA Human Resource Information System</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-green-100">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></p>
                <p class="text-sm text-green-100">Department: <?php echo htmlspecialchars($employee['department_name'] ?? 'Not assigned'); ?></p>
            </div>
        </div>
    </div>

    <!-- Leave Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Vacation Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['vacation_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-heartbeat text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Sick Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['sick_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Emergency Leave</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $leave_balance['personal_balance'] ?? 0; ?> days</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Request Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-calendar-plus mr-2 text-green-600"></i>Leave Request Form
        </h3>

        <form id="leaveRequestForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Leave Type -->
                <div>
                    <label for="leave_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Leave Type <span class="text-red-500">*</span>
                    </label>
                    <select id="leave_type_id" name="leave_type_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select Leave Type</option>
                        <?php foreach ($leave_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Total Days (Auto-calculated) -->
                <div>
                    <label for="total_days" class="block text-sm font-medium text-gray-700 mb-2">
                        Total Days
                    </label>
                    <input type="text" id="total_days" name="total_days" readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="start_date" name="start_date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="end_date" name="end_date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <!-- Reason -->
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Reason <span class="text-red-500">*</span>
                </label>
                <textarea id="reason" name="reason" rows="4" required
                          placeholder="Please provide a detailed reason for your leave request..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <a href="employee-dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Request
                </button>
            </div>
        </form>
    </div>

    <!-- Response Message -->
    <div id="responseMessage" class="mt-6 hidden">
        <div id="messageContent" class="p-4 rounded-lg"></div>
    </div>

    <!-- Leave Request History -->
    <div class="bg-white rounded-lg shadow-md mt-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-history mr-2 text-blue-600"></i>Recent Leave Requests
            </h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recent_leaves)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center py-8">
                                    <i class="fas fa-calendar-times text-gray-300 text-4xl mb-4"></i>
                                    <p class="text-gray-500 text-lg">No leave requests found</p>
                                    <p class="text-gray-400 text-sm">Submit your first leave request above</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_leaves as $leave): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                                        <?php echo htmlspecialchars($leave['leave_type_name'] ?? 'Leave'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['start_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $leave['total_days']; ?> day(s)
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($leave['status']) {
                                            case 'approved_by_hr': echo 'bg-green-100 text-green-800'; break;
                                            case 'approved_by_head': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                            case 'cancelled': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $leave['status'] ?? 'Unknown')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($leave['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($leave['reason'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars($leave['reason'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($recent_leaves)): ?>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">
                    Showing <?php echo count($recent_leaves); ?> most recent leave requests
                </p>
                <a href="employee-leave-history.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All Leave History <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('leaveRequestForm');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalDaysInput = document.getElementById('total_days');
    const submitBtn = document.getElementById('submitBtn');
    const responseMessage = document.getElementById('responseMessage');
    const messageContent = document.getElementById('messageContent');

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
    endDateInput.min = today;

    // Calculate total days when dates change
    function calculateDays() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDateInput.value && endDateInput.value) {
            if (startDate <= endDate) {
                const timeDiff = endDate.getTime() - startDate.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end dates
                totalDaysInput.value = daysDiff + ' day(s)';
            } else {
                totalDaysInput.value = '';
                alert('End date must be after or equal to start date');
            }
        } else {
            totalDaysInput.value = '';
        }
    }

    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        calculateDays();
    });

    endDateInput.addEventListener('change', calculateDays);

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
        
        // Prepare form data
        const formData = new FormData(form);
        
        // Send request to create-leave-request.php
        fetch('create-leave-request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageContent.innerHTML = `
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Success!</strong> ${data.message}
                        </div>
                    </div>
                `;
                form.reset();
                totalDaysInput.value = '';
                
                // Reload page after 2 seconds to show updated history
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                messageContent.innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Error!</strong> ${data.message}
                        </div>
                    </div>
                `;
            }
            
            responseMessage.classList.remove('hidden');
            
            // Scroll to message
            responseMessage.scrollIntoView({ behavior: 'smooth' });
            
            // Hide message after 5 seconds
            setTimeout(() => {
                responseMessage.classList.add('hidden');
            }, 5000);
        })
        .catch(error => {
            console.error('Error:', error);
            messageContent.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Error!</strong> An unexpected error occurred. Please try again.
                    </div>
                </div>
            `;
            responseMessage.classList.remove('hidden');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Submit Request';
        });
    });
});
</script>
