<?php
/**
 * Seed Sample Payroll Periods
 * Creates realistic Philippine government payroll periods
 */

require_once 'config/database.php';
date_default_timezone_set('Asia/Manila');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Payroll Periods - NIA HRIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-calendar-alt text-green-600 mr-2"></i>Seed Payroll Periods
                </h1>
                <p class="text-gray-600">Creating sample semi-monthly payroll periods</p>
            </div>

            <div class="space-y-2 font-mono text-sm">
<?php

echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-calendar text-green-500 mr-2'></i>Creating Payroll Periods...</h3>";

// Get current user ID for created_by
$created_by = 1; // Default to admin user
if (isset($_SESSION['user_id'])) {
    $created_by = $_SESSION['user_id'];
}

// Philippine government typically uses semi-monthly payroll (15th and end of month)
// Create periods for the past 6 months and next 3 months

$periods_created = 0;
$current_year = date('Y');
$current_month = date('n');

// Generate periods for 6 months back to 3 months forward
for ($i = -6; $i <= 3; $i++) {
    $month = $current_month + $i;
    $year = $current_year;
    
    // Adjust year if month goes beyond 12 or below 1
    while ($month > 12) {
        $month -= 12;
        $year++;
    }
    while ($month < 1) {
        $month += 12;
        $year--;
    }
    
    $month_name = date('F', mktime(0, 0, 0, $month, 1, $year));
    
    // First half (1st to 15th)
    $period1_name = "$month_name $year - 1st Half";
    $period1_start = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
    $period1_end = date('Y-m-d', mktime(0, 0, 0, $month, 15, $year));
    $period1_payment = date('Y-m-d', mktime(0, 0, 0, $month, 20, $year)); // Payment on 20th
    
    // Determine status based on date
    $today = date('Y-m-d');
    if ($period1_end < $today) {
        $status1 = 'closed'; // Past periods are closed
    } elseif ($period1_start <= $today && $period1_end >= $today) {
        $status1 = 'open'; // Current period is open
    } else {
        $status1 = 'draft'; // Future periods are draft
    }
    
    // Check if period exists
    $check_query = "SELECT id FROM payroll_periods WHERE period_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $period1_name);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) == 0) {
        // Insert first half period
        $insert_query = "INSERT INTO payroll_periods (
            period_name, period_type, start_date, end_date, payment_date,
            status, created_by, created_at
        ) VALUES (?, 'semi-monthly', ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "sssssi",
            $period1_name, $period1_start, $period1_end, $period1_payment,
            $status1, $created_by
        );
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<span class='success'>✓ Created: $period1_name ($status1)</span><br>";
            $periods_created++;
        } else {
            echo "<span class='error'>✗ Error creating: $period1_name</span><br>";
        }
    } else {
        echo "<span class='info'>→ Already exists: $period1_name</span><br>";
    }
    
    // Second half (16th to end of month)
    $period2_name = "$month_name $year - 2nd Half";
    $last_day = date('t', mktime(0, 0, 0, $month, 1, $year)); // Get last day of month
    $period2_start = date('Y-m-d', mktime(0, 0, 0, $month, 16, $year));
    $period2_end = date('Y-m-d', mktime(0, 0, 0, $month, $last_day, $year));
    $period2_payment = date('Y-m-d', mktime(0, 0, 0, $month + 1, 5, $year)); // Payment on 5th of next month
    
    if ($period2_end < $today) {
        $status2 = 'closed';
    } elseif ($period2_start <= $today && $period2_end >= $today) {
        $status2 = 'open';
    } else {
        $status2 = 'draft';
    }
    
    // Check if period exists
    $check_stmt2 = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt2, "s", $period2_name);
    mysqli_stmt_execute($check_stmt2);
    $check_result2 = mysqli_stmt_get_result($check_stmt2);
    
    if (mysqli_num_rows($check_result2) == 0) {
        // Insert second half period
        $stmt2 = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt2, "sssssi",
            $period2_name, $period2_start, $period2_end, $period2_payment,
            $status2, $created_by
        );
        
        if (mysqli_stmt_execute($stmt2)) {
            echo "<span class='success'>✓ Created: $period2_name ($status2)</span><br>";
            $periods_created++;
        } else {
            echo "<span class='error'>✗ Error creating: $period2_name</span><br>";
        }
    } else {
        echo "<span class='info'>→ Already exists: $period2_name</span><br>";
    }
}

echo "<div class='mt-8 p-6 bg-green-50 border-l-4 border-green-500 rounded'>";
echo "<h3 class='text-lg font-bold text-green-800 mb-2'><i class='fas fa-check-circle mr-2'></i>Payroll Periods Seeding Completed!</h3>";
echo "<p class='text-green-700'>Successfully created payroll periods:</p>";
echo "<ul class='list-disc list-inside text-green-700 mt-2 space-y-1'>";
echo "<li>$periods_created New payroll periods created</li>";
echo "<li>Semi-monthly schedule (15th and end of month)</li>";
echo "<li>Covers 6 months past to 3 months future</li>";
echo "<li>Automatic status assignment (Draft/Open/Closed)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='mt-6 p-4 bg-blue-50 border border-blue-200 rounded'>";
echo "<h4 class='font-bold text-blue-900 mb-2'><i class='fas fa-info-circle text-blue-600 mr-2'></i>Period Schedule</h4>";
echo "<ul class='text-sm text-blue-800 space-y-1'>";
echo "<li><strong>1st Half:</strong> 1st - 15th of month → Payment on 20th</li>";
echo "<li><strong>2nd Half:</strong> 16th - End of month → Payment on 5th of next month</li>";
echo "<li><strong>Status:</strong> Past = Closed, Current = Open, Future = Draft</li>";
echo "</ul>";
echo "</div>";

// Get some sample periods
$sample_query = "SELECT period_name, start_date, end_date, payment_date, status 
                 FROM payroll_periods 
                 ORDER BY start_date DESC 
                 LIMIT 6";
$sample_result = mysqli_query($conn, $sample_query);

if (mysqli_num_rows($sample_result) > 0) {
    echo "<div class='mt-6 p-4 bg-purple-50 border border-purple-200 rounded'>";
    echo "<h4 class='font-bold text-purple-900 mb-3'><i class='fas fa-list text-purple-600 mr-2'></i>Recent Periods Created</h4>";
    echo "<div class='space-y-2'>";
    
    while ($sample = mysqli_fetch_assoc($sample_result)) {
        $status_colors = [
            'draft' => 'bg-gray-200 text-gray-800',
            'open' => 'bg-green-200 text-green-800',
            'closed' => 'bg-blue-200 text-blue-800',
            'paid' => 'bg-purple-200 text-purple-800'
        ];
        $status_class = $status_colors[$sample['status']] ?? 'bg-gray-200 text-gray-800';
        
        echo "<div class='flex items-center justify-between p-2 bg-white rounded'>";
        echo "<div class='flex-1'>";
        echo "<p class='font-semibold text-sm text-gray-900'>{$sample['period_name']}</p>";
        echo "<p class='text-xs text-gray-600'>" . date('M d', strtotime($sample['start_date'])) . " - " . date('M d, Y', strtotime($sample['end_date'])) . " • Pay: " . date('M d, Y', strtotime($sample['payment_date'])) . "</p>";
        echo "</div>";
        echo "<span class='px-2 py-1 rounded-full text-xs font-semibold $status_class'>" . ucfirst($sample['status']) . "</span>";
        echo "</div>";
    }
    
    echo "</div></div>";
}

?>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="dtr-management.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-clock mr-2"></i>DTR Management
                </a>
                <a href="payroll-management.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-money-check-alt mr-2"></i>Payroll Management
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

