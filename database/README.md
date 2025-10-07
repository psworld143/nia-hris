# NIA-HRIS Database Dump

## 📦 File Information

**Filename:** `nia_hris_complete_dump.sql`  
**Size:** 108 KB  
**Type:** Complete MySQL Database Dump  
**Database:** `nia_hris`

## 📋 Contents

This file contains:
- ✅ **Complete database structure** (all tables, indexes, constraints)
- ✅ **All data** from all tables
- ✅ **Triggers** (if any)
- ✅ **Routines** (stored procedures and functions)
- ✅ **Events** (scheduled events)
- ✅ **DROP DATABASE** statement (will remove existing database)
- ✅ **CREATE DATABASE** statement

## 🚀 How to Import

### Method 1: Command Line (Recommended)

```bash
# Navigate to database folder
cd /Applications/XAMPP/xamppfiles/htdocs/nia-hris/database

# Import the complete dump
mysql -u root < nia_hris_complete_dump.sql
```

### Method 2: phpMyAdmin

1. Open **phpMyAdmin**
2. Click **"Import"** tab
3. Click **"Choose File"** and select `nia_hris_complete_dump.sql`
4. Click **"Go"**
5. Wait for completion

### Method 3: MySQL Command (Alternative)

```bash
mysql -u root -e "source /Applications/XAMPP/xamppfiles/htdocs/nia-hris/database/nia_hris_complete_dump.sql"
```

## ⚠️ Important Warnings

### ⚠️ THIS WILL:
- **DROP** the existing `nia_hris` database if it exists
- **DELETE ALL DATA** in the existing database
- **CREATE** a fresh `nia_hris` database
- **RESTORE** all tables and data from the dump

### 🛡️ Before Importing:
1. **Backup your current database** if you have important data
2. Make sure you want to **replace** the entire database
3. Close all connections to the database

## 📊 What's Included

### Government Benefits Tables
- `government_benefit_rates` - GSIS, SSS, PhilHealth, Pag-IBIG rates
- `tax_brackets` - Philippine income tax brackets (TRAIN Law)
- `employee_benefit_configurations` - Employee-specific deduction settings

### Payroll Tables
- `payroll_deduction_types` - All deduction types (GSIS, SSS, loans, etc.)
- `payroll_periods` - Payroll period records
- `payroll_records` - Employee payroll data
- `payroll_deductions` - Individual deduction records
- `payroll_earnings` - Earnings records
- `payroll_adjustments` - Payroll adjustments
- `payroll_audit_logs` - Audit trail

### Employee Management Tables
- `employees` - Employee master data
- `departments` - Department information
- `degrees` - Educational degrees
- And more...

### Other System Tables
- `users` - System users and authentication
- Performance review tables
- Training and development tables
- Leave management tables
- And all other system tables

## 🔄 Creating a New Dump

To create a fresh dump of your current database:

```bash
mysqldump -u root --databases nia_hris \
  --add-drop-database \
  --add-drop-table \
  --routines \
  --triggers \
  --events \
  --hex-blob \
  --single-transaction \
  > database/nia_hris_complete_dump.sql
```

## 📅 Version Control

When updating the dump file, consider:
1. Renaming old file with date: `nia_hris_complete_dump_2025-10-07.sql`
2. Creating new dump with current date
3. Keeping multiple versions for rollback capability

## 🔍 Verification

After importing, verify the database:

```sql
-- Check database exists
SHOW DATABASES LIKE 'nia_hris';

-- Check tables
USE nia_hris;
SHOW TABLES;

-- Check record counts
SELECT 
  'employees' as table_name, COUNT(*) as records FROM employees
UNION ALL SELECT 'government_benefit_rates', COUNT(*) FROM government_benefit_rates
UNION ALL SELECT 'payroll_deduction_types', COUNT(*) FROM payroll_deduction_types;
```

## 🆘 Troubleshooting

### Error: "Access denied"
```bash
# Add password if required
mysql -u root -p < nia_hris_complete_dump.sql
```

### Error: "Database already exists"
The dump includes `DROP DATABASE IF EXISTS`, so this shouldn't occur.

### Large File Issues
If file is too large for phpMyAdmin:
1. Increase `upload_max_filesize` in php.ini
2. Use command line instead (recommended for files > 2MB)

### Import Takes Too Long
- Normal for large databases
- Use `--single-transaction` flag (already included)
- Import during off-peak hours

## 📞 Support

For issues:
1. Check MySQL error log
2. Verify MySQL version compatibility
3. Ensure sufficient disk space
4. Check user permissions

## 🔐 Security Notes

- **Do not commit this file to public repositories** (contains sensitive data)
- Store securely with restricted access
- Encrypt if transmitting over network
- Regularly update and rotate backups

---

**Generated:** October 7, 2025  
**Database Version:** MySQL/MariaDB  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci

