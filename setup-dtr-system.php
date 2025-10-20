<?php
/**
 * Setup DTR Card Upload System
 * Creates table for storing employee DTR card images per payroll period
 */

require_once 'config/database.php';
date_default_timezone_set('Asia/Manila');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup DTR System - NIA HRIS</title>
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
                    <i class="fas fa-clock text-blue-600 mr-2"></i>Setup DTR Card Upload System
                </h1>
                <p class="text-gray-600">Creating DTR card management infrastructure</p>
            </div>

            <div class="space-y-2 font-mono text-sm">
<?php

echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-database text-blue-500 mr-2'></i>Creating DTR Tables...</h3>";

// 1. Create DTR cards table
$create_dtr_table = "CREATE TABLE IF NOT EXISTS employee_dtr_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_period_id INT DEFAULT NULL,
    period_start_date DATE NOT NULL,
    period_end_date DATE NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT DEFAULT NULL,
    status ENUM('pending', 'verified', 'processed', 'rejected') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_period (employee_id, period_start_date, period_end_date),
    INDEX idx_payroll_period (payroll_period_id),
    INDEX idx_status (status),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $create_dtr_table)) {
    echo "<span class='success'>✓ DTR cards table created successfully</span><br>";
} else {
    echo "<span class='error'>✗ Error creating DTR cards table: " . mysqli_error($conn) . "</span><br>";
}

// 2. Create DTR upload directory
$upload_dir = __DIR__ . '/uploads/dtr-cards';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<span class='success'>✓ Created upload directory: uploads/dtr-cards/</span><br>";
    } else {
        echo "<span class='error'>✗ Failed to create upload directory</span><br>";
    }
} else {
    echo "<span class='info'>→ Upload directory already exists</span><br>";
}

// 3. Create .htaccess for security
$htaccess_content = "# Protect DTR card images
<FilesMatch \"\.(jpg|jpeg|png|gif|pdf)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Allow access only through PHP scripts
Options -Indexes
";

$htaccess_file = $upload_dir . '/.htaccess';
if (file_put_contents($htaccess_file, $htaccess_content)) {
    echo "<span class='success'>✓ Created security .htaccess file</span><br>";
} else {
    echo "<span class='error'>✗ Failed to create .htaccess file</span><br>";
}

echo "<div class='mt-8 p-6 bg-green-50 border-l-4 border-green-500 rounded'>";
echo "<h3 class='text-lg font-bold text-green-800 mb-2'><i class='fas fa-check-circle mr-2'></i>DTR System Setup Completed!</h3>";
echo "<p class='text-green-700'>Successfully created:</p>";
echo "<ul class='list-disc list-inside text-green-700 mt-2 space-y-1'>";
echo "<li>employee_dtr_cards table</li>";
echo "<li>Upload directory: uploads/dtr-cards/</li>";
echo "<li>Security configuration (.htaccess)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='mt-6 p-4 bg-blue-50 border border-blue-200 rounded'>";
echo "<h4 class='font-bold text-blue-900 mb-2'><i class='fas fa-info-circle text-blue-600 mr-2'></i>Next Steps</h4>";
echo "<ul class='list-disc list-inside text-sm text-blue-800 space-y-1'>";
echo "<li>Upload DTR cards for each payroll period</li>";
echo "<li>Verify and approve uploaded DTR cards</li>";
echo "<li>Process payroll based on verified DTR data</li>";
echo "</ul>";
echo "</div>";

?>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="dtr-management.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-clock mr-2"></i>Manage DTR Cards
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

