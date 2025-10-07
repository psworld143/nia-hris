# Performance Review System - Setup Guide

## Overview
The Performance Review System has been successfully migrated from SEAIT website to NIA-HRIS. This guide covers the setup and usage of the system.

## What's Included

### ✅ Files Copied:
1. **conduct-performance-review.php** - Interface for conducting and editing performance reviews
2. **performance-reviews.php** - Main dashboard for viewing and managing reviews (already exists)
3. **view-performance-review.php** - Detailed view and print functionality (already exists)
4. **migrate-performance-reviews.php** - Migration script to copy tables from SEAIT
5. **install-performance-reviews.php** - Updated with migration option

### ✅ Database Tables (8 tables):
1. **performance_review_categories** - Review categories with weights (e.g., Job Performance 40%, Communication 15%)
2. **performance_review_criteria** - Specific criteria within each category
3. **performance_reviews** - Main review records
4. **performance_review_scores** - Individual criteria scores and comments
5. **performance_review_goals** - Performance goals with tracking
6. **performance_review_attachments** - File attachments for reviews
7. **performance_review_scores_detailed** - Detailed scoring view
8. **performance_review_summary** - Summary view for reporting

## Installation Options

### Option 1: Migrate from SEAIT (Recommended)
This copies ALL existing data including reviews, scores, goals, and attachments.

**Steps:**
1. Navigate to: `http://localhost/nia-hris/install-performance-reviews.php`
2. Click **"Migrate from SEAIT Website"** button
3. Confirm the migration
4. Wait for completion (copies structure + data)
5. Access the system: `http://localhost/nia-hris/performance-reviews.php`

**What gets copied:**
- ✅ Complete table structures
- ✅ All existing reviews and scores
- ✅ All categories and criteria
- ✅ All performance goals
- ✅ All attachments references
- ✅ Summary views

### Option 2: Fresh Installation
This creates empty tables with sample categories and criteria only.

**Steps:**
1. Navigate to: `http://localhost/nia-hris/install-performance-reviews.php`
2. Click **"Install Fresh Database"** button
3. Confirm installation
4. System creates tables with sample data

## System Features

### 1. Performance Review Management
- **Create Reviews**: For annual, semi-annual, quarterly, probationary, promotion, or special reviews
- **Conduct Reviews**: Score employees across multiple categories
- **Track Progress**: Monitor review status (Draft, In Progress, Completed, Approved)
- **Set Goals**: Define and track performance goals with completion percentages

### 2. Scoring System
- **6 Main Categories**:
  - Job Performance (40% weight)
  - Communication Skills (15% weight)
  - Teamwork & Collaboration (15% weight)
  - Leadership & Initiative (10% weight)
  - Professional Development (10% weight)
  - Attendance & Punctuality (10% weight)

- **15+ Criteria** with 0-5 scale scoring
- **Weighted Calculations** for overall performance percentage
- **Comments Section** for each criterion

### 3. Goal Management
- Set SMART goals during reviews
- Track goal progress and completion
- Monitor achievement percentages
- Add comments and updates

### 4. Reporting
- View review summaries
- Print review documents
- Export review data
- Track review history

## Access Permissions

Only users with the following roles can access:
- **admin**
- **human_resource**
- **hr_manager**

## Usage Guide

### Creating a Performance Review

1. **Navigate to Performance Reviews**
   ```
   http://localhost/nia-hris/performance-reviews.php
   ```

2. **Click "New Review"**
   - Select employee
   - Choose review type (Annual, Quarterly, etc.)
   - Set review period
   - Click "Create Review"

3. **Conduct the Review**
   ```
   http://localhost/nia-hris/conduct-performance-review.php?id=[review_id]
   ```
   - Score each criterion (0-5)
   - Add detailed comments
   - Set performance goals
   - Add manager and employee comments
   - Upload attachments (if needed)

4. **Complete and Submit**
   - Set status to "Completed"
   - Set next review date
   - Submit for approval

### Viewing Reviews

1. **Review List**: See all reviews with filters
2. **Detailed View**: Click "View" to see complete review
3. **Print**: Use print button for physical copies
4. **Edit**: Click "Edit" to modify reviews

## Database Schema

### Core Table Relationships
```
performance_reviews (main table)
├── performance_review_scores (1:many)
│   └── performance_review_criteria
│       └── performance_review_categories
├── performance_review_goals (1:many)
└── performance_review_attachments (1:many)
```

### Key Fields
- **Reviews**: employee_id, reviewer_id, review_period, review_type, status, overall_rating
- **Scores**: review_id, criteria_id, score (0-5), comments
- **Goals**: review_id, goal_description, target_date, achievement_percentage, status

## Troubleshooting

### Migration Issues

1. **"Cannot connect to seait_website"**
   - Verify seait_website database exists
   - Check database credentials
   - Ensure MySQL is running

2. **"Table already exists"**
   - Migration will drop and recreate tables
   - Backup data if needed before migrating

3. **"Permission denied"**
   - Verify user has HR role
   - Check session authentication
   - Verify database user permissions

### Performance Issues

1. **Slow Loading**
   - Check database indexes
   - Optimize table joins
   - Consider caching for frequent queries

2. **Missing Data**
   - Verify foreign key relationships
   - Check data integrity
   - Review error logs

## File Structure

```
nia-hris/
├── performance-reviews.php              # Main dashboard
├── conduct-performance-review.php       # Create/edit reviews
├── view-performance-review.php          # View/print reviews
├── install-performance-reviews.php      # Installation interface
├── migrate-performance-reviews.php      # Migration script
├── database/
│   └── performance_reviews_database.sql # SQL schema
└── README_PERFORMANCE_REVIEWS.md        # Full documentation
```

## URLs

### Main Access Points
- **Dashboard**: `http://localhost/nia-hris/performance-reviews.php`
- **Installation**: `http://localhost/nia-hris/install-performance-reviews.php`
- **Migration**: `http://localhost/nia-hris/migrate-performance-reviews.php`
- **Conduct Review**: `http://localhost/nia-hris/conduct-performance-review.php`
- **View Review**: `http://localhost/nia-hris/view-performance-review.php?id=[review_id]`

## Security Features

- ✅ **SQL Injection Protection**: All queries use prepared statements
- ✅ **XSS Protection**: HTML escaping on all outputs
- ✅ **Access Control**: Role-based permissions
- ✅ **ID Encryption**: Secure URL parameters
- ✅ **Input Validation**: Comprehensive sanitization

## Next Steps

1. **Run the migration** to copy all data from SEAIT
2. **Test the system** with a sample review
3. **Train HR staff** on the new interface
4. **Set up regular backups** for review data
5. **Monitor performance** and optimize as needed

## Support

For issues or questions:
- Check the full documentation: `README_PERFORMANCE_REVIEWS.md`
- Review database logs for errors
- Contact system administrator
- Check SEAIT source system for reference

## Success Metrics

Track these metrics to measure system effectiveness:
- **Review Completion Rate**: % of employees reviewed on time
- **Average Review Time**: Time to complete a review
- **Goal Achievement**: % of goals completed
- **System Adoption**: % of HR staff using the system
- **Data Accuracy**: Quality of review data entered

---

**Status**: Ready for migration and use
**Last Updated**: <?php echo date('Y-m-d'); ?>

**Migrated From**: SEAIT Website (seait_website database)
**Migrated To**: NIA-HRIS (nia_hris database)

