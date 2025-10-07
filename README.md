# NIA-HRIS (NIA Human Resource Information System)

A standalone Human Resource Information System extracted from SEAIT's HR module, specifically designed for NIA (National Irrigation Administration).

## Features

### 🎯 Core Functionality
- **Employee Management**: Complete employee lifecycle management
- **Faculty Management**: Academic staff management with evaluation systems
- **Leave Management**: Comprehensive leave request and approval system
- **Salary Management**: Salary structures and increment management
- **Performance Reviews**: Employee performance evaluation system
- **Training Programs**: Training and development management
- **Regularization**: Employee regularization criteria and management
- **Government Benefits**: Government-mandated benefits management

### 📊 Reporting & Analytics
- **Dashboard Analytics**: Real-time statistics and KPIs
- **Department Analysis**: Performance metrics by department
- **Leave Reports**: Comprehensive leave reporting
- **Salary Reports**: Salary and increment reporting
- **Performance Reports**: Employee performance analytics

### 🔐 Security & Permissions
- **Role-based Access**: Admin, HR Manager, and HR Staff roles
- **Audit Logging**: Complete action history with IP tracking
- **Data Validation**: Server-side and client-side validation
- **Secure Authentication**: Password hashing and session management

## Installation Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.2 or higher
- Web server (Apache/Nginx)
- MySQLi extension enabled

### Step 1: Database Setup
1. Access the database setup page: `http://localhost/nia-hris/setup_database.php`
2. This will create all necessary tables and insert default data
3. Default admin credentials:
   - Username: `admin`
   - Password: `admin123`

### Step 2: Configuration
1. Edit `config/database.php` to match your database settings:
   ```php
   $host = 'localhost';
   $dbname = 'nia_hris';
   $username = 'your_username';
   $password = 'your_password';
   ```

### Step 3: File Permissions
Ensure the following directories are writable:
- `uploads/`
- `logs/`

### Step 4: Access the System
1. Navigate to `http://localhost/nia-hris/`
2. Login with the default admin credentials
3. Change the default password immediately

## System Architecture

### Directory Structure
```
nia-hris/
├── config/                 # Database and system configuration
├── includes/              # Shared PHP includes
│   ├── functions.php      # Utility functions
│   ├── header.php         # Common header
│   ├── footer.php         # Common footer
│   └── id_encryption.php  # ID encryption utilities
├── assets/                # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Images and icons
├── database/              # Database scripts and backups
├── uploads/               # File uploads
├── logs/                  # System logs
├── index.php             # Main dashboard
├── login.php             # Login page
├── logout.php            # Logout handler
└── setup_database.php    # Database setup script
```

### Database Schema
The system uses the following main tables:
- `users` - System users and authentication
- `settings` - System configuration
- `colleges` - College/division management
- `departments` - Department management
- `faculty` - Faculty/staff records
- `employees` - Employee records
- `activity_log` - System activity tracking

## Usage Guide

### For System Administrators
1. **User Management**: Create and manage user accounts
2. **System Settings**: Configure organization settings
3. **Database Management**: Monitor system performance

### For HR Managers
1. **Employee Management**: Add, edit, and manage employee records
2. **Leave Management**: Approve/reject leave requests
3. **Performance Reviews**: Conduct employee evaluations
4. **Reports**: Generate various HR reports

### For HR Staff
1. **Data Entry**: Add new employees and faculty
2. **Leave Processing**: Process leave requests
3. **Record Keeping**: Maintain employee records

## Security Features

### Authentication
- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control

### Data Protection
- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- ID encryption for sensitive operations

### Audit Trail
- Complete activity logging
- IP address tracking
- User action history
- System access monitoring

## Customization

### Branding
- Update organization settings in the database
- Modify logo and colors in `includes/header.php`
- Customize favicon in `assets/images/`

### Features
- Enable/disable modules by modifying navigation
- Add custom fields to employee records
- Create custom reports

## Troubleshooting

### Common Issues
1. **Database Connection Error**: Check database credentials in `config/database.php`
2. **Permission Denied**: Ensure upload directories are writable
3. **Session Issues**: Check PHP session configuration
4. **File Upload Errors**: Verify upload directory permissions

### Log Files
- Check `logs/` directory for error logs
- Monitor database logs for connection issues
- Review web server error logs

## Support

For technical support or feature requests, please contact the system administrator.

## License

This system is proprietary software developed for NIA (National Irrigation Administration).

---

**NIA-HRIS v1.0** - Human Resource Information System
