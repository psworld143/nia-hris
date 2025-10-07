# Performance Reviews System

A comprehensive performance review management system for the SEAIT Human Resource department.

## Overview

The Performance Reviews system provides a structured approach to evaluating employee performance, setting goals, and tracking development progress. It features a flexible scoring system with multiple categories and criteria, goal setting and tracking, and comprehensive reporting capabilities.

## Features

### Core Features
- **Multi-Category Performance Evaluation**: 6 predefined categories with weighted scoring
- **Flexible Criteria System**: Customizable criteria within each category
- **Goal Setting & Tracking**: Set and monitor performance goals with status tracking
- **Review Types**: Support for annual, semi-annual, quarterly, probationary, promotion, and special reviews
- **Status Management**: Draft, in-progress, completed, approved, and rejected statuses
- **Attachment Support**: Upload and manage supporting documents
- **Comprehensive Reporting**: Detailed performance reports with calculated scores
- **Print Functionality**: Print-ready review documents

### Performance Categories
1. **Job Performance** (40% weight)
   - Quality of Work
   - Productivity
   - Problem Solving
   - Knowledge & Skills

2. **Communication Skills** (15% weight)
   - Verbal Communication
   - Written Communication
   - Listening Skills

3. **Teamwork & Collaboration** (15% weight)
   - Team Contribution
   - Interpersonal Skills
   - Conflict Resolution

4. **Leadership & Initiative** (10% weight)
   - Initiative
   - Leadership
   - Innovation

5. **Professional Development** (10% weight)
   - Learning Agility
   - Skill Development
   - Mentoring

6. **Attendance & Punctuality** (10% weight)
   - Attendance
   - Punctuality

## Installation

### Prerequisites
- MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+
- MySQLi extension enabled
- File read permissions for SQL file
- Database write permissions

### Installation Steps

1. **Access the Installation Page**
   ```
   http://localhost/seait/human-resource/install-performance-reviews.php
   ```

2. **Check Installation Status**
   - The system will automatically detect existing tables
   - Review the table status and requirements

3. **Install Database Structure**
   - Click "Install Database" if tables are missing
   - The system will create all required tables and sample data

4. **Verify Installation**
   - Confirm all tables are created successfully
   - Sample categories and criteria will be pre-populated

### Database Structure

#### Core Tables
- `performance_review_categories` - Review categories and weights
- `performance_review_criteria` - Specific criteria within categories
- `performance_reviews` - Main review records
- `performance_review_scores` - Individual criteria scores and comments
- `performance_review_goals` - Performance goals and status tracking
- `performance_review_attachments` - File attachments for reviews

#### Sample Data Included
- 6 performance categories with predefined weights
- 15+ performance criteria with descriptions
- Pre-configured rating scales
- Database views for reporting

## Usage

### Creating a Performance Review

1. **Navigate to Performance Reviews**
   ```
   http://localhost/seait/human-resource/performance-reviews.php
   ```

2. **Create New Review**
   - Click "New Review" button
   - Select employee, review type, and period
   - Click "Create Review"

3. **Conduct the Review**
   - Access the review via "Edit" button
   - Score each criteria (0-5 scale)
   - Add comments for each criteria
   - Set overall rating and percentage
   - Add performance goals
   - Include manager and employee comments

4. **Complete the Review**
   - Set review status to "Completed"
   - Set next review date
   - Save the review

### Viewing Reviews

1. **Review List**
   - View all reviews with filtering options
   - Filter by status, type, department, or search by name
   - See overall ratings and progress indicators

2. **Detailed View**
   - Click "View" to see complete review details
   - View all scores, comments, and goals
   - Print the review document
   - See review timeline and approval status

### Managing Goals

1. **Setting Goals**
   - Add multiple goals during review creation
   - Set target dates and achievement percentages
   - Track goal status (Not Started, In Progress, Completed, Overdue, Cancelled)

2. **Goal Tracking**
   - Monitor goal progress with percentage completion
   - Add comments and updates
   - View goal history and achievements

## File Structure

```
human-resource/
├── performance-reviews.php              # Main reviews list and management
├── conduct-performance-review.php       # Review creation and editing
├── view-performance-review.php          # Review viewing and printing
├── install-performance-reviews.php      # Database installation
├── database/
│   └── performance_reviews_database.sql # Complete database schema
└── README_PERFORMANCE_REVIEWS.md        # This documentation
```

## Database Schema Details

### Performance Review Categories
```sql
CREATE TABLE performance_review_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    weight_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Performance Reviews
```sql
CREATE TABLE performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    employee_type ENUM('faculty','staff','admin') DEFAULT 'faculty',
    reviewer_id INT NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    review_type ENUM('annual','semi_annual','quarterly','probationary','promotion','special') DEFAULT 'annual',
    status ENUM('draft','in_progress','completed','approved','rejected') DEFAULT 'draft',
    overall_rating DECIMAL(5,2),
    overall_percentage DECIMAL(5,2),
    -- Additional fields for comments, goals, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: HTML escaping on all outputs
- **Access Control**: Role-based permissions (HR and HR Manager only)
- **ID Encryption**: Secure URL parameters using encrypted IDs
- **Input Validation**: Comprehensive input sanitization

## Reporting and Analytics

### Built-in Reports
- **Review Summary**: Overall performance statistics
- **Category Breakdown**: Performance by category
- **Goal Tracking**: Goal achievement rates
- **Review Timeline**: Review history and scheduling

### Database Views
- `performance_review_summary` - Complete review overview
- `performance_review_scores_detailed` - Detailed scoring with calculations

## Customization

### Adding New Categories
1. Insert into `performance_review_categories` table
2. Add corresponding criteria to `performance_review_criteria`
3. Ensure total weights add up to 100%

### Modifying Criteria
1. Update criteria descriptions and weights
2. Adjust maximum scores as needed
3. Reorder criteria using the `order_number` field

### Custom Review Types
1. Add new types to the `review_type` enum in the database
2. Update the PHP code to include new types in dropdowns

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify database credentials in `config/database.php`
   - Ensure MySQL/MariaDB service is running
   - Check table creation was successful

2. **Permission Denied**
   - Verify user roles in database
   - Check session management
   - Ensure proper authentication

3. **Installation Failures**
   - Check PHP error logs
   - Verify file permissions
   - Ensure database user has CREATE TABLE privileges

### Performance Optimization

- **Database Indexes**: Already optimized with composite indexes
- **Query Optimization**: Uses prepared statements and efficient joins
- **Caching**: Consider implementing Redis for session management
- **File Storage**: Consider cloud storage for attachments

## Support and Maintenance

### Regular Maintenance
- **Database Cleanup**: Archive old reviews periodically
- **Log Rotation**: Manage error log size
- **Backup Strategy**: Regular database backups
- **Performance Monitoring**: Monitor query performance

### Updates and Patches
- **Version Control**: Track system changes
- **Testing Environment**: Test updates before production
- **User Training**: Keep staff updated on new features
- **Documentation**: Maintain current documentation

## Success Metrics

The system provides comprehensive metrics to measure success:

- **Processing Time**: Average review completion time
- **Participation Rate**: Percentage of employees reviewed
- **Goal Achievement**: Rate of goal completion
- **Review Quality**: Completeness of review data
- **User Satisfaction**: Feedback on system usability

## Conclusion

The Performance Reviews system provides a comprehensive solution for managing employee performance evaluations. With its flexible scoring system, goal tracking capabilities, and detailed reporting, it supports effective performance management and employee development.

For technical support or feature requests, please contact the HR department or system administrator.
