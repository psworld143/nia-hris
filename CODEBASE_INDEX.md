# NIA-HRIS Codebase Index

**Last Updated:** January 2025  
**Version:** 1.0  
**Database:** nia_hris  
**Total PHP Files:** 100+  
**Total Database Tables:** 32+

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Directory Structure](#directory-structure)
3. [Core Configuration](#core-configuration)
4. [Authentication & Security](#authentication--security)
5. [Main Modules](#main-modules)
6. [Database Schema](#database-schema)
7. [API Endpoints](#api-endpoints)
8. [File Index](#file-index)
9. [Role-Based Access Control](#role-based-access-control)
10. [Setup & Installation](#setup--installation)

---

## System Overview

**NIA-HRIS** (National Irrigation Administration Human Resource Information System) is a standalone PHP/MySQL-based HR management system designed for government agency operations. It was extracted from SEAIT's HR module and customized for NIA.

### Key Technologies
- **Backend:** PHP 7.4+ with MySQLi
- **Frontend:** TailwindCSS, jQuery, Font Awesome
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **Server:** Apache/Nginx (XAMPP compatible)
- **Timezone:** Asia/Manila (UTC+8)

### System Status
- ✅ **Operational Status:** Fully Operational
- ✅ **Database Tables:** 32+ tables created
- ✅ **User Roles:** 6 roles implemented
- ✅ **Modules:** 8 major modules active

---

## Directory Structure

```
nia-hris/
├── api/                          # API endpoints
│   ├── get-benefit-config.php
│   ├── get-employee-salary.php
│   ├── get-employees.php
│   └── leave-allowance-api.php
├── assets/                       # Static assets
│   ├── css/                      # Stylesheets
│   ├── js/                       # JavaScript files
│   └── images/                   # Images and icons
├── bugs/                         # Bug tracking
├── config/                       # Configuration files
│   └── database.php              # Database connection
├── database/                     # SQL scripts and backups
├── includes/                     # Shared PHP includes
│   ├── functions.php             # Utility functions
│   ├── header.php                # Common header with sidebar
│   ├── footer.php                # Common footer
│   ├── id_encryption.php         # ID encryption utilities
│   ├── roles.php                 # RBAC definitions
│   ├── employee_id_generator.php
│   ├── leave_allowance_calculator.php
│   └── leave_allowance_calculator_v2.php
├── logs/                         # System logs
├── uploads/                      # File uploads
├── [PHP Files]                   # Main application files (see File Index)
└── [Documentation]               # Markdown documentation files
```

---

## Core Configuration

### Database Configuration
**File:** `config/database.php`
- **Host:** localhost (XAMPP)
- **Database:** nia_hris
- **Socket:** `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock` (macOS)
- **Charset:** utf8mb4
- **Timezone:** Asia/Manila

### Key Includes
- `includes/functions.php` - Core utility functions
- `includes/header.php` - Responsive header with sidebar navigation
- `includes/footer.php` - Common footer
- `includes/roles.php` - Role-based access control functions
- `includes/id_encryption.php` - ID encryption for security

---

## Authentication & Security

### Authentication System
**File:** `login.php`
- Session-based authentication
- Password hashing using PHP `password_hash()`
- Role validation on login
- Session variables: `user_id`, `username`, `first_name`, `last_name`, `role`

### Security Features
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (htmlspecialchars, sanitize_input)
- ✅ CSRF protection (session tokens)
- ✅ ID encryption for sensitive operations
- ✅ Password hashing (bcrypt)
- ✅ Activity logging with IP tracking
- ✅ Role-based access control (RBAC)

### Session Management
- Session start in all protected pages
- Automatic redirect to login if not authenticated
- Role-based page access control
- Session timeout handling

---

## Main Modules

### 1. Employee Management
**Key Files:**
- `admin-employee.php` - Employee listing and management
- `add-employee.php` - Add new employee
- `add-employee-comprehensive.php` - Comprehensive employee form
- `add-employee-comprehensive-form.php` - Employee form UI
- `edit-employee.php` - Edit employee records
- `view-employee.php` - View employee details
- `delete-employee.php` - Delete employee
- `force-delete-employee.php` - Force delete with cleanup
- `get-employees.php` - AJAX endpoint for employee listing
- `get-next-employee-id.php` - Employee ID generation
- `get-recent-employees.php` - Recent employees API

**Features:**
- Employee CRUD operations
- Employee ID auto-generation
- Profile photo upload
- Employment type management
- Department assignment
- Comprehensive personal information tracking

### 2. Leave Management
**Key Files:**
- `leave-management.php` - Main leave management interface
- `leave-balances.php` - Leave balance tracking
- `leave-allowance-management.php` - Leave allowance configuration
- `leave-reports.php` - Leave reporting
- `create-leave-request.php` - Create leave request handler
- `create-leave-request-form.php` - Leave request form
- `approve-leave.php` - Leave approval handler
- `employee-leave-history.php` - Employee leave history view
- `leave-accumulation-history.php` - Leave accumulation tracking
- `initialize-leave-allowances.php` - Leave allowance initialization
- `get-leave-types.php` - Leave types API
- `get-leave-details.php` - Leave details API
- `get-leave-history.php` - Leave history API
- `get-leave-allowance-details.php` - Leave allowance API
- `export-leave-balances.php` - Export leave balances
- `secure-delete-leave.php` - Secure leave deletion

**Database Tables:**
- `leave_types` (30 types)
- `employee_leave_requests`
- `employee_leave_allowances`
- `leave_accumulation_history`
- `leave_notifications`

**Features:**
- 30+ leave types
- Leave request workflow (pending → approved/rejected)
- Leave balance tracking
- Leave accumulation calculation
- Leave allowance management
- Leave reports and analytics

### 3. Payroll Management
**Key Files:**
- `payroll-management.php` - Payroll period management
- `payroll-process.php` - Payroll processing (hours entry, calculations)
- `payroll-view.php` - View payroll records
- `payroll-reports.php` - Payroll reporting
- `payslip-view.php` - Individual payslip view
- `employee-payslips.php` - Employee payslip listing
- `save-payroll-data.php` - Save payroll data handler
- `save-payroll-period.php` - Create/update payroll period
- `delete-payroll-period.php` - Delete payroll period
- `setup-payroll-system.php` - Payroll system setup
- `seed-payroll-periods.php` - Seed payroll periods

**Database Tables:**
- `payroll_periods`
- `payroll_records`
- `payroll_deduction_types` (12 types)
- `payroll_earning_types` (10 types)
- `payroll_custom_deductions`
- `payroll_custom_earnings`
- `payroll_adjustments`
- `payroll_audit_log`
- Views: `v_payroll_summary`, `v_payroll_statistics`

**Features:**
- Payroll period management (monthly/semi-monthly/bi-weekly/weekly)
- Hours tracking (regular, overtime, night differential)
- Automatic salary calculation
- Government benefits deduction (SSS, PhilHealth, Pag-IBIG, Tax)
- Payslip generation
- Payroll reports and analytics

### 4. Salary & Increment Management
**Key Files:**
- `salary-structures.php` - Salary structure management
- `salary-incrementation.php` - Salary increment processing
- `salary-monitor.php` - Salary monitoring dashboard
- `salary-audit-system.php` - Salary audit trail
- `enhanced-salary-increment.php` - Enhanced increment interface
- `create-increment-request.php` - Create increment request
- `review-increment-request.php` - Review increment requests
- `increment-requests.php` - Increment request listing
- `increment-reports.php` - Increment reports
- `increment-reports-dashboard.php` - Increment reports dashboard
- `confirm-increment.php` - Confirm increment handler
- `auto-increment-management.php` - Auto-increment management
- `auto-increment-processor.php` - Auto-increment processing
- `get-salary-history.php` - Salary history API
- `get-employee-salary.php` - Employee salary API

**Database Tables:**
- `salary_structures`
- `employee_salaries`
- `employee_salary_history`
- `salary_audit_log`
- `increment_types` (20 types)
- `increment_requests`

**Features:**
- Salary structure definition
- Salary increment processing
- Increment request workflow
- Salary history tracking
- Salary audit logging
- 20+ increment types

### 5. Performance Reviews
**Key Files:**
- `performance-reviews.php` - Performance review listing
- `conduct-performance-review.php` - Conduct performance review
- `view-performance-review.php` - View performance review
- `get-evaluation-scores.php` - Evaluation scores API
- `install-performance-reviews.php` - Performance review system setup
- `migrate-performance-reviews.php` - Performance review migration

**Database Tables:**
- `performance_reviews`
- `performance_review_categories` (12 categories)
- `performance_review_criteria`
- `performance_review_scores`
- `performance_review_goals`

**Features:**
- Performance review workflow
- 12 review categories
- Review criteria management
- Scoring system
- Goal tracking

### 6. Training & Development
**Key Files:**
- `training-programs.php` - Training program management
- `trainings.php` - Training listing
- `training-suggestions.php` - Training suggestions
- `add-training.php` - Add training program
- `migrate-training-system.php` - Training system migration
- `test-training-migration.php` - Training migration test

**Database Tables:**
- `training_categories` (6 categories)
- `training_programs`
- `employee_training`
- `training_materials`

**Features:**
- Training program management
- 6 training categories
- Employee training enrollment
- Training materials management
- Training suggestions

### 7. Medical Records
**Key Files:**
- `medical-records.php` - Medical records management
- `medical-records-new.php` - New medical records interface
- `add-medical-history.php` - Add medical history
- `get-medical-history.php` - Medical history API

**Features:**
- Medical history tracking
- Nurse-exclusive update permissions
- Medical records viewing
- Health information management

### 8. Regularization System
**Key Files:**
- `manage-regularization.php` - Regularization management
- `add-regularization.php` - Add regularization record
- `process-regularization.php` - Regularization processing
- `regularization-criteria.php` - Regularization criteria
- `get-regularization-criteria.php` - Regularization criteria API
- `setup-regularization.php` - Regularization system setup

**Database Tables:**
- `regularization_status` (7 statuses)
- `employee_regularization`
- `regularization_criteria`
- `regularization_reviews`
- `regularization_notifications`

**Features:**
- Probationary to regular status tracking
- Regularization criteria management
- Review scheduling
- Status workflow (7 statuses)

### 9. Benefits Management
**Key Files:**
- `government-benefits.php` - Government benefits management
- `manage-benefits.php` - Benefits management
- `manage-benefit-rates.php` - Benefit rates management
- `save-benefit-type.php` - Save benefit type handler
- `save-deduction-type.php` - Save deduction type handler
- `toggle-benefit-status.php` - Toggle benefit status
- `toggle-deduction-status.php` - Toggle deduction status
- `setup-benefits-system.php` - Benefits system setup
- `setup-benefit-deductions.php` - Benefit deductions setup
- `setup-government-benefits.php` - Government benefits setup

**Features:**
- Government benefits tracking (SSS, PhilHealth, Pag-IBIG)
- Benefit rates management
- Deduction type management
- Benefits status toggle

### 10. Department Management
**Key Files:**
- `manage-departments.php` - Department management
- `add-department.php` - Add department
- `edit-department.php` - Edit department
- `view-department.php` - View department
- `delete-department.php` - Delete department
- `get-department-details.php` - Department details API
- `toggle-department-status.php` - Toggle department status
- `migrate-department-foreign-key.php` - Department FK migration

**Features:**
- Department CRUD operations
- Department status management
- Department code and naming
- Department color themes

### 11. User Management
**Key Files:**
- `user-management.php` - User management interface
- `save-user.php` - Save user handler
- `toggle-user-status.php` - Toggle user status
- `reset-user-password.php` - Reset user password
- `reset-employee-passwords.php` - Bulk password reset
- `create-sample-users.php` - Create sample users
- `verify-accounts.php` - Verify user accounts
- `check-database-users.php` - Check database users

**Features:**
- User account management
- Role assignment
- User status management
- Password reset
- Account verification

### 12. Reports & Analytics
**Key Files:**
- `reports.php` - Main reports interface
- `leave-reports.php` - Leave reports
- `payroll-reports.php` - Payroll reports
- `increment-reports.php` - Increment reports
- `get-employee-stats.php` - Employee statistics API

**Features:**
- Comprehensive reporting system
- Leave analytics
- Payroll analytics
- Salary increment analytics
- Employee statistics

### 13. Settings & Configuration
**Key Files:**
- `settings.php` - System settings
- `categories.php` - Category management
- `manage-main-categories.php` - Main category management
- `sub-categories.php` - Sub-category management
- `get_sub_categories_hr.php` - Sub-category API

**Features:**
- System configuration
- Category management
- Settings persistence

### 14. Dashboard
**Key Files:**
- `index.php` - Main admin dashboard
- `employee-dashboard.php` - Employee dashboard
- `employee-profile.php` - Employee profile view

**Features:**
- Role-based dashboards
- Real-time statistics
- Quick actions
- Recent activities
- System information

---

## Database Schema

### Core System Tables (5 tables)
1. `users` - System users and authentication
2. `settings` - System configuration
3. `activity_log` - Audit trail
4. `employees` - Core employee records
5. `departments` - Department management (6 departments)

### Employee Details (2 tables)
6. `employee_details` - Personal info, family, government IDs
7. `employee_benefits` - Benefits tracking

### Regularization System (5 tables)
8. `regularization_status` - 7 statuses
9. `employee_regularization` - Employee regularization records
10. `regularization_criteria` - Regularization criteria
11. `regularization_reviews` - Regularization reviews
12. `regularization_notifications` - Regularization notifications

### Leave Management (5 tables)
13. `leave_types` - 30 leave types
14. `employee_leave_requests` - Leave requests
15. `employee_leave_allowances` - Leave allowances
16. `leave_accumulation_history` - Leave accumulation
17. `leave_notifications` - Leave notifications

### Salary & Compensation (6 tables)
18. `salary_structures` - Salary structures
19. `employee_salaries` - Employee salaries
20. `employee_salary_history` - Salary history
21. `salary_audit_log` - Salary audit trail
22. `increment_types` - 20 increment types
23. `increment_requests` - Increment requests

### Payroll System (7 tables + 2 views)
24. `payroll_periods` - Payroll periods
25. `payroll_records` - Payroll records
26. `payroll_deduction_types` - 12 deduction types
27. `payroll_earning_types` - 10 earning types
28. `payroll_custom_deductions` - Custom deductions
29. `payroll_custom_earnings` - Custom earnings
30. `payroll_adjustments` - Payroll adjustments
31. `payroll_audit_log` - Payroll audit trail
32. `v_payroll_summary` - Payroll summary view
33. `v_payroll_statistics` - Payroll statistics view

### Training & Development (4 tables)
34. `training_categories` - 6 categories
35. `training_programs` - Training programs
36. `employee_training` - Employee training records
37. `training_materials` - Training materials

### Performance Management (5 tables)
38. `performance_reviews` - Performance reviews
39. `performance_review_categories` - 12 categories
40. `performance_review_criteria` - Review criteria
41. `performance_review_scores` - Review scores
42. `performance_review_goals` - Review goals

**Note:** Additional tables may exist for medical records, job postings, and other features.

---

## API Endpoints

### Employee APIs
- `api/get-employees.php` - Get employee list
- `get-employees.php` - Employee listing endpoint
- `get-recent-employees.php` - Recent employees
- `get-next-employee-id.php` - Next employee ID
- `get-employee-stats.php` - Employee statistics
- `api/get-employee-salary.php` - Employee salary
- `get-salary-history.php` - Salary history

### Leave APIs
- `api/leave-allowance-api.php` - Leave allowance API
- `get-leave-types.php` - Leave types
- `get-leave-details.php` - Leave details
- `get-leave-history.php` - Leave history
- `get-leave-allowance-details.php` - Leave allowance details

### Payroll APIs
- `api/get-benefit-config.php` - Benefit configuration

### Department APIs
- `get-department-details.php` - Department details

### Other APIs
- `get-regularization-criteria.php` - Regularization criteria
- `get-evaluation-scores.php` - Evaluation scores
- `get-medical-history.php` - Medical history
- `get_sub_categories_hr.php` - Sub-categories

---

## File Index

### Core Files
- `index.php` - Main dashboard
- `login.php` - Login page
- `logout.php` - Logout handler
- `config/database.php` - Database configuration

### Employee Management (15+ files)
- `admin-employee.php`, `add-employee.php`, `edit-employee.php`, `view-employee.php`, `delete-employee.php`, `force-delete-employee.php`, `add-employee-comprehensive.php`, `add-employee-comprehensive-form.php`, `get-employees.php`, `get-next-employee-id.php`, `get-recent-employees.php`, `upload-photo.php`, `employee-profile.php`, `employee-dashboard.php`, `get-employee-stats.php`

### Leave Management (15+ files)
- `leave-management.php`, `leave-balances.php`, `leave-allowance-management.php`, `leave-reports.php`, `create-leave-request.php`, `create-leave-request-form.php`, `approve-leave.php`, `employee-leave-history.php`, `leave-accumulation-history.php`, `initialize-leave-allowances.php`, `get-leave-types.php`, `get-leave-details.php`, `get-leave-history.php`, `get-leave-allowance-details.php`, `export-leave-balances.php`, `secure-delete-leave.php`

### Payroll Management (12+ files)
- `payroll-management.php`, `payroll-process.php`, `payroll-view.php`, `payroll-reports.php`, `payslip-view.php`, `employee-payslips.php`, `save-payroll-data.php`, `save-payroll-period.php`, `delete-payroll-period.php`, `setup-payroll-system.php`, `seed-payroll-periods.php`

### Salary & Increment (15+ files)
- `salary-structures.php`, `salary-incrementation.php`, `salary-monitor.php`, `salary-audit-system.php`, `enhanced-salary-increment.php`, `create-increment-request.php`, `review-increment-request.php`, `increment-requests.php`, `increment-reports.php`, `increment-reports-dashboard.php`, `confirm-increment.php`, `auto-increment-management.php`, `auto-increment-processor.php`, `get-salary-history.php`

### Performance Reviews (6+ files)
- `performance-reviews.php`, `conduct-performance-review.php`, `view-performance-review.php`, `get-evaluation-scores.php`, `install-performance-reviews.php`, `migrate-performance-reviews.php`

### Training (6+ files)
- `training-programs.php`, `trainings.php`, `training-suggestions.php`, `add-training.php`, `migrate-training-system.php`, `test-training-migration.php`

### Medical Records (4+ files)
- `medical-records.php`, `medical-records-new.php`, `add-medical-history.php`, `get-medical-history.php`

### Regularization (6+ files)
- `manage-regularization.php`, `add-regularization.php`, `process-regularization.php`, `regularization-criteria.php`, `get-regularization-criteria.php`, `setup-regularization.php`

### Benefits (9+ files)
- `government-benefits.php`, `manage-benefits.php`, `manage-benefit-rates.php`, `save-benefit-type.php`, `save-deduction-type.php`, `toggle-benefit-status.php`, `toggle-deduction-status.php`, `setup-benefits-system.php`, `setup-benefit-deductions.php`, `setup-government-benefits.php`

### Department Management (8+ files)
- `manage-departments.php`, `add-department.php`, `edit-department.php`, `view-department.php`, `delete-department.php`, `get-department-details.php`, `toggle-department-status.php`, `migrate-department-foreign-key.php`

### User Management (8+ files)
- `user-management.php`, `save-user.php`, `toggle-user-status.php`, `reset-user-password.php`, `reset-employee-passwords.php`, `create-sample-users.php`, `verify-accounts.php`, `check-database-users.php`

### Settings & Utilities (10+ files)
- `settings.php`, `categories.php`, `manage-main-categories.php`, `sub-categories.php`, `get_sub_categories_hr.php`

### Reports (5+ files)
- `reports.php`, `leave-reports.php`, `payroll-reports.php`, `increment-reports.php`, `get-employee-stats.php`

### Setup & Migration Scripts (20+ files)
- `setup_database.php`, `setup-regularization.php`, `setup_complete_hr_system.php`, `setup-payroll-system.php`, `setup-benefits-system.php`, `setup-user-roles.php`, `setup-medical-history.php`, `setup-salary-audit.php`, `setup-dtr-system.php`, `setup-government-benefits.php`, `setup-cron-jobs.php`, `migrate-employee-types.php`, `migrate-medical-fields.php`, `migrate-department-foreign-key.php`, `migrate-performance-reviews.php`, `migrate-training-system.php`, `migrate-degrees-from-seait.php`, `complete-setup-wizard.php`, `run-all-setup.php`, `install-performance-reviews.php`

### Testing & Debug Files (10+ files)
- `test_system.php`, `test-add-employee.php`, `test-comprehensive-fix.php`, `test-db-insert.php`, `test-employee-api.php`, `test-employee-dashboard.php`, `test-employee-form.php`, `test-regularization.php`, `test-setup.php`, `test-training-migration.php`, `debug-leave-calculator.php`, `debug-session.php`

### Degree Management (5+ files)
- `manage-degrees.php`, `add-degree.php`, `delete-degree.php`, `toggle-degree-status.php`, `migrate-degrees-from-seait.php`, `setup-degrees-table.php`

### Other Features
- `job-postings.php`, `my-job-postings.php`, `questionnaires.php`, `dtr-management.php`, `upload-dtr-cards.php`, `verify-dtr-card.php`, `government-benefits.php`

---

## Role-Based Access Control

### User Roles

1. **Super Admin** (`super_admin`)
   - Full system access
   - User management
   - System settings
   - Medical records update
   - All HR functions

2. **Admin** (`admin`)
   - Employee management (CRUD)
   - Payroll processing
   - Salary management
   - Leave approval
   - Performance reviews
   - Department management
   - Cannot: Manage users, modify settings, update medical records

3. **HR Manager** (`hr_manager`)
   - Employee management (CRUD)
   - Payroll processing
   - Salary management
   - Leave management
   - Performance reviews
   - Department management
   - Cannot: Manage users, modify settings, update medical records

4. **HR Staff** (`human_resource`)
   - View employees
   - Add/edit employees
   - Leave management
   - Reports viewing
   - Cannot: Delete employees, manage salary, process payroll, manage departments, update medical records

5. **Nurse** (`nurse`)
   - View employees
   - View medical records
   - **Update medical records (exclusive)**
   - Medical reports
   - Cannot: Add/edit/delete employees, manage salary, process payroll

6. **Employee** (`employee`)
   - View own profile
   - View own leave requests
   - Create leave requests
   - View own payslips
   - Employee dashboard access

### RBAC Functions
**File:** `includes/roles.php`

Key Functions:
- `hasRole($role)` - Check specific role
- `hasAnyRole($roles)` - Check multiple roles
- `isSuperAdmin()`, `isAdmin()`, `isHRManager()`, etc.
- `canViewEmployees()`, `canAddEmployees()`, etc.
- `requireRole($roles)` - Require role or redirect
- `hasPermission($permission)` - Check permission
- `getRolePermissions($role)` - Get all permissions for role

---

## Setup & Installation

### Quick Setup
1. Run `complete-setup-wizard.php` (one-click setup)
2. Default credentials provided in `START_HERE.md`

### Manual Setup Steps
1. **Database Setup:** `setup_database.php`
2. **User Roles:** `setup-user-roles.php`
3. **Medical Fields:** `migrate-medical-fields.php`
4. **User Accounts:** `create-sample-users.php`
5. **Verification:** `verify-accounts.php`

### Database Setup Scripts
- `setup_database.php` - Core system tables
- `setup-regularization.php` - Regularization system
- `setup_complete_hr_system.php` - Complete HR system
- `setup-payroll-system.php` - Payroll system
- `setup-benefits-system.php` - Benefits system
- `setup-user-roles.php` - User roles
- `setup-medical-history.php` - Medical history
- `complete-setup-wizard.php` - Complete setup wizard

### Documentation Files
- `README.md` - Main documentation
- `START_HERE.md` - Quick start guide
- `INSTALLATION.md` - Installation guide
- `SYSTEM_STATUS.md` - System status
- `ROLE_BASED_ACCESS_GUIDE.md` - RBAC guide
- `README_PAYROLL_SYSTEM.md` - Payroll system docs
- `README_INCREMENT_REQUESTS.md` - Increment requests
- `README_PERFORMANCE_REVIEWS.md` - Performance reviews
- And 10+ more documentation files

---

## Key Utilities

### Functions (`includes/functions.php`)
- `sanitize_input($data)` - Input sanitization
- `check_login()` - Login verification
- `check_hr_access()` - HR access check
- `get_user_role()` - Get user role
- `is_logged_in()` - Check login status
- `redirect($url)` - Safe redirect
- `logActivity($action, $description, $conn)` - Activity logging
- `getCurrentAcademicYearAndSemester()` - Academic year utility

### ID Encryption (`includes/id_encryption.php`)
- Encrypt/decrypt employee IDs for security
- Prevents direct ID manipulation
- Used in sensitive operations

### Leave Calculator (`includes/leave_allowance_calculator*.php`)
- Leave balance calculation
- Leave accumulation logic
- Leave allowance computation

---

## File Naming Conventions

- `*-management.php` - Main management interfaces
- `add-*.php` - Add/create handlers
- `edit-*.php` - Edit/update handlers
- `view-*.php` - View/display pages
- `delete-*.php` - Delete handlers
- `get-*.php` - API/AJAX endpoints
- `save-*.php` - Save/create handlers
- `toggle-*.php` - Status toggle handlers
- `setup-*.php` - Setup/installation scripts
- `migrate-*.php` - Database migration scripts
- `test-*.php` - Testing scripts
- `debug-*.php` - Debug utilities

---

## Common Patterns

### Page Structure
```php
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], [...roles])) {
    header('Location: login.php');
    exit();
}

// Check permissions
requireRole([...roles]);
// or
requirePermission('canFunctionName');

// Page logic here

include 'includes/header.php';
// HTML content
include 'includes/footer.php';
?>
```

### Database Queries
- Always use prepared statements
- Error handling with mysqli_error()
- Transaction support where needed

### Form Handling
- POST method for data submission
- Input sanitization with `sanitize_input()`
- CSRF protection (where implemented)
- Success/error message display

---

## Development Notes

### Active Development Areas
- Employee management enhancements
- Payroll system improvements
- Leave management refinements
- Medical records system

### Known Issues
- Check `bugs/listOfBugs.txt` for tracked issues

### Migration Notes
- System was extracted from SEAIT HR module
- Faculty/college features removed
- Government agency structure adapted

---

## Support & Resources

### Configuration Files
- `config/database.php` - Database connection
- `includes/header.php` - Navigation and layout

### Key Documentation
- `README.md` - Main documentation
- `START_HERE.md` - Quick start
- `SYSTEM_STATUS.md` - Current status
- Module-specific README files

### Logs
- Check `logs/` directory for error logs
- Database activity in `activity_log` table
- Payroll audit in `payroll_audit_log`
- Salary audit in `salary_audit_log`

---

**End of Codebase Index**

For specific module documentation, refer to the respective README files in the root directory.

