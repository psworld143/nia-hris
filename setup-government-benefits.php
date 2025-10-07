<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        header('Location: index.php');
    }
    exit;
}

$is_ajax = isset($_POST['ajax']);

if ($is_ajax) {
    header('Content-Type: application/json');
}

try {
    // Create government_benefit_rates table
    $create_rates_table = "CREATE TABLE IF NOT EXISTS `government_benefit_rates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `benefit_type` ENUM('sss', 'philhealth', 'pagibig') NOT NULL,
        `salary_range_min` DECIMAL(10,2) NOT NULL,
        `salary_range_max` DECIMAL(10,2) NOT NULL,
        `employee_rate` DECIMAL(10,2) NOT NULL,
        `employer_rate` DECIMAL(10,2) DEFAULT 0.00,
        `is_percentage` TINYINT(1) DEFAULT 0,
        `effective_date` DATE NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_benefit_type` (`benefit_type`),
        INDEX `idx_salary_range` (`salary_range_min`, `salary_range_max`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $create_rates_table)) {
        throw new Exception("Error creating government_benefit_rates table: " . mysqli_error($conn));
    }
    
    // Create tax_brackets table
    $create_tax_table = "CREATE TABLE IF NOT EXISTS `tax_brackets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `bracket_name` VARCHAR(100) NOT NULL,
        `income_min` DECIMAL(10,2) NOT NULL,
        `income_max` DECIMAL(10,2) NULL,
        `base_tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `tax_rate` DECIMAL(5,2) NOT NULL,
        `effective_date` DATE NOT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_income_range` (`income_min`, `income_max`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!mysqli_query($conn, $create_tax_table)) {
        throw new Exception("Error creating tax_brackets table: " . mysqli_error($conn));
    }
    
    // Insert default SSS rates (2024 rates)
    $sss_rates = [
        [3250, 3749.99, 135, 517.50],
        [3750, 4249.99, 157.50, 602.50],
        [4250, 4749.99, 180, 687.50],
        [4750, 5249.99, 202.50, 772.50],
        [5250, 5749.99, 225, 857.50],
        [5750, 6249.99, 247.50, 942.50],
        [6250, 6749.99, 270, 1027.50],
        [6750, 7249.99, 292.50, 1112.50],
        [7250, 7749.99, 315, 1197.50],
        [7750, 8249.99, 337.50, 1282.50],
        [8250, 8749.99, 360, 1367.50],
        [8750, 9249.99, 382.50, 1452.50],
        [9250, 9749.99, 405, 1537.50],
        [9750, 10249.99, 427.50, 1622.50],
        [10250, 10749.99, 450, 1707.50],
        [10750, 11249.99, 472.50, 1792.50],
        [11250, 11749.99, 495, 1877.50],
        [11750, 12249.99, 517.50, 1962.50],
        [12250, 12749.99, 540, 2047.50],
        [12750, 13249.99, 562.50, 2132.50],
        [13250, 13749.99, 585, 2217.50],
        [13750, 14249.99, 607.50, 2302.50],
        [14250, 14749.99, 630, 2387.50],
        [14750, 15249.99, 652.50, 2472.50],
        [15250, 15749.99, 675, 2557.50],
        [15750, 16249.99, 697.50, 2642.50],
        [16250, 16749.99, 720, 2727.50],
        [16750, 17249.99, 742.50, 2812.50],
        [17250, 17749.99, 765, 2897.50],
        [17750, 18249.99, 787.50, 2982.50],
        [18250, 18749.99, 810, 3067.50],
        [18750, 19249.99, 832.50, 3152.50],
        [19250, 19749.99, 855, 3237.50],
        [19750, 20249.99, 877.50, 3322.50],
        [20250, 20749.99, 900, 3407.50],
        [20750, 24999.99, 900, 3407.50],
        [25000, 29999.99, 1125, 4262.50],
        [30000, 50000, 1350, 5117.50]
    ];
    
    $sss_insert = "INSERT INTO government_benefit_rates (benefit_type, salary_range_min, salary_range_max, employee_rate, employer_rate, is_percentage, effective_date) VALUES ";
    $sss_values = [];
    foreach ($sss_rates as $rate) {
        $sss_values[] = "('sss', {$rate[0]}, {$rate[1]}, {$rate[2]}, {$rate[3]}, 0, '2024-01-01')";
    }
    mysqli_query($conn, $sss_insert . implode(', ', $sss_values));
    
    // Insert default PhilHealth rates (2024)
    $philhealth_rates = [
        [0, 10000, 450, 0, 0],
        [10000.01, 99999.99, 4.5, 0, 1], // 4.5% of basic salary
        [100000, 9999999.99, 4500, 0, 0] // Maximum cap
    ];
    
    $philhealth_insert = "INSERT INTO government_benefit_rates (benefit_type, salary_range_min, salary_range_max, employee_rate, employer_rate, is_percentage, effective_date) VALUES ";
    $philhealth_values = [];
    foreach ($philhealth_rates as $rate) {
        $philhealth_values[] = "('philhealth', {$rate[0]}, {$rate[1]}, {$rate[2]}, {$rate[3]}, {$rate[4]}, '2024-01-01')";
    }
    mysqli_query($conn, $philhealth_insert . implode(', ', $philhealth_values));
    
    // Insert default Pag-IBIG rates
    $pagibig_rates = [
        [0, 1500, 1, 2, 1], // 1% employee, 2% employer
        [1500.01, 5000, 2, 2, 1], // 2% employee, 2% employer
        [5000.01, 9999999.99, 100, 100, 0] // Fixed ₱100 employee, ₱100 employer
    ];
    
    $pagibig_insert = "INSERT INTO government_benefit_rates (benefit_type, salary_range_min, salary_range_max, employee_rate, employer_rate, is_percentage, effective_date) VALUES ";
    $pagibig_values = [];
    foreach ($pagibig_rates as $rate) {
        $pagibig_values[] = "('pagibig', {$rate[0]}, {$rate[1]}, {$rate[2]}, {$rate[3]}, {$rate[4]}, '2024-01-01')";
    }
    mysqli_query($conn, $pagibig_insert . implode(', ', $pagibig_values));
    
    // Insert default tax brackets (TRAIN Law 2024)
    $tax_brackets = [
        ['Below ₱250,000', 0, 250000, 0, 0, '2024-01-01'],
        ['₱250,000 - ₱400,000', 250000, 400000, 0, 15, '2024-01-01'],
        ['₱400,000 - ₱800,000', 400000, 800000, 22500, 20, '2024-01-01'],
        ['₱800,000 - ₱2,000,000', 800000, 2000000, 102500, 25, '2024-01-01'],
        ['₱2,000,000 - ₱8,000,000', 2000000, 8000000, 402500, 30, '2024-01-01'],
        ['Above ₱8,000,000', 8000000, null, 2202500, 35, '2024-01-01']
    ];
    
    $tax_insert = "INSERT INTO tax_brackets (bracket_name, income_min, income_max, base_tax, tax_rate, effective_date) VALUES ";
    $tax_values = [];
    foreach ($tax_brackets as $bracket) {
        $income_max = $bracket[2] ? $bracket[2] : 'NULL';
        $tax_values[] = "('{$bracket[0]}', {$bracket[1]}, {$income_max}, {$bracket[3]}, {$bracket[4]}, '{$bracket[5]}')";
    }
    mysqli_query($conn, $tax_insert . implode(', ', $tax_values));
    
    $message = "✅ Government benefit tables installed successfully!\n\n";
    $message .= "Created tables:\n";
    $message .= "- government_benefit_rates (with SSS, PhilHealth, Pag-IBIG rates)\n";
    $message .= "- tax_brackets (with TRAIN Law 2024 brackets)\n\n";
    $message .= "You can now manage benefit rates and tax brackets.";
    
    if ($is_ajax) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        header('Location: manage-benefit-rates.php?success=1');
    }
    
} catch (Exception $e) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        die("Error: " . $e->getMessage());
    }
}

mysqli_close($conn);
?>
