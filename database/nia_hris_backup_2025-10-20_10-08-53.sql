-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: nia_hris
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `nia_hris`
--

/*!40000 DROP DATABASE IF EXISTS `nia_hris`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `nia_hris` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `nia_hris`;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `degrees`
--

DROP TABLE IF EXISTS `degrees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `degrees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `degree_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `degree_name` (`degree_name`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_degree_name` (`degree_name`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `degrees_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `degrees_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `degrees`
--

LOCK TABLES `degrees` WRITE;
/*!40000 ALTER TABLE `degrees` DISABLE KEYS */;
INSERT INTO `degrees` VALUES (1,'Elementary','Completed elementary education',1,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(2,'High School','Completed high school education',2,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(3,'Vocational','Vocational or technical training',3,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(4,'Associate Degree','Two-year college degree (Associate\'s)',4,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(5,'Bachelor\'s Degree','Four-year undergraduate degree',5,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(6,'Master\'s Degree','Graduate degree beyond bachelor\'s',6,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(7,'Doctorate','Doctoral degree (PhD, EdD, etc.)',7,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56'),(8,'Post-Doctorate','Post-doctoral research or studies',8,1,1,NULL,'2025-10-07 08:34:56','2025-10-07 08:34:56');
/*!40000 ALTER TABLE `degrees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `color_theme` varchar(7) DEFAULT '#FF6B35',
  `sort_order` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Engineering Department','ENG','Engineering and Technical Services','fas fa-cogs','#3B82F6',1,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(2,'Administration Department','ADMIN','Administrative and General Services','fas fa-user-tie','#8B5CF6',2,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(3,'Finance Department','FIN','Finance and Accounting Services','fas fa-dollar-sign','#10B981',3,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(4,'Human Resources Department','HR','Human Resources Management','fas fa-users','#F59E0B',4,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(5,'Operations Department','OPS','Operations and Maintenance','fas fa-tasks','#EF4444',5,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(6,'Planning Department','PLAN','Planning and Development','fas fa-clipboard-list','#06B6D4',6,NULL,1,'2025-10-07 05:11:59','2025-10-07 05:42:10'),(7,'Information Technology','IT','IT support and development',NULL,'#FF6B35',0,NULL,1,'2025-10-19 16:22:58','2025-10-19 16:22:58'),(8,'Health Services','HEALTH','Medical and health services',NULL,'#FF6B35',0,NULL,1,'2025-10-19 16:22:58','2025-10-19 16:22:58');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_benefit_configurations`
--

DROP TABLE IF EXISTS `employee_benefit_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_benefit_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `sss_deduction_type` enum('auto','fixed','percentage','none') DEFAULT 'auto',
  `sss_fixed_amount` decimal(10,2) DEFAULT 0.00,
  `sss_percentage` decimal(5,2) DEFAULT 0.00,
  `philhealth_deduction_type` enum('auto','fixed','percentage','none') DEFAULT 'auto',
  `philhealth_fixed_amount` decimal(10,2) DEFAULT 0.00,
  `philhealth_percentage` decimal(5,2) DEFAULT 0.00,
  `pagibig_deduction_type` enum('auto','fixed','percentage','none') DEFAULT 'auto',
  `pagibig_fixed_amount` decimal(10,2) DEFAULT 0.00,
  `pagibig_percentage` decimal(5,2) DEFAULT 0.00,
  `tax_deduction_type` enum('auto','fixed','percentage','none') DEFAULT 'auto',
  `tax_fixed_amount` decimal(10,2) DEFAULT 0.00,
  `tax_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee` (`employee_id`),
  CONSTRAINT `employee_benefit_configurations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_benefit_configurations`
--

LOCK TABLES `employee_benefit_configurations` WRITE;
/*!40000 ALTER TABLE `employee_benefit_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_benefit_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_benefits`
--

DROP TABLE IF EXISTS `employee_benefits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_benefits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL COMMENT 'Foreign key to employees table',
  `benefit_type` enum('Health Insurance','Life Insurance','Dental','Vision','Retirement','Bonus','Allowance','Other') NOT NULL COMMENT 'Type of benefit',
  `benefit_name` varchar(100) NOT NULL COMMENT 'Name of the benefit',
  `benefit_description` text DEFAULT NULL COMMENT 'Description of the benefit',
  `benefit_amount` decimal(10,2) DEFAULT NULL COMMENT 'Monetary value of benefit',
  `coverage_percentage` decimal(5,2) DEFAULT 100.00 COMMENT 'Coverage percentage (e.g., 80% for health insurance)',
  `provider` varchar(100) DEFAULT NULL COMMENT 'Benefit provider (insurance company, etc.)',
  `policy_number` varchar(50) DEFAULT NULL COMMENT 'Policy or account number',
  `effective_date` date DEFAULT NULL COMMENT 'Date benefit became effective',
  `expiry_date` date DEFAULT NULL COMMENT 'Date benefit expires',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether benefit is currently active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_benefits` (`employee_id`,`benefit_type`),
  KEY `idx_benefit_type` (`benefit_type`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_employee_benefits_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee benefits and insurance information';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_benefits`
--

LOCK TABLES `employee_benefits` WRITE;
/*!40000 ALTER TABLE `employee_benefits` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_benefits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_details`
--

DROP TABLE IF EXISTS `employee_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL COMMENT 'Foreign key to employees table',
  `middle_name` varchar(50) DEFAULT NULL COMMENT 'Middle name of the employee',
  `date_of_birth` date DEFAULT NULL COMMENT 'Date of birth of the employee',
  `gender` enum('Male','Female','Other') DEFAULT NULL COMMENT 'Gender of the employee',
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated') DEFAULT NULL COMMENT 'Civil status of the employee',
  `nationality` varchar(50) DEFAULT 'Filipino' COMMENT 'Nationality of the employee',
  `religion` varchar(100) DEFAULT NULL COMMENT 'Religion of the employee',
  `emergency_contact_name` varchar(100) DEFAULT NULL COMMENT 'Name of emergency contact person',
  `emergency_contact_number` varchar(20) DEFAULT NULL COMMENT 'Phone number of emergency contact',
  `emergency_contact_relationship` varchar(50) DEFAULT NULL COMMENT 'Relationship to emergency contact',
  `employment_type` enum('Full-time','Part-time','Contract','Temporary','Probationary','Casual','Regular','Contractual','Part Time') DEFAULT 'Probationary',
  `employment_status` enum('Full Time','Visiting') DEFAULT 'Full Time',
  `job_level` enum('Entry Level','Associate','Senior','Supervisor','Manager','Director','Executive') DEFAULT 'Entry Level' COMMENT 'Job level or hierarchy',
  `immediate_supervisor` varchar(100) DEFAULT NULL COMMENT 'Name of immediate supervisor',
  `work_schedule` enum('Regular','Shifting','Flexible','Remote','Hybrid') DEFAULT 'Regular' COMMENT 'Work schedule type',
  `probation_period_months` int(2) DEFAULT 6 COMMENT 'Probation period in months',
  `regularization_date` date DEFAULT NULL COMMENT 'Date when employee was regularized',
  `basic_salary` decimal(12,2) DEFAULT NULL COMMENT 'Basic monthly salary amount',
  `salary_grade` varchar(20) DEFAULT NULL COMMENT 'Government salary grade level',
  `step_increment` int(2) DEFAULT 1 COMMENT 'Step increment within salary grade',
  `allowances` decimal(10,2) DEFAULT 0.00 COMMENT 'Monthly allowances amount',
  `overtime_rate` decimal(8,2) DEFAULT NULL COMMENT 'Hourly overtime rate',
  `night_differential_rate` decimal(5,2) DEFAULT 0.10 COMMENT 'Night differential percentage (default 10%)',
  `hazard_pay` decimal(10,2) DEFAULT 0.00 COMMENT 'Monthly hazard pay amount',
  `pay_schedule` enum('Monthly','Bi-monthly','Quincena','Weekly','Daily') DEFAULT 'Monthly' COMMENT 'Payment schedule',
  `salary_structure_id` int(11) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL COMMENT 'Bank account number for salary',
  `bank_name` varchar(100) DEFAULT NULL COMMENT 'Bank name for salary account',
  `tin_number` varchar(20) DEFAULT NULL COMMENT 'Tax Identification Number (BIR)',
  `sss_number` varchar(20) DEFAULT NULL COMMENT 'Social Security System number',
  `philhealth_number` varchar(20) DEFAULT NULL COMMENT 'PhilHealth ID number',
  `pagibig_number` varchar(20) DEFAULT NULL COMMENT 'PAG-IBIG (HDMF) number',
  `umid_number` varchar(20) DEFAULT NULL COMMENT 'Unified Multi-Purpose ID number',
  `postal_id` varchar(20) DEFAULT NULL COMMENT 'Postal ID number',
  `voters_id` varchar(20) DEFAULT NULL COMMENT 'Voters ID number',
  `drivers_license` varchar(20) DEFAULT NULL COMMENT 'Drivers license number',
  `passport_number` varchar(20) DEFAULT NULL COMMENT 'Passport number',
  `passport_expiry` date DEFAULT NULL COMMENT 'Passport expiry date',
  `prc_license_number` varchar(50) DEFAULT NULL COMMENT 'PRC (Professional Regulation Commission) license number',
  `prc_license_expiry` date DEFAULT NULL COMMENT 'PRC license expiry date',
  `prc_profession` varchar(100) DEFAULT NULL COMMENT 'Licensed profession',
  `other_licenses` text DEFAULT NULL COMMENT 'Other professional licenses and certifications (JSON format)',
  `certifications` text DEFAULT NULL COMMENT 'Professional certifications and training (JSON format)',
  `highest_education` enum('Elementary','High School','Vocational','Associate Degree','Bachelor''s Degree','Master''s Degree','Doctorate','Post-Doctorate') DEFAULT NULL COMMENT 'Highest educational attainment',
  `field_of_study` varchar(100) DEFAULT NULL COMMENT 'Field of study or specialization',
  `school_university` varchar(200) DEFAULT NULL COMMENT 'School or university attended',
  `year_graduated` int(4) DEFAULT NULL COMMENT 'Year of graduation',
  `honors_awards` varchar(200) DEFAULT NULL COMMENT 'Academic honors and awards received',
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL COMMENT 'Blood type',
  `medical_conditions` text DEFAULT NULL COMMENT 'Known medical conditions or allergies',
  `fitness_for_duty` enum('Fit','Unfit','Conditional') DEFAULT 'Fit' COMMENT 'Medical fitness for duty status',
  `last_medical_exam` date DEFAULT NULL COMMENT 'Date of last medical examination',
  `next_medical_exam` date DEFAULT NULL COMMENT 'Date of next required medical exam',
  `skills_competencies` text DEFAULT NULL COMMENT 'Professional skills and competencies (JSON format)',
  `languages_spoken` varchar(200) DEFAULT 'Filipino, English' COMMENT 'Languages spoken',
  `references` text DEFAULT NULL COMMENT 'Professional references (JSON format)',
  `notes` text DEFAULT NULL COMMENT 'Additional HR notes',
  `profile_photo` varchar(255) DEFAULT NULL COMMENT 'Path to employee profile photo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Record creation timestamp',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Record update timestamp',
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_employee_id` (`employee_id`) COMMENT 'One detail record per employee',
  KEY `idx_employment_type` (`employment_type`) COMMENT 'Index for employment type filtering',
  KEY `idx_job_level` (`job_level`) COMMENT 'Index for job level filtering',
  KEY `idx_salary_grade` (`salary_grade`) COMMENT 'Index for salary grade queries',
  KEY `idx_regularization_date` (`regularization_date`) COMMENT 'Index for regularization tracking',
  KEY `idx_tin_number` (`tin_number`) COMMENT 'Index for TIN lookups',
  KEY `idx_sss_number` (`sss_number`) COMMENT 'Index for SSS lookups',
  KEY `idx_philhealth_number` (`philhealth_number`) COMMENT 'Index for PhilHealth lookups',
  KEY `idx_pagibig_number` (`pagibig_number`) COMMENT 'Index for PAG-IBIG lookups',
  KEY `idx_prc_license` (`prc_license_number`) COMMENT 'Index for PRC license lookups',
  KEY `idx_created_at` (`created_at`) COMMENT 'Index for creation date queries',
  KEY `fk_employee_details_created_by` (`created_by`),
  KEY `fk_employee_details_updated_by` (`updated_by`),
  CONSTRAINT `fk_employee_details_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_details_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_details_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive HR employment details for employees';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_details`
--

LOCK TABLES `employee_details` WRITE;
/*!40000 ALTER TABLE `employee_details` DISABLE KEYS */;
INSERT INTO `employee_details` VALUES (27,12,NULL,NULL,NULL,NULL,'Filipino',NULL,NULL,NULL,NULL,'Regular','Full Time','Entry Level',NULL,'Regular',6,NULL,35000.00,NULL,1,0.00,NULL,0.10,0.00,'Monthly',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fit',NULL,NULL,NULL,'Filipino, English',NULL,NULL,NULL,'2025-10-19 16:26:39',NULL,NULL,NULL),(28,13,NULL,NULL,NULL,NULL,'Filipino',NULL,NULL,NULL,NULL,'Regular','Full Time','Entry Level',NULL,'Regular',6,NULL,45000.00,NULL,1,0.00,NULL,0.10,0.00,'Monthly',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fit',NULL,NULL,NULL,'Filipino, English',NULL,NULL,NULL,'2025-10-19 16:26:39',NULL,NULL,NULL);
/*!40000 ALTER TABLE `employee_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_dtr_cards`
--

DROP TABLE IF EXISTS `employee_dtr_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_dtr_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `payroll_period_id` int(11) DEFAULT NULL,
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL,
  `status` enum('pending','verified','processed','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_employee_period` (`employee_id`,`period_start_date`,`period_end_date`),
  KEY `idx_payroll_period` (`payroll_period_id`),
  KEY `idx_status` (`status`),
  KEY `idx_upload_date` (`upload_date`),
  CONSTRAINT `employee_dtr_cards_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_dtr_cards_ibfk_2` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_dtr_cards_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_dtr_cards_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_dtr_cards`
--

LOCK TABLES `employee_dtr_cards` WRITE;
/*!40000 ALTER TABLE `employee_dtr_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_dtr_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_leave_allowances`
--

DROP TABLE IF EXISTS `employee_leave_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_leave_allowances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(4) NOT NULL,
  `base_days` int(11) NOT NULL DEFAULT 5,
  `accumulated_days` int(11) NOT NULL DEFAULT 0,
  `total_days` int(11) NOT NULL DEFAULT 5,
  `used_days` int(11) NOT NULL DEFAULT 0,
  `remaining_days` int(11) NOT NULL DEFAULT 5,
  `is_regular` tinyint(1) DEFAULT 0,
  `regularization_date` date DEFAULT NULL,
  `can_accumulate` tinyint(1) DEFAULT 0,
  `accumulation_start_year` int(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_year_type` (`employee_id`,`leave_type_id`,`year`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_year` (`year`),
  KEY `idx_leave_type` (`leave_type_id`),
  KEY `idx_is_regular` (`is_regular`),
  KEY `idx_can_accumulate` (`can_accumulate`)
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_allowances`
--

LOCK TABLES `employee_leave_allowances` WRITE;
/*!40000 ALTER TABLE `employee_leave_allowances` DISABLE KEYS */;
INSERT INTO `employee_leave_allowances` VALUES (162,12,1,2025,5,0,15,0,15,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL),(163,12,2,2025,5,0,15,0,15,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL),(164,12,3,2025,5,0,3,0,3,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL),(165,13,1,2025,5,0,15,0,15,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL),(166,13,2,2025,5,0,15,0,15,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL),(167,13,3,2025,5,0,3,0,3,0,NULL,0,NULL,'2025-10-19 16:26:39',NULL);
/*!40000 ALTER TABLE `employee_leave_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_leave_requests`
--

DROP TABLE IF EXISTS `employee_leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved_by_head','approved_by_hr','rejected','cancelled') DEFAULT 'pending',
  `department_head_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `hr_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `department_head_id` int(11) DEFAULT NULL,
  `hr_approver_id` int(11) DEFAULT NULL,
  `department_head_comment` text DEFAULT NULL,
  `hr_comment` text DEFAULT NULL,
  `department_head_approved_at` timestamp NULL DEFAULT NULL,
  `hr_approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_leave_type_id` (`leave_type_id`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_status` (`status`),
  KEY `idx_department_head_approval` (`department_head_approval`),
  KEY `idx_hr_approval` (`hr_approval`),
  KEY `department_head_id` (`department_head_id`),
  KEY `hr_approver_id` (`hr_approver_id`),
  CONSTRAINT `employee_leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_leave_requests_ibfk_3` FOREIGN KEY (`department_head_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_leave_requests_ibfk_4` FOREIGN KEY (`hr_approver_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_requests`
--

LOCK TABLES `employee_leave_requests` WRITE;
/*!40000 ALTER TABLE `employee_leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_medical_history`
--

DROP TABLE IF EXISTS `employee_medical_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_medical_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('checkup','diagnosis','treatment','vaccination','lab_test','consultation','emergency','follow_up') NOT NULL DEFAULT 'checkup',
  `chief_complaint` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medication_prescribed` text DEFAULT NULL,
  `lab_results` text DEFAULT NULL,
  `vital_signs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vital_signs`)),
  `doctor_name` varchar(100) DEFAULT NULL,
  `clinic_hospital` varchar(200) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_employee_date` (`employee_id`,`record_date`),
  KEY `idx_record_type` (`record_type`),
  CONSTRAINT `employee_medical_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_medical_history_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_medical_history`
--

LOCK TABLES `employee_medical_history` WRITE;
/*!40000 ALTER TABLE `employee_medical_history` DISABLE KEYS */;
INSERT INTO `employee_medical_history` VALUES (1,12,'2023-12-01','diagnosis','Fatigue and weakness','Tension headache','Advised to return if symptoms persist','Salbutamol inhaler - 2 puffs as needed',NULL,'{\"blood_pressure\":\"126\\/70\",\"heart_rate\":85,\"temperature\":36.3,\"respiratory_rate\":16,\"weight\":62,\"height\":154}','Dr. Miguel Fernandez','NIA Health Center',NULL,'Recorded during Diagnosis visit on December 1, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(2,12,'2025-08-22','diagnosis','Back pain','Muscle strain','Home care instructions given','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"130\\/71\",\"heart_rate\":68,\"temperature\":36.8,\"respiratory_rate\":19,\"weight\":53,\"height\":160}','Dr. Jose Garcia','The Medical City','2025-09-18','Recorded during Diagnosis visit on August 22, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(3,12,'2025-04-26','checkup','Routine health checkup','Good health status','Lifestyle modification recommended','Cetirizine 10mg - 1 tab once daily',NULL,'{\"blood_pressure\":\"122\\/85\",\"heart_rate\":89,\"temperature\":36.3,\"respiratory_rate\":20,\"weight\":61,\"height\":160}','Dr. Carmen Ramos','Makati Medical Center',NULL,'Recorded during Checkup visit on April 26, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(4,12,'2025-07-15','consultation','Second opinion','Advice given','Rest and observation','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"122\\/84\",\"heart_rate\":61,\"temperature\":36.3,\"respiratory_rate\":18,\"weight\":53,\"height\":160}','Dr. Pedro Cruz','St. Luke\'s Medical Center',NULL,'Recorded during Consultation visit on July 15, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(5,12,'2025-10-13','vaccination','Tetanus booster','Vaccination completed','Referred to specialist','Paracetamol 500mg - 1 tab every 6 hours as needed',NULL,'{\"blood_pressure\":\"126\\/73\",\"heart_rate\":75,\"temperature\":36.4,\"respiratory_rate\":16,\"weight\":68,\"height\":163}','Dr. Ana Reyes','Makati Medical Center',NULL,'Recorded during Vaccination visit on October 13, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(6,12,'2024-01-11','diagnosis','Back pain','Viral infection','Advised to return if symptoms persist','Mefenamic acid 500mg - 1 cap every 8 hours',NULL,'{\"blood_pressure\":\"112\\/74\",\"heart_rate\":84,\"temperature\":37.5,\"respiratory_rate\":14,\"weight\":64,\"height\":160}','Dr. Pedro Cruz','St. Luke\'s Medical Center','2024-01-22','Recorded during Diagnosis visit on January 11, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(7,12,'2023-03-10','lab_test','Blood sugar test','No abnormalities detected','Continue current medications','Paracetamol 500mg - 1 tab every 6 hours as needed','CBC: WBC 7.2, RBC 5.1, Hgb 14.5, Hct 42%\nUrinalysis: Normal\nOther parameters: Within normal limits','{\"blood_pressure\":\"122\\/85\",\"heart_rate\":68,\"temperature\":36.1,\"respiratory_rate\":19,\"weight\":73,\"height\":163}','Dr. Miguel Fernandez','The Medical City','2023-03-25','Recorded during Lab_test visit on March 10, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(8,12,'2023-07-26','diagnosis','Fever and cough','Muscle strain','Continue current medications','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"117\\/78\",\"heart_rate\":87,\"temperature\":36,\"respiratory_rate\":16,\"weight\":65,\"height\":165}','Dr. Ana Reyes','Makati Medical Center',NULL,'Recorded during Diagnosis visit on July 26, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(9,12,'2022-12-01','checkup','Routine health checkup','Generally healthy','Home care instructions given','Multivitamins - 1 tab once daily',NULL,'{\"blood_pressure\":\"117\\/77\",\"heart_rate\":61,\"temperature\":36.5,\"respiratory_rate\":14,\"weight\":60,\"height\":164}','Dr. Miguel Fernandez','The Medical City',NULL,'Recorded during Checkup visit on December 1, 2022',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(10,12,'2024-12-05','vaccination','Tetanus booster','Booster dose given','Lifestyle modification recommended','Salbutamol inhaler - 2 puffs as needed',NULL,'{\"blood_pressure\":\"123\\/85\",\"heart_rate\":85,\"temperature\":36.1,\"respiratory_rate\":15,\"weight\":70,\"height\":164}','Dr. Pedro Cruz','Makati Medical Center',NULL,'Recorded during Vaccination visit on December 5, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(11,12,'2023-11-03','consultation','Health guidance','Observation recommended','Medication prescribed','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"117\\/83\",\"heart_rate\":69,\"temperature\":36.2,\"respiratory_rate\":18,\"weight\":58,\"height\":153}','Dr. Maria Santos','Asian Hospital and Medical Center',NULL,'Recorded during Consultation visit on November 3, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(12,12,'2023-01-29','vaccination','COVID-19 vaccination','Booster dose given','Rest and observation','Amoxicillin 500mg - 1 cap 3x a day for 7 days',NULL,'{\"blood_pressure\":\"120\\/75\",\"heart_rate\":73,\"temperature\":37.4,\"respiratory_rate\":14,\"weight\":78,\"height\":166}','Dr. Jose Garcia','Manila Doctors Hospital','2023-02-08','Recorded during Vaccination visit on January 29, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(13,12,'2023-08-01','checkup','Pre-employment medical exam','Good health status','Continue current medications','Cetirizine 10mg - 1 tab once daily',NULL,'{\"blood_pressure\":\"121\\/79\",\"heart_rate\":77,\"temperature\":36.5,\"respiratory_rate\":19,\"weight\":67,\"height\":173}','Dr. Pedro Cruz','NIA Health Center','2023-08-20','Recorded during Checkup visit on August 1, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(14,12,'2025-08-16','vaccination','Pneumonia vaccine','Vaccination completed','Medication prescribed','Mefenamic acid 500mg - 1 cap every 8 hours',NULL,'{\"blood_pressure\":\"130\\/74\",\"heart_rate\":81,\"temperature\":37,\"respiratory_rate\":15,\"weight\":59,\"height\":162}','Dr. Ana Reyes','Manila Doctors Hospital','2025-09-09','Recorded during Vaccination visit on August 16, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(15,12,'2022-12-17','consultation','Follow-up consultation','Advice given','Advised to return if symptoms persist','Amoxicillin 500mg - 1 cap 3x a day for 7 days',NULL,'{\"blood_pressure\":\"121\\/85\",\"heart_rate\":61,\"temperature\":36.1,\"respiratory_rate\":16,\"weight\":77,\"height\":157}','Dr. Carmen Ramos','St. Luke\'s Medical Center','2022-12-25','Recorded during Consultation visit on December 17, 2022',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(16,13,'2025-06-24','diagnosis','Back pain','Viral infection','Medication prescribed','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"114\\/85\",\"heart_rate\":90,\"temperature\":36,\"respiratory_rate\":15,\"weight\":76,\"height\":150}','Dr. Carmen Ramos','The Medical City','2025-07-09','Recorded during Diagnosis visit on June 24, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(17,13,'2025-08-09','diagnosis','Persistent headache','Muscle strain','Continue current medications','Multivitamins - 1 tab once daily',NULL,'{\"blood_pressure\":\"125\\/71\",\"heart_rate\":61,\"temperature\":36.2,\"respiratory_rate\":14,\"weight\":71,\"height\":155}','Dr. Maria Santos','Manila Doctors Hospital',NULL,'Recorded during Diagnosis visit on August 9, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(18,13,'2024-11-23','checkup','Routine health checkup','Fit for work','Rest and observation','Salbutamol inhaler - 2 puffs as needed',NULL,'{\"blood_pressure\":\"122\\/73\",\"heart_rate\":64,\"temperature\":36.6,\"respiratory_rate\":15,\"weight\":76,\"height\":164}','Dr. Carmen Ramos','NIA Health Center',NULL,'Recorded during Checkup visit on November 23, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(19,13,'2023-05-11','consultation','Follow-up consultation','Observation recommended','Home care instructions given','Cetirizine 10mg - 1 tab once daily',NULL,'{\"blood_pressure\":\"110\\/81\",\"heart_rate\":62,\"temperature\":36.5,\"respiratory_rate\":14,\"weight\":78,\"height\":180}','Dr. Maria Santos','Makati Medical Center','2023-06-05','Recorded during Consultation visit on May 11, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(20,13,'2023-10-09','checkup','Health maintenance visit','No significant findings','Home care instructions given','Salbutamol inhaler - 2 puffs as needed',NULL,'{\"blood_pressure\":\"111\\/75\",\"heart_rate\":89,\"temperature\":36.8,\"respiratory_rate\":18,\"weight\":73,\"height\":173}','Dr. Maria Santos','Makati Medical Center','2023-11-03','Recorded during Checkup visit on October 9, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(21,13,'2024-02-01','consultation','Second opinion','Observation recommended','Medication prescribed','Mefenamic acid 500mg - 1 cap every 8 hours',NULL,'{\"blood_pressure\":\"110\\/73\",\"heart_rate\":87,\"temperature\":36.9,\"respiratory_rate\":19,\"weight\":74,\"height\":170}','Dr. Jose Garcia','Manila Doctors Hospital','2024-02-12','Recorded during Consultation visit on February 1, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(22,13,'2024-08-23','diagnosis','Joint pain','Tension headache','Medication prescribed','Salbutamol inhaler - 2 puffs as needed',NULL,'{\"blood_pressure\":\"121\\/78\",\"heart_rate\":67,\"temperature\":36.2,\"respiratory_rate\":19,\"weight\":55,\"height\":170}','Dr. Jose Garcia','Manila Doctors Hospital','2024-08-31','Recorded during Diagnosis visit on August 23, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(23,13,'2025-01-03','consultation','Medical advice needed','Observation recommended','Home care instructions given','None prescribed - advised rest and hydration',NULL,'{\"blood_pressure\":\"122\\/75\",\"heart_rate\":84,\"temperature\":37.2,\"respiratory_rate\":14,\"weight\":54,\"height\":174}','Dr. Ana Reyes','Manila Doctors Hospital','2025-02-01','Recorded during Consultation visit on January 3, 2025',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(24,13,'2023-04-02','lab_test','Complete blood count','No abnormalities detected','Rest and observation','Ibuprofen 400mg - 1 tab every 8 hours after meals','CBC: WBC 7.2, RBC 5.1, Hgb 14.5, Hct 42%\nUrinalysis: Normal\nOther parameters: Within normal limits','{\"blood_pressure\":\"114\\/75\",\"heart_rate\":60,\"temperature\":37.3,\"respiratory_rate\":17,\"weight\":67,\"height\":150}','Dr. Maria Santos','St. Luke\'s Medical Center',NULL,'Recorded during Lab_test visit on April 2, 2023',1,'2025-10-19 16:43:51','2025-10-19 16:43:51'),(25,13,'2024-05-31','diagnosis','Back pain','Muscle strain','Follow-up in 2 weeks','Paracetamol 500mg - 1 tab every 6 hours as needed',NULL,'{\"blood_pressure\":\"127\\/74\",\"heart_rate\":61,\"temperature\":36.1,\"respiratory_rate\":18,\"weight\":50,\"height\":152}','Dr. Pedro Cruz','NIA Health Center',NULL,'Recorded during Diagnosis visit on May 31, 2024',1,'2025-10-19 16:43:51','2025-10-19 16:43:51');
/*!40000 ALTER TABLE `employee_medical_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_regularization`
--

DROP TABLE IF EXISTS `employee_regularization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_regularization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `current_status_id` int(11) NOT NULL,
  `date_of_hire` date NOT NULL,
  `probation_start_date` date NOT NULL,
  `probation_end_date` date NOT NULL,
  `regularization_review_date` date DEFAULT NULL,
  `regularization_date` date DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_current_status` (`current_status_id`),
  KEY `idx_probation_start` (`probation_start_date`),
  KEY `idx_regularization_review` (`regularization_review_date`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `employee_regularization_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_regularization_ibfk_2` FOREIGN KEY (`current_status_id`) REFERENCES `regularization_status` (`id`),
  CONSTRAINT `employee_regularization_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_regularization`
--

LOCK TABLES `employee_regularization` WRITE;
/*!40000 ALTER TABLE `employee_regularization` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_regularization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salaries`
--

DROP TABLE IF EXISTS `employee_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_salaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `employee_type` enum('employee','faculty') NOT NULL,
  `current_salary` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salaries`
--

LOCK TABLES `employee_salaries` WRITE;
/*!40000 ALTER TABLE `employee_salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_salary_history`
--

DROP TABLE IF EXISTS `employee_salary_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_salary_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL COMMENT 'Foreign key to employees table',
  `effective_date` date NOT NULL COMMENT 'Date when salary change took effect',
  `previous_salary` decimal(12,2) DEFAULT NULL COMMENT 'Previous salary amount',
  `new_salary` decimal(12,2) NOT NULL COMMENT 'New salary amount',
  `previous_grade` varchar(20) DEFAULT NULL COMMENT 'Previous salary grade',
  `new_grade` varchar(20) DEFAULT NULL COMMENT 'New salary grade',
  `change_type` enum('Promotion','Merit Increase','Grade Adjustment','Regularization','Cost of Living','Other') NOT NULL COMMENT 'Type of salary change',
  `change_reason` text DEFAULT NULL COMMENT 'Reason for salary change',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User ID who approved the change',
  `remarks` text DEFAULT NULL COMMENT 'Additional remarks',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_salary_history` (`employee_id`,`effective_date`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_change_type` (`change_type`),
  KEY `fk_salary_approved_by` (`approved_by`),
  CONSTRAINT `fk_salary_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_salary_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salary change history tracking for employees';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_salary_history`
--

LOCK TABLES `employee_salary_history` WRITE;
/*!40000 ALTER TABLE `employee_salary_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salary_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `employee_type` enum('faculty','staff','admin') DEFAULT 'staff',
  `hire_date` date NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `sss_number` varchar(20) DEFAULT NULL,
  `pagibig_number` varchar(20) DEFAULT NULL,
  `tin_number` varchar(20) DEFAULT NULL,
  `philhealth_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `blood_type` varchar(10) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `last_medical_checkup` date DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_department` (`department`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_department_id` (`department_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (12,'EMP-2024-001','Juan','Dela Cruz','juan.delacruz@nia.gov.ph','$2y$10$dYemFxmRPNC9PnTj3INT/eWhAtDW5VVZ0pw1vEgJ.oUsnBcqvCnyy','Senior Administrative Officer','Administration',NULL,'staff','2020-01-15','63','34-1234567-8','1234-5678-9012','123-456-789-000','12-345678901-2','123 Mabini St., Quezon City, Metro Manila',1,'2025-10-19 16:26:39','2025-10-19 16:26:39','O+','None','None','Metformin','2025-08-28','Annual physical examination completed','Juan Family','+63 973 613 4497'),(13,'EMP-2024-002','Maria','Santos','maria.santos@nia.gov.ph','$2y$10$QuE9nn1Fgz7Bb86ceyCYSOhBmtU4qoGt7hdJbttpkjn9Zl6/8ZTzW','HR Manager','Human Resources',NULL,'admin','2019-03-10','63','34-2345678-9','2345-6789-0123','234-567-890-000','23-456789012-3','456 Rizal Ave., Makati City, Metro Manila',1,'2025-10-19 16:26:39','2025-10-19 16:26:39','A+','None','None','Losartan','2025-07-13','Annual physical examination completed','Maria Family','+63 958 758 1918');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluation_sub_categories`
--

DROP TABLE IF EXISTS `evaluation_sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main_category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `order_number` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_by_role` enum('guidance_officer','human_resource','hr_manager','head','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `main_category_id` (`main_category_id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `order_number` (`order_number`),
  KEY `idx_evaluation_sub_categories_main_order` (`main_category_id`,`order_number`,`status`),
  KEY `idx_evaluation_sub_categories_created_by_role` (`created_by_role`),
  CONSTRAINT `fk_evaluation_sub_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evaluation_sub_categories_main` FOREIGN KEY (`main_category_id`) REFERENCES `main_evaluation_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluation_sub_categories`
--

LOCK TABLES `evaluation_sub_categories` WRITE;
/*!40000 ALTER TABLE `evaluation_sub_categories` DISABLE KEYS */;
INSERT INTO `evaluation_sub_categories` VALUES (1,1,'Classroom Management','Evaluation of teacher\'s ability to maintain order and create a conducive learning environment','active',1,1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:47:14'),(2,1,'Teaching Skills','Assessment of teacher\'s instructional methods and delivery','active',2,1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:47:14'),(3,1,'Subject Knowledge','Evaluation of teacher\'s mastery of the subject matter','active',3,1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:47:14'),(4,1,'Communication Skills','Assessment of teacher\'s ability to communicate effectively with students','active',4,1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:47:14'),(5,1,'Student Engagement','Evaluation of how well the teacher engages students in learning','active',5,1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:47:14'),(10,3,'Leadership','Assessment of leadership qualities and initiative','active',1,1,'human_resource','2025-08-10 15:14:16','2025-10-03 15:00:28'),(11,3,'Administrative Skills','Evaluation of administrative and organizational skills','active',2,1,'human_resource','2025-08-10 15:14:16','2025-10-03 15:00:28'),(12,3,'Professional Development','Assessment of continuous learning and growth','active',3,1,'human_resource','2025-08-10 15:14:16','2025-10-03 15:00:28'),(13,3,'Compliance','Evaluation of adherence to policies and procedures','active',4,1,'human_resource','2025-08-10 15:14:16','2025-10-03 15:00:28'),(18,5,'Professional Competence','Evaluation of colleague\'s professional skills and knowledge','active',1,1,'human_resource','2025-08-14 10:47:27','2025-10-03 14:58:42'),(19,5,'Collaboration','Assessment of teamwork and cooperation with colleagues','active',2,1,'human_resource','2025-08-14 10:47:27','2025-10-03 14:58:42'),(20,5,'Innovation','Evaluation of teaching innovations and creativity','active',3,1,'human_resource','2025-08-14 10:47:27','2025-10-03 14:58:42'),(21,5,'Mentoring','Assessment of ability to mentor and support other teachers','active',4,1,'human_resource','2025-08-14 10:47:27','2025-10-03 14:58:42');
/*!40000 ALTER TABLE `evaluation_sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `government_benefit_rates`
--

DROP TABLE IF EXISTS `government_benefit_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `government_benefit_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `benefit_type` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `benefit_category` enum('government','private','universal') DEFAULT 'universal',
  `salary_range_min` decimal(10,2) NOT NULL,
  `salary_range_max` decimal(10,2) NOT NULL,
  `employee_rate` decimal(10,2) NOT NULL,
  `employer_rate` decimal(10,2) DEFAULT 0.00,
  `is_percentage` tinyint(1) DEFAULT 0,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_benefit_type` (`benefit_type`),
  KEY `idx_salary_range` (`salary_range_min`,`salary_range_max`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `government_benefit_rates`
--

LOCK TABLES `government_benefit_rates` WRITE;
/*!40000 ALTER TABLE `government_benefit_rates` DISABLE KEYS */;
INSERT INTO `government_benefit_rates` VALUES (1,'sss','Social Security System contribution','private',3250.00,3749.99,135.00,517.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(2,'sss','Social Security System contribution','private',3750.00,4249.99,157.50,602.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(3,'sss','Social Security System contribution','private',4250.00,4749.99,180.00,687.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(4,'sss','Social Security System contribution','private',4750.00,5249.99,202.50,772.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(5,'sss','Social Security System contribution','private',5250.00,5749.99,225.00,857.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(6,'sss','Social Security System contribution','private',5750.00,6249.99,247.50,942.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(7,'sss','Social Security System contribution','private',6250.00,6749.99,270.00,1027.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(8,'sss','Social Security System contribution','private',6750.00,7249.99,292.50,1112.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(9,'sss','Social Security System contribution','private',7250.00,7749.99,315.00,1197.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(10,'sss','Social Security System contribution','private',7750.00,8249.99,337.50,1282.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(11,'sss','Social Security System contribution','private',8250.00,8749.99,360.00,1367.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(12,'sss','Social Security System contribution','private',8750.00,9249.99,382.50,1452.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(13,'sss','Social Security System contribution','private',9250.00,9749.99,405.00,1537.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(14,'sss','Social Security System contribution','private',9750.00,10249.99,427.50,1622.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(15,'sss','Social Security System contribution','private',10250.00,10749.99,450.00,1707.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(16,'sss','Social Security System contribution','private',10750.00,11249.99,472.50,1792.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(17,'sss','Social Security System contribution','private',11250.00,11749.99,495.00,1877.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(18,'sss','Social Security System contribution','private',11750.00,12249.99,517.50,1962.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(19,'sss','Social Security System contribution','private',12250.00,12749.99,540.00,2047.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(20,'sss','Social Security System contribution','private',12750.00,13249.99,562.50,2132.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(21,'sss','Social Security System contribution','private',13250.00,13749.99,585.00,2217.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(22,'sss','Social Security System contribution','private',13750.00,14249.99,607.50,2302.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(23,'sss','Social Security System contribution','private',14250.00,14749.99,630.00,2387.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(24,'sss','Social Security System contribution','private',14750.00,15249.99,652.50,2472.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(25,'sss','Social Security System contribution','private',15250.00,15749.99,675.00,2557.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(26,'sss','Social Security System contribution','private',15750.00,16249.99,697.50,2642.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(27,'sss','Social Security System contribution','private',16250.00,16749.99,720.00,2727.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(28,'sss','Social Security System contribution','private',16750.00,17249.99,742.50,2812.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(29,'sss','Social Security System contribution','private',17250.00,17749.99,765.00,2897.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(30,'sss','Social Security System contribution','private',17750.00,18249.99,787.50,2982.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(31,'sss','Social Security System contribution','private',18250.00,18749.99,810.00,3067.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(32,'sss','Social Security System contribution','private',18750.00,19249.99,832.50,3152.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(33,'sss','Social Security System contribution','private',19250.00,19749.99,855.00,3237.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(34,'sss','Social Security System contribution','private',19750.00,20249.99,877.50,3322.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(35,'sss','Social Security System contribution','private',20250.00,20749.99,900.00,3407.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(36,'sss','Social Security System contribution','private',20750.00,24999.99,900.00,3407.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(37,'sss','Social Security System contribution','private',25000.00,29999.99,1125.00,4262.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(38,'sss','Social Security System contribution','private',30000.00,50000.00,1350.00,5117.50,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(42,'pagibig','Pag-IBIG Fund contribution','universal',0.00,1500.00,1.00,2.00,1,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(43,'pagibig','Pag-IBIG Fund contribution','universal',1500.01,5000.00,2.00,2.00,1,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(44,'pagibig','Pag-IBIG Fund contribution','universal',5000.01,9999999.99,100.00,100.00,0,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:51:41'),(45,'gsis','GSIS Life Insurance Premium','government',0.00,9999999.99,1.50,0.00,1,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41'),(46,'gsis_ps','GSIS Personal Share (Employee)','government',0.00,9999999.99,9.00,12.00,1,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41'),(47,'gsis_optional','GSIS Optional Life Insurance','government',0.00,9999999.99,0.50,0.00,1,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41'),(48,'philhealth','PhilHealth - Below minimum','universal',0.00,10000.00,500.00,500.00,0,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41'),(49,'philhealth','PhilHealth - 5% premium rate','universal',10000.01,99999.99,2.50,2.50,1,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41'),(50,'philhealth','PhilHealth - Maximum cap','universal',100000.00,9999999.99,5000.00,5000.00,0,'2024-01-01',1,'2025-10-07 10:51:41','2025-10-07 10:51:41');
/*!40000 ALTER TABLE `government_benefit_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `increment_requests`
--

DROP TABLE IF EXISTS `increment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `increment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `employee_type` enum('employee','faculty') NOT NULL,
  `increment_type_id` int(11) NOT NULL,
  `current_salary` decimal(10,2) NOT NULL,
  `proposed_salary` decimal(10,2) NOT NULL,
  `increment_amount` decimal(10,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `increment_type_id` (`increment_type_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `increment_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `increment_requests_ibfk_2` FOREIGN KEY (`increment_type_id`) REFERENCES `increment_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `increment_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `increment_requests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `increment_requests`
--

LOCK TABLES `increment_requests` WRITE;
/*!40000 ALTER TABLE `increment_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `increment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `increment_types`
--

DROP TABLE IF EXISTS `increment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `increment_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `fixed_amount` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `increment_types`
--

LOCK TABLES `increment_types` WRITE;
/*!40000 ALTER TABLE `increment_types` DISABLE KEYS */;
INSERT INTO `increment_types` VALUES (1,'Regular Increment','Regular annual salary increment',5.00,NULL,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(2,'Performance Increment','Performance-based salary increment',10.00,NULL,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(3,'Promotion Increment','Salary increment due to promotion',NULL,5000.00,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(4,'Merit Increment','Merit-based salary increment',7.50,NULL,1,'2025-10-07 03:32:20','2025-10-07 03:32:20');
/*!40000 ALTER TABLE `increment_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_accumulation_history`
--

DROP TABLE IF EXISTS `leave_accumulation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_accumulation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `accumulated_days` decimal(5,2) NOT NULL,
  `accumulation_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  KEY `year_month` (`year`,`month`),
  CONSTRAINT `leave_accumulation_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_accumulation_history_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_accumulation_history`
--

LOCK TABLES `leave_accumulation_history` WRITE;
/*!40000 ALTER TABLE `leave_accumulation_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_accumulation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_notifications`
--

DROP TABLE IF EXISTS `leave_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_request_id` int(11) DEFAULT NULL,
  `notification_type` enum('approval','rejection','reminder','expiry','balance_low') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_request_id` (`leave_request_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `leave_notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_notifications_ibfk_2` FOREIGN KEY (`leave_request_id`) REFERENCES `employee_leave_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_notifications`
--

LOCK TABLES `leave_notifications` WRITE;
/*!40000 ALTER TABLE `leave_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `max_days_per_year` int(11) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Vacation Leave','Annual vacation leave',15,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(2,'Sick Leave','Medical leave for illness',15,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(3,'Emergency Leave','Emergency situations',3,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(4,'Maternity Leave','Maternity leave for female employees',105,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(5,'Paternity Leave','Paternity leave for male employees',7,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20'),(6,'Study Leave','Leave for educational purposes',30,1,1,'2025-10-07 03:32:20','2025-10-07 03:32:20');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `main_evaluation_categories`
--

DROP TABLE IF EXISTS `main_evaluation_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `main_evaluation_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `evaluation_type` enum('student_to_teacher','peer_to_peer','head_to_teacher') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_by_role` enum('guidance_officer','human_resource','hr_manager','head','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `idx_main_evaluation_categories_type_status` (`evaluation_type`,`status`),
  KEY `idx_main_evaluation_categories_created_by_role` (`created_by_role`),
  CONSTRAINT `fk_main_evaluation_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `main_evaluation_categories`
--

LOCK TABLES `main_evaluation_categories` WRITE;
/*!40000 ALTER TABLE `main_evaluation_categories` DISABLE KEYS */;
INSERT INTO `main_evaluation_categories` VALUES (1,'Student to Teacher Evaluation','Students evaluate their teachers on various aspects of teaching and classroom management','student_to_teacher','active',1,'guidance_officer','2025-08-10 15:14:16','2025-10-03 14:46:21'),(3,'Head to Teacher Evaluation','Department heads and administrators evaluate teachers on leadership and administrative skills','head_to_teacher','active',1,'human_resource','2025-08-10 15:14:16','2025-10-03 15:00:24'),(5,'Peer to Peer Evaluation','Teachers evaluate their colleagues on professional competence and collaboration','peer_to_peer','active',1,'human_resource','2025-08-14 10:47:27','2025-10-03 14:58:39');
/*!40000 ALTER TABLE `main_evaluation_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_adjustments`
--

DROP TABLE IF EXISTS `payroll_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_record_id` int(11) NOT NULL,
  `adjustment_type` enum('addition','deduction') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  KEY `idx_payroll_record` (`payroll_record_id`),
  CONSTRAINT `payroll_adjustments_ibfk_1` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_adjustments_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payroll_adjustments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_adjustments`
--

LOCK TABLES `payroll_adjustments` WRITE;
/*!40000 ALTER TABLE `payroll_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_audit_log`
--

DROP TABLE IF EXISTS `payroll_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_period_id` int(11) DEFAULT NULL,
  `payroll_record_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `performed_by` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payroll_record_id` (`payroll_record_id`),
  KEY `performed_by` (`performed_by`),
  KEY `idx_period` (`payroll_period_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `payroll_audit_log_ibfk_1` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_audit_log_ibfk_2` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_audit_log_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_audit_log`
--

LOCK TABLES `payroll_audit_log` WRITE;
/*!40000 ALTER TABLE `payroll_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_custom_deductions`
--

DROP TABLE IF EXISTS `payroll_custom_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_custom_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_record_id` int(11) NOT NULL,
  `deduction_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `deduction_type_id` (`deduction_type_id`),
  KEY `idx_payroll_record` (`payroll_record_id`),
  CONSTRAINT `payroll_custom_deductions_ibfk_1` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_custom_deductions_ibfk_2` FOREIGN KEY (`deduction_type_id`) REFERENCES `payroll_deduction_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_custom_deductions`
--

LOCK TABLES `payroll_custom_deductions` WRITE;
/*!40000 ALTER TABLE `payroll_custom_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_custom_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_custom_earnings`
--

DROP TABLE IF EXISTS `payroll_custom_earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_custom_earnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_record_id` int(11) NOT NULL,
  `earning_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `earning_type_id` (`earning_type_id`),
  KEY `idx_payroll_record` (`payroll_record_id`),
  CONSTRAINT `payroll_custom_earnings_ibfk_1` FOREIGN KEY (`payroll_record_id`) REFERENCES `payroll_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_custom_earnings_ibfk_2` FOREIGN KEY (`earning_type_id`) REFERENCES `payroll_earning_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_custom_earnings`
--

LOCK TABLES `payroll_custom_earnings` WRITE;
/*!40000 ALTER TABLE `payroll_custom_earnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_custom_earnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_deduction_types`
--

DROP TABLE IF EXISTS `payroll_deduction_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_deduction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('mandatory','government','loan','attendance','other') DEFAULT 'other',
  `is_percentage` tinyint(1) DEFAULT 0,
  `default_value` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deduction_types`
--

LOCK TABLES `payroll_deduction_types` WRITE;
/*!40000 ALTER TABLE `payroll_deduction_types` DISABLE KEYS */;
INSERT INTO `payroll_deduction_types` VALUES (1,'SSS','SSS Contribution','Social Security System (Private Sector employees only)','mandatory',0,0.00,1,4,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(2,'PHIC','PhilHealth Contribution','Philippine Health Insurance mandatory contribution','mandatory',0,0.00,1,5,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(3,'HDMF','Pag-IBIG Contribution','Home Development Mutual Fund mandatory contribution','mandatory',0,0.00,1,6,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(4,'WTAX','Withholding Tax','Income tax withholding','mandatory',0,0.00,1,7,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(5,'SSS_LOAN','SSS Loan','SSS salary loan deduction','loan',0,0.00,1,8,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(6,'HDMF_LOAN','Pag-IBIG Loan','Pag-IBIG housing loan deduction','loan',0,0.00,1,9,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(7,'SALARY_LOAN','Salary Loan','Company salary loan deduction','loan',0,0.00,1,10,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(8,'LATE','Late Deduction','Deduction for late arrivals','attendance',0,0.00,1,11,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(9,'UNDERTIME','Undertime Deduction','Deduction for undertime','attendance',0,0.00,1,12,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(10,'ABSENCE','Absence Deduction','Deduction for absences','attendance',0,0.00,1,13,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(11,'UNIFORMS','Uniforms','Uniform purchase deduction','other',0,0.00,1,14,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(12,'CASH_ADVANCE','Cash Advance','Cash advance deduction','other',0,0.00,1,15,'2025-10-07 09:49:04','2025-10-07 11:53:54'),(13,'GSIS_LIFE','GSIS Life Insurance','GSIS Life Insurance Premium (1.5% of basic salary)','government',1,1.50,1,1,'2025-10-07 11:53:54','2025-10-07 11:53:54'),(14,'GSIS_PS','GSIS Personal Share','GSIS Personal Share - Employee Contribution (9% of basic salary)','government',1,9.00,1,2,'2025-10-07 11:53:54','2025-10-07 11:53:54'),(15,'GSIS_OPTIONAL','GSIS Optional Life','GSIS Optional Life Insurance (0.5% of basic salary)','government',1,0.50,1,3,'2025-10-07 11:53:54','2025-10-07 11:53:54');
/*!40000 ALTER TABLE `payroll_deduction_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_earning_types`
--

DROP TABLE IF EXISTS `payroll_earning_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_earning_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('regular','overtime','allowance','bonus','other') DEFAULT 'other',
  `is_taxable` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_earning_types`
--

LOCK TABLES `payroll_earning_types` WRITE;
/*!40000 ALTER TABLE `payroll_earning_types` DISABLE KEYS */;
INSERT INTO `payroll_earning_types` VALUES (1,'BASIC','Basic Pay','Regular monthly salary','regular',1,1,1,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(2,'OVERTIME','Overtime Pay','Overtime hours payment (1.25x)','overtime',1,1,2,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(3,'NIGHT_DIFF','Night Differential','Night shift differential (10%)','overtime',1,1,3,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(4,'HOLIDAY','Holiday Pay','Holiday premium pay','overtime',1,1,4,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(5,'REST_DAY','Rest Day Pay','Rest day premium pay','overtime',1,1,5,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(6,'ALLOWANCE','Allowances','Monthly allowances','allowance',1,1,6,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(7,'13TH_MONTH','13th Month Pay','13th month pay (tax-exempt up to 90k)','bonus',0,1,7,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(8,'PERFORMANCE_BONUS','Performance Bonus','Performance-based bonus','bonus',1,1,8,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(9,'RICE_SUBSIDY','Rice Subsidy','Rice subsidy allowance','allowance',0,1,9,'2025-10-07 09:49:04','2025-10-07 09:49:04'),(10,'TRANSPORTATION','Transportation Allowance','Transportation allowance','allowance',0,1,10,'2025-10-07 09:49:04','2025-10-07 09:49:04');
/*!40000 ALTER TABLE `payroll_earning_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) NOT NULL,
  `period_type` enum('monthly','semi-monthly','bi-weekly','weekly') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('draft','open','processing','calculated','approved','paid','closed') DEFAULT 'draft',
  `total_employees` int(11) DEFAULT 0,
  `total_gross_pay` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net_pay` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_period_dates` (`start_date`,`end_date`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payroll_periods_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
INSERT INTO `payroll_periods` VALUES (1,'April 2025 - 1st Half','semi-monthly','2025-04-01','2025-04-15','2025-04-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(2,'April 2025 - 2nd Half','semi-monthly','2025-04-16','2025-04-30','2025-05-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(3,'May 2025 - 1st Half','semi-monthly','2025-05-01','2025-05-15','2025-05-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(4,'May 2025 - 2nd Half','semi-monthly','2025-05-16','2025-05-31','2025-06-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(5,'June 2025 - 1st Half','semi-monthly','2025-06-01','2025-06-15','2025-06-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(6,'June 2025 - 2nd Half','semi-monthly','2025-06-16','2025-06-30','2025-07-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(7,'July 2025 - 1st Half','semi-monthly','2025-07-01','2025-07-15','2025-07-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(8,'July 2025 - 2nd Half','semi-monthly','2025-07-16','2025-07-31','2025-08-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(9,'August 2025 - 1st Half','semi-monthly','2025-08-01','2025-08-15','2025-08-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(10,'August 2025 - 2nd Half','semi-monthly','2025-08-16','2025-08-31','2025-09-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(11,'September 2025 - 1st Half','semi-monthly','2025-09-01','2025-09-15','2025-09-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(12,'September 2025 - 2nd Half','semi-monthly','2025-09-16','2025-09-30','2025-10-05','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(13,'October 2025 - 1st Half','semi-monthly','2025-10-01','2025-10-15','2025-10-20','closed',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(14,'October 2025 - 2nd Half','semi-monthly','2025-10-16','2025-10-31','2025-11-05','open',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(15,'November 2025 - 1st Half','semi-monthly','2025-11-01','2025-11-15','2025-11-20','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(16,'November 2025 - 2nd Half','semi-monthly','2025-11-16','2025-11-30','2025-12-05','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(17,'December 2025 - 1st Half','semi-monthly','2025-12-01','2025-12-15','2025-12-20','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(18,'December 2025 - 2nd Half','semi-monthly','2025-12-16','2025-12-31','2026-01-05','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(19,'January 2026 - 1st Half','semi-monthly','2026-01-01','2026-01-15','2026-01-20','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44'),(20,'January 2026 - 2nd Half','semi-monthly','2026-01-16','2026-01-31','2026-02-05','draft',0,0.00,0.00,0.00,NULL,1,NULL,NULL,'2025-10-19 17:26:44','2025-10-19 17:26:44');
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_period_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(200) NOT NULL,
  `employee_number` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `regular_hours` decimal(8,2) DEFAULT 0.00,
  `overtime_hours` decimal(8,2) DEFAULT 0.00,
  `night_diff_hours` decimal(8,2) DEFAULT 0.00,
  `holiday_hours` decimal(8,2) DEFAULT 0.00,
  `rest_day_hours` decimal(8,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `monthly_rate` decimal(12,2) NOT NULL,
  `overtime_rate` decimal(10,2) DEFAULT 0.00,
  `night_diff_rate` decimal(10,2) DEFAULT 0.00,
  `basic_pay` decimal(12,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `night_diff_pay` decimal(10,2) DEFAULT 0.00,
  `holiday_pay` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `bonuses` decimal(10,2) DEFAULT 0.00,
  `other_earnings` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) DEFAULT 0.00,
  `sss_contribution` decimal(10,2) DEFAULT 0.00,
  `philhealth_contribution` decimal(10,2) DEFAULT 0.00,
  `pagibig_contribution` decimal(10,2) DEFAULT 0.00,
  `withholding_tax` decimal(10,2) DEFAULT 0.00,
  `sss_loan` decimal(10,2) DEFAULT 0.00,
  `pagibig_loan` decimal(10,2) DEFAULT 0.00,
  `salary_loan` decimal(10,2) DEFAULT 0.00,
  `late_deduction` decimal(10,2) DEFAULT 0.00,
  `undertime_deduction` decimal(10,2) DEFAULT 0.00,
  `absences_deduction` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','calculated','approved','paid','void') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_period` (`payroll_period_id`,`employee_id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_review_categories`
--

DROP TABLE IF EXISTS `performance_review_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_review_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `weight_percentage` decimal(5,2) DEFAULT 0.00,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_review_categories`
--

LOCK TABLES `performance_review_categories` WRITE;
/*!40000 ALTER TABLE `performance_review_categories` DISABLE KEYS */;
INSERT INTO `performance_review_categories` VALUES (1,'Job Knowledge','Understanding of job responsibilities and technical skills',20.00,1,1,'2025-10-07 05:38:02'),(2,'Quality of Work','Accuracy, thoroughness, and excellence',25.00,2,1,'2025-10-07 05:38:02'),(3,'Productivity','Efficiency and timeliness in completing tasks',20.00,3,1,'2025-10-07 05:38:02'),(4,'Communication','Verbal and written communication effectiveness',15.00,4,1,'2025-10-07 05:38:02'),(5,'Teamwork','Collaboration and interpersonal skills',10.00,5,1,'2025-10-07 05:38:02'),(6,'Initiative','Proactive behavior and problem-solving',10.00,6,1,'2025-10-07 05:38:02'),(7,'Job Knowledge','Understanding of job responsibilities and technical skills',20.00,1,1,'2025-10-07 05:39:08'),(8,'Quality of Work','Accuracy, thoroughness, and excellence',25.00,2,1,'2025-10-07 05:39:08'),(9,'Productivity','Efficiency and timeliness in completing tasks',20.00,3,1,'2025-10-07 05:39:08'),(10,'Communication','Verbal and written communication effectiveness',15.00,4,1,'2025-10-07 05:39:08'),(11,'Teamwork','Collaboration and interpersonal skills',10.00,5,1,'2025-10-07 05:39:08'),(12,'Initiative','Proactive behavior and problem-solving',10.00,6,1,'2025-10-07 05:39:08');
/*!40000 ALTER TABLE `performance_review_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_review_criteria`
--

DROP TABLE IF EXISTS `performance_review_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_review_criteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `criteria_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `max_score` int(11) DEFAULT 5,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `performance_review_criteria_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `performance_review_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_review_criteria`
--

LOCK TABLES `performance_review_criteria` WRITE;
/*!40000 ALTER TABLE `performance_review_criteria` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_review_criteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_review_goals`
--

DROP TABLE IF EXISTS `performance_review_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_review_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `performance_review_id` int(11) NOT NULL,
  `goal_description` text NOT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completion_date` date DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `performance_review_id` (`performance_review_id`),
  CONSTRAINT `performance_review_goals_ibfk_1` FOREIGN KEY (`performance_review_id`) REFERENCES `performance_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_review_goals`
--

LOCK TABLES `performance_review_goals` WRITE;
/*!40000 ALTER TABLE `performance_review_goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_review_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_review_scores`
--

DROP TABLE IF EXISTS `performance_review_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_review_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `performance_review_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `performance_review_id` (`performance_review_id`),
  KEY `criteria_id` (`criteria_id`),
  CONSTRAINT `performance_review_scores_ibfk_1` FOREIGN KEY (`performance_review_id`) REFERENCES `performance_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `performance_review_scores_ibfk_2` FOREIGN KEY (`criteria_id`) REFERENCES `performance_review_criteria` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_review_scores`
--

LOCK TABLES `performance_review_scores` WRITE;
/*!40000 ALTER TABLE `performance_review_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_review_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_reviews`
--

DROP TABLE IF EXISTS `performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `employee_type` enum('faculty','staff','admin') NOT NULL DEFAULT 'faculty',
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `review_type` enum('annual','semi_annual','quarterly','probationary','promotion','special') NOT NULL DEFAULT 'annual',
  `status` enum('draft','in_progress','completed','approved','rejected') NOT NULL DEFAULT 'draft',
  `overall_rating` decimal(5,2) DEFAULT NULL,
  `overall_percentage` decimal(5,2) DEFAULT NULL,
  `goals_achieved` text DEFAULT NULL,
  `areas_of_strength` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `development_plan` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `manager_comments` text DEFAULT NULL,
  `employee_comments` text DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_review_employee` (`employee_id`),
  KEY `fk_review_reviewer` (`reviewer_id`),
  KEY `fk_review_approved_by` (`approved_by`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_review_type` (`review_type`),
  KEY `idx_status` (`status`),
  KEY `idx_review_period` (`review_period_start`,`review_period_end`),
  KEY `idx_performance_reviews_employee_period` (`employee_id`,`review_period_start`,`review_period_end`),
  KEY `idx_performance_reviews_reviewer_status` (`reviewer_id`,`status`),
  KEY `idx_performance_reviews_type_status` (`review_type`,`status`),
  CONSTRAINT `fk_review_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_review_employee` FOREIGN KEY (`employee_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reviews`
--

LOCK TABLES `performance_reviews` WRITE;
/*!40000 ALTER TABLE `performance_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regularization_criteria`
--

DROP TABLE IF EXISTS `regularization_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regularization_criteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `criteria_name` varchar(255) NOT NULL,
  `criteria_description` text DEFAULT NULL,
  `minimum_months` int(11) DEFAULT 6,
  `performance_rating_min` decimal(3,2) DEFAULT 3.00,
  `attendance_percentage_min` decimal(5,2) DEFAULT 95.00,
  `disciplinary_issues_max` int(11) DEFAULT 0,
  `training_completion_required` tinyint(1) DEFAULT 0,
  `evaluation_score_min` decimal(5,2) DEFAULT 75.00,
  `additional_requirements` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `created_at` (`created_at`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `regularization_criteria_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `regularization_criteria_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regularization_criteria`
--

LOCK TABLES `regularization_criteria` WRITE;
/*!40000 ALTER TABLE `regularization_criteria` DISABLE KEYS */;
INSERT INTO `regularization_criteria` VALUES (1,'Standard Employee Regularization','Standard criteria for employee regularization in government service',6,3.00,95.00,0,1,75.00,NULL,1,'2025-10-07 05:32:11','2025-10-07 05:32:11',NULL,NULL),(2,'Standard Employee Regularization','Standard criteria for employee regularization in government service',6,3.00,95.00,0,1,75.00,NULL,1,'2025-10-07 05:39:08','2025-10-07 05:39:08',NULL,NULL);
/*!40000 ALTER TABLE `regularization_criteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regularization_notifications`
--

DROP TABLE IF EXISTS `regularization_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regularization_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `notification_type` enum('upcoming_review','review_due','probation_ending','regularized','extended','terminated') NOT NULL,
  `notification_date` date NOT NULL,
  `is_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `notification_date` (`notification_date`),
  KEY `is_sent` (`is_sent`),
  CONSTRAINT `regularization_notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regularization_notifications`
--

LOCK TABLES `regularization_notifications` WRITE;
/*!40000 ALTER TABLE `regularization_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `regularization_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regularization_reviews`
--

DROP TABLE IF EXISTS `regularization_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regularization_reviews` (
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
  `review_comments` text DEFAULT NULL,
  `recommendation` enum('approve','extend','terminate') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_regularization_id` (`employee_regularization_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `status_id` (`status_id`),
  KEY `review_date` (`review_date`),
  CONSTRAINT `regularization_reviews_ibfk_1` FOREIGN KEY (`employee_regularization_id`) REFERENCES `employee_regularization` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regularization_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `regularization_reviews_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `regularization_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regularization_reviews`
--

LOCK TABLES `regularization_reviews` WRITE;
/*!40000 ALTER TABLE `regularization_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `regularization_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regularization_status`
--

DROP TABLE IF EXISTS `regularization_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regularization_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#6B7280',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regularization_status`
--

LOCK TABLES `regularization_status` WRITE;
/*!40000 ALTER TABLE `regularization_status` DISABLE KEYS */;
INSERT INTO `regularization_status` VALUES (1,'Probationary','Currently in probation period','#F59E0B',1,'2025-10-07 05:31:56'),(2,'Under Review','Currently under review for regularization','#3B82F6',1,'2025-10-07 05:31:56'),(3,'Regular','Successfully regularized','#10B981',1,'2025-10-07 05:31:56'),(4,'Extended Probation','Probation period extended','#F97316',1,'2025-10-07 05:31:56'),(5,'Terminated','Employment terminated','#EF4444',1,'2025-10-07 05:31:56'),(6,'Pending Review','Awaiting review process','#8B5CF6',1,'2025-10-07 05:31:56'),(7,'Resigned','Employee has resigned from position','#6B7280',1,'2025-10-07 05:31:56');
/*!40000 ALTER TABLE `regularization_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_audit_log`
--

DROP TABLE IF EXISTS `salary_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `action_type` enum('view','update','delete','export') NOT NULL,
  `field_changed` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `performed_by` (`performed_by`),
  KEY `action_type` (`action_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `salary_audit_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_audit_log_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_audit_log`
--

LOCK TABLES `salary_audit_log` WRITE;
/*!40000 ALTER TABLE `salary_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_increments`
--

DROP TABLE IF EXISTS `salary_increments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_increments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `salary_structure_id` int(11) NOT NULL,
  `current_salary` decimal(10,2) NOT NULL,
  `increment_amount` decimal(10,2) NOT NULL,
  `new_salary` decimal(10,2) NOT NULL,
  `increment_percentage` decimal(5,2) NOT NULL,
  `increment_type` enum('regular','promotion','merit','cost_of_living','special') DEFAULT 'regular',
  `incrementation_name` varchar(100) DEFAULT NULL,
  `incrementation_description` text DEFAULT NULL,
  `incrementation_amount` decimal(10,2) DEFAULT NULL,
  `incrementation_frequency_years` int(11) DEFAULT 1,
  `effective_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','implemented') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `salary_structure_id` (`salary_structure_id`),
  KEY `effective_date` (`effective_date`),
  KEY `status` (`status`),
  KEY `approved_by` (`approved_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `salary_increments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_increments_ibfk_2` FOREIGN KEY (`salary_structure_id`) REFERENCES `salary_structures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_increments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `salary_increments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_increments`
--

LOCK TABLES `salary_increments` WRITE;
/*!40000 ALTER TABLE `salary_increments` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_increments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_title` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `minimum_salary` decimal(10,2) NOT NULL,
  `maximum_salary` decimal(10,2) NOT NULL,
  `increment_percentage` decimal(5,2) DEFAULT 5.00,
  `increment_frequency` enum('annual','bi-annual','quarterly','monthly') DEFAULT 'annual',
  `incrementation_name` varchar(100) DEFAULT NULL,
  `incrementation_description` text DEFAULT NULL,
  `incrementation_amount` decimal(10,2) DEFAULT NULL,
  `incrementation_frequency_years` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_department` (`department`),
  KEY `idx_grade_level` (`grade_level`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structures`
--

LOCK TABLES `salary_structures` WRITE;
/*!40000 ALTER TABLE `salary_structures` DISABLE KEYS */;
INSERT INTO `salary_structures` VALUES (1,'Administrative Aide I','Administration','SG-1',13000.00,13000.00,15000.00,3.00,'annual','0','Annual step increment',390.00,3,1,1,'2025-10-19 14:50:37',NULL),(2,'Administrative Aide II','Administration','SG-2',13572.00,13572.00,16000.00,3.00,'annual','0','Annual step increment',407.16,3,1,1,'2025-10-19 14:50:37',NULL),(3,'Administrative Aide III','Administration','SG-3',14159.00,14159.00,17000.00,3.00,'annual','0','Annual step increment',424.77,3,1,1,'2025-10-19 14:50:37',NULL),(4,'Administrative Assistant I','Administration','SG-4',14762.00,14762.00,18500.00,3.00,'annual','0','Annual step increment',442.86,3,1,1,'2025-10-19 14:50:37',NULL),(5,'Administrative Assistant II','Administration','SG-5',15380.00,15380.00,20000.00,3.00,'annual','0','Annual step increment',461.40,3,1,1,'2025-10-19 14:50:37',NULL),(6,'Administrative Assistant III','Administration','SG-6',16019.00,16019.00,21500.00,3.00,'annual','0','Annual step increment',480.57,3,1,1,'2025-10-19 14:50:37',NULL),(7,'Administrative Officer I','Administration','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(8,'Administrative Officer II','Administration','SG-9',18426.00,18426.00,26000.00,3.00,'annual','0','Annual step increment',552.78,3,1,1,'2025-10-19 14:50:37',NULL),(9,'Administrative Officer III','Administration','SG-11',20179.00,20179.00,30000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(10,'Administrative Officer IV','Administration','SG-12',21024.00,21024.00,32000.00,3.00,'annual','0','Annual step increment',630.72,3,1,1,'2025-10-19 14:50:37',NULL),(11,'Administrative Officer V','Administration','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(12,'Records Officer I','Human Resources','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(13,'Records Officer II','Human Resources','SG-9',18426.00,18426.00,26000.00,3.00,'annual','0','Annual step increment',552.78,3,1,1,'2025-10-19 14:50:37',NULL),(14,'HR Assistant I','Human Resources','SG-6',16019.00,16019.00,21500.00,3.00,'annual','0','Annual step increment',480.57,3,1,1,'2025-10-19 14:50:37',NULL),(15,'HR Assistant II','Human Resources','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(16,'HR Officer I','Human Resources','SG-11',20179.00,20179.00,30000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(17,'HR Officer II','Human Resources','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(18,'HR Officer III','Human Resources','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(19,'Accounting Clerk I','Finance','SG-4',14762.00,14762.00,18500.00,3.00,'annual','0','Annual step increment',442.86,3,1,1,'2025-10-19 14:50:37',NULL),(20,'Accounting Clerk II','Finance','SG-5',15380.00,15380.00,20000.00,3.00,'annual','0','Annual step increment',461.40,3,1,1,'2025-10-19 14:50:37',NULL),(21,'Bookkeeper I','Finance','SG-6',16019.00,16019.00,21500.00,3.00,'annual','0','Annual step increment',480.57,3,1,1,'2025-10-19 14:50:37',NULL),(22,'Bookkeeper II','Finance','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(23,'Accountant I','Finance','SG-11',20179.00,20179.00,30000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(24,'Accountant II','Finance','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(25,'Accountant III','Finance','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(26,'Budget Officer I','Finance','SG-11',20179.00,20179.00,30000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(27,'Budget Officer II','Finance','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(28,'Nurse I','Medical Services','SG-11',20179.00,20179.00,32000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(29,'Nurse II','Medical Services','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(30,'Nurse III','Medical Services','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(31,'Nurse IV','Medical Services','SG-17',27000.00,27000.00,45000.00,3.00,'annual','0','Annual step increment',810.00,3,1,1,'2025-10-19 14:50:37',NULL),(32,'Senior Nurse','Medical Services','SG-18',28164.00,28164.00,48000.00,3.00,'annual','0','Annual step increment',844.92,3,1,1,'2025-10-19 14:50:37',NULL),(33,'Chief Nurse','Medical Services','SG-19',29359.00,29359.00,52000.00,3.00,'annual','0','Annual step increment',880.77,3,1,1,'2025-10-19 14:50:37',NULL),(34,'Public Health Nurse I','Medical Services','SG-11',20179.00,20179.00,32000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(35,'Public Health Nurse II','Medical Services','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(36,'Computer Operator I','Information Technology','SG-6',16019.00,16019.00,21500.00,3.00,'annual','0','Annual step increment',480.57,3,1,1,'2025-10-19 14:50:37',NULL),(37,'Computer Operator II','Information Technology','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(38,'IT Assistant','Information Technology','SG-9',18426.00,18426.00,26000.00,3.00,'annual','0','Annual step increment',552.78,3,1,1,'2025-10-19 14:50:37',NULL),(39,'IT Specialist I','Information Technology','SG-11',20179.00,20179.00,30000.00,3.00,'annual','0','Annual step increment',605.37,3,1,1,'2025-10-19 14:50:37',NULL),(40,'IT Specialist II','Information Technology','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(41,'IT Specialist III','Information Technology','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(42,'Systems Analyst I','Information Technology','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(43,'Systems Analyst II','Information Technology','SG-17',27000.00,27000.00,45000.00,3.00,'annual','0','Annual step increment',810.00,3,1,1,'2025-10-19 14:50:37',NULL),(44,'Supervising Administrative Officer','Administration','SG-15',24316.00,24316.00,42000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(45,'Chief Administrative Officer','Administration','SG-18',28164.00,28164.00,52000.00,3.00,'annual','0','Annual step increment',844.92,3,1,1,'2025-10-19 14:50:37',NULL),(46,'Division Chief','Administration','SG-22',43415.00,43415.00,75000.00,3.00,'annual','0','Annual step increment',1302.45,3,1,1,'2025-10-19 14:50:37',NULL),(47,'Assistant Department Manager','Administration','SG-24',54251.00,54251.00,95000.00,3.00,'annual','0','Annual step increment',1627.53,3,1,1,'2025-10-19 14:50:37',NULL),(48,'Department Manager','Administration','SG-25',60021.00,60021.00,110000.00,3.00,'annual','0','Annual step increment',1800.63,3,1,1,'2025-10-19 14:50:37',NULL),(49,'Legal Assistant','Legal','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(50,'Attorney I','Legal','SG-18',28164.00,28164.00,50000.00,3.00,'annual','0','Annual step increment',844.92,3,1,1,'2025-10-19 14:50:37',NULL),(51,'Attorney II','Legal','SG-19',29359.00,29359.00,55000.00,3.00,'annual','0','Annual step increment',880.77,3,1,1,'2025-10-19 14:50:37',NULL),(52,'Attorney III','Legal','SG-20',30590.00,30590.00,60000.00,3.00,'annual','0','Annual step increment',917.70,3,1,1,'2025-10-19 14:50:37',NULL),(53,'Attorney IV','Legal','SG-22',43415.00,43415.00,80000.00,3.00,'annual','0','Annual step increment',1302.45,3,1,1,'2025-10-19 14:50:37',NULL),(54,'Engineering Assistant','Engineering','SG-8',17679.00,17679.00,24000.00,3.00,'annual','0','Annual step increment',530.37,3,1,1,'2025-10-19 14:50:37',NULL),(55,'Engineer I','Engineering','SG-13',21901.00,21901.00,35000.00,3.00,'annual','0','Annual step increment',657.03,3,1,1,'2025-10-19 14:50:37',NULL),(56,'Engineer II','Engineering','SG-15',24316.00,24316.00,40000.00,3.00,'annual','0','Annual step increment',729.48,3,1,1,'2025-10-19 14:50:37',NULL),(57,'Engineer III','Engineering','SG-17',27000.00,27000.00,45000.00,3.00,'annual','0','Annual step increment',810.00,3,1,1,'2025-10-19 14:50:37',NULL),(58,'Engineer IV','Engineering','SG-19',29359.00,29359.00,52000.00,3.00,'annual','0','Annual step increment',880.77,3,1,1,'2025-10-19 14:50:37',NULL),(59,'Senior Engineer','Engineering','SG-22',43415.00,43415.00,75000.00,3.00,'annual','0','Annual step increment',1302.45,3,1,1,'2025-10-19 14:50:37',NULL),(60,'Executive Assistant I','Executive Office','SG-18',28164.00,28164.00,50000.00,3.00,'annual','0','Annual step increment',844.92,3,1,1,'2025-10-19 14:50:37',NULL),(61,'Executive Assistant II','Executive Office','SG-20',30590.00,30590.00,55000.00,3.00,'annual','0','Annual step increment',917.70,3,1,1,'2025-10-19 14:50:37',NULL),(62,'Assistant Director','Executive Office','SG-26',66374.00,66374.00,125000.00,3.00,'annual','0','Annual step increment',1991.22,3,1,1,'2025-10-19 14:50:37',NULL),(63,'Director','Executive Office','SG-27',73402.00,73402.00,150000.00,3.00,'annual','0','Annual step increment',2202.06,3,1,1,'2025-10-19 14:50:37',NULL),(64,'Assistant Administrator','Executive Office','SG-29',88611.00,88611.00,180000.00,3.00,'annual','0','Annual step increment',2658.33,3,1,1,'2025-10-19 14:50:37',NULL),(65,'Administrator','Executive Office','SG-30',98087.00,98087.00,200000.00,3.00,'annual','0','Annual step increment',2942.61,3,1,1,'2025-10-19 14:50:37',NULL);
/*!40000 ALTER TABLE `salary_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'organization_name','NIA','Organization Name','2025-10-07 03:25:50','2025-10-07 03:25:50'),(2,'organization_logo','','Organization Logo Path','2025-10-07 03:25:50','2025-10-07 03:25:50'),(3,'site_title','NIA-HRIS','Site Title','2025-10-07 03:25:50','2025-10-07 03:25:50'),(4,'site_description','NIA Human Resource Information System','Site Description','2025-10-07 03:25:50','2025-10-07 03:25:50');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_brackets`
--

DROP TABLE IF EXISTS `tax_brackets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_brackets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bracket_name` varchar(100) NOT NULL,
  `income_min` decimal(10,2) NOT NULL,
  `income_max` decimal(10,2) DEFAULT NULL,
  `base_tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL,
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_income_range` (`income_min`,`income_max`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_brackets`
--

LOCK TABLES `tax_brackets` WRITE;
/*!40000 ALTER TABLE `tax_brackets` DISABLE KEYS */;
INSERT INTO `tax_brackets` VALUES (1,'Below ₱250,000',0.00,250000.00,0.00,0.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33'),(2,'₱250,000 - ₱400,000',250000.00,400000.00,0.00,15.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33'),(3,'₱400,000 - ₱800,000',400000.00,800000.00,22500.00,20.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33'),(4,'₱800,000 - ₱2,000,000',800000.00,2000000.00,102500.00,25.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33'),(5,'₱2,000,000 - ₱8,000,000',2000000.00,8000000.00,402500.00,30.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33'),(6,'Above ₱8,000,000',8000000.00,NULL,2202500.00,35.00,'2024-01-01',1,'2025-10-07 10:32:33','2025-10-07 10:32:33');
/*!40000 ALTER TABLE `tax_brackets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_categories`
--

DROP TABLE IF EXISTS `training_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_by_role` enum('guidance_officer','human_resource','hr_manager','head','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `idx_training_categories_created_by_role` (`created_by_role`),
  CONSTRAINT `fk_training_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_categories`
--

LOCK TABLES `training_categories` WRITE;
/*!40000 ALTER TABLE `training_categories` DISABLE KEYS */;
INSERT INTO `training_categories` VALUES (1,'Classroom Management','Trainings focused on improving classroom discipline and management skills','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(2,'Teaching Methodologies','Seminars on modern teaching techniques and strategies','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(3,'Technology Integration','Workshops on incorporating technology in teaching','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(4,'Student Engagement','Training on methods to increase student participation and engagement','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(5,'Assessment Strategies','Seminars on effective assessment and evaluation methods','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(6,'Professional Development','General professional development and career advancement','active',1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56');
/*!40000 ALTER TABLE `training_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_registrations`
--

DROP TABLE IF EXISTS `training_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('registered','attended','completed','no_show','cancelled') DEFAULT 'registered',
  `attendance_date` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_issued_date` datetime DEFAULT NULL,
  `feedback_rating` int(11) DEFAULT NULL COMMENT '1-5 rating',
  `feedback_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_role` enum('guidance_officer','human_resource','hr_manager','head','teacher','student') NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_user` (`training_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `registration_date` (`registration_date`),
  KEY `idx_training_registrations_user_status` (`user_id`,`status`),
  KEY `idx_training_registrations_training_status` (`training_id`,`status`),
  KEY `idx_training_registrations_date` (`registration_date`),
  KEY `idx_training_registrations_created_by_role` (`created_by_role`),
  CONSTRAINT `fk_training_registrations_training` FOREIGN KEY (`training_id`) REFERENCES `trainings_seminars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_training_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_registrations`
--

LOCK TABLES `training_registrations` WRITE;
/*!40000 ALTER TABLE `training_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_suggestions`
--

DROP TABLE IF EXISTS `training_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_suggestions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `suggestion_reason` text NOT NULL COMMENT 'Why this training is suggested',
  `evaluation_category_id` int(11) DEFAULT NULL COMMENT 'Related evaluation category',
  `evaluation_score` decimal(3,2) DEFAULT NULL COMMENT 'Teacher''s score in this category',
  `priority_level` enum('low','medium','high','critical') DEFAULT 'medium',
  `suggested_by` int(11) NOT NULL,
  `suggestion_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','declined','completed') DEFAULT 'pending',
  `response_date` datetime DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `training_id` (`training_id`),
  KEY `evaluation_category_id` (`evaluation_category_id`),
  KEY `priority_level` (`priority_level`),
  KEY `status` (`status`),
  KEY `suggested_by` (`suggested_by`),
  KEY `idx_training_suggestions_user_status` (`user_id`,`status`),
  KEY `idx_training_suggestions_priority` (`priority_level`,`status`),
  KEY `idx_training_suggestions_category` (`evaluation_category_id`),
  CONSTRAINT `fk_training_suggestions_category` FOREIGN KEY (`evaluation_category_id`) REFERENCES `evaluation_sub_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_training_suggestions_suggested_by` FOREIGN KEY (`suggested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_training_suggestions_training` FOREIGN KEY (`training_id`) REFERENCES `trainings_seminars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_training_suggestions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_suggestions`
--

LOCK TABLES `training_suggestions` WRITE;
/*!40000 ALTER TABLE `training_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainings_seminars`
--

DROP TABLE IF EXISTS `trainings_seminars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainings_seminars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('training','seminar','workshop','conference') NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `main_category_id` int(11) DEFAULT NULL COMMENT 'Linked to evaluation main category',
  `sub_category_id` int(11) DEFAULT NULL COMMENT 'Linked to evaluation sub-category',
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `status` enum('draft','published','ongoing','completed','cancelled') DEFAULT 'draft',
  `is_mandatory` tinyint(1) DEFAULT 0,
  `certificate_provided` tinyint(1) DEFAULT 0,
  `materials_provided` tinyint(1) DEFAULT 0,
  `cost` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_by_role` enum('guidance_officer','human_resource','hr_manager','head','teacher','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `main_category_id` (`main_category_id`),
  KEY `sub_category_id` (`sub_category_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  KEY `created_by` (`created_by`),
  KEY `idx_trainings_seminars_type_status` (`type`,`status`),
  KEY `idx_trainings_seminars_dates` (`start_date`,`end_date`),
  KEY `idx_trainings_seminars_category_status` (`category_id`,`status`),
  KEY `idx_trainings_seminars_main_category` (`main_category_id`,`status`),
  KEY `idx_trainings_seminars_created_by_role` (`created_by_role`),
  CONSTRAINT `fk_trainings_seminars_category` FOREIGN KEY (`category_id`) REFERENCES `training_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trainings_seminars_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trainings_seminars_main_category` FOREIGN KEY (`main_category_id`) REFERENCES `main_evaluation_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trainings_seminars_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `evaluation_sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings_seminars`
--

LOCK TABLES `trainings_seminars` WRITE;
/*!40000 ALTER TABLE `trainings_seminars` DISABLE KEYS */;
INSERT INTO `trainings_seminars` VALUES (1,'Effective Classroom Management Strategies','Comprehensive workshop on effective classroom management strategies, behavior management techniques, and creating positive learning environments.','training',1,1,1,8.00,25,'Conference Room A','2025-09-15 09:00:00','2025-09-15 17:00:00','2024-03-10 17:00:00','published',0,1,1,0.00,1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(2,'Modern Teaching Methodologies Workshop','Advanced training on modern teaching methodologies, instructional design, and active learning strategies for enhanced student engagement.','workshop',2,1,2,6.00,20,'Training Hall B','2025-09-20 09:00:00','2025-09-20 15:00:00','2024-03-15 17:00:00','published',0,1,1,500.00,1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(3,'Technology Integration in Education','Workshop on integrating technology tools and digital resources into classroom instruction for improved learning outcomes.','seminar',3,1,4,4.00,30,'Computer Lab 1','2025-09-25 13:00:00','2025-09-25 17:00:00','2024-03-20 17:00:00','published',0,1,0,0.00,1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(4,'Student Engagement Techniques','Discover methods to increase student participation and motivation','training',4,1,5,6.00,25,'Conference Room C','2024-04-01 09:00:00','2024-04-01 15:00:00','2024-03-27 17:00:00','draft',0,1,1,0.00,1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56'),(5,'Assessment and Evaluation Best Practices','Learn effective assessment strategies and evaluation methods','seminar',5,1,2,4.00,35,'Lecture Hall','2024-04-05 14:00:00','2024-04-05 18:00:00','2024-04-01 17:00:00','draft',0,1,0,0.00,1,'guidance_officer','2025-08-13 04:23:02','2025-10-03 15:11:56');
/*!40000 ALTER TABLE `trainings_seminars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','hr_manager','human_resource','nurse') NOT NULL DEFAULT 'human_resource',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$wyYKEDN890CFfPpsf6s3QOjJEM92zkKcgUN0dMyg5PE1RNY./aktW','System','Administrator','admin@nia-hris.com','uploads/hr-photos/hr_1_1759822247.jpg','super_admin','active','2025-10-07 03:25:50','2025-10-19 15:09:33'),(7,'superadmin','$2y$10$jclhvpqF6rg9mUrzCpI9gelzqstrXGKMqG8WEqBip3IAmP.MkHISG','Juan','Dela Cruz','superadmin@nia.gov.ph',NULL,'super_admin','active','2025-10-19 15:17:28','2025-10-19 15:17:28'),(8,'hrmanager','$2y$10$BnCeSaPCSHnnS6qLlVsfmuZa82EV.CiBokIz/3YgKBPMmv08q5eCu','Maria','Garcia','hrmanager@nia.gov.ph',NULL,'hr_manager','active','2025-10-19 15:17:28','2025-10-19 15:17:28'),(9,'hrstaff','$2y$10$5P4AW5qCJlFmJyXc819SeON67kjXI.16hVUPs4K8KPZKZ05B40HYK','Pedro','Santos','hrstaff@nia.gov.ph',NULL,'human_resource','active','2025-10-19 15:17:28','2025-10-19 15:17:28'),(10,'nurse1','$2y$10$.I59afI1SwXz9DrTbqrA6eiOkFfkwWr.i.2lRmGHA0LDj38obuVde','Ana','Reyes','nurse@nia.gov.ph',NULL,'nurse','active','2025-10-19 15:17:28','2025-10-19 15:17:28');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_payroll_statistics`
--

DROP TABLE IF EXISTS `v_payroll_statistics`;
/*!50001 DROP VIEW IF EXISTS `v_payroll_statistics`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_payroll_statistics` AS SELECT
 1 AS `period_id`,
  1 AS `period_name`,
  1 AS `status`,
  1 AS `total_employees`,
  1 AS `total_gross`,
  1 AS `total_deductions`,
  1 AS `total_net`,
  1 AS `average_net_pay`,
  1 AS `total_sss`,
  1 AS `total_philhealth`,
  1 AS `total_pagibig`,
  1 AS `total_tax` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_payroll_summary`
--

DROP TABLE IF EXISTS `v_payroll_summary`;
/*!50001 DROP VIEW IF EXISTS `v_payroll_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_payroll_summary` AS SELECT
 1 AS `period_id`,
  1 AS `period_name`,
  1 AS `start_date`,
  1 AS `end_date`,
  1 AS `payment_date`,
  1 AS `period_status`,
  1 AS `record_id`,
  1 AS `employee_id`,
  1 AS `employee_name`,
  1 AS `employee_number`,
  1 AS `department`,
  1 AS `position`,
  1 AS `regular_hours`,
  1 AS `overtime_hours`,
  1 AS `gross_pay`,
  1 AS `total_deductions`,
  1 AS `net_pay`,
  1 AS `record_status`,
  1 AS `created_by_name` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'nia_hris'
--

--
-- Dumping routines for database 'nia_hris'
--

--
-- Current Database: `nia_hris`
--

USE `nia_hris`;

--
-- Final view structure for view `v_payroll_statistics`
--

/*!50001 DROP VIEW IF EXISTS `v_payroll_statistics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_payroll_statistics` AS select `pp`.`id` AS `period_id`,`pp`.`period_name` AS `period_name`,`pp`.`status` AS `status`,count(`pr`.`id`) AS `total_employees`,sum(`pr`.`gross_pay`) AS `total_gross`,sum(`pr`.`total_deductions`) AS `total_deductions`,sum(`pr`.`net_pay`) AS `total_net`,avg(`pr`.`net_pay`) AS `average_net_pay`,sum(`pr`.`sss_contribution`) AS `total_sss`,sum(`pr`.`philhealth_contribution`) AS `total_philhealth`,sum(`pr`.`pagibig_contribution`) AS `total_pagibig`,sum(`pr`.`withholding_tax`) AS `total_tax` from (`payroll_periods` `pp` left join `payroll_records` `pr` on(`pp`.`id` = `pr`.`payroll_period_id`)) group by `pp`.`id`,`pp`.`period_name`,`pp`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_payroll_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_payroll_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_payroll_summary` AS select `pp`.`id` AS `period_id`,`pp`.`period_name` AS `period_name`,`pp`.`start_date` AS `start_date`,`pp`.`end_date` AS `end_date`,`pp`.`payment_date` AS `payment_date`,`pp`.`status` AS `period_status`,`pr`.`id` AS `record_id`,`pr`.`employee_id` AS `employee_id`,`pr`.`employee_name` AS `employee_name`,`pr`.`employee_number` AS `employee_number`,`pr`.`department` AS `department`,`pr`.`position` AS `position`,`pr`.`regular_hours` AS `regular_hours`,`pr`.`overtime_hours` AS `overtime_hours`,`pr`.`gross_pay` AS `gross_pay`,`pr`.`total_deductions` AS `total_deductions`,`pr`.`net_pay` AS `net_pay`,`pr`.`status` AS `record_status`,`u`.`username` AS `created_by_name` from ((`payroll_periods` `pp` left join `payroll_records` `pr` on(`pp`.`id` = `pr`.`payroll_period_id`)) left join `users` `u` on(`pp`.`created_by` = `u`.`id`)) order by `pp`.`created_at` desc,`pr`.`employee_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-20 10:08:33
