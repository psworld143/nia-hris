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

// Handle form submission for updating medical records
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_update) {
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
        
        // Log activity
        logActivity('UPDATE_MEDICAL_RECORD', "Updated medical records for employee ID: $employee_id", $conn);
    } else {
        $error_message = "Error updating medical records: " . mysqli_error($conn);
    }
}

// Get employees with medical information
$query = "SELECT id, employee_id, first_name, last_name, email, phone, department,
          blood_type, medical_conditions, allergies, emergency_contact_name,
          emergency_contact_number, medications, last_medical_checkup, medical_notes
          FROM employees 
          WHERE is_active = 1
          ORDER BY last_name, first_name";

$result = mysqli_query($conn, $query);

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
                <p class="opacity-90">View and manage employee medical information</p>
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
                <p class="text-2xl font-bold text-gray-900"><?php echo count($employees); ?></p>
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
                <p class="text-2xl font-bold text-gray-900">
                    <?php echo count(array_filter($employees, function($e) { return !empty($e['allergies']); })); ?>
                </p>
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
                <p class="text-2xl font-bold text-gray-900">
                    <?php echo count(array_filter($employees, function($e) { return !empty($e['medications']); })); ?>
                </p>
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
                <p class="text-2xl font-bold text-gray-900">
                    <?php 
                    $recent = array_filter($employees, function($e) { 
                        return !empty($e['last_medical_checkup']) && 
                               strtotime($e['last_medical_checkup']) > strtotime('-6 months'); 
                    }); 
                    echo count($recent);
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Employee Medical Records Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Employee Medical Records</h3>
        <div class="flex space-x-2">
            <input type="text" id="searchInput" placeholder="Search employees..." 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewMedicalRecord(<?php echo htmlspecialchars(json_encode($emp)); ?>)" 
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
            </tbody>
        </table>
    </div>
</div>

<!-- View/Edit Modal -->
<div id="medicalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white mb-10">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Medical Record</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="medicalForm" method="POST" class="space-y-4">
                <input type="hidden" name="employee_id" id="employee_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee Name</label>
                        <input type="text" id="employee_name" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Blood Type</label>
                        <select name="blood_type" id="blood_type" <?php echo !$can_update ? 'disabled' : ''; ?>
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Select Blood Type</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medical Conditions</label>
                    <textarea name="medical_conditions" id="medical_conditions" rows="3" <?php echo !$can_update ? 'readonly' : ''; ?>
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List any medical conditions..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Allergies</label>
                    <textarea name="allergies" id="allergies" rows="2" <?php echo !$can_update ? 'readonly' : ''; ?>
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List any allergies..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Medications</label>
                    <textarea name="medications" id="medications" rows="2" <?php echo !$can_update ? 'readonly' : ''; ?>
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="List current medications..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" <?php echo !$can_update ? 'readonly' : ''; ?>
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Phone</label>
                        <input type="tel" name="emergency_phone" id="emergency_phone" <?php echo !$can_update ? 'readonly' : ''; ?>
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Medical Checkup</label>
                    <input type="date" name="last_checkup" id="last_checkup" <?php echo !$can_update ? 'readonly' : ''; ?>
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medical Notes</label>
                    <textarea name="notes" id="notes" rows="3" <?php echo !$can_update ? 'readonly' : ''; ?>
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                              placeholder="Additional medical notes..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Close
                    </button>
                    <?php if ($can_update): ?>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700">
                        Save Changes
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#medicalTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// View medical record
function viewMedicalRecord(employee) {
    document.getElementById('modalTitle').textContent = 'View Medical Record';
    populateForm(employee, false);
    document.getElementById('medicalModal').classList.remove('hidden');
}

// Edit medical record
function editMedicalRecord(employee) {
    document.getElementById('modalTitle').textContent = 'Edit Medical Record';
    populateForm(employee, true);
    document.getElementById('medicalModal').classList.remove('hidden');
}

// Populate form with employee data
function populateForm(employee, editable) {
    document.getElementById('employee_id').value = employee.id;
    document.getElementById('employee_name').value = employee.first_name + ' ' + employee.last_name;
    document.getElementById('blood_type').value = employee.blood_type || '';
    document.getElementById('medical_conditions').value = employee.medical_conditions || '';
    document.getElementById('allergies').value = employee.allergies || '';
    document.getElementById('medications').value = employee.medications || '';
    document.getElementById('emergency_contact').value = employee.emergency_contact_name || '';
    document.getElementById('emergency_phone').value = employee.emergency_contact_number || '';
    document.getElementById('last_checkup').value = employee.last_medical_checkup || '';
    document.getElementById('notes').value = employee.medical_notes || '';
}

// Close modal
function closeModal() {
    document.getElementById('medicalModal').classList.add('hidden');
    document.getElementById('medicalForm').reset();
}
</script>

