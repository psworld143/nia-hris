<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && (in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) || $_SESSION['role'] === 'hr_manager' || $_SESSION['role'] === 'admin')) {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        // Check if user exists
        $query = "SELECT id, username, password, first_name, last_name, role, status FROM users WHERE username = ? AND status = 'active'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                // Check if user has appropriate role
                if (in_array($user['role'], ['human_resource', 'hr_manager', 'admin'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect to dashboard
                    header('Location: index.php');
                    exit();
                } else {
                    $error_message = 'You do not have permission to access this system.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            $error_message = 'Invalid username or password.';
        }
    } else {
        $error_message = 'Please enter both username and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIA-HRIS Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">NIA-HRIS</h1>
                <p class="text-gray-600">Human Resource Information System</p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="">
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter your username">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter your password">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> NIA-HRIS. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
