<?php
// NIA-HRIS Utility Functions
// Standalone Human Resource Information System

/**
 * Get current academic year and semester
 * @return array Array with 'academic_year' and 'semester' keys
 */
function getCurrentAcademicYearAndSemester() {
    $current_year = date('Y');
    $current_month = date('n');
    
    // Determine academic year based on current month
    if ($current_month >= 6 && $current_month <= 12) {
        $academic_year = $current_year . '-' . ($current_year + 1);
    } else {
        $academic_year = ($current_year - 1) . '-' . $current_year;
    }
    
    // Determine semester based on current month
    if ($current_month >= 6 && $current_month <= 12) {
        $semester = 'First Semester';
    } else {
        $semester = 'Second Semester';
    }
    
    return [
        'academic_year' => $academic_year,
        'semester' => $semester
    ];
}

function sanitize_input($data) {
    // Handle NULL values - preserve them as NULL
    if ($data === null) {
        return null;
    }
    
    // Handle non-string values - convert to string first
    if (!is_string($data)) {
        if (is_array($data)) {
            // Arrays should not be converted to strings, return NULL instead
            return null;
        }
        $data = (string)$data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // Return NULL if the result is empty (to preserve NULL semantics)
    return empty($data) ? null : $data;
}

function get_login_path() {
    // Determine the correct path to index.php (main page with login modal) based on current directory
    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
    $depth = substr_count($current_dir, '/') - 1; // -1 because we start from root

    if ($depth > 0) {
        return str_repeat('../', $depth) . 'index.php';
    } else {
        return 'index.php';
    }
}

function check_login() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Use output buffering to prevent header errors
        if (!headers_sent()) {
            header("Location: " . get_login_path());
            exit();
        } else {
            // If headers already sent, use JavaScript redirect
            echo '<script>window.location.href = "' . get_login_path() . '";</script>';
            exit();
        }
    }
}

function check_hr_access() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'human_resource' && $_SESSION['role'] !== 'hr_manager' && $_SESSION['role'] !== 'admin')) {
        // Use output buffering to prevent header errors
        if (!headers_sent()) {
            header("Location: " . get_login_path());
            exit();
        } else {
            // If headers already sent, use JavaScript redirect
            echo '<script>window.location.href = "' . get_login_path() . '";</script>';
            exit();
        }
    }
}

function get_user_role() {
    return isset($_SESSION['role']) && is_string($_SESSION['role']) ? $_SESSION['role'] : '';
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirect($url) {
    // Use output buffering to prevent header errors
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        // If headers already sent, use JavaScript redirect
        echo '<script>window.location.href = "' . $url . '";</script>';
        exit();
    }
}

function display_message($message, $type = 'info') {
    $alert_class = '';
    switch($type) {
        case 'success':
            $alert_class = 'bg-green-100 border-green-400 text-green-700';
            break;
        case 'error':
            $alert_class = 'bg-red-100 border-red-400 text-red-700';
            break;
        case 'warning':
            $alert_class = 'bg-yellow-100 border-yellow-400 text-yellow-700';
            break;
        default:
            $alert_class = 'bg-blue-100 border-blue-400 text-blue-700';
    }

    return "<div class='$alert_class border px-4 py-3 rounded mb-4'>$message</div>";
}

/**
 * Get organization logo from database settings
 * @param mysqli $conn Database connection
 * @return string Organization logo URL or empty string if not set
 */
function get_organization_logo($conn) {
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'organization_logo'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'] ?? '';
    }
    return '';
}

/**
 * Get organization abbreviation from database settings
 * @param mysqli $conn Database connection
 * @return string Organization abbreviation or 'NIA' as fallback
 */
function get_organization_abbreviation($conn) {
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'organization_name'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'] ?? 'NIA';
    }
    return 'NIA';
}

/**
 * Generate favicon HTML tags from organization logo
 * @param mysqli $conn Database connection
 * @param string $base_path Base path for relative URLs (e.g., '../' for subdirectories)
 * @return string HTML favicon tags
 */
function generate_favicon_tags($conn, $base_path = '') {
    $organization_logo = get_organization_logo($conn);
    
    if (!empty($organization_logo)) {
        $logo_path = $base_path . $organization_logo;
        return '
    <link rel="icon" type="image/png" href="' . htmlspecialchars($logo_path) . '">
    <link rel="shortcut icon" type="image/png" href="' . htmlspecialchars($logo_path) . '">
    <link rel="apple-touch-icon" type="image/png" href="' . htmlspecialchars($logo_path) . '">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="' . htmlspecialchars($logo_path) . '">
    <meta name="msapplication-TileImage" content="' . htmlspecialchars($logo_path) . '">';
    } else {
        $default_path = $base_path . 'assets/images/';
        return '
    <link rel="icon" type="image/x-icon" href="' . $default_path . 'favicon.ico">
    <link rel="icon" type="image/png" href="' . $default_path . 'nia-logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="' . $default_path . 'favicon.ico">
    <link rel="shortcut icon" type="image/png" href="' . $default_path . 'nia-logo.png">
    <link rel="apple-touch-icon" type="image/png" href="' . $default_path . 'nia-logo.png">
    <link rel="apple-touch-icon-precomposed" type="image/png" href="' . $default_path . 'nia-logo.png">
    <meta name="msapplication-TileImage" content="' . $default_path . 'nia-logo.png">';
    }
}

/**
 * Get organization setting value
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function get_organization_setting($conn, $key, $default = '') {
    $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'] ?? $default;
    }
    return $default;
}
?>
