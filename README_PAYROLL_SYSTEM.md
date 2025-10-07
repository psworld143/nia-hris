# Payroll Management System

## 🎯 Overview

A comprehensive payroll management system for NIA-HRIS that handles employee hours tracking, automatic salary calculations, government benefit deductions, and payslip generation.

## ✨ Key Features

### 1. **Payroll Period Management**
- Create and manage monthly/semi-monthly/bi-weekly/weekly payroll periods
- Track period status (Draft → Open → Processing → Calculated → Approved → Paid → Closed)
- Set payment dates and cutoff periods
- View period summaries and statistics

### 2. **Hours Entry System**
- Batch input for all employees in a single interface
- Track regular hours, overtime, and night differential
- Real-time calculation as you enter hours
- Auto-save functionality

### 3. **Automatic Salary Calculation**
- **Basic Pay**: Calculated based on monthly rate and hours worked
- **Overtime Pay**: Regular rate × overtime multiplier (default 1.25x)
- **Night Differential**: Base rate × night diff percentage (default 10%)
- **Allowances**: Monthly allowances automatically included
- **Gross Pay**: Auto-calculated total earnings

### 4. **Benefits Deduction Checklist**
Government contributions (checked by default if employee has ID number):
- ✅ **SSS Contribution** - Social Security System
- ✅ **PhilHealth Contribution** - Philippine Health Insurance
- ✅ **Pag-IBIG Contribution** - Home Development Mutual Fund
- ✅ **Withholding Tax** - Income tax

Optional loan deductions:
- 📋 **SSS Loan** - SSS salary loan
- 📋 **Pag-IBIG Loan** - Housing loan
- 📋 **Salary Loan** - Company loan

### 5. **Payslip Generation**
- Professional payslip design
- Print-ready format
- Shows all earnings and deductions
- Includes government ID numbers
- Bank account information

### 6. **Reports & Analytics**
- Month-by-month summary reports
- Year-to-date statistics
- Breakdown by deduction types
- Employee-wise reports
- Export capabilities

## 📋 Database Structure

### Core Tables (7 tables + 2 views):

1. **payroll_periods** - Payroll period definitions
2. **payroll_records** - Main payroll data per employee
3. **payroll_deduction_types** - Types of deductions (12 pre-defined)
4. **payroll_earning_types** - Types of earnings (10 pre-defined)
5. **payroll_custom_deductions** - Additional deductions
6. **payroll_custom_earnings** - Additional earnings
7. **payroll_adjustments** - Manual adjustments
8. **payroll_audit_log** - Complete audit trail
9. **v_payroll_summary** (VIEW) - Summary data
10. **v_payroll_statistics** (VIEW) - Statistical data

## 🚀 Installation

### Step 1: Access the Payroll System
Navigate to: `http://localhost/nia-hris/payroll-management.php`

### Step 2: Install Database
If tables don't exist, you'll see a setup page. Click **"Install Payroll System"** button.

The system will:
- Create all 7 payroll tables
- Insert 12 default deduction types
- Insert 10 default earning types
- Create 2 database views for reporting
- Set up audit logging

### Step 3: Start Using
After installation, you can immediately start creating payroll periods and processing payroll.

## 📖 How to Use

### Creating a Payroll Period

1. **Go to** `http://localhost/nia-hris/payroll-management.php`
2. Click **"New Payroll Period"** button
3. Fill in the form:
   - **Period Name**: e.g., "January 2025 - 1st Half"
   - **Period Type**: Monthly/Semi-monthly/Bi-weekly/Weekly
   - **Start Date**: First day of period
   - **End Date**: Last day of period
   - **Payment Date**: When employees get paid
   - **Notes**: Optional notes
4. Click **"Create Period"**

### Processing Payroll

1. **Click "Process" icon** on a payroll period
2. **Enter Hours** for each employee:
   - **Regular Hours**: Normal working hours
   - **Overtime Hours**: Extra hours worked
   - **Night Differential Hours**: Hours worked during night shift
3. **Click "Calculate All"** to auto-calculate salaries
4. **Manage Deductions** (click on deduction amount):
   - Check/uncheck deduction items
   - Enter custom amounts
   - System auto-calculates if government IDs are present
5. **Click "Save All"** to save the payroll data

### Managing Deductions (Checklist System)

When you click on a deduction amount, a modal appears with:

**Government Contributions (Auto-checked if ID exists):**
- ☑️ SSS Contribution - Input amount
- ☑️ PhilHealth Contribution - Input amount
- ☑️ Pag-IBIG Contribution - Input amount
- ☑️ Withholding Tax - Input amount

**Loan Deductions (Check if applicable):**
- ☐ SSS Loan - Input monthly amortization
- ☐ Pag-IBIG Loan - Input monthly amortization
- ☐ Salary Loan - Input monthly amortization

**Total Deductions** shown at the bottom and auto-updates.

### Viewing Payslips

1. Click the **payslip icon** next to any employee
2. Payslip opens in new window
3. **Print** or **Save as PDF** from browser
4. Professional format with company header

### Generating Reports

1. Go to **Payroll Reports** (Reports button)
2. Select **Year** and **Month**
3. View comprehensive reports:
   - Employee count per period
   - Gross pay totals
   - Deductions breakdown (SSS, PhilHealth, Pag-IBIG, Tax)
   - Net pay summaries
   - Year-to-date statistics

## 💡 Calculation Logic

### Hourly Rate Calculation:
```
Monthly Rate = Basic Salary
Daily Rate = Monthly Rate / 22 (working days)
Hourly Rate = Daily Rate / 8 (working hours)
```

### Earnings Calculation:
```
Basic Pay = (Regular Hours / 176) × Monthly Rate
Overtime Pay = Overtime Hours × Hourly Rate × Overtime Multiplier (1.25x)
Night Differential = Night Diff Hours × Hourly Rate × Night Diff Rate (10%)
Gross Pay = Basic Pay + Overtime Pay + Night Diff + Allowances
```

### Net Pay Calculation:
```
Net Pay = Gross Pay - Total Deductions
Total Deductions = SSS + PhilHealth + Pag-IBIG + Tax + Loans
```

## 🔒 Security Features

- ✅ **Role-Based Access**: Only admin, HR, and HR managers can access
- ✅ **Audit Logging**: All changes tracked with user, IP, and timestamp
- ✅ **Input Validation**: All inputs sanitized and validated
- ✅ **SQL Injection Protection**: Prepared statements throughout
- ✅ **XSS Protection**: HTML escaping on all outputs

## 📊 Workflow

```
1. Create Period (Draft)
   ↓
2. Open Period for Entry
   ↓
3. Enter Hours for All Employees
   ↓
4. Calculate Salaries (Processing)
   ↓
5. Review & Approve (Calculated → Approved)
   ↓
6. Generate Payslips & Pay (Paid)
   ↓
7. Close Period (Closed)
```

## 🎨 User Interface Features

### Beautiful Modals
- ✨ **Create Period Modal**: Animated slide-in with form validation
- ✨ **Deductions Modal**: Checklist interface with real-time totals
- ✨ **Confirmation Modals**: Beautiful success/error notifications

### Color-Coded Interface
- 🔵 **Blue**: Information and stats
- 🟢 **Green**: Earnings and positive actions
- 🔴 **Red**: Deductions and warnings
- 🟣 **Purple**: Net pay and final amounts
- 🟡 **Yellow**: Processing and pending states

### Responsive Design
- 📱 Mobile-friendly tables
- 💻 Desktop-optimized layouts
- 🖨️ Print-ready payslips

## 📁 File Structure

```
nia-hris/
├── payroll-management.php          # Main dashboard
├── payroll-process.php             # Hours entry & calculation
├── payroll-view.php                # View period details
├── payroll-reports.php             # Reports & analytics
├── payslip-view.php                # Individual payslip view/print
├── save-payroll-period.php         # Backend: Create/edit periods
├── delete-payroll-period.php       # Backend: Delete periods
├── save-payroll-data.php           # Backend: Save payroll calculations
├── setup-payroll-system.php        # Database installation script
└── database/
    └── payroll_system.sql          # Complete database schema
```

## 🌐 Access URLs

- **Main Dashboard**: `http://localhost/nia-hris/payroll-management.php`
- **Process Payroll**: `http://localhost/nia-hris/payroll-process.php?period_id=[ID]`
- **View Period**: `http://localhost/nia-hris/payroll-view.php?period_id=[ID]`
- **Reports**: `http://localhost/nia-hris/payroll-reports.php`
- **Payslip**: `http://localhost/nia-hris/payslip-view.php?period_id=[ID]&employee_id=[ID]`

## 🔧 Customization

### Adding Custom Deductions
```sql
INSERT INTO payroll_deduction_types (code, name, description, category) 
VALUES ('CUSTOM', 'Custom Deduction', 'Description', 'other');
```

### Adding Custom Earnings
```sql
INSERT INTO payroll_earning_types (code, name, description, category) 
VALUES ('CUSTOM', 'Custom Earning', 'Description', 'other');
```

### Modifying Rates
- Overtime rate: Default 1.25x (adjustable per employee)
- Night differential: Default 10% (adjustable per employee)
- Tax rates: Can be customized in calculation logic

## 📝 Default Deduction Types

1. SSS Contribution (Mandatory)
2. PhilHealth Contribution (Mandatory)
3. Pag-IBIG Contribution (Mandatory)
4. Withholding Tax (Mandatory)
5. SSS Loan (Optional)
6. Pag-IBIG Loan (Optional)
7. Salary Loan (Optional)
8. Late Deduction (Optional)
9. Undertime Deduction (Optional)
10. Absence Deduction (Optional)
11. Uniforms (Optional)
12. Cash Advance (Optional)

## 📝 Default Earning Types

1. Basic Pay (Regular)
2. Overtime Pay (Overtime)
3. Night Differential (Overtime)
4. Holiday Pay (Overtime)
5. Rest Day Pay (Overtime)
6. Allowances (Allowance)
7. 13th Month Pay (Bonus - Tax Exempt up to ₱90,000)
8. Performance Bonus (Bonus - Taxable)
9. Rice Subsidy (Allowance - Tax Exempt)
10. Transportation Allowance (Allowance - Tax Exempt)

## ⚙️ System Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.2+
- MySQLi extension
- JSON support
- Minimum 2GB RAM recommended for calculations

## 🔍 Troubleshooting

### "Payroll System Not Installed"
- Click the "Install Payroll System" button on the setup page
- Verify database permissions

### "Calculations Not Working"
- Check that employee has basic_salary in employee_details
- Verify hours are entered correctly
- Check browser console for JavaScript errors

### "Deductions Not Saving"
- Ensure government ID numbers are filled in employee_details
- Check that amounts are valid numbers
- Verify employee has active status

## 🎓 Best Practices

1. **Always Calculate First**: Click "Calculate All" before saving
2. **Review Deductions**: Double-check mandatory deductions for accuracy
3. **Backup Before Closing**: Periods cannot be edited once closed
4. **Regular Audits**: Review audit log periodically
5. **Test First**: Use draft periods for testing calculations

## 📈 Future Enhancements

Possible additions:
- 💳 Bank file generation for direct deposit
- 📧 Email payslips to employees
- 📊 Advanced analytics dashboards
- 🔄 Recurring deductions (loans with auto-payment)
- 📅 Automatic period creation
- 💰 Tax table integration
- 📱 Mobile app for viewing payslips

## 🏆 Success!

The payroll system is now fully integrated into NIA-HRIS with:
- ✅ Complete database structure
- ✅ Hours tracking interface
- ✅ Automatic calculations
- ✅ Deduction checklist system
- ✅ Beautiful modals and UI
- ✅ Payslip generation
- ✅ Comprehensive reports
- ✅ Full CRUD operations
- ✅ Audit logging

**Status**: Ready for production use
**Last Updated**: October 7, 2025
**Version**: 1.0.0

---

For support or questions, contact the HR Department or system administrator.

