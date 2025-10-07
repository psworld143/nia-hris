<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get filter parameter (employee, faculty, or all)
$filter = $_GET['filter'] ?? 'all';

// Get employees from both tables
$employees = [];

// Get employees from employees table (if filter allows)
if ($filter === 'all' || $filter === 'employee') {
    $employee_query = "SELECT e.id, e.first_name, e.last_name, e.employee_id, e.department, e.employee_type, e.position,
                              ed.employment_type, ed.employment_status
                       FROM employees e
                       LEFT JOIN employee_details ed ON e.id = ed.employee_id
                       WHERE e.is_active = 1 
                       ORDER BY e.first_name, e.last_name";
    $employee_result = mysqli_query($conn, $employee_query);

    if ($employee_result) {
        while ($row = mysqli_fetch_assoc($employee_result)) {
            $row['source_table'] = 'employee'; // Use singular table name to match create-leave-request.php
            // Format employment type for display
            if ($row['employment_status'] && $row['employment_type']) {
                $row['employment_display'] = $row['employment_status'] . ' ' . $row['employment_type'];
            } else if ($row['employment_type']) {
                $row['employment_display'] = $row['employment_type'];
            } else if ($row['employment_status']) {
                $row['employment_display'] = $row['employment_status'];
            } else {
                $row['employment_display'] = 'Not Specified';
            }
            $employees[] = $row;
        }
    }
}

// Get employees erom faculty table (if filter allows)
if ($filter === 'all' || $filter === 'faculty') {
    $faculty_query = "SELECT f.id, e.first_name, e.last_name, f.id as employee_id, e.department, 'faculty' as employee_type, f.position,
                              fd.employment_type, fd.employment_status
                      FROM employees f
                      LEFT JOIN employee_details fd ON f.id = fd.employee_id
                      WHERE f.is_active = 1 
                      ORDER BY e.first_name, e.last_name";
    $faculty_result = mysqli_query($conn, $faculty_query);

    if ($faculty_result) {
        while ($row = mysqli_fetch_assoc($faculty_result)) {
            $row['source_table'] = 'faculty';
            // Format employment type for display
            if ($row['employment_status'] && $row['employment_type']) {
                $row['employment_display'] = $row['employment_status'] . ' ' . $row['employment_type'];
            } else if ($row['employment_type']) {
                $row['employment_display'] = $row['employment_type'];
            } else if ($row['employment_status']) {
                $row['employment_display'] = $row['employment_status'];
            } else {
                $row['employment_display'] = 'Not Specified';
            }
            $employees[] = $row;
        }
    }
}

// Sort all employees by name
usort($employees, function($a, $b) {
    return strcmp($a['first_name'] . ' ' . $a['last_name'], $b['first_name'] . ' ' . $b['last_name']);
});

header('Content-Type: application/json');
echo json_encode($employees);
?>
