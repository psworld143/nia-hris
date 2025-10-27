# NIA HRIS Database Export System

## Overview
The NIA HRIS Database Export System provides comprehensive database export functionality with proper constraint handling to avoid import errors when transferring the database to other PCs or servers.

## Features

### ✅ **Constraint-Safe Export**
- Disables foreign key checks during export
- Re-enables constraints after import
- Prevents constraint violation errors

### ✅ **Dependency-Ordered Tables**
- Exports parent tables first
- Ensures proper table creation order
- Handles foreign key relationships correctly

### ✅ **Cross-Platform Compatibility**
- Proper SQL escaping for all data
- Compatible with different MySQL versions
- Works on Windows, Linux, and macOS

### ✅ **Batch Data Export**
- Exports large datasets in manageable chunks
- Prevents memory issues with large tables
- Optimized for performance

### ✅ **Comprehensive Coverage**
- All 51+ tables included
- Complete table structures
- All data preserved

## Files Created

### 1. `export-database.php` (Web Interface)
- **Purpose**: Web-based database export
- **Access**: Super Admin only
- **Usage**: Download directly through browser
- **Features**: 
  - One-click export
  - Automatic file download
  - Timestamped filename

### 2. `export-database-cli.php` (Command Line)
- **Purpose**: Server-side command line export
- **Usage**: `php export-database-cli.php [filename]`
- **Features**:
  - Progress reporting
  - Custom filename support
  - Detailed statistics
  - File size reporting

### 3. `database-export.php` (Management Interface)
- **Purpose**: Web interface for database export management
- **Access**: Super Admin only
- **Features**:
  - Export statistics
  - Database information
  - Import instructions
  - Export options

## Usage Instructions

### Web Export (Recommended)
1. **Access**: Navigate to `database-export.php` as Super Admin
2. **Export**: Click "Export Now" button
3. **Download**: File downloads automatically with timestamp
4. **Filename**: `nia_hris_database_YYYY-MM-DD_HH-MM-SS.sql`

### Command Line Export
```bash
# Basic export with auto-generated filename
php export-database-cli.php

# Export with custom filename
php export-database-cli.php my_backup.sql

# Export to specific directory
php export-database-cli.php /path/to/backup.sql
```

## Import Instructions

### Method 1: Command Line (Recommended)
```bash
# Create new database
mysql -u root -p -e "CREATE DATABASE nia_hris;"

# Import the SQL file
mysql -u root -p nia_hris < exported_file.sql
```

### Method 2: phpMyAdmin
1. Open phpMyAdmin
2. Create new database named "nia_hris"
3. Select the database
4. Go to "Import" tab
5. Choose the exported SQL file
6. Click "Go" to import

### Method 3: MySQL Workbench
1. Open MySQL Workbench
2. Connect to your MySQL server
3. Create new database "nia_hris"
4. Use "Data Import/Restore" feature
5. Select the exported SQL file
6. Execute the import

## Database Statistics

The export includes comprehensive statistics:
- **51+ Tables**: Complete database structure
- **10 Users**: All system users
- **6 Employees**: Employee records
- **8 Departments**: Department information
- **6 Leave Types**: Leave configuration
- **65 Salary Structures**: Salary configurations
- **21 Payroll Periods**: Payroll periods
- **18 Payroll Records**: Payroll data

## Constraint Handling

### Export Process
```sql
-- Disable constraints for safe import
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Export all tables and data
-- ... (table structures and data) ...

-- Re-enable constraints
SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;
COMMIT;
SET AUTOCOMMIT = 1;
```

### Table Order (Dependency-Based)
1. **Core Tables**: `users`, `departments`, `degrees`, `leave_types`
2. **Configuration Tables**: `salary_structures`, `benefit_types`, `deduction_types`
3. **Employee Tables**: `employees`, `employee_details`, `employee_leave_allowances`
4. **Payroll Tables**: `payroll_periods`, `payroll_records`, `payroll_deductions`
5. **Other Tables**: All remaining tables in dependency order

## Error Prevention

### Common Import Issues Fixed
- ❌ **Foreign Key Violations**: Disabled during import
- ❌ **Unique Constraint Errors**: Handled properly
- ❌ **Table Dependency Issues**: Resolved with proper ordering
- ❌ **Data Type Mismatches**: Proper escaping applied
- ❌ **Character Encoding Issues**: UTF-8 compatible

### Safety Features
- **Transaction Management**: All operations wrapped in transactions
- **Error Handling**: Comprehensive error checking
- **Data Validation**: Proper data type handling
- **Memory Management**: Batch processing for large datasets

## File Structure

```
nia-hris/
├── export-database.php          # Web export script
├── export-database-cli.php      # CLI export script
├── database-export.php          # Export management interface
└── config/
    └── database.php            # Database configuration
```

## Security

### Access Control
- **Super Admin Only**: All export functions require Super Admin privileges
- **Session Validation**: Proper session management
- **Input Sanitization**: All inputs properly sanitized
- **SQL Injection Prevention**: Prepared statements used throughout

### File Security
- **Temporary Files**: Test files automatically cleaned up
- **Access Logging**: Export activities can be logged
- **Permission Checks**: Proper file permission handling

## Performance

### Optimization Features
- **Batch Processing**: Large tables exported in chunks
- **Memory Management**: Efficient memory usage
- **Progress Reporting**: Real-time export progress
- **Error Recovery**: Graceful error handling

### Export Times
- **Small Database** (< 1MB): ~2-5 seconds
- **Medium Database** (1-10MB): ~10-30 seconds
- **Large Database** (> 10MB): ~1-5 minutes

## Troubleshooting

### Common Issues

#### Export Fails
- **Check Permissions**: Ensure Super Admin access
- **Database Connection**: Verify database connectivity
- **Disk Space**: Ensure sufficient disk space
- **Memory Limit**: Check PHP memory limits

#### Import Fails
- **Database Exists**: Ensure target database exists
- **User Permissions**: Check MySQL user permissions
- **File Size**: Verify file wasn't corrupted during transfer
- **MySQL Version**: Ensure compatible MySQL version

#### Constraint Errors
- **Foreign Keys**: Should be handled automatically
- **Unique Constraints**: Properly managed in export
- **Data Types**: All data types preserved correctly

### Support
For technical support or issues:
1. Check the error logs
2. Verify database connectivity
3. Ensure proper user permissions
4. Contact system administrator

## Version History

### v1.0 (Current)
- Initial release
- Complete database export functionality
- Constraint-safe import/export
- Web and CLI interfaces
- Comprehensive error handling

## Future Enhancements

### Planned Features
- **Incremental Backups**: Export only changed data
- **Compression**: Gzip compression for large exports
- **Scheduled Exports**: Automated backup scheduling
- **Cloud Integration**: Direct cloud storage upload
- **Encryption**: Optional encryption for sensitive data

---

**Note**: This export system is designed specifically for the NIA HRIS database structure and may require modifications for other systems.
