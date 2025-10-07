<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$period_id = (int)($input['period_id'] ?? 0);
$payroll_data = $input['payroll_data'] ?? [];

if ($period_id <= 0 || empty($payroll_data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

try {
    $saved_count = 0;
    $error_count = 0;
    
    foreach ($payroll_data as $record) {
        $employee_id = (int)$record['employee_id'];
        
        // Get employee details for the record
        $emp_query = "SELECT e.employee_id, e.first_name, e.last_name, e.department, e.position 
                      FROM employees e WHERE e.id = ?";
        $emp_stmt = mysqli_prepare($conn, $emp_query);
        mysqli_stmt_bind_param($emp_stmt, "i", $employee_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_result = mysqli_stmt_get_result($emp_stmt);
        $emp = mysqli_fetch_assoc($emp_result);
        
        if (!$emp) continue;
        
        $employee_name = $emp['first_name'] . ' ' . $emp['last_name'];
        
        // Insert or update payroll record
        $query = "INSERT INTO payroll_records (
            payroll_period_id, employee_id, employee_name, employee_number, department, position,
            regular_hours, overtime_hours, night_diff_hours,
            hourly_rate, daily_rate, monthly_rate,
            overtime_rate, night_diff_rate,
            basic_pay, overtime_pay, night_diff_pay, allowances,
            gross_pay,
            sss_contribution, philhealth_contribution, pagibig_contribution, withholding_tax,
            sss_loan, pagibig_loan, salary_loan,
            total_deductions,
            net_pay,
            status
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?,
            ?,
            'calculated'
        ) ON DUPLICATE KEY UPDATE
            regular_hours = VALUES(regular_hours),
            overtime_hours = VALUES(overtime_hours),
            night_diff_hours = VALUES(night_diff_hours),
            hourly_rate = VALUES(hourly_rate),
            daily_rate = VALUES(daily_rate),
            monthly_rate = VALUES(monthly_rate),
            overtime_rate = VALUES(overtime_rate),
            night_diff_rate = VALUES(night_diff_rate),
            basic_pay = VALUES(basic_pay),
            overtime_pay = VALUES(overtime_pay),
            night_diff_pay = VALUES(night_diff_pay),
            allowances = VALUES(allowances),
            gross_pay = VALUES(gross_pay),
            sss_contribution = VALUES(sss_contribution),
            philhealth_contribution = VALUES(philhealth_contribution),
            pagibig_contribution = VALUES(pagibig_contribution),
            withholding_tax = VALUES(withholding_tax),
            sss_loan = VALUES(sss_loan),
            pagibig_loan = VALUES(pagibig_loan),
            salary_loan = VALUES(salary_loan),
            total_deductions = VALUES(total_deductions),
            net_pay = VALUES(net_pay),
            updated_at = NOW()";
        
        $stmt = mysqli_prepare($conn, $query);
        
        // Assign values to variables for bind_param
        $overtime_rate = $record['overtimeRate'] ?? 1.25;
        $night_diff_rate = $record['nightDiffRate'] ?? 0.10;
        
        mysqli_stmt_bind_param($stmt, "iissssddddddddddddddddddddd",
            $period_id,
            $employee_id,
            $employee_name,
            $emp['employee_id'],
            $emp['department'],
            $emp['position'],
            $record['regularHours'],
            $record['overtimeHours'],
            $record['nightDiffHours'],
            $record['hourlyRate'],
            $record['dailyRate'],
            $record['monthlyRate'],
            $overtime_rate,
            $night_diff_rate,
            $record['basicPay'],
            $record['overtimePay'],
            $record['nightDiffPay'],
            $record['allowancePay'],
            $record['grossPay'],
            $record['deductions']['sss'],
            $record['deductions']['philhealth'],
            $record['deductions']['pagibig'],
            $record['deductions']['tax'],
            $record['deductions']['sss_loan'],
            $record['deductions']['pagibig_loan'],
            $record['deductions']['salary_loan'],
            $record['totalDeductions'],
            $record['netPay']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $saved_count++;
        } else {
            $error_count++;
        }
    }
    
    // Update period totals
    $update_period = "UPDATE payroll_periods pp
                      SET total_employees = (SELECT COUNT(*) FROM payroll_records WHERE payroll_period_id = ?),
                          total_gross_pay = (SELECT SUM(gross_pay) FROM payroll_records WHERE payroll_period_id = ?),
                          total_deductions = (SELECT SUM(total_deductions) FROM payroll_records WHERE payroll_period_id = ?),
                          total_net_pay = (SELECT SUM(net_pay) FROM payroll_records WHERE payroll_period_id = ?),
                          status = 'processing'
                      WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_period);
    mysqli_stmt_bind_param($update_stmt, "iiiii", $period_id, $period_id, $period_id, $period_id, $period_id);
    mysqli_stmt_execute($update_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => "Payroll data saved successfully! ($saved_count employee(s) processed)",
        'saved' => $saved_count,
        'errors' => $error_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>

