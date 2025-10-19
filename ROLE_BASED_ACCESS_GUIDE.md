# Role-Based Access Control (RBAC) Guide

## Overview
The NIA-HRIS system now supports comprehensive role-based access control with 5 distinct user roles, each with specific permissions and access levels.

## User Roles

### 1. Super Admin 🔴
**Full System Access**
- Can perform **ALL** functions in the system
- Manages system settings and configurations
- Creates and manages all user accounts
- Has override access to all features
- Can view and modify all data

**Default Username:** `admin`  
**Recommended for:** System administrators only

---

### 2. Admin 🟢
**Administrative Access**
- Manages employees (add, edit, delete)
- Processes payroll
- Manages salary structures
- Approves leave requests
- Conducts performance reviews
- Manages departments and training programs
- Views all reports
- **Cannot:** Manage users, modify system settings, update medical records

**Recommended for:** Department heads, senior HR staff

---

### 3. HR Manager 🔵
**HR Management Functions**
- Manages employees (add, edit, delete)
- Processes payroll
- Manages salary structures
- Manages leave requests
- Conducts performance reviews
- Manages departments
- Views reports
- **Cannot:** Manage users, modify settings, update medical records

**Recommended for:** HR department managers

---

### 4. HR Staff (Human Resource) 🔵
**HR Operations**
- Views employee information
- Adds and edits employees
- Manages leave requests
- Views reports
- Basic HR functions
- **Cannot:** Delete employees, manage salary, process payroll, manage departments, update medical records

**Recommended for:** HR staff members, HR assistants

---

### 5. Nurse 🟣
**Medical Records Management**
- Views employee information
- **Views medical records** (all employees)
- **Updates medical records** (exclusive permission)
- Views medical-related reports
- **Cannot:** Add/edit/delete employees, manage salary, process payroll, manage departments

**Recommended for:** Medical staff, company nurses, health officers

---

## Permission Matrix

| Function | Super Admin | Admin | HR Manager | HR Staff | Nurse |
|----------|-------------|-------|------------|----------|-------|
| **Employee Management** |
| View Employees | ✅ | ✅ | ✅ | ✅ | ✅ |
| Add Employees | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit Employees | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete Employees | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Medical Records** |
| View Medical Records | ✅ | ✅ | ✅ | ✅ | ✅ |
| Update Medical Records | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Financial** |
| View Salary Information | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Salary Structures | ✅ | ✅ | ✅ | ❌ | ❌ |
| Process Payroll | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Administration** |
| Manage Users | ✅ | ❌ | ❌ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manage Departments | ✅ | ✅ | ✅ | ❌ | ❌ |
| **HR Functions** |
| Manage Leave Requests | ✅ | ✅ | ✅ | ✅ | ❌ |
| Performance Reviews | ✅ | ✅ | ✅ | ❌ | ❌ |
| Training Programs | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Reporting** |
| View Reports | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Setup Instructions

### Step 1: Run User Roles Setup
```
http://localhost/nia-hris/setup-user-roles.php
```

This will:
- Update the users table role enum
- Add super_admin and nurse roles
- Update existing admin to super_admin
- Show role distribution

### Step 2: Add Medical Record Fields
```
http://localhost/nia-hris/migrate-medical-fields.php
```

This adds medical record fields to the employees table:
- blood_type
- medical_conditions
- allergies
- medications
- last_medical_checkup
- medical_notes
- emergency_contact_name
- emergency_contact_number

### Step 3: Create User Accounts

#### Creating a Nurse Account
1. Log in as Super Admin
2. Go to User Management (if available)
3. Create new user with role: `nurse`
4. Or manually via database:

```sql
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('nurse1', PASSWORD_HASH, 'Jane', 'Doe', 'nurse@hospital.com', 'nurse', 'active');
```

---

## Using the System

### For Super Admin
Access all features through the main navigation menu.

### For Admin/HR Manager/HR Staff
Standard HR functions through the navigation menu:
- Employee Management
- Payroll (if authorized)
- Leave Management
- Reports

### For Nurses
Special medical records interface:
```
http://localhost/nia-hris/medical-records.php
```

**Features:**
- View all employees
- Search employees
- View medical records
- Update medical information
- Track medical checkups
- Manage emergency contacts
- Document allergies and medications

---

## Medical Records Management

### What Nurses Can Do:

#### 1. View Employee Medical Information
- Blood type
- Medical conditions
- Allergies
- Current medications
- Last checkup date
- Medical notes

#### 2. Update Medical Records
- Update blood type
- Document medical conditions
- Record allergies
- List current medications
- Track medical checkups
- Add medical notes
- Update emergency contacts

#### 3. View Statistics
- Total employees
- Employees with allergies
- Employees on medication
- Recent checkups

### Medical Record Fields

**Required Information:**
- Blood Type: A+, A-, B+, B-, AB+, AB-, O+, O-
- Medical Conditions: Chronic illnesses, disabilities
- Allergies: Drug allergies, food allergies
- Medications: Current prescriptions

**Emergency Information:**
- Emergency Contact Name
- Emergency Contact Phone

**Tracking:**
- Last Medical Checkup Date
- Medical Notes (observations, recommendations)

---

## Security Best Practices

### 1. Password Security
- Use strong passwords (8+ characters, mixed case, numbers, symbols)
- Change default passwords immediately
- Never share passwords

### 2. Role Assignment
- Assign minimum necessary permissions
- Review user roles quarterly
- Remove inactive users promptly

### 3. Medical Records
- Access only when necessary
- Maintain patient confidentiality
- Log all medical record updates
- Follow HIPAA/privacy guidelines

### 4. Audit Trail
All actions are logged with:
- User ID and role
- Action performed
- Timestamp
- IP address

---

## Technical Implementation

### Role Checking Functions

```php
// Include roles helper
require_once 'includes/roles.php';

// Check specific role
if (isSuperAdmin()) {
    // Super admin only code
}

if (isNurse()) {
    // Nurse only code
}

// Check permissions
if (canViewMedicalRecords()) {
    // Show medical records
}

if (canUpdateMedicalRecords()) {
    // Allow medical record updates
}

// Require permission
requirePermission('canManageSalary');
```

### Protecting Pages

```php
// At top of page
require_once 'includes/roles.php';

// Method 1: Check and redirect
if (!canViewEmployees()) {
    header('Location: index.php');
    exit();
}

// Method 2: Require permission
requirePermission('canProcessPayroll', 'index.php');
```

---

## Troubleshooting

### Issue: Cannot access medical records page
**Solution:** 
- Ensure user role is set correctly
- Run setup-user-roles.php
- Check session is active

### Issue: Cannot update medical records as nurse
**Solution:**
- Verify role is exactly 'nurse' (not 'Nurse')
- Run migrate-medical-fields.php
- Check form permissions

### Issue: Super admin lost privileges
**Solution:**
```sql
UPDATE users SET role = 'super_admin' WHERE username = 'admin';
```

---

## Customization

### Adding Custom Permissions

Edit `includes/roles.php`:

```php
function canCustomFunction() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_CUSTOM]);
}
```

### Modifying Role Permissions

Edit the `getRolePermissions()` function in `includes/roles.php`:

```php
ROLE_NURSE => [
    'view_employees' => true,
    'custom_permission' => true,
    // Add more permissions
],
```

---

## Support and Maintenance

### Regular Tasks
1. Review user access quarterly
2. Audit medical record access logs
3. Update user passwords every 90 days
4. Review and remove inactive accounts

### Backup Recommendations
- Daily backup of users table
- Weekly backup of employees table (includes medical data)
- Secure backup storage with encryption

---

## Compliance Notes

### Data Privacy
- Medical records are sensitive personal data
- Implement proper access controls
- Maintain audit trails
- Follow local data protection laws

### HIPAA Considerations (if applicable)
- Encrypt medical data
- Log all access to medical records
- Implement strong authentication
- Regular security audits

---

## Version History

**Version 1.0** - Initial RBAC implementation
- Added 5 user roles
- Implemented permission system
- Created medical records interface
- Added nurse role functionality

---

## Contact & Support

For questions about role-based access:
- System Administrator
- HR Department
- IT Support Team

**Documentation Updated:** October 2024

