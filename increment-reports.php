<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Set page title
$page_title = 'Salary Increment Reports';

// Get user information
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';

// Build query conditions
$where_conditions = ["si.created_at BETWEEN ? AND ?"];
$params = [$start_date, $end_date];
$param_types = "ss";

if (!empty($department)) {
    $where_conditions[] = "e.department = ?";
    $params[] = $department;
    $param_types .= "s";
}

if (!empty($status)) {
    $where_conditions[] = "si.status = ?";
    $params[] = $status;
    $param_types .= "s";
}

$where_clause = implode(' AND ', $where_conditions);

// Get increment reports
$reports_query = "SELECT 
    si.*,
    e.first_name,
    e.last_name,
    e.employee_id as emp_id,
    e.position,
    e.department,
    ss.position_title,
    ss.grade_level,
    approver.first_name as approver_first_name,
    approver.last_name as approver_last_name
    FROM salary_increments si
    LEFT JOIN employees e ON si.employee_id = e.id
    LEFT JOIN salary_structures ss ON si.salary_structure_id = ss.id
    LEFT JOIN employees approver ON si.approved_by = approver.id
    WHERE $where_clause
    ORDER BY si.created_at DESC";

$stmt = mysqli_prepare($conn, $reports_query);
mysqli_stmt_bind_param($stmt, $param_types, ...$params);
mysqli_stmt_execute($stmt);
$reports_result = mysqli_stmt_get_result($stmt);

$reports = [];
while ($row = mysqli_fetch_assoc($reports_result)) {
    $reports[] = $row;
}

// Get summary statistics
$summary_query = "SELECT 
    COUNT(*) as total_increments,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    AVG(increment_percentage) as avg_increment_percentage,
    SUM(increment_amount) as total_increment_amount,
    AVG(current_salary) as avg_current_salary,
    AVG(new_salary) as avg_new_salary
    FROM salary_increments si
    LEFT JOIN employees e ON si.employee_id = e.id
    WHERE $where_clause";

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, $param_types, ...$params);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);

// Get departments for filter
$departments_query = "SELECT DISTINCT department FROM employees WHERE is_active = 1 ORDER BY department";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
while ($row = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $row['department'];
}

// Get monthly trend data
$trend_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count,
    AVG(increment_percentage) as avg_percentage,
    SUM(increment_amount) as total_amount
    FROM salary_increments si
    LEFT JOIN employees e ON si.employee_id = e.id
    WHERE $where_clause
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";

$trend_stmt = mysqli_prepare($conn, $trend_query);
mysqli_stmt_bind_param($trend_stmt, $param_types, ...$params);
mysqli_stmt_execute($trend_stmt);
$trend_result = mysqli_stmt_get_result($trend_stmt);

$trend_data = [];
while ($row = mysqli_fetch_assoc($trend_result)) {
    $trend_data[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Salary Increment Reports</h2>
                <p class="opacity-90">Comprehensive reports and analytics for salary increments</p>
            </div>
            <div class="text-right">
                <button onclick="exportReport()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors mr-2">
                    <i class="fas fa-download mr-2"></i>Export Report
                </button>
                <button onclick="printReport()" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-print mr-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Reports</h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="implemented" <?php echo $status === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
            </select>
        </div>
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Apply Filters
            </button>
        </div>
    </form>
</div>

<!-- Summary Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Increments</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $summary['total_increments']; ?></p>
                <p class="text-xs text-blue-600 mt-1">In selected period</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Approved</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $summary['approved_count']; ?></p>
                <p class="text-xs text-green-600 mt-1"><?php echo $summary['total_increments'] > 0 ? round(($summary['approved_count'] / $summary['total_increments']) * 100, 1) : 0; ?>% approval rate</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Avg. Increment</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo round($summary['avg_increment_percentage'], 2); ?>%</p>
                <p class="text-xs text-purple-600 mt-1">Average percentage</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center text-white text-2xl">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Amount</p>
                <p class="text-2xl font-bold text-gray-900">₱<?php echo number_format($summary['total_increment_amount'], 0); ?></p>
                <p class="text-xs text-yellow-600 mt-1">Increment value</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Monthly Trend Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Increment Trend</h3>
        <div class="h-64">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </div>

    <!-- Status Distribution Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Status Distribution</h3>
        <div class="h-64">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<!-- Detailed Reports Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Detailed Increment Reports</h3>
        <div class="text-sm text-gray-500">
            Showing <?php echo count($reports); ?> records
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Current Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Increment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">New Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-chart-line text-4xl mb-4"></i>
                            <p>No increment reports found for the selected criteria.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                                        <?php echo strtoupper(substr($report['first_name'], 0, 1) . substr($report['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo $report['first_name'] . ' ' . $report['last_name']; ?>
                                        </div>
                                        <div class="text-sm text-gray-500"><?php echo $report['emp_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $report['department']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $report['position']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₱<?php echo number_format($report['current_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">₱<?php echo number_format($report['increment_amount'], 2); ?></div>
                                <div class="text-sm text-green-600"><?php echo $report['increment_percentage']; ?>%</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₱<?php echo number_format($report['new_salary'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($report['increment_type']) {
                                        case 'regular': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'promotion': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'merit': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'cost_of_living': echo 'bg-green-100 text-green-800'; break;
                                        case 'special': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $report['increment_type'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php 
                                    switch($report['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        case 'implemented': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($report['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Monthly Trend Chart
const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
const trendData = <?php echo json_encode($trend_data); ?>;

new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Increment Count',
            data: trendData.map(item => item.count),
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10B981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = {
    labels: ['Approved', 'Pending', 'Rejected', 'Implemented'],
    datasets: [{
        data: [
            <?php echo $summary['approved_count']; ?>,
            <?php echo $summary['pending_count']; ?>,
            <?php echo $summary['rejected_count']; ?>,
            0 // Implemented count would need to be calculated
        ],
        backgroundColor: [
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#3B82F6'
        ],
        borderWidth: 2,
        borderColor: '#fff'
    }]
};

new Chart(statusCtx, {
    type: 'doughnut',
    data: statusData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});

// Export and Print functions
function exportReport() {
    // Implement export functionality
    alert('Export functionality will be implemented');
}

function printReport() {
    window.print();
}

// Auto-hide success/error messages
setTimeout(function() {
    const messages = document.querySelectorAll('.alert');
    messages.forEach(message => {
        message.style.display = 'none';
    });
}, 5000);
</script>

<style>
/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .bg-gradient-to-r {
        background: #10B981 !important;
        color: white !important;
    }
    
    .shadow-lg {
        box-shadow: none !important;
    }
}

/* Custom styles for reports */
.report-card {
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>
