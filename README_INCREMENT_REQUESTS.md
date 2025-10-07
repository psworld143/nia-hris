# SEAIT HR Increment Requests System

## Overview
A comprehensive salary increment requests management system for SEAIT's Human Resource department. This system allows HR staff to create, manage, review, and approve salary increment requests with full audit trails and reporting capabilities.

## Features

### 🎯 Core Functionality
- **Request Management**: Create, edit, and track increment requests
- **Multi-level Approval Workflow**: Configurable approval levels based on amount and type
- **Employee Integration**: Works with both faculty and staff employee records
- **Comprehensive Audit Trail**: Full history tracking of all actions
- **Document Attachments**: Support for supporting documents
- **Comments System**: Internal and external communication on requests

### 📊 Reporting & Analytics
- **Dashboard Analytics**: Real-time statistics and KPIs
- **Department Analysis**: Performance metrics by department
- **Trend Analysis**: Monthly and yearly trend reporting
- **Export Capabilities**: PDF, Excel, CSV export options
- **Visual Charts**: Interactive charts using Chart.js

### 🔐 Security & Permissions
- **Role-based Access**: HR and HR Manager role restrictions
- **Audit Logging**: Complete action history with IP tracking
- **Data Validation**: Server-side and client-side validation
- **Secure File Uploads**: Controlled document attachments

## Installation Instructions

### Step 1: Database Setup
Run the database schema creation script:

```sql
-- Execute this file in your MySQL/MariaDB database
SOURCE /path/to/seait-1/database/increment_requests_system.sql;
```

**CRITICAL**: This script will create all necessary tables, views, stored procedures, and sample data. Make sure to backup your database before running.

### Step 2: File Structure
The system includes the following files:

```
human-resource/
├── increment-requests.php              # Main dashboard
├── create-increment-request.php        # Create new requests
├── review-increment-request.php        # Review and approve requests
├── increment-reports-dashboard.php     # Analytics and reporting
├── api/
│   ├── get-employees.php              # Employee data API
│   └── get-employee-salary.php        # Salary calculation API
└── database/
    └── increment_requests_system.sql   # Database schema
```

### Step 3: Navigation Update
The system is already integrated into the HR navigation menu via `includes/header.php`:

- **Increment Requests** → `salary-incrementation.php` (updated to use new system)
- **Add Salary Increment** → `add-salary-increment.php` 
- **Auto Increment** → `auto-increment-management.php`
- **Increment Reports** → `increment-reports.php`

### Step 4: Permissions Setup
Ensure users have the correct roles:

```sql
-- HR Staff
UPDATE users SET role = 'human_resource' WHERE id = [user_id];

-- HR Managers (can approve higher amounts)
UPDATE users SET role = 'hr_manager' WHERE id = [user_id];
```

## Database Schema

### Main Tables Created

1. **increment_types** - Types of increments (Merit, Promotion, COLA, etc.)
2. **increment_approval_levels** - Configurable approval workflow
3. **increment_requests** - Main requests table
4. **increment_request_approvals** - Approval tracking
5. **increment_request_history** - Complete audit trail
6. **increment_request_comments** - Communication system
7. **increment_request_attachments** - File attachments

### Key Features of Schema

- **Foreign Key Constraints**: Ensures data integrity
- **Indexes**: Optimized for performance
- **Views**: Pre-built queries for common operations
- **Stored Procedures**: Automated workflows
- **JSON Fields**: Flexible data storage for attachments and metadata

## Usage Guide

### For HR Staff

1. **Creating Requests**:
   - Navigate to "Increment Requests" → "New Request"
   - Select employee type (Faculty/Staff)
   - Choose employee and increment type
   - Enter justification and supporting details
   - Save as draft or submit for approval

2. **Managing Requests**:
   - View all requests in the main dashboard
   - Filter by status, department, or date range
   - Search by employee name or request number
   - Export data for external reporting

3. **Review Process**:
   - Click "Review" on submitted requests
   - Add comments and feedback
   - Approve, reject, or put on hold
   - View complete request history

### For HR Managers

- **Advanced Reporting**: Access comprehensive analytics dashboard
- **Bulk Operations**: Manage multiple requests efficiently
- **System Configuration**: Modify increment types and approval levels
- **Audit Reviews**: Monitor system usage and compliance

## Configuration Options

### Increment Types
Modify `increment_types` table to add/edit:
- Merit increases
- Promotions
- Cost of living adjustments
- Performance bonuses
- Educational qualifications
- Experience increments

### Approval Levels
Configure `increment_approval_levels` for:
- Amount thresholds
- Required roles
- Timeout periods
- Auto-approval rules

### Salary Calculation
Update `api/get-employee-salary.php` to integrate with your existing payroll system or modify the calculation logic.

## Integration Points

### Existing HR System
- **Employees Table**: Links to existing employee records
- **Faculty Table**: Integrates with faculty management
- **Users Table**: Uses existing authentication system
- **Leave System**: Similar workflow patterns

### Future Enhancements
- **Email Notifications**: Integration with SMTP system
- **Mobile Responsive**: Already optimized for mobile devices
- **API Endpoints**: RESTful APIs for external integrations
- **Advanced Reporting**: Power BI or Tableau integration

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Verify database credentials in `config/database.php`
   - Ensure MySQL/MariaDB service is running
   - Check table creation was successful

2. **Permission Denied**:
   - Verify user roles in database
   - Check session management
   - Ensure proper authentication

3. **File Upload Issues**:
   - Check PHP upload limits
   - Verify directory permissions
   - Ensure proper file type validation

### Performance Optimization

- **Database Indexes**: Already optimized with composite indexes
- **Query Optimization**: Uses prepared statements and efficient joins
- **Caching**: Consider implementing Redis for session management
- **File Storage**: Consider cloud storage for attachments

## Security Considerations

### Data Protection
- **SQL Injection Prevention**: All queries use prepared statements
- **XSS Protection**: HTML escaping on all outputs
- **CSRF Protection**: Form tokens recommended for production
- **File Upload Security**: Type and size validation

### Access Control
- **Role-based Permissions**: Granular access control
- **Audit Logging**: Complete action tracking
- **Session Management**: Secure session handling
- **IP Tracking**: Monitor access patterns

## Support & Maintenance

### Regular Maintenance
- **Database Cleanup**: Archive old requests periodically
- **Log Rotation**: Manage audit log size
- **Backup Strategy**: Regular database backups
- **Performance Monitoring**: Monitor query performance

### Updates & Patches
- **Version Control**: Track system changes
- **Testing Environment**: Test updates before production
- **User Training**: Keep staff updated on new features
- **Documentation**: Maintain current documentation

## Success Metrics

The system provides comprehensive metrics to measure success:

- **Processing Time**: Average approval time reduction
- **Transparency**: Complete audit trails
- **Efficiency**: Reduced manual paperwork
- **Compliance**: Standardized approval processes
- **Reporting**: Real-time analytics and insights

## Conclusion

This increment requests system provides a modern, efficient, and secure solution for managing salary increments at SEAIT. The system is designed to be scalable, maintainable, and user-friendly while providing comprehensive audit trails and reporting capabilities.

For technical support or feature requests, contact the IT department or system administrator.

---

**Created**: October 2025  
**Version**: 1.0  
**Author**: SEAIT IT Department  
**Last Updated**: October 2, 2025
