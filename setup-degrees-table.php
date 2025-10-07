<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    die('Unauthorized access. Admin or HR privileges required.');
}

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if ($is_ajax) {
    header('Content-Type: application/json');
}

$messages = [];
$success = true;

if (!$is_ajax) {
    echo "<h2>Setting up Degrees Table...</h2>";
}

// Create degrees table
$create_table = "CREATE TABLE IF NOT EXISTS degrees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    degree_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_degree_name (degree_name),
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $create_table)) {
    $messages[] = "Degrees table created successfully!";
    if (!$is_ajax) {
        echo "<p style='color: green;'>✓ Degrees table created successfully!</p>";
    }
} else {
    $success = false;
    $messages[] = "Error creating degrees table: " . mysqli_error($conn);
    if (!$is_ajax) {
        echo "<p style='color: red;'>✗ Error creating degrees table: " . mysqli_error($conn) . "</p>";
    }
}

// Insert default degrees
$default_degrees = [
    ['Elementary', 'Completed elementary education', 1],
    ['High School', 'Completed high school education', 2],
    ['Vocational', 'Vocational or technical training', 3],
    ['Associate Degree', "Two-year college degree (Associate's)", 4],
    ["Bachelor's Degree", 'Four-year undergraduate degree', 5],
    ["Master's Degree", 'Graduate degree beyond bachelor\'s', 6],
    ['Doctorate', 'Doctoral degree (PhD, EdD, etc.)', 7],
    ['Post-Doctorate', 'Post-doctoral research or studies', 8]
];

if (!$is_ajax) {
    echo "<h3>Inserting default degrees...</h3>";
}

$added_count = 0;
$existing_count = 0;

foreach ($default_degrees as $degree) {
    $check_query = "SELECT id FROM degrees WHERE degree_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $degree[0]);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $insert_query = "INSERT INTO degrees (degree_name, description, sort_order, is_active, created_by) VALUES (?, ?, ?, 1, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssii", $degree[0], $degree[1], $degree[2], $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $added_count++;
            if (!$is_ajax) {
                echo "<p style='color: green;'>✓ Added: {$degree[0]}</p>";
            }
        } else {
            $success = false;
            if (!$is_ajax) {
                echo "<p style='color: red;'>✗ Failed to add: {$degree[0]}</p>";
            }
        }
    } else {
        $existing_count++;
        if (!$is_ajax) {
            echo "<p style='color: blue;'>• Already exists: {$degree[0]}</p>";
        }
    }
}

if ($is_ajax) {
    // Return JSON response for AJAX
    $response_message = "Setup completed successfully! Added $added_count new degrees.";
    if ($existing_count > 0) {
        $response_message .= " ($existing_count already existed)";
    }
    echo json_encode([
        'success' => $success,
        'message' => $response_message,
        'added' => $added_count,
        'existing' => $existing_count
    ]);
} else {
    // Show HTML response for direct access
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Added $added_count new degrees. $existing_count degrees already existed.</p>";
    echo "<p><a href='manage-degrees.php' style='display: inline-block; padding: 10px 20px; background: #10B981; color: white; text-decoration: none; border-radius: 5px;'>Go to Manage Degrees</a></p>";
}
?>

