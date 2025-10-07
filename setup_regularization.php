<?php
/**
 * NIA-HRIS Regularization System Setup
 * Based on SEAIT database structure
 */

require_once 'config/database.php';

echo "<h2>Setting up Regularization System...</h2>";

// 1. Create regularization_status table
$regularization_status_table = "CREATE TABLE IF NOT EXISTS `regularization_status` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL UNIQUE,
    `description` text,
    `color` varchar(20) DEFAULT '#6B7280',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $regularization_status_table)) {
    echo "✓ regularization_status table created<br>";
} else {
    echo "✗ Error creating regularization_status: " . mysqli_error($conn) . "<br>";
}

// 2. Insert default regularization statuses
$statuses = [
    ['Probationary', 'Currently in probation period', '#F59E0B'],
    ['Under Review', 'Currently under review for regularization', '#3B82F6'],
    ['Regular', 'Successfully regularized', '#10B981'],
    ['Extended Probation', 'Probation period extended', '#F97316'],
    ['Terminated', 'Employment terminated', '#EF4444'],
    ['Pending Review', 'Awaiting review process', '#8B5CF6'],
    ['Resigned', 'Employee has resigned from position', '#6B7280']
];

foreach ($statuses as $status) {
    $query = "INSERT IGNORE INTO regularization_status (name, description, color) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $status[0], $status[1], $status[2]);
    mysqli_stmt_execute($stmt);
}
echo "✓ Regularization statuses inserted<br>";

// 3. Create employee_regularization table
$employee_regularization_table = "CREATE TABLE IF NOT EXISTS `employee_regularization` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL UNIQUE,
    `current_status_id` int(11) NOT NULL,
    `date_of_hire` date NOT NULL,
    `probation_start_date` date NOT NULL,
    `probation_end_date` date NOT NULL,
    `regularization_review_date` date DEFAULT NULL,
    `regularization_date` date DEFAULT NULL,
    `review_notes` text,
    `reviewed_by` int(11) DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    `next_review_date` date DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_current_status` (`current_status_id`),
    KEY `idx_probation_start` (`probation_start_date`),
    KEY `idx_regularization_review` (`regularization_review_date`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_status_id`) REFERENCES `regularization_status`(`id`),
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $employee_regularization_table)) {
    echo "✓ employee_regularization table created<br>";
} else {
    echo "✗ Error creating employee_regularization: " . mysqli_error($conn) . "<br>";
}

// 4. Create regularization_criteria table
$regularization_criteria_table = "CREATE TABLE IF NOT EXISTS `regularization_criteria` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `criteria_name` varchar(255) NOT NULL,
    `criteria_description` text,
    `minimum_months` int(11) DEFAULT 6,
    `performance_rating_min` decimal(3,2) DEFAULT 3.00,
    `attendance_percentage_min` decimal(5,2) DEFAULT 95.00,
    `disciplinary_issues_max` int(11) DEFAULT 0,
    `training_completion_required` tinyint(1) DEFAULT 0,
    `evaluation_score_min` decimal(5,2) DEFAULT 75.00,
    `additional_requirements` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `is_active` (`is_active`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $regularization_criteria_table)) {
    echo "✓ regularization_criteria table created<br>";
} else {
    echo "✗ Error creating regularization_criteria: " . mysqli_error($conn) . "<br>";
}

// 5. Insert default regularization criteria
$default_criteria = [
    'Standard Employee Regularization',
    'Standard criteria for employee regularization in government service',
    6,  // 6 months probation
    3.00,  // minimum performance rating
    95.00,  // 95% attendance
    0,  // no disciplinary issues
    1,  // training completion required
    75.00  // 75% evaluation score
];

$criteria_query = "INSERT INTO regularization_criteria 
    (criteria_name, criteria_description, minimum_months, performance_rating_min, 
     attendance_percentage_min, disciplinary_issues_max, training_completion_required, evaluation_score_min) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE criteria_name=criteria_name";
$criteria_stmt = mysqli_prepare($conn, $criteria_query);
mysqli_stmt_bind_param($criteria_stmt, "ssiddiid", 
    $default_criteria[0], $default_criteria[1], $default_criteria[2], 
    $default_criteria[3], $default_criteria[4], $default_criteria[5],
    $default_criteria[6], $default_criteria[7]
);
mysqli_stmt_execute($criteria_stmt);
echo "✓ Default regularization criteria inserted<br>";

// 6. Create regularization_reviews table
$regularization_reviews_table = "CREATE TABLE IF NOT EXISTS `regularization_reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_regularization_id` int(11) NOT NULL,
    `review_date` date NOT NULL,
    `reviewer_id` int(11) NOT NULL,
    `status_id` int(11) NOT NULL,
    `performance_rating` decimal(3,2) DEFAULT NULL,
    `attendance_percentage` decimal(5,2) DEFAULT NULL,
    `evaluation_score` decimal(5,2) DEFAULT NULL,
    `disciplinary_issues` int(11) DEFAULT 0,
    `training_completed` tinyint(1) DEFAULT 0,
    `review_comments` text,
    `recommendation` enum('approve','extend','terminate') DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_regularization_id` (`employee_regularization_id`),
    KEY `reviewer_id` (`reviewer_id`),
    KEY `status_id` (`status_id`),
    KEY `review_date` (`review_date`),
    FOREIGN KEY (`employee_regularization_id`) REFERENCES `employee_regularization`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`status_id`) REFERENCES `regularization_status`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $regularization_reviews_table)) {
    echo "✓ regularization_reviews table created<br>";
} else {
    echo "✗ Error creating regularization_reviews: " . mysqli_error($conn) . "<br>";
}

// 7. Create regularization_notifications table
$regularization_notifications_table = "CREATE TABLE IF NOT EXISTS `regularization_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `notification_type` enum('upcoming_review','review_due','probation_ending','regularized','extended','terminated') NOT NULL,
    `notification_date` date NOT NULL,
    `is_sent` tinyint(1) DEFAULT 0,
    `sent_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `notification_date` (`notification_date`),
    KEY `is_sent` (`is_sent`),
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $regularization_notifications_table)) {
    echo "✓ regularization_notifications table created<br>";
} else {
    echo "✗ Error creating regularization_notifications: " . mysqli_error($conn) . "<br>";
}

echo "<br><h3>✓ Regularization System Setup Complete!</h3>";
echo "<p>The following tables have been created:</p>";
echo "<ul>";
echo "<li>regularization_status (7 default statuses)</li>";
echo "<li>employee_regularization</li>";
echo "<li>regularization_criteria (1 default criteria)</li>";
echo "<li>regularization_reviews</li>";
echo "<li>regularization_notifications</li>";
echo "</ul>";
echo "<p><a href='manage-regularization.php'>Go to Regularization Management</a> | ";
echo "<a href='index.php'>Go to Dashboard</a></p>";
?>

