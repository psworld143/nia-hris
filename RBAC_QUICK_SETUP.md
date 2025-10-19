# Quick Setup: Role-Based Access Control

## 🚀 Quick Start (3 Steps)

### Step 1: Setup User Roles (2 minutes)
```
http://localhost/nia-hris/setup-user-roles.php
```
✅ Updates database to support Super Admin and Nurse roles  
✅ Converts existing admin to super_admin  
✅ Shows permission matrix

---

### Step 2: Add Medical Record Fields (1 minute)
```
http://localhost/nia-hris/migrate-medical-fields.php
```
✅ Adds medical record fields to employees table  
✅ Enables medical records management

---

### Step 3: Access Medical Records (Nurses)
```
http://localhost/nia-hris/medical-records.php
```
✅ View all employee medical information  
✅ Update medical records (Nurses only)  
✅ Track checkups and medications

---

## 👤 User Roles Summary

| Role | Key Features | Access Level |
|------|-------------|--------------|
| **Super Admin** 🔴 | Everything | Full System |
| **Admin** 🟢 | HR + Payroll | High |
| **HR Manager** 🔵 | HR Functions | Medium |
| **HR Staff** 🔵 | Basic HR | Limited |
| **Nurse** 🟣 | Medical Records | Specialized |

---

## 🏥 Nurse Capabilities

✅ **CAN DO:**
- View all employees
- View medical records
- **Update medical records** (EXCLUSIVE)
- Track blood types
- Document allergies
- List medications
- Update emergency contacts
- Record medical checkups

❌ **CANNOT DO:**
- Add/edit/delete employees
- Manage salary/payroll
- Manage departments
- Process leave requests

---

## 📋 Super Admin Capabilities

✅ **CAN DO EVERYTHING:**
- All employee functions
- All HR functions
- Payroll processing
- User management
- System settings
- **Full medical record access**
- All reports

---

## 🔐 Default Login

**Username:** `admin`  
**Password:** `admin123`  
**Role:** Will be upgraded to `super_admin`

⚠️ **Change default password immediately!**

---

## 📝 Creating a Nurse Account

### Option 1: Via SQL (Quick)
```sql
INSERT INTO users (username, password, first_name, last_name, email, role, status) 
VALUES ('nurse1', '$2y$10$...', 'Maria', 'Santos', 'nurse@nia.gov.ph', 'nurse', 'active');
```

### Option 2: Via PHP Script
Create file `create-nurse.php`:
```php
<?php
require_once 'config/database.php';
$password = password_hash('nurse123', PASSWORD_DEFAULT);
$query = "INSERT INTO users (username, password, first_name, last_name, email, role) 
          VALUES ('nurse1', '$password', 'Maria', 'Santos', 'nurse@nia.gov.ph', 'nurse')";
mysqli_query($conn, $query);
echo "Nurse account created!";
?>
```

---

## 🎯 Quick Test

1. **Login as Super Admin**
   - Go to: `http://localhost/nia-hris/login.php`
   - Use: `admin` / `admin123`
   - Verify: You can access everything

2. **Create Nurse Account**
   - Use SQL or script above
   - Set role to `nurse`

3. **Login as Nurse**
   - Go to: `http://localhost/nia-hris/login.php`
   - Use nurse credentials
   - Go to: Medical Records page
   - Verify: Can view and edit medical records

4. **Test Permissions**
   - Try accessing different pages
   - Verify redirects work correctly

---

## 🛠️ Files Created

| File | Purpose |
|------|---------|
| `setup-user-roles.php` | Database migration for roles |
| `migrate-medical-fields.php` | Add medical fields |
| `includes/roles.php` | Permission system |
| `medical-records.php` | Medical records interface |
| `ROLE_BASED_ACCESS_GUIDE.md` | Full documentation |

---

## ✅ Verification Checklist

- [ ] Run `setup-user-roles.php` successfully
- [ ] Run `migrate-medical-fields.php` successfully  
- [ ] Login as super_admin works
- [ ] Create nurse account
- [ ] Nurse can access medical-records.php
- [ ] Nurse can update medical records
- [ ] HR staff cannot update medical records
- [ ] Permissions are enforced correctly

---

## 🚨 Troubleshooting

**Problem:** Page shows blank/error  
**Solution:** Check PHP error logs, verify database connection

**Problem:** Cannot access medical records  
**Solution:** Run migration scripts, check user role in database

**Problem:** Nurse cannot update records  
**Solution:** Verify role is exactly 'nurse' (lowercase)

**Problem:** Lost super admin access  
**Solution:** 
```sql
UPDATE users SET role = 'super_admin' WHERE username = 'admin';
```

---

## 📚 Full Documentation

For complete details, see:  
`ROLE_BASED_ACCESS_GUIDE.md`

---

## 🎉 You're Done!

Your system now has:
- ✅ Role-based access control
- ✅ Super Admin with full access
- ✅ Nurse role with medical record access
- ✅ Proper permission enforcement
- ✅ Secure medical data management

**Next:** Create user accounts and test permissions!

