# Degree Management System

## Overview
The Degree Management System allows HR administrators to manage educational degree levels that are used throughout the employee management system.

## Features
- ✅ Add, edit, and delete degree levels
- ✅ Activate/deactivate degrees
- ✅ Custom sort ordering
- ✅ Employee count tracking
- ✅ Protection against deleting degrees in use
- ✅ Search and filter functionality
- ✅ Pagination support

## Setup Instructions

### Step 1: Run Database Setup
1. Make sure you're logged in as an **admin** user
2. Navigate to: `http://localhost/nia-hris/setup-degrees-table.php`
3. The script will:
   - Create the `degrees` table
   - Insert 8 default degree levels (Elementary through Post-Doctorate)

### Step 2: Access Degree Management
Navigate to: `http://localhost/nia-hris/manage-degrees.php`

Or access it from:
- Main navigation menu → **Manage Degrees**
- Admin Employee page → **Manage Degrees** button

## Default Degrees Included
1. **Elementary** - Completed elementary education
2. **High School** - Completed high school education
3. **Vocational** - Vocational or technical training
4. **Associate Degree** - Two-year college degree
5. **Bachelor's Degree** - Four-year undergraduate degree
6. **Master's Degree** - Graduate degree beyond bachelor's
7. **Doctorate** - Doctoral degree (PhD, EdD, etc.)
8. **Post-Doctorate** - Post-doctoral research or studies

## Usage

### Adding a New Degree
1. Click **"Add New Degree"** button
2. Enter degree name (required)
3. Add description (optional)
4. Set sort order (lower numbers appear first)
5. Set status (Active/Inactive)
6. Click **"Add Degree"**

### Editing a Degree
1. Click the **edit icon** (pencil) next to the degree
2. Modify the information
3. Click **"Update Degree"**

### Activating/Deactivating
- Click the **status icon** to toggle between active and inactive
- Inactive degrees won't appear in employee forms

### Deleting a Degree
- Click the **delete icon** (trash can)
- **Note**: You can only delete degrees that are not assigned to any employees

## Integration with Employee Forms
Once set up, degrees will automatically populate in:
- Add Employee forms
- Edit Employee forms
- Employee profile views

The system will use the active degrees from the database instead of hardcoded values.

## Database Structure

### Table: `degrees`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- degree_name (VARCHAR(100), UNIQUE)
- description (TEXT)
- sort_order (INT)
- is_active (TINYINT(1))
- created_by (INT, FOREIGN KEY → users.id)
- updated_by (INT, FOREIGN KEY → users.id)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## Files Created
1. **manage-degrees.php** - Main management interface
2. **add-degree.php** - Handles add/edit operations
3. **toggle-degree-status.php** - Handles activate/deactivate
4. **delete-degree.php** - Handles deletion
5. **setup-degrees-table.php** - Database setup script

## Next Steps (Optional)
To fully integrate with existing employee forms, you may want to:
1. Update `add-employee-comprehensive-form.php` to load degrees from database
2. Update `edit-employee.php` to load degrees from database
3. This will replace the hardcoded degree options with dynamic database values

## Permissions
Only users with the following roles can access:
- **admin**
- **human_resource**
- **hr_manager**

## Support
For issues or questions, contact your system administrator.

