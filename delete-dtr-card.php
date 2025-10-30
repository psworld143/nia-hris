<?php
/**
 * Delete DTR Card Handler
 * Deletes a DTR card record and its associated file
 */

// Prevent any output before JSON
ob_start();

// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error occurred: ' . $error['message'],
            'error_type' => 'FatalError',
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit();
    }
});

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once 'config/database.php';
    require_once 'includes/functions.php';

    // Clean any output buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');

    // Check permissions - only admins, hr_manager, and super_admin can delete
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }

    // Get DTR card ID
    $dtr_id = intval($_POST['dtr_id'] ?? 0);

    if (!$dtr_id) {
        echo json_encode(['success' => false, 'message' => 'DTR card ID is required']);
        exit();
    }

    // Check database connection
    if (!isset($conn) || !$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Get DTR card details before deleting
    $select_query = "SELECT file_path, employee_id, payroll_period_id, file_name FROM employee_dtr_cards WHERE id = ?";
    $select_stmt = mysqli_prepare($conn, $select_query);
    
    if (!$select_stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . mysqli_error($conn)]);
        exit();
    }

    mysqli_stmt_bind_param($select_stmt, "i", $dtr_id);
    mysqli_stmt_execute($select_stmt);
    $result = mysqli_stmt_get_result($select_stmt);
    $dtr_card = mysqli_fetch_assoc($result);
    mysqli_stmt_close($select_stmt);

    if (!$dtr_card) {
        echo json_encode(['success' => false, 'message' => 'DTR card not found']);
        exit();
    }

    // Get file path and other details
    $file_path = $dtr_card['file_path'] ?? '';
    $employee_id = $dtr_card['employee_id'] ?? 0;
    $payroll_period_id = $dtr_card['payroll_period_id'] ?? 0;
    $upload_dir = __DIR__ . '/uploads/dtr-cards/';
    
    // Find the file to delete (try stored path first, then search if needed)
    $absolute_path = null;
    $file_found = false;
    
    // Try stored file path first
    if (!empty($file_path) && $file_path !== '0' && $file_path !== 'null') {
        // Normalize file path - remove leading / if present to get relative path
        $relative_path = ltrim($file_path, '/');
        $absolute_path = __DIR__ . '/' . $relative_path;
        
        if (file_exists($absolute_path)) {
            $file_found = true;
        }
    }
    
    // If file not found using stored path, try to find it by employee_id and payroll_period_id
    if (!$file_found && $employee_id && $payroll_period_id && is_dir($upload_dir)) {
        // Build pattern: dtr_{employee_id}_{payroll_period_id}_*.{ext}
        $patterns = [
            $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.jpg",
            $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.jpeg",
            $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.png",
            $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.gif",
            $upload_dir . "dtr_{$employee_id}_{$payroll_period_id}_*.pdf"
        ];
        
        $files = [];
        foreach ($patterns as $pattern) {
            $found = glob($pattern);
            if ($found) {
                $files = array_merge($files, $found);
            }
        }
        
        if (!empty($files)) {
            // Use the most recent file if multiple found
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $absolute_path = $files[0];
            $file_found = true;
            error_log("DTR Delete: Found file by search - $absolute_path");
        }
    }
    
    // Delete the physical file first (before database deletion)
    $file_deleted = false;
    $file_delete_error = null;
    
    if ($file_found && $absolute_path && file_exists($absolute_path)) {
        // Try to delete the file
        if (@unlink($absolute_path)) {
            $file_deleted = true;
            error_log("DTR Delete: Successfully deleted file at $absolute_path");
        } else {
            $last_error = error_get_last();
            $file_delete_error = "Failed to delete file: " . ($last_error['message'] ?? 'Permission denied or file locked');
            error_log("DTR Delete Warning: Failed to delete file at $absolute_path - " . $file_delete_error);
            
            // Check file permissions
            $perms = substr(sprintf('%o', fileperms($absolute_path)), -4);
            error_log("DTR Delete: File permissions: $perms");
        }
    } else {
        // File doesn't exist - this is okay, we'll still delete the record
        $file_deleted = true; // Consider it "deleted" if it doesn't exist
        error_log("DTR Delete Info: File not found at " . ($absolute_path ?? 'N/A') . ", proceeding with record deletion");
    }
    
    // Now delete the database record
    $delete_query = "DELETE FROM employee_dtr_cards WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);

    if (!$delete_stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare delete query: ' . mysqli_error($conn)]);
        exit();
    }

    mysqli_stmt_bind_param($delete_stmt, "i", $dtr_id);
    
    if (!mysqli_stmt_execute($delete_stmt)) {
        $error = mysqli_error($conn);
        mysqli_stmt_close($delete_stmt);
        echo json_encode(['success' => false, 'message' => 'Failed to delete DTR card: ' . $error]);
        exit();
    }

    mysqli_stmt_close($delete_stmt);

    // Log the deletion
    error_log("DTR Card Deleted - ID: $dtr_id, Employee ID: {$dtr_card['employee_id']}, File: {$dtr_card['file_name']}, File deleted: " . ($file_deleted ? 'Yes' : 'No'));
    
    // Build success message
    $message = 'DTR card and file deleted successfully';
    if (!$file_deleted && $file_delete_error) {
        $message = 'DTR card deleted from database, but file could not be removed: ' . $file_delete_error;
    } else if (!$file_found) {
        $message = 'DTR card deleted from database (file was not found on server)';
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $message,
        'dtr_id' => $dtr_id,
        'file_deleted' => $file_deleted,
        'file_found' => $file_found
    ]);

    if (isset($conn)) {
        mysqli_close($conn);
    }
    exit();
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("DTR Delete Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting DTR card: ' . $e->getMessage(),
        'error_type' => 'Exception',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("DTR Delete Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error deleting DTR card: ' . $e->getMessage(),
        'error_type' => 'Throwable',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}
