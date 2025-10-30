<?php
// Prevent any output before JSON
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL); // Still log errors, just don't display them

require_once 'config/database.php';

// Clean any output buffer and set JSON header
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data: ' . json_last_error_msg()
    ]);
    exit();
}

$period_id = (int)($input['period_id'] ?? 0);
$payroll_data = $input['payroll_data'] ?? [];

if ($period_id <= 0 || empty($payroll_data)) {
    ob_clean();
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
        
        if (!$stmt) {
            error_log("Failed to prepare payroll record query for employee $employee_id: " . mysqli_error($conn));
            $error_count++;
            continue;
        }
        
        // Assign values to variables for bind_param - ensure all are properly typed and have defaults
        $overtime_rate = (float)($record['overtimeRate'] ?? 1.25);
        $night_diff_rate = (float)($record['nightDiffRate'] ?? 0.10);
        
        // Safely get all values with defaults to prevent null warnings
        $regular_hours = (float)($record['regularHours'] ?? 0);
        $overtime_hours = (float)($record['overtimeHours'] ?? 0);
        $night_diff_hours = (float)($record['nightDiffHours'] ?? 0);
        $hourly_rate = (float)($record['hourlyRate'] ?? 0);
        $daily_rate = (float)($record['dailyRate'] ?? 0);
        $monthly_rate = (float)($record['monthlyRate'] ?? 0);
        $basic_pay = (float)($record['basicPay'] ?? 0);
        $overtime_pay = (float)($record['overtimePay'] ?? 0);
        $night_diff_pay = (float)($record['nightDiffPay'] ?? 0);
        $allowance_pay = (float)($record['allowancePay'] ?? 0);
        $gross_pay = (float)($record['grossPay'] ?? 0);
        $sss = (float)($record['deductions']['sss'] ?? 0);
        $philhealth = (float)($record['deductions']['philhealth'] ?? 0);
        $pagibig = (float)($record['deductions']['pagibig'] ?? 0);
        $tax = (float)($record['deductions']['tax'] ?? 0);
        $sss_loan = (float)($record['deductions']['sss_loan'] ?? 0);
        $pagibig_loan = (float)($record['deductions']['pagibig_loan'] ?? 0);
        $salary_loan = (float)($record['deductions']['salary_loan'] ?? 0);
        $total_deductions = (float)($record['totalDeductions'] ?? 0);
        $net_pay = (float)($record['netPay'] ?? 0);
        
        // Ensure string values are not null
        $employee_number = $emp['employee_id'] ?? '';
        $department = $emp['department'] ?? '';
        $position = $emp['position'] ?? '';
        
        mysqli_stmt_bind_param($stmt, "iissssdddddddddddddddddddddd",
            $period_id,
            $employee_id,
            $employee_name,
            $employee_number,
            $department,
            $position,
            $regular_hours,
            $overtime_hours,
            $night_diff_hours,
            $hourly_rate,
            $daily_rate,
            $monthly_rate,
            $overtime_rate,
            $night_diff_rate,
            $basic_pay,
            $overtime_pay,
            $night_diff_pay,
            $allowance_pay,
            $gross_pay,
            $sss,
            $philhealth,
            $pagibig,
            $tax,
            $sss_loan,
            $pagibig_loan,
            $salary_loan,
            $total_deductions,
            $net_pay
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $saved_count++;
        } else {
            $error_count++;
            $db_error = mysqli_error($conn);
            $stmt_error = mysqli_stmt_error($stmt);
            // Log the actual SQL error for debugging
            error_log("Payroll record save error for employee $employee_id: " . $db_error);
            error_log("SQL Error: " . $stmt_error);
            
            // Store error details for response
            if (!isset($errors)) {
                $errors = [];
            }
            $errors[] = [
                'employee_id' => $employee_id,
                'employee_name' => $employee_name,
                'database_error' => $db_error,
                'statement_error' => $stmt_error
            ];
        }
    }
    
    // Update period totals
    $update_period = "UPDATE payroll_periods pp
                      SET total_employees = (SELECT COUNT(*) FROM payroll_records WHERE payroll_period_id = ?),
                          total_gross_pay = COALESCE((SELECT SUM(gross_pay) FROM payroll_records WHERE payroll_period_id = ?), 0),
                          total_deductions = COALESCE((SELECT SUM(total_deductions) FROM payroll_records WHERE payroll_period_id = ?), 0),
                          total_net_pay = COALESCE((SELECT SUM(net_pay) FROM payroll_records WHERE payroll_period_id = ?), 0),
                          status = 'processing'
                      WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_period);
    if (!$update_stmt) {
        error_log("Failed to prepare period update query: " . mysqli_error($conn));
        throw new Exception("Database error: Failed to prepare period update query");
    }
    mysqli_stmt_bind_param($update_stmt, "iiiii", $period_id, $period_id, $period_id, $period_id, $period_id);
    if (!mysqli_stmt_execute($update_stmt)) {
        error_log("Failed to execute period update: " . mysqli_error($conn));
        throw new Exception("Database error: Failed to update period totals");
    }
    
    // Clean any output before sending JSON
    ob_clean();
    
    $response = [
        'success' => true,
        'message' => "Payroll data saved successfully! ($saved_count employee(s) processed)",
        'saved' => $saved_count,
        'errors' => $error_count
    ];
    
    // Include detailed errors if any occurred
    if (isset($errors) && !empty($errors)) {
        $response['error_details'] = $errors;
        $response['message'] = "Payroll data saved with $error_count error(s)! ($saved_count employee(s) processed successfully)";
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clean output and return error as JSON
    ob_clean();
    http_response_code(500);
    
    $db_error = isset($conn) ? mysqli_error($conn) : 'No database connection';
    $exception_msg = $e->getMessage();
    $stack_trace = $e->getTraceAsString();
    
    error_log("Payroll save exception: " . $exception_msg);
    error_log("Database error: " . $db_error);
    error_log("Stack trace: " . $stack_trace);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $exception_msg,
        'error_type' => 'Exception',
        'database_error' => $db_error,
        'stack_trace' => $stack_trace,
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Catch fatal errors too
    ob_clean();
    http_response_code(500);
    
    $db_error = isset($conn) ? mysqli_error($conn) : 'No database connection';
    $error_msg = $e->getMessage();
    $stack_trace = $e->getTraceAsString();
    
    error_log("Payroll save fatal error: " . $error_msg);
    error_log("Database error: " . $db_error);
    error_log("Stack trace: " . $stack_trace);
    
    echo json_encode([
        'success' => false,
        'message' => 'Fatal Error: ' . $error_msg,
        'error_type' => 'FatalError',
        'database_error' => $db_error,
        'stack_trace' => $stack_trace,
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
exit();