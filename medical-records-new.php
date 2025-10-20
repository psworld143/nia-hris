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
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
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
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total Employees</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_employees; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
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
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
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
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
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
        <i class="fas fa-filter text-purple-500 mr-2"></i>Filters
    </h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-search text-purple-500 mr-1"></i>Search
            </label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Name or ID..." 
                   class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-building text-purple-500 mr-1"></i>Department
            </label>
            <select name="department" 
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($dept); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors font-semibold">
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
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700">
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
                <tr class="hover:bg-purple-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="text-purple-600 font-semibold">
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
                                class="text-purple-600 hover:text-purple-900 mr-3">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <?php if ($can_update): ?>
                        <button onclick="editMedicalRecord(<?php echo htmlspecialchars(json_encode($emp)); ?>)" 
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php endif; ?>
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
                <i class="fas fa-edit text-purple-600 mr-2"></i>Edit Medical Record
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
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
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
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-medical text-yellow-500 mr-1"></i>Medical Conditions
                    </label>
                    <textarea name="medical_conditions" id="edit_medical_conditions" rows="3"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List any medical conditions"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-allergies text-orange-500 mr-1"></i>Allergies
                    </label>
                    <textarea name="allergies" id="edit_allergies" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List any allergies"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-pills text-purple-500 mr-1"></i>Current Medications
                    </label>
                    <textarea name="medications" id="edit_medications" rows="2"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List current medications and dosages"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user-shield text-green-500 mr-1"></i>Emergency Contact Name
                    </label>
                    <input type="text" name="emergency_contact" id="edit_emergency_contact"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-green-500 mr-1"></i>Emergency Contact Number
                    </label>
                    <input type="text" name="emergency_phone" id="edit_emergency_phone"
                           class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-notes-medical text-blue-500 mr-1"></i>Medical Notes
                    </label>
                    <textarea name="notes" id="edit_notes" rows="3"
                              class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="Additional medical notes"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
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
                <i class="fas fa-heartbeat text-purple-600 mr-2"></i>Medical Records & History
            </h3>
            <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="medicalHistoryContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i>
                <p class="text-gray-600 mt-4">Loading medical records...</p>
            </div>
        </div>
    </div>
</div>

<script>
function editMedicalRecord(employee) {
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
</script>

<?php include 'includes/footer.php'; ?>

