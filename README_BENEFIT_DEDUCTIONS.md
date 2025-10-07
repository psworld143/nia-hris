# Government Benefits Deduction Configuration

## Overview
The system now supports configurable deduction methods for government benefits in payroll processing. Administrators can choose how each employee's mandatory deductions (SSS, PhilHealth, Pag-IBIG, and Tax) are calculated.

## Deduction Types

### 1. **Auto (Automatic)**
- Uses the standard contribution tables based on employee salary
- Recommended for most employees
- Ensures compliance with government regulations
- Badge: Gray "Auto"

### 2. **Fixed Amount**
- Deducts a specific fixed amount regardless of salary
- Useful for special cases or fixed contribution agreements
- Amount configured per employee
- Badge: Blue "Fixed"

### 3. **Percentage**
- Deducts a specific percentage of the employee's gross pay
- Flexible for varying salaries
- Percentage configured per employee
- Badge: Purple "%"

### 4. **None** *(Future feature)*
- No deduction for this benefit type
- Use with caution - may affect compliance
- Badge: Red "None"

## Configuration Steps

### Step 1: Access Government Benefits
1. Navigate to **Government Benefits** page
2. Find the employee you want to configure
3. Click the **Edit** button (pencil icon)

### Step 2: Set ID Numbers
- Enter government ID numbers if not already set:
  - SSS Number
  - PhilHealth Number
  - Pag-IBIG (HDMF) Number
  - BIR TIN

### Step 3: Configure Deductions
For each benefit type (SSS, PhilHealth, Pag-IBIG, Tax):

1. **Select Deduction Type:**
   - Choose Auto, Fixed, or Percentage radio button

2. **Enter Values (if applicable):**
   - For **Fixed**: Enter the amount in pesos
   - For **Percentage**: Enter the percentage (e.g., 5 for 5%)

3. **Save Configuration:**
   - Click "Update Benefits" button

## Integration with Payroll

### Automatic Application
When processing payroll:
1. System fetches employee's benefit configuration
2. Deductions are calculated based on configured type:
   - **Auto**: Looks up contribution table
   - **Fixed**: Uses configured fixed amount
   - **Percentage**: Calculates percentage of gross pay

### Deduction Auto-Check Logic
In payroll processing, deductions are automatically checked if:
- The employee has the corresponding government ID number
- The deduction type is NOT 'none'

## Database Structure

### Table: `employee_benefit_configurations`
```sql
- id (Primary Key)
- employee_id (Foreign Key → employees.id)
- sss_deduction_type (ENUM: 'auto', 'fixed', 'percentage', 'none')
- sss_fixed_amount (DECIMAL)
- sss_percentage (DECIMAL)
- philhealth_deduction_type
- philhealth_fixed_amount
- philhealth_percentage
- pagibig_deduction_type
- pagibig_fixed_amount
- pagibig_percentage
- tax_deduction_type
- tax_fixed_amount
- tax_percentage
- created_at, updated_at
```

## API Endpoint

### Get Benefit Configuration
**Endpoint:** `/api/get-benefit-config.php`

**Method:** GET

**Parameters:**
- `employee_id` (required): Employee ID

**Response:**
```json
{
  "success": true,
  "config": {
    "sss": {
      "type": "auto",
      "fixed_amount": 0,
      "percentage": 0,
      "has_id": true
    },
    "philhealth": {
      "type": "percentage",
      "fixed_amount": 0,
      "percentage": 2.5,
      "has_id": true
    },
    "pagibig": {
      "type": "fixed",
      "fixed_amount": 200,
      "percentage": 0,
      "has_id": true
    },
    "tax": {
      "type": "auto",
      "fixed_amount": 0,
      "percentage": 0,
      "has_id": true
    }
  }
}
```

## Visual Indicators

### Table Badges
Each government ID number column displays a colored badge indicating the configured deduction type:

| Badge Color | Type       | Meaning                    |
|-------------|------------|----------------------------|
| Gray        | Auto       | Standard table rates       |
| Blue        | Fixed      | Fixed amount deduction     |
| Purple      | %          | Percentage-based deduction |
| Red         | None       | No deduction               |

## Use Cases

### Example 1: Regular Employee
- **Scenario**: Standard employee with normal contributions
- **Configuration**: All set to "Auto"
- **Result**: Standard SSS, PhilHealth, Pag-IBIG rates applied

### Example 2: Fixed Contribution Agreement
- **Scenario**: Employee with special Pag-IBIG arrangement
- **Configuration**:
  - SSS: Auto
  - PhilHealth: Auto
  - Pag-IBIG: Fixed ₱300/month
  - Tax: Auto
- **Result**: Pag-IBIG always deducts ₱300 regardless of salary

### Example 3: Percentage-Based
- **Scenario**: Employee with percentage-based PhilHealth
- **Configuration**:
  - SSS: Auto
  - PhilHealth: Percentage 3%
  - Pag-IBIG: Auto
  - Tax: Auto
- **Result**: PhilHealth deduction is 3% of gross pay

## Important Notes

1. **Compliance**: Ensure custom deduction configurations comply with government regulations
2. **Defaults**: New employees default to "Auto" for all deductions
3. **Updates**: Changes take effect in the next payroll period
4. **Validation**: System validates that fixed amounts and percentages are reasonable
5. **Audit Trail**: All configuration changes are logged with timestamps

## Testing

To test the configuration:
1. Set up test employee with different deduction types
2. Process payroll for test period
3. Verify deduction amounts in payslip
4. Check that badges display correctly in Government Benefits page

## Troubleshooting

### Deduction Not Applied
- **Check**: Employee has valid government ID number
- **Check**: Deduction type is not set to 'none'
- **Check**: For percentage/fixed, ensure values are entered

### Incorrect Amount
- **Check**: Verify deduction type matches intended calculation
- **Check**: For fixed, ensure amount is correct
- **Check**: For percentage, ensure percentage is entered (not decimal)

### Configuration Not Saving
- **Check**: All required fields are filled
- **Check**: User has permission to update benefits
- **Check**: Database connection is active

## Future Enhancements
- Bulk configuration import/export
- Historical configuration tracking
- Configuration effective dates
- Deduction templates
- Automatic government rate updates

