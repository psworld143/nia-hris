<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'User Management';

// Get all users that are matched with employees
$users_query = "SELECT u.* FROM users u 
                INNER JOIN employees e ON u.email = e.email 
                WHERE e.is_active = 1 
                ORDER BY u.created_at DESC";
$users_result = mysqli_query($conn, $users_query);
$users = [];
while ($user = mysqli_fetch_assoc($users_result)) {
    $users[] = $user;
}

// Calculate statistics
$stats = [
    'total' => count($users),
    'active' => count(array_filter($users, fn($u) => $u['status'] === 'active')),
    'inactive' => count(array_filter($users, fn($u) => $u['status'] === 'inactive')),
    'super_admin' => count(array_filter($users, fn($u) => $u['role'] === 'super_admin')),
    'admin' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
    'hr_manager' => count(array_filter($users, fn($u) => $u['role'] === 'hr_manager')),
    'human_resource' => count(array_filter($users, fn($u) => $u['role'] === 'human_resource')),
    'nurse' => count(array_filter($users, fn($u) => $u['role'] === 'nurse')),
    'employee' => count(array_filter($users, fn($u) => $u['role'] === 'employee'))
];

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-users-cog mr-2"></i>User Management
                </h2>
                <p class="opacity-90">Manage system users, roles, and permissions</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if (function_exists('getRoleBadge')): ?>
                    <?php echo getRoleBadge($_SESSION['role']); ?>
                <?php endif; ?>
                <button onclick="openAddUserModal()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Add New User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Users</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Active</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['active']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-crown text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Super Admins</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['super_admin']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user-shield text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Admins</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['admin']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-user-nurse text-pink-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Nurses</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['nurse']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-users text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No users found</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-green-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-green-600 font-semibold">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                        $role_badges = [
                            'super_admin' => 'bg-red-100 text-red-800 border-red-200',
                            'admin' => 'bg-purple-100 text-purple-800 border-purple-200',
                            'hr_manager' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'human_resource' => 'bg-green-100 text-green-800 border-green-200',
                            'nurse' => 'bg-pink-100 text-pink-800 border-pink-200',
                            'employee' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                        $role_icons = [
                            'super_admin' => 'fa-crown',
                            'admin' => 'fa-user-shield',
                            'hr_manager' => 'fa-user-tie',
                            'human_resource' => 'fa-user',
                            'nurse' => 'fa-user-nurse',
                            'employee' => 'fa-user-circle'
                        ];
                        $role_display = [
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'hr_manager' => 'HR Manager',
                            'human_resource' => 'Human Resource',
                            'nurse' => 'Nurse',
                            'employee' => 'Employee'
                        ];
                        // Get role with fallback to prevent undefined key errors
                        $user_role = $user['role'] ?? 'unknown';
                        $role_badge = $role_badges[$user_role] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        $role_icon = $role_icons[$user_role] ?? 'fa-user';
                        $role_name = $role_display[$user_role] ?? ucfirst(str_replace('_', ' ', $user_role));
                        ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $role_badge; ?>">
                            <i class="fas <?php echo $role_icon; ?> mr-1"></i>
                            <?php echo $role_name; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($user['status'] === 'active'): ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <i class="fas fa-times-circle mr-1"></i>Inactive
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex flex-wrap gap-2">
                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                    class="text-green-600 hover:text-green-900">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="resetUserPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                    class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" 
                                    class="<?php echo $user['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check-circle'; ?>"></i>
                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">
                <i class="fas fa-user-plus text-green-600 mr-2"></i>Add New User
            </h3>
            <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="userForm" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="user_id" id="userId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-indigo-500 mr-1"></i>First Name *
                    </label>
                    <input type="text" name="first_name" id="firstName" required
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-indigo-500 mr-1"></i>Last Name *
                    </label>
                    <input type="text" name="last_name" id="lastName" required
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-at text-indigo-500 mr-1"></i>Username *
                    </label>
                    <input type="text" name="username" id="username" required
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope text-indigo-500 mr-1"></i>Email *
                    </label>
                    <input type="email" name="email" id="email" required
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-shield-alt text-indigo-500 mr-1"></i>Role *
                    </label>
                    <select name="role" id="role" required
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="human_resource">Human Resource</option>
                        <option value="hr_manager">HR Manager</option>
                        <option value="admin">Admin</option>
                        <option value="nurse">Nurse</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-toggle-on text-indigo-500 mr-1"></i>Status *
                    </label>
                    <select name="status" id="status" required
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div id="passwordField" class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock text-indigo-500 mr-1"></i>Password <span id="passwordRequired">*</span>
                    </label>
                    <input type="password" name="password" id="password"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep existing password (when editing)</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="closeUserModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i><span id="submitButtonText">Create User</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Reset Confirmation Modal -->
<div id="passwordResetModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="passwordResetModalContent">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-key text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Reset Password</h3>
                        <p class="text-sm text-gray-600">Confirm Password Reset</p>
                    </div>
                </div>
                <button onclick="closePasswordResetModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="mb-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-900 mb-2">Password Reset Warning</h4>
                            <p class="text-yellow-800 text-sm leading-relaxed">
                                You are about to reset the password for user <strong id="resetUsername"></strong>. 
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                        <i class="fas fa-key text-blue-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">New Password</p>
                            <p class="text-sm text-gray-600">Password will be reset to: <strong class="text-blue-600">NIA2025</strong></p>
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-green-50 rounded-lg">
                        <i class="fas fa-shield-alt text-green-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">Security Notice</p>
                            <p class="text-sm text-gray-600">User will need to change this password on next login</p>
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                        <i class="fas fa-bell text-purple-600 mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">User Notification</p>
                            <p class="text-sm text-gray-600">Please inform the user of the new password immediately</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex space-x-3">
                <button onclick="closePasswordResetModal()" 
                        class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                <button onclick="confirmPasswordReset()" 
                        class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white py-3 px-4 rounded-lg font-medium hover:from-red-600 hover:to-red-700 transition-all transform hover:scale-105">
                    <i class="fas fa-key mr-2"></i>
                    Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Success Modal -->
<div id="passwordResetSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="passwordResetSuccessModalContent" style="transform: scale(0.95); opacity: 0;">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Password Reset Successful!</h3>
                <p class="text-gray-600">The password has been reset successfully</p>
            </div>

            <!-- Success Content -->
            <div class="mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-green-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-green-900 mb-2">Reset Complete</h4>
                            <p class="text-green-800 text-sm leading-relaxed">
                                The password has been successfully reset for the user. Please provide them with the new credentials below.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Credentials Display -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-key text-blue-600 mr-2"></i>
                        New Login Credentials
                    </h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-white rounded border">
                            <span class="text-sm font-medium text-gray-600">Username:</span>
                            <span class="text-sm font-semibold text-gray-900" id="successUsername"></span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-white rounded border">
                            <span class="text-sm font-medium text-gray-600">New Password:</span>
                            <span class="text-sm font-bold text-blue-600" id="successPassword">NIA2025</span>
                        </div>
                    </div>
                </div>

                <!-- Important Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-900 mb-2">Important Notice</h4>
                            <ul class="text-yellow-800 text-sm space-y-1">
                                <li>• User must change password on next login</li>
                                <li>• Provide credentials securely to the user</li>
                                <li>• Password reset has been logged for audit</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex space-x-3">
                <button onclick="copyCredentials()" 
                        class="flex-1 bg-blue-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-600 transition-colors">
                    <i class="fas fa-copy mr-2"></i>
                    Copy Credentials
                </button>
                <button onclick="closePasswordResetSuccessModal()" 
                        class="flex-1 bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus text-green-600 mr-2"></i>Add New User';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitButtonText').textContent = 'Create User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').setAttribute('required', 'required');
    document.getElementById('passwordRequired').textContent = '*';
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(user) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-yellow-600 mr-2"></i>Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('submitButtonText').textContent = 'Update User';
    document.getElementById('userId').value = user.id;
    document.getElementById('firstName').value = user.first_name;
    document.getElementById('lastName').value = user.last_name;
    document.getElementById('username').value = user.username;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('status').value = user.status;
    document.getElementById('password').removeAttribute('required');
    document.getElementById('passwordRequired').textContent = '';
    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

// Handle form submission
document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    fetch('save-user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error saving user');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function toggleUserStatus(userId, currentStatus, username) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    const message = currentStatus === 'active' 
        ? `Deactivate user "${username}"?\n\nThis user will not be able to log in.`
        : `Activate user "${username}"?`;
    
    if (!confirm(message)) return;
    
    fetch('toggle-user-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating user status');
    });
}

function resetUserPassword(userId, username) {
    // Store the user data for the confirmation
    window.pendingPasswordReset = { userId: userId, username: username };
    
    // Update the modal content
    document.getElementById('resetUsername').textContent = username;
    
    // Show the modal
    openPasswordResetModal();
}

function openPasswordResetModal() {
    const modal = document.getElementById('passwordResetModal');
    const modalContent = document.getElementById('passwordResetModalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Trigger animation
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closePasswordResetModal() {
    const modal = document.getElementById('passwordResetModal');
    const modalContent = document.getElementById('passwordResetModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
        
        // Clear pending reset data
        window.pendingPasswordReset = null;
    }, 300);
}

function confirmPasswordReset() {
    if (!window.pendingPasswordReset) return;
    
    const { userId, username } = window.pendingPasswordReset;
    
    // Close the modal first
    closePasswordResetModal();
    
    // Show loading state
    const resetBtn = event.target;
    const originalText = resetBtn.innerHTML;
    resetBtn.disabled = true;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting...';
    
    fetch('reset-user-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Show success modal
            showPasswordResetSuccess(username);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Password reset error:', error);
        alert('Error resetting password: ' + error.message);
    })
    .finally(() => {
        resetBtn.disabled = false;
        resetBtn.innerHTML = originalText;
    });
}

function showPasswordResetSuccess(username) {
    console.log('showPasswordResetSuccess called with username:', username);
    
    // Update the success modal content
    const usernameElement = document.getElementById('successUsername');
    if (usernameElement) {
        usernameElement.textContent = username;
        console.log('Username element updated');
    } else {
        console.error('Username element not found');
    }
    
    // Show the success modal
    openPasswordResetSuccessModal();
}

function openPasswordResetSuccessModal() {
    console.log('openPasswordResetSuccessModal called');
    
    const modal = document.getElementById('passwordResetSuccessModal');
    const modalContent = document.getElementById('passwordResetSuccessModalContent');
    
    if (!modal) {
        console.error('Success modal element not found');
        return;
    }
    
    if (!modalContent) {
        console.error('Success modal content element not found');
        return;
    }
    
    console.log('Modal elements found, showing modal');
    
    // Show modal using both class and style
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    
    // Trigger animation
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
        modalContent.style.transform = 'scale(1)';
        modalContent.style.opacity = '1';
        console.log('Modal animation triggered');
    }, 10);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closePasswordResetSuccessModal() {
    const modal = document.getElementById('passwordResetSuccessModal');
    const modalContent = document.getElementById('passwordResetSuccessModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    modalContent.style.transform = 'scale(0.95)';
    modalContent.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reload the page to show updated data
        window.location.reload();
    }, 300);
}

function copyCredentials() {
    const username = document.getElementById('successUsername').textContent;
    const password = document.getElementById('successPassword').textContent;
    
    const credentials = `Username: ${username}\nPassword: ${password}`;
    
    navigator.clipboard.writeText(credentials).then(() => {
        // Show success feedback
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
        button.classList.add('bg-green-500', 'hover:bg-green-600');
        button.classList.remove('bg-blue-500', 'hover:bg-blue-600');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-500', 'hover:bg-green-600');
            button.classList.add('bg-blue-500', 'hover:bg-blue-600');
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        alert('Credentials copied to clipboard!');
    });
}

// Close password reset modal when clicking outside
document.getElementById('passwordResetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordResetModal();
    }
});

// Close password reset modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const passwordResetModal = document.getElementById('passwordResetModal');
        const successModal = document.getElementById('passwordResetSuccessModal');
        
        if (!passwordResetModal.classList.contains('hidden')) {
            closePasswordResetModal();
        } else if (!successModal.classList.contains('hidden')) {
            closePasswordResetSuccessModal();
        }
    }
});

// Close success modal when clicking outside
document.getElementById('passwordResetSuccessModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordResetSuccessModal();
    }
});
</script>

</body>
</html>

