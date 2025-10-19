# Philippine Salary Structures Guide

## Overview
This seed data is based on the **Philippine Government Salary Standardization Law (SSL) 2024** and typical government agency salary structures suitable for NIA-HRIS.

## Salary Grades (SG) Explanation

### What are Salary Grades?
Salary Grades (SG) are standardized pay scales used by the Philippine government to ensure fair and consistent compensation across all government agencies.

### Salary Grade Ranges
- **SG-1 to SG-8**: Entry-level to Junior positions
- **SG-9 to SG-15**: Mid-level positions
- **SG-16 to SG-22**: Senior and Supervisory positions
- **SG-23 to SG-30**: Management and Executive positions

## Step Increments

### What are Steps?
Each salary grade has **8 steps** representing incremental salary increases based on length of service.

### Step Progression
- **Initial Appointment**: Step 1
- **After 3 years**: Step 2 (if satisfactory performance)
- **After 6 years**: Step 3
- **After 9 years**: Step 4
- **Maximum**: Step 8 (after 21 years)

### Step Increment Amount
- Typically **3% of base salary**
- Applied every **3 years** of continuous service
- Requires satisfactory performance evaluation

## Position Categories

### 1. Administrative/Office Staff (SG-1 to SG-13)
**Entry Level:**
- Administrative Aide I-III (SG-1 to SG-3): ₱13,000 - ₱17,000
- Administrative Assistant I-III (SG-4 to SG-6): ₱14,762 - ₱21,500

**Mid-Level:**
- Administrative Officer I-V (SG-8 to SG-13): ₱17,679 - ₱35,000

**Common Positions:**
- Clerks, Assistants, Records Officers
- General office and clerical work

### 2. Human Resources Staff (SG-6 to SG-15)
**Positions:**
- HR Assistant I-II (SG-6, SG-8): ₱16,019 - ₱24,000
- HR Officer I-III (SG-11, SG-13, SG-15): ₱20,179 - ₱40,000

**Responsibilities:**
- Recruitment and selection
- Employee relations
- Records management
- Compensation and benefits

### 3. Finance/Accounting Staff (SG-4 to SG-15)
**Entry Level:**
- Accounting Clerk I-II (SG-4, SG-5): ₱14,762 - ₱20,000
- Bookkeeper I-II (SG-6, SG-8): ₱16,019 - ₱24,000

**Professional:**
- Accountant I-III (SG-11, SG-13, SG-15): ₱20,179 - ₱40,000
- Budget Officer I-II (SG-11, SG-13): ₱20,179 - ₱35,000

### 4. Nursing Positions (SG-11 to SG-19)
**Staff Nurses:**
- Nurse I-IV (SG-11, SG-13, SG-15, SG-17): ₱20,179 - ₱45,000
- Public Health Nurse I-II (SG-11, SG-13): ₱20,179 - ₱35,000

**Supervisory:**
- Senior Nurse (SG-18): ₱28,164 - ₱48,000
- Chief Nurse (SG-19): ₱29,359 - ₱52,000

**Requirements:**
- Valid PRC Nursing License
- BSN Degree
- Updated professional registration

### 5. IT/Technical Staff (SG-6 to SG-17)
**Entry:**
- Computer Operator I-II (SG-6, SG-8): ₱16,019 - ₱24,000
- IT Assistant (SG-9): ₱18,426 - ₱26,000

**Professional:**
- IT Specialist I-III (SG-11, SG-13, SG-15): ₱20,179 - ₱40,000
- Systems Analyst I-II (SG-15, SG-17): ₱24,316 - ₱45,000

### 6. Supervisory/Management (SG-15 to SG-25)
**Department Level:**
- Supervising Administrative Officer (SG-15): ₱24,316 - ₱42,000
- Chief Administrative Officer (SG-18): ₱28,164 - ₱52,000
- Division Chief (SG-22): ₱43,415 - ₱75,000

**Management:**
- Assistant Department Manager (SG-24): ₱54,251 - ₱95,000
- Department Manager (SG-25): ₱60,021 - ₱110,000

### 7. Professional Positions

**Legal (SG-8 to SG-22):**
- Legal Assistant (SG-8): ₱17,679 - ₱24,000
- Attorney I-IV (SG-18 to SG-22): ₱28,164 - ₱80,000

**Engineering (SG-8 to SG-22):**
- Engineering Assistant (SG-8): ₱17,679 - ₱24,000
- Engineer I-IV (SG-13 to SG-19): ₱21,901 - ₱52,000
- Senior Engineer (SG-22): ₱43,415 - ₱75,000

### 8. Executive Positions (SG-18 to SG-30)
**Executive Staff:**
- Executive Assistant I-II (SG-18, SG-20): ₱28,164 - ₱55,000

**Top Management:**
- Assistant Director (SG-26): ₱66,374 - ₱125,000
- Director (SG-27): ₱73,402 - ₱150,000
- Assistant Administrator (SG-29): ₱88,611 - ₱180,000
- Administrator (SG-30): ₱98,087 - ₱200,000

## Salary Components

### Base Salary
The minimum monthly salary for a position at Step 1 of the salary grade.

### Minimum Salary
Starting salary (usually same as base salary at Step 1).

### Maximum Salary
Highest possible salary after all 8 step increments.

### Incrementation Amount
Fixed peso amount added for each step increment (typically 3% of base salary).

## Additional Compensation (Not in Seed Data)

### Allowances
Common government allowances may include:
- **PERA** (Personnel Economic Relief Allowance): ₱2,000/month
- **Representation Allowance**: For supervisory positions
- **Transportation Allowance**: For field personnel
- **Clothing Allowance**: Uniform/clothing budget
- **Laundry Allowance**: For uniformed personnel

### Benefits
Government employees typically receive:
- **13th Month Pay**: Mandatory
- **Mid-Year Bonus**: ₱5,000 (if available)
- **Year-End Bonus**: Based on performance
- **Cash Gift**: ₱5,000 (Christmas bonus)
- **Loyalty Pay**: Based on years of service
- **Productivity Incentive Bonus**: Performance-based

## How to Use This Seed Data

### 1. Run the Seed Script
```
http://localhost/nia-hris/seed-salary-structures.php
```

### 2. Verify Data
- Check total positions created (should be 67+)
- Review department breakdown
- Verify salary ranges

### 3. Add Employees
- Use the salary structures when adding new employees
- Position dropdown will auto-populate from this data
- Salary details will auto-fill based on selected structure

### 4. Customize if Needed
You can modify the seed data to:
- Add agency-specific positions
- Adjust salary ranges (if not using strict SSL)
- Add custom departments
- Modify increment schedules

## Employment Type Mapping

### Permanent
- Regular government employees
- Entitled to all benefits and increments
- Uses standard SSL salary grades

### Casual/Project
- Temporary or project-based
- May use reduced salary scales
- Limited benefits

### Casual Subsidy
- Subsidized casual employees
- Special salary arrangements
- Typically lower than permanent

### Job Order
- Per-job basis payment
- No fixed salary grade
- Paid per output/deliverable

### Contract of Service
- Professional services contract
- Negotiated rates
- May exceed standard grades

## Important Notes

### Legal Basis
- **RA 11466**: SSL V (2019-2023)
- **EO 64**: Latest SSL adjustments
- **CSC Guidelines**: Civil Service Commission regulations

### Updates
- Salary grades may change with new SSL legislation
- Step increment percentages remain consistent
- PERA and allowances adjusted annually

### Compliance
- All salaries must comply with national minimum wage
- Benefits must meet labor code requirements
- Proper deductions (SSS, PhilHealth, Pag-IBIG, Tax)

## Support

### References
- Civil Service Commission (CSC): https://csc.gov.ph
- Department of Budget and Management (DBM): https://dbm.gov.ph
- National Wages and Productivity Commission (NWPC): https://nwpc.dole.gov.ph

### Contact
For questions about salary structures:
- HR Department
- Budget Office
- System Administrator

---

**Version:** 1.0  
**Last Updated:** October 2024  
**Based on:** SSL 2024, Philippine Government Standards  
**Currency:** Philippine Peso (PHP)

