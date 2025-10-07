-- Payroll System Database Schema
-- Complete payroll management with hours tracking, automatic calculations, and deductions

-- ============================================================================
-- PAYROLL PERIODS TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_periods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_name` VARCHAR(100) NOT NULL,
    `period_type` ENUM('monthly', 'semi-monthly', 'bi-weekly', 'weekly') DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `payment_date` DATE NOT NULL,
    `status` ENUM('draft', 'open', 'processing', 'calculated', 'approved', 'paid', 'closed') DEFAULT 'draft',
    `total_employees` INT DEFAULT 0,
    `total_gross_pay` DECIMAL(15,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
    `total_net_pay` DECIMAL(15,2) DEFAULT 0.00,
    `notes` TEXT,
    `created_by` INT NOT NULL,
    `approved_by` INT,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
    INDEX idx_period_dates (`start_date`, `end_date`),
    INDEX idx_status (`status`),
    INDEX idx_payment_date (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL RECORDS TABLE (Main payroll entries per employee)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_period_id` INT NOT NULL,
    `employee_id` INT NOT NULL,
    `employee_name` VARCHAR(200) NOT NULL,
    `employee_number` VARCHAR(50),
    `department` VARCHAR(100),
    `position` VARCHAR(100),
    
    -- Time and Attendance
    `regular_hours` DECIMAL(8,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(8,2) DEFAULT 0.00,
    `night_diff_hours` DECIMAL(8,2) DEFAULT 0.00,
    `holiday_hours` DECIMAL(8,2) DEFAULT 0.00,
    `rest_day_hours` DECIMAL(8,2) DEFAULT 0.00,
    
    -- Rates
    `hourly_rate` DECIMAL(10,2) NOT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL,
    `monthly_rate` DECIMAL(12,2) NOT NULL,
    `overtime_rate` DECIMAL(10,2) DEFAULT 0.00,
    `night_diff_rate` DECIMAL(10,2) DEFAULT 0.00,
    
    -- Earnings
    `basic_pay` DECIMAL(12,2) DEFAULT 0.00,
    `overtime_pay` DECIMAL(10,2) DEFAULT 0.00,
    `night_diff_pay` DECIMAL(10,2) DEFAULT 0.00,
    `holiday_pay` DECIMAL(10,2) DEFAULT 0.00,
    `allowances` DECIMAL(10,2) DEFAULT 0.00,
    `bonuses` DECIMAL(10,2) DEFAULT 0.00,
    `other_earnings` DECIMAL(10,2) DEFAULT 0.00,
    `gross_pay` DECIMAL(12,2) DEFAULT 0.00,
    
    -- Deductions
    `sss_contribution` DECIMAL(10,2) DEFAULT 0.00,
    `philhealth_contribution` DECIMAL(10,2) DEFAULT 0.00,
    `pagibig_contribution` DECIMAL(10,2) DEFAULT 0.00,
    `withholding_tax` DECIMAL(10,2) DEFAULT 0.00,
    `sss_loan` DECIMAL(10,2) DEFAULT 0.00,
    `pagibig_loan` DECIMAL(10,2) DEFAULT 0.00,
    `salary_loan` DECIMAL(10,2) DEFAULT 0.00,
    `late_deduction` DECIMAL(10,2) DEFAULT 0.00,
    `undertime_deduction` DECIMAL(10,2) DEFAULT 0.00,
    `absences_deduction` DECIMAL(10,2) DEFAULT 0.00,
    `other_deductions` DECIMAL(10,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(12,2) DEFAULT 0.00,
    
    -- Net Pay
    `net_pay` DECIMAL(12,2) DEFAULT 0.00,
    
    -- Status and Notes
    `status` ENUM('draft', 'calculated', 'approved', 'paid', 'void') DEFAULT 'draft',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_employee_period` (`payroll_period_id`, `employee_id`),
    INDEX idx_employee (`employee_id`),
    INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL DEDUCTION TYPES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_deduction_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `category` ENUM('mandatory', 'loan', 'attendance', 'other') DEFAULT 'other',
    `is_percentage` TINYINT(1) DEFAULT 0,
    `default_value` DECIMAL(10,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (`code`),
    INDEX idx_category (`category`),
    INDEX idx_active (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL CUSTOM DEDUCTIONS TABLE (Additional deductions per employee)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_custom_deductions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_record_id` INT NOT NULL,
    `deduction_type_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`deduction_type_id`) REFERENCES `payroll_deduction_types`(`id`),
    INDEX idx_payroll_record (`payroll_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL EARNINGS TYPES TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_earning_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `category` ENUM('regular', 'overtime', 'allowance', 'bonus', 'other') DEFAULT 'other',
    `is_taxable` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (`code`),
    INDEX idx_category (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL CUSTOM EARNINGS TABLE (Additional earnings per employee)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_custom_earnings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_record_id` INT NOT NULL,
    `earning_type_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `notes` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`earning_type_id`) REFERENCES `payroll_earning_types`(`id`),
    INDEX idx_payroll_record (`payroll_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL ADJUSTMENTS TABLE (Manual adjustments)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_adjustments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_record_id` INT NOT NULL,
    `adjustment_type` ENUM('addition', 'deduction') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `approved_by` INT,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    INDEX idx_payroll_record (`payroll_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYROLL AUDIT LOG TABLE
-- ============================================================================
CREATE TABLE IF NOT EXISTS `payroll_audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_period_id` INT,
    `payroll_record_id` INT,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `old_values` JSON,
    `new_values` JSON,
    `performed_by` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`),
    INDEX idx_period (`payroll_period_id`),
    INDEX idx_action (`action`),
    INDEX idx_created_at (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT DEDUCTION TYPES
-- ============================================================================
INSERT INTO `payroll_deduction_types` (`code`, `name`, `description`, `category`, `is_percentage`, `default_value`, `sort_order`) VALUES
('SSS', 'SSS Contribution', 'Social Security System mandatory contribution', 'mandatory', 0, 0.00, 1),
('PHIC', 'PhilHealth Contribution', 'Philippine Health Insurance mandatory contribution', 'mandatory', 0, 0.00, 2),
('HDMF', 'Pag-IBIG Contribution', 'Home Development Mutual Fund mandatory contribution', 'mandatory', 0, 0.00, 3),
('WTAX', 'Withholding Tax', 'Income tax withholding', 'mandatory', 0, 0.00, 4),
('SSS_LOAN', 'SSS Loan', 'SSS salary loan deduction', 'loan', 0, 0.00, 5),
('HDMF_LOAN', 'Pag-IBIG Loan', 'Pag-IBIG housing loan deduction', 'loan', 0, 0.00, 6),
('SALARY_LOAN', 'Salary Loan', 'Company salary loan deduction', 'loan', 0, 0.00, 7),
('LATE', 'Late Deduction', 'Deduction for late arrivals', 'attendance', 0, 0.00, 8),
('UNDERTIME', 'Undertime Deduction', 'Deduction for undertime', 'attendance', 0, 0.00, 9),
('ABSENCE', 'Absence Deduction', 'Deduction for absences', 'attendance', 0, 0.00, 10),
('UNIFORMS', 'Uniforms', 'Uniform purchase deduction', 'other', 0, 0.00, 11),
('CASH_ADVANCE', 'Cash Advance', 'Cash advance deduction', 'other', 0, 0.00, 12)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================================
-- INSERT DEFAULT EARNING TYPES
-- ============================================================================
INSERT INTO `payroll_earning_types` (`code`, `name`, `description`, `category`, `is_taxable`, `sort_order`) VALUES
('BASIC', 'Basic Pay', 'Regular monthly salary', 'regular', 1, 1),
('OVERTIME', 'Overtime Pay', 'Overtime hours payment (1.25x)', 'overtime', 1, 2),
('NIGHT_DIFF', 'Night Differential', 'Night shift differential (10%)', 'overtime', 1, 3),
('HOLIDAY', 'Holiday Pay', 'Holiday premium pay', 'overtime', 1, 4),
('REST_DAY', 'Rest Day Pay', 'Rest day premium pay', 'overtime', 1, 5),
('ALLOWANCE', 'Allowances', 'Monthly allowances', 'allowance', 1, 6),
('13TH_MONTH', '13th Month Pay', '13th month pay (tax-exempt up to 90k)', 'bonus', 0, 7),
('PERFORMANCE_BONUS', 'Performance Bonus', 'Performance-based bonus', 'bonus', 1, 8),
('RICE_SUBSIDY', 'Rice Subsidy', 'Rice subsidy allowance', 'allowance', 0, 9),
('TRANSPORTATION', 'Transportation Allowance', 'Transportation allowance', 'allowance', 0, 10)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ============================================================================
-- PAYROLL CALCULATION VIEW
-- ============================================================================
CREATE OR REPLACE VIEW `v_payroll_summary` AS
SELECT 
    pp.id as period_id,
    pp.period_name,
    pp.start_date,
    pp.end_date,
    pp.payment_date,
    pp.status as period_status,
    pr.id as record_id,
    pr.employee_id,
    pr.employee_name,
    pr.employee_number,
    pr.department,
    pr.position,
    pr.regular_hours,
    pr.overtime_hours,
    pr.gross_pay,
    pr.total_deductions,
    pr.net_pay,
    pr.status as record_status,
    u.username as created_by_name
FROM payroll_periods pp
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
LEFT JOIN users u ON pp.created_by = u.id
ORDER BY pp.created_at DESC, pr.employee_name ASC;

-- ============================================================================
-- PAYROLL STATISTICS VIEW
-- ============================================================================
CREATE OR REPLACE VIEW `v_payroll_statistics` AS
SELECT 
    pp.id as period_id,
    pp.period_name,
    pp.status,
    COUNT(pr.id) as total_employees,
    SUM(pr.gross_pay) as total_gross,
    SUM(pr.total_deductions) as total_deductions,
    SUM(pr.net_pay) as total_net,
    AVG(pr.net_pay) as average_net_pay,
    SUM(pr.sss_contribution) as total_sss,
    SUM(pr.philhealth_contribution) as total_philhealth,
    SUM(pr.pagibig_contribution) as total_pagibig,
    SUM(pr.withholding_tax) as total_tax
FROM payroll_periods pp
LEFT JOIN payroll_records pr ON pp.id = pr.payroll_period_id
GROUP BY pp.id, pp.period_name, pp.status;

