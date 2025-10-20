# 🏥 Medical History System Guide

## Overview

The NIA HRIS now includes a comprehensive **Medical History Timeline** feature that tracks all medical events for each employee over time.

## ✅ What Was Created

### 1. **Database Table: `employee_medical_history`**

A new table to store historical medical records with the following fields:

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Primary key |
| `employee_id` | INT | Reference to employee |
| `record_date` | DATE | Date of medical event |
| `record_type` | ENUM | Type of medical record |
| `chief_complaint` | VARCHAR | Patient's main concern |
| `diagnosis` | TEXT | Medical diagnosis |
| `treatment` | TEXT | Treatment provided |
| `medication_prescribed` | TEXT | Medications given |
| `lab_results` | TEXT | Laboratory test results |
| `vital_signs` | JSON | Blood pressure, heart rate, temp, etc. |
| `doctor_name` | VARCHAR | Attending physician |
| `clinic_hospital` | VARCHAR | Medical facility |
| `follow_up_date` | DATE | Next appointment date |
| `notes` | TEXT | Additional notes |
| `recorded_by` | INT | User who recorded the entry |

### 2. **Record Types**

The system supports 8 types of medical records:

| Type | Icon | Color | Description |
|------|------|-------|-------------|
| **Checkup** | 🩺 | Blue | Annual physical exams, routine checkups |
| **Diagnosis** | 🔴 | Red | Medical diagnoses and conditions |
| **Treatment** | 💚 | Green | Treatments and therapies |
| **Vaccination** | 💉 | Purple | Immunizations and vaccines |
| **Lab Test** | 🧪 | Yellow | Laboratory tests and results |
| **Consultation** | 👨‍⚕️ | Teal | Medical consultations |
| **Emergency** | 🚨 | Orange | Emergency visits |
| **Follow-up** | 📋 | Indigo | Follow-up appointments |

### 3. **UI Components**

Added to `view-employee.php`:

#### **Medical History Card** (Current Status)
- Blood Type
- Last Medical Checkup
- Medical Conditions
- Allergies
- Current Medications
- Emergency Contacts
- Medical Notes

#### **Medical History Timeline** (Historical Records)
- Chronological list of all medical events
- Color-coded by record type
- Shows vital signs, diagnoses, treatments
- Doctor and clinic information
- Follow-up dates
- Scrollable timeline (max 20 recent records)

## 🚀 Setup Instructions

### Step 1: Run Setup Script

**Web Browser:**
```
http://localhost/nia-hris/setup-medical-history.php
```

**Command Line:**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/nia-hris
php setup-medical-history.php
```

### Step 2: Verify

Check that medical history records were created:
```sql
SELECT COUNT(*) FROM employee_medical_history;
```

Expected: 10-15 records per employee

## 📊 Sample Data Created

For each employee, 10-15 medical history records are generated including:

### Checkups (Annual Exams)
- Annual physical examinations
- Pre-employment medical exams
- Routine wellness checkups
- Normal vital signs
- Generally healthy status

### Vaccinations
- COVID-19 vaccination
- Flu vaccine
- Hepatitis B booster
- Tetanus booster
- Pneumonia vaccine

### Diagnoses
- Tension headaches
- Viral infections
- Upper respiratory infections
- Muscle strains
- Joint pain (arthralgia)

### Lab Tests
- Complete Blood Count (CBC)
- Urinalysis
- Lipid profile
- Blood sugar tests
- Chest X-rays

### Consultations
- Health consultations
- Medical advice
- Follow-up appointments
- Second opinions
- Health guidance

## 🎨 UI Features

### Timeline Display
- **Color-coded cards** - Each record type has a unique color
- **Chronological order** - Most recent first
- **Vital signs badges** - BP, HR, Temperature displayed as pills
- **Scrollable** - Max height with smooth scrolling
- **Hover effects** - Cards have shadow on hover
- **Responsive** - Works on all screen sizes

### Information Shown
- ✅ Date of visit
- ✅ Type of medical event
- ✅ Chief complaint
- ✅ Diagnosis
- ✅ Treatment given
- ✅ Medications prescribed
- ✅ Vital signs (BP, HR, Temp, Weight, Height)
- ✅ Doctor name
- ✅ Clinic/Hospital
- ✅ Follow-up dates

## 🔐 Permissions

### Viewing Medical History
- ✅ Super Admin
- ✅ Admin
- ✅ HR Manager
- ✅ Human Resource
- ✅ Nurse

### Updating Medical Records
- ✅ Super Admin (via Medical Records page)
- ✅ Nurse (via Medical Records page)

## 📍 Where to Find

### Medical History Timeline
Navigate to any employee's profile:
```
View Employee → [Select Employee] → Medical History Timeline section
```

The timeline appears after the "Medical History" card showing current status.

## ➕ How to Add Medical History Records

### Via Employee Profile Page

**For Super Admin and Nurses:**

1. Navigate to any employee profile:
   ```
   View Employee → [Select Employee]
   ```

2. Scroll to **"Medical History Timeline"** section

3. Click the **"Add Record"** button (red button in the header)

4. Fill in the medical history form:
   - **Record Date** * (required)
   - **Record Type** * (required): Checkup, Diagnosis, Treatment, Vaccination, Lab Test, Consultation, Emergency, or Follow-up
   - **Chief Complaint**: Main reason for visit
   - **Diagnosis**: Medical diagnosis
   - **Treatment**: Treatment provided
   - **Medication**: Prescribed medications and dosage
   - **Vital Signs**: Blood pressure, heart rate, temperature, respiratory rate, weight, height
   - **Doctor Name**: Attending physician
   - **Clinic/Hospital**: Medical facility
   - **Lab Results**: Laboratory test results
   - **Follow-up Date**: Next appointment (if applicable)
   - **Notes**: Additional observations

5. Click **"Save Record"**

6. The page will reload and show the new record in the timeline

### Features:

✅ **Modal Form** - Clean, user-friendly interface
✅ **Comprehensive Fields** - All medical information in one form
✅ **Vital Signs** - Capture BP, HR, Temperature, RR, Weight, Height
✅ **Auto-date** - Sets today's date by default
✅ **Validation** - Required fields marked with *
✅ **AJAX Submission** - No page refresh during save
✅ **Instant Update** - Page reloads to show new record immediately

### Permissions:

**Can Add Medical History:**
- ✅ Super Admin
- ✅ Nurse

**Cannot Add (View Only):**
- ❌ Admin
- ❌ HR Manager
- ❌ Human Resource

### Via Direct API (Advanced):

For programmatic insertion:

```php
POST to: add-medical-history.php

Parameters:
- employee_id (required)
- record_date (required)
- record_type (required)
- chief_complaint
- diagnosis
- treatment
- medication_prescribed
- blood_pressure
- heart_rate
- temperature
- respiratory_rate
- weight
- height
- doctor_name
- clinic_hospital
- lab_results
- follow_up_date
- notes
```

## 💡 Use Cases

### For Nurses
- Track employee health over time
- Monitor chronic conditions
- Ensure vaccinations are up-to-date
- Review past treatments before new consultations

### For HR
- Pre-employment medical clearance
- Fitness-to-work evaluations
- Health and wellness program tracking
- Occupational health monitoring

### For Management
- Overall employee health statistics
- Identify health trends
- Plan wellness programs
- Track medical compliance

## 📈 Statistics Available

From the seeded data:
- **Total Records**: ~25 records (for 2 employees)
- **Average per Employee**: 10-15 records
- **Date Range**: Last 3 years
- **Record Types**: 5 main types (checkup, diagnosis, vaccination, lab_test, consultation)

## 🎯 Features

### Vital Signs Tracking
All records include realistic vital signs in JSON format:
- **Blood Pressure**: 110-130/70-85 mmHg
- **Heart Rate**: 60-90 bpm
- **Temperature**: 36.0-37.5°C
- **Respiratory Rate**: 14-20 breaths/min
- **Weight**: 50-85 kg
- **Height**: 150-180 cm

### Filipino Medical Context
- **Doctors**: Filipino names (Dr. Maria Santos, Dr. Jose Garcia, etc.)
- **Hospitals**: Major Philippine medical centers
  - Makati Medical Center
  - St. Luke's Medical Center
  - The Medical City
  - Manila Doctors Hospital
  - Asian Hospital
- **Medications**: Common Philippine medications and dosages

## 🔧 Maintenance

### Re-seeding Data
To clear and reseed medical history:
```bash
# Clear existing records
mysql -u root nia_hris -e "TRUNCATE TABLE employee_medical_history"

# Run setup again
php setup-medical-history.php
```

### Viewing All Records
```sql
SELECT * FROM employee_medical_history 
WHERE employee_id = [EMPLOYEE_ID] 
ORDER BY record_date DESC;
```

## 🆘 Troubleshooting

### Timeline Not Showing
**Check**: Does the employee have medical history records?
```sql
SELECT COUNT(*) FROM employee_medical_history WHERE employee_id = [ID];
```

### Empty Timeline
**Solution**: Run the setup script to seed data
```
http://localhost/nia-hris/setup-medical-history.php
```

### Table Doesn't Exist
**Solution**: Run the setup script to create the table
```bash
php setup-medical-history.php
```

## 📱 Mobile Responsive

The timeline is fully responsive:
- Desktop: Full timeline with all details
- Tablet: Adjusted spacing and layout
- Mobile: Stacked cards with essential info

## 🎨 Color Scheme

Each record type has a consistent color across the system:
- **Checkup**: Blue (#3B82F6)
- **Diagnosis**: Red (#EF4444)
- **Treatment**: Green (#10B981)
- **Vaccination**: Purple (#8B5CF6)
- **Lab Test**: Yellow (#F59E0B)
- **Consultation**: Teal (#14B8A6)
- **Emergency**: Orange (#F97316)
- **Follow-up**: Indigo (#6366F1)

---

**Created**: October 2025  
**Status**: ACTIVE  
**Maintained by**: NIA HRIS Development Team

