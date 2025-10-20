<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Manage Regularization';

// Get filter parameters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Get eligible employees (6+ months from hire date, not regular)
$employee_where_conditions = [
    "e.is_active = 1",
    "e.hire_date <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)",
    "(rs.name IS NULL OR rs.name != 'Regular')"
];
$employee_params = [];
$employee_types = "";

if ($search) {
    $employee_where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $employee_params[] = $search_param;
    $employee_params[] = $search_param;
    $employee_params[] = $search_param;
    $employee_types .= "sss";
}

if ($department_filter) {
    $employee_where_conditions[] = "e.department_id = ?";
    $employee_params[] = $department_filter;
    $employee_types .= "i";
}

$employee_where_clause = "WHERE " . implode(" AND ", $employee_where_conditions);

$employees_query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email, e.position, 
                           e.department_id, e.hire_date, e.is_active,
                           d.name as department_name,
                           ed.profile_photo, ed.employment_type, ed.job_level,
                           rs.name as regularization_status, rs.color as status_color,
                           er.regularization_review_date, er.regularization_date,
                           DATEDIFF(CURDATE(), e.hire_date) as days_employed,
                           FLOOR(DATEDIFF(CURDATE(), e.hire_date) / 365.25) as years_employed,
                           FLOOR((DATEDIFF(CURDATE(), e.hire_date) % 365.25) / 30.44) as remaining_months,
                           ROUND(DATEDIFF(CURDATE(), e.hire_date) / 30.44, 1) as total_months
                    FROM employees e 
                    LEFT JOIN departments d ON e.department_id = d.id
                    LEFT JOIN employee_details ed ON e.id = ed.employee_id
                    LEFT JOIN employee_regularization er ON e.id = er.employee_id
                    LEFT JOIN regularization_status rs ON er.current_status_id = rs.id
                    $employee_where_clause
                    ORDER BY e.hire_date ASC";

$employee_stmt = mysqli_prepare($conn, $employees_query);
if ($employee_stmt && !empty($employee_params)) {
    mysqli_stmt_bind_param($employee_stmt, $employee_types, ...$employee_params);
    mysqli_stmt_execute($employee_stmt);
    $employee_result = mysqli_stmt_get_result($employee_stmt);
} else {
    $employee_result = mysqli_query($conn, $employees_query);
}

$eligible_employees = [];
while ($row = mysqli_fetch_assoc($employee_result)) {
    $eligible_employees[] = $row;
}

// Get departments for filter
$departments = [];
$dept_query = "SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name";
$dept_result = mysqli_query($conn, $dept_query);
while ($row = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $row;
}

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Regularization</h1>
            <p class="text-gray-600">Track and manage employee regularization eligibility</p>
        </div>
        <div class="flex space-x-3">
            <a href="admin-employee.php" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-users mr-2"></i>Manage Employees
            </a>
        </div>
    </div>
</div>


<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-200">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search by name, email, or ID..."
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['id']); ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 hover:shadow-lg font-medium">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </div>
        
        <div class="flex items-end">
            <a href="manage-regularization.php" class="w-full bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium text-center">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </div>
    </form>
</div>

<!-- Employee Regularization Content -->
<div id="employees-content">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Employees Eligible for Regularization</h3>
                <p class="text-sm text-gray-600">Employees with 6+ months of service who are not yet regular</p>
            </div>
            <div class="text-sm text-gray-500">
                <i class="fas fa-clock mr-2"></i><?php echo count($eligible_employees); ?> employees found
            </div>
        </div>
        
        <?php if (empty($eligible_employees)): ?>
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-check text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No employees eligible for regularization</h3>
                <p class="text-gray-500">All employees are either recently hired (less than 6 months) or already regularized.</p>
            </div>
        <?php else: ?>
            <!-- Employee Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($eligible_employees as $employee): ?>
                    <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg hover:border-green-500 transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full">
                        <!-- Employee Photo and Basic Info -->
                        <div class="text-center mb-4">
                            <?php if (!empty($employee['profile_photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/seait/' . $employee['profile_photo'])): ?>
                                <div class="w-16 h-16 rounded-full overflow-hidden mx-auto mb-3 shadow-lg border-2 border-gray-200 hover:border-green-500 transition-colors">
                                    <img src="../<?php echo htmlspecialchars($employee['profile_photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-lg mx-auto mb-3 shadow-lg">
                                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h4 class="text-lg font-semibold text-gray-900 mb-1">
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                            </h4>
                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($employee['position']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($employee['department_name'] ?? 'No Department'); ?></p>
                        </div>
                        
                        <!-- Employment Status -->
                        <div class="mb-4">
                            <?php if ($employee['regularization_status']): ?>
                                <span class="inline-flex items-center px-3 py-1 text-xs rounded-full font-medium mb-2" 
                                      style="background-color: <?php echo $employee['status_color']; ?>20; color: <?php echo $employee['status_color']; ?>;">
                                    <?php echo $employee['regularization_status']; ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 text-xs rounded-full font-medium mb-2 bg-yellow-100 text-yellow-800">
                                    Pending Regularization
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Employment Details -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Employee ID:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Date of Hire:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($employee['hire_date'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Time Employed:</span>
                                <span class="text-sm font-bold text-green-600">
                                    <?php 
                                    $years = $employee['years_employed'];
                                    $months = $employee['remaining_months'];
                                    
                                    if ($years > 0) {
                                        echo $years . ' year' . ($years > 1 ? 's' : '');
                                        if ($months > 0) {
                                            echo ', ' . $months . ' month' . ($months > 1 ? 's' : '');
                                        }
                                    } else {
                                        echo $employee['total_months'] . ' month' . ($employee['total_months'] > 1 ? 's' : '');
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if ($employee['employment_type']): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Employment Type:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['employment_type']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex space-x-2 mt-auto">
                            <a href="view-employee.php?id=<?php echo encrypt_id($employee['id']); ?>" 
                               class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-2 px-3 rounded text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            <button onclick="processRegularization(<?php echo $employee['id']; ?>, 'employee', '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')" 
                                    class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-3 rounded text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-user-check mr-1"></i>Regularize
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Eligible Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($eligible_employees); ?></p>
                <p class="text-xs text-gray-500">6+ months tenure</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Eligible</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($eligible_employees); ?></p>
                <p class="text-xs text-gray-500">Ready for review</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Overdue Reviews</p>
                <p class="text-2xl font-bold text-gray-900">
                    <?php 
                    $overdue_count = 0;
                    foreach ($eligible_employees as $emp) {
                        if ($emp['regularization_review_date'] && strtotime($emp['regularization_review_date']) < time()) {
                            $overdue_count++;
                        }
                    }
                    echo $overdue_count;
                    ?>
                </p>
                <p class="text-xs text-gray-500">Past due date</p>
            </div>
        </div>
    </div>
</div>


<!-- Custom JavaScript -->
<script>

// Process regularization
function processRegularization(id, type, name) {
    console.log('processRegularization called with:', {id, type, name});
    showRegularizationConfirmModal(id, type, name);
}

// Show regularization confirmation modal with criteria checklist
function showRegularizationConfirmModal(id, type, name) {
    console.log('showRegularizationConfirmModal called with:', {id, type, name});
    
    // Fetch both criteria and evaluation scores in parallel
    const criteriaPromise = fetch('get-regularization-criteria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employee_type=${type}&action=get_criteria`
    }).then(response => response.json());
    
    const evaluationPromise = fetch('get-evaluation-scores.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employee_id=${id}&employee_type=${type}`
    }).then(response => response.json());
    
    // Wait for both requests to complete
    Promise.all([criteriaPromise, evaluationPromise])
        .then(([criteriaData, evaluationData]) => {
            console.log('Criteria data:', criteriaData);
            console.log('Evaluation data:', evaluationData);
            
            if (criteriaData.success) {
                showRegularizationModalWithCriteria(id, type, name, criteriaData.criteria, evaluationData);
            } else {
                console.error('Error from server:', criteriaData.message);
                showToast('Error loading regularization criteria: ' + (criteriaData.message || 'Unknown error'), 'error');
                // Fallback to simple confirmation modal
                showSimpleRegularizationModal(id, type, name);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Error loading data: ' + error.message, 'error');
            // Fallback to simple confirmation modal
            showSimpleRegularizationModal(id, type, name);
        });
}

// Simple regularization modal (fallback)
function showSimpleRegularizationModal(id, type, name) {
    console.log('Showing simple modal for:', {id, type, name});
    
    // Create modal if it doesn't exist
    let modal = document.getElementById('regularizationConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'regularizationConfirmModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50 hidden';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-300 scale-95 opacity-0" id="regularizationModalContent">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Confirm Regularization</h3>
                            <p class="text-gray-600 text-sm">This action cannot be undone</p>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="mb-6">
                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900" id="confirmName">Employee Name</p>
                                    <p class="text-sm text-gray-600" id="confirmType">Employee Type</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-3">
                                <p class="text-sm text-gray-700">
                                    <i class="fas fa-info-circle text-green-500 mr-2"></i>
                                    This will update their status to <strong class="text-green-600">Regular</strong> and record the regularization date.
                                </p>
                            </div>
                        </div>
                        
                        <p class="text-gray-700 text-center">
                            Are you sure you want to process regularization for <strong id="confirmNameText">this person</strong>?
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button onclick="closeRegularizationModal()" 
                                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button onclick="confirmRegularization()" 
                                class="flex-1 px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                            <i class="fas fa-check mr-2"></i>Confirm Regularization
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Update modal content
    document.getElementById('confirmName').textContent = name;
    document.getElementById('confirmNameText').textContent = name;
    document.getElementById('confirmType').textContent = 'Employee';
    
    // Store current regularization data
    modal.dataset.regularizationId = id;
    modal.dataset.regularizationType = type;
    modal.dataset.regularizationName = name;
    
    // Show modal with animation
    modal.classList.remove('hidden');
    setTimeout(() => {
        const content = document.getElementById('regularizationModalContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Show regularization modal with criteria checklist
function showRegularizationModalWithCriteria(id, type, name, criteria, evaluationData = null) {
    console.log('showRegularizationModalWithCriteria called with:', {id, type, name, criteria, evaluationData});
    // Create modal if it doesn't exist
    let modal = document.getElementById('regularizationConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'regularizationConfirmModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50 hidden';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-95 opacity-0" id="regularizationModalContent">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user-check text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Regularization Checklist</h3>
                            <p class="text-gray-600 text-sm">Verify all criteria before proceeding</p>
                        </div>
                    </div>
                    
                    <!-- Employee Info -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900" id="confirmName">Employee Name</p>
                                <p class="text-sm text-gray-600" id="confirmType">Employee Type</p>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-sm text-gray-700">
                                <i class="fas fa-info-circle text-green-500 mr-2"></i>
                                Review and confirm all regularization criteria below.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Evaluation Results Section -->
                    <div id="evaluationResultsSection" class="mb-6 hidden">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-bar text-blue-600 mr-2"></i>Evaluation Results
                        </h4>
                        <div id="evaluationResultsContent" class="bg-blue-50 rounded-lg p-4">
                            <!-- Evaluation results will be populated here -->
                        </div>
                    </div>
                    
                    <!-- Criteria Checklist -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-list-check text-seait-orange mr-2"></i>Regularization Criteria
                        </h4>
                        <div id="criteriaChecklist" class="space-y-3">
                            <!-- Criteria will be dynamically loaded here -->
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button onclick="closeRegularizationModal()" 
                                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button onclick="confirmRegularization()" 
                                class="flex-1 px-4 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium">
                            <i class="fas fa-check mr-2"></i>Confirm Regularization
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Update modal content
    document.getElementById('confirmName').textContent = name;
    document.getElementById('confirmType').textContent = 'Employee';
    
    // Populate evaluation results if available - ONLY for faculty
    const evaluationSection = document.getElementById('evaluationResultsSection');
    const evaluationContent = document.getElementById('evaluationResultsContent');
    
    // Only show evaluation results for faculty members
    if (type === 'faculty' && evaluationData && evaluationData.success) {
        const stats = evaluationData.overall_stats;
        const scores = evaluationData.evaluation_scores;
        
        // Separate scores by evaluation type
        const peerToPeerScores = scores.filter(score => score.evaluation_type === 'peer_to_peer');
        const headToTeacherScores = scores.filter(score => score.evaluation_type === 'head_to_teacher');
        
        let evaluationHtml = '';
        
        // Overall Summary Card
        if (stats.total_evaluations > 0) {
            evaluationHtml += `
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                        Overall Evaluation Summary
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-blue-600">${stats.overall_average}</div>
                            <div class="text-sm text-gray-600">Overall Rating</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-green-600">${stats.total_evaluations}</div>
                            <div class="text-sm text-gray-600">Total Evaluations</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-purple-600">${stats.latest_evaluation_date ? new Date(stats.latest_evaluation_date).toLocaleDateString() : 'N/A'}</div>
                            <div class="text-sm text-gray-600">Latest Evaluation</div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Peer-to-Peer Evaluation Card
        if (peerToPeerScores.length > 0) {
            const peerAvg = peerToPeerScores.reduce((sum, score) => sum + score.average_rating, 0) / peerToPeerScores.length;
            const latestPeer = peerToPeerScores[0]; // Already sorted by date DESC
            
            evaluationHtml += `
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-users text-green-600 mr-2"></i>
                        Peer-to-Peer Evaluation Results
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-green-600">${peerAvg.toFixed(2)}</div>
                            <div class="text-xs text-gray-600">Average Rating</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-blue-600">${peerToPeerScores.length}</div>
                            <div class="text-xs text-gray-600">Total Evaluations</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-purple-600">${latestPeer.max_rating}</div>
                            <div class="text-xs text-gray-600">Highest Rating</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-orange-600">${latestPeer.min_rating}</div>
                            <div class="text-xs text-gray-600">Lowest Rating</div>
                        </div>
                    </div>
                    <div class="mt-3 bg-white rounded-lg p-3">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-calendar text-green-500 mr-1"></i>
                            <strong>Latest Evaluation:</strong> ${new Date(latestPeer.evaluation_date).toLocaleDateString()}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-chart-bar text-green-500 mr-1"></i>
                            <strong>Latest Score:</strong> ${latestPeer.average_rating}/5.0
                        </div>
                    </div>
                </div>
            `;
        } else {
            evaluationHtml += `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-users text-gray-500 mr-2"></i>
                        Peer-to-Peer Evaluation Results
                    </h5>
                    <div class="text-center text-gray-500 py-4">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>No peer-to-peer evaluations found</p>
                    </div>
                </div>
            `;
        }
        
        // Head-to-Teacher Evaluation Card
        if (headToTeacherScores.length > 0) {
            const headAvg = headToTeacherScores.reduce((sum, score) => sum + score.average_rating, 0) / headToTeacherScores.length;
            const latestHead = headToTeacherScores[0]; // Already sorted by date DESC
            
            evaluationHtml += `
                <div class="bg-gradient-to-r from-purple-50 to-violet-50 border border-purple-200 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-user-tie text-purple-600 mr-2"></i>
                        Head-to-Teacher Evaluation Results
                    </h5>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-purple-600">${headAvg.toFixed(2)}</div>
                            <div class="text-xs text-gray-600">Average Rating</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-blue-600">${headToTeacherScores.length}</div>
                            <div class="text-xs text-gray-600">Total Evaluations</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-green-600">${latestHead.max_rating}</div>
                            <div class="text-xs text-gray-600">Highest Rating</div>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <div class="text-xl font-bold text-red-600">${latestHead.min_rating}</div>
                            <div class="text-xs text-gray-600">Lowest Rating</div>
                        </div>
                    </div>
                    <div class="mt-3 bg-white rounded-lg p-3">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-calendar text-purple-500 mr-1"></i>
                            <strong>Latest Evaluation:</strong> ${new Date(latestHead.evaluation_date).toLocaleDateString()}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-chart-bar text-purple-500 mr-1"></i>
                            <strong>Latest Score:</strong> ${latestHead.average_rating}/5.0
                        </div>
                    </div>
                </div>
            `;
        } else {
            evaluationHtml += `
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-user-tie text-gray-500 mr-2"></i>
                        Head-to-Teacher Evaluation Results
                    </h5>
                    <div class="text-center text-gray-500 py-4">
                        <i class="fas fa-exclamation-circle text-2xl mb-2"></i>
                        <p>No head-to-teacher evaluations found</p>
                    </div>
                </div>
            `;
        }
        
        evaluationContent.innerHTML = evaluationHtml;
        evaluationSection.classList.remove('hidden');
    } else {
        evaluationSection.classList.add('hidden');
    }
    
    // Populate criteria checklist
    const criteriaContainer = document.getElementById('criteriaChecklist');
    criteriaContainer.innerHTML = '';
    
    if (criteria && criteria.length > 0) {
        criteria.forEach(criterion => {
            // Check if evaluation requirements are met - ONLY for faculty
            let evaluationStatus = '';
            let evaluationColor = 'gray';
            let evaluationIcon = 'fas fa-question-circle';
            
            if (type === 'faculty' && evaluationData && evaluationData.success && evaluationData.overall_stats.total_evaluations > 0) {
                const overallRating = evaluationData.overall_stats.overall_average;
                const requiredRating = criterion.evaluation_score_min || 0;
                
                if (overallRating >= requiredRating) {
                    evaluationStatus = `✓ Meets requirement (${overallRating}/${requiredRating})`;
                    evaluationColor = 'green';
                    evaluationIcon = 'fas fa-check-circle';
                } else {
                    evaluationStatus = `✗ Below requirement (${overallRating}/${requiredRating})`;
                    evaluationColor = 'red';
                    evaluationIcon = 'fas fa-times-circle';
                }
            } else if (type === 'faculty') {
                evaluationStatus = 'No evaluation data available';
                evaluationColor = 'yellow';
                evaluationIcon = 'fas fa-exclamation-triangle';
            } else {
                // For employees, don't show evaluation status
                evaluationStatus = 'Not applicable for employees';
                evaluationColor = 'gray';
                evaluationIcon = 'fas fa-minus-circle';
            }
            
            const criteriaItem = document.createElement('div');
            criteriaItem.className = 'bg-white border border-gray-200 rounded-lg p-4';
            criteriaItem.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-1">
                        <input type="checkbox" 
                               class="criteria-checkbox w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 focus:ring-2" 
                               id="criteria_${criterion.id}"
                               data-criteria-id="${criterion.id}">
                    </div>
                    <div class="flex-1">
                        <label for="criteria_${criterion.id}" class="block text-sm font-medium text-gray-900 cursor-pointer">
                            ${criterion.criteria_name}
                        </label>
                        ${criterion.criteria_description ? `<p class="text-sm text-gray-600 mt-1">${criterion.criteria_description}</p>` : ''}
                        <div class="mt-2 grid grid-cols-2 gap-4 text-xs text-gray-500">
                            <div><i class="fas fa-calendar mr-1"></i>Min Months: ${criterion.minimum_months}</div>
                            <div><i class="fas fa-star mr-1"></i>Performance: ${criterion.performance_rating_min}/5.0</div>
                            <div><i class="fas fa-clock mr-1"></i>Attendance: ${criterion.attendance_percentage_min}%</div>
                            <div><i class="fas fa-graduation-cap mr-1"></i>Training: ${criterion.training_completion_required == 1 ? 'Required' : 'Optional'}</div>
                        </div>
                        <div class="mt-2 p-2 bg-${evaluationColor}-50 border border-${evaluationColor}-200 rounded">
                            <div class="flex items-center text-xs">
                                <i class="${evaluationIcon} text-${evaluationColor}-600 mr-1"></i>
                                <span class="text-${evaluationColor}-800 font-medium">Evaluation: ${evaluationStatus}</span>
                            </div>
                        </div>
                        ${criterion.additional_requirements ? `<p class="text-xs text-blue-600 mt-2"><i class="fas fa-info-circle mr-1"></i>${criterion.additional_requirements}</p>` : ''}
                    </div>
                </div>
            `;
            criteriaContainer.appendChild(criteriaItem);
        });
    } else {
        criteriaContainer.innerHTML = `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mb-2"></i>
                <p class="text-yellow-800">No regularization criteria found for ${type}s.</p>
                <p class="text-yellow-600 text-sm mt-1">Please set up criteria in the Regularization Criteria management page.</p>
            </div>
        `;
    }
    
    // Store current regularization data
    modal.dataset.regularizationId = id;
    modal.dataset.regularizationType = type;
    modal.dataset.regularizationName = name;
    
    // Show modal with animation
    modal.classList.remove('hidden');
    setTimeout(() => {
        const content = document.getElementById('regularizationModalContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

// Close regularization modal
function closeRegularizationModal() {
    const modal = document.getElementById('regularizationConfirmModal');
    if (modal) {
        const content = document.getElementById('regularizationModalContent');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
}

// Confirm and process regularization
function confirmRegularization() {
    const modal = document.getElementById('regularizationConfirmModal');
    const id = modal.dataset.regularizationId;
    const type = modal.dataset.regularizationType;
    const name = modal.dataset.regularizationName;
    
    console.log('confirmRegularization called with:', {id, type, name});
    
    // Check if this is the simple modal (no criteria) or criteria modal
    const checkboxes = document.querySelectorAll('.criteria-checkbox');
    let checkedCriteriaIds = [];
    
    if (checkboxes.length > 0) {
        // This is the criteria modal - validate criteria
        const checkedBoxes = document.querySelectorAll('.criteria-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            showToast('Please check at least one criteria before proceeding', 'error');
            return;
        }
        
        // Check for low evaluation scores in checked criteria - ONLY for faculty
        let hasLowEvaluation = false;
        let lowEvaluationMessage = '';
        
        if (type === 'faculty') {
            checkedBoxes.forEach(checkbox => {
                const criteriaId = checkbox.dataset.criteriaId;
                const criteriaItem = checkbox.closest('.bg-white');
                const evaluationStatus = criteriaItem.querySelector('.text-red-800');
                
                if (evaluationStatus && evaluationStatus.textContent.includes('✗ Below requirement')) {
                    hasLowEvaluation = true;
                    const criteriaName = criteriaItem.querySelector('label').textContent.trim();
                    lowEvaluationMessage += `\n• ${criteriaName}: ${evaluationStatus.textContent}`;
                }
            });
            
            if (hasLowEvaluation) {
                const confirmed = confirm(`WARNING: Some criteria have evaluation scores below requirements:${lowEvaluationMessage}\n\nDo you want to proceed with regularization anyway?`);
                if (!confirmed) {
                    return;
                }
            }
        }
        // For employees, skip evaluation validation entirely
        
        if (checkedBoxes.length < checkboxes.length) {
            const confirmed = confirm(`You have only checked ${checkedBoxes.length} out of ${checkboxes.length} criteria. Do you want to proceed anyway?`);
            if (!confirmed) {
                return;
            }
        }
        
        // Collect checked criteria IDs
        checkedCriteriaIds = Array.from(checkedBoxes).map(checkbox => checkbox.dataset.criteriaId);
    } else {
        // This is the simple modal - no criteria validation
        console.log('Simple modal - no criteria validation');
    }
    
    // Close modal first
    closeRegularizationModal();
    
    // Show loading state on the original button
    const button = document.querySelector(`button[onclick*="processRegularization(${id}, '${type}'"]`);
    if (button) {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...';
        button.disabled = true;
        
        fetch('process-regularization.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                type: type,
                action: 'regularize',
                criteria_ids: checkedCriteriaIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(name, type);
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showToast(data.message || 'Error processing regularization', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error. Please try again.', 'error');
        })
        .finally(() => {
            // Reset button state
            if (button) {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
        });
    }
}

// Show success modal
function showSuccessModal(name, type) {
    // Create success modal
    const successModal = document.createElement('div');
    successModal.className = 'fixed inset-0 bg-green-600 bg-opacity-20 flex items-center justify-center p-4 z-50';
    successModal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all duration-500 scale-95 opacity-0" id="successModalContent">
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Regularization Successful!</h3>
                <p class="text-gray-600 mb-4">
                    <strong>${name}</strong> has been successfully regularized as a <strong class="text-green-600">Regular Employee</strong>.
                </p>
                <div class="bg-green-50 rounded-lg p-3 mb-4">
                    <p class="text-sm text-green-700">
                        <i class="fas fa-calendar-check mr-2"></i>
                        Regularization date: <strong>${new Date().toLocaleDateString()}</strong>
                    </p>
                </div>
                <div class="text-sm text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Page will refresh automatically in 3 seconds...
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(successModal);
    
    // Animate in
    setTimeout(() => {
        const content = document.getElementById('successModalContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (document.body.contains(successModal)) {
            document.body.removeChild(successModal);
        }
    }, 3000);
}

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 text-white font-medium ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-yellow-500' : 'bg-green-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 4000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Page initialization
    console.log('Manage Regularization page loaded');
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('regularizationConfirmModal');
    if (modal && e.target === modal) {
        closeRegularizationModal();
    }
});
</script>
