<?php
/**
 * Enhanced Leave Allowance Calculator
 * Implements the specific leave criteria for SEAIT:
 * - Admin employees: 5 days leave with pay regardless of employment status
 * - After 6 months: entitled for regularization
 * - Regular employees: can enjoy leave accumulation
 * - Faculty: same as admin but can only enjoy accumulated leave after 3 years
 * - Non-regular employees/faculty: leave resets every year
 */

class LeaveAllowanceCalculator {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Calculate leave allowance for an employee/faculty for a specific year
     */
    public function calculateLeaveAllowance($employee_id, $employee_type, $year, $leave_type_id = 1) {
        try {
            // Get employee details and regularization status
            $employee_info = $this->getEmployeeInfo($employee_id, $employee_type);
            if (!$employee_info) {
                throw new Exception("Employee not found");
            }
            
            // Check if regularization record exists
            $regularization_info = $this->getRegularizationInfo($employee_id, $employee_type);
            
            // Determine if employee is regular
            $is_regular = $this->isEmployeeRegular($employee_id, $employee_type, $year);
            
            // Get base leave days (always 5 for all employees)
            $base_days = 5;
            
            // Calculate accumulated days from previous years
            $accumulated_days = 0;
            $can_accumulate = false;
            $accumulation_start_year = null;
            
            // Only regular employees/faculty can accumulate leave
            if ($is_regular) {
                $can_accumulate = $this->canEmployeeAccumulateLeave($employee_id, $employee_type, $year);
                if ($can_accumulate) {
                    $accumulated_days = $this->calculateAccumulatedDays($employee_id, $employee_type, $year, $leave_type_id);
                    $accumulation_start_year = $this->getAccumulationStartYear($employee_id, $employee_type);
                }
            } else {
                // Explicitly ensure non-regular employees/faculty have no accumulated leave
                $accumulated_days = 0;
                $can_accumulate = false;
                $accumulation_start_year = null;
            }
            
            $total_days = $base_days + $accumulated_days;
            
            // Get used days for the year
            $used_days = $this->getUsedDays($employee_id, $employee_type, $year, $leave_type_id);
            $remaining_days = max(0, $total_days - $used_days);
            
            return [
                'employee_id' => $employee_id,
                'employee_type' => $employee_type,
                'leave_type_id' => $leave_type_id,
                'year' => $year,
                'base_days' => $base_days,
                'accumulated_days' => $accumulated_days,
                'total_days' => $total_days,
                'used_days' => $used_days,
                'remaining_days' => $remaining_days,
                'is_regular' => $is_regular,
                'can_accumulate' => $can_accumulate,
                'accumulation_start_year' => $accumulation_start_year,
                'regularization_date' => $regularization_info['regularization_date'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Leave calculation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if employee is regular based on regularization status
     */
    private function isEmployeeRegular($employee_id, $employee_type, $year) {
        $regularization_info = $this->getRegularizationInfo($employee_id, $employee_type);
        
        if (!$regularization_info) {
            return false;
        }
        
        // Check if regularization date exists and is before or in the current year
        if ($regularization_info['regularization_date']) {
            $regularization_year = date('Y', strtotime($regularization_info['regularization_date']));
            return $regularization_year <= $year;
        }
        
        // Check if status is 'Regular'
        return $regularization_info['status_name'] === 'Regular';
    }
    
    /**
     * Check if employee can accumulate leave based on rules
     */
    private function canEmployeeAccumulateLeave($employee_id, $employee_type, $year) {
        $regularization_info = $this->getRegularizationInfo($employee_id, $employee_type);
        
        if (!$regularization_info || !$regularization_info['regularization_date']) {
            return false;
        }
        
        $regularization_date = new DateTime($regularization_info['regularization_date']);
        $current_date = new DateTime($year . '-01-01');
        
        // For employees: can accumulate after 6 months (regularization)
        // For faculty: can accumulate after 3 years (36 months)
        if ($employee_type === 'employee') {
            $months_required = 6;
        } else {
            $months_required = 36;
        }
        
        $months_since_regularization = $regularization_date->diff($current_date)->m + ($regularization_date->diff($current_date)->y * 12);
        
        return $months_since_regularization >= $months_required;
    }
    
    /**
     * Calculate accumulated days from previous years
     */
    private function calculateAccumulatedDays($employee_id, $employee_type, $year, $leave_type_id) {
        // Get the year when accumulation started
        $accumulation_start_year = $this->getAccumulationStartYear($employee_id, $employee_type);
        
        if (!$accumulation_start_year || $accumulation_start_year >= $year) {
            return 0;
        }
        
        $total_accumulated = 0;
        
        // Calculate accumulated days from each year since accumulation started
        for ($y = $accumulation_start_year; $y < $year; $y++) {
            $year_balance = $this->getYearBalance($employee_id, $employee_type, $y, $leave_type_id);
            if ($year_balance) {
                $unused_days = max(0, $year_balance['total_days'] - $year_balance['used_days']);
                $total_accumulated += $unused_days;
                
                // Record accumulation in history
                $this->recordAccumulation($employee_id, $employee_type, $y, $year, $unused_days, $leave_type_id);
            }
        }
        
        return $total_accumulated;
    }
    
    /**
     * Get employee information
     */
    private function getEmployeeInfo($employee_id, $employee_type) {
        if ($employee_type === 'employee') {
            $query = "SELECT id, first_name, last_name, employee_id, department, position, hire_date 
                     FROM employees WHERE id = ? AND is_active = 1";
        } else {
            $query = "SELECT id, first_name, last_name, qrcode as employee_id, department, position, created_at as hire_date 
                     FROM faculty WHERE id = ? AND is_active = 1";
        }
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $employee_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Get regularization information
     */
    private function getRegularizationInfo($employee_id, $employee_type) {
        if ($employee_type === 'employee') {
            $query = "SELECT er.*, rs.name as status_name, rs.color as status_color
                     FROM employee_regularization er
                     LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                     WHERE er.employee_id = ? AND er.is_active = 1";
        } else {
            $query = "SELECT fr.*, rs.name as status_name, rs.color as status_color
                     FROM faculty_regularization fr
                     LEFT JOIN regularization_status rs ON fr.current_status_id = rs.id
                     WHERE fr.faculty_id = ? AND fr.is_active = 1";
        }
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $employee_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Get used days for a specific year and leave type
     */
    private function getUsedDays($employee_id, $employee_type, $year, $leave_type_id) {
        if ($employee_type === 'employee') {
            $query = "SELECT COALESCE(SUM(total_days), 0) as used_days
                     FROM employee_leave_requests
                     WHERE employee_id = ? AND leave_type_id = ? 
                     AND YEAR(start_date) = ? AND status IN ('approved_by_head', 'approved_by_hr')";
        } else {
            $query = "SELECT COALESCE(SUM(total_days), 0) as used_days
                     FROM faculty_leave_requests
                     WHERE faculty_id = ? AND leave_type_id = ? 
                     AND YEAR(start_date) = ? AND status IN ('approved_by_head', 'approved_by_hr')";
        }
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'iii', $employee_id, $leave_type_id, $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        return (int)$row['used_days'];
    }
    
    /**
     * Get year balance from enhanced_leave_balances table
     */
    private function getYearBalance($employee_id, $employee_type, $year, $leave_type_id) {
        $query = "SELECT * FROM enhanced_leave_balances 
                 WHERE employee_id = ? AND employee_type = ? AND year = ? AND leave_type_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'isii', $employee_id, $employee_type, $year, $leave_type_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_fetch_assoc($result);
    }
    
    /**
     * Get the year when accumulation started for this employee
     */
    private function getAccumulationStartYear($employee_id, $employee_type) {
        $regularization_info = $this->getRegularizationInfo($employee_id, $employee_type);
        
        if (!$regularization_info || !$regularization_info['regularization_date']) {
            return null;
        }
        
        $regularization_date = new DateTime($regularization_info['regularization_date']);
        
        // For employees: accumulation starts after 6 months
        // For faculty: accumulation starts after 3 years (36 months)
        if ($employee_type === 'employee') {
            $months_required = 6;
        } else {
            $months_required = 36;
        }
        
        $accumulation_start = clone $regularization_date;
        $accumulation_start->add(new DateInterval('P' . $months_required . 'M'));
        
        return (int)$accumulation_start->format('Y');
    }
    
    /**
     * Record accumulation in history table
     */
    private function recordAccumulation($employee_id, $employee_type, $from_year, $to_year, $days, $leave_type_id) {
        // Check if record already exists
        $check_query = "SELECT id FROM leave_accumulation_history 
                       WHERE employee_id = ? AND employee_type = ? AND from_year = ? AND to_year = ? AND leave_type_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $check_query);
        mysqli_stmt_bind_param($stmt, 'isiii', $employee_id, $employee_type, $from_year, $to_year, $leave_type_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            return; // Already recorded
        }
        
        // Insert new record
        $insert_query = "INSERT INTO leave_accumulation_history 
                        (employee_id, employee_type, leave_type_id, from_year, to_year, accumulated_days, reason) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $reason = "Accumulated unused leave from {$from_year} to {$to_year}";
        $stmt = mysqli_prepare($this->conn, $insert_query);
        mysqli_stmt_bind_param($stmt, 'isiiiis', $employee_id, $employee_type, $leave_type_id, $from_year, $to_year, $days, $reason);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Update or create leave balance record
     */
    public function updateLeaveBalance($employee_id, $employee_type, $year, $leave_type_id) {
        $balance_data = $this->calculateLeaveAllowance($employee_id, $employee_type, $year, $leave_type_id);
        
        if (!$balance_data) {
            return false;
        }
        
        // Check if record exists
        $check_query = "SELECT id FROM enhanced_leave_balances 
                       WHERE employee_id = ? AND employee_type = ? AND year = ? AND leave_type_id = ?";
        
        $stmt = mysqli_prepare($this->conn, $check_query);
        mysqli_stmt_bind_param($stmt, 'isii', $employee_id, $employee_type, $year, $leave_type_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing record
            $update_query = "UPDATE enhanced_leave_balances SET 
                            base_days = ?, accumulated_days = ?, total_days = ?, used_days = ?, 
                            remaining_days = ?, is_regular = ?, can_accumulate = ?, 
                            accumulation_start_year = ?, regularization_date = ?, updated_at = NOW()
                            WHERE employee_id = ? AND employee_type = ? AND year = ? AND leave_type_id = ?";
            
            $stmt = mysqli_prepare($this->conn, $update_query);
            mysqli_stmt_bind_param($stmt, 'iiiiiiiisssii', 
                $balance_data['base_days'], $balance_data['accumulated_days'], $balance_data['total_days'],
                $balance_data['used_days'], $balance_data['remaining_days'], $balance_data['is_regular'],
                $balance_data['can_accumulate'], $balance_data['accumulation_start_year'],
                $balance_data['regularization_date'], $employee_id, $employee_type, $year, $leave_type_id);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO enhanced_leave_balances 
                            (employee_id, employee_type, leave_type_id, year, base_days, accumulated_days, 
                             total_days, used_days, remaining_days, is_regular, can_accumulate, 
                             accumulation_start_year, regularization_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($this->conn, $insert_query);
            mysqli_stmt_bind_param($stmt, 'isiiiiiiiiiis', 
                $employee_id, $employee_type, $leave_type_id, $year, $balance_data['base_days'],
                $balance_data['accumulated_days'], $balance_data['total_days'], $balance_data['used_days'],
                $balance_data['remaining_days'], $balance_data['is_regular'], $balance_data['can_accumulate'],
                $balance_data['accumulation_start_year'], $balance_data['regularization_date']);
        }
        
        return mysqli_stmt_execute($stmt);
    }
    
    /**
     * Initialize leave balances for all employees for a specific year
     */
    public function initializeYearlyLeaveBalances($year) {
        $success_count = 0;
        $error_count = 0;
        
        // Get all active employees
        $employee_query = "SELECT id FROM employees WHERE is_active = 1";
        $employee_result = mysqli_query($this->conn, $employee_query);
        
        while ($row = mysqli_fetch_assoc($employee_result)) {
            if ($this->updateLeaveBalance($row['id'], 'employee', $year, 1)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        // Get all active faculty
        $faculty_query = "SELECT id FROM faculty WHERE is_active = 1";
        $faculty_result = mysqli_query($this->conn, $faculty_query);
        
        while ($row = mysqli_fetch_assoc($faculty_result)) {
            if ($this->updateLeaveBalance($row['id'], 'faculty', $year, 1)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        return [
            'success_count' => $success_count,
            'error_count' => $error_count,
            'total_processed' => $success_count + $error_count
        ];
    }
    
    /**
     * Get leave balance summary for HR dashboard
     */
    public function getLeaveBalanceSummary($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = "SELECT 
                    employee_type,
                    COUNT(*) as total_employees,
                    SUM(base_days) as total_base_days,
                    SUM(accumulated_days) as total_accumulated_days,
                    SUM(total_days) as total_available_days,
                    SUM(used_days) as total_used_days,
                    SUM(remaining_days) as total_remaining_days,
                    SUM(CASE WHEN is_regular = 1 THEN 1 ELSE 0 END) as regular_employees,
                    SUM(CASE WHEN can_accumulate = 1 THEN 1 ELSE 0 END) as can_accumulate_count
                  FROM enhanced_leave_balances 
                  WHERE year = ?
                  GROUP BY employee_type";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $summary = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $summary[$row['employee_type']] = $row;
        }
        
        return $summary;
    }
}
?>
