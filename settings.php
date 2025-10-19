<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if profile_photo column exists in users table, if not add it
$check_column = "SHOW COLUMNS FROM users LIKE 'profile_photo'";
$result = mysqli_query($conn, $check_column);
if (mysqli_num_rows($result) == 0) {
    $add_column = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER email";
    mysqli_query($conn, $add_column);
}

// Check if user is logged in and has human_resource or hr_manager role
// Only Super Admin can access settings
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $first_name = sanitize_input($_POST['first_name']);
                $last_name = sanitize_input($_POST['last_name']);
                $email = sanitize_input($_POST['email']);
                $phone = sanitize_input($_POST['phone']);
                $department = sanitize_input($_POST['department']);
                $position = sanitize_input($_POST['position']);

                // Validate email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    // Check if email is already taken by another user
                    $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
                    $stmt = mysqli_prepare($conn, $email_check_query);
                    mysqli_stmt_bind_param($stmt, "si", $email, $_SESSION['user_id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    if (mysqli_num_rows($result) > 0) {
                        $error = 'Email address is already taken by another user.';
                    } else {
                        // Update users table
                        $update_user_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                        $stmt = mysqli_prepare($conn, $update_user_query);
                        mysqli_stmt_bind_param($stmt, "sssi", $first_name, $last_name, $email, $_SESSION['user_id']);

                        if (mysqli_stmt_execute($stmt)) {
                            // Update session data
                            $_SESSION['first_name'] = $first_name;
                            $_SESSION['last_name'] = $last_name;
                            $_SESSION['email'] = $email;

                            $message = 'Profile updated successfully!';
                        } else {
                            $error = 'Error updating user information: ' . mysqli_error($conn);
                        }
                    }
                }
                break;

            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Verify current password
                $verify_query = "SELECT password FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $verify_query);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);

                if (password_verify($current_password, $user['password'])) {
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 6) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_query = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                            $stmt = mysqli_prepare($conn, $update_query);
                            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['user_id']);

                            if (mysqli_stmt_execute($stmt)) {
                                $message = 'Password changed successfully!';
                            } else {
                                $error = 'Failed to change password.';
                            }
                        } else {
                            $error = 'New password must be at least 6 characters long.';
                        }
                    } else {
                        $error = 'New passwords do not match.';
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
                break;

            case 'update_photo':
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['profile_photo'];
                    $upload_dir = 'uploads/hr-photos/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $error_message = "Failed to create upload directory. Please check permissions.";
                            break;
                        }
                    }
                    
                    // Validate file type
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, $allowed_types)) {
                        // Validate file size (2MB max)
                        if ($file['size'] <= 2 * 1024 * 1024) {
                            // Get current photo to delete later
                            $current_photo_query = "SELECT profile_photo FROM users WHERE id = ?";
                            $current_photo_stmt = mysqli_prepare($conn, $current_photo_query);
                            mysqli_stmt_bind_param($current_photo_stmt, "i", $_SESSION['user_id']);
                            mysqli_stmt_execute($current_photo_stmt);
                            $current_photo_result = mysqli_stmt_get_result($current_photo_stmt);
                            $current_photo_row = mysqli_fetch_assoc($current_photo_result);
                            $current_photo = $current_photo_row ? $current_photo_row['profile_photo'] : null;
                            
                            // Generate unique filename
                            $filename = 'hr_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                                // Update database with new photo path
                                $photo_path = 'uploads/hr-photos/' . $filename;
                                $update_query = "UPDATE users SET profile_photo = ? WHERE id = ?";
                                $stmt = mysqli_prepare($conn, $update_query);
                                mysqli_stmt_bind_param($stmt, "si", $photo_path, $_SESSION['user_id']);

                                if (mysqli_stmt_execute($stmt)) {
                                    // Delete old photo if it exists
                                    if ($current_photo && file_exists($current_photo)) {
                                        unlink($current_photo);
                                    }
                                    
                                    // Update session with new photo path
                                    $_SESSION['profile_photo'] = $photo_path;
                                    
                                    $message = 'Profile photo updated successfully!';
                                } else {
                                    $error = 'Failed to update profile photo in database.';
                                }
                            } else {
                                $error = 'Failed to upload photo.';
                            }
                        } else {
                            $error = 'Photo size must be less than 2MB.';
                        }
                    } else {
                        $error = 'Invalid file type. Please upload JPG, PNG, or GIF.';
                    }
                } else {
                    $error = 'No photo selected or upload error occurred.';
                }
                break;
        }
    }
}

// Get current user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

// Set page title
$page_title = 'HR Settings';

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">HR Settings</h1>
            <p class="text-gray-600">Manage your profile information and account settings</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-right">
                <p class="text-sm text-gray-500">Last updated</p>
                <p class="text-sm font-medium text-gray-900"><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($message): ?>
<div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
    <div class="flex items-center">
        <i class="fas fa-check-circle text-green-500 mr-3"></i>
        <p class="text-green-800 font-medium"><?php echo htmlspecialchars($message); ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($error); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Settings Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Information -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Profile Information</h3>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user text-green-500"></i>
                    <span class="text-sm text-gray-500">Personal Details</span>
                </div>
            </div>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <input type="text" name="department" value="Human Resources" readonly
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg bg-gray-50 text-gray-600">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($user['position'] ?? 'HR Officer'); ?>"
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Profile Photo & Security -->
    <div class="space-y-6">
        <!-- Profile Photo -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Profile Photo</h3>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-camera text-green-500"></i>
                    <span class="text-sm text-gray-500">Photo</span>
                </div>
            </div>
            
            <div class="text-center">
                <div class="w-24 h-24 mx-auto mb-4 rounded-full overflow-hidden border-4 border-gray-200" id="photoContainer">
                    <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                             alt="Profile Photo" class="w-full h-full object-cover" id="currentPhoto">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white font-bold text-2xl" id="initialsDisplay">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_photo">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Photo</label>
                        
                        <!-- Custom File Input -->
                        <div class="relative">
                            <input type="file" name="profile_photo" accept="image/*" id="photoInput" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div class="w-full border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-green-400 hover:bg-green-50 transition-all duration-300 cursor-pointer group" id="fileInputArea">
                                <div class="space-y-2">
                                    <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center group-hover:bg-green-100 transition-colors">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 group-hover:text-green-500 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700 group-hover:text-green-700">
                                            <span class="text-green-600">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, or GIF. Max 2MB.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Info Display -->
                        <div id="fileInfo" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-image text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-800" id="fileName"></p>
                                    <p class="text-xs text-green-600" id="fileSize"></p>
                                </div>
                                <button type="button" onclick="clearFileSelection()" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-700 transform transition-all hover:scale-105 font-medium">
                            <i class="fas fa-upload mr-2"></i>Update Photo
                        </button>
                        <button type="button" onclick="clearFileSelection()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Security</h3>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-lock text-green-500"></i>
                    <span class="text-sm text-gray-500">Password</span>
                </div>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                    <input type="password" name="current_password" required
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                    <input type="password" name="new_password" required minlength="6"
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg hover:from-red-600 hover:to-red-700 transform transition-all hover:scale-105 font-medium">
                    <i class="fas fa-key mr-2"></i>Change Password
                </button>
            </form>
        </div>
        
        <!-- Account Information -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Account Info</h3>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-info-circle text-green-500"></i>
                    <span class="text-sm text-gray-500">Details</span>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-600">User ID</span>
                    <span class="text-sm text-gray-900">#<?php echo $user['id']; ?></span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-600">Role</span>
                    <span class="text-sm text-gray-900 capitalize"><?php echo str_replace('_', ' ', $user['role']); ?></span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-600">Status</span>
                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-semibold bg-green-100 text-green-800">
                        Active
                    </span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-600">Member Since</span>
                    <span class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                </div>
                
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm font-medium text-gray-600">Last Updated</span>
                    <span class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Custom JavaScript for Settings -->
<script>
// Add enhanced styles for file input
const style = document.createElement('style');
style.textContent = `
    /* Enhanced File Input Animations */
    #fileInputArea {
        transition: all 0.3s ease;
    }
    
    #fileInputArea:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.15);
    }
    
    #fileInputArea.drag-over {
        border-color: #10b981;
        background-color: #ecfdf5;
        transform: scale(1.02);
    }
    
    .file-upload-icon {
        transition: all 0.3s ease;
    }
    
    #fileInputArea:hover .file-upload-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .file-info-animation {
        animation: slideInUp 0.3s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .error-shake {
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.querySelector('input[name="new_password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    function validatePasswordMatch() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Enhanced Photo Input Functionality
    const photoInput = document.getElementById('photoInput');
    const photoContainer = document.getElementById('photoContainer');
    const fileInputArea = document.getElementById('fileInputArea');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    
    // File input change handler
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileSelection(file);
        }
    });
    
    // Enhanced drag and drop functionality
    fileInputArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    
    fileInputArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
    
    fileInputArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
        }
    });
    
    // Handle file selection
    function handleFileSelection(file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showFileError('Please select a valid image file (JPG, PNG, or GIF).');
            return;
        }
        
        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            showFileError('File size must be less than 2MB.');
            return;
        }
        
        // Show file info
        showFileInfo(file);
        
        // Show loading indicator in photo container
        photoContainer.innerHTML = `
            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                <i class="fas fa-spinner fa-spin text-gray-500 text-xl"></i>
            </div>
        `;
        
        // Read and preview the file
        const reader = new FileReader();
        reader.onload = function(e) {
            photoContainer.innerHTML = `
                <img src="${e.target.result}" 
                     alt="Profile Photo Preview" 
                     class="w-full h-full object-cover"
                     id="photoPreview">
            `;
        };
        reader.readAsDataURL(file);
    }
    
    // Show file information
    function showFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
        fileInfo.classList.add('file-info-animation');
        fileInputArea.classList.add('hidden');
    }
    
    // Show file error
    function showFileError(message) {
        // Create a temporary error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mt-3 p-3 bg-red-50 border border-red-200 rounded-lg error-shake';
        errorDiv.innerHTML = `
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
                <p class="text-sm text-red-800">${message}</p>
            </div>
        `;
        
        // Insert after file input area
        fileInputArea.parentNode.insertBefore(errorDiv, fileInputArea.nextSibling);
        
        // Remove error after 3 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 3000);
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Clear file selection
    window.clearFileSelection = function() {
        photoInput.value = '';
        fileInfo.classList.add('hidden');
        fileInfo.classList.remove('file-info-animation');
        fileInputArea.classList.remove('hidden');
        clearPhotoPreview();
    };
    
    // Clear photo preview function
    window.clearPhotoPreview = function() {
        const photoInput = document.querySelector('input[name="profile_photo"]');
        const photoContainer = document.getElementById('photoContainer');
        
        // Clear the file input
        photoInput.value = '';
        
        // Restore to original state (either current photo or initials)
        <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
            photoContainer.innerHTML = `
                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                     alt="Profile Photo" 
                     class="w-full h-full object-cover" 
                     id="currentPhoto">
            `;
        <?php else: ?>
            photoContainer.innerHTML = `
                <div class="w-full h-full bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white font-bold text-2xl" id="initialsDisplay">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
            `;
        <?php endif; ?>
    };
    
    // Form submission feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    });
});
</script>
