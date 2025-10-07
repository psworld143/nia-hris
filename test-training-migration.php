<?php
// Clean all buffers
while (ob_get_level()) {
    ob_end_clean();
}

session_start();
require_once 'config/database.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized', 'step' => 'auth']);
    exit();
}

try {
    // Test connection to seait_website
    $seait_conn = @mysqli_connect($host, $username, $password, 'seait_website', 3306, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
    
    if (!$seait_conn) {
        echo json_encode(['success' => false, 'message' => 'Cannot connect to seait_website: ' . mysqli_connect_error(), 'step' => 'connection']);
        exit();
    }
    
    // Check if trainings_seminars table exists
    $check_query = mysqli_query($seait_conn, "SHOW TABLES LIKE 'trainings_seminars'");
    $table_exists = mysqli_num_rows($check_query) > 0;
    
    if (!$table_exists) {
        echo json_encode(['success' => false, 'message' => 'trainings_seminars table not found in seait_website', 'step' => 'table_check']);
        exit();
    }
    
    // Count records
    $count_result = mysqli_query($seait_conn, "SELECT COUNT(*) as cnt FROM trainings_seminars");
    $count_row = mysqli_fetch_assoc($count_result);
    $record_count = $count_row['cnt'];
    
    mysqli_close($seait_conn);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Connection test successful', 
        'step' => 'complete',
        'records_found' => $record_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'step' => 'exception']);
}
?>

