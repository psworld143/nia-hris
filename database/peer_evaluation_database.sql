-- =====================================================
-- PEER-TO-PEER EVALUATION DATABASE STRUCTURE
-- Complete SQL for HR Manager Peer Evaluation System
-- =====================================================

-- 1. Main Evaluation Categories Table
CREATE TABLE IF NOT EXISTS `main_evaluation_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `evaluation_type` enum('peer_to_peer','student_to_teacher','head_to_teacher') NOT NULL DEFAULT 'peer_to_peer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_evaluation_type` (`evaluation_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Evaluation Sub-Categories Table
CREATE TABLE IF NOT EXISTS `evaluation_sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `main_category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `order_number` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sub_main_category` (`main_category_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_sub_main_category` FOREIGN KEY (`main_category_id`) REFERENCES `main_evaluation_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Evaluation Questionnaires Table
CREATE TABLE IF NOT EXISTS `evaluation_questionnaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_category_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('rating_1_5','text','yes_no','multiple_choice') NOT NULL DEFAULT 'rating_1_5',
  `order_number` int(11) NOT NULL DEFAULT 1,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_questionnaire_sub_category` (`sub_category_id`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_questionnaire_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `evaluation_sub_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Faculty Table (if not exists)
CREATE TABLE IF NOT EXISTS `faculty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(100),
  `phone` varchar(20),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_department` (`department`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Semesters Table (if not exists)
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Evaluation Sessions Table
CREATE TABLE IF NOT EXISTS `evaluation_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluator_id` int(11) NOT NULL,
  `evaluator_type` enum('teacher','head','admin') NOT NULL DEFAULT 'teacher',
  `evaluatee_id` int(11) NOT NULL,
  `evaluatee_type` enum('teacher','head','admin') NOT NULL DEFAULT 'teacher',
  `main_category_id` int(11) NOT NULL,
  `semester_id` int(11) NULL,
  `evaluation_date` date NOT NULL,
  `status` enum('draft','completed','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_session_evaluator` (`evaluator_id`),
  KEY `fk_session_evaluatee` (`evaluatee_id`),
  KEY `fk_session_main_category` (`main_category_id`),
  KEY `fk_session_semester` (`semester_id`),
  KEY `idx_status` (`status`),
  KEY `idx_evaluation_date` (`evaluation_date`),
  CONSTRAINT `fk_session_evaluator` FOREIGN KEY (`evaluator_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_evaluatee` FOREIGN KEY (`evaluatee_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_main_category` FOREIGN KEY (`main_category_id`) REFERENCES `main_evaluation_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Evaluation Responses Table
CREATE TABLE IF NOT EXISTS `evaluation_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evaluation_session_id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `response` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_response_session` (`evaluation_session_id`),
  KEY `fk_response_questionnaire` (`questionnaire_id`),
  UNIQUE KEY `unique_session_questionnaire` (`evaluation_session_id`, `questionnaire_id`),
  CONSTRAINT `fk_response_session` FOREIGN KEY (`evaluation_session_id`) REFERENCES `evaluation_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_response_questionnaire` FOREIGN KEY (`questionnaire_id`) REFERENCES `evaluation_questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Evaluation Schedules Table (Optional - for time-based evaluations)
CREATE TABLE IF NOT EXISTS `evaluation_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `semester_id` int(11) NULL,
  `evaluation_type` enum('peer_to_peer','student_to_teacher','head_to_teacher') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_schedule_semester` (`semester_id`),
  KEY `idx_evaluation_type` (`evaluation_type`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_schedule_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert Sample Main Category
INSERT INTO `main_evaluation_categories` (`name`, `description`, `evaluation_type`, `status`) VALUES
('Peer to Peer Evaluation', 'Teachers evaluate their colleagues on professional competence and collaboration', 'peer_to_peer', 'active');

-- Insert Sample Sub-Categories
INSERT INTO `evaluation_sub_categories` (`main_category_id`, `name`, `description`, `order_number`) VALUES
(1, 'Professional Competence', 'Evaluation of colleague\'s professional skills and knowledge', 1),
(1, 'Collaboration Skills', 'Assessment of colleague\'s teamwork and collaboration abilities', 2),
(1, 'Communication', 'Evaluation of colleague\'s communication effectiveness', 3),
(1, 'Innovation and Adaptability', 'Assessment of colleague\'s innovation and adaptability skills', 4);

-- Insert Sample Questions
INSERT INTO `evaluation_questionnaires` (`sub_category_id`, `question_text`, `question_type`, `order_number`) VALUES
-- Professional Competence Questions
(1, 'How well does the colleague demonstrate subject matter expertise?', 'rating_1_5', 1),
(1, 'How effectively does the colleague plan and organize lessons?', 'rating_1_5', 2),
(1, 'How committed is the colleague to professional development?', 'rating_1_5', 3),
(1, 'How well does the colleague stay updated with current educational trends?', 'rating_1_5', 4),
(1, 'What specific areas of professional development would you recommend for this colleague?', 'text', 5),

-- Collaboration Skills Questions
(2, 'How effectively does the colleague work in team settings?', 'rating_1_5', 1),
(2, 'How well does the colleague share resources and knowledge?', 'rating_1_5', 2),
(2, 'Does the colleague actively participate in departmental meetings?', 'yes_no', 3),
(2, 'How supportive is the colleague towards other team members?', 'rating_1_5', 4),
(2, 'Describe a specific example of effective collaboration with this colleague.', 'text', 5),

-- Communication Questions
(3, 'How clearly does the colleague communicate ideas and instructions?', 'rating_1_5', 1),
(3, 'How well does the colleague listen to others\' perspectives?', 'rating_1_5', 2),
(3, 'Is the colleague approachable for discussions and consultations?', 'yes_no', 3),
(3, 'How effectively does the colleague handle conflicts or disagreements?', 'rating_1_5', 4),

-- Innovation and Adaptability Questions
(4, 'How open is the colleague to new teaching methods and technologies?', 'rating_1_5', 1),
(4, 'How well does the colleague adapt to changes in curriculum or policies?', 'rating_1_5', 2),
(4, 'Does the colleague contribute innovative ideas to improve processes?', 'yes_no', 3),
(4, 'How effectively does the colleague handle unexpected challenges?', 'rating_1_5', 4);

-- Insert Sample Faculty (if needed for testing)
INSERT INTO `faculty` (`first_name`, `last_name`, `email`, `department`, `position`) VALUES
('Michael Paul', 'Sebando', 'paul.sebando@seait.edu.ph', 'College of Information and Communication Technology', 'Professor'),
('Hernie', 'Deduro', 'h.deduro@seait.edu.ph', 'College of Information and Communication Technology', 'Associate Professor'),
('Maria', 'Santos', 'm.santos@seait.edu.ph', 'College of Education', 'Professor'),
('John', 'Cruz', 'j.cruz@seait.edu.ph', 'College of Business', 'Assistant Professor'),
('Ana', 'Reyes', 'a.reyes@seait.edu.ph', 'College of Engineering', 'Professor');

-- Insert Sample Semester
INSERT INTO `semesters` (`name`, `start_date`, `end_date`, `status`) VALUES
('First Semester 2025-2026', '2025-08-01', '2025-12-15', 'active');

-- Insert Sample Evaluation Session
INSERT INTO `evaluation_sessions` (`evaluator_id`, `evaluatee_id`, `main_category_id`, `semester_id`, `evaluation_date`, `status`) VALUES
(1, 2, 1, 1, '2025-10-03', 'completed');

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX `idx_main_category_type_status` ON `main_evaluation_categories` (`evaluation_type`, `status`);
CREATE INDEX `idx_sub_category_main_status` ON `evaluation_sub_categories` (`main_category_id`, `status`);
CREATE INDEX `idx_questionnaire_sub_status` ON `evaluation_questionnaires` (`sub_category_id`, `status`);
CREATE INDEX `idx_session_evaluator_status` ON `evaluation_sessions` (`evaluator_id`, `status`);
CREATE INDEX `idx_session_evaluatee_status` ON `evaluation_sessions` (`evaluatee_id`, `status`);
CREATE INDEX `idx_session_category_status` ON `evaluation_sessions` (`main_category_id`, `status`);
