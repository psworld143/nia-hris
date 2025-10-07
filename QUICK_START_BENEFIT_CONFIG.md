# Quick Start: Configuring Government Benefit Deductions

## 🚀 Getting Started in 3 Steps

### Step 1: Open Government Benefits Page
Navigate to: **Government Benefits** from the main menu

You'll see a table of all employees with their government ID numbers and colored badges showing their deduction configuration.

### Step 2: Edit Employee Configuration
1. Find the employee you want to configure
2. Click the **pencil icon** (Edit) button
3. A modal will open with two sections:
   - **Government ID Numbers** (gray background)
   - **Payroll Deduction Configuration** (blue background)

### Step 3: Set Deduction Types
For each benefit (SSS, PhilHealth, Pag-IBIG, Tax):

#### Option A: Auto (Recommended)
- Select **Auto** radio button
- System uses standard government contribution tables
- **No additional input needed**

#### Option B: Fixed Amount
- Select **Fixed** radio button
- Enter the fixed amount in the **Fixed amount** field
- Example: Enter `200` for ₱200.00 monthly deduction

#### Option C: Percentage
- Select **Percentage** radio button
- Enter the percentage in the **Percentage %** field
- Example: Enter `3` for 3% of gross pay

Click **Update Benefits** to save.

---

## 📊 Understanding the Badges

When you look at the Government Benefits table, you'll see colored badges next to each ID number:

| Badge | Color | Meaning |
|-------|-------|---------|
| **Auto** | Gray | Using standard government rates |
| **Fixed** | Blue | Using a fixed monthly amount |
| **%** | Purple | Using a percentage of gross pay |
| **None** | Red | No deduction applied |

---

## 💡 Common Scenarios

### Scenario 1: New Employee (Default)
**Situation**: Just hired, no special arrangements  
**Configuration**: Leave everything as **Auto**  
**Result**: Standard SSS/PhilHealth/Pag-IBIG rates apply

### Scenario 2: Special Pag-IBIG Rate
**Situation**: Employee has arrangement for fixed ₱200 Pag-IBIG  
**Configuration**:
- SSS: **Auto**
- PhilHealth: **Auto**
- Pag-IBIG: **Fixed** → Enter `200`
- Tax: **Auto**

**Result**: Pag-IBIG always deducts ₱200, others use standard rates

### Scenario 3: Percentage-Based PhilHealth
**Situation**: Employee pays 3% for PhilHealth  
**Configuration**:
- SSS: **Auto**
- PhilHealth: **Percentage** → Enter `3`
- Pag-IBIG: **Auto**
- Tax: **Auto**

**Result**: PhilHealth deducts 3% of gross salary

---

## ⚠️ Important Notes

1. **Always enter government ID numbers first**
   - Required for payroll deductions to apply
   - Without ID numbers, no deductions will be taken

2. **Changes apply to next payroll period**
   - Current period uses existing configuration
   - New settings effective on next payroll run

3. **Compliance is your responsibility**
   - Ensure custom rates comply with regulations
   - Auto mode ensures government compliance

4. **Fixed amounts are monthly**
   - Not affected by hours worked or salary changes
   - Deducted every payroll period

5. **Percentages are of gross pay**
   - Calculated before other deductions
   - Higher gross = higher deduction

---

## 🔍 Quick Reference: Configuration Modal

```
┌─────────────────────────────────────────────┐
│  Edit Government Benefits              [X]  │
├─────────────────────────────────────────────┤
│  Employee Info                              │
│  📋 Name, ID, Position                      │
├─────────────────────────────────────────────┤
│  Government ID Numbers                      │
│  ├─ SSS Number: ______________             │
│  ├─ PhilHealth: ______________             │
│  ├─ Pag-IBIG:   ______________             │
│  └─ BIR TIN:    ______________             │
├─────────────────────────────────────────────┤
│  💰 Payroll Deduction Configuration        │
│                                             │
│  SSS Deduction                              │
│  ○ Auto  ○ Fixed  ○ Percentage             │
│  [Fixed amount] [Percentage %]              │
│                                             │
│  PhilHealth Deduction                       │
│  ○ Auto  ○ Fixed  ○ Percentage             │
│  [Fixed amount] [Percentage %]              │
│                                             │
│  Pag-IBIG Deduction                         │
│  ○ Auto  ○ Fixed  ○ Percentage             │
│  [Fixed amount] [Percentage %]              │
│                                             │
│  Withholding Tax                            │
│  ○ Auto  ○ Fixed  ○ Percentage             │
│  [Fixed amount] [Percentage %]              │
│                                             │
│  Note: Auto uses contribution tables        │
├─────────────────────────────────────────────┤
│              [Cancel] [Update Benefits]     │
└─────────────────────────────────────────────┘
```

---

## ✅ Checklist for Configuration

- [ ] Navigate to Government Benefits page
- [ ] Click Edit on employee
- [ ] Verify/enter government ID numbers
- [ ] For each benefit, choose deduction type:
  - [ ] SSS configured
  - [ ] PhilHealth configured
  - [ ] Pag-IBIG configured
  - [ ] Tax configured
- [ ] If Fixed: enter fixed amount
- [ ] If Percentage: enter percentage value
- [ ] Click "Update Benefits"
- [ ] Verify badges show correct type in table

---

## 🆘 Troubleshooting

**Problem**: Badge still shows "Auto" after changing  
**Solution**: Refresh the page after saving

**Problem**: Deduction not appearing in payroll  
**Solution**: Verify employee has government ID number entered

**Problem**: Wrong amount deducted  
**Solution**: Check configuration type and values match intention

**Problem**: Can't save configuration  
**Solution**: Ensure all required ID numbers are filled

---

## 📞 Need Help?

If you encounter issues:
1. Check that government ID numbers are entered
2. Verify values are reasonable (percentages 0-100, amounts > 0)
3. Refresh the page and try again
4. Check with system administrator

---

**Last Updated**: October 7, 2025  
**Feature Version**: 1.0  
**Documentation**: See README_BENEFIT_DEDUCTIONS.md for details

