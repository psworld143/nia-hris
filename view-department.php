<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Validate and decrypt department ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid request: missing department ID';
    exit();
}

$department_id = safe_decrypt_id($_GET['id']);
if ($department_id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid department ID';
    exit();
}

// Fetch department details
$query = "SELECT d.*, u.first_name, u.last_name
          FROM departments d
          LEFT JOIN users u ON d.created_by = u.id
          WHERE d.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $department_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$department = mysqli_fetch_assoc($result);

if (!$department) {
    header('HTTP/1.1 404 Not Found');
    echo 'Department not found';
    exit();
}

$page_title = 'Department Details';
include 'includes/header.php';
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Department Details</h1>
            <p class="text-gray-600">View information for <?php echo htmlspecialchars($department['name']); ?></p>
        </div>
        <div class="flex space-x-2">
            <a href="manage-departments.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Back to Departments
            </a>
            <a href="edit-department.php?id=<?php echo encrypt_id($department['id']); ?>" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition font-medium">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Summary Card -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <div class="flex items-start space-x-4">
            <div class="w-16 h-16 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-lg"
                 style="background-color: <?php echo $department['color_theme']; ?>">
                <i class="<?php echo $department['icon']; ?> text-2xl"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center space-x-3">
                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">
                        <?php echo htmlspecialchars($department['name']); ?>
                    </h2>
                    <span class="inline-flex items-center px-3 py-1 text-xs rounded-full font-semibold <?php echo $department['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $department['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <p class="mt-2 text-gray-600">
                    <?php echo $department['description'] ? nl2br(htmlspecialchars($department['description'])) : 'No description'; ?>
                </p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="text-sm text-gray-500">Sort Order</div>
                <div class="text-lg font-semibold text-gray-900"><?php echo (int)$department['sort_order']; ?></div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="text-sm text-gray-500">Created By</div>
                <div class="text-lg font-semibold text-gray-900">
                    <?php echo ($department['first_name'] || $department['last_name']) ? trim(($department['first_name'] ?? '').' '.($department['last_name'] ?? '')) : 'System'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Meta / Actions Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
        <div class="space-y-3">
            <a href="edit-department.php?id=<?php echo encrypt_id($department['id']); ?>" class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-edit mr-2"></i> Edit Department
            </a>
            <a href="manage-departments.php" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-list mr-2"></i> Back to List
            </a>
        </div>
        <hr class="my-6 border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Info</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            <li><span class="font-medium">ID:</span> <?php echo (int)$department['id']; ?></li>
            <li><span class="font-medium">Status:</span> <?php echo $department['is_active'] ? 'Active' : 'Inactive'; ?></li>
            <li><span class="font-medium">Icon:</span> <?php echo htmlspecialchars($department['icon']); ?></li>
            <li><span class="font-medium">Color:</span> <span class="inline-block align-middle w-4 h-4 rounded" style="background: <?php echo $department['color_theme']; ?>"></span> <code class="ml-2"><?php echo htmlspecialchars($department['color_theme']); ?></code></li>
        </ul>
    </div>
</div>

