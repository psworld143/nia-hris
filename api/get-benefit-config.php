<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get employee ID
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employee_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Employee ID is required']);
    exit;
}

// Fetch employee benefit configuration
$query = "SELECT 
            ebc.sss_deduction_type,
            ebc.sss_fixed_amount,
            ebc.sss_percentage,
            ebc.philhealth_deduction_type,
            ebc.philhealth_fixed_amount,
            ebc.philhealth_percentage,
            ebc.pagibig_deduction_type,
            ebc.pagibig_fixed_amount,
            ebc.pagibig_percentage,
            ebc.tax_deduction_type,
            ebc.tax_fixed_amount,
            ebc.tax_percentage,
            e.sss_number,
            e.philhealth_number,
            e.pagibig_number,
            e.tin_number
          FROM employees e
          LEFT JOIN employee_benefit_configurations ebc ON e.id = ebc.employee_id
          WHERE e.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    // Set defaults if no configuration exists
    $config = [
        'sss' => [
            'type' => $row['sss_deduction_type'] ?? 'auto',
            'fixed_amount' => floatval($row['sss_fixed_amount'] ?? 0),
            'percentage' => floatval($row['sss_percentage'] ?? 0),
            'has_id' => !empty($row['sss_number'])
        ],
        'philhealth' => [
            'type' => $row['philhealth_deduction_type'] ?? 'auto',
            'fixed_amount' => floatval($row['philhealth_fixed_amount'] ?? 0),
            'percentage' => floatval($row['philhealth_percentage'] ?? 0),
            'has_id' => !empty($row['philhealth_number'])
        ],
        'pagibig' => [
            'type' => $row['pagibig_deduction_type'] ?? 'auto',
            'fixed_amount' => floatval($row['pagibig_fixed_amount'] ?? 0),
            'percentage' => floatval($row['pagibig_percentage'] ?? 0),
            'has_id' => !empty($row['pagibig_number'])
        ],
        'tax' => [
            'type' => $row['tax_deduction_type'] ?? 'auto',
            'fixed_amount' => floatval($row['tax_fixed_amount'] ?? 0),
            'percentage' => floatval($row['tax_percentage'] ?? 0),
            'has_id' => !empty($row['tin_number'])
        ]
    ];
    
    echo json_encode(['success' => true, 'config' => $config]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Employee not found']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

