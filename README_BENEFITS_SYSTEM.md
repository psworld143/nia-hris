# Comprehensive Benefits Management System

## 🎯 Overview
A flexible, dynamic benefit management system that allows you to manage ALL types of employee benefits, deductions, and contributions - not just government-mandated ones!

## 🚀 Quick Start

### Step 1: Install the System
Visit: `http://localhost/nia-hris/setup-benefits-system.php`

Click "Install Benefits System Now" to create:
- ✅ `benefit_types` table - Define all benefit types
- ✅ `benefit_rate_tables` table - Salary-based rate tables
- ✅ Default benefits (SSS, PhilHealth, Pag-IBIG, Tax)

### Step 2: Manage Benefits
Visit: `http://localhost/nia-hris/manage-benefits.php`

## 📊 Features

### 1. **Add Custom Benefit Types**
Click "Add New Benefit Type" to create:
- Company benefits (health insurance, meal allowance)
- Loan deductions (salary loan, emergency loan)
- Optional deductions (uniform, equipment)
- Any custom benefit you need!

### 2. **Flexible Calculation Types**

#### Fixed Amount
- Deduct a specific amount (e.g., ₱200 meal allowance)
- Best for: Allowances, fixed deductions

#### Percentage
- Deduct a percentage of salary (e.g., 5% company loan)
- Best for: Proportional deductions, percentage-based benefits

#### Salary-based Table
- Use contribution tables based on salary ranges
- Best for: Government benefits (SSS, PhilHealth, Pag-IBIG)
- Click "View Table" or "Manage Rates" to configure

### 3. **Benefit Categories**

| Category | Use For | Color Badge |
|----------|---------|-------------|
| Mandatory | Government-required (SSS, PhilHealth, Tax) | Blue |
| Optional | Company benefits (insurance, allowances) | Green |
| Loan | Employee loans, advances | Yellow |
| Other | Miscellaneous | Gray |

### 4. **Employer Share**
Check "Has Employer Share" for benefits where:
- Company also contributes (like SSS, PhilHealth)
- Cost is split between employee and employer

## 🎨 User Interface

### Dashboard View
```
┌─────────────────────────────────────────────────────┐
│  📊 Statistics                                       │
│  ├─ Mandatory: 4                                    │
│  ├─ Optional: 2                                     │
│  ├─ Loans: 3                                        │
│  └─ Total Active: 9                                 │
├─────────────────────────────────────────────────────┤
│  📋 All Benefit Types Table                         │
│  ├─ Code | Name | Category | Type | Rate | Status  │
│  ├─ SSS | Social Security | Mandatory | Table | ... │
│  ├─ MEAL | Meal Allowance | Optional | Fixed | ₱200│
│  └─ LOAN01 | Salary Loan | Loan | Percentage | 5% │
└─────────────────────────────────────────────────────┘
```

### Add Benefit Modal
```
┌─────────────────────────────────────┐
│  Add New Benefit Type          [X]  │
├─────────────────────────────────────┤
│  Benefit Code: [MEAL___]            │
│  Benefit Name: [Meal Allowance]     │
│  Category: [Optional ▼]             │
│  Calculation Type: [Fixed ▼]        │
│  Default Rate: [200.00]             │
│  ☐ Has Employer Share               │
│  Description: [________]            │
├─────────────────────────────────────┤
│        [Cancel] [Save Benefit]      │
└─────────────────────────────────────┘
```

## 💡 Common Use Cases

### Example 1: Add Meal Allowance
```
Benefit Code: MEAL
Benefit Name: Meal Allowance
Category: Optional
Calculation Type: Fixed
Default Rate: 200.00
Employer Share: No
```
**Result**: Every employee gets ₱200 meal allowance

### Example 2: Add Company Loan
```
Benefit Code: LOAN01
Benefit Name: Salary Loan
Category: Loan
Calculation Type: Percentage
Default Rate: 5
Employer Share: No
```
**Result**: Deduct 5% of salary for loan repayment

### Example 3: Add Health Insurance
```
Benefit Code: HMO
Benefit Name: Health Insurance Premium
Category: Optional
Calculation Type: Fixed
Default Rate: 500.00
Employer Share: Yes (if company pays part)
```
**Result**: ₱500 health insurance deduction

## 🔧 Technical Details

### Database Structure

#### benefit_types Table
```sql
- id (Primary Key)
- benefit_code (VARCHAR, UNIQUE) - e.g., "SSS", "MEAL", "LOAN01"
- benefit_name (VARCHAR) - Display name
- category (ENUM: mandatory, optional, loan, other)
- calculation_type (ENUM: fixed, percentage, table)
- default_rate (DECIMAL) - For fixed/percentage types
- has_employer_share (BOOLEAN)
- description (TEXT)
- is_active (BOOLEAN)
```

#### benefit_rate_tables Table
```sql
- id (Primary Key)
- benefit_type_id (Foreign Key)
- salary_range_min (DECIMAL)
- salary_range_max (DECIMAL)
- employee_rate (DECIMAL)
- employer_rate (DECIMAL)
- is_percentage (BOOLEAN)
- effective_date (DATE)
- is_active (BOOLEAN)
```

### API Endpoints

| File | Method | Purpose |
|------|--------|---------|
| `save-benefit-type.php` | POST | Create/update benefit types |
| `toggle-benefit-status.php` | POST | Activate/deactivate benefits |
| `manage-benefit-rates-table.php` | GET | Manage salary-based rate tables |

## 🎯 Integration with Payroll

The benefits system integrates with:

1. **Employee Benefit Configurations** (`employee_benefit_configurations`)
   - Stores employee-specific overrides
   - Auto/Fixed/Percentage per employee

2. **Payroll Processing** (`payroll-process.php`)
   - Fetches applicable benefits
   - Calculates deductions based on type
   - Applies employee-specific configurations

### Calculation Flow
```
1. Get employee's salary
2. Fetch all active benefits
3. For each benefit:
   - Check if employee has custom config
   - If custom: Use employee config
   - If auto:
     - Fixed: Deduct default_rate
     - Percentage: salary * (default_rate / 100)
     - Table: Lookup in benefit_rate_tables
4. Sum all deductions
5. Calculate net pay
```

## 📋 Actions Available

### For Each Benefit Type

| Icon | Action | Description |
|------|--------|-------------|
| ✏️ | Edit | Modify benefit details |
| 🔄 | Toggle | Activate/deactivate |
| ⚙️ | Manage Rates | Configure rate tables (table-type only) |
| 📊 | View Table | See current rate table (table-type only) |

## 🔒 Permissions

Access restricted to:
- `admin`
- `human_resource`
- `hr_manager`

## 🆕 Adding New Benefits - Step by Step

### 1. Click "Add New Benefit Type"
### 2. Fill in the form:
   - **Benefit Code**: Short, unique code (e.g., "UNIFORM")
   - **Benefit Name**: Full name (e.g., "Uniform Deduction")
   - **Category**: Select appropriate category
   - **Calculation Type**: How to calculate
   - **Default Rate**: Amount or percentage
   - **Employer Share**: Check if applicable
   - **Description**: Brief explanation
### 3. Click "Save Benefit"
### 4. Benefit is now available system-wide!

## 🔄 Differences from Old System

### Old System (manage-benefit-rates.php)
- ❌ Hardcoded to SSS, PhilHealth, Pag-IBIG
- ❌ Can't add custom benefits
- ❌ Limited flexibility

### New System (manage-benefits.php)
- ✅ Add unlimited benefit types
- ✅ Fully customizable
- ✅ Flexible calculation methods
- ✅ Category management
- ✅ Easy to extend

## 🎉 Benefits of This System

1. **Flexibility**: Add any benefit type you need
2. **Scalability**: No code changes to add benefits
3. **Organization**: Categories keep things organized
4. **Integration**: Works seamlessly with payroll
5. **User-Friendly**: Beautiful modals and intuitive UI
6. **Professional**: Color-coded badges and status indicators

## 🚦 Next Steps

After installation:
1. ✅ Review default benefits (SSS, PhilHealth, Pag-IBIG, Tax)
2. ✅ Add your company-specific benefits
3. ✅ Configure rate tables for table-based benefits
4. ✅ Test with sample payroll calculation
5. ✅ Train HR staff on adding new benefits

## 📞 Support

For issues or questions:
1. Check that tables are installed correctly
2. Verify permissions (admin/HR access)
3. Review benefit type configurations
4. Test with inactive benefits first

---

**Created**: October 7, 2025  
**Version**: 2.0  
**Status**: ✅ Production Ready

