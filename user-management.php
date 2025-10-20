<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is Super Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit();
}

$page_title = 'User Management';

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
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
    'nurse' => count(array_filter($users, fn($u) => $u['role'] === 'nurse'))
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
                            'nurse' => 'bg-pink-100 text-pink-800 border-pink-200'
                        ];
                        $role_icons = [
                            'super_admin' => 'fa-crown',
                            'admin' => 'fa-user-shield',
                            'hr_manager' => 'fa-user-tie',
                            'human_resource' => 'fa-user',
                            'nurse' => 'fa-user-nurse'
                        ];
                        $role_display = [
                            'super_admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'hr_manager' => 'HR Manager',
                            'human_resource' => 'Human Resource',
                            'nurse' => 'Nurse'
                        ];
                        ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full border <?php echo $role_badges[$user['role']]; ?>">
                            <i class="fas <?php echo $role_icons[$user['role']]; ?> mr-1"></i>
                            <?php echo $role_display[$user['role']]; ?>
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
                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" 
                                class="<?php echo $user['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>">
                            <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check-circle'; ?>"></i>
                            <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        <?php endif; ?>
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
</script>

