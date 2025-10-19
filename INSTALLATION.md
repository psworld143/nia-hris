# NIA-HRIS Installation Guide

## Quick Start

### 1. Prerequisites
- XAMPP (Apache + MySQL + PHP) installed and running
- Web browser (Chrome, Firefox, Safari, etc.)

### 2. Installation Steps

#### Step 1: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Ensure both services show "Running" status

#### Step 2: Access the System
1. Open your web browser
2. Navigate to: `http://localhost/nia-hris/`
3. You should see the NIA-HRIS login page

#### Step 3: Database Setup
1. Navigate to: `http://localhost/nia-hris/setup_database.php`
2. Wait for the "Database setup completed!" message
3. Note the default admin credentials:
   - **Username:** `admin`
   - **Password:** `admin123`

#### Step 4: System Test
1. Navigate to: `http://localhost/nia-hris/test_system.php`
2. Verify all tests show ✓ (green checkmarks)
3. If any tests fail, check XAMPP services are running

#### Step 5: First Login
1. Go to: `http://localhost/nia-hris/login.php`
2. Login with:
   - Username: `admin`
   - Password: `admin123`
3. You should be redirected to the dashboard

### 3. Post-Installation

#### Change Default Password
1. After first login, go to Settings
2. Change the default admin password
3. Create additional user accounts as needed

#### Configure Organization Settings
1. Go to Settings in the sidebar
2. Update organization name, logo, and other settings
3. Save changes

### 4. System Access

#### Main URLs
- **Login:** `http://localhost/nia-hris/login.php`
- **Dashboard:** `http://localhost/nia-hris/index.php`
- **Database Setup:** `http://localhost/nia-hris/setup_database.php`
- **System Test:** `http://localhost/nia-hris/test_system.php`

#### Default Credentials
- **Username:** admin
- **Password:** admin123

### 5. Troubleshooting

#### Database Connection Issues
- Ensure MySQL is running in XAMPP
- Check if port 3306 is available
- Verify MySQL socket path in `config/database.php`

#### Permission Issues
- Ensure `uploads/` and `logs/` directories are writable
- Check file permissions (755 for directories, 644 for files)

#### Login Issues
- Clear browser cache and cookies
- Check if session storage is enabled
- Verify database tables exist

### 6. System Features

#### Available Modules
- **Employee Management:** Add, edit, manage employees
- **Faculty Management:** Academic staff management
- **Leave Management:** Leave requests and approvals
- **Salary Management:** Salary structures and increments
- **Performance Reviews:** Employee evaluations
- **Training Programs:** Training and development
- **Reports:** Various HR reports and analytics

#### User Roles
- **Admin:** Full system access
- **HR Manager:** HR management functions
- **HR Staff:** Basic HR operations

### 7. Security Notes

#### Important Security Steps
1. **Change default password immediately**
2. **Create strong passwords for all users**
3. **Regularly backup the database**
4. **Keep the system updated**
5. **Monitor user activities through audit logs**

#### File Security
- Keep `config/database.php` secure
- Don't expose sensitive files to web access
- Regular security updates

### 8. Support

#### System Information
- **Version:** NIA-HRIS v1.0
- **PHP Version:** 8.1.33
- **MySQL Version:** 10.4.28-MariaDB
- **Database:** nia_hris

#### Getting Help
- Check the README.md file for detailed documentation
- Review system logs in the `logs/` directory
- Contact system administrator for technical support

---

**Installation completed successfully!** 🎉

The NIA-HRIS system is now ready for use. You can start adding employees, managing leave requests, and utilizing all the HR management features.

