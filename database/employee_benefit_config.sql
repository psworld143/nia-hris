-- Employee-specific government benefit configuration

-- ============================================================================
-- EMPLOYEE BENEFIT CONFIGURATIONS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `employee_benefit_configurations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` INT NOT NULL,
    
    -- SSS Configuration
    `sss_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
    `sss_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
    `sss_percentage` DECIMAL(5,2) DEFAULT 0.00,
    
    -- PhilHealth Configuration
    `philhealth_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
    `philhealth_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
    `philhealth_percentage` DECIMAL(5,2) DEFAULT 0.00,
    
    -- Pag-IBIG Configuration
    `pagibig_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
    `pagibig_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
    `pagibig_percentage` DECIMAL(5,2) DEFAULT 0.00,
    
    -- Tax Configuration
    `tax_deduction_type` ENUM('auto', 'fixed', 'percentage', 'none') DEFAULT 'auto',
    `tax_fixed_amount` DECIMAL(10,2) DEFAULT 0.00,
    `tax_percentage` DECIMAL(5,2) DEFAULT 0.00,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

