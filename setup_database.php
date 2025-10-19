<?php
/**
 * NIA-HRIS Database Setup Script
 * This script creates the necessary database tables for the standalone HR system
 */

require_once 'config/database.php';

// Check if database exists, if not create it
$create_db_query = "CREATE DATABASE IF NOT EXISTS `nia_hris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
mysqli_query($conn, $create_db_query);

// Select the database
mysqli_select_db($conn, 'nia_hris');

// Create tables
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `first_name` varchar(100) NOT NULL,
        `last_name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `role` enum('admin','human_resource','hr_manager') NOT NULL DEFAULT 'human_resource',
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Settings table
    "CREATE TABLE IF NOT EXISTS `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(100) NOT NULL UNIQUE,
        `setting_value` text,
        `description` varchar(255),
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Departments table (matching SEAIT structure)
    "CREATE TABLE IF NOT EXISTS `departments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `code` varchar(10) NOT NULL,
        `description` text,
        `icon` varchar(100) DEFAULT NULL,
        `color_theme` varchar(7) DEFAULT '#FF6B35',
        `sort_order` int(11) DEFAULT 0,
        `created_by` int(11) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Employees table
    "CREATE TABLE IF NOT EXISTS `employees` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` varchar(20) NOT NULL UNIQUE,
        `first_name` varchar(100) NOT NULL,
        `last_name` varchar(100) NOT NULL,
        `middle_name` varchar(100),
        `email` varchar(100) NOT NULL UNIQUE,
        `phone` varchar(20),
        `department_id` int(11),
        `position` varchar(100),
        `employment_status` enum('regular','contractual','part-time','probationary') NOT NULL DEFAULT 'probationary',
        `hire_date` date,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Activity log table
    "CREATE TABLE IF NOT EXISTS `activity_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11),
        `action` varchar(100) NOT NULL,
        `description` text NOT NULL,
        `ip_address` varchar(45),
        `user_agent` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",


    // Employee leave requests table
    "CREATE TABLE IF NOT EXISTS `employee_leave_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `leave_type` varchar(50) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `days_requested` int(11) NOT NULL,
        `reason` text,
        `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
        `approved_by` int(11),
        `approved_at` timestamp NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Leave types table
    "CREATE TABLE IF NOT EXISTS `leave_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `max_days_per_year` int(11) DEFAULT NULL,
        `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Salary structures table
    "CREATE TABLE IF NOT EXISTS `salary_structures` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `position` varchar(100) NOT NULL,
        `grade_level` varchar(20),
        `base_salary` decimal(10,2) NOT NULL,
        `allowances` decimal(10,2) DEFAULT 0,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Employee salaries table
    "CREATE TABLE IF NOT EXISTS `employee_salaries` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `employee_type` enum('employee') NOT NULL DEFAULT 'employee',
        `current_salary` decimal(10,2) NOT NULL,
        `effective_date` date NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Increment types table
    "CREATE TABLE IF NOT EXISTS `increment_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `percentage` decimal(5,2) DEFAULT NULL,
        `fixed_amount` decimal(10,2) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Increment requests table
    "CREATE TABLE IF NOT EXISTS `increment_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `employee_type` enum('employee') NOT NULL DEFAULT 'employee',
        `increment_type_id` int(11) NOT NULL,
        `current_salary` decimal(10,2) NOT NULL,
        `proposed_salary` decimal(10,2) NOT NULL,
        `increment_amount` decimal(10,2) NOT NULL,
        `reason` text,
        `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
        `requested_by` int(11) NOT NULL,
        `approved_by` int(11),
        `approved_at` timestamp NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`increment_type_id`) REFERENCES `increment_types`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Training programs table
    "CREATE TABLE IF NOT EXISTS `training_programs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text,
        `start_date` date,
        `end_date` date,
        `location` varchar(255),
        `instructor` varchar(255),
        `max_participants` int(11),
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Performance reviews table
    "CREATE TABLE IF NOT EXISTS `performance_reviews` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `employee_type` enum('employee') NOT NULL DEFAULT 'employee',
        `review_period_start` date NOT NULL,
        `review_period_end` date NOT NULL,
        `reviewer_id` int(11) NOT NULL,
        `overall_rating` decimal(3,2),
        `comments` text,
        `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Execute table creation queries
foreach ($tables as $query) {
    if (mysqli_query($conn, $query)) {
        echo "✓ Table created successfully<br>";
    } else {
        echo "✗ Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Insert default settings
$settings = [
    ['organization_name', 'NIA', 'Organization Name'],
    ['organization_logo', '', 'Organization Logo Path'],
    ['site_title', 'NIA-HRIS', 'Site Title'],
    ['site_description', 'NIA Human Resource Information System', 'Site Description']
];

foreach ($settings as $setting) {
    $query = "INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $setting[0], $setting[1], $setting[2]);
    mysqli_stmt_execute($stmt);
}

// Insert default leave types
$leave_types = [
    ['Vacation Leave', 'Annual vacation leave', 15, 1],
    ['Sick Leave', 'Medical leave for illness', 15, 1],
    ['Emergency Leave', 'Emergency situations', 3, 1],
    ['Maternity Leave', 'Maternity leave for female employees', 105, 1],
    ['Paternity Leave', 'Paternity leave for male employees', 7, 1],
    ['Study Leave', 'Leave for educational purposes', 30, 1]
];

foreach ($leave_types as $leave_type) {
    $query = "INSERT IGNORE INTO leave_types (name, description, max_days_per_year, requires_approval) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssii", $leave_type[0], $leave_type[1], $leave_type[2], $leave_type[3]);
    mysqli_stmt_execute($stmt);
}

// Insert default increment types
$increment_types = [
    ['Regular Increment', 'Regular annual salary increment', 5.00, NULL],
    ['Performance Increment', 'Performance-based salary increment', 10.00, NULL],
    ['Promotion Increment', 'Salary increment due to promotion', NULL, 5000.00],
    ['Merit Increment', 'Merit-based salary increment', 7.50, NULL]
];

foreach ($increment_types as $increment_type) {
    $query = "INSERT IGNORE INTO increment_types (name, description, percentage, fixed_amount) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssdd", $increment_type[0], $increment_type[1], $increment_type[2], $increment_type[3]);
    mysqli_stmt_execute($stmt);
}

// Create default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$query = "INSERT IGNORE INTO users (username, password, first_name, last_name, email, role) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
$username = 'admin';
$first_name = 'System';
$last_name = 'Administrator';
$email = 'admin@nia-hris.com';
$role = 'admin';
mysqli_stmt_bind_param($stmt, "ssssss", $username, $admin_password, $first_name, $last_name, $email, $role);
mysqli_stmt_execute($stmt);

echo "<br><strong>Database setup completed!</strong><br>";
echo "Default admin credentials:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "<br><a href='login.php'>Go to Login Page</a>";
?>

