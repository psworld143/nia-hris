<?php
/**
 * Fix DTR File Paths
 * This script fixes DTR card records that have invalid file paths
 */

require_once 'config/database.php';

$upload_dir = __DIR__ . '/uploads/dtr-cards/';
$fixed_count = 0;
$not_found_count = 0;

// Get all DTR cards with invalid file paths
$query = "SELECT id, employee_id, payroll_period_id, file_path, file_name, upload_date 
          FROM employee_dtr_cards 
          WHERE file_path = '0' OR file_path = '' OR file_path IS NULL OR file_path = 'null'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error: " . mysqli_error($conn) . "\n");
}

echo "Found " . mysqli_num_rows($result) . " records with invalid file paths.\n\n";

while ($row = mysqli_fetch_assoc($result)) {
    $dtr_id = $row['id'];
    $employee_id = $row['employee_id'];
    $payroll_period_id = $row['payroll_period_id'];
    $upload_date = $row['upload_date'];
    
    echo "Processing DTR ID: $dtr_id (Employee: $employee_id, Period: $payroll_period_id)\n";
    
    // Try to find matching file
    $file_found = false;
    $correct_path = '';
    
    if (is_dir($upload_dir)) {
        // Try patterns: dtr_{employee_id}_{payroll_period_id}_*.{ext}
        $patterns = [
            "dtr_{$employee_id}_{$payroll_period_id}_*.jpg",
            "dtr_{$employee_id}_{$payroll_period_id}_*.jpeg",
            "dtr_{$employee_id}_{$payroll_period_id}_*.png",
            "dtr_{$employee_id}_{$payroll_period_id}_*.gif",
            "dtr_{$employee_id}_{$payroll_period_id}_*.pdf"
        ];
        
        $files = [];
        foreach ($patterns as $pattern) {
            $found = glob($upload_dir . $pattern);
            if ($found) {
                $files = array_merge($files, $found);
            }
        }
        
        if (!empty($files)) {
            // If multiple files, try to match by upload_date
            $matched_file = null;
            
            if (count($files) === 1) {
                $matched_file = $files[0];
            } else if ($upload_date) {
                // Find file with timestamp closest to upload_date
                $upload_timestamp = strtotime($upload_date);
                $best_match = null;
                $min_diff = PHP_INT_MAX;
                
                foreach ($files as $file) {
                    if (preg_match('/dtr_\d+_\d+_(\d+)\.\w+$/', basename($file), $matches)) {
                        $file_timestamp = intval($matches[1]);
                        $diff = abs($file_timestamp - $upload_timestamp);
                        if ($diff < $min_diff) {
                            $min_diff = $diff;
                            $best_match = $file;
                        }
                    }
                }
                
                if ($best_match) {
                    $matched_file = $best_match;
                } else {
                    // Use most recent file
                    usort($files, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $matched_file = $files[0];
                }
            } else {
                // Use most recent file
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $matched_file = $files[0];
            }
            
            if ($matched_file && file_exists($matched_file)) {
                $relative_path = str_replace(__DIR__ . '/', '', $matched_file);
                $correct_path = $relative_path;
                $file_found = true;
            }
        }
    }
    
    if ($file_found) {
        // Update the database
        $update_query = "UPDATE employee_dtr_cards SET file_path = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $correct_path, $dtr_id);
            if (mysqli_stmt_execute($update_stmt)) {
                echo "  ✓ Fixed: Set file_path to '$correct_path'\n";
                $fixed_count++;
            } else {
                echo "  ✗ Error updating: " . mysqli_error($conn) . "\n";
            }
            mysqli_stmt_close($update_stmt);
        } else {
            echo "  ✗ Error preparing update: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "  ✗ File not found in upload directory\n";
        $not_found_count++;
    }
    
    echo "\n";
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed_count records\n";
echo "Not Found: $not_found_count records\n";

mysqli_close($conn);
?>

