# NIA-HRIS Quick Reference

## 🔑 Default Login Credentials

| Username | Password | Role |
|----------|----------|------|
| `superadmin` | `Super@2024` | Super Admin |
| `admin` | `admin123` | Admin |
| `hrmanager` | `HRM@2024` | HR Manager |
| `hrstaff` | `HRStaff@2024` | HR Staff |
| `nurse1` | `Nurse@2024` | Nurse |

## 📁 Key Directories

```
config/          → Database configuration
includes/        → Shared PHP functions and components
api/             → API endpoints
assets/          → CSS, JS, images
database/        → SQL scripts and backups
uploads/         → File uploads
logs/            → System logs
```

## 🗄️ Database Connection

**File:** `config/database.php`
- Database: `nia_hris`
- Host: `localhost`
- Socket: `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`
- Charset: `utf8mb4`
- Timezone: `Asia/Manila`

## 🔐 Authentication Check Pattern

```php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse', 'employee'])) {
    header('Location: login.php');
    exit();
}
```

## 📋 Main Modules

| Module | Main File | Key Features |
|--------|-----------|--------------|
| **Employee** | `admin-employee.php` | CRUD operations, profile management |
| **Leave** | `leave-management.php` | 30+ leave types, approvals, balances |
| **Payroll** | `payroll-management.php` | Hours tracking, calculations, payslips |
| **Salary** | `salary-structures.php` | Structures, increments, history |
| **Performance** | `performance-reviews.php` | Reviews, scoring, goals |
| **Training** | `training-programs.php` | Programs, enrollment, materials |
| **Medical** | `medical-records.php` | Nurse-only updates |
| **Regularization** | `manage-regularization.php` | Probation to regular tracking |
| **Benefits** | `government-benefits.php` | SSS, PhilHealth, Pag-IBIG |
| **Departments** | `manage-departments.php` | Department CRUD |

## 🎯 User Roles & Permissions

### Super Admin
- ✅ Everything (full access)

### Admin
- ✅ Employee management, payroll, salary, leave, performance
- ❌ User management, settings, medical updates

### HR Manager
- ✅ Employee management, payroll, salary, leave, performance
- ❌ User management, settings, medical updates

### HR Staff
- ✅ View/add/edit employees, leave management
- ❌ Delete employees, salary, payroll, departments, medical updates

### Nurse
- ✅ View employees, view/update medical records
- ❌ All other operations

### Employee
- ✅ Own profile, leave requests, payslips
- ❌ All other operations

## 📊 Database Tables (32+)

### Core (5)
- `users`, `settings`, `activity_log`, `employees`, `departments`

### Employee (2)
- `employee_details`, `employee_benefits`

### Leave (5)
- `leave_types` (30 types), `employee_leave_requests`, `employee_leave_allowances`, etc.

### Payroll (7+2 views)
- `payroll_periods`, `payroll_records`, deduction/earning types, etc.

### Salary (6)
- `salary_structures`, `employee_salaries`, `increment_types` (20 types), etc.

### Performance (5)
- `performance_reviews`, `performance_review_categories` (12 categories), etc.

### Training (4)
- `training_categories` (6 categories), `training_programs`, etc.

### Regularization (5)
- `regularization_status` (7 statuses), `employee_regularization`, etc.

## 🔧 Common Functions

**File:** `includes/functions.php`
- `sanitize_input($data)` - Sanitize input
- `check_login()` - Verify authentication
- `get_user_role()` - Get current user role
- `logActivity($action, $description, $conn)` - Log activity

**File:** `includes/roles.php`
- `hasRole($role)` - Check specific role
- `hasAnyRole($roles)` - Check multiple roles
- `canViewEmployees()` - Permission checks
- `requireRole($roles)` - Require role

## 🚀 Quick Setup

1. **One-Click:** Run `complete-setup-wizard.php`
2. **Manual:**
   - `setup_database.php`
   - `setup-user-roles.php`
   - `migrate-medical-fields.php`
   - `create-sample-users.php`

## 📝 File Naming Patterns

- `*-management.php` → Main interface
- `add-*.php` → Create handler
- `edit-*.php` → Update handler
- `view-*.php` → Display page
- `get-*.php` → API endpoint
- `save-*.php` → Save handler
- `setup-*.php` → Installation script
- `migrate-*.php` → Migration script

## 🔗 Important URLs

- Dashboard: `index.php`
- Login: `login.php`
- Employee Dashboard: `employee-dashboard.php`
- Setup Wizard: `complete-setup-wizard.php`

## 📚 Documentation Files

- `CODEBASE_INDEX.md` - Complete codebase index (this file's source)
- `START_HERE.md` - Quick start guide
- `README.md` - Main documentation
- `SYSTEM_STATUS.md` - Current system status
- `ROLE_BASED_ACCESS_GUIDE.md` - RBAC details
- Module-specific READMEs (payroll, performance, etc.)

## ⚠️ Common Issues

### Database Connection
- Check `config/database.php`
- Verify MySQL socket path (macOS: `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`)

### Permissions
- Check `includes/roles.php` for role definitions
- Verify session variables are set after login

### File Uploads
- Ensure `uploads/` directory is writable
- Check file permissions

### Session Issues
- Verify `session_start()` is called
- Check PHP session configuration

## 🛠️ Development Utilities

### Testing Files
- `test_system.php` - System testing
- `test-*.php` - Module-specific tests

### Debug Files
- `debug-leave-calculator.php`
- `debug-session.php`

### Migration Scripts
- `migrate-*.php` - Various migrations
- `setup-*.php` - System setup scripts

---

**For detailed information, see `CODEBASE_INDEX.md`**

