<?php
/**
 * Automatic Salary Increment Processor
 * 
 * This script automatically applies salary increments to all eligible employees
 * every 3 years after their hire date.
 * 
 * Usage:
 * - Run manually: php auto-increment-processor.php
 * - Set up cron job: 0 0 1 * * php /path/to/auto-increment-processor.php (monthly)
 */

// Use unified database connection
require_once __DIR__ . '/config/database.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");
// require_once 'includes/functions.php'; // Not needed for this script

// Set timezone
date_default_timezone_set('Asia/Manila');

echo "=== Automatic Salary Increment Processor ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    $processed_count = 0;
    $eligible_count = 0;
    $error_count = 0;
    $processed_details = [];
    
    // Process employees first
    $employee_query = "SELECT 
        e.id as employee_id,
        e.employee_id as emp_id,
        e.first_name,
        e.last_name,
        e.position,
        e.department,
        e.employee_type,
        e.hire_date,
        ed.basic_salary,
        DATEDIFF(NOW(), e.hire_date) / 365.25 as years_service,
        COALESCE(last_inc.last_increment_date, e.hire_date) as reference_date,
        DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, e.hire_date)) / 365.25 as years_since_last_increment,
        ss.id as salary_structure_id,
        ss.increment_percentage,
        ss.incrementation_amount,
        ss.incrementation_frequency_years,
        ss.minimum_salary,
        ss.maximum_salary,
        'employee' as person_type
    FROM employees e 
    LEFT JOIN employee_details ed ON e.id = ed.employee_id 
    LEFT JOIN salary_structures ss ON e.position = ss.position_title AND ss.is_active = 1
    LEFT JOIN (
        SELECT employee_id, MAX(effective_date) as last_increment_date
        FROM salary_increments 
        WHERE status IN ('approved', 'implemented')
        GROUP BY employee_id
    ) last_inc ON e.id = last_inc.employee_id
    WHERE e.is_active = 1 
    AND e.hire_date IS NOT NULL
    AND ss.id IS NOT NULL  -- Must have a salary structure
    AND DATEDIFF(NOW(), e.hire_date) >= (ss.incrementation_frequency_years * 365.25)  -- At least required years of service
    AND DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, e.hire_date)) >= (ss.incrementation_frequency_years * 365.25)  -- At least required years since last increment
    ORDER BY e.hire_date";
    
    // Process faculty second
    $faculty_query = "SELECT 
        f.id as employee_id,
        f.employee_id as emp_id,
        f.first_name,
        f.last_name,
        f.position,
        f.department,
        'faculty' as employee_type,
        f.hire_date,
        fd.basic_salary,
        DATEDIFF(NOW(), f.hire_date) / 365.25 as years_service,
        COALESCE(last_inc.last_increment_date, f.hire_date) as reference_date,
        DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, f.hire_date)) / 365.25 as years_since_last_increment,
        ss.id as salary_structure_id,
        ss.increment_percentage,
        ss.incrementation_amount,
        ss.incrementation_frequency_years,
        ss.minimum_salary,
        ss.maximum_salary,
        'faculty' as person_type
    FROM employees f 
    LEFT JOIN employee_details fd ON f.id = fd.employee_id 
    LEFT JOIN salary_structures ss ON f.position COLLATE utf8mb4_unicode_ci = ss.position_title AND ss.is_active = 1
    LEFT JOIN (
        SELECT employee_id, MAX(effective_date) as last_increment_date
        FROM salary_increments 
        WHERE status IN ('approved', 'implemented')
        GROUP BY employee_id
    ) last_inc ON f.id = last_inc.employee_id
    WHERE f.is_active = 1 
    AND f.hire_date IS NOT NULL
    AND ss.id IS NOT NULL  -- Must have a salary structure
    AND DATEDIFF(NOW(), f.hire_date) >= (ss.incrementation_frequency_years * 365.25)  -- At least required years of service
    AND DATEDIFF(NOW(), COALESCE(last_inc.last_increment_date, f.hire_date)) >= (ss.incrementation_frequency_years * 365.25)  -- At least required years since last increment
    ORDER BY f.hire_date";
    
    // Process employees
    $employee_result = mysqli_query($conn, $employee_query);
    if (!$employee_result) {
        throw new Exception("Database error (employees): " . mysqli_error($conn));
    }
    
    // Process faculty
    $faculty_result = mysqli_query($conn, $faculty_query);
    if (!$faculty_result) {
        throw new Exception("Database error (faculty): " . mysqli_error($conn));
    }
    
    $employee_count = mysqli_num_rows($employee_result);
    $faculty_count = mysqli_num_rows($faculty_result);
    $eligible_count = $employee_count + $faculty_count;
    
    echo "Found {$eligible_count} employees and faculty eligible for automatic increment:\n";
    echo "  - Employees: {$employee_count}\n";
    echo "  - Faculty: {$faculty_count}\n\n";
    
    // Process employees first
    while ($employee = mysqli_fetch_assoc($employee_result)) {
        $person_type = ucfirst($employee['person_type']);
        echo "Processing: {$employee['first_name']} {$employee['last_name']} (ID: {$employee['emp_id']}) - {$person_type}\n";
        echo "  - Position: {$employee['position']}\n";
        echo "  - Years of Service: " . round($employee['years_service'], 1) . " years\n";
        echo "  - Years Since Last Increment: " . round($employee['years_since_last_increment'], 1) . " years\n";
        echo "  - Current Salary: ₱" . number_format($employee['basic_salary'] ?? 0, 2) . "\n";
        
        try {
            // Set default salary if not set
            $current_salary = $employee['basic_salary'] ?? 0;
            if ($current_salary <= 0) {
                $current_salary = $employee['minimum_salary'] ?? getDefaultSalaryForPosition($employee['position']);
                echo "  - Setting default salary: ₱" . number_format($current_salary, 2) . "\n";
                
                // Update employee_details with default salary
                $update_salary_query = "INSERT INTO employee_details (employee_id, basic_salary, created_at) 
                                       VALUES (?, ?, NOW()) 
                                       ON DUPLICATE KEY UPDATE basic_salary = ?, updated_at = NOW()";
                $update_stmt = mysqli_prepare($conn, $update_salary_query);
                mysqli_stmt_bind_param($update_stmt, "idd", $employee['employee_id'], $current_salary, $current_salary);
                mysqli_stmt_execute($update_stmt);
            }
            
            // Calculate increment based on salary structure rules
            $increment_amount = 0;
            $increment_type = '';
            
            if (!empty($employee['incrementation_amount']) && $employee['incrementation_amount'] > 0) {
                // Fixed amount increment
                $increment_amount = $employee['incrementation_amount'];
                $increment_type = 'fixed';
                echo "  - Using fixed increment: ₱" . number_format($increment_amount, 2) . "\n";
            } elseif (!empty($employee['increment_percentage']) && $employee['increment_percentage'] > 0) {
                // Percentage increment
                $increment_amount = ($current_salary * $employee['increment_percentage']) / 100;
                $increment_type = 'percentage';
                echo "  - Using percentage increment: " . $employee['increment_percentage'] . "% (₱" . number_format($increment_amount, 2) . ")\n";
            } else {
                // Fallback to default 1000 if no structure rules defined
                $increment_amount = 1000.00;
                $increment_type = 'default';
                echo "  - Using default increment: ₱" . number_format($increment_amount, 2) . "\n";
            }
            
            $new_salary = $current_salary + $increment_amount;
            $increment_percentage = $current_salary > 0 ? ($increment_amount / $current_salary) * 100 : 0;
            
            // Validate against maximum salary
            if (!empty($employee['maximum_salary']) && $new_salary > $employee['maximum_salary']) {
                $increment_amount = $employee['maximum_salary'] - $current_salary;
                $new_salary = $employee['maximum_salary'];
                $increment_percentage = $current_salary > 0 ? ($increment_amount / $current_salary) * 100 : 0;
                echo "  - Adjusted increment to respect maximum salary: ₱" . number_format($increment_amount, 2) . "\n";
            }
            
            $salary_structure_id = $employee['salary_structure_id'];
            
            // Calculate effective date based on salary structure frequency
            $frequency_years = $employee['incrementation_frequency_years'] ?? 3;
            $effective_date = date('Y-m-d', strtotime($employee['reference_date'] . " + {$frequency_years} years"));
            
            // Insert automatic salary increment
            $incrementation_name = "Automatic {$frequency_years}-Year Increment ({$increment_type})";
            $reason = "Automatic increment after {$frequency_years} years of service based on salary structure rules";
            
            $increment_query = "INSERT INTO salary_increments (
                employee_id, 
                salary_structure_id, 
                current_salary, 
                increment_amount, 
                new_salary, 
                increment_percentage, 
                increment_type, 
                incrementation_name,
                incrementation_description,
                incrementation_amount,
                incrementation_frequency_years,
                effective_date, 
                reason, 
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 'regular', ?, ?, ?, ?, ?, ?, 'pending', 1)";
            
            $increment_stmt = mysqli_prepare($conn, $increment_query);
            mysqli_stmt_bind_param($increment_stmt, "iiddddsssiss", 
                $employee['employee_id'], 
                $salary_structure_id, 
                $current_salary, 
                $increment_amount, 
                $new_salary, 
                $increment_percentage,
                $incrementation_name,
                $reason,
                $increment_amount,
                $frequency_years,
                $effective_date, 
                $reason
            );
            
            if (mysqli_stmt_execute($increment_stmt)) {
                $increment_id = mysqli_insert_id($conn);
                
                // Note: Salary will be updated only after HR confirmation
                echo "  - Created pending increment record (ID: {$increment_id})\n";
                echo "  - Awaiting HR confirmation before salary update\n";
                
                // Add to history
                $history_query = "INSERT INTO increment_history (
                    employee_id, 
                    increment_id, 
                    old_salary, 
                    new_salary, 
                    increment_amount, 
                    increment_percentage, 
                    effective_date, 
                    action, 
                    action_by,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'created', 1, 'Automatic increment created - pending HR confirmation')";
                
                $history_stmt = mysqli_prepare($conn, $history_query);
                mysqli_stmt_bind_param($history_stmt, "iidddds", 
                    $employee['employee_id'], 
                    $increment_id, 
                    $current_salary, 
                    $new_salary, 
                    $increment_amount, 
                    $increment_percentage, 
                    $effective_date
                );
                mysqli_stmt_execute($history_stmt);
                
                // Log the pending increment
                $monitor_query = "INSERT INTO salary_change_monitor (
                    employee_id, 
                    field_changed, 
                    old_value, 
                    new_value, 
                    change_reason, 
                    changed_by,
                    change_source, 
                    is_authorized, 
                    authorization_reference
                ) VALUES (?, 'basic_salary', ?, ?, 'Pending automatic increment - awaiting HR confirmation', 1, 'automatic_pending', 0, ?)";
                
                $monitor_stmt = mysqli_prepare($conn, $monitor_query);
                $reference = "AUTO_INCREMENT_" . $increment_id;
                mysqli_stmt_bind_param($monitor_stmt, "isss", 
                    $employee['employee_id'], 
                    $current_salary, 
                    $new_salary, 
                    $reference
                );
                mysqli_stmt_execute($monitor_stmt);
                
                $processed_count++;
                echo "  ✓ Increment applied successfully!\n";
                echo "  - New Salary: ₱" . number_format($new_salary, 2) . "\n";
                echo "  - Increment ID: #" . $increment_id . "\n";
                
                $processed_details[] = [
                    'employee_id' => $employee['employee_id'],
                    'name' => $employee['first_name'] . ' ' . $employee['last_name'],
                    'position' => $employee['position'],
                    'old_salary' => $current_salary,
                    'new_salary' => $new_salary,
                    'increment_id' => $increment_id
                ];
                
            } else {
                throw new Exception("Error creating salary increment: " . mysqli_error($conn));
            }
            
        } catch (Exception $e) {
            $error_count++;
            echo "  ✗ Error processing employee: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Process faculty second
    while ($faculty = mysqli_fetch_assoc($faculty_result)) {
        $person_type = ucfirst($faculty['person_type']);
        echo "Processing: {$faculty['first_name']} {$faculty['last_name']} (ID: {$faculty['emp_id']}) - {$person_type}\n";
        echo "  - Position: {$faculty['position']}\n";
        echo "  - Years of Service: " . round($faculty['years_service'], 1) . " years\n";
        echo "  - Years Since Last Increment: " . round($faculty['years_since_last_increment'], 1) . " years\n";
        echo "  - Current Salary: ₱" . number_format($faculty['basic_salary'] ?? 0, 2) . "\n";
        
        try {
            // Set default salary if not set
            $current_salary = $faculty['basic_salary'] ?? 0;
            if ($current_salary <= 0) {
                $current_salary = $faculty['minimum_salary'] ?? 20000;
                echo "  - Warning: No salary set, using minimum: ₱" . number_format($current_salary, 2) . "\n";
            }
            
            // Calculate increment amount based on salary structure
            $salary_structure_id = $faculty['salary_structure_id'];
            $increment_percentage = $faculty['increment_percentage'] ?? 0;
            $increment_amount = $faculty['incrementation_amount'] ?? 1000;
            
            // Use percentage if available, otherwise use fixed amount
            if ($increment_percentage > 0) {
                $increment_amount = $current_salary * ($increment_percentage / 100);
                $increment_type = "percentage";
            } else {
                $increment_type = "fixed";
            }
            
            $new_salary = $current_salary + $increment_amount;
            
            // Check if new salary exceeds maximum
            $maximum_salary = $faculty['maximum_salary'] ?? null;
            if ($maximum_salary && $new_salary > $maximum_salary) {
                $increment_amount = $maximum_salary - $current_salary;
                $new_salary = $maximum_salary;
                echo "  - Warning: Increment capped at maximum salary limit\n";
            }
            
            echo "  - Increment Amount: ₱" . number_format($increment_amount, 2) . " ({$increment_type})\n";
            echo "  - New Salary: ₱" . number_format($new_salary, 2) . "\n";
            
            // Calculate effective date based on frequency
            $frequency_years = $faculty['incrementation_frequency_years'] ?? 3;
            $effective_date = date('Y-m-d', strtotime($faculty['reference_date'] . " + {$frequency_years} years"));
            
            // Insert automatic salary increment
            $incrementation_name = "Automatic {$frequency_years}-Year Increment ({$increment_type})";
            $reason = "Automatic increment after {$frequency_years} years of service based on salary structure rules";
            
            $increment_query = "INSERT INTO salary_increments (
                employee_id, 
                salary_structure_id, 
                current_salary, 
                increment_amount, 
                new_salary, 
                increment_percentage, 
                increment_type, 
                incrementation_name,
                incrementation_description,
                incrementation_amount,
                incrementation_frequency_years,
                effective_date, 
                reason, 
                status,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 'regular', ?, ?, ?, ?, ?, ?, 'pending', 1)";
            
            $increment_stmt = mysqli_prepare($conn, $increment_query);
            mysqli_stmt_bind_param($increment_stmt, "iiddddsssiss", 
                $faculty['employee_id'], 
                $salary_structure_id, 
                $current_salary, 
                $increment_amount, 
                $new_salary, 
                $increment_percentage,
                $incrementation_name,
                $reason,
                $increment_amount,
                $frequency_years,
                $effective_date, 
                $reason
            );
            
            if (mysqli_stmt_execute($increment_stmt)) {
                $increment_id = mysqli_insert_id($conn);
                
                // Note: Salary will be updated only after HR confirmation
                echo "  - Created pending increment record (ID: {$increment_id})\n";
                echo "  - Awaiting HR confirmation before salary update\n";
                
                // Add to history
                $history_query = "INSERT INTO increment_history (
                    employee_id, 
                    increment_id, 
                    old_salary, 
                    new_salary, 
                    increment_amount, 
                    increment_percentage,
                    effective_date,
                    action, 
                    action_by,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'created', 1, ?)";
                
                $history_stmt = mysqli_prepare($conn, $history_query);
                $history_notes = "Automatic increment created - pending HR confirmation";
                mysqli_stmt_bind_param($history_stmt, "iiddddss", 
                    $faculty['employee_id'], 
                    $increment_id, 
                    $current_salary, 
                    $new_salary, 
                    $increment_amount,
                    $increment_percentage,
                    $effective_date,
                    $history_notes
                );
                mysqli_stmt_execute($history_stmt);
                
                // Monitor salary change
                $monitor_query = "INSERT INTO salary_change_monitor (
                    employee_id, 
                    field_changed,
                    old_value, 
                    new_value, 
                    change_reason, 
                    changed_by,
                    change_source, 
                    is_authorized, 
                    authorization_reference
                ) VALUES (?, 'basic_salary', ?, ?, ?, 1, 'automatic', 0, ?)";
                
                $monitor_stmt = mysqli_prepare($conn, $monitor_query);
                $monitor_reason = "Pending automatic increment - awaiting HR confirmation";
                $auth_reference = "increment_id_{$increment_id}";
                mysqli_stmt_bind_param($monitor_stmt, "issss", 
                    $faculty['employee_id'], 
                    $current_salary, 
                    $new_salary, 
                    $monitor_reason,
                    $auth_reference
                );
                mysqli_stmt_execute($monitor_stmt);
                
                $processed_count++;
                $processed_details[] = [
                    'name' => "{$faculty['first_name']} {$faculty['last_name']}",
                    'type' => 'Faculty',
                    'amount' => $increment_amount,
                    'effective_date' => $effective_date
                ];
                
                echo "  ✓ Successfully created pending increment\n";
                
            } else {
                throw new Exception("Failed to create increment record: " . mysqli_stmt_error($increment_stmt));
            }
            
        } catch (Exception $e) {
            $error_count++;
            echo "  ✗ Error processing faculty: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Audit logging removed - not needed for core functionality
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo "=== Summary ===\n";
    echo "Total eligible employees: {$eligible_count}\n";
    echo "Pending increments created: {$processed_count}\n";
    echo "Errors encountered: {$error_count}\n";
    
    if ($processed_count > 0) {
        echo "\n=== Pending Increments (Awaiting HR Confirmation) ===\n";
        foreach ($processed_details as $detail) {
            echo "- {$detail['name']} ({$detail['position']}): ₱" . number_format($detail['old_salary'], 2) . " → ₱" . number_format($detail['new_salary'], 2) . " [ID: {$detail['increment_id']}]\n";
        }
        
        echo "\n⚠️  IMPORTANT: These increments are PENDING and require HR confirmation before taking effect.\n";
        echo "   Please review and approve them in the Auto-Increment Management interface.\n";
        
        // Log success
        $log_message = "Auto-increment processor completed: {$processed_count} pending increments created at " . date('Y-m-d H:i:s');
        error_log("[AUTO_INCREMENT] {$log_message}");
        
        echo "\nSuccess logged: {$log_message}\n";
    } else {
        echo "\nNo employees were eligible for automatic increment at this time.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Rollback transaction if it was started
    if (mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
    // Log the error
    error_log("[AUTO_INCREMENT] Error in auto-increment processor: " . $e->getMessage());
}

echo "\nScript completed at: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n";

// Function to get default salary based on position
function getDefaultSalaryForPosition($position) {
    $default_salaries = [
        'Professor' => 45000,
        'Associate Professor' => 40000,
        'Assistant Professor' => 35000,
        'Instructor' => 30000,
        'HR Manager' => 35000,
        'Department Head' => 40000,
        'Administrative Staff' => 25000,
        'IT Staff' => 30000,
        'Finance Staff' => 28000
    ];
    
    return $default_salaries[$position] ?? 25000; // Default minimum salary
}
?>
