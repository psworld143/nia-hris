<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource or hr_manager role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Get selected semester from form
$selected_semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Pagination variables
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Initialize variables with default values
$teacher_insights = [];
$pattern_insights = [];
$department_insights = [];
$total_evaluations = 0;
$semester_evaluations = 0;
$total_teachers = 0;
$total_students = 0;
$total_subjects = 0;
$avg_rating = 0;

// Get available semesters for dropdown
$semesters_query = "SELECT id, name, academic_year FROM semesters WHERE status = 'active' ORDER BY start_date DESC";
$semesters_result = mysqli_query($conn, $semesters_query);

// Get available academic years
$years_query = "SELECT DISTINCT academic_year FROM semesters WHERE status = 'active' ORDER BY academic_year DESC";
$years_result = mysqli_query($conn, $years_query);

// Base date filters
$current_month = date('Y-m');
$current_year = date('Y');

// Semester-specific date range
$semester_start_date = null;
$semester_end_date = null;
if ($selected_semester > 0) {
    $semester_dates_query = "SELECT start_date, end_date FROM semesters WHERE id = ?";
    $stmt = mysqli_prepare($conn, $semester_dates_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $selected_semester);
        mysqli_stmt_execute($stmt);
        $semester_dates_result = mysqli_stmt_get_result($stmt);
        if ($semester_row = mysqli_fetch_assoc($semester_dates_result)) {
            $semester_start_date = $semester_row['start_date'];
            $semester_end_date = $semester_row['end_date'];
        }
    }
}

// Get basic evaluation statistics
$stats_query = "SELECT 
                COUNT(*) as total_evaluations,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_evaluations,
                AVG(CASE WHEN er.rating_value IS NOT NULL THEN er.rating_value END) as avg_rating,
                COUNT(DISTINCT es.evaluatee_id) as evaluated_teachers
                FROM evaluation_sessions es
                LEFT JOIN evaluation_responses er ON es.id = er.evaluation_session_id
                WHERE es.evaluatee_type = 'teacher'";

if ($selected_semester > 0 && $semester_start_date && $semester_end_date) {
    $stats_query .= " AND es.evaluation_date BETWEEN ? AND ?";
    $stmt = mysqli_prepare($conn, $stats_query);
    mysqli_stmt_bind_param($stmt, "ss", $semester_start_date, $semester_end_date);
    mysqli_stmt_execute($stmt);
    $stats_result = mysqli_stmt_get_result($stmt);
} else {
    $stats_result = mysqli_query($conn, $stats_query);
}

if ($stats_result && $stats_row = mysqli_fetch_assoc($stats_result)) {
    $total_evaluations = $stats_row['total_evaluations'];
    $semester_evaluations = $stats_row['completed_evaluations'];
    $avg_rating = round($stats_row['avg_rating'] ?? 0, 2);
    $total_teachers = $stats_row['evaluated_teachers'];
}

// Get total teachers
$teachers_query = "SELECT COUNT(*) as total FROM employees WHERE is_active = 1";
$teachers_result = mysqli_query($conn, $teachers_query);
if ($teachers_row = mysqli_fetch_assoc($teachers_result)) {
    $total_teachers = $teachers_row['total'];
}

// Get total students
$students_query = "SELECT COUNT(*) as total FROM students WHERE is_active = 1";
$students_result = mysqli_query($conn, $students_query);
if ($students_row = mysqli_fetch_assoc($students_result)) {
    $total_students = $students_row['total'];
}

// Get department performance
$department_stats_query = "SELECT 
                          e.department,
                          COUNT(DISTINCT es.evaluatee_id) as teacher_count,
                          AVG(CASE WHEN er.rating_value IS NOT NULL THEN er.rating_value END) as avg_rating,
                          COUNT(es.id) as evaluation_count
                          FROM employees f
                          LEFT JOIN evaluation_sessions es ON f.id = es.evaluatee_id AND es.evaluatee_type = 'teacher'
                          LEFT JOIN evaluation_responses er ON es.id = er.evaluation_session_id
                          WHERE f.is_active = 1";

if ($selected_semester > 0 && $semester_start_date && $semester_end_date) {
    $department_stats_query .= " AND es.evaluation_date BETWEEN ? AND ?";
    $department_stats_query .= " GROUP BY e.department ORDER BY avg_rating DESC";
    $stmt = mysqli_prepare($conn, $department_stats_query);
    mysqli_stmt_bind_param($stmt, "ss", $semester_start_date, $semester_end_date);
    mysqli_stmt_execute($stmt);
    $department_stats_result = mysqli_stmt_get_result($stmt);
} else {
    $department_stats_query .= " GROUP BY e.department ORDER BY avg_rating DESC";
    $department_stats_result = mysqli_query($conn, $department_stats_query);
}

$department_stats = [];
while ($dept_row = mysqli_fetch_assoc($department_stats_result)) {
    $department_stats[] = $dept_row;
}

// Get teacher performance data
$teacher_performance_query = "SELECT 
                             f.id, e.first_name, e.last_name, e.department,
                             AVG(CASE WHEN er.rating_value IS NOT NULL THEN er.rating_value END) as avg_rating,
                             COUNT(es.id) as evaluation_count,
                             COUNT(CASE WHEN er.rating_value IS NOT NULL THEN 1 END) as response_count
                             FROM employees f
                             LEFT JOIN evaluation_sessions es ON f.id = es.evaluatee_id AND es.evaluatee_type = 'teacher'
                             LEFT JOIN evaluation_responses er ON es.id = er.evaluation_session_id
                             WHERE f.is_active = 1";

if ($selected_semester > 0 && $semester_start_date && $semester_end_date) {
    $teacher_performance_query .= " AND es.evaluation_date BETWEEN ? AND ?";
    $teacher_performance_query .= " GROUP BY f.id ORDER BY avg_rating DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $teacher_performance_query);
    mysqli_stmt_bind_param($stmt, "ssii", $semester_start_date, $semester_end_date, $items_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $teacher_performance_result = mysqli_stmt_get_result($stmt);
} else {
    $teacher_performance_query .= " GROUP BY f.id ORDER BY avg_rating DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $teacher_performance_query);
    mysqli_stmt_bind_param($stmt, "ii", $items_per_page, $offset);
    mysqli_stmt_execute($stmt);
    $teacher_performance_result = mysqli_stmt_get_result($stmt);
}

$teacher_performance = [];
while ($teacher_row = mysqli_fetch_assoc($teacher_performance_result)) {
    $teacher_performance[] = $teacher_row;
}

// Get total count for pagination
$teacher_count_query = "SELECT COUNT(*) as total FROM employees WHERE is_active = 1";
$teacher_count_result = mysqli_query($conn, $teacher_count_query);
$teacher_count_row = mysqli_fetch_assoc($teacher_count_result);
$total_teacher_pages = ceil($teacher_count_row['total'] / $items_per_page);

// Include the shared header
include 'includes/header.php';
?>

<div class="mb-6 sm:mb-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-seait-dark mb-2">Reports & Analytics</h1>
    <p class="text-sm sm:text-base text-gray-600">Comprehensive evaluation reports and performance analytics</p>
</div>

<!-- Report Filters -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Filters</h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
            <select name="semester" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-seait-orange">
                <option value="0">All Semesters</option>
                <?php while ($semester = mysqli_fetch_assoc($semesters_result)): ?>
                <option value="<?php echo $semester['id']; ?>" <?php echo $selected_semester == $semester['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($semester['name'] . ' ' . $semester['academic_year']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
            <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-seait-orange">
                <?php while ($year = mysqli_fetch_assoc($years_result)): ?>
                <option value="<?php echo $year['academic_year']; ?>" <?php echo $selected_year == $year['academic_year'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($year['academic_year']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
            <select name="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-seait-orange">
                <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Detailed</option>
                <option value="performance" <?php echo $report_type === 'performance' ? 'selected' : ''; ?>>Performance</option>
                <option value="training" <?php echo $report_type === 'training' ? 'selected' : ''; ?>>Training</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full bg-seait-orange text-white px-4 py-2 rounded-md hover:bg-orange-600 transition-colors">
                <i class="fas fa-filter mr-2"></i>Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Key Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-chart-line text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Evaluations</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_evaluations); ?></p>
                <p class="text-xs text-green-600"><?php echo number_format($semester_evaluations); ?> completed</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-star text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Average Rating</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((float)$avg_rating, 1); ?>/5.0</p>
                <p class="text-xs text-green-600">Overall performance</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Teachers</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_teachers); ?></p>
                <p class="text-xs text-purple-600">Active faculty</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center">
            <div class="p-3 bg-orange-100 rounded-full">
                <i class="fas fa-graduation-cap text-orange-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Students</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_students); ?></p>
                <p class="text-xs text-orange-600">Active students</p>
            </div>
        </div>
    </div>
</div>

<!-- Department Performance -->
<?php if (!empty($department_stats)): ?>
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Department Performance</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teachers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evaluations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($department_stats as $dept): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo number_format($dept['teacher_count']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo number_format($dept['evaluation_count']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo number_format((float)$dept['avg_rating'], 1); ?>/5.0
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-seait-orange h-2 rounded-full" style="width: <?php echo min(100, (float)$dept['avg_rating'] * 20); ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500">
                                <?php echo number_format((float)$dept['avg_rating'] * 20, 1); ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Teacher Performance -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Teacher Performance Rankings</h3>
        <div class="flex space-x-2">
            <a href="export_teacher_ratings.php" class="bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                <i class="fas fa-download mr-2"></i>Export
            </a>
        </div>
    </div>

    <?php if (!empty($teacher_performance)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evaluations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($teacher_performance as $index => $teacher): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #<?php echo $offset + $index + 1; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($teacher['department']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo number_format($teacher['evaluation_count']); ?>
                        <span class="text-xs text-gray-500">(<?php echo number_format($teacher['response_count']); ?> responses)</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo number_format((float)$teacher['avg_rating'], 1); ?>/5.0
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-seait-orange h-2 rounded-full" style="width: <?php echo min(100, (float)$teacher['avg_rating'] * 20); ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500">
                                <?php echo number_format((float)$teacher['avg_rating'] * 20, 1); ?>%
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="view-teacher-suggestions.php?teacher_id=<?php echo safe_encrypt_id($teacher['id']); ?>" 
                           class="text-seait-orange hover:text-orange-600">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_teacher_pages > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>&report_type=<?php echo $report_type; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_teacher_pages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?>&semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>&report_type=<?php echo $report_type; ?>" 
               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page ? 'z-10 bg-seait-orange border-seait-orange text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_teacher_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>&report_type=<?php echo $report_type; ?>" 
               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="text-center py-8">
        <i class="fas fa-chart-bar text-gray-300 text-4xl mb-4"></i>
        <p class="text-gray-500">No performance data available for the selected period.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Export Options -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Reports</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="export_reports.php?type=overview&semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>" 
           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <div class="text-center">
                <i class="fas fa-chart-pie text-blue-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Overview Report</p>
                <p class="text-xs text-gray-500">PDF Export</p>
            </div>
        </a>

        <a href="export_reports.php?type=detailed&semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>" 
           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <div class="text-center">
                <i class="fas fa-file-alt text-green-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Detailed Report</p>
                <p class="text-xs text-gray-500">Excel Export</p>
            </div>
        </a>

        <a href="export_teacher_ratings.php?semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>" 
           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <div class="text-center">
                <i class="fas fa-users text-purple-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Teacher Ratings</p>
                <p class="text-xs text-gray-500">CSV Export</p>
            </div>
        </a>

        <a href="export_suggestions.php?semester=<?php echo $selected_semester; ?>&year=<?php echo $selected_year; ?>" 
           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <div class="text-center">
                <i class="fas fa-lightbulb text-orange-600 text-2xl mb-2"></i>
                <p class="text-sm font-medium text-gray-900">Training Suggestions</p>
                <p class="text-xs text-gray-500">Excel Export</p>
            </div>
        </a>
    </div>
</div>

<?php
// Include the shared footer
?>
