<?php
/**
 * Remove all faculty table references from the system
 * Replace with employees table
 */

echo "<h1>Removing All Faculty References</h1><hr>";

$files_to_fix = [
    'add-employee-comprehensive.php',
    'add-regularization.php',
    'approve-leave.php',
    'auto-increment-management.php',
    'auto-increment-processor.php',
    'create-increment-request.php',
    'create-leave-request.php',
    'delete-department.php',
    'export-leave-balances.php',
    'get-employees.php',
    'get-leave-allowance-details.php',
    'get-leave-details.php',
    'get-next-employee-id.php',
    'government-benefits.php',
    'initialize-leave-allowances.php',
    'leave-accumulation-history.php',
    'leave-allowance-management.php',
    'leave-balances.php',
    'leave-management.php',
    'manage-regularization.php',
    'process-regularization.php',
    'reports.php',
    'training-suggestions.php'
];

$replacements = [
    // Table references
    'FROM faculty ' => 'FROM employees ',
    'JOIN faculty ' => 'JOIN employees ',
    'LEFT JOIN faculty ' => 'LEFT JOIN employees ',
    'INNER JOIN faculty ' => 'INNER JOIN employees ',
    
    // Specific column references
    'faculty_id' => 'employee_id',
    'faculty_details' => 'employee_details',
    'faculty_leave_requests' => 'employee_leave_requests',
    'faculty_regularization' => 'employee_regularization',
    
    // Aliases
    'faculty f' => 'employees e',
    'faculty f1' => 'employees e1',
    'faculty f2' => 'employees e2',
    'faculty evaluator_f' => 'employees evaluator_e',
    'faculty evaluatee_f' => 'employees evaluatee_e',
    
    // WHERE clauses
    'WHERE f.id' => 'WHERE e.id',
    'f.first_name' => 'e.first_name',
    'f.last_name' => 'e.last_name',
    'f.email' => 'e.email',
    'f.department' => 'e.department',
    'f.qrcode' => 'e.employee_id',
    
    // fd alias for faculty_details
    'fd.faculty_id' => 'ed.employee_id',
    'fd.basic_salary' => 'ed.basic_salary',
];

$files_fixed = 0;

foreach ($files_to_fix as $file) {
    if (!file_exists($file)) {
        echo "⚠️ Skipped: $file (not found)<br>";
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Fixed: $file<br>";
        $files_fixed++;
    } else {
        echo "○ No changes: $file<br>";
    }
}

echo "<hr><h2>Summary</h2>";
echo "<p><strong>Files Fixed:</strong> $files_fixed</p>";
echo "<p><strong>Status:</strong> ✅ Faculty references replaced with employee references</p>";
echo "<p><a href='leave-reports.php'>Test Leave Reports</a> | <a href='index.php'>Dashboard</a></p>";
?>

