<?php
// This file contains the shared responsive header for all NIA-HRIS pages
// Include this file at the top of each page after session_start() and database connection

// Include functions if not already included
if (!function_exists('get_organization_logo')) {
    require_once 'includes/functions.php';
}

// Get organization logo and abbreviation from database
$organization_logo = get_organization_logo($conn);
$organization_abbreviation = get_organization_abbreviation($conn);

// Get user profile photo
$user_photo = null;
if (isset($_SESSION['user_id'])) {
    $photo_query = "SELECT profile_photo FROM users WHERE id = ?";
    $photo_stmt = mysqli_prepare($conn, $photo_query);
    mysqli_stmt_bind_param($photo_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($photo_stmt);
    $photo_result = mysqli_stmt_get_result($photo_stmt);
    $photo_row = mysqli_fetch_assoc($photo_result);
    $user_photo = $photo_row ? $photo_row['profile_photo'] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - NIA-HRIS' : 'NIA-HRIS'; ?></title>
    
    <!-- Favicon Configuration -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <link rel="shortcut icon" href="assets/images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nia-blue': '#1E40AF',
                        'nia-dark': '#1F2937',
                        'nia-light': '#F8FAFC',
                        'hr-secondary': '#374151'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- jQuery and jGrowl -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jgrowl/1.4.8/jquery.jgrowl.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jgrowl/1.4.8/jquery.jgrowl.min.css">
    <style>
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sidebar-overlay {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .sidebar-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        @media (min-width: 1024px) {
            .sidebar {
                transform: translateX(0);
            }
        }

        /* Sidebar profile - fixed */
        .sidebar-profile {
            flex-shrink: 0;
            padding: 1rem;
            position: relative;
        }

        /* Add shadow below profile when content is scrolled */
        .sidebar-profile::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), transparent);
            pointer-events: none;
        }

        /* Sidebar scrollable content */
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 1rem 1rem 1rem;
        }

        .sidebar-content::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Sidebar header and footer fixed */
        .sidebar-header,
        .sidebar-footer {
            flex-shrink: 0;
        }

        /* Prevent horizontal overflow */
        body, html {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Ensure main content doesn't overflow */
        .flex-1 {
            min-width: 0;
            max-width: 100%;
        }

        /* Custom jGrowl Success Theme */
        .jGrowl-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .jGrowl-success .jGrowl-header {
            color: #16a34a;
            font-weight: 600;
            font-size: 14px;
        }
        
        .jGrowl-success .jGrowl-message {
            color: #16a34a;
            font-size: 13px;
        }
        
        .jGrowl-success .jGrowl-close {
            color: #16a34a;
        }
        
        .jGrowl-success .jGrowl-close:hover {
            color: #15803d;
        }
        
        /* Custom jGrowl Error Theme */
        .jGrowl-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .jGrowl-error .jGrowl-header {
            color: #dc2626;
            font-weight: 600;
            font-size: 14px;
        }
        
        .jGrowl-error .jGrowl-message {
            color: #dc2626;
            font-size: 13px;
        }
        
        .jGrowl-error .jGrowl-close {
            color: #dc2626;
        }
        
        .jGrowl-error .jGrowl-close:hover {
            color: #b91c1c;
        }
        
        /* jGrowl Container Styling */
        #jGrowl {
            z-index: 9999;
        }
        
        .jGrowl-notification {
            font-family: 'Poppins', sans-serif;
        }

        /* Custom animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        /* Sidebar open/close animations */
        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        /* Smooth transitions for all interactive elements */
        .sidebar a {
            position: relative;
            overflow: hidden;
        }

        .sidebar a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .sidebar a:hover::before {
            left: 100%;
        }

        /* Active state animations */
        .sidebar a.bg-green-600 {
            animation: activePulse 2s infinite;
        }

        @keyframes activePulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(22, 163, 74, 0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar Overlay -->
        <div id="sidebarOverlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden transition-opacity duration-300 ease-in-out opacity-0 pointer-events-none" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <div id="sidebar" class="sidebar fixed top-0 left-0 h-full w-64 bg-nia-dark z-50 lg:relative lg:z-auto transform transition-transform duration-300 ease-in-out -translate-x-full lg:translate-x-0">
            <!-- Sidebar Header -->
            <div class="sidebar-header flex items-center justify-center p-4 border-b border-gray-700 bg-gradient-to-r from-gray-800 to-gray-700">
                <div class="flex items-center transform transition-transform duration-200 hover:scale-105">
                    <img src="assets/images/logo.png" alt="NIA Logo" class="h-10 w-10 mr-3 transition-all duration-200 hover:rotate-12 hover:scale-110 rounded-full ring-2 ring-green-400 ring-opacity-50">
                    <div class="flex flex-col">
                        <span class="text-white font-bold text-sm">NIA HRIS</span>
                        <span class="text-gray-300 text-xs">Human Resources</span>
                    </div>
                </div>
            </div>

            <!-- User Profile Section - Fixed -->
            <div class="sidebar-profile border-b border-gray-700">
                <div class="p-4 bg-gray-800 rounded-lg transform transition-all duration-300 hover:bg-gray-700 hover:scale-105 hover:shadow-lg">
                    <div class="flex flex-col items-center text-center">
                        <div class="h-20 w-20 rounded-full overflow-hidden bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center mb-3 transition-all duration-300 hover:from-green-600 hover:to-green-800 hover:scale-110 hover:shadow-xl ring-4 ring-green-400 ring-opacity-50">
                            <?php if (!empty($user_photo) && file_exists($user_photo)): ?>
                                <img src="<?php echo htmlspecialchars($user_photo); ?>" 
                                     alt="Profile Photo" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-white font-bold text-2xl"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="w-full">
                            <p class="text-white font-bold text-base mb-1"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                            <p class="text-gray-300 text-xs mb-2 bg-gray-700 px-3 py-1 rounded-full inline-block"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></p>
                            <div class="flex items-center justify-center mt-2">
                                <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                                <span class="text-green-400 text-xs font-semibold">Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu - Scrollable Content -->
            <div class="sidebar-content">
                <div class="space-y-6 pt-4">
                    <!-- Dashboard Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.1s;">
                        <a href="index.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                            <i class="fas fa-tachometer-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Dashboard
                        </a>
                    </div>

                    <!-- Employee Management Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.2s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Employee Management</h3>
                        <div class="space-y-1">
                            <a href="manage-departments.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'manage-departments.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-building mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Departments
                            </a>

                            <a href="admin-employee.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'admin-employee.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-user-plus mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Manage Employees
                            </a>
                        </div>
                    </div>

                    <!-- Leave Management Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.25s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Leave Management</h3>
                        <div class="space-y-1">
                            <a href="leave-management.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'leave-management.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-calendar-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Leave Requests
                            </a>

                            <a href="leave-allowance-management.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'leave-allowance-management.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-calendar-check mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Leave Allowance
                            </a>

                            <a href="leave-reports.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'leave-reports.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-file-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Leave Reports
                            </a>
                        </div>
                    </div>

                    <!-- Salaries and Wages Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.3s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Salaries and Wages</h3>
                        <div class="space-y-1">
                            <a href="salary-structures.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'salary-structures.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-sitemap mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Salary Structures
                            </a>

                            <a href="auto-increment-management.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'auto-increment-management.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-robot mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Auto Increment
                            </a>
                        </div>
                    </div>

                    <!-- Regularization Management Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.25s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Regularization</h3>
                        <div class="space-y-1">
                            <a href="regularization-criteria.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'regularization-criteria.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-list-ul mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Regularization Criteria
                            </a>

                            <a href="manage-regularization.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'manage-regularization.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-clipboard-check mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Regularization List
                            </a>
                        </div>
                        <br>
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Education</h3>
                        <div class="space-y-1">
                            <a href="manage-degrees.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'manage-degrees.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-graduation-cap mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Manage Degrees
                            </a>
                        </div>
                    </div>

                    <!-- Employee Benefits Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.3s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Employee Benefits</h3>
                        <div class="space-y-1">
                            <a href="government-benefits.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'government-benefits.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-shield-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Government Benefits
                            </a>
                            
                            <a href="payroll-management.php" class="flex items-center <?php echo in_array(basename($_SERVER['PHP_SELF']), ['payroll-management.php', 'payroll-process.php', 'payroll-view.php', 'payroll-reports.php']) ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-money-check-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Payroll Management
                            </a>
                        </div>
                    </div>

                    <!-- Performance Management Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.35s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">Performance</h3>
                        <div class="space-y-1">
                            <a href="performance-reviews.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'performance-reviews.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-star mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Performance Reviews
                            </a>

                            <a href="training-programs.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'training-programs.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-graduation-cap mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Training Programs
                            </a>
                        </div>
                    </div>

                    <!-- System Settings Section -->
                    <div class="animate-fadeInUp" style="animation-delay: 0.4s;">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 px-3">System</h3>
                        <div class="space-y-1">
                            <a href="settings.php" class="flex items-center <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md">
                                <i class="fas fa-cog mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer p-4 border-t border-gray-700 bg-gradient-to-r from-gray-800 to-gray-700">
                <a href="logout.php" class="flex items-center bg-red-600 text-white hover:bg-red-700 px-3 py-2 rounded-lg transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-sign-out-alt mr-3 w-5 text-center transition-transform duration-200 hover:rotate-12"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex justify-between items-center py-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center">
                        <!-- Mobile Sidebar Toggle -->
                        <button onclick="toggleSidebar()" class="lg:hidden mr-3 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-xl"></i>
                        </button>

                        <div>
                            <h1 class="text-lg sm:text-xl font-bold text-nia-dark">NIA Human Resource Management</h1>
                            <p class="text-xs sm:text-sm text-gray-600">Employee & Recruitment Management System</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-full overflow-hidden bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center ring-2 ring-green-400 ring-opacity-50">
                            <?php if (!empty($user_photo) && file_exists($user_photo)): ?>
                                <img src="<?php echo htmlspecialchars($user_photo); ?>" 
                                     alt="Profile Photo" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-white text-sm sm:text-base font-bold"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="logout.php" class="text-gray-600 hover:text-red-600 p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Logout">
                            <i class="fas fa-sign-out-alt text-xl"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Container -->
            <main class="flex-1 py-4 sm:py-6 px-4 sm:px-6 lg:px-8 overflow-auto">
                <div class="px-0 sm:px-0">

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar && overlay) {
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        } else {
            sidebar.classList.add('open');
            overlay.classList.add('open');
        }
    }
}

// Initialize sidebar behavior
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarLinks = document.querySelectorAll('#sidebar a:not([href^="#"])'); // Exclude anchor links
    const sidebarContent = document.querySelector('.sidebar-content');

    // Store scroll position in sessionStorage
    function saveScrollPosition() {
        if (sidebarContent) {
            sessionStorage.setItem('niaHrSidebarScrollPosition', sidebarContent.scrollTop);
        }
    }

    // Restore scroll position from sessionStorage
    function restoreScrollPosition() {
        if (sidebarContent) {
            const savedPosition = sessionStorage.getItem('niaHrSidebarScrollPosition');
            if (savedPosition !== null) {
                setTimeout(() => {
                    sidebarContent.scrollTop = parseInt(savedPosition);
                }, 100);
            }
        }
    }

    // Save scroll position when scrolling
    if (sidebarContent) {
        sidebarContent.addEventListener('scroll', function() {
            saveScrollPosition();
        });
    }

    // Restore scroll position on page load
    restoreScrollPosition();

    // Ensure sidebar is in correct state on load
    if (sidebar) {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
        }
    }

    // Handle link clicks - NO MOVEMENT
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Save current scroll position before navigation
            saveScrollPosition();

            // Prevent any default behavior that might cause movement
            e.preventDefault();

            // Get the href and navigate immediately without any delays or animations
            const href = link.getAttribute('href');
            if (href) {
                // Navigate immediately - no delays, no animations, no sidebar movement
                window.location.href = href;
            }
        });
    });

    // Handle overlay clicks
    if (overlay) {
        overlay.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.innerWidth < 1024) {
                toggleSidebar();
            }
        });
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (window.innerWidth >= 1024) {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
    }
});

// Prevent unwanted sidebar interactions on desktop
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', (e) => {
            // Prevent event bubbling on desktop
            if (window.innerWidth >= 1024) {
                e.stopPropagation();
            }
        });
    }
});

// Prevent any zooming or scaling issues
document.addEventListener('DOMContentLoaded', function() {
    // Ensure viewport meta tag is properly set
    const viewport = document.querySelector('meta[name="viewport"]');
    if (!viewport) {
        const meta = document.createElement('meta');
        meta.name = 'viewport';
        meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        document.head.appendChild(meta);
    } else {
        viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
    }
});
</script>