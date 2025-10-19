# 🇵🇭 Filipino Employee Data Seeding Guide

## Overview

This guide explains how to populate the NIA HRIS system with realistic Filipino employee sample data.

## 📋 What Gets Seeded

The `seed-pinoy-employees.php` script creates **8 sample Filipino employees** with complete data across all related tables:

### Employee Data
- ✅ **Basic Information**: Filipino names, government-issued email addresses
- ✅ **Contact Details**: Philippine mobile numbers (+63 format), Manila addresses
- ✅ **Government IDs**: SSS, PhilHealth, Pag-IBIG, TIN numbers
- ✅ **Employment Details**: Position, department, hire date, employment type
- ✅ **Medical Records**: Blood type, medical conditions, allergies, medications
- ✅ **Emergency Contacts**: Family emergency contact information

### Related Data
- ✅ **Departments**: 6 departments (Admin, HR, IT, Finance, Operations, Health)
- ✅ **Employee Details**: Salary information, employment type
- ✅ **Leave Balances**: Annual leave allocations per employee
- ✅ **Performance Reviews**: Historical performance ratings (for permanent staff)
- ✅ **Training Records**: Completed training courses

## 🚀 How to Run

### Method 1: Web Browser (Recommended)
1. Open your web browser
2. Navigate to: `http://localhost/nia-hris/seed-pinoy-employees.php`
3. Wait for completion (usually takes 10-30 seconds)
4. View results on the page

### Method 2: Command Line
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/nia-hris
php seed-pinoy-employees.php
```

## 👥 Sample Employees Created

| Employee ID | Name | Position | Department | Type |
|-------------|------|----------|------------|------|
| EMP-2024-001 | Juan Dela Cruz | Senior Administrative Officer | Administration | Staff |
| EMP-2024-002 | Maria Santos | HR Manager | Human Resources | Admin |
| EMP-2024-003 | Jose Reyes | IT Specialist | Information Technology | Staff |
| EMP-2024-004 | Ana Garcia | Senior Accountant | Finance | Staff |
| EMP-2024-005 | Pedro Mendoza | Operations Supervisor | Operations | Staff |
| EMP-2024-006 | Rosa Cruz | Registered Nurse | Health Services | Nurse |
| EMP-2024-007 | Miguel Bautista | Administrative Assistant | Administration | Staff |
| EMP-2024-008 | Cristina Fernandez | HR Specialist | Human Resources | Staff |

## 🔑 Login Credentials

All seeded employees can login using:
- **Username**: Their email address (e.g., `juan.delacruz@nia.gov.ph`)
- **Password**: `employee123`

### Examples:
```
Email: juan.delacruz@nia.gov.ph
Password: employee123

Email: maria.santos@nia.gov.ph
Password: employee123
```

## 📊 Data Characteristics

### Government IDs
- **SSS Numbers**: Format `34-XXXXXXX-X` (realistic Philippine SSS format)
- **PhilHealth**: Format `XX-XXXXXXXXX-X`
- **Pag-IBIG**: Format `XXXX-XXXX-XXXX`
- **TIN**: Format `XXX-XXX-XXX-000`

### Addresses
All addresses are in Metro Manila cities:
- Quezon City
- Makati City
- Pasig City
- Taguig City
- Mandaluyong City
- Paranaque City
- Manila City
- Caloocan City

### Phone Numbers
- Format: `+63 9XX XXX XXXX` (Philippine mobile numbers)
- All numbers start with +63 (Philippines country code)

### Salaries
Range: ₱25,000 - ₱45,000 per month
- Based on Philippine government salary grades
- Varies by position and experience

### Blood Types
Realistic distribution: O+, A+, B+, AB+, O-, A-, B-

### Medical Records
- Random realistic medical conditions (Hypertension, Diabetes, Asthma, or None)
- Common Philippine allergies (Shellfish, Peanuts, Dust, or None)
- Appropriate medications if needed
- Last checkup dates within past year

### Employment Types
- **Permanent**: Long-term regular employees
- **Job Order**: Project-based workers
- **Contract of Service**: Contractual staff

## ⚠️ Important Notes

### Data Clearing
The script will **DELETE** existing employees with:
- Employee IDs starting with `EMP-`
- This ensures clean seeding without duplicates

### Safe to Run Multiple Times
- ✅ Can be run multiple times
- ✅ Will clear previous seed data
- ✅ Will NOT affect real employees (with different ID formats)

### Foreign Key Relationships
The script automatically:
- Creates departments if they don't exist
- Links employees to departments
- Creates leave balances for each employee
- Generates performance reviews
- Records training history

## 🔄 Re-seeding

To reseed with fresh data:
1. Simply run the script again
2. Previous seed data (EMP-* IDs) will be cleared
3. New data will be generated

## 🧪 Testing Scenarios

Use this seeded data to test:
- ✅ Employee management
- ✅ Leave request workflows
- ✅ Medical records management
- ✅ Performance review system
- ✅ Payroll processing
- ✅ Department management
- ✅ Training tracking
- ✅ Reports and analytics

## 📝 Customization

To add more employees, edit `seed-pinoy-employees.php`:

```php
$pinoy_employees = [
    [
        'employee_id' => 'EMP-2024-009',
        'first_name' => 'Carlos',
        'last_name' => 'Ramos',
        // ... add more fields
    ],
    // Add more employees here
];
```

## 🆘 Troubleshooting

### Error: "Table doesn't exist"
**Solution**: Run database migrations first:
```bash
php setup_database.php
```

### Error: "Duplicate entry"
**Solution**: The script should clear duplicates automatically. If error persists, manually delete:
```sql
DELETE FROM employees WHERE employee_id LIKE 'EMP-%';
```

### No employees appear
**Check**:
1. Database connection in `config/database.php`
2. MySQL server is running
3. Database tables exist

## 📞 Support

For issues or questions:
1. Check the script output for specific error messages
2. Verify database tables exist
3. Ensure proper permissions
4. Check PHP error logs

## 🎯 Best Practices

1. **Development**: Use seed data freely
2. **Testing**: Perfect for QA and UAT
3. **Production**: DO NOT use seed script on live data
4. **Backup**: Always backup before running on important databases

---

**Created**: October 2025  
**Version**: 1.0  
**Maintained by**: NIA HRIS Development Team

