<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || (!in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager']) && $_SESSION['role'] !== 'hr_manager')) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Increment Requests Management';
$user_id = $_SESSION['user_id'];

// Check if increment_requests table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'increment_requests'");
if (mysqli_num_rows($table_check) == 0) {
    header('Location: check-increment-tables.php');
    exit();
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_requests') {
    header('Content-Type: application/json');
    
    $status_filter = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 10);
    $offset = intval($_GET['offset'] ?? 0);
    
    $where_conditions = [];
    $params = [];
    
    if ($status_filter !== 'all') {
        $where_conditions[] = "ir.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(ir.request_number LIKE ? OR employee_name LIKE ? OR ir.department LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT ir.*, 
                     COALESCE(
                         CASE 
                             WHEN ir.employee_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
                             WHEN ir.employee_type = 'employee' THEN CONCAT(f.first_name, ' ', f.last_name)
                         END,
                         'Unknown Employee'
                     ) as employee_name,
                     COALESCE(it.type_name, 'Unknown Type') as type_name,
                     COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'System') as created_by_name
              FROM increment_requests ir
              LEFT JOIN employees e ON ir.employee_id = e.id AND ir.employee_type = 'employee'
              LEFT JOIN employee f ON ir.employee_id = f.id AND ir.employee_type = 'employee'
              LEFT JOIN increment_types it ON ir.increment_type_id = it.id
              LEFT JOIN users u ON ir.created_by = u.id
              $where_clause
              ORDER BY ir.created_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii';
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM increment_requests ir
                    LEFT JOIN employees e ON ir.employee_id = e.id AND ir.employee_type = 'employee'
                    LEFT JOIN employee f ON ir.employee_id = f.id AND ir.employee_type = 'employee'
                    LEFT JOIN increment_types it ON ir.increment_type_id = it.id
                    $where_clause";
    
    $count_stmt = mysqli_prepare($conn, $count_query);
    if (!empty($where_conditions)) {
        $count_params = array_slice($params, 0, -2);
        $count_types = str_repeat('s', count($count_params));
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = mysqli_fetch_assoc($count_result)['total'];
    
    echo json_encode(['requests' => $requests, 'total' => $total]);
    exit();
}

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
    AVG(requested_amount) as avg_amount,
    SUM(CASE WHEN status = 'approved' THEN requested_amount ELSE 0 END) as total_approved_amount
    FROM increment_requests 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)";

$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Increment Requests</h1>
                <p class="text-gray-600">Manage salary increment requests and approvals</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <button onclick="openCreateModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i>New Request
                </button>
                <button onclick="exportRequests()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
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
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_requests'] ?? 0); ?></p>
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
                    <p class="text-sm font-medium text-gray-500">Pending Review</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format(($stats['submitted'] ?? 0) + ($stats['under_review'] ?? 0)); ?></p>
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
                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['approved'] ?? 0); ?></p>
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
                    <p class="text-sm font-medium text-gray-500">Total Approved</p>
                    <p class="text-2xl font-bold text-gray-900">₱<?php echo number_format($stats['total_approved_amount'] ?? 0, 0); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                    <select id="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="all">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="searchInput" placeholder="Search requests..." 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div>
                <button onclick="refreshRequests()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Increment Requests</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="requestsTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- Dynamic content loaded via JavaScript -->
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span id="totalRecords">0</span> results
                </div>
                <div class="flex space-x-2">
                    <button id="prevBtn" onclick="changePage(-1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Previous</button>
                    <button id="nextBtn" onclick="changePage(1)" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 0;
const pageSize = 10;

// Load requests on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRequests();
    
    // Add event listeners
    document.getElementById('statusFilter').addEventListener('change', loadRequests);
    document.getElementById('searchInput').addEventListener('input', debounce(loadRequests, 300));
});

function loadRequests() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    const offset = currentPage * pageSize;
    
    fetch(`?action=get_requests&status=${status}&search=${encodeURIComponent(search)}&limit=${pageSize}&offset=${offset}`)
        .then(response => response.json())
        .then(data => {
            displayRequests(data.requests);
            updatePagination(data.total);
        })
        .catch(error => {
            console.error('Error loading requests:', error);
            showNotification('Error loading requests', 'error');
        });
}

function displayRequests(requests) {
    const tbody = document.getElementById('requestsTableBody');
    tbody.innerHTML = '';
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No requests found</td></tr>';
        return;
    }
    
    requests.forEach(request => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        const statusBadge = getStatusBadge(request.status);
        const formattedDate = new Date(request.created_at).toLocaleDateString();
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${request.request_number}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${request.employee_name}</div>
                <div class="text-sm text-gray-500">${request.department}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${request.type_name}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱${parseFloat(request.requested_amount).toLocaleString()}</td>
            <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formattedDate}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="viewRequest(${request.id})" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                ${request.status === 'submitted' ? `<button onclick="reviewRequest(${request.id})" class="text-green-600 hover:text-green-900">Review</button>` : ''}
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>',
        'submitted': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Submitted</span>',
        'under_review': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Under Review</span>',
        'approved': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>',
        'rejected': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>',
        'cancelled': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Cancelled</span>',
        'on_hold': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">On Hold</span>'
    };
    return badges[status] || status;
}

function updatePagination(total) {
    const start = currentPage * pageSize + 1;
    const end = Math.min((currentPage + 1) * pageSize, total);
    
    document.getElementById('showingStart').textContent = total > 0 ? start : 0;
    document.getElementById('showingEnd').textContent = end;
    document.getElementById('totalRecords').textContent = total;
    
    document.getElementById('prevBtn').disabled = currentPage === 0;
    document.getElementById('nextBtn').disabled = end >= total;
}

function changePage(direction) {
    currentPage += direction;
    if (currentPage < 0) currentPage = 0;
    loadRequests();
}

function refreshRequests() {
    currentPage = 0;
    loadRequests();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function openCreateModal() {
    window.location.href = 'create-increment-request.php';
}

function viewRequest(id) {
    window.location.href = `view-increment-request.php?id=${id}`;
}

function reviewRequest(id) {
    window.location.href = `review-increment-request.php?id=${id}`;
}

function exportRequests() {
    window.location.href = 'export-increment-requests.php';
}

function showNotification(message, type = 'info') {
    // Implementation for notifications
    console.log(`${type}: ${message}`);
}
</script>

