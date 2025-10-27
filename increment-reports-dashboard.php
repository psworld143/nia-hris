<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: index.php');
    exit();
}

$page_title = 'Increment Reports Dashboard';

// Get date range from request or default to current year
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

// Summary Statistics
$summary_query = "SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
    COUNT(CASE WHEN status IN ('submitted', 'under_review') THEN 1 END) as pending_count,
    AVG(requested_amount) as avg_requested_amount,
    SUM(CASE WHEN status = 'approved' THEN requested_amount ELSE 0 END) as total_approved_amount,
    AVG(CASE WHEN status = 'approved' AND approved_at IS NOT NULL AND submitted_at IS NOT NULL 
        THEN DATEDIFF(approved_at, submitted_at) END) as avg_approval_days
    FROM increment_requests 
    WHERE created_at BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Requests by Department
$dept_query = "SELECT 
    department,
    COUNT(*) as request_count,
    AVG(requested_amount) as avg_amount,
    SUM(CASE WHEN status = 'approved' THEN requested_amount ELSE 0 END) as approved_amount
    FROM increment_requests 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY department
    ORDER BY request_count DESC";

$dept_stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($dept_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($dept_stmt);
$dept_data = mysqli_fetch_all(mysqli_stmt_get_result($dept_stmt), MYSQLI_ASSOC);

// Requests by Type
$type_query = "SELECT 
    it.type_name,
    COUNT(*) as request_count,
    AVG(ir.requested_amount) as avg_amount,
    COUNT(CASE WHEN ir.status = 'approved' THEN 1 END) as approved_count
    FROM increment_requests ir
    JOIN increment_types it ON ir.increment_type_id = it.id
    WHERE ir.created_at BETWEEN ? AND ?
    GROUP BY it.id, it.type_name
    ORDER BY request_count DESC";

$type_stmt = mysqli_prepare($conn, $type_query);
mysqli_stmt_bind_param($type_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($type_stmt);
$type_data = mysqli_fetch_all(mysqli_stmt_get_result($type_stmt), MYSQLI_ASSOC);

// Monthly Trends
$trend_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as request_count,
    AVG(requested_amount) as avg_amount,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count
    FROM increment_requests 
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";

$trend_stmt = mysqli_prepare($conn, $trend_query);
mysqli_stmt_bind_param($trend_stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($trend_stmt);
$trend_data = mysqli_fetch_all(mysqli_stmt_get_result($trend_stmt), MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Increment Reports Dashboard</h1>
                <p class="text-gray-600">Comprehensive analytics and reporting for increment requests</p>
            </div>
            <div class="mt-4 md:mt-0">
                <form method="GET" class="flex space-x-3">
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        Update Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Requests</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['total_requests']); ?></p>
                    <p class="text-xs text-blue-600 mt-1">
                        <?php 
                        $approval_rate = $summary['total_requests'] > 0 ? 
                            ($summary['approved_count'] / $summary['total_requests']) * 100 : 0;
                        echo number_format($approval_rate, 1) . '% approval rate';
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['approved_count']); ?></p>
                    <p class="text-xs text-green-600 mt-1">
                        ₱<?php echo number_format($summary['total_approved_amount'], 0); ?> total
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($summary['pending_count']); ?></p>
                    <p class="text-xs text-yellow-600 mt-1">
                        <?php echo number_format($summary['avg_approval_days'] ?? 0, 1); ?> avg days
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Average Request</p>
                    <p class="text-2xl font-bold text-gray-900">₱<?php echo number_format($summary['avg_requested_amount'] ?? 0, 0); ?></p>
                    <p class="text-xs text-purple-600 mt-1">per request</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Monthly Trends Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Trends</h3>
            <div class="h-64">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>

        <!-- Requests by Type Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Requests by Type</h3>
            <div class="h-64">
                <canvas id="typeChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Analysis -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Department Analysis</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dept_data as $dept): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($dept['department']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo number_format($dept['request_count']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ₱<?php echo number_format($dept['avg_amount'], 0); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ₱<?php echo number_format($dept['approved_amount'], 0); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $performance = $dept['request_count'] > 0 ? 
                                ($dept['approved_amount'] / ($dept['avg_amount'] * $dept['request_count'])) * 100 : 0;
                            ?>
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min($performance, 100); ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo number_format($performance, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Export Reports</h3>
        <div class="flex flex-wrap gap-4">
            <button onclick="exportToPDF()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-file-pdf mr-2"></i>Export to PDF
            </button>
            <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-file-excel mr-2"></i>Export to Excel
            </button>
            <button onclick="exportToCSV()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-file-csv mr-2"></i>Export to CSV
            </button>
            <button onclick="printReport()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-print mr-2"></i>Print Report
            </button>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const trendsData = <?php echo json_encode($trend_data); ?>;
    
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: trendsData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Total Requests',
                data: trendsData.map(item => item.request_count),
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }, {
                label: 'Approved',
                data: trendsData.map(item => item.approved_count),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Requests by Type Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    const typeData = <?php echo json_encode($type_data); ?>;
    
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: typeData.map(item => item.type_name),
            datasets: [{
                data: typeData.map(item => item.request_count),
                backgroundColor: [
                    '#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', 
                    '#EF4444', '#06B6D4', '#84CC16', '#F97316'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
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
});

function exportToPDF() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export-increment-reports.php?format=pdf&start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function exportToExcel() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export-increment-reports.php?format=excel&start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function exportToCSV() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export-increment-reports.php?format=csv&start_date=${startDate}&end_date=${endDate}`, '_blank');
}

function printReport() {
    window.print();
}
</script>

