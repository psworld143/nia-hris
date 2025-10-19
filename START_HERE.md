# 🚀 START HERE - Quick Setup Guide

## ⚡ One-Click Setup (EASIEST)

Just run this ONE script and everything will be configured automatically:

```
http://localhost/nia-hris/complete-setup-wizard.php
```

**This will:**
- ✅ Update user roles (add super_admin and nurse)
- ✅ Add medical record fields to database
- ✅ Create all 5 user accounts
- ✅ Verify everything is working
- ✅ Show you all login credentials

**Time Required:** 1 minute

---

## 🔐 Login Credentials (After Setup)

| Username | Password | Role |
|----------|----------|------|
| `superadmin` | `Super@2024` | Super Admin - Full Access |
| `admin` | `admin123` | Admin - HR & Payroll |
| `hrmanager` | `HRM@2024` | HR Manager |
| `hrstaff` | `HRStaff@2024` | HR Staff |
| `nurse1` | `Nurse@2024` | Nurse - Medical Records |

---

## 📋 Step-by-Step (Manual Method)

If the wizard doesn't work, run these in order:

### 1. Setup User Roles
```
http://localhost/nia-hris/setup-user-roles.php
```

### 2. Add Medical Fields
```
http://localhost/nia-hris/migrate-medical-fields.php
```

### 3. Create User Accounts
```
http://localhost/nia-hris/create-sample-users.php
```

### 4. Verify Setup
```
http://localhost/nia-hris/verify-accounts.php
```

---

## 🎯 Quick Test

After setup, test the system:

### Test 1: Super Admin
1. Go to: `http://localhost/nia-hris/login.php`
2. Login: `superadmin` / `Super@2024`
3. Check sidebar - should see ALL menu items
4. Click "Medical Records" - should work
5. Click "Settings" - should work

### Test 2: Nurse
1. Logout and login again
2. Login: `nurse1` / `Nurse@2024`
3. Check sidebar - should see only Dashboard + Medical Records
4. Click "Medical Records" - should work
5. Can view and update medical information

---

## ❌ Troubleshooting

### Problem: "Invalid username or password"
**Solution:** 
- Run the setup wizard first
- Make sure you ran `complete-setup-wizard.php`

### Problem: "Role not found" error
**Solution:**
- Run `setup-user-roles.php` first
- Then run `create-sample-users.php`

### Problem: Medical Records page shows error
**Solution:**
- Run `migrate-medical-fields.php`
- This adds required database fields

### Problem: Sidebar shows wrong items
**Solution:**
- Clear browser cache (Ctrl+F5)
- Logout and login again
- Check your role in database

---

## 🎉 After Setup

### Change Passwords!
⚠️ **IMPORTANT:** Change all default passwords immediately!

### What Each Role Can Do:

**Super Admin:**
- Everything in the system
- User management
- System settings
- Medical records

**Nurse:**
- View employees
- **Update medical records** (exclusive)
- Medical reports

**Admin/HR Manager:**
- Employee management
- Payroll processing
- Leave management
- Reports

**HR Staff:**
- Basic employee operations
- Leave management
- Limited access

---

## 📚 Documentation

Full documentation available:
- `ROLE_BASED_ACCESS_GUIDE.md` - Complete guide
- `USER_ACCOUNTS_REFERENCE.md` - All credentials
- `RBAC_QUICK_SETUP.md` - Quick setup
- `SIDEBAR_FIX_SUMMARY.md` - Sidebar changes

---

## ✅ Setup Checklist

- [ ] Run `complete-setup-wizard.php`
- [ ] Verify all 4 steps completed successfully
- [ ] Login with `superadmin` / `Super@2024`
- [ ] Check sidebar shows all menu items
- [ ] Test Medical Records page
- [ ] Login with `nurse1` / `Nurse@2024`
- [ ] Verify nurse can update medical records
- [ ] Change all default passwords

---

## 🆘 Need Help?

If something doesn't work:
1. Check browser console for errors
2. Run `verify-accounts.php` to check account status
3. Check PHP error logs
4. Re-run the wizard

---

**Ready? Run the wizard now:** `http://localhost/nia-hris/complete-setup-wizard.php`

🎯 **One click and you're done!**

