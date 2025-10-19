# User Accounts Reference

## 🔐 Default Account Credentials

### **Current System Accounts**

---

## 1️⃣ **Super Admin Account**

```
Username: superadmin
Password: Super@2024
Role: super_admin
Email: superadmin@nia.gov.ph
```

**Access Level:** FULL SYSTEM ACCESS
- ✅ All functions
- ✅ User management
- ✅ System settings
- ✅ Medical records (view & update)
- ✅ All reports

**Use For:** System administration, full control

---

## 2️⃣ **Admin Account** (Default/Existing)

```
Username: admin
Password: admin123
Role: admin (upgrades to super_admin after setup)
Email: admin@nia-hris.com
```

**Access Level:** ADMINISTRATIVE
- ✅ Employee management
- ✅ Payroll processing
- ✅ Department management
- ✅ Reports
- ❌ User management
- ❌ System settings
- ❌ Medical record updates

**Use For:** HR administration, payroll

---

## 3️⃣ **HR Manager Account**

```
Username: hrmanager
Password: HRM@2024
Role: hr_manager
Email: hrmanager@nia.gov.ph
```

**Access Level:** HR MANAGEMENT
- ✅ Employee management
- ✅ Leave management
- ✅ Performance reviews
- ✅ Salary management
- ✅ Payroll processing
- ❌ User management
- ❌ Medical record updates

**Use For:** HR department management

---

## 4️⃣ **HR Staff Account**

```
Username: hrstaff
Password: HRStaff@2024
Role: human_resource
Email: hrstaff@nia.gov.ph
```

**Access Level:** HR OPERATIONS
- ✅ View employees
- ✅ Add/edit employees
- ✅ Leave management
- ✅ Basic reports
- ❌ Delete employees
- ❌ Salary management
- ❌ Payroll processing
- ❌ Medical record updates

**Use For:** Daily HR operations, employee records

---

## 5️⃣ **Nurse Account**

```
Username: nurse1
Password: Nurse@2024
Role: nurse
Email: nurse@nia.gov.ph
```

**Access Level:** MEDICAL RECORDS
- ✅ View all employees
- ✅ View medical records
- ✅ **UPDATE MEDICAL RECORDS** (Exclusive)
- ✅ Medical reports
- ❌ Add/edit/delete employees
- ❌ Salary/payroll access
- ❌ HR functions

**Use For:** Medical records management, health monitoring

**Special Access:** `http://localhost/nia-hris/medical-records.php`

---

## 📋 Quick Reference Table

| Role | Username | Password | Email | Primary Function |
|------|----------|----------|-------|-----------------|
| Super Admin | `superadmin` | `Super@2024` | superadmin@nia.gov.ph | Full System Access |
| Admin | `admin` | `admin123` | admin@nia-hris.com | HR & Payroll |
| HR Manager | `hrmanager` | `HRM@2024` | hrmanager@nia.gov.ph | HR Management |
| HR Staff | `hrstaff` | `HRStaff@2024` | hrstaff@nia.gov.ph | HR Operations |
| Nurse | `nurse1` | `Nurse@2024` | nurse@nia.gov.ph | Medical Records |

---

## 🚀 How to Create These Accounts

### **Method 1: Run Auto-Setup Script (RECOMMENDED)**

```
http://localhost/nia-hris/create-sample-users.php
```

This will:
- ✅ Create all sample accounts automatically
- ✅ Hash passwords securely
- ✅ Skip existing accounts
- ✅ Show creation results
- ✅ Display credentials table

---

### **Method 2: Manual SQL Creation**

```sql
-- Super Admin
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('superadmin', '$2y$10$...', 'Juan', 'Dela Cruz', 'superadmin@nia.gov.ph', 'super_admin', 'active');

-- HR Manager
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('hrmanager', '$2y$10$...', 'Maria', 'Garcia', 'hrmanager@nia.gov.ph', 'hr_manager', 'active');

-- HR Staff
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('hrstaff', '$2y$10$...', 'Pedro', 'Santos', 'hrstaff@nia.gov.ph', 'human_resource', 'active');

-- Nurse
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('nurse1', '$2y$10$...', 'Ana', 'Reyes', 'nurse@nia.gov.ph', 'nurse', 'active');
```

**Note:** Replace `$2y$10$...` with actual hashed passwords using `password_hash()`

---

## 🔒 Security Best Practices

### ⚠️ **IMPORTANT: Change Default Passwords!**

**After first login, immediately change:**
1. ✅ All default passwords
2. ✅ Use strong passwords (8+ characters)
3. ✅ Mix uppercase, lowercase, numbers, symbols
4. ✅ Don't reuse passwords
5. ✅ Store securely (password manager)

### **Strong Password Examples:**
- `NIA#Hr2024!Manager`
- `Nurse@Medical2024$`
- `SuperAdmin*2024#Secure`

### **Password Requirements:**
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- At least 1 special character (@, #, $, !, etc.)

---

## 🧪 Testing Accounts

### **Test Login Flow:**

1. **Login as Super Admin**
   - URL: `http://localhost/nia-hris/login.php`
   - Username: `superadmin`
   - Password: `Super@2024`
   - Verify: Can access all menus

2. **Login as Nurse**
   - URL: `http://localhost/nia-hris/login.php`
   - Username: `nurse1`
   - Password: `Nurse@2024`
   - Navigate to: Medical Records
   - Verify: Can view and edit medical records

3. **Login as HR Staff**
   - URL: `http://localhost/nia-hris/login.php`
   - Username: `hrstaff`
   - Password: `HRStaff@2024`
   - Verify: Limited menu options
   - Try: Cannot access medical records update

---

## 📊 Permission Comparison

| Feature | Super Admin | Admin | HR Manager | HR Staff | Nurse |
|---------|-------------|-------|------------|----------|-------|
| View Employees | ✅ | ✅ | ✅ | ✅ | ✅ |
| Add Employees | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit Employees | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete Employees | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Medical | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Update Medical** | ✅ | ❌ | ❌ | ❌ | ✅ |
| Manage Salary | ✅ | ✅ | ✅ | ❌ | ❌ |
| Process Payroll | ✅ | ✅ | ✅ | ❌ | ❌ |
| Manage Users | ✅ | ❌ | ❌ | ❌ | ❌ |
| System Settings | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 🔄 Password Reset Process

### **If Password is Lost:**

#### **Option 1: Database Reset (Admin Access)**
```sql
-- Reset password to 'newpassword123'
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'username_here';
```

#### **Option 2: PHP Script**
Create file `reset-password.php`:
```php
<?php
require_once 'config/database.php';
$username = 'username_here';
$new_password = 'NewPassword123!';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$query = "UPDATE users SET password = ? WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $hashed, $username);
mysqli_stmt_execute($stmt);
echo "Password reset successful!";
?>
```

---

## 📝 Creating Additional Accounts

### **For New Nurse:**
```sql
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('nurse2', PASSWORD_HASH, 'Rosa', 'Martinez', 'nurse2@nia.gov.ph', 'nurse', 'active');
```

### **For New HR Staff:**
```sql
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('hrstaff2', PASSWORD_HASH, 'Luis', 'Ramos', 'hrstaff2@nia.gov.ph', 'human_resource', 'active');
```

---

## 🎯 Quick Access URLs

| User Type | Login URL | Dashboard | Special Page |
|-----------|-----------|-----------|--------------|
| All Users | [/login.php](http://localhost/nia-hris/login.php) | [/index.php](http://localhost/nia-hris/index.php) | - |
| Super Admin | Same | Same | [/settings.php](http://localhost/nia-hris/settings.php) |
| Nurse | Same | Same | [/medical-records.php](http://localhost/nia-hris/medical-records.php) |
| HR Staff | Same | Same | [/admin-employee.php](http://localhost/nia-hris/admin-employee.php) |

---

## ✅ Account Setup Checklist

- [ ] Run `create-sample-users.php`
- [ ] Verify all accounts created successfully
- [ ] Test login for each role
- [ ] Change all default passwords
- [ ] Document new passwords securely
- [ ] Test permissions for each role
- [ ] Verify nurse can access medical records
- [ ] Verify HR staff cannot update medical records
- [ ] Review and customize as needed

---

## 📞 Support

**For Account Issues:**
- Super Admin access required
- Database access for password resets
- Check activity logs for security audit

**Documentation:**
- Full Guide: `ROLE_BASED_ACCESS_GUIDE.md`
- Quick Setup: `RBAC_QUICK_SETUP.md`

---

**Last Updated:** October 2024  
**Version:** 1.0  
**Status:** Sample credentials for initial setup

⚠️ **REMINDER: These are DEFAULT passwords. Change them immediately!**

