# Payroll System - Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Access Payroll (30 seconds)
```
http://localhost/nia-hris/payroll-management.php
```

### Step 2: Install Database (1 minute)
- Click **"Install Payroll System"** button
- Wait for installation to complete
- Click **"Continue to Payroll Management"**

### Step 3: Create First Period (1 minute)
- Click **"New Payroll Period"** button
- System auto-fills with current month dates
- Click **"Create Period"**

### Step 4: Enter Hours (2 minutes)
- Click **"Process"** icon on the period
- Enter hours for employees:
  - **Regular Hours**: e.g., 176 (full month)
  - **Overtime Hours**: e.g., 8 (extra hours)
  - **Night Diff Hours**: e.g., 16 (night shifts)
- Click **"Calculate All"**

### Step 5: Manage Deductions (30 seconds)
- Click on any **blue deduction amount**
- Modal opens with checklist:
  - ✅ SSS (auto-checked if has SSS number)
  - ✅ PhilHealth (auto-checked if has PhilHealth number)
  - ✅ Pag-IBIG (auto-checked if has Pag-IBIG number)
  - ✅ Tax (auto-checked if has TIN)
  - ☐ Loans (check if applicable)
- Enter amounts for each checked item
- Click **"Apply Deductions"**
- Click **"Save All"**

### Step 6: View Payslips (30 seconds)
- Click **payslip icon** next to any employee
- Payslip opens in new window
- Click **"Print"** or Save as PDF

## 💡 Quick Tips

### Auto-Calculations
- Hours are automatically converted to pay
- Overtime calculated at 1.25x rate
- Night differential at 10% premium
- Gross = Basic + OT + Night Diff + Allowances
- Net = Gross - Deductions

### Deduction Checklist Behavior
- ✅ **Auto-checked** if employee has government ID number
- 💰 **Enter amount** for each checked deduction
- 🔄 **Real-time total** updates as you type
- ☐ **Uncheck** to skip a deduction
- 💾 **Applies** immediately when you click "Apply Deductions"

### Color Guide
- 🟢 **Green** = Earnings, Gross Pay
- 🔴 **Red** = Deductions
- 🟣 **Purple** = Net Pay (take-home)
- 🔵 **Blue** = Information, Edit actions
- 🟡 **Yellow** = Processing, Warnings

## 📱 Main Pages

| Page | URL | Purpose |
|------|-----|---------|
| **Dashboard** | `/payroll-management.php` | View all periods, create new |
| **Process** | `/payroll-process.php?period_id=X` | Enter hours & deductions |
| **View** | `/payroll-view.php?period_id=X` | View period summary |
| **Reports** | `/payroll-reports.php` | Analytics & reports |
| **Payslip** | `/payslip-view.php?period_id=X&employee_id=Y` | Individual payslip |

## 🎯 Common Tasks

### Generate Monthly Payroll
1. Create period for the month
2. Enter hours for all employees
3. Calculate all
4. Manage deductions
5. Save all
6. View reports
7. Print payslips

### Handle Employee Loan
1. Open deductions modal for employee
2. Check **SSS Loan**, **Pag-IBIG Loan**, or **Salary Loan**
3. Enter monthly amortization amount
4. Click "Apply Deductions"
5. Loan amount now deducted from payslip

### Monthly Report
1. Go to Payroll Reports
2. Select Year and Month
3. View breakdown by period
4. Print summary

## ⚙️ Default Settings

### Rates
- Overtime Multiplier: **1.25x** (125%)
- Night Differential: **10%** (0.10)
- Working Days/Month: **22 days**
- Working Hours/Day: **8 hours**
- Monthly Hours: **176 hours**

### Mandatory Deductions (if IDs exist)
- SSS Contribution
- PhilHealth Contribution
- Pag-IBIG Contribution
- Withholding Tax

### Optional Deductions (user selects)
- SSS Loan
- Pag-IBIG Loan
- Salary Loan
- Late/Undertime/Absence
- Others (Uniforms, Cash Advance, etc.)

## 🔐 Access Permissions

Only these roles can access:
- **admin**
- **human_resource**
- **hr_manager**

## 📞 Support

For questions or issues:
1. Check `README_PAYROLL_SYSTEM.md` for detailed documentation
2. Review calculation formulas in system
3. Contact HR Department or System Administrator

---

**Status**: Production Ready ✅
**Version**: 1.0.0
**Date**: October 7, 2025

