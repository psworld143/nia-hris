<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: index.php');
    exit();
}

$page_title = 'Review Increment Request';
$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);
$success_message = '';
$error_message = '';

if ($request_id <= 0) {
    header('Location: increment-requests.php');
    exit();
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $comments = trim($_POST['comments'] ?? '');
    
    if ($action === 'approve') {
        // Update request status
        $update_query = "UPDATE increment_requests 
                        SET status = 'approved', approved_at = CURRENT_TIMESTAMP 
                        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $request_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log approval
            $history_query = "INSERT INTO increment_request_history 
                             (request_id, action, old_status, new_status, comments, performed_by)
                             VALUES (?, 'approved', 'submitted', 'approved', ?, ?)";
            $history_stmt = mysqli_prepare($conn, $history_query);
            mysqli_stmt_bind_param($history_stmt, "isi", $request_id, $comments, $user_id);
            mysqli_stmt_execute($history_stmt);
            
            $success_message = "Increment request approved successfully!";
        } else {
            $error_message = "Error approving request: " . mysqli_error($conn);
        }
        
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason']);
        
        // Update request status
        $update_query = "UPDATE increment_requests 
                        SET status = 'rejected', rejected_at = CURRENT_TIMESTAMP, rejection_reason = ?
                        WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $rejection_reason, $request_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log rejection
            $history_query = "INSERT INTO increment_request_history 
                             (request_id, action, old_status, new_status, comments, performed_by)
                             VALUES (?, 'rejected', 'submitted', 'rejected', ?, ?)";
            $history_stmt = mysqli_prepare($conn, $history_query);
            $combined_comments = "Rejection Reason: " . $rejection_reason . ($comments ? " | Comments: " . $comments : "");
            mysqli_stmt_bind_param($history_stmt, "isi", $request_id, $combined_comments, $user_id);
            mysqli_stmt_execute($history_stmt);
            
            $success_message = "Increment request rejected.";
        } else {
            $error_message = "Error rejecting request: " . mysqli_error($conn);
        }
    }
}

// Get request details
$query = "SELECT ir.*, 
                 CASE 
                     WHEN ir.employee_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
                 END as employee_name,
                 CASE 
                     WHEN ir.employee_type = 'employee' THEN e.email
                 END as employee_email,
                 it.type_name, it.description as type_description,
                 CONCAT(u.first_name, ' ', u.last_name) as created_by_name
          FROM increment_requests ir
          LEFT JOIN employees e ON ir.employee_id = e.id AND ir.employee_type = 'employee'
          LEFT JOIN increment_types it ON ir.increment_type_id = it.id
          LEFT JOIN users u ON ir.created_by = u.id
          WHERE ir.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);

if (!$request) {
    header('Location: increment-requests.php');
    exit();
}

// Get request history
$history_query = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as performed_by_name
                  FROM increment_request_history h
                  LEFT JOIN users u ON h.performed_by = u.id
                  WHERE h.request_id = ?
                  ORDER BY h.created_at DESC";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $request_id);
mysqli_stmt_execute($history_stmt);
$history_result = mysqli_stmt_get_result($history_stmt);
$history = mysqli_fetch_all($history_result, MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Review Increment Request</h1>
                <p class="text-gray-600">Request #<?php echo htmlspecialchars($request['request_number']); ?></p>
            </div>
            <a href="increment-requests.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to Requests
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Request Details -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Request Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Employee</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($request['employee_name']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($request['employee_email']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Department</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($request['department']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Position</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($request['position']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Increment Type</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($request['type_name']); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Current Salary</label>
                        <p class="text-lg text-gray-900">₱<?php echo number_format($request['current_salary'], 2); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Requested Amount</label>
                        <p class="text-lg text-green-600 font-semibold">₱<?php echo number_format($request['requested_amount'], 2); ?></p>
                        <p class="text-sm text-gray-600"><?php echo number_format($request['requested_percentage'], 2); ?>% increase</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">New Salary</label>
                        <p class="text-lg text-blue-600 font-semibold">₱<?php echo number_format($request['new_salary'], 2); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Effective Date</label>
                        <p class="text-lg text-gray-900"><?php echo date('F j, Y', strtotime($request['effective_date'])); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Priority</label>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php 
                            switch($request['priority']) {
                                case 'urgent': echo 'bg-red-100 text-red-800'; break;
                                case 'high': echo 'bg-orange-100 text-orange-800'; break;
                                case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($request['priority']); ?>
                        </span>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php 
                            switch($request['status']) {
                                case 'approved': echo 'bg-green-100 text-green-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'under_review': echo 'bg-yellow-100 text-yellow-800'; break;
                                default: echo 'bg-blue-100 text-blue-800';
                            }
                            ?>">
                            <?php echo ucwords(str_replace('_', ' ', $request['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Justification</label>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($request['justification'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Approval Actions -->
            <?php if ($request['status'] === 'submitted' || $request['status'] === 'under_review'): ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Review Actions</h3>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                        <textarea name="comments" rows="4" 
                                  placeholder="Add your review comments..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>
                    
                    <div id="rejectionReason" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                        <textarea name="rejection_reason" rows="3" 
                                  placeholder="Please provide a reason for rejection..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="action" value="approve"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                            <i class="fas fa-check mr-2"></i>Approve Request
                        </button>
                        <button type="button" onclick="showRejectionForm()"
                                class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
                            <i class="fas fa-times mr-2"></i>Reject Request
                        </button>
                        <button type="submit" name="action" value="hold" id="holdBtn" class="hidden"
                                class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 flex items-center">
                            <i class="fas fa-pause mr-2"></i>Put on Hold
                        </button>
                    </div>
                    
                    <button type="submit" name="action" value="reject" id="confirmRejectBtn" 
                            class="hidden px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Confirm Rejection
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Request Info -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Request Information</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created:</span>
                        <span class="text-gray-900"><?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created by:</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($request['created_by_name']); ?></span>
                    </div>
                    <?php if ($request['submitted_at']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Submitted:</span>
                        <span class="text-gray-900"><?php echo date('M j, Y', strtotime($request['submitted_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Fiscal Year:</span>
                        <span class="text-gray-900"><?php echo $request['fiscal_year']; ?></span>
                    </div>
                </div>
            </div>

            <!-- History -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Request History</h3>
                <div class="space-y-4">
                    <?php foreach ($history as $item): ?>
                    <div class="border-l-2 border-gray-200 pl-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900"><?php echo ucfirst($item['action']); ?></span>
                            <span class="text-xs text-gray-500"><?php echo date('M j, H:i', strtotime($item['created_at'])); ?></span>
                        </div>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['performed_by_name']); ?></p>
                        <?php if ($item['comments']): ?>
                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($item['comments']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showRejectionForm() {
    document.getElementById('rejectionReason').classList.remove('hidden');
    document.getElementById('confirmRejectBtn').classList.remove('hidden');
    document.getElementById('holdBtn').classList.remove('hidden');
}
</script>

