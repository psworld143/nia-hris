<?php
/**
 * NIA-HRIS Complete HR System Setup
 * Table structures copied from SEAIT reference (standalone system)
 * For EMPLOYEES only (no faculty)
 */

require_once 'config/database.php';

echo "<h1>NIA HRIS - Complete HR System Setup</h1>";
echo "<p>Constructing all HR tables from SEAIT database...</p><hr>";

$tables_created = 0;
$errors = [];

// ============================================================
// 1. EMPLOYEE DETAILS TABLE
// ============================================================
echo "<h3>1. Employee Details System</h3>";

$employee_details = "CREATE TABLE IF NOT EXISTS `employee_details` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL UNIQUE,
    `profile_photo` varchar(255) DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `place_of_birth` varchar(255) DEFAULT NULL,
    `gender` enum('Male','Female','Other') DEFAULT NULL,
    `civil_status` enum('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL,
    `nationality` varchar(100) DEFAULT 'Filipino',
    `religion` varchar(100) DEFAULT NULL,
    `blood_type` varchar(5) DEFAULT NULL,
    `height` decimal(5,2) DEFAULT NULL,
    `weight` decimal(5,2) DEFAULT NULL,
    `present_address` text,
    `permanent_address` text,
    `mobile_number` varchar(20) DEFAULT NULL,
    `landline_number` varchar(20) DEFAULT NULL,
    `personal_email` varchar(100) DEFAULT NULL,
    `emergency_contact_name` varchar(255) DEFAULT NULL,
    `emergency_contact_relationship` varchar(100) DEFAULT NULL,
    `emergency_contact_number` varchar(20) DEFAULT NULL,
    `emergency_contact_address` text,
    `spouse_name` varchar(255) DEFAULT NULL,
    `spouse_occupation` varchar(255) DEFAULT NULL,
    `spouse_employer` varchar(255) DEFAULT NULL,
    `spouse_contact` varchar(20) DEFAULT NULL,
    `father_name` varchar(255) DEFAULT NULL,
    `father_occupation` varchar(255) DEFAULT NULL,
    `mother_name` varchar(255) DEFAULT NULL,
    `mother_occupation` varchar(255) DEFAULT NULL,
    `number_of_children` int(11) DEFAULT 0,
    `sss_number` varchar(20) DEFAULT NULL,
    `tin_number` varchar(20) DEFAULT NULL,
    `philhealth_number` varchar(20) DEFAULT NULL,
    `pagibig_number` varchar(20) DEFAULT NULL,
    `gsis_number` varchar(20) DEFAULT NULL,
    `prc_license_number` varchar(50) DEFAULT NULL,
    `prc_license_expiry` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_details)) {
    echo "✓ employee_details table created<br>";
    $tables_created++;
} else {
    $errors[] = "employee_details: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// 2. EMPLOYEE BENEFITS SYSTEM
// ============================================================
echo "<h3>2. Employee Benefits System</h3>";

$employee_benefits = "CREATE TABLE IF NOT EXISTS `employee_benefits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `benefit_type` varchar(100) NOT NULL,
    `benefit_name` varchar(255) NOT NULL,
    `benefit_description` text,
    `benefit_amount` decimal(10,2) DEFAULT NULL,
    `benefit_percentage` decimal(5,2) DEFAULT NULL,
    `start_date` date NOT NULL,
    `end_date` date DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `provider` varchar(255) DEFAULT NULL,
    `policy_number` varchar(100) DEFAULT NULL,
    `coverage_details` text,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `benefit_type` (`benefit_type`),
    KEY `is_active` (`is_active`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_benefits)) {
    echo "✓ employee_benefits table created<br>";
    $tables_created++;
} else {
    $errors[] = "employee_benefits: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// 3. EMPLOYEE SALARY HISTORY
// ============================================================
echo "<h3>3. Salary Management System</h3>";

$employee_salary_history = "CREATE TABLE IF NOT EXISTS `employee_salary_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `previous_salary` decimal(10,2) DEFAULT NULL,
    `new_salary` decimal(10,2) NOT NULL,
    `salary_grade` varchar(20) DEFAULT NULL,
    `step_increment` int(11) DEFAULT NULL,
    `change_type` enum('initial','increment','promotion','adjustment','demotion') NOT NULL,
    `change_reason` text,
    `effective_date` date NOT NULL,
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `supporting_document` varchar(255) DEFAULT NULL,
    `remarks` text,
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `effective_date` (`effective_date`),
    KEY `change_type` (`change_type`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_salary_history)) {
    echo "✓ employee_salary_history table created<br>";
    $tables_created++;
} else {
    $errors[] = "employee_salary_history: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$salary_audit_log = "CREATE TABLE IF NOT EXISTS `salary_audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `action_type` enum('view','update','delete','export') NOT NULL,
    `field_changed` varchar(100) DEFAULT NULL,
    `old_value` text,
    `new_value` text,
    `change_reason` text,
    `performed_by` int(11) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `performed_by` (`performed_by`),
    KEY `action_type` (`action_type`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $salary_audit_log)) {
    echo "✓ salary_audit_log table created<br>";
    $tables_created++;
} else {
    $errors[] = "salary_audit_log: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// 4. LEAVE MANAGEMENT ENHANCED
// ============================================================
echo "<h3>4. Enhanced Leave Management System</h3>";

$employee_leave_allowances = "CREATE TABLE IF NOT EXISTS `employee_leave_allowances` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `leave_type_id` int(11) NOT NULL,
    `year` int(11) NOT NULL,
    `total_days` decimal(5,2) NOT NULL DEFAULT 0.00,
    `used_days` decimal(5,2) NOT NULL DEFAULT 0.00,
    `remaining_days` decimal(5,2) NOT NULL DEFAULT 0.00,
    `carried_forward` decimal(5,2) DEFAULT 0.00,
    `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `employee_leave_year` (`employee_id`,`leave_type_id`,`year`),
    KEY `leave_type_id` (`leave_type_id`),
    KEY `year` (`year`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_leave_allowances)) {
    echo "✓ employee_leave_allowances table created<br>";
    $tables_created++;
} else {
    $errors[] = "employee_leave_allowances: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$leave_accumulation_history = "CREATE TABLE IF NOT EXISTS `leave_accumulation_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `leave_type_id` int(11) NOT NULL,
    `year` int(11) NOT NULL,
    `month` int(11) NOT NULL,
    `accumulated_days` decimal(5,2) NOT NULL,
    `accumulation_date` date NOT NULL,
    `remarks` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `leave_type_id` (`leave_type_id`),
    KEY `year_month` (`year`,`month`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $leave_accumulation_history)) {
    echo "✓ leave_accumulation_history table created<br>";
    $tables_created++;
} else {
    $errors[] = "leave_accumulation_history: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$leave_notifications = "CREATE TABLE IF NOT EXISTS `leave_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `leave_request_id` int(11) DEFAULT NULL,
    `notification_type` enum('approval','rejection','reminder','expiry','balance_low') NOT NULL,
    `message` text NOT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `read_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `leave_request_id` (`leave_request_id`),
    KEY `is_read` (`is_read`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_request_id`) REFERENCES `employee_leave_requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $leave_notifications)) {
    echo "✓ leave_notifications table created<br>";
    $tables_created++;
} else {
    $errors[] = "leave_notifications: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// 5. TRAINING & DEVELOPMENT SYSTEM
// ============================================================
echo "<h3>5. Training & Development System</h3>";

$training_categories = "CREATE TABLE IF NOT EXISTS `training_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_name` varchar(255) NOT NULL,
    `description` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $training_categories)) {
    echo "✓ training_categories table created<br>";
    $tables_created++;
    
    // Insert default categories
    $default_categories = [
        'Technical Skills',
        'Leadership & Management',
        'Communication Skills',
        'Safety & Compliance',
        'Professional Development',
        'Software & Technology'
    ];
    
    foreach ($default_categories as $cat) {
        mysqli_query($conn, "INSERT IGNORE INTO training_categories (category_name) VALUES ('$cat')");
    }
    echo "  ✓ Default categories inserted<br>";
} else {
    $errors[] = "training_categories: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$employee_training = "CREATE TABLE IF NOT EXISTS `employee_training` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `training_program_id` int(11) DEFAULT NULL,
    `training_title` varchar(255) NOT NULL,
    `training_category_id` int(11) DEFAULT NULL,
    `training_provider` varchar(255) DEFAULT NULL,
    `training_type` enum('internal','external','online','certification') DEFAULT 'internal',
    `start_date` date NOT NULL,
    `end_date` date DEFAULT NULL,
    `duration_hours` decimal(5,2) DEFAULT NULL,
    `location` varchar(255) DEFAULT NULL,
    `cost` decimal(10,2) DEFAULT 0.00,
    `status` enum('registered','ongoing','completed','cancelled') DEFAULT 'registered',
    `completion_date` date DEFAULT NULL,
    `certificate_number` varchar(100) DEFAULT NULL,
    `certificate_file` varchar(255) DEFAULT NULL,
    `rating` decimal(3,2) DEFAULT NULL,
    `feedback` text,
    `skills_acquired` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `training_program_id` (`training_program_id`),
    KEY `training_category_id` (`training_category_id`),
    KEY `status` (`status`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`training_program_id`) REFERENCES `training_programs`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`training_category_id`) REFERENCES `training_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_training)) {
    echo "✓ employee_training table created<br>";
    $tables_created++;
} else {
    $errors[] = "employee_training: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$training_materials = "CREATE TABLE IF NOT EXISTS `training_materials` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `training_program_id` int(11) NOT NULL,
    `material_title` varchar(255) NOT NULL,
    `material_type` enum('document','video','presentation','link','other') NOT NULL,
    `file_path` varchar(255) DEFAULT NULL,
    `file_url` varchar(500) DEFAULT NULL,
    `description` text,
    `uploaded_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `training_program_id` (`training_program_id`),
    KEY `uploaded_by` (`uploaded_by`),
    FOREIGN KEY (`training_program_id`) REFERENCES `training_programs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $training_materials)) {
    echo "✓ training_materials table created<br>";
    $tables_created++;
} else {
    $errors[] = "training_materials: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// 6. PERFORMANCE REVIEW SYSTEM
// ============================================================
echo "<h3>6. Performance Review System</h3>";

$performance_review_categories = "CREATE TABLE IF NOT EXISTS `performance_review_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_name` varchar(255) NOT NULL,
    `description` text,
    `weight_percentage` decimal(5,2) DEFAULT 0.00,
    `display_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $performance_review_categories)) {
    echo "✓ performance_review_categories table created<br>";
    $tables_created++;
    
    // Insert default categories
    $review_categories = [
        ['Job Knowledge', 'Understanding of job responsibilities and technical skills', 20, 1],
        ['Quality of Work', 'Accuracy, thoroughness, and excellence', 25, 2],
        ['Productivity', 'Efficiency and timeliness in completing tasks', 20, 3],
        ['Communication', 'Verbal and written communication effectiveness', 15, 4],
        ['Teamwork', 'Collaboration and interpersonal skills', 10, 5],
        ['Initiative', 'Proactive behavior and problem-solving', 10, 6]
    ];
    
    foreach ($review_categories as $cat) {
        $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO performance_review_categories (category_name, description, weight_percentage, display_order) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdi", $cat[0], $cat[1], $cat[2], $cat[3]);
        mysqli_stmt_execute($stmt);
    }
    echo "  ✓ Default review categories inserted<br>";
} else {
    $errors[] = "performance_review_categories: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$performance_review_criteria = "CREATE TABLE IF NOT EXISTS `performance_review_criteria` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `criteria_name` varchar(255) NOT NULL,
    `description` text,
    `max_score` int(11) DEFAULT 5,
    `display_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    FOREIGN KEY (`category_id`) REFERENCES `performance_review_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $performance_review_criteria)) {
    echo "✓ performance_review_criteria table created<br>";
    $tables_created++;
} else {
    $errors[] = "performance_review_criteria: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$performance_review_scores = "CREATE TABLE IF NOT EXISTS `performance_review_scores` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `performance_review_id` int(11) NOT NULL,
    `criteria_id` int(11) NOT NULL,
    `score` decimal(5,2) NOT NULL,
    `comments` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `performance_review_id` (`performance_review_id`),
    KEY `criteria_id` (`criteria_id`),
    FOREIGN KEY (`performance_review_id`) REFERENCES `performance_reviews`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`criteria_id`) REFERENCES `performance_review_criteria`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $performance_review_scores)) {
    echo "✓ performance_review_scores table created<br>";
    $tables_created++;
} else {
    $errors[] = "performance_review_scores: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

$performance_review_goals = "CREATE TABLE IF NOT EXISTS `performance_review_goals` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `performance_review_id` int(11) NOT NULL,
    `goal_description` text NOT NULL,
    `target_date` date DEFAULT NULL,
    `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    `completion_date` date DEFAULT NULL,
    `completion_notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `performance_review_id` (`performance_review_id`),
    FOREIGN KEY (`performance_review_id`) REFERENCES `performance_reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $performance_review_goals)) {
    echo "✓ performance_review_goals table created<br>";
    $tables_created++;
} else {
    $errors[] = "performance_review_goals: " . mysqli_error($conn);
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// ============================================================
// SUMMARY
// ============================================================
echo "<hr><h2>Setup Complete!</h2>";
echo "<p><strong>✓ Successfully created $tables_created tables</strong></p>";

if (!empty($errors)) {
    echo "<h3 style='color: #c00;'>Errors Encountered:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<h3>Tables Created:</h3>";
echo "<ul>";
echo "<li><strong>Employee Details:</strong> employee_details</li>";
echo "<li><strong>Benefits:</strong> employee_benefits</li>";
echo "<li><strong>Salary:</strong> employee_salary_history, salary_audit_log</li>";
echo "<li><strong>Leave:</strong> employee_leave_allowances, leave_accumulation_history, leave_notifications</li>";
echo "<li><strong>Training:</strong> training_categories, employee_training, training_materials</li>";
echo "<li><strong>Performance:</strong> performance_review_categories, performance_review_criteria, performance_review_scores, performance_review_goals</li>";
echo "</ul>";

echo "<p><a href='admin-employee.php' class='btn'>Go to Employee Management</a> | ";
echo "<a href='index.php' class='btn'>Go to Dashboard</a></p>";
?>

