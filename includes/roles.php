<?php
/**
 * Role-Based Access Control (RBAC) System
 * Defines permissions and functions for different user roles
 */

// Role definitions
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_HR_MANAGER', 'hr_manager');
define('ROLE_HR_STAFF', 'human_resource');
define('ROLE_NURSE', 'nurse');

/**
 * Check if user has a specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

/**
 * Check if user is Super Admin (full access)
 */
function isSuperAdmin() {
    return hasRole(ROLE_SUPER_ADMIN);
}

/**
 * Check if user is Admin (administrative access)
 */
function isAdmin() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN]);
}

/**
 * Check if user is HR Manager
 */
function isHRManager() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user is HR Staff
 */
function isHRStaff() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF]);
}

/**
 * Check if user is Nurse
 */
function isNurse() {
    return hasRole(ROLE_NURSE);
}

/**
 * Check if user can view employees
 */
function canViewEmployees() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF, ROLE_NURSE]);
}

/**
 * Check if user can add employees
 */
function canAddEmployees() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF]);
}

/**
 * Check if user can edit employees
 */
function canEditEmployees() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF]);
}

/**
 * Check if user can delete employees
 */
function canDeleteEmployees() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user can view medical records
 */
function canViewMedicalRecords() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF, ROLE_NURSE]);
}

/**
 * Check if user can update medical records
 */
function canUpdateMedicalRecords() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_NURSE]);
}

/**
 * Check if user can manage salary
 */
function canManageSalary() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user can process payroll
 */
function canProcessPayroll() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user can manage users
 */
function canManageUsers() {
    return isSuperAdmin();
}

/**
 * Check if user can access system settings
 */
function canAccessSettings() {
    return isSuperAdmin();
}

/**
 * Check if user can view reports
 */
function canViewReports() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF, ROLE_NURSE]);
}

/**
 * Check if user can manage departments
 */
function canManageDepartments() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user can manage leave requests
 */
function canManageLeave() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER, ROLE_HR_STAFF]);
}

/**
 * Check if user can conduct performance reviews
 */
function canConductPerformanceReviews() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Check if user can manage training programs
 */
function canManageTraining() {
    return hasAnyRole([ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_HR_MANAGER]);
}

/**
 * Require specific role (redirect if unauthorized)
 */
function requireRole($roles, $redirect = 'index.php') {
    if (!hasAnyRole($roles)) {
        header("Location: $redirect");
        exit();
    }
}

/**
 * Require permission (redirect if unauthorized)
 */
function requirePermission($permissionFunction, $redirect = 'index.php') {
    if (!$permissionFunction()) {
        header("Location: $redirect");
        exit();
    }
}

/**
 * Get role display name
 */
function getRoleDisplayName($role) {
    $roleNames = [
        ROLE_SUPER_ADMIN => 'Super Administrator',
        ROLE_ADMIN => 'Administrator',
        ROLE_HR_MANAGER => 'HR Manager',
        ROLE_HR_STAFF => 'HR Staff',
        ROLE_NURSE => 'Nurse'
    ];
    return $roleNames[$role] ?? 'Unknown Role';
}

/**
 * Get role badge HTML
 */
function getRoleBadge($role) {
    $badges = [
        ROLE_SUPER_ADMIN => '<span class="badge bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-xs font-semibold">SUPER ADMIN</span>',
        ROLE_ADMIN => '<span class="badge bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-1 rounded-full text-xs font-semibold">ADMIN</span>',
        ROLE_HR_MANAGER => '<span class="badge bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">HR MANAGER</span>',
        ROLE_HR_STAFF => '<span class="badge bg-gradient-to-r from-cyan-500 to-cyan-600 text-white px-3 py-1 rounded-full text-xs font-semibold">HR STAFF</span>',
        ROLE_NURSE => '<span class="badge bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-1 rounded-full text-xs font-semibold">NURSE</span>'
    ];
    return $badges[$role] ?? '<span class="badge bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-semibold">UNKNOWN</span>';
}

/**
 * Get all permissions for a role
 */
function getRolePermissions($role) {
    $permissions = [
        ROLE_SUPER_ADMIN => [
            'view_employees' => true,
            'add_employees' => true,
            'edit_employees' => true,
            'delete_employees' => true,
            'view_medical_records' => true,
            'update_medical_records' => true,
            'manage_salary' => true,
            'process_payroll' => true,
            'manage_users' => true,
            'access_settings' => true,
            'view_reports' => true,
            'manage_departments' => true,
            'manage_leave' => true,
            'conduct_performance_reviews' => true,
            'manage_training' => true,
        ],
        ROLE_ADMIN => [
            'view_employees' => true,
            'add_employees' => true,
            'edit_employees' => true,
            'delete_employees' => true,
            'view_medical_records' => true,
            'update_medical_records' => false,
            'manage_salary' => true,
            'process_payroll' => true,
            'manage_users' => false,
            'access_settings' => false,
            'view_reports' => true,
            'manage_departments' => true,
            'manage_leave' => true,
            'conduct_performance_reviews' => true,
            'manage_training' => true,
        ],
        ROLE_HR_MANAGER => [
            'view_employees' => true,
            'add_employees' => true,
            'edit_employees' => true,
            'delete_employees' => true,
            'view_medical_records' => true,
            'update_medical_records' => false,
            'manage_salary' => true,
            'process_payroll' => true,
            'manage_users' => false,
            'access_settings' => false,
            'view_reports' => true,
            'manage_departments' => true,
            'manage_leave' => true,
            'conduct_performance_reviews' => true,
            'manage_training' => true,
        ],
        ROLE_HR_STAFF => [
            'view_employees' => true,
            'add_employees' => true,
            'edit_employees' => true,
            'delete_employees' => false,
            'view_medical_records' => true,
            'update_medical_records' => false,
            'manage_salary' => false,
            'process_payroll' => false,
            'manage_users' => false,
            'access_settings' => false,
            'view_reports' => true,
            'manage_departments' => false,
            'manage_leave' => true,
            'conduct_performance_reviews' => false,
            'manage_training' => false,
        ],
        ROLE_NURSE => [
            'view_employees' => true,
            'add_employees' => false,
            'edit_employees' => false,
            'delete_employees' => false,
            'view_medical_records' => true,
            'update_medical_records' => true,
            'manage_salary' => false,
            'process_payroll' => false,
            'manage_users' => false,
            'access_settings' => false,
            'view_reports' => true,
            'manage_departments' => false,
            'manage_leave' => false,
            'conduct_performance_reviews' => false,
            'manage_training' => false,
        ],
    ];
    
    return $permissions[$role] ?? [];
}

/**
 * Check if current user has a specific permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $permissions = getRolePermissions($_SESSION['role']);
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

/**
 * Get logged in user's role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get logged in user's full name
 */
function getCurrentUserName() {
    $firstName = $_SESSION['first_name'] ?? '';
    $lastName = $_SESSION['last_name'] ?? '';
    return trim("$firstName $lastName");
}

/**
 * Log user activity with role information
 */
function logActivity($action, $description, $conn) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $role = getCurrentUserRole();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $action, $description, $ip_address, $user_agent);
    return mysqli_stmt_execute($stmt);
}
?>

