<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$benefit_id = isset($_POST['benefit_id']) ? intval($_POST['benefit_id']) : 0;
$benefit_code = strtoupper(trim($_POST['benefit_code']));
$benefit_name = trim($_POST['benefit_name']);
$category = $_POST['category'];
$calculation_type = $_POST['calculation_type'];
$default_rate = isset($_POST['default_rate']) ? floatval($_POST['default_rate']) : 0;
$has_employer_share = isset($_POST['has_employer_share']) ? 1 : 0;
$description = trim($_POST['description'] ?? '');

// Validation
if (empty($benefit_code) || empty($benefit_name)) {
    echo json_encode(['success' => false, 'message' => 'Benefit code and name are required']);
    exit;
}

if ($benefit_id > 0) {
    // Update existing benefit
    $query = "UPDATE benefit_types SET 
              benefit_code = ?,
              benefit_name = ?,
              category = ?,
              calculation_type = ?,
              default_rate = ?,
              has_employer_share = ?,
              description = ?
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssdsi", 
        $benefit_code, $benefit_name, $category, $calculation_type, 
        $default_rate, $has_employer_share, $description, $benefit_id);
} else {
    // Check if code already exists
    $check_query = "SELECT id FROM benefit_types WHERE benefit_code = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $benefit_code);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Benefit code already exists']);
        exit;
    }
    
    // Insert new benefit
    $query = "INSERT INTO benefit_types 
              (benefit_code, benefit_name, category, calculation_type, default_rate, has_employer_share, description) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssdis", 
        $benefit_code, $benefit_name, $category, $calculation_type, 
        $default_rate, $has_employer_share, $description);
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'message' => $benefit_id > 0 ? 'Benefit updated successfully' : 'Benefit added successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

