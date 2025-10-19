<?php
/**
 * Seed Data: Philippine Salary Structures
 * Based on Philippine Government Salary Standardization Law (SSL) and typical government agency structures
 * Includes positions for Staff, Admin, and Nurse employee types
 */

require_once 'config/database.php';

// Check if user is logged in (optional, for security)
session_start();
$created_by = $_SESSION['user_id'] ?? 1; // Default to user ID 1 if not set

echo "<!DOCTYPE html><html><head><title>Seed Salary Structures</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 30px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    h2 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; margin-top: 0; }
    h3 { color: #555; background: linear-gradient(to right, #f0f8ff, #fff); padding: 12px; border-left: 4px solid #2196F3; margin-top: 25px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #f44336; font-weight: bold; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    th { background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 12px; text-align: left; position: sticky; top: 0; }
    td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
    tr:hover { background: #f5f9ff; }
    tr:nth-child(even) { background: #f9f9f9; }
    .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 0 0; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
    .btn:hover { background: #45a049; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4); }
    .btn-blue { background: #2196F3; } .btn-blue:hover { background: #0b7dda; }
    .btn-orange { background: #FF9800; } .btn-orange:hover { background: #e68900; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
    .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
    .stat-card h4 { margin: 0 0 10px 0; font-size: 14px; opacity: 0.9; }
    .stat-card .number { font-size: 32px; font-weight: bold; }
</style></head><body><div class='container'>";

echo "<h2>🏢 Philippine Salary Structures - Seed Data</h2>";
echo "<div class='info'>
    <strong>📋 Based on:</strong> Philippine Government Salary Standardization Law (SSL) and NIA salary structure<br>
    <strong>💼 Employee Types:</strong> Staff, Admin, Nurse<br>
    <strong>📅 Reference Year:</strong> 2024
</div>";

// Clear existing data (with foreign key handling)
echo "<h3>Step 1: Clearing Existing Data</h3>";

// Disable foreign key checks temporarily
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Clear salary structures
$clear = mysqli_query($conn, "TRUNCATE TABLE salary_structures");
if ($clear) {
    echo "<span class='success'>✓ Existing salary structures cleared</span><br>";
} else {
    echo "<span class='error'>✗ Error clearing table: " . mysqli_error($conn) . "</span><br>";
    // If truncate fails, try delete
    $delete = mysqli_query($conn, "DELETE FROM salary_structures");
    if ($delete) {
        echo "<span class='success'>✓ Existing records deleted instead</span><br>";
        // Reset auto increment
        mysqli_query($conn, "ALTER TABLE salary_structures AUTO_INCREMENT = 1");
    }
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
echo "<br>";

// Philippine Government Salary Structures based on SSL 2024
// Salary ranges are in Philippine Peso (PHP)
$salary_structures = [
    // ADMINISTRATIVE/OFFICE STAFF POSITIONS
    ['Administrative Aide I', 'Administration', 'SG-1', 13000.00, 13000.00, 15000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 390.00],
    ['Administrative Aide II', 'Administration', 'SG-2', 13572.00, 13572.00, 16000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 407.16],
    ['Administrative Aide III', 'Administration', 'SG-3', 14159.00, 14159.00, 17000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 424.77],
    ['Administrative Assistant I', 'Administration', 'SG-4', 14762.00, 14762.00, 18500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 442.86],
    ['Administrative Assistant II', 'Administration', 'SG-5', 15380.00, 15380.00, 20000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 461.40],
    ['Administrative Assistant III', 'Administration', 'SG-6', 16019.00, 16019.00, 21500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 480.57],
    ['Administrative Officer I', 'Administration', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['Administrative Officer II', 'Administration', 'SG-9', 18426.00, 18426.00, 26000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 552.78],
    ['Administrative Officer III', 'Administration', 'SG-11', 20179.00, 20179.00, 30000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['Administrative Officer IV', 'Administration', 'SG-12', 21024.00, 21024.00, 32000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 630.72],
    ['Administrative Officer V', 'Administration', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    
    // HR AND RECORDS STAFF
    ['Records Officer I', 'Human Resources', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['Records Officer II', 'Human Resources', 'SG-9', 18426.00, 18426.00, 26000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 552.78],
    ['HR Assistant I', 'Human Resources', 'SG-6', 16019.00, 16019.00, 21500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 480.57],
    ['HR Assistant II', 'Human Resources', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['HR Officer I', 'Human Resources', 'SG-11', 20179.00, 20179.00, 30000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['HR Officer II', 'Human Resources', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    ['HR Officer III', 'Human Resources', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    
    // ACCOUNTING AND FINANCE STAFF
    ['Accounting Clerk I', 'Finance', 'SG-4', 14762.00, 14762.00, 18500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 442.86],
    ['Accounting Clerk II', 'Finance', 'SG-5', 15380.00, 15380.00, 20000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 461.40],
    ['Bookkeeper I', 'Finance', 'SG-6', 16019.00, 16019.00, 21500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 480.57],
    ['Bookkeeper II', 'Finance', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['Accountant I', 'Finance', 'SG-11', 20179.00, 20179.00, 30000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['Accountant II', 'Finance', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    ['Accountant III', 'Finance', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Budget Officer I', 'Finance', 'SG-11', 20179.00, 20179.00, 30000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['Budget Officer II', 'Finance', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    
    // NURSING POSITIONS
    ['Nurse I', 'Medical Services', 'SG-11', 20179.00, 20179.00, 32000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['Nurse II', 'Medical Services', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    ['Nurse III', 'Medical Services', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Nurse IV', 'Medical Services', 'SG-17', 27000.00, 27000.00, 45000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 810.00],
    ['Senior Nurse', 'Medical Services', 'SG-18', 28164.00, 28164.00, 48000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 844.92],
    ['Chief Nurse', 'Medical Services', 'SG-19', 29359.00, 29359.00, 52000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 880.77],
    ['Public Health Nurse I', 'Medical Services', 'SG-11', 20179.00, 20179.00, 32000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['Public Health Nurse II', 'Medical Services', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    
    // IT AND TECHNICAL STAFF
    ['Computer Operator I', 'Information Technology', 'SG-6', 16019.00, 16019.00, 21500.00, 3.00, 3, 'Step Increment', 'Annual step increment', 480.57],
    ['Computer Operator II', 'Information Technology', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['IT Assistant', 'Information Technology', 'SG-9', 18426.00, 18426.00, 26000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 552.78],
    ['IT Specialist I', 'Information Technology', 'SG-11', 20179.00, 20179.00, 30000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 605.37],
    ['IT Specialist II', 'Information Technology', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    ['IT Specialist III', 'Information Technology', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Systems Analyst I', 'Information Technology', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Systems Analyst II', 'Information Technology', 'SG-17', 27000.00, 27000.00, 45000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 810.00],
    
    // SUPERVISORY AND MANAGEMENT
    ['Supervising Administrative Officer', 'Administration', 'SG-15', 24316.00, 24316.00, 42000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Chief Administrative Officer', 'Administration', 'SG-18', 28164.00, 28164.00, 52000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 844.92],
    ['Division Chief', 'Administration', 'SG-22', 43415.00, 43415.00, 75000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1302.45],
    ['Assistant Department Manager', 'Administration', 'SG-24', 54251.00, 54251.00, 95000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1627.53],
    ['Department Manager', 'Administration', 'SG-25', 60021.00, 60021.00, 110000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1800.63],
    
    // LEGAL AND COMPLIANCE
    ['Legal Assistant', 'Legal', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['Attorney I', 'Legal', 'SG-18', 28164.00, 28164.00, 50000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 844.92],
    ['Attorney II', 'Legal', 'SG-19', 29359.00, 29359.00, 55000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 880.77],
    ['Attorney III', 'Legal', 'SG-20', 30590.00, 30590.00, 60000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 917.70],
    ['Attorney IV', 'Legal', 'SG-22', 43415.00, 43415.00, 80000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1302.45],
    
    // ENGINEERING (if applicable)
    ['Engineering Assistant', 'Engineering', 'SG-8', 17679.00, 17679.00, 24000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 530.37],
    ['Engineer I', 'Engineering', 'SG-13', 21901.00, 21901.00, 35000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 657.03],
    ['Engineer II', 'Engineering', 'SG-15', 24316.00, 24316.00, 40000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 729.48],
    ['Engineer III', 'Engineering', 'SG-17', 27000.00, 27000.00, 45000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 810.00],
    ['Engineer IV', 'Engineering', 'SG-19', 29359.00, 29359.00, 52000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 880.77],
    ['Senior Engineer', 'Engineering', 'SG-22', 43415.00, 43415.00, 75000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1302.45],
    
    // EXECUTIVE POSITIONS
    ['Executive Assistant I', 'Executive Office', 'SG-18', 28164.00, 28164.00, 50000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 844.92],
    ['Executive Assistant II', 'Executive Office', 'SG-20', 30590.00, 30590.00, 55000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 917.70],
    ['Assistant Director', 'Executive Office', 'SG-26', 66374.00, 66374.00, 125000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 1991.22],
    ['Director', 'Executive Office', 'SG-27', 73402.00, 73402.00, 150000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 2202.06],
    ['Assistant Administrator', 'Executive Office', 'SG-29', 88611.00, 88611.00, 180000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 2658.33],
    ['Administrator', 'Executive Office', 'SG-30', 98087.00, 98087.00, 200000.00, 3.00, 3, 'Step Increment', 'Annual step increment', 2942.61],
];

// Insert salary structures
echo "<h3>Step 2: Inserting Salary Structures</h3>";
echo "<table>";
echo "<tr><th>#</th><th>Position</th><th>Department</th><th>Grade</th><th>Base Salary</th><th>Max Salary</th><th>Status</th></tr>";

$count = 0;
$insert_query = "INSERT INTO salary_structures 
    (position_title, department, grade_level, base_salary, minimum_salary, maximum_salary, 
     increment_percentage, incrementation_frequency_years, incrementation_name, 
     incrementation_description, incrementation_amount, is_active, created_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

$stmt = mysqli_prepare($conn, $insert_query);

foreach ($salary_structures as $index => $structure) {
    list($position, $dept, $grade, $base, $min, $max, $inc_pct, $inc_freq, $inc_name, $inc_desc, $inc_amt) = $structure;
    
    mysqli_stmt_bind_param($stmt, "sssddddiisdi", 
        $position, $dept, $grade, $base, $min, $max, 
        $inc_pct, $inc_freq, $inc_name, $inc_desc, $inc_amt, $created_by
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $count++;
        $status = "<span class='success'>✓</span>";
    } else {
        $status = "<span class='error'>✗</span>";
    }
    
    echo "<tr>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>{$position}</td>";
    echo "<td>{$dept}</td>";
    echo "<td>{$grade}</td>";
    echo "<td>₱" . number_format($base, 2) . "</td>";
    echo "<td>₱" . number_format($max, 2) . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "</table>";

mysqli_stmt_close($stmt);

// Statistics
echo "<h3>Step 3: Summary Statistics</h3>";

$stats = [
    'total' => mysqli_query($conn, "SELECT COUNT(*) as count FROM salary_structures")->fetch_assoc()['count'],
    'departments' => mysqli_query($conn, "SELECT COUNT(DISTINCT department) as count FROM salary_structures")->fetch_assoc()['count'],
    'min_salary' => mysqli_query($conn, "SELECT MIN(base_salary) as amount FROM salary_structures")->fetch_assoc()['amount'],
    'max_salary' => mysqli_query($conn, "SELECT MAX(maximum_salary) as amount FROM salary_structures")->fetch_assoc()['amount'],
    'avg_salary' => mysqli_query($conn, "SELECT AVG(base_salary) as amount FROM salary_structures")->fetch_assoc()['amount']
];

echo "<div class='stats'>";
echo "<div class='stat-card'><h4>Total Positions</h4><div class='number'>{$stats['total']}</div></div>";
echo "<div class='stat-card'><h4>Departments</h4><div class='number'>{$stats['departments']}</div></div>";
echo "<div class='stat-card'><h4>Lowest Base Salary</h4><div class='number'>₱" . number_format($stats['min_salary'], 2) . "</div></div>";
echo "<div class='stat-card'><h4>Highest Max Salary</h4><div class='number'>₱" . number_format($stats['max_salary'], 2) . "</div></div>";
echo "<div class='stat-card'><h4>Average Base Salary</h4><div class='number'>₱" . number_format($stats['avg_salary'], 2) . "</div></div>";
echo "</div>";

// Department breakdown
echo "<h3>Step 4: Breakdown by Department</h3>";
$dept_query = "SELECT department, COUNT(*) as count, 
               MIN(base_salary) as min_salary, 
               MAX(maximum_salary) as max_salary,
               AVG(base_salary) as avg_salary
               FROM salary_structures 
               GROUP BY department 
               ORDER BY count DESC";

$dept_result = mysqli_query($conn, $dept_query);

echo "<table>";
echo "<tr><th>Department</th><th>Positions</th><th>Min Salary</th><th>Max Salary</th><th>Avg Salary</th></tr>";
while ($row = mysqli_fetch_assoc($dept_result)) {
    echo "<tr>";
    echo "<td>{$row['department']}</td>";
    echo "<td>{$row['count']}</td>";
    echo "<td>₱" . number_format($row['min_salary'], 2) . "</td>";
    echo "<td>₱" . number_format($row['max_salary'], 2) . "</td>";
    echo "<td>₱" . number_format($row['avg_salary'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3 class='success'>✓ Seeding Completed Successfully!</h3>";
echo "<p><strong>Total Salary Structures Inserted:</strong> {$count}</p>";
echo "<div class='info'>
    <strong>📌 Note:</strong> 
    <ul style='margin: 10px 0;'>
        <li>Salaries are based on Philippine Government SSL 2024</li>
        <li>Step increments are applied every 3 years (as per government practice)</li>
        <li>Each position has 3% annual increment percentage</li>
        <li>Salary ranges account for step increments (1-8 steps)</li>
    </ul>
</div>";

echo "<a href='add-employee-comprehensive-form.php' class='btn'>Add Employee</a>";
echo "<a href='salary-structures.php' class='btn btn-blue'>View Salary Structures</a>";
echo "<a href='admin-employee.php' class='btn btn-orange'>Employee List</a>";

echo "</div></body></html>";
mysqli_close($conn);
?>

