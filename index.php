<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';
require_once 'includes/roles.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager', 'human_resource', 'nurse', 'employee'])) {
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Redirect employees to their dedicated dashboard
if ($_SESSION['role'] === 'employee') {
    header('Location: employee-dashboard.php');
    exit();
}

// Set page title
$page_title = 'NIA-HRIS Dashboard';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get comprehensive HR statistics
$stats = [];

// Get total employees count
$employees_query = "SELECT COUNT(*) as total FROM employees WHERE is_active = 1";
$employees_result = mysqli_query($conn, $employees_query);
if ($employees_result) {
    $stats['total_employees'] = mysqli_fetch_assoc($employees_result)['total'];
} else {
    $stats['total_employees'] = 0;
}

// Get total users count
$users_query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$users_result = mysqli_query($conn, $users_query);
$stats['total_users'] = mysqli_fetch_assoc($users_result)['total'];

// Get total departments count
$departments_query = "SELECT COUNT(*) as total FROM departments WHERE is_active = 1";
$departments_result = mysqli_query($conn, $departments_query);
$stats['total_departments'] = mysqli_fetch_assoc($departments_result)['total'];

// Get pending leave requests count
$pending_leaves_query = "SELECT COUNT(*) as total FROM employee_leave_requests WHERE status = 'pending'";
$pending_leaves_result = mysqli_query($conn, $pending_leaves_query);
if ($pending_leaves_result) {
    $stats['pending_leaves'] = mysqli_fetch_assoc($pending_leaves_result)['total'];
} else {
    $stats['pending_leaves'] = 0;
}

// Get recent activities (last 5 activities)
$recent_activities = [];
$activities_query = "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5";
$activities_result = mysqli_query($conn, $activities_query);
if ($activities_result) {
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $recent_activities[] = $row;
    }
}

// ============================================
// COMPREHENSIVE ANALYTICS DATA QUERIES
// ============================================

$analytics = [];

// 1. Employee Demographics
// Employees by department
$dept_query = "SELECT e.department, COUNT(*) as count FROM employees e WHERE e.is_active = 1 GROUP BY e.department ORDER BY count DESC";
$dept_result = mysqli_query($conn, $dept_query);
$analytics['by_department'] = [];
while ($row = mysqli_fetch_assoc($dept_result)) {
    $analytics['by_department'][] = $row;
}

// Employees by position
$pos_query = "SELECT e.position, COUNT(*) as count FROM employees e WHERE e.is_active = 1 GROUP BY e.position ORDER BY count DESC LIMIT 10";
$pos_result = mysqli_query($conn, $pos_query);
$analytics['by_position'] = [];
while ($row = mysqli_fetch_assoc($pos_result)) {
    $analytics['by_position'][] = $row;
}

// Employees by employment type
$emp_type_query = "SELECT COALESCE(ed.employment_type, 'Not Set') as employment_type, COUNT(*) as count 
                   FROM employees e 
                   LEFT JOIN employee_details ed ON e.id = ed.employee_id 
                   LEFT JOIN employee_regularization er ON e.id = er.employee_id
                   LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                   WHERE e.is_active = 1 
                   GROUP BY COALESCE(COALESCE(rs.name, ed.employment_type), 'Not Set')";
$emp_type_result = mysqli_query($conn, $emp_type_query);
$analytics['by_employment_type'] = [];
while ($row = mysqli_fetch_assoc($emp_type_result)) {
    $analytics['by_employment_type'][] = $row;
}

// Employees by gender
$gender_query = "SELECT COALESCE(ed.gender, 'Not Specified') as gender, COUNT(*) as count 
                 FROM employees e 
                 LEFT JOIN employee_details ed ON e.id = ed.employee_id 
                 WHERE e.is_active = 1 
                 GROUP BY COALESCE(ed.gender, 'Not Specified')";
$gender_result = mysqli_query($conn, $gender_query);
$analytics['by_gender'] = [];
while ($row = mysqli_fetch_assoc($gender_result)) {
    $analytics['by_gender'][] = $row;
}

// Employees by age group
$age_query = "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                    WHEN TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                    WHEN TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                    WHEN TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
                    WHEN TIMESTAMPDIFF(YEAR, ed.date_of_birth, CURDATE()) > 55 THEN '55+'
                    ELSE 'Not Specified'
                END as age_group,
                COUNT(*) as count
              FROM employees e 
              LEFT JOIN employee_details ed ON e.id = ed.employee_id 
              WHERE e.is_active = 1 
              GROUP BY age_group";
$age_result = mysqli_query($conn, $age_query);
$analytics['by_age'] = [];
while ($row = mysqli_fetch_assoc($age_result)) {
    $analytics['by_age'][] = $row;
}

// Employees by educational attainment
$edu_query = "SELECT COALESCE(ed.highest_education, 'Not Specified') as education, COUNT(*) as count 
              FROM employees e 
              LEFT JOIN employee_details ed ON e.id = ed.employee_id 
              WHERE e.is_active = 1 
              GROUP BY COALESCE(ed.highest_education, 'Not Specified')";
$edu_result = mysqli_query($conn, $edu_query);
$analytics['by_education'] = [];
while ($row = mysqli_fetch_assoc($edu_result)) {
    $analytics['by_education'][] = $row;
}

// Employees by years of service
$tenure_query = "SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) < 1 THEN 'Less than 1 year'
                        WHEN TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) BETWEEN 1 AND 5 THEN '1-5 years'
                        WHEN TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) BETWEEN 6 AND 10 THEN '6-10 years'
                        WHEN TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) BETWEEN 11 AND 15 THEN '11-15 years'
                        WHEN TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE()) > 15 THEN '15+ years'
                    END as tenure_group,
                    COUNT(*) as count
                 FROM employees e 
                 WHERE e.is_active = 1 
                 GROUP BY tenure_group";
$tenure_result = mysqli_query($conn, $tenure_query);
$analytics['by_tenure'] = [];
while ($row = mysqli_fetch_assoc($tenure_result)) {
    $analytics['by_tenure'][] = $row;
}

// Employee growth trend (monthly headcount)
$growth_query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                  FROM employees 
                  WHERE is_active = 1 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
$growth_result = mysqli_query($conn, $growth_query);
$analytics['headcount_growth'] = [];
if ($growth_result) {
    while ($row = mysqli_fetch_assoc($growth_result)) {
        $analytics['headcount_growth'][] = $row;
    }
}

// 2. Recruitment and Hiring
$analytics['new_hires_this_month'] = 0;
$new_hires_query = "SELECT COUNT(*) as count FROM employees WHERE MONTH(hire_date) = MONTH(CURDATE()) AND YEAR(hire_date) = YEAR(CURDATE()) AND is_active = 1";
$new_hires_result = mysqli_query($conn, $new_hires_query);
if ($new_hires_result) {
    $analytics['new_hires_this_month'] = mysqli_fetch_assoc($new_hires_result)['count'];
}

// New hires by month (last 6 months)
$hires_trend_query = "SELECT 
                        DATE_FORMAT(hire_date, '%Y-%m') as month,
                        COUNT(*) as count
                      FROM employees 
                      WHERE is_active = 1 
                        AND hire_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(hire_date, '%Y-%m')
                      ORDER BY month DESC";
$hires_trend_result = mysqli_query($conn, $hires_trend_query);
$analytics['hiring_trend'] = [];
if ($hires_trend_result) {
    while ($row = mysqli_fetch_assoc($hires_trend_result)) {
        $analytics['hiring_trend'][] = $row;
    }
}

// Hires by department
$hires_dept_query = "SELECT d.name as department, COUNT(*) as count
                     FROM employees e
                     LEFT JOIN departments d ON e.department_id = d.id
                     WHERE e.is_active = 1 
                       AND YEAR(e.hire_date) = YEAR(CURDATE())
                     GROUP BY d.name
                     ORDER BY count DESC";
$hires_dept_result = mysqli_query($conn, $hires_dept_query);
$analytics['hires_by_dept'] = [];
if ($hires_dept_result) {
    while ($row = mysqli_fetch_assoc($hires_dept_result)) {
        $analytics['hires_by_dept'][] = $row;
    }
}

// Job postings/applicants (placeholder - tables may not exist)
$analytics['recruitment'] = [
    'total_applicants' => 0,
    'hired_vs_not_hired' => ['hired' => 0, 'not_hired' => 0],
    'applicants_by_source' => [],
    'job_openings_filled' => 0,
    'avg_hiring_time_days' => 0
];

// 3. Payroll and Compensation
$payroll_query = "SELECT AVG(es.current_salary) as avg_salary, d.name as department
                  FROM employee_salaries es
                  JOIN employees e ON es.employee_id = e.id
                  LEFT JOIN departments d ON e.department_id = d.id
                  WHERE e.is_active = 1
                  GROUP BY d.name";
$payroll_result = mysqli_query($conn, $payroll_query);
$analytics['avg_salary_by_dept'] = [];
if ($payroll_result) {
    while ($row = mysqli_fetch_assoc($payroll_result)) {
        $analytics['avg_salary_by_dept'][] = $row;
    }
}

// Total payroll expenses per month
$payroll_expense_query = "SELECT 
                            DATE_FORMAT(pp.start_date, '%Y-%m') as month,
                            SUM(pr.net_pay) as total_expense,
                            SUM(pr.gross_pay) as total_gross,
                            SUM(pr.total_deductions) as total_deductions
                          FROM payroll_records pr
                          JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
                          WHERE pr.status IN ('paid', 'approved')
                          GROUP BY DATE_FORMAT(pp.start_date, '%Y-%m')
                          ORDER BY month DESC
                          LIMIT 12";
$payroll_expense_result = mysqli_query($conn, $payroll_expense_query);
$analytics['payroll_expense_trend'] = [];
if ($payroll_expense_result) {
    while ($row = mysqli_fetch_assoc($payroll_expense_result)) {
        $analytics['payroll_expense_trend'][] = $row;
    }
}

// Salary increase trends
$salary_increase_query = "SELECT 
                            DATE_FORMAT(ir.created_at, '%Y-%m') as month,
                            COUNT(*) as increments_count,
                            AVG(ir.increment_amount) as avg_increment,
                            SUM(ir.increment_amount) as total_increment
                          FROM increment_requests ir
                          WHERE ir.status = 'approved'
                          GROUP BY DATE_FORMAT(ir.created_at, '%Y-%m')
                          ORDER BY month DESC
                          LIMIT 12";
$salary_increase_result = mysqli_query($conn, $salary_increase_query);
$analytics['salary_increase_trend'] = [];
if ($salary_increase_result) {
    while ($row = mysqli_fetch_assoc($salary_increase_result)) {
        $analytics['salary_increase_trend'][] = $row;
    }
}

// Overtime hours and pay
$overtime_query = "SELECT 
                     SUM(pr.overtime_hours) as total_overtime_hours,
                     SUM(pr.overtime_pay) as total_overtime_pay,
                     AVG(pr.overtime_hours) as avg_overtime_hours,
                     COUNT(DISTINCT pr.employee_id) as employees_with_overtime
                   FROM payroll_records pr
                   WHERE pr.status IN ('paid', 'approved')
                     AND pr.overtime_hours > 0";
$overtime_result = mysqli_query($conn, $overtime_query);
$analytics['overtime_stats'] = ['total_hours' => 0, 'total_pay' => 0, 'avg_hours' => 0, 'employees_count' => 0];
if ($overtime_result) {
    $overtime_row = mysqli_fetch_assoc($overtime_result);
    if ($overtime_row) {
        $analytics['overtime_stats'] = $overtime_row;
    }
}

// Deductions breakdown
$deductions_query = "SELECT 
                       SUM(pr.sss_contribution) as sss_total,
                       SUM(pr.philhealth_contribution) as philhealth_total,
                       SUM(pr.pagibig_contribution) as pagibig_total,
                       SUM(pr.withholding_tax) as tax_total,
                       SUM(pr.late_deduction) as late_total,
                       SUM(pr.undertime_deduction) as undertime_total,
                       SUM(pr.absences_deduction) as absences_total,
                       SUM(pr.other_deductions) as other_total
                     FROM payroll_records pr
                     WHERE pr.status IN ('paid', 'approved')";
$deductions_result = mysqli_query($conn, $deductions_query);
$analytics['deductions_breakdown'] = [];
if ($deductions_result) {
    $deductions_row = mysqli_fetch_assoc($deductions_result);
    if ($deductions_row) {
        $analytics['deductions_breakdown'] = $deductions_row;
    }
}

// Allowances and bonuses breakdown
$allowances_bonuses_query = "SELECT 
                               SUM(pr.allowances) as total_allowances,
                               SUM(pr.bonuses) as total_bonuses,
                               SUM(pr.other_earnings) as total_other_earnings,
                               AVG(pr.allowances) as avg_allowances,
                               AVG(pr.bonuses) as avg_bonuses,
                               COUNT(DISTINCT CASE WHEN pr.allowances > 0 THEN pr.employee_id END) as employees_with_allowances,
                               COUNT(DISTINCT CASE WHEN pr.bonuses > 0 THEN pr.employee_id END) as employees_with_bonuses
                             FROM payroll_records pr
                             WHERE pr.status IN ('paid', 'approved')";
$allowances_bonuses_result = mysqli_query($conn, $allowances_bonuses_query);
$analytics['allowances_bonuses'] = [];
if ($allowances_bonuses_result) {
    $ab_row = mysqli_fetch_assoc($allowances_bonuses_result);
    if ($ab_row) {
        $analytics['allowances_bonuses'] = $ab_row;
    }
}

// 4. Attendance and Leave
$leave_type_query = "SELECT lt.name as leave_type, COUNT(*) as count 
                     FROM employee_leave_requests elr
                     JOIN leave_types lt ON elr.leave_type_id = lt.id
                     WHERE elr.status = 'approved'
                     GROUP BY lt.name
                     ORDER BY count DESC
                     LIMIT 5";
$leave_type_result = mysqli_query($conn, $leave_type_query);
$analytics['leaves_by_type'] = [];
if ($leave_type_result) {
    while ($row = mysqli_fetch_assoc($leave_type_result)) {
        $analytics['leaves_by_type'][] = $row;
    }
}

$approved_leaves = 0;
$denied_leaves = 0;
$leave_status_query = "SELECT status, COUNT(*) as count FROM employee_leave_requests GROUP BY status";
$leave_status_result = mysqli_query($conn, $leave_status_query);
if ($leave_status_result) {
    while ($row = mysqli_fetch_assoc($leave_status_result)) {
        if ($row['status'] === 'approved') $approved_leaves = $row['count'];
        if ($row['status'] === 'denied') $denied_leaves = $row['count'];
    }
}
$analytics['leave_approval_rate'] = ['approved' => $approved_leaves, 'denied' => $denied_leaves];

// Total absences per month
$absences_query = "SELECT 
                     DATE_FORMAT(elr.start_date, '%Y-%m') as month,
                     COUNT(*) as total_absences,
                     SUM(DATEDIFF(elr.end_date, elr.start_date) + 1) as total_days
                   FROM employee_leave_requests elr
                   WHERE elr.status = 'approved'
                   GROUP BY DATE_FORMAT(elr.start_date, '%Y-%m')
                   ORDER BY month DESC
                   LIMIT 12";
$absences_result = mysqli_query($conn, $absences_query);
$analytics['absences_by_month'] = [];
if ($absences_result) {
    while ($row = mysqli_fetch_assoc($absences_result)) {
        $analytics['absences_by_month'][] = $row;
    }
}

// Late arrivals and undertime occurrences
$late_undertime_query = "SELECT 
                           SUM(CASE WHEN pr.late_deduction > 0 THEN 1 ELSE 0 END) as late_occurrences,
                           SUM(pr.late_deduction) as total_late_deduction,
                           SUM(CASE WHEN pr.undertime_deduction > 0 THEN 1 ELSE 0 END) as undertime_occurrences,
                           SUM(pr.undertime_deduction) as total_undertime_deduction,
                           COUNT(DISTINCT CASE WHEN pr.late_deduction > 0 THEN pr.employee_id END) as employees_with_late,
                           COUNT(DISTINCT CASE WHEN pr.undertime_deduction > 0 THEN pr.employee_id END) as employees_with_undertime
                         FROM payroll_records pr
                         WHERE pr.status IN ('paid', 'approved')";
$late_undertime_result = mysqli_query($conn, $late_undertime_query);
$analytics['late_undertime_stats'] = [];
if ($late_undertime_result) {
    $late_undertime_row = mysqli_fetch_assoc($late_undertime_result);
    if ($late_undertime_row) {
        $analytics['late_undertime_stats'] = $late_undertime_row;
    }
}

// 5. Performance Evaluation
$perf_query = "SELECT AVG(pr.overall_rating) as avg_rating, d.name as department
               FROM performance_reviews pr
               JOIN employees e ON pr.employee_id = e.id
               LEFT JOIN departments d ON e.department_id = d.id
               WHERE pr.status = 'completed'
               GROUP BY d.name
               ORDER BY avg_rating DESC";
$perf_result = mysqli_query($conn, $perf_query);
$analytics['performance_by_dept'] = [];
if ($perf_result) {
    while ($row = mysqli_fetch_assoc($perf_result)) {
        $analytics['performance_by_dept'][] = $row;
    }
}

// Top performing employees
$top_performers_query = "SELECT 
                           e.id,
                           CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                           d.name as department,
                           pr.overall_rating,
                           pr.review_period_end as review_date
                         FROM performance_reviews pr
                         JOIN employees e ON pr.employee_id = e.id
                         LEFT JOIN departments d ON e.department_id = d.id
                         WHERE pr.status = 'completed'
                           AND e.is_active = 1
                         ORDER BY pr.overall_rating DESC
                         LIMIT 10";
$top_performers_result = mysqli_query($conn, $top_performers_query);
$analytics['top_performers'] = [];
if ($top_performers_result) {
    while ($row = mysqli_fetch_assoc($top_performers_result)) {
        $analytics['top_performers'][] = $row;
    }
}

// Performance trend over time
$perf_trend_query = "SELECT 
                       DATE_FORMAT(pr.review_period_end, '%Y-%m') as month,
                       AVG(pr.overall_rating) as avg_rating,
                       COUNT(*) as reviews_count
                     FROM performance_reviews pr
                     WHERE pr.status = 'completed'
                     GROUP BY DATE_FORMAT(pr.review_period_end, '%Y-%m')
                     ORDER BY month DESC
                     LIMIT 12";
$perf_trend_result = mysqli_query($conn, $perf_trend_query);
$analytics['performance_trend'] = [];
if ($perf_trend_result) {
    while ($row = mysqli_fetch_assoc($perf_trend_result)) {
        $analytics['performance_trend'][] = $row;
    }
}

// 6. Training and Development
$training_query = "SELECT COUNT(DISTINCT tr.user_id) as employees_trained, COUNT(tr.id) as total_trainings
                   FROM training_registrations tr
                   JOIN trainings_seminars ts ON tr.training_id = ts.id
                   WHERE tr.status = 'completed'";
$training_result = mysqli_query($conn, $training_query);
$analytics['training_stats'] = ['employees_trained' => 0, 'total_trainings' => 0];
if ($training_result) {
    $analytics['training_stats'] = mysqli_fetch_assoc($training_result);
}

// Training completion rate
$training_completion_query = "SELECT 
                                 COUNT(*) as total_registrations,
                                 SUM(CASE WHEN tr.status = 'completed' THEN 1 ELSE 0 END) as completed,
                                 SUM(CASE WHEN tr.status = 'registered' THEN 1 ELSE 0 END) as registered,
                                 SUM(CASE WHEN tr.status = 'no_show' THEN 1 ELSE 0 END) as no_show
                               FROM training_registrations tr";
$training_completion_result = mysqli_query($conn, $training_completion_query);
$analytics['training_completion_rate'] = [];
if ($training_completion_result) {
    $completion_row = mysqli_fetch_assoc($training_completion_result);
    if ($completion_row) {
        $analytics['training_completion_rate'] = $completion_row;
    }
}

// Training attendance by department (if users are linked to employees)
$training_dept_query = "SELECT 
                          d.name as department,
                          COUNT(DISTINCT tr.user_id) as employees_trained,
                          COUNT(tr.id) as total_registrations
                        FROM training_registrations tr
                        JOIN trainings_seminars ts ON tr.training_id = ts.id
                        JOIN users u ON tr.user_id = u.id
                        LEFT JOIN employees e ON u.email = e.email
                        LEFT JOIN departments d ON e.department_id = d.id
                        WHERE tr.status = 'completed'
                        GROUP BY d.name
                        ORDER BY employees_trained DESC";
$training_dept_result = mysqli_query($conn, $training_dept_query);
$analytics['training_by_dept'] = [];
if ($training_dept_result) {
    while ($row = mysqli_fetch_assoc($training_dept_result)) {
        $analytics['training_by_dept'][] = $row;
    }
}

// 7. Employee Satisfaction / Feedback (Placeholder - tables may not exist)
$analytics['satisfaction'] = [
    'avg_satisfaction_score' => 0,
    'engagement_score' => 0,
    'satisfaction_by_dept' => [],
    'engagement_trend' => [],
    'feedback_categories' => []
];

// 8. Resignation / Turnover
$resignations_this_month = 0;
$resignation_query = "SELECT COUNT(*) as count FROM employees 
                      WHERE is_active = 0 
                      AND MONTH(updated_at) = MONTH(CURDATE()) 
                      AND YEAR(updated_at) = YEAR(CURDATE())";
$resignation_result = mysqli_query($conn, $resignation_query);
if ($resignation_result) {
    $resignations_this_month = mysqli_fetch_assoc($resignation_result)['count'];
}
$analytics['resignations_this_month'] = $resignations_this_month;

// Turnover rate calculation
$total_active = $stats['total_employees'];
$total_inactive_query = "SELECT COUNT(*) as count FROM employees WHERE is_active = 0";
$total_inactive_result = mysqli_query($conn, $total_inactive_query);
$total_inactive = 0;
if ($total_inactive_result) {
    $total_inactive = mysqli_fetch_assoc($total_inactive_result)['count'];
}
$total_employees_all_time = $total_active + $total_inactive;
$turnover_rate = $total_employees_all_time > 0 ? ($total_inactive / $total_employees_all_time) * 100 : 0;
$analytics['turnover_rate'] = round($turnover_rate, 2);

// Resignations trend by month
$resignation_trend_query = "SELECT 
                              DATE_FORMAT(updated_at, '%Y-%m') as month,
                              COUNT(*) as count
                            FROM employees 
                            WHERE is_active = 0 
                              AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                            GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                            ORDER BY month DESC";
$resignation_trend_result = mysqli_query($conn, $resignation_trend_query);
$analytics['resignation_trend'] = [];
if ($resignation_trend_result) {
    while ($row = mysqli_fetch_assoc($resignation_trend_result)) {
        $analytics['resignation_trend'][] = $row;
    }
}

// Average tenure before resignation
$avg_tenure_resignation_query = "SELECT 
                                   AVG(TIMESTAMPDIFF(YEAR, hire_date, updated_at)) as avg_years,
                                   AVG(TIMESTAMPDIFF(MONTH, hire_date, updated_at)) as avg_months
                                 FROM employees 
                                 WHERE is_active = 0 
                                   AND hire_date IS NOT NULL";
$avg_tenure_result = mysqli_query($conn, $avg_tenure_resignation_query);
$analytics['avg_tenure_before_resignation'] = ['avg_years' => 0, 'avg_months' => 0];
if ($avg_tenure_result) {
    $tenure_row = mysqli_fetch_assoc($avg_tenure_result);
    if ($tenure_row) {
        $analytics['avg_tenure_before_resignation'] = $tenure_row;
    }
}

// 9. Compliance and Benefits
$sss_complete = 0;
$philhealth_complete = 0;
$pagibig_complete = 0;
$compliance_query = "SELECT 
                        SUM(CASE WHEN e.sss_number IS NOT NULL AND e.sss_number != '' THEN 1 ELSE 0 END) as sss,
                        SUM(CASE WHEN e.philhealth_number IS NOT NULL AND e.philhealth_number != '' THEN 1 ELSE 0 END) as philhealth,
                        SUM(CASE WHEN e.pagibig_number IS NOT NULL AND e.pagibig_number != '' THEN 1 ELSE 0 END) as pagibig,
                        SUM(CASE WHEN e.tin_number IS NOT NULL AND e.tin_number != '' THEN 1 ELSE 0 END) as tin,
                        COUNT(*) as total
                     FROM employees e 
                     WHERE e.is_active = 1";
$compliance_result = mysqli_query($conn, $compliance_query);
if ($compliance_result) {
    $comp_row = mysqli_fetch_assoc($compliance_result);
    $analytics['compliance'] = [
        'sss' => $comp_row['sss'],
        'philhealth' => $comp_row['philhealth'],
        'pagibig' => $comp_row['pagibig'],
        'tin' => $comp_row['tin'],
        'total' => $comp_row['total']
    ];
    // Calculate compliance rate
    $total = $comp_row['total'];
    if ($total > 0) {
        $complete_compliance = 0;
        $complete_query = "SELECT COUNT(*) as count 
                          FROM employees e 
                          WHERE e.is_active = 1 
                            AND e.sss_number IS NOT NULL AND e.sss_number != ''
                            AND e.philhealth_number IS NOT NULL AND e.philhealth_number != ''
                            AND e.pagibig_number IS NOT NULL AND e.pagibig_number != ''
                            AND e.tin_number IS NOT NULL AND e.tin_number != ''";
        $complete_result = mysqli_query($conn, $complete_query);
        if ($complete_result) {
            $complete_row = mysqli_fetch_assoc($complete_result);
            $complete_compliance = $complete_row['count'];
        }
        $analytics['compliance']['complete_count'] = $complete_compliance;
        $analytics['compliance']['compliance_rate'] = round(($complete_compliance / $total) * 100, 2);
    }
}

// Compliance by department
$compliance_dept_query = "SELECT 
                             d.name as department,
                             COUNT(*) as total,
                             SUM(CASE WHEN e.sss_number IS NOT NULL AND e.sss_number != '' THEN 1 ELSE 0 END) as sss,
                             SUM(CASE WHEN e.philhealth_number IS NOT NULL AND e.philhealth_number != '' THEN 1 ELSE 0 END) as philhealth,
                             SUM(CASE WHEN e.pagibig_number IS NOT NULL AND e.pagibig_number != '' THEN 1 ELSE 0 END) as pagibig
                           FROM employees e
                           LEFT JOIN departments d ON e.department_id = d.id
                           WHERE e.is_active = 1
                           GROUP BY d.name
                           ORDER BY total DESC";
$compliance_dept_result = mysqli_query($conn, $compliance_dept_query);
$analytics['compliance_by_dept'] = [];
if ($compliance_dept_result) {
    while ($row = mysqli_fetch_assoc($compliance_dept_result)) {
        $analytics['compliance_by_dept'][] = $row;
    }
}

// Current month's payroll total
$current_month_payroll_query = "SELECT 
                                   SUM(pr.net_pay) as total_payroll,
                                   SUM(pr.gross_pay) as total_gross,
                                   SUM(pr.total_deductions) as total_deductions,
                                   COUNT(DISTINCT pr.employee_id) as employees_paid
                                 FROM payroll_records pr
                                 JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
                                 WHERE pr.status IN ('paid', 'approved')
                                   AND MONTH(pp.start_date) = MONTH(CURDATE())
                                   AND YEAR(pp.start_date) = YEAR(CURDATE())";
$current_month_payroll_result = mysqli_query($conn, $current_month_payroll_query);
$current_month_payroll = ['total_payroll' => 0, 'total_gross' => 0, 'total_deductions' => 0, 'employees_paid' => 0];
if ($current_month_payroll_result) {
    $payroll_row = mysqli_fetch_assoc($current_month_payroll_result);
    if ($payroll_row) {
        $current_month_payroll = $payroll_row;
    }
}

// Average attendance rate (based on leave requests and absences)
$total_workdays_this_month = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
$attendance_query = "SELECT 
                       COUNT(DISTINCT elr.employee_id) as employees_with_leave,
                       SUM(DATEDIFF(elr.end_date, elr.start_date) + 1) as total_leave_days
                     FROM employee_leave_requests elr
                     WHERE elr.status = 'approved'
                       AND MONTH(elr.start_date) = MONTH(CURDATE())
                       AND YEAR(elr.start_date) = YEAR(CURDATE())";
$attendance_result = mysqli_query($conn, $attendance_query);
$attendance_data = ['employees_with_leave' => 0, 'total_leave_days' => 0];
if ($attendance_result) {
    $attendance_data = mysqli_fetch_assoc($attendance_result);
}
$total_employee_days = $stats['total_employees'] * $total_workdays_this_month;
$total_attended_days = $total_employee_days - ($attendance_data['total_leave_days'] ?? 0);
$avg_attendance_rate = $total_employee_days > 0 ? ($total_attended_days / $total_employee_days) * 100 : 100;

// System Summary
$analytics['system_summary'] = [
    'total_employees' => $stats['total_employees'],
    'active_employees' => $stats['total_employees'],
    'inactive_employees' => 0,
    'new_hires_this_month' => $analytics['new_hires_this_month'],
    'resignations_this_month' => $resignations_this_month,
    'current_month_payroll' => $current_month_payroll['total_payroll'],
    'current_month_gross' => $current_month_payroll['total_gross'],
    'current_month_deductions' => $current_month_payroll['total_deductions'],
    'employees_paid_this_month' => $current_month_payroll['employees_paid'],
    'avg_attendance_rate' => round($avg_attendance_rate, 2),
    'turnover_rate' => $analytics['turnover_rate'] ?? 0,
    'compliance_rate' => $analytics['compliance']['compliance_rate'] ?? 0
];

$inactive_query = "SELECT COUNT(*) as count FROM employees WHERE is_active = 0";
$inactive_result = mysqli_query($conn, $inactive_query);
if ($inactive_result) {
    $analytics['system_summary']['inactive_employees'] = mysqli_fetch_assoc($inactive_result)['count'];
}

// Include the header
include 'includes/header.php';
?>

    <!-- Page Header -->
    <div class="mb-6">
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">
                        <i class="fas fa-home mr-2"></i>NIA-HRIS Dashboard
                    </h2>
                    <p class="opacity-90">Welcome back, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>! • Last Login: <?php echo date('M d, Y H:i'); ?></p>
                </div>
                <div>
                    <?php echo getRoleBadge(getCurrentUserRole()); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-user-tie text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Employees</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_employees']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Departments</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_departments']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">System Users</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Leaves</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_leaves']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="admin-employee.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <i class="fas fa-user-plus text-blue-600 text-xl mr-3"></i>
                    <span class="text-blue-800 font-medium">Add Employee</span>
                </a>
                <a href="manage-departments.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-building text-green-600 text-xl mr-3"></i>
                    <span class="text-green-800 font-medium">Manage Departments</span>
                </a>
                <a href="leave-management.php" class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                    <i class="fas fa-calendar-alt text-orange-600 text-xl mr-3"></i>
                    <span class="text-orange-800 font-medium">Leave Management</span>
                </a>
                <a href="salary-structures.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <i class="fas fa-money-bill-wave text-purple-600 text-xl mr-3"></i>
                    <span class="text-purple-800 font-medium">Salary Structures</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
            <div class="space-y-3">
                <?php if (empty($recent_activities)): ?>
                    <p class="text-gray-500 text-sm">No recent activities</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Employees by Department Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-building text-purple-600 mr-2"></i>Employees by Department
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>

        <!-- Employees by Gender Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-venus-mars text-pink-600 mr-2"></i>Employees by Gender
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="genderChart"></canvas>
            </div>
        </div>

        <!-- Employees by Employment Type Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-briefcase text-blue-600 mr-2"></i>Employment Type Distribution
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="employmentTypeChart"></canvas>
            </div>
        </div>

        <!-- Employee Growth Trend Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-chart-line text-green-600 mr-2"></i>Employee Growth Trend
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="growthChart"></canvas>
            </div>
        </div>

        <!-- Payroll Expense Trend Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-money-bill-wave text-yellow-600 mr-2"></i>Payroll Expense Trend
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="payrollChart"></canvas>
            </div>
        </div>

        <!-- Leave Types Distribution Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-calendar-alt text-orange-600 mr-2"></i>Leave Types Distribution
            </h3>
            <div class="chart-container" style="position: relative; height: 300px;">
                <canvas id="leaveTypeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Performing Employees Table -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-star text-yellow-500 mr-2"></i>Top Performers
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($analytics['top_performers'])): ?>
                            <?php foreach (array_slice($analytics['top_performers'], 0, 5) as $performer): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($performer['employee_name']); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo htmlspecialchars($performer['department'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <?php echo number_format($performer['overall_rating'], 2); ?> / 5.0
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                                    No performance reviews available
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Compliance Summary -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-shield-alt text-green-600 mr-2"></i>Compliance Summary
            </h3>
            <?php if (!empty($analytics['compliance'])): ?>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">SSS</span>
                            <span class="font-semibold text-gray-900">
                                <?php echo $analytics['compliance']['sss']; ?> / <?php echo $analytics['compliance']['total']; ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($analytics['compliance']['total'] > 0) ? ($analytics['compliance']['sss'] / $analytics['compliance']['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">PhilHealth</span>
                            <span class="font-semibold text-gray-900">
                                <?php echo $analytics['compliance']['philhealth']; ?> / <?php echo $analytics['compliance']['total']; ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($analytics['compliance']['total'] > 0) ? ($analytics['compliance']['philhealth'] / $analytics['compliance']['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Pag-IBIG</span>
                            <span class="font-semibold text-gray-900">
                                <?php echo $analytics['compliance']['pagibig']; ?> / <?php echo $analytics['compliance']['total']; ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo ($analytics['compliance']['total'] > 0) ? ($analytics['compliance']['pagibig'] / $analytics['compliance']['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">TIN</span>
                            <span class="font-semibold text-gray-900">
                                <?php echo $analytics['compliance']['tin']; ?> / <?php echo $analytics['compliance']['total']; ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-600 h-2 rounded-full" style="width: <?php echo ($analytics['compliance']['total'] > 0) ? ($analytics['compliance']['tin'] / $analytics['compliance']['total'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <?php if (isset($analytics['compliance']['complete_count'])): ?>
                        <div class="pt-2 border-t border-gray-200">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-semibold text-gray-900">Fully Compliant</span>
                                <span class="font-bold text-green-600">
                                    <?php echo number_format($analytics['compliance']['compliance_rate'], 1); ?>%
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm text-center py-8">No compliance data available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color palettes
    const colors = {
        primary: ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'],
        gradient: ['rgba(16, 185, 129, 0.1)', 'rgba(59, 130, 246, 0.1)', 'rgba(139, 92, 246, 0.1)', 'rgba(245, 158, 11, 0.1)']
    };

    // Employees by Department Chart
    const deptData = <?php echo json_encode($analytics['by_department']); ?>;
    if (document.getElementById('departmentChart')) {
        new Chart(document.getElementById('departmentChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: deptData.map(item => item.department),
                datasets: [{
                    label: 'Employees',
                    data: deptData.map(item => item.count),
                    backgroundColor: colors.primary,
                    borderColor: colors.primary.map(color => color.replace('0.1', '1')),
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Employees by Gender Chart
    const genderData = <?php echo json_encode($analytics['by_gender']); ?>;
    if (document.getElementById('genderChart')) {
        new Chart(document.getElementById('genderChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: genderData.map(item => item.gender),
                datasets: [{
                    data: genderData.map(item => item.count),
                    backgroundColor: ['#EC4899', '#06B6D4', '#8B5CF6'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    }

    // Employment Type Chart
    const empTypeData = <?php echo json_encode($analytics['by_employment_type']); ?>;
    if (document.getElementById('employmentTypeChart')) {
        new Chart(document.getElementById('employmentTypeChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: empTypeData.map(item => item.employment_type),
                datasets: [{
                    data: empTypeData.map(item => item.count),
                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    }

    // Employee Growth Trend Chart
    const growthData = <?php echo json_encode($analytics['headcount_growth']); ?>;
    if (document.getElementById('growthChart')) {
        new Chart(document.getElementById('growthChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: growthData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Headcount',
                    data: growthData.map(item => item.count),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10B981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Payroll Expense Trend Chart
    const payrollData = <?php echo json_encode($analytics['payroll_expense_trend']); ?>;
    if (document.getElementById('payrollChart') && payrollData.length > 0) {
        new Chart(document.getElementById('payrollChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: payrollData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Total Payroll (₱)',
                    data: payrollData.map(item => parseFloat(item.total_expense || 0)),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#F59E0B',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + parseFloat(context.parsed.y).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + parseFloat(value).toLocaleString('en-US');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Leave Types Distribution Chart
    const leaveTypeData = <?php echo json_encode($analytics['leaves_by_type']); ?>;
    if (document.getElementById('leaveTypeChart') && leaveTypeData.length > 0) {
        new Chart(document.getElementById('leaveTypeChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: leaveTypeData.map(item => item.leave_type),
                datasets: [{
                    data: leaveTypeData.map(item => item.count),
                    backgroundColor: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>

