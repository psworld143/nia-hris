<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource or hr_manager role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['human_resource', 'hr_manager'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get main category ID from request
$main_category_id = isset($_GET['main_category_id']) ? (int)$_GET['main_category_id'] : 0;

if ($main_category_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid main category ID']);
    exit();
}

// Get sub-categories for the specified main category (HR-created only)
$query = "SELECT id, name FROM evaluation_sub_categories 
          WHERE main_category_id = ? AND status = 'active' AND created_by_role IN ('human_resource', 'hr_manager')
          ORDER BY name ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $main_category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sub_categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sub_categories[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($sub_categories);
?>
