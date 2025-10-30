<?php
/**
 * Upload DTR Cards Handler
 * Processes multiple DTR card uploads per payroll period
 */

// Prevent any output before JSON
ob_start();

// Suppress error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL); // Still log errors, just don't display them

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

    // Check permissions
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }

    // Get payroll period
    $payroll_period_id = intval($_POST['payroll_period_id'] ?? 0);

    if (!$payroll_period_id) {
        echo json_encode(['success' => false, 'message' => 'Payroll period is required']);
        exit();
    }

    // Check database connection
    if (!isset($conn) || !$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    // Get period dates
    $period_query = "SELECT start_date, end_date FROM payroll_periods WHERE id = ?";
    $period_stmt = mysqli_prepare($conn, $period_query);
    mysqli_stmt_bind_param($period_stmt, "i", $payroll_period_id);
    mysqli_stmt_execute($period_stmt);
    $period_result = mysqli_stmt_get_result($period_stmt);
    $period = mysqli_fetch_assoc($period_result);

    if (!$period) {
        echo json_encode(['success' => false, 'message' => 'Invalid payroll period']);
        exit();
    }

    $period_start = $period['start_date'];
    $period_end = $period['end_date'];

    // Process uploads
    $employee_ids = $_POST['employee_id'] ?? [];
    $upload_dir = __DIR__ . '/uploads/dtr-cards/';
    $uploaded_count = 0;
    $errors = [];

    // Ensure upload directory exists with proper permissions
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        // Ensure it's writable even after creation
        @chmod($upload_dir, 0777);
    } else {
        // Ensure existing directory is writable
        @chmod($upload_dir, 0777);
    }

    foreach ($employee_ids as $index => $employee_id) {
    $employee_id = intval($employee_id);
    
    if (!$employee_id) {
        continue;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['dtr_file']['name'][$index]) || $_FILES['dtr_file']['error'][$index] !== UPLOAD_ERR_OK) {
        $errors[] = "No file uploaded for employee ID: $employee_id";
        continue;
    }
    
    $file_name = $_FILES['dtr_file']['name'][$index];
    $file_tmp = $_FILES['dtr_file']['tmp_name'][$index];
    $file_size = $_FILES['dtr_file']['size'][$index];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($file_ext, $allowed_extensions)) {
        $errors[] = "Invalid file type for employee ID: $employee_id. Allowed: " . implode(', ', $allowed_extensions);
        continue;
    }
    
    // Validate file size (max 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        $errors[] = "File too large for employee ID: $employee_id. Max size: 5MB";
        continue;
    }
    
    // Generate unique filename
    $new_filename = 'dtr_' . $employee_id . '_' . $payroll_period_id . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $new_filename;
    $relative_path = 'uploads/dtr-cards/' . $new_filename;
    
    // Log upload details for debugging
    error_log("DTR Upload Attempt - Employee ID: $employee_id, File: $file_name, Size: $file_size, Temp: $file_tmp, Target: $file_path");
    
    // Check if upload directory exists and is writable
    if (!is_dir($upload_dir)) {
        $errors[] = "Upload directory does not exist: $upload_dir";
        error_log("DTR Upload Error: Directory missing - $upload_dir");
        continue;
    }
    
    if (!is_writable($upload_dir)) {
        $errors[] = "Upload directory is not writable: $upload_dir";
        error_log("DTR Upload Error: Directory not writable - $upload_dir (Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . ")");
        continue;
    }
    
    // Check if temp file exists
    if (!file_exists($file_tmp)) {
        $errors[] = "Temporary file not found for employee ID: $employee_id";
        error_log("DTR Upload Error: Temp file missing - $file_tmp");
        continue;
    }
    
    // Check if temp file is readable
    if (!is_readable($file_tmp)) {
        $errors[] = "Temporary file not readable for employee ID: $employee_id";
        error_log("DTR Upload Error: Temp file not readable - $file_tmp");
        continue;
    }
    
    // Try to move uploaded file
    $move_result = @move_uploaded_file($file_tmp, $file_path);
    
    if ($move_result) {
        // Verify file was moved successfully
        if (!file_exists($file_path)) {
            $errors[] = "File moved but target file not found for employee ID: $employee_id";
            error_log("DTR Upload Error: File missing after move - $file_path");
            continue;
        }
        
        // Insert into database
        $insert_query = "INSERT INTO employee_dtr_cards (
            employee_id, payroll_period_id, period_start_date, period_end_date,
            file_path, file_name, file_size, uploaded_by, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        
        if (!$stmt) {
            $errors[] = "Failed to prepare database query for employee ID: $employee_id - " . mysqli_error($conn);
            error_log("DTR Upload Error: Statement prepare failed - " . mysqli_error($conn));
            // Delete uploaded file if database prep failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            continue;
        }
        
        $uploaded_by = $_SESSION['user_id'];
        
        // Parameter types: employee_id(i), payroll_period_id(i), period_start_date(s), period_end_date(s), file_path(s), file_name(s), file_size(i), uploaded_by(i)
        mysqli_stmt_bind_param($stmt, "iissssii",
            $employee_id, $payroll_period_id, $period_start, $period_end,
            $relative_path, $file_name, $file_size, $uploaded_by
        );
        
        // Log the values being bound for debugging
        error_log("DTR Bind Values - Employee ID: $employee_id, Period: $payroll_period_id, Path: $relative_path, File: $file_name, Size: $file_size, Uploaded By: $uploaded_by");
        
        if (mysqli_stmt_execute($stmt)) {
            $uploaded_count++;
            $inserted_id = mysqli_insert_id($conn);
            error_log("DTR Upload Success - Employee ID: $employee_id, File: $new_filename, Record ID: $inserted_id, Path: $relative_path");
            
            // Verify the insert was correct
            $verify_query = "SELECT file_path FROM employee_dtr_cards WHERE id = ?";
            $verify_stmt = mysqli_prepare($conn, $verify_query);
            if ($verify_stmt) {
                mysqli_stmt_bind_param($verify_stmt, "i", $inserted_id);
                mysqli_stmt_execute($verify_stmt);
                $verify_result = mysqli_stmt_get_result($verify_stmt);
                $verify_row = mysqli_fetch_assoc($verify_result);
                if ($verify_row) {
                    error_log("DTR Path Verification - Record ID: $inserted_id, Stored Path: " . ($verify_row['file_path'] ?? 'NULL'));
                    if (empty($verify_row['file_path']) || $verify_row['file_path'] === '0') {
                        // Path was not saved correctly, try to fix it
                        error_log("DTR Path Fix - Attempting to update record $inserted_id with path: $relative_path");
                        $fix_query = "UPDATE employee_dtr_cards SET file_path = ? WHERE id = ?";
                        $fix_stmt = mysqli_prepare($conn, $fix_query);
                        if ($fix_stmt) {
                            mysqli_stmt_bind_param($fix_stmt, "si", $relative_path, $inserted_id);
                            if (mysqli_stmt_execute($fix_stmt)) {
                                error_log("DTR Path Fix - Successfully updated record $inserted_id");
                            } else {
                                error_log("DTR Path Fix - Failed to update record $inserted_id: " . mysqli_error($conn));
                            }
                            mysqli_stmt_close($fix_stmt);
                        }
                    }
                }
                mysqli_stmt_close($verify_stmt);
            }
        } else {
            $db_error = mysqli_error($conn);
            $stmt_error = mysqli_stmt_error($stmt);
            $error_msg = "Database error for employee ID: $employee_id";
            if ($db_error) {
                $error_msg .= " - " . $db_error;
            }
            if ($stmt_error) {
                $error_msg .= " (Statement: " . $stmt_error . ")";
            }
            $errors[] = $error_msg;
            error_log("DTR upload database error for employee $employee_id: $db_error (Statement: $stmt_error)");
            // Delete uploaded file if database insert failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    } else {
        // Get detailed error information
        $upload_error = $_FILES['dtr_file']['error'][$index];
        $error_msg = "Failed to upload file for employee ID: $employee_id";
        
        // Check PHP upload error code
        $upload_error_messages = [
            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
        ];
        
        if (isset($upload_error_messages[$upload_error])) {
            $error_msg .= " - " . $upload_error_messages[$upload_error] . " (Error code: $upload_error)";
        } else {
            $error_msg .= " - Unknown upload error (Code: $upload_error)";
        }
        
        // Additional checks
        $error_details = [];
        if (!file_exists($upload_dir)) {
            $error_details[] = "Upload directory missing: $upload_dir";
        }
        if (file_exists($upload_dir) && !is_writable($upload_dir)) {
            $error_details[] = "Upload directory not writable: $upload_dir";
        }
        if (!file_exists($file_tmp)) {
            $error_details[] = "Temp file missing: $file_tmp";
        }
        if (file_exists($file_path)) {
            $error_details[] = "Target file already exists: $file_path";
        }
        
        if (!empty($error_details)) {
            $error_msg .= " - " . implode(", ", $error_details);
        }
        
        $errors[] = $error_msg;
        error_log("DTR Upload Error Details - Employee ID: $employee_id, Error Code: $upload_error, Details: " . implode("; ", $error_details));
    }
}

    // Return response
    ob_clean();

    if ($uploaded_count > 0) {
        $message = "$uploaded_count DTR card(s) uploaded successfully";
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " error(s) occurred.";
        }
        echo json_encode([
            'success' => true,
            'message' => $message,
            'uploaded' => $uploaded_count,
            'errors' => $errors
        ]);
    } else {
        $error_message = 'No DTR cards were uploaded';
        if (!empty($errors)) {
            $error_message .= ': ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $error_message .= ' (and ' . (count($errors) - 3) . ' more)';
            }
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $error_message,
            'errors' => $errors,
            'error_count' => count($errors)
        ]);
    }

    if (isset($conn)) {
        mysqli_close($conn);
    }
    exit();
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("DTR Upload Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing upload: ' . $e->getMessage(),
        'error_type' => 'Exception',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
} catch (Throwable $e) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("DTR Upload Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Fatal error processing upload: ' . $e->getMessage(),
        'error_type' => 'Throwable',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit();
}

