<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager', 'nurse'])) {
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
                // Check if user has appropriate role (all valid roles)
                if (in_array($user['role'], ['super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse'])) {
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #f3f4f6;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            animation: float 15s infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }
        }
        
        .login-container {
            position: relative;
            z-index: 2;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .logo-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus + .input-icon {
            color: #3b82f6;
        }
        
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .error-shake {
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .toggle-password {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #3b82f6;
        }
        
        .checkbox-custom {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .checkbox-custom:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .checkbox-custom:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .loading-spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 2px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .success-message {
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <!-- Animated Background Particles -->
    <div class="particles">
        <script>
            for(let i = 0; i < 30; i++) {
                let particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                document.querySelector('.particles').appendChild(particle);
            }
        </script>
    </div>

    <div class="login-container max-w-md w-full mx-4 fade-in">
        <div class="glass-card rounded-2xl shadow-2xl p-8 md:p-10">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="mx-auto w-20 h-20 bg-gradient-to-br from-blue-600 to-purple-600 rounded-full flex items-center justify-center mb-4 logo-pulse shadow-lg">
                    <i class="fas fa-building text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
                <p class="text-gray-600 font-medium">NIA Human Resource Information System</p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="" id="loginForm">
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-6 error-shake flex items-start">
                        <i class="fas fa-exclamation-circle mt-0.5 mr-3 text-lg"></i>
                        <div>
                            <p class="font-semibold">Login Failed</p>
                            <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                        Username
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required 
                               class="input-field w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your username"
                               autocomplete="username">
                        <i class="input-icon fas fa-user"></i>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               class="input-field w-full pl-11 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your password"
                               autocomplete="current-password">
                        <i class="input-icon fas fa-lock"></i>
                        <i class="toggle-password fas fa-eye absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400" id="togglePassword"></i>
                    </div>
                </div>

                <div class="mb-6 flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" class="checkbox-custom" id="rememberMe">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" 
                        id="loginBtn"
                        class="btn-login w-full text-white py-3 px-6 rounded-lg font-semibold text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span id="btnText">Sign In</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="mt-8 flex items-center justify-center">
                <div class="border-t border-gray-300 flex-grow"></div>
                <span class="px-4 text-sm text-gray-500">Secure Login</span>
                <div class="border-t border-gray-300 flex-grow"></div>
            </div>

            <!-- Additional Info -->
            <div class="mt-6 text-center">
                <div class="flex items-center justify-center text-sm text-gray-600 space-x-4">
                    <span class="flex items-center">
                        <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                        Encrypted
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-lock text-blue-600 mr-2"></i>
                        Secure
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-xs text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> NIA-HRIS. All rights reserved.</p>
                <p class="mt-1">Version 2.0 - Human Resource Management</p>
            </div>
        </div>
    </div>

    <script>
        // Password Toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Form Submission with Loading State
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = document.getElementById('btnText');
        
        loginForm.addEventListener('submit', function(e) {
            loginBtn.disabled = true;
            loginBtn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.innerHTML = '<span class="loading-spinner"></span>Signing in...';
        });

        // Remember Me Functionality
        const rememberMe = document.getElementById('rememberMe');
        const usernameInput = document.getElementById('username');
        
        // Load saved username if exists
        if(localStorage.getItem('rememberedUsername')) {
            usernameInput.value = localStorage.getItem('rememberedUsername');
            rememberMe.checked = true;
        }
        
        loginForm.addEventListener('submit', function() {
            if(rememberMe.checked) {
                localStorage.setItem('rememberedUsername', usernameInput.value);
            } else {
                localStorage.removeItem('rememberedUsername');
            }
        });

        // Keyboard Shortcut (Enter to submit)
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
                e.preventDefault();
                loginForm.submit();
            }
        });

        // Add focus to username field on page load
        window.addEventListener('load', function() {
            if(!usernameInput.value) {
                usernameInput.focus();
            } else {
                passwordInput.focus();
            }
        });

        // Add smooth validation feedback
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.classList.add('border-red-500', 'error-shake');
                setTimeout(() => {
                    this.classList.remove('error-shake');
                }, 500);
            });
            
            input.addEventListener('input', function() {
                if(this.validity.valid) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                }
            });
        });
    </script>
</body>
</html>

