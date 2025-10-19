# Employee Type Migration Guide

## Summary
Updated the employee type enum from `('faculty','staff','admin')` to `('Staff','Admin','Nurse')`.

## Changes Made

### 1. **Database Schema** ❗ REQUIRES MIGRATION
- **Current:** `employee_type` enum('faculty','staff','admin') DEFAULT 'staff'
- **New:** `employee_type` enum('Staff','Admin','Nurse') DEFAULT 'Staff'

### 2. **Files Updated** ✅

#### Forms
- ✅ `add-employee-comprehensive-form.php` - Updated dropdown to Staff, Admin, Nurse
- ✅ `edit-employee.php` - Updated dropdown to Staff, Admin, Nurse

#### Validation
- ✅ `add-employee.php` - Updated validation array to ['Staff', 'Admin', 'Nurse']

#### Migration Script
- ✅ `migrate-employee-types.php` - Created migration script to update database

## How to Apply Changes

### Step 1: Backup Your Database
```bash
mysqldump -u root -p nia_hris > backup_before_migration.sql
```

### Step 2: Run the Migration Script
Navigate to: `http://localhost/nia-hris/migrate-employee-types.php`

The migration script will:
1. Check current enum values
2. Update existing employee records:
   - 'faculty' → 'Nurse'
   - 'staff' → 'Staff'  
   - 'admin' → 'Admin'
3. Alter the enum to new values
4. Verify the changes
5. Show employee type distribution

### Step 3: Test the System
1. Go to `http://localhost/nia-hris/add-employee-comprehensive-form.php`
2. Verify the Employee Type dropdown shows: Staff, Admin, Nurse
3. Try adding a new employee
4. Go to `http://localhost/nia-hris/edit-employee.php?id=X`
5. Verify the Employee Type dropdown works correctly

## Migration Mapping

| Old Value  | New Value |
|------------|-----------|
| faculty    | Nurse     |
| staff      | Staff     |
| admin      | Admin     |

## Notes

### Case Sensitivity
The new enum uses proper case (capitalized first letter):
- ✅ 'Staff', 'Admin', 'Nurse'
- ❌ 'staff', 'admin', 'nurse', 'faculty'

### Default Value
- Old default: 'staff'
- New default: 'Staff'

### Affected Tables
- `employees` table - Main employee records

### Related Files Not Updated (By Design)
These files reference faculty/staff/admin but are either:
- Backup files
- Report filters (may need separate review)
- Legacy code

Files to review later if needed:
- `leave-reports.php` - Has faculty filter
- `leave-management-backup.php` - Backup file
- `get-evaluation-scores.php` - Has faculty check
- `create-increment-request.php` - Has faculty option

## Troubleshooting

### If Migration Fails
1. Restore from backup:
   ```bash
   mysql -u root -p nia_hris < backup_before_migration.sql
   ```

2. Check for:
   - Foreign key constraints
   - Triggers or stored procedures using employee_type
   - Views using employee_type

### If Dropdown Doesn't Show New Values
1. Clear browser cache
2. Check if you're viewing the correct file
3. Verify the form file was updated correctly

## Verification Checklist

- [ ] Backup created
- [ ] Migration script executed successfully
- [ ] Database enum updated to ('Staff','Admin','Nurse')
- [ ] Existing employee records updated
- [ ] Add employee form shows correct values
- [ ] Edit employee form shows correct values
- [ ] Can add new employee with new types
- [ ] Can edit existing employee types

## Support

If you encounter any issues:
1. Check the migration script output for errors
2. Verify database connection in `config/database.php`
3. Check MySQL error logs
4. Restore from backup if needed

---
**Created:** <?php echo date('Y-m-d H:i:s'); ?>  
**Status:** Ready for migration

