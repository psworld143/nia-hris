<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user has permission to view medical records
if (!canViewMedicalRecords()) {
    header('Location: index.php');
    exit();
}

$page_title = 'Medical Records Management';
$can_update = canUpdateMedicalRecords();

// Handle form submission for updating basic medical records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_update && isset($_POST['action']) && $_POST['action'] === 'update_basic') {
    $employee_id = intval($_POST['employee_id']);
    $blood_type = sanitize_input($_POST['blood_type']);
    $medical_conditions = sanitize_input($_POST['medical_conditions']);
    $allergies = sanitize_input($_POST['allergies']);
    $emergency_contact = sanitize_input($_POST['emergency_contact']);
    $emergency_phone = sanitize_input($_POST['emergency_phone']);
    $medications = sanitize_input($_POST['medications']);
    $last_checkup = sanitize_input($_POST['last_checkup']);
    $notes = sanitize_input($_POST['notes']);
    
    // Update employee medical information
    $update_query = "UPDATE employees SET 
                     blood_type = ?,
                     medical_conditions = ?,
                     allergies = ?,
                     emergency_contact_name = ?,
                     emergency_contact_number = ?,
                     medications = ?,
                     last_medical_checkup = ?,
                     medical_notes = ?,
                     updated_at = NOW()
                     WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssssssssi", 
        $blood_type, $medical_conditions, $allergies, 
        $emergency_contact, $emergency_phone, $medications, 
        $last_checkup, $notes, $employee_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Medical records updated successfully!";
        logActivity('UPDATE_MEDICAL_RECORD', "Updated medical records for employee ID: $employee_id", $conn);
    } else {
        $error_message = "Error updating medical records: " . mysqli_error($conn);
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Build WHERE clause
$where_conditions = ["e.is_active = 1"];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($department_filter) {
    $where_conditions[] = "e.department = ?";
    $params[] = $department_filter;
    $types .= 's';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get all departments for filter
$departments_query = "SELECT DISTINCT department FROM employees WHERE is_active = 1 AND department IS NOT NULL ORDER BY department";
$departments_result = mysqli_query($conn, $departments_query);
$departments = [];
while ($dept = mysqli_fetch_assoc($departments_result)) {
    $departments[] = $dept['department'];
}

// Get employees with medical information
$query = "SELECT e.id, e.employee_id, e.first_name, e.last_name, e.email, e.phone, e.department,
          e.blood_type, e.medical_conditions, e.allergies, e.emergency_contact_name,
          e.emergency_contact_number, e.medications, e.last_medical_checkup, e.medical_notes,
          (SELECT COUNT(*) FROM employee_medical_history WHERE employee_id = e.id) as history_count
          FROM employees e
          $where_clause
          ORDER BY e.last_name, e.first_name";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Check for query errors
if (!$result) {
    die("<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg m-4'>
         <strong>Database Error:</strong> " . mysqli_error($conn) . "<br><br>
         <strong>Tip:</strong> You may need to run the migration script first: 
         <a href='migrate-medical-fields.php' class='underline font-bold'>migrate-medical-fields.php</a>
         </div>");
}

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

// Calculate statistics
$total_employees = count($employees);
$with_allergies = count(array_filter($employees, function($e) { return !empty($e['allergies']) && $e['allergies'] !== 'None'; }));
$on_medication = count(array_filter($employees, function($e) { return !empty($e['medications']) && $e['medications'] !== 'None'; }));
$recent_checkups = count(array_filter($employees, function($e) { 
    return !empty($e['last_medical_checkup']) && strtotime($e['last_medical_checkup']) > strtotime('-6 months');
}));

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-heartbeat mr-2"></i>Medical Records Management
                </h2>
                <p class="opacity-90">View, edit, and track employee medical information and histories</p>
            </div>
            <div>
                <?php echo getRoleBadge(getCurrentUserRole()); ?>
            </div>
        </div>
    </div>
</div>

<?php if (isset($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_employees; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-allergies text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">With Allergies</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $with_allergies; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-pills text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">On Medication</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $on_medication; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 ">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-stethoscope text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Recent Checkups</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $recent_checkups; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-filter text-green-500 mr-2"></i>Filters
    </h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-search text-purple-500 mr-1"></i>Search
            </label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Name or ID..." 
                   class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-building text-purple-500 mr-1"></i>Department
            </label>
            <select name="department" 
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-semibold">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="medical-records.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Employee Medical Records Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Employee Medical Records</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="medicalTable">
            <thead class="bg-gradient-to-r from-green-600 to-green-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Blood Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Allergies</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Medications</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Last Checkup</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">History</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-user-md text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-700">No employees found</p>
                            <p class="text-sm text-gray-500 mt-1">Try adjusting your filters</p>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($employees as $emp): ?>
                <tr class="hover:bg-green-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="text-green-600 font-semibold">
                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($emp['employee_id']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo !empty($emp['blood_type']) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo htmlspecialchars($emp['blood_type'] ?: 'N/A'); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate">
                            <?php echo htmlspecialchars($emp['allergies'] ?: 'None'); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate">
                            <?php echo htmlspecialchars($emp['medications'] ?: 'None'); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php 
                        if (!empty($emp['last_medical_checkup'])) {
                            echo date('M d, Y', strtotime($emp['last_medical_checkup']));
                        } else {
                            echo '<span class="text-gray-400">No record</span>';
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                            <i class="fas fa-history mr-1"></i><?php echo $emp['history_count']; ?> records
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewEmployeeMedical(<?php echo $emp['id']; ?>)" 
                                class="text-green-600 hover:text-green-900 mr-3">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button onclick="editMedicalRecord(<?php echo htmlspecialchars(json_encode($emp)); ?>)" 
                                class="text-blue-600 hover:text-blue-900 <?php echo !$can_update ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo !$can_update ? 'disabled title="You do not have permission to edit medical records"' : ''; ?>>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Medical Record Modal -->
<div id="editMedicalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-edit text-green-600 mr-2"></i>Edit Medical Record
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_basic">
            <input type="hidden" name="employee_id" id="edit_employee_id">
            
            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
                <p class="text-sm text-purple-800">
                    <i class="fas fa-user mr-2"></i>
                    <strong id="edit_employee_name"></strong> (<span id="edit_employee_id_display"></span>)
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tint text-red-500 mr-1"></i>Blood Type
                    </label>
                    <select name="blood_type" id="edit_blood_type"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select...</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-check text-blue-500 mr-1"></i>Last Medical Checkup
                    </label>
                    <input type="date" name="last_checkup" id="edit_last_checkup"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-medical text-yellow-500 mr-1"></i>Medical Conditions
                    </label>
                    <textarea name="medical_conditions" id="edit_medical_conditions" rows="3"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="List any medical conditions"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-allergies text-orange-500 mr-1"></i>Allergies
                    </label>
                    <textarea name="allergies" id="edit_allergies" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="List any allergies"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-pills text-purple-500 mr-1"></i>Current Medications
                    </label>
                    <textarea name="medications" id="edit_medications" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="List current medications and dosages"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-shield text-green-500 mr-1"></i>Emergency Contact Name
                    </label>
                    <input type="text" name="emergency_contact" id="edit_emergency_contact"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-green-500 mr-1"></i>Emergency Contact Number
                    </label>
                    <input type="text" name="emergency_phone" id="edit_emergency_phone"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-notes-medical text-blue-500 mr-1"></i>Medical Notes
                    </label>
                    <textarea name="notes" id="edit_notes" rows="3"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                              placeholder="Additional medical notes"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Medical History Modal -->
<div id="viewMedicalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-5xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-heartbeat text-green-600 mr-2"></i>Medical Records & History
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="medicalHistoryContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-green-600"></i>
                <p class="text-gray-600 mt-4">Loading medical records...</p>
            </div>
        </div>
    </div>
</div>

<!-- Delete Medical Record Confirmation Modal -->
<div id="deleteMedicalRecordModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden z-[70]">
    <div class="relative top-20 mx-auto p-0 border w-full max-w-md shadow-2xl rounded-xl bg-white mb-20">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-t-xl p-6">
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-3xl"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-center">Confirm Deletion</h3>
        </div>
        
        <div class="p-6">
            <div class="text-center mb-6">
                <p class="text-gray-700 text-lg mb-2">Are you sure you want to delete this medical record?</p>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded mt-4">
                    <p class="text-sm text-gray-600 mb-1">
                        <strong class="text-gray-900">Employee:</strong> <span id="delete_employee_name" class="text-gray-700"></span>
                    </p>
                    <p class="text-sm text-gray-600 mb-1">
                        <strong class="text-gray-900">Date:</strong> <span id="delete_record_date" class="text-gray-700"></span>
                    </p>
                    <p class="text-sm text-gray-600">
                        <strong class="text-gray-900">Type:</strong> <span id="delete_record_type" class="text-gray-700"></span>
                    </p>
                </div>
                <p class="text-red-600 text-sm font-semibold mt-4">
                    <i class="fas fa-exclamation-circle mr-1"></i>This action cannot be undone!
                </p>
            </div>
            
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button onclick="closeDeleteMedicalRecordModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmDeleteBtn" onclick="deleteMedicalRecord()" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                    <i class="fas fa-trash-alt mr-2"></i>Delete Record
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Medical History Success Modal -->
<div id="addMedicalHistorySuccessModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden z-[70]">
    <div class="relative top-20 mx-auto p-0 border w-full max-w-md shadow-2xl rounded-xl bg-white mb-20">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-t-xl p-6">
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-center">Success!</h3>
        </div>
        
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-red-600 text-4xl"></i>
                </div>
                <p class="text-gray-700 text-lg font-semibold mb-2" id="addMedicalHistorySuccessMessage">Medical history record added successfully!</p>
                <p class="text-gray-600 text-sm" id="addMedicalHistorySuccessDetails">The record has been saved and will appear in the medical history timeline.</p>
            </div>
            
            <div class="flex justify-center pt-4 border-t">
                <button onclick="closeAddMedicalHistorySuccessModal()" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Attachments Modal -->
<div id="viewAttachmentsModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden z-[80]">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-5xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-file-medical text-blue-600 mr-2"></i>Medical Certificate Attachments
            </h3>
            <button onclick="closeViewAttachmentsModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="viewAttachmentsContent" class="max-h-[70vh] overflow-y-auto">
            <!-- Attachments will be loaded here -->
        </div>
    </div>
</div>

<!-- View Full Image Modal -->
<div id="viewFullImageModal" class="fixed inset-0 bg-black bg-opacity-90 overflow-y-auto h-full w-full hidden z-[90]">
    <div class="relative top-10 mx-auto p-6 w-full max-w-6xl mb-10">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white" id="fullImageName">
                <!-- Image name will be loaded here -->
            </h3>
            <button onclick="closeViewFullImageModal()" class="text-white hover:text-gray-300 text-2xl bg-black bg-opacity-50 rounded-full p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="bg-white rounded-lg p-4">
            <img id="fullImageSrc" src="" alt="" class="max-w-full h-auto mx-auto rounded-lg shadow-lg">
        </div>
    </div>
</div>

<!-- Delete Success Modal -->
<div id="deleteSuccessModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full hidden z-[70]">
    <div class="relative top-20 mx-auto p-0 border w-full max-w-md shadow-2xl rounded-xl bg-white mb-20">
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-t-xl p-6">
            <div class="flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-center">Success!</h3>
        </div>
        
        <div class="p-6">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-4xl"></i>
                </div>
                <p class="text-gray-700 text-lg font-semibold mb-2">Medical record deleted successfully!</p>
                <p class="text-gray-600 text-sm">The record has been permanently removed from the system.</p>
            </div>
            
            <div class="flex justify-center pt-4 border-t">
                <button onclick="closeDeleteSuccessModal()" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Medical History Modal -->
<div id="addMedicalHistoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-[60]">
    <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-2xl rounded-xl bg-white mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-plus-circle text-red-600 mr-2"></i>Add Medical History Record
            </h3>
            <button onclick="closeAddMedicalHistoryModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="addMedicalHistoryForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="employee_id" id="add_employee_id">
            
            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-4">
                <p class="text-sm text-purple-800">
                    <i class="fas fa-user mr-2"></i>
                    <strong id="add_employee_name"></strong> (<span id="add_employee_id_display"></span>)
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Record Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar text-red-500 mr-1"></i>Record Date *
                    </label>
                    <input type="date" name="record_date" required 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Record Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-tags text-red-500 mr-1"></i>Record Type *
                    </label>
                    <select name="record_type" required 
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="checkup">Checkup</option>
                        <option value="diagnosis">Diagnosis</option>
                        <option value="treatment">Treatment</option>
                        <option value="vaccination">Vaccination</option>
                        <option value="lab_test">Lab Test</option>
                        <option value="consultation">Consultation</option>
                        <option value="emergency">Emergency</option>
                        <option value="follow_up">Follow-up</option>
                    </select>
                </div>

                <!-- Chief Complaint -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment-medical text-red-500 mr-1"></i>Chief Complaint
                    </label>
                    <input type="text" name="chief_complaint" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Main reason for visit">
                </div>

                <!-- Diagnosis -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-stethoscope text-red-500 mr-1"></i>Diagnosis
                    </label>
                    <textarea name="diagnosis" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Medical diagnosis"></textarea>
                </div>

                <!-- Treatment -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-procedures text-red-500 mr-1"></i>Treatment
                    </label>
                    <textarea name="treatment" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Treatment provided"></textarea>
                </div>

                <!-- Medication -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-pills text-red-500 mr-1"></i>Medication Prescribed
                    </label>
                    <textarea name="medication_prescribed" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Medications and dosage"></textarea>
                </div>

                <!-- Vital Signs Section -->
                <div class="md:col-span-2 border-t pt-4">
                    <h4 class="font-semibold text-gray-700 mb-3">
                        <i class="fas fa-heartbeat text-red-500 mr-2"></i>Vital Signs
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Blood Pressure</label>
                            <input type="text" name="blood_pressure" placeholder="120/80"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" placeholder="72"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" placeholder="36.5"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Respiratory Rate</label>
                            <input type="number" name="respiratory_rate" placeholder="16"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" placeholder="65"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Height (cm)</label>
                            <input type="number" name="height" placeholder="165"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
                        </div>
                    </div>
                </div>

                <!-- Doctor Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-md text-red-500 mr-1"></i>Doctor Name
                    </label>
                    <input type="text" name="doctor_name" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Dr. Juan Dela Cruz">
                </div>

                <!-- Clinic/Hospital -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-hospital text-red-500 mr-1"></i>Clinic/Hospital
                    </label>
                    <input type="text" name="clinic_hospital" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="NIA Health Center">
                </div>

                <!-- Lab Results -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-vial text-red-500 mr-1"></i>Lab Results
                    </label>
                    <textarea name="lab_results" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Laboratory test results"></textarea>
                </div>

                <!-- Follow-up Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-check text-red-500 mr-1"></i>Follow-up Date
                    </label>
                    <input type="date" name="follow_up_date" 
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-notes-medical text-red-500 mr-1"></i>Additional Notes
                    </label>
                    <textarea name="notes" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Any additional observations or notes"></textarea>
                </div>

                <!-- Medical Certificate Attachments -->
                <div class="md:col-span-2 border-t pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-file-medical text-red-500 mr-1"></i>Medical Certificate Attachments
                    </label>
                    <div class="space-y-3">
                        <!-- File Upload Area -->
                        <div class="file-upload-container">
                            <input type="file" name="medical_certificates[]" id="medicalCertificates" multiple 
                                   accept="image/*,.pdf,.doc,.docx"
                                   class="hidden"
                                   onchange="handleMedicalFileSelect(this)">
                            <div class="file-drop-area border-2 border-dashed border-red-300 rounded-lg p-6 text-center cursor-pointer hover:border-red-500 hover:bg-red-50 transition-all"
                                 onclick="document.getElementById('medicalCertificates').click()"
                                 ondrop="handleMedicalFileDrop(event, this)" 
                                 ondragover="handleMedicalDragOver(event)"
                                 ondragleave="handleMedicalDragLeave(event)">
                                <div class="file-upload-content">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-red-400 mb-3"></i>
                                    <p class="text-sm font-semibold text-gray-700 mb-1">
                                        Click to upload or drag and drop medical certificates
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        JPG, PNG, PDF, DOC, DOCX (Max 10MB per file)
                                    </p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Multiple files allowed
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- File Preview List -->
                        <div id="medicalCertificatesPreview" class="space-y-2 hidden">
                            <p class="text-xs font-semibold text-gray-600 mb-2">Selected Files:</p>
                            <div id="medicalCertificatesList" class="space-y-2">
                                <!-- Files will be listed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="closeAddMedicalHistoryModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editMedicalRecord(employee) {
    // Check if user has permission to edit (button should be disabled if not)
    const canUpdate = <?php echo $can_update ? 'true' : 'false'; ?>;
    if (!canUpdate) {
        alert('You do not have permission to edit medical records. Please contact a Super Admin or Nurse.');
        return;
    }
    
    document.getElementById('edit_employee_id').value = employee.id;
    document.getElementById('edit_employee_name').textContent = employee.first_name + ' ' + employee.last_name;
    document.getElementById('edit_employee_id_display').textContent = employee.employee_id;
    document.getElementById('edit_blood_type').value = employee.blood_type || '';
    document.getElementById('edit_medical_conditions').value = employee.medical_conditions || '';
    document.getElementById('edit_allergies').value = employee.allergies || '';
    document.getElementById('edit_medications').value = employee.medications || '';
    document.getElementById('edit_last_checkup').value = employee.last_medical_checkup || '';
    document.getElementById('edit_emergency_contact').value = employee.emergency_contact_name || '';
    document.getElementById('edit_emergency_phone').value = employee.emergency_contact_number || '';
    document.getElementById('edit_notes').value = employee.medical_notes || '';
    
    document.getElementById('editMedicalModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editMedicalModal').classList.add('hidden');
}

function viewEmployeeMedical(employeeId) {
    document.getElementById('viewMedicalModal').classList.remove('hidden');
    
    // Load medical history via AJAX
    fetch(`get-medical-history.php?employee_id=${employeeId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('medicalHistoryContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('medicalHistoryContent').innerHTML = 
                '<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle text-4xl mb-4"></i><p>Error loading medical history</p></div>';
        });
}

function closeViewModal() {
    document.getElementById('viewMedicalModal').classList.add('hidden');
}

// Function to open add medical history modal
// This function is called from the AJAX-loaded content
var currentEmployeeForHistory = null;
var canUpdateMedicalRecords = <?php echo $can_update ? 'true' : 'false'; ?>;

function openAddHistoryModalForEmployee(employeeId, employeeName, employeeIdDisplay) {
    // Check if user has permission to add medical records
    if (!canUpdateMedicalRecords) {
        alert('You do not have permission to add medical records. Please contact a Super Admin or Nurse.');
        return;
    }
    
    // Store employee info
    if (typeof employeeId === 'object') {
        // If passed as object from get-medical-history.php
        currentEmployeeForHistory = employeeId;
    } else {
        // If passed as separate parameters
        currentEmployeeForHistory = {
            id: employeeId,
            name: employeeName,
            employee_id: employeeIdDisplay
        };
    }
    
    // Close view modal
    closeViewModal();
    
    // Set employee info in add modal
    document.getElementById('add_employee_id').value = currentEmployeeForHistory.id;
    document.getElementById('add_employee_name').textContent = currentEmployeeForHistory.name || employeeName;
    document.getElementById('add_employee_id_display').textContent = currentEmployeeForHistory.employee_id || employeeIdDisplay;
    
    // Set default date to today
    document.querySelector('#addMedicalHistoryForm [name="record_date"]').value = new Date().toISOString().split('T')[0];
    
    // Open add modal
    document.getElementById('addMedicalHistoryModal').classList.remove('hidden');
}

function closeAddMedicalHistoryModal() {
    document.getElementById('addMedicalHistoryModal').classList.add('hidden');
    document.getElementById('addMedicalHistoryForm').reset();
    // Reset file preview
    document.getElementById('medicalCertificatesPreview').classList.add('hidden');
    document.getElementById('medicalCertificatesList').innerHTML = '';
    document.getElementById('medicalCertificates').value = '';
}

// Medical Certificate File Upload Functions
function handleMedicalFileSelect(input) {
    const files = Array.from(input.files);
    if (files.length > 0) {
        displayMedicalFilePreview(files);
    }
}

function handleMedicalDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.add('border-red-500', 'bg-red-50');
}

function handleMedicalDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    event.currentTarget.classList.remove('border-red-500', 'bg-red-50');
}

function handleMedicalFileDrop(event, dropArea) {
    event.preventDefault();
    event.stopPropagation();
    dropArea.classList.remove('border-red-500', 'bg-red-50');
    
    const files = Array.from(event.dataTransfer.files);
    if (files.length > 0) {
        const fileInput = document.getElementById('medicalCertificates');
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        displayMedicalFilePreview(files);
    }
}

function displayMedicalFilePreview(files) {
    const previewContainer = document.getElementById('medicalCertificatesPreview');
    const fileList = document.getElementById('medicalCertificatesList');
    
    fileList.innerHTML = '';
    
    files.forEach((file, index) => {
        const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
        const fileType = file.type;
        const isImage = fileType.startsWith('image/');
        const isPDF = fileType === 'application/pdf';
        
        let iconClass = 'fa-file';
        let iconColor = 'text-gray-500';
        
        if (isImage) {
            iconClass = 'fa-file-image';
            iconColor = 'text-blue-500';
        } else if (isPDF) {
            iconClass = 'fa-file-pdf';
            iconColor = 'text-red-500';
        } else if (fileType.includes('word') || fileType.includes('document')) {
            iconClass = 'fa-file-word';
            iconColor = 'text-blue-600';
        }
        
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200';
        fileItem.innerHTML = `
            <div class="flex items-center flex-1 min-w-0">
                <i class="fas ${iconClass} ${iconColor} text-xl mr-3"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${fileSize} MB</p>
                </div>
            </div>
            <button type="button" onclick="removeMedicalFile(${index})" 
                    class="ml-3 text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded transition-colors">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        fileList.appendChild(fileItem);
    });
    
    previewContainer.classList.remove('hidden');
}

function removeMedicalFile(index) {
    const fileInput = document.getElementById('medicalCertificates');
    const files = Array.from(fileInput.files);
    files.splice(index, 1);
    
    const dataTransfer = new DataTransfer();
    files.forEach(file => dataTransfer.items.add(file));
    fileInput.files = dataTransfer.files;
    
    if (files.length > 0) {
        displayMedicalFilePreview(files);
    } else {
        document.getElementById('medicalCertificatesPreview').classList.add('hidden');
        document.getElementById('medicalCertificatesList').innerHTML = '';
    }
}

// Handle add medical history form submission
document.getElementById('addMedicalHistoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    
    fetch('add-medical-history.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close add modal first
            closeAddMedicalHistoryModal();
            
            // Set success message
            let message = 'Medical history record added successfully!';
            let details = 'The record has been saved and will appear in the medical history timeline.';
            
            if (data.attachments_count && data.attachments_count > 0) {
                details = `The record has been saved with ${data.attachments_count} certificate${data.attachments_count > 1 ? 's' : ''} attached.`;
            }
            
            document.getElementById('addMedicalHistorySuccessMessage').textContent = message;
            document.getElementById('addMedicalHistorySuccessDetails').textContent = details;
            
            // Show success modal
            showAddMedicalHistorySuccessModal();
            
            // Reload page after modal is closed
        } else {
            alert('Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error adding medical history record. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Delete Medical Record Functions
var recordToDelete = null;
var currentEmployeeIdForDelete = null;

function confirmDeleteMedicalRecord(recordId, employeeId, employeeName, recordDate, recordType) {
    // Check if user has permission to delete medical records
    if (!canUpdateMedicalRecords) {
        alert('You do not have permission to delete medical records. Please contact a Super Admin or Nurse.');
        return;
    }
    
    recordToDelete = recordId;
    currentEmployeeIdForDelete = employeeId;
    
    // Set modal content
    document.getElementById('delete_employee_name').textContent = employeeName;
    document.getElementById('delete_record_date').textContent = recordDate;
    document.getElementById('delete_record_type').textContent = recordType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    
    // Show modal
    document.getElementById('deleteMedicalRecordModal').classList.remove('hidden');
}

function closeDeleteMedicalRecordModal() {
    document.getElementById('deleteMedicalRecordModal').classList.add('hidden');
    recordToDelete = null;
    currentEmployeeIdForDelete = null;
}

function showAddMedicalHistorySuccessModal() {
    document.getElementById('addMedicalHistorySuccessModal').classList.remove('hidden');
}

function closeAddMedicalHistorySuccessModal() {
    document.getElementById('addMedicalHistorySuccessModal').classList.add('hidden');
    // Reload page to show updated data
    window.location.reload();
}

function showDeleteSuccessModal() {
    document.getElementById('deleteSuccessModal').classList.remove('hidden');
}

function closeDeleteSuccessModal() {
    document.getElementById('deleteSuccessModal').classList.add('hidden');
    
    // Reload the medical history if view modal is open
    const viewModal = document.getElementById('viewMedicalModal');
    if (viewModal && !viewModal.classList.contains('hidden')) {
        // Reload the medical history content
        if (currentEmployeeIdForDelete) {
            viewEmployeeMedical(currentEmployeeIdForDelete);
        } else {
            // Fallback: reload the page
            window.location.reload();
        }
    } else {
        // Reload the page to show updated data
        window.location.reload();
    }
}

function deleteMedicalRecord() {
    if (!recordToDelete) {
        alert('No record selected for deletion');
        return;
    }
    
    // Get delete button and show loading state
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn ? deleteBtn.innerHTML : '';
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';
    }
    
    // Make AJAX request to delete endpoint
    fetch('delete-medical-history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            record_id: recordToDelete
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close confirmation modal
            closeDeleteMedicalRecordModal();
            
            // Show success modal
            showDeleteSuccessModal();
            
            // Reload the medical history if view modal is open after a short delay
            setTimeout(() => {
                const viewModal = document.getElementById('viewMedicalModal');
                if (viewModal && !viewModal.classList.contains('hidden')) {
                    // Reload the medical history content
                    if (currentEmployeeIdForDelete) {
                        viewEmployeeMedical(currentEmployeeIdForDelete);
                    } else {
                        // Fallback: reload the page
                        window.location.reload();
                    }
                } else {
                    // Reload the page to show updated data
                    window.location.reload();
                }
            }, 1500); // Wait 1.5 seconds before reloading
        } else {
            alert('Error: ' + (data.message || 'Failed to delete medical record'));
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            }
        }
    })
    .catch(error => {
        alert('Error deleting medical record. Please try again.');
        console.error('Error:', error);
        if (deleteBtn) {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalText;
        }
    });
}

// Close modal when clicking outside
document.getElementById('deleteMedicalRecordModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteMedicalRecordModal();
    }
});

document.getElementById('addMedicalHistorySuccessModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddMedicalHistorySuccessModal();
    }
});

document.getElementById('deleteSuccessModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteSuccessModal();
    }
});

// View Medical Attachments Modal Functions
function viewMedicalAttachments(recordId, attachments) {
    if (!attachments || attachments.length === 0) {
        alert('No attachments available for this record.');
        return;
    }
    
    const modal = document.getElementById('viewAttachmentsModal');
    const content = document.getElementById('viewAttachmentsContent');
    
    // Build attachments HTML
    let attachmentsHTML = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    
    attachments.forEach((attachment, index) => {
        const fileExt = attachment.file_name.split('.').pop().toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExt);
        const isPDF = fileExt === 'pdf';
        const isDoc = ['doc', 'docx'].includes(fileExt);
        
        let iconClass = 'fa-file';
        let iconColor = 'text-gray-500';
        
        if (isImage) {
            iconClass = 'fa-file-image';
            iconColor = 'text-blue-500';
        } else if (isPDF) {
            iconClass = 'fa-file-pdf';
            iconColor = 'text-red-500';
        } else if (isDoc) {
            iconClass = 'fa-file-word';
            iconColor = 'text-blue-600';
        }
        
        const fileSizeMB = (attachment.file_size / 1024 / 1024).toFixed(2);
        
        attachmentsHTML += `
            <div class="bg-white border-2 border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center flex-1 min-w-0">
                        <i class="fas ${iconClass} ${iconColor} text-2xl mr-3"></i>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">${escapeHtml(attachment.file_name)}</p>
                            <p class="text-sm text-gray-500">${fileSizeMB} MB</p>
                        </div>
                    </div>
                </div>
                ${isImage ? `
                    <div class="mb-3 rounded-lg overflow-hidden border border-gray-200">
                        <img src="${escapeHtml(attachment.file_path)}" 
                             alt="${escapeHtml(attachment.file_name)}"
                             class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                             onclick="viewFullImage('${escapeHtml(attachment.file_path)}', '${escapeHtml(attachment.file_name)}')">
                    </div>
                ` : ''}
                <div class="flex gap-2">
                    <a href="${escapeHtml(attachment.file_path)}" 
                       target="_blank"
                       class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors text-center">
                        <i class="fas fa-external-link-alt mr-1"></i>View
                    </a>
                    <a href="${escapeHtml(attachment.file_path)}" 
                       download="${escapeHtml(attachment.file_name)}"
                       class="flex-1 px-3 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors text-center">
                        <i class="fas fa-download mr-1"></i>Download
                    </a>
                </div>
            </div>
        `;
    });
    
    attachmentsHTML += '</div>';
    content.innerHTML = attachmentsHTML;
    
    modal.classList.remove('hidden');
}

function closeViewAttachmentsModal() {
    document.getElementById('viewAttachmentsModal').classList.add('hidden');
}

function viewFullImage(imagePath, imageName) {
    const modal = document.getElementById('viewFullImageModal');
    document.getElementById('fullImageSrc').src = imagePath;
    document.getElementById('fullImageName').textContent = imageName;
    modal.classList.remove('hidden');
}

function closeViewFullImageModal() {
    document.getElementById('viewFullImageModal').classList.add('hidden');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals when clicking outside
document.getElementById('viewAttachmentsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewAttachmentsModal();
    }
});

document.getElementById('viewFullImageModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewFullImageModal();
    }
});
</script>
