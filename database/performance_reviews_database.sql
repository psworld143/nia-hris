-- =====================================================
-- PERFORMANCE REVIEWS DATABASE STRUCTURE
-- Complete SQL for HR Manager Performance Review System
-- =====================================================

-- 1. Performance Review Categories Table
CREATE TABLE IF NOT EXISTS `performance_review_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_weight` (`weight_percentage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Performance Review Criteria Table
CREATE TABLE IF NOT EXISTS `performance_review_criteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `criteria_name` varchar(255) NOT NULL,
  `description` text,
  `max_score` decimal(5,2) NOT NULL DEFAULT 5.00,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `order_number` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_criteria_category` (`category_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_criteria_category` FOREIGN KEY (`category_id`) REFERENCES `performance_review_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Performance Reviews Table
CREATE TABLE IF NOT EXISTS `performance_reviews` (
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
  `goals_achieved` text,
  `areas_of_strength` text,
  `areas_for_improvement` text,
  `development_plan` text,
  `recommendations` text,
  `manager_comments` text,
  `employee_comments` text,
  `next_review_date` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_review_employee` (`employee_id`),
  KEY `fk_review_reviewer` (`reviewer_id`),
  KEY `fk_review_approved_by` (`approved_by`),
  KEY `idx_employee_type` (`employee_type`),
  KEY `idx_review_type` (`review_type`),
  KEY `idx_status` (`status`),
  KEY `idx_review_period` (`review_period_start`, `review_period_end`),
  CONSTRAINT `fk_review_employee` FOREIGN KEY (`employee_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Performance Review Scores Table
CREATE TABLE IF NOT EXISTS `performance_review_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `comments` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_score_review` (`review_id`),
  KEY `fk_score_criteria` (`criteria_id`),
  UNIQUE KEY `unique_review_criteria` (`review_id`, `criteria_id`),
  CONSTRAINT `fk_score_review` FOREIGN KEY (`review_id`) REFERENCES `performance_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_score_criteria` FOREIGN KEY (`criteria_id`) REFERENCES `performance_review_criteria` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Performance Review Goals Table
CREATE TABLE IF NOT EXISTS `performance_review_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `goal_title` varchar(255) NOT NULL,
  `goal_description` text,
  `target_date` date DEFAULT NULL,
  `achievement_status` enum('not_started','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'not_started',
  `achievement_percentage` decimal(5,2) DEFAULT 0.00,
  `comments` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_goal_review` (`review_id`),
  KEY `idx_achievement_status` (`achievement_status`),
  CONSTRAINT `fk_goal_review` FOREIGN KEY (`review_id`) REFERENCES `performance_reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Performance Review Attachments Table
CREATE TABLE IF NOT EXISTS `performance_review_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `description` text,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_attachment_review` (`review_id`),
  KEY `fk_attachment_uploader` (`uploaded_by`),
  CONSTRAINT `fk_attachment_review` FOREIGN KEY (`review_id`) REFERENCES `performance_reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attachment_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert Sample Performance Review Categories
INSERT INTO `performance_review_categories` (`name`, `description`, `weight_percentage`, `status`) VALUES
('Job Performance', 'Core job responsibilities and duties', 40.00, 'active'),
('Communication Skills', 'Effectiveness in verbal and written communication', 15.00, 'active'),
('Teamwork & Collaboration', 'Ability to work with others and contribute to team goals', 15.00, 'active'),
('Leadership & Initiative', 'Taking initiative and demonstrating leadership qualities', 10.00, 'active'),
('Professional Development', 'Commitment to continuous learning and growth', 10.00, 'active'),
('Attendance & Punctuality', 'Reliability in attendance and time management', 10.00, 'active');

-- Insert Sample Performance Review Criteria
INSERT INTO `performance_review_criteria` (`category_id`, `criteria_name`, `description`, `max_score`, `weight_percentage`, `order_number`) VALUES
-- Job Performance Criteria
(1, 'Quality of Work', 'Accuracy, thoroughness, and attention to detail', 5.00, 30.00, 1),
(1, 'Productivity', 'Efficiency and volume of work completed', 5.00, 25.00, 2),
(1, 'Problem Solving', 'Ability to identify and resolve issues effectively', 5.00, 25.00, 3),
(1, 'Knowledge & Skills', 'Demonstration of required knowledge and competencies', 5.00, 20.00, 4),

-- Communication Skills Criteria
(2, 'Verbal Communication', 'Clarity and effectiveness in spoken communication', 5.00, 40.00, 1),
(2, 'Written Communication', 'Quality and clarity of written materials', 5.00, 35.00, 2),
(2, 'Listening Skills', 'Ability to understand and respond appropriately', 5.00, 25.00, 3),

-- Teamwork & Collaboration Criteria
(3, 'Team Contribution', 'Active participation and contribution to team efforts', 5.00, 35.00, 1),
(3, 'Interpersonal Skills', 'Effectiveness in working with colleagues', 5.00, 35.00, 2),
(3, 'Conflict Resolution', 'Ability to handle and resolve conflicts constructively', 5.00, 30.00, 3),

-- Leadership & Initiative Criteria
(4, 'Initiative', 'Proactive approach to tasks and responsibilities', 5.00, 40.00, 1),
(4, 'Leadership', 'Ability to guide and influence others', 5.00, 35.00, 2),
(4, 'Innovation', 'Creativity and forward-thinking approach', 5.00, 25.00, 3),

-- Professional Development Criteria
(5, 'Learning Agility', 'Speed and ability to acquire new knowledge', 5.00, 40.00, 1),
(5, 'Skill Development', 'Efforts to improve and expand skills', 5.00, 35.00, 2),
(5, 'Mentoring', 'Willingness to help and guide others', 5.00, 25.00, 3),

-- Attendance & Punctuality Criteria
(6, 'Attendance', 'Consistency in attendance and availability', 5.00, 50.00, 1),
(6, 'Punctuality', 'Timeliness in meetings, deadlines, and commitments', 5.00, 50.00, 2);

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX `idx_performance_reviews_employee_period` ON `performance_reviews` (`employee_id`, `review_period_start`, `review_period_end`);
CREATE INDEX `idx_performance_reviews_reviewer_status` ON `performance_reviews` (`reviewer_id`, `status`);
CREATE INDEX `idx_performance_reviews_type_status` ON `performance_reviews` (`review_type`, `status`);
CREATE INDEX `idx_performance_review_scores_review` ON `performance_review_scores` (`review_id`);
CREATE INDEX `idx_performance_review_goals_review_status` ON `performance_review_goals` (`review_id`, `achievement_status`);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- View for Performance Review Summary
CREATE OR REPLACE VIEW `performance_review_summary` AS
SELECT 
    pr.id,
    pr.employee_id,
    f.first_name,
    f.last_name,
    f.email,
    f.department,
    f.position,
    pr.reviewer_id,
    u.username as reviewer_name,
    pr.review_type,
    pr.status,
    pr.overall_rating,
    pr.overall_percentage,
    pr.review_period_start,
    pr.review_period_end,
    pr.next_review_date,
    pr.created_at,
    pr.updated_at,
    COUNT(prs.id) as criteria_scored,
    COUNT(prg.id) as goals_set,
    COUNT(pra.id) as attachments_count
FROM performance_reviews pr
LEFT JOIN faculty f ON pr.employee_id = f.id
LEFT JOIN users u ON pr.reviewer_id = u.id
LEFT JOIN performance_review_scores prs ON pr.id = prs.review_id
LEFT JOIN performance_review_goals prg ON pr.id = prg.review_id
LEFT JOIN performance_review_attachments pra ON pr.id = pra.review_id
GROUP BY pr.id;

-- View for Performance Review Scores with Criteria
CREATE OR REPLACE VIEW `performance_review_scores_detailed` AS
SELECT 
    prs.id,
    prs.review_id,
    prs.criteria_id,
    prc.criteria_name,
    prc.description as criteria_description,
    prc.max_score,
    prc.weight_percentage,
    prc2.name as category_name,
    prc2.weight_percentage as category_weight,
    prs.score,
    prs.comments,
    (prs.score / prc.max_score) * 100 as percentage_score,
    (prs.score / prc.max_score) * prc.weight_percentage as weighted_score
FROM performance_review_scores prs
LEFT JOIN performance_review_criteria prc ON prs.criteria_id = prc.id
LEFT JOIN performance_review_categories prc2 ON prc.category_id = prc2.id;
