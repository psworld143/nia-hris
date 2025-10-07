<?php
/**
 * Salary Monitoring Script
 * 
 * This script monitors salary changes and prevents unauthorized increments.
 * It should be run periodically (e.g., via cron job) to detect issues.
 * 
 * Usage:
 * - Run manually: php salary-monitor.php
 * - Set up cron job: 0 0,6,12,18 * * * php /path/to/salary-monitor.php
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

echo "=== Salary Monitoring Script ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create a snapshot of current salary data
    $snapshot_query = "SELECT ed.employee_id, ed.basic_salary, ed.step_increment, ed.updated_at, 
                              e.first_name, e.last_name, e.position, e.hire_date
                       FROM employee_details ed 
                       JOIN employees e ON ed.employee_id = e.id 
                       WHERE ed.basic_salary > 0
                       ORDER BY ed.employee_id";
    
    $snapshot_result = mysqli_query($conn, $snapshot_query);
    
    if (!$snapshot_result) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    $employees_checked = mysqli_num_rows($snapshot_result);
    $issues_found = 0;
    $issues_details = [];
    
    echo "Checking {$employees_checked} employees with salary data...\n\n";
    
    // Start transaction for monitoring
    mysqli_begin_transaction($conn);
    
    while ($employee = mysqli_fetch_assoc($snapshot_result)) {
        $employee_issues = [];
        
        // Check 1: Verify if there are approved salary increments for this employee
        $increment_check_query = "SELECT COUNT(*) as count, MAX(created_at) as last_increment 
                                 FROM salary_increments 
                                 WHERE employee_id = ? AND status IN ('approved', 'implemented')";
        $increment_stmt = mysqli_prepare($conn, $increment_check_query);
        mysqli_stmt_bind_param($increment_stmt, "i", $employee['employee_id']);
        mysqli_stmt_execute($increment_stmt);
        $increment_result = mysqli_stmt_get_result($increment_stmt);
        $increment_data = mysqli_fetch_assoc($increment_result);
        
        // Only flag as issue if no increments AND salary is set (not for automatic increments)
        if ($increment_data['count'] == 0 && $employee['basic_salary'] > 0) {
            // Check if this might be an automatic increment
            $auto_increment_query = "SELECT COUNT(*) as count FROM salary_change_monitor 
                                    WHERE employee_id = ? AND change_source = 'automatic' AND is_authorized = 1";
            $auto_stmt = mysqli_prepare($conn, $auto_increment_query);
            mysqli_stmt_bind_param($auto_stmt, "i", $employee['employee_id']);
            mysqli_stmt_execute($auto_stmt);
            $auto_result = mysqli_stmt_get_result($auto_stmt);
            $auto_count = mysqli_fetch_assoc($auto_result)['count'];
            
            if ($auto_count == 0) {
                $employee_issues[] = "Salary set without any approved increment records";
                $issues_found++;
            }
        }
        
        // Check 2: Verify 3-year rule compliance
        if ($employee['hire_date']) {
            $hire_date = new DateTime($employee['hire_date']);
            $current_date = new DateTime();
            $years_of_service = $current_date->diff($hire_date)->y;
            
            if ($increment_data['count'] > 0 && $years_of_service < 3) {
                $employee_issues[] = "Increment before 3 years of service (Current: {$years_of_service} years)";
                $issues_found++;
            }
        }
        
        // Check 3: Check for recent unauthorized changes (within last 24 hours)
        $recent_change_query = "SELECT COUNT(*) as count FROM salary_increments 
                               WHERE employee_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $recent_stmt = mysqli_prepare($conn, $recent_change_query);
        mysqli_stmt_bind_param($recent_stmt, "i", $employee['employee_id']);
        mysqli_stmt_execute($recent_stmt);
        $recent_result = mysqli_stmt_get_result($recent_stmt);
        $recent_data = mysqli_fetch_assoc($recent_result);
        
        if ($recent_data['count'] == 0 && strtotime($employee['updated_at']) > strtotime('-1 day')) {
            $employee_issues[] = "Recent salary change without increment record";
            $issues_found++;
        }
        
        // Check 4: Verify increment amount doesn't exceed 1000
        $amount_check_query = "SELECT increment_amount FROM salary_increments 
                              WHERE employee_id = ? AND status = 'approved' 
                              ORDER BY created_at DESC LIMIT 1";
        $amount_stmt = mysqli_prepare($conn, $amount_check_query);
        mysqli_stmt_bind_param($amount_stmt, "i", $employee['employee_id']);
        mysqli_stmt_execute($amount_stmt);
        $amount_result = mysqli_stmt_get_result($amount_stmt);
        $amount_data = mysqli_fetch_assoc($amount_result);
        
        if ($amount_data && $amount_data['increment_amount'] > 1000) {
            $employee_issues[] = "Increment amount exceeds 1000 limit: " . $amount_data['increment_amount'];
            $issues_found++;
        }
        
        // Log any issues found
        if (!empty($employee_issues)) {
            $issues_details[] = [
                'employee_id' => $employee['employee_id'],
                'name' => $employee['first_name'] . ' ' . $employee['last_name'],
                'position' => $employee['position'],
                'issues' => $employee_issues
            ];
            
            // Log to salary_change_monitor table
            foreach ($employee_issues as $issue) {
                $monitor_query = "INSERT INTO salary_change_monitor (
                    employee_id, 
                    field_changed, 
                    old_value, 
                    new_value, 
                    change_reason, 
                    change_source, 
                    is_authorized, 
                    authorization_reference
                ) VALUES (?, 'salary_validation', '', '', ?, 'automatic', 0, 'MONITOR_SCRIPT')";
                
                $monitor_stmt = mysqli_prepare($conn, $monitor_query);
                mysqli_stmt_bind_param($monitor_stmt, "is", $employee['employee_id'], $issue);
                mysqli_stmt_execute($monitor_stmt);
            }
            
            echo "- ISSUE FOUND: {$employee['first_name']} {$employee['last_name']} (ID: {$employee['employee_id']})\n";
            foreach ($employee_issues as $issue) {
                echo "  * {$issue}\n";
            }
            echo "\n";
        }
    }
    
    // Audit logging removed - not needed for core functionality
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo "=== Summary ===\n";
    echo "Total employees checked: {$employees_checked}\n";
    echo "Issues found: {$issues_found}\n";
    
    if ($issues_found > 0) {
        echo "\n=== Issues Details ===\n";
        foreach ($issues_details as $detail) {
            echo "Employee: {$detail['name']} (ID: {$detail['employee_id']})\n";
            foreach ($detail['issues'] as $issue) {
                echo "  - {$issue}\n";
            }
            echo "\n";
        }
        
        // Send alert (you can customize this)
        $alert_message = "ALERT: {$issues_found} salary issues detected in monitoring script at " . date('Y-m-d H:i:s');
        error_log("[SALARY_MONITOR] {$alert_message}");
        
        echo "Alert logged: {$alert_message}\n";
    } else {
        echo "No issues found. All salary data appears to be compliant.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Rollback transaction if it was started
    if (mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
    // Log the error
    error_log("[SALARY_MONITOR] Error in monitoring script: " . $e->getMessage());
}

echo "\nScript completed at: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n";
?>
