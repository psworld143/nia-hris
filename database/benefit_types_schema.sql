-- Benefit Types and Rates Management Schema

-- ============================================================================
-- BENEFIT TYPES TABLE (Define what benefits exist)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `benefit_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `benefit_code` VARCHAR(50) NOT NULL UNIQUE,
    `benefit_name` VARCHAR(100) NOT NULL,
    `category` ENUM('mandatory', 'optional', 'loan', 'other') DEFAULT 'optional',
    `calculation_type` ENUM('fixed', 'percentage', 'table') DEFAULT 'fixed',
    `default_rate` DECIMAL(10,2) DEFAULT 0.00,
    `has_employer_share` TINYINT(1) DEFAULT 0,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_benefit_code` (`benefit_code`),
    INDEX `idx_category` (`category`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- BENEFIT RATE TABLES (For benefits that use salary-based tables)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `benefit_rate_tables` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `benefit_type_id` INT NOT NULL,
    `salary_range_min` DECIMAL(10,2) NOT NULL,
    `salary_range_max` DECIMAL(10,2) NOT NULL,
    `employee_rate` DECIMAL(10,2) NOT NULL,
    `employer_rate` DECIMAL(10,2) DEFAULT 0.00,
    `is_percentage` TINYINT(1) DEFAULT 0,
    `effective_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`benefit_type_id`) REFERENCES `benefit_types`(`id`) ON DELETE CASCADE,
    INDEX `idx_benefit_type` (`benefit_type_id`),
    INDEX `idx_salary_range` (`salary_range_min`, `salary_range_max`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default benefit types
INSERT INTO `benefit_types` (`benefit_code`, `benefit_name`, `category`, `calculation_type`, `has_employer_share`, `description`) VALUES
('SSS', 'Social Security System', 'mandatory', 'table', 1, 'Government-mandated SSS contributions'),
('PHILHEALTH', 'PhilHealth', 'mandatory', 'table', 1, 'National Health Insurance Program'),
('PAGIBIG', 'Pag-IBIG Fund', 'mandatory', 'table', 1, 'Home Development Mutual Fund'),
('TAX', 'Withholding Tax', 'mandatory', 'table', 0, 'Income tax based on TRAIN law');

