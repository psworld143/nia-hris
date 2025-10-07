-- Government Benefits Contribution Tables

-- ============================================================================
-- GOVERNMENT BENEFIT RATES TABLE (SSS, PhilHealth, Pag-IBIG contribution rates)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `government_benefit_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `benefit_type` ENUM('sss', 'philhealth', 'pagibig') NOT NULL,
    `salary_range_min` DECIMAL(12,2) NOT NULL,
    `salary_range_max` DECIMAL(12,2) NOT NULL,
    `employee_rate` DECIMAL(10,2) NOT NULL COMMENT 'Employee contribution amount or percentage',
    `employer_rate` DECIMAL(10,2) NOT NULL COMMENT 'Employer contribution amount or percentage',
    `is_percentage` TINYINT(1) DEFAULT 0 COMMENT '0=Fixed amount, 1=Percentage',
    `effective_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    INDEX idx_benefit_type (`benefit_type`),
    INDEX idx_salary_range (`salary_range_min`, `salary_range_max`),
    INDEX idx_active (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TAX BRACKETS TABLE (BIR Withholding Tax)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tax_brackets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bracket_name` VARCHAR(100) NOT NULL,
    `income_min` DECIMAL(12,2) NOT NULL,
    `income_max` DECIMAL(12,2),
    `base_tax` DECIMAL(10,2) DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL COMMENT 'Percentage rate for income over minimum',
    `effective_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`),
    INDEX idx_income_range (`income_min`, `income_max`),
    INDEX idx_active (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT SSS CONTRIBUTION RATES (2024-2025)
-- ============================================================================
INSERT INTO `government_benefit_rates` (`benefit_type`, `salary_range_min`, `salary_range_max`, `employee_rate`, `employer_rate`, `is_percentage`, `effective_date`, `notes`) VALUES
('sss', 0.00, 4249.99, 180.00, 380.00, 0, '2024-01-01', 'SSS contribution for salary below ₱4,250'),
('sss', 4250.00, 4749.99, 202.50, 427.50, 0, '2024-01-01', 'SSS contribution for salary ₱4,250 - ₱4,749.99'),
('sss', 4750.00, 5249.99, 225.00, 475.00, 0, '2024-01-01', 'SSS contribution for salary ₱4,750 - ₱5,249.99'),
('sss', 5250.00, 5749.99, 247.50, 522.50, 0, '2024-01-01', 'SSS contribution for salary ₱5,250 - ₱5,749.99'),
('sss', 5750.00, 6249.99, 270.00, 570.00, 0, '2024-01-01', 'SSS contribution for salary ₱5,750 - ₱6,249.99'),
('sss', 10000.00, 14999.99, 450.00, 950.00, 0, '2024-01-01', 'SSS contribution for salary ₱10,000 - ₱14,999.99'),
('sss', 15000.00, 19999.99, 675.00, 1425.00, 0, '2024-01-01', 'SSS contribution for salary ₱15,000 - ₱19,999.99'),
('sss', 20000.00, 24999.99, 900.00, 1900.00, 0, '2024-01-01', 'SSS contribution for salary ₱20,000 - ₱24,999.99'),
('sss', 25000.00, 29999.99, 1125.00, 2375.00, 0, '2024-01-01', 'SSS contribution for salary ₱25,000 - ₱29,999.99'),
('sss', 30000.00, 999999.99, 1350.00, 2850.00, 0, '2024-01-01', 'SSS maximum contribution for salary ₱30,000 and above')
ON DUPLICATE KEY UPDATE employee_rate = VALUES(employee_rate);

-- ============================================================================
-- INSERT DEFAULT PHILHEALTH CONTRIBUTION RATES (2024-2025)
-- ============================================================================
INSERT INTO `government_benefit_rates` (`benefit_type`, `salary_range_min`, `salary_range_max`, `employee_rate`, `employer_rate`, `is_percentage`, `effective_date`, `notes`) VALUES
('philhealth', 0.00, 10000.00, 500.00, 500.00, 0, '2024-01-01', 'PhilHealth minimum premium'),
('philhealth', 10000.01, 99999.99, 0.00, 0.00, 1, '2024-01-01', 'PhilHealth 5% of basic salary (shared equally)'),
('philhealth', 100000.00, 999999.99, 5000.00, 5000.00, 0, '2024-01-01', 'PhilHealth maximum premium')
ON DUPLICATE KEY UPDATE employee_rate = VALUES(employee_rate);

-- ============================================================================
-- INSERT DEFAULT PAG-IBIG CONTRIBUTION RATES (2024-2025)
-- ============================================================================
INSERT INTO `government_benefit_rates` (`benefit_type`, `salary_range_min`, `salary_range_max`, `employee_rate`, `employer_rate`, `is_percentage`, `effective_date`, `notes`) VALUES
('pagibig', 0.00, 1500.00, 1.00, 2.00, 1, '2024-01-01', 'Pag-IBIG 1% employee, 2% employer for salary below ₱1,500'),
('pagibig', 1500.01, 4999.99, 2.00, 2.00, 1, '2024-01-01', 'Pag-IBIG 2% for both employee and employer'),
('pagibig', 5000.00, 999999.99, 100.00, 100.00, 0, '2024-01-01', 'Pag-IBIG maximum contribution ₱100 each')
ON DUPLICATE KEY UPDATE employee_rate = VALUES(employee_rate);

-- ============================================================================
-- INSERT DEFAULT TAX BRACKETS (2024 BIR Tax Table)
-- ============================================================================
INSERT INTO `tax_brackets` (`bracket_name`, `income_min`, `income_max`, `base_tax`, `tax_rate`, `effective_date`) VALUES
('Tax Exempt', 0.00, 250000.00, 0.00, 0.00, '2024-01-01'),
('20%', 250000.01, 400000.00, 0.00, 20.00, '2024-01-01'),
('25%', 400000.01, 800000.00, 30000.00, 25.00, '2024-01-01'),
('30%', 800000.01, 2000000.00, 130000.00, 30.00, '2024-01-01'),
('32%', 2000000.01, 8000000.00, 490000.00, 32.00, '2024-01-01'),
('35%', 8000000.01, 999999999.99, 2410000.00, 35.00, '2024-01-01')
ON DUPLICATE KEY UPDATE base_tax = VALUES(base_tax);

