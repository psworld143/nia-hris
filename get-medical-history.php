<?php
/**
 * Get Medical History for Employee
 * AJAX endpoint to load comprehensive medical records and timeline
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/roles.php';

// Check if user has permission
if (!canViewMedicalRecords()) {
    echo '<div class="text-center py-8 text-red-600">
            <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
            <p>Unauthorized access</p>
          </div>';
    exit();
}

$employee_id = intval($_GET['employee_id'] ?? 0);
$can_update = canUpdateMedicalRecords();

if (!$employee_id) {
    echo '<div class="text-center py-8 text-red-600">
            <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
            <p>Invalid employee ID</p>
          </div>';
    exit();
}

// Get employee basic info
$emp_query = "SELECT id, employee_id, first_name, last_name, department,
              blood_type, medical_conditions, allergies, medications,
              last_medical_checkup, medical_notes, emergency_contact_name,
              emergency_contact_number
              FROM employees WHERE id = ?";
$emp_stmt = mysqli_prepare($conn, $emp_query);
mysqli_stmt_bind_param($emp_stmt, "i", $employee_id);
mysqli_stmt_execute($emp_stmt);
$emp_result = mysqli_stmt_get_result($emp_stmt);
$employee = mysqli_fetch_assoc($emp_result);

if (!$employee) {
    echo '<div class="text-center py-8 text-red-600">
            <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
            <p>Employee not found</p>
          </div>';
    exit();
}

// Get medical history records
$history_query = "SELECT * FROM employee_medical_history 
                  WHERE employee_id = ? 
                  ORDER BY record_date DESC, created_at DESC";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $employee_id);
mysqli_stmt_execute($history_stmt);
$history_result = mysqli_stmt_get_result($history_stmt);
$medical_history = [];
while ($record = mysqli_fetch_assoc($history_result)) {
    $medical_history[] = $record;
}

?>

<!-- Employee Header -->
<div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mr-4">
                <span class="text-purple-600 font-bold text-xl">
                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                </span>
            </div>
            <div>
                <h4 class="text-2xl font-bold"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                <p class="opacity-90"><?php echo htmlspecialchars($employee['employee_id']); ?> • <?php echo htmlspecialchars($employee['department']); ?></p>
            </div>
        </div>
        <?php if ($can_update): ?>
        <button onclick="parent.openAddHistoryModalForEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', '<?php echo htmlspecialchars($employee['employee_id']); ?>')" 
                class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors font-semibold">
            <i class="fas fa-plus mr-2"></i>Add History Record
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Current Medical Status -->
<div class="bg-white rounded-lg border-2 border-purple-200 p-6 mb-6">
    <h4 class="text-lg font-bold text-gray-900 mb-4">
        <i class="fas fa-clipboard-check text-purple-600 mr-2"></i>Current Medical Status
    </h4>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-xs text-gray-600 mb-1">Blood Type</p>
            <p class="font-semibold text-gray-900"><?php echo $employee['blood_type'] ?: 'N/A'; ?></p>
        </div>
        <div>
            <p class="text-xs text-gray-600 mb-1">Last Checkup</p>
            <p class="font-semibold text-gray-900">
                <?php echo $employee['last_medical_checkup'] ? date('M d, Y', strtotime($employee['last_medical_checkup'])) : 'No record'; ?>
            </p>
        </div>
        <div class="md:col-span-2">
            <p class="text-xs text-gray-600 mb-1">Allergies</p>
            <p class="font-semibold text-gray-900"><?php echo $employee['allergies'] ?: 'None'; ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="text-xs text-gray-600 mb-1">Medical Conditions</p>
            <p class="font-semibold text-gray-900"><?php echo $employee['medical_conditions'] ?: 'None'; ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="text-xs text-gray-600 mb-1">Current Medications</p>
            <p class="font-semibold text-gray-900"><?php echo $employee['medications'] ?: 'None'; ?></p>
        </div>
    </div>
</div>

<!-- Medical History Timeline -->
<div class="bg-white rounded-lg border-2 border-purple-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-bold text-gray-900">
            <i class="fas fa-history text-purple-600 mr-2"></i>Medical History Timeline
        </h4>
        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">
            <?php echo count($medical_history); ?> Records
        </span>
    </div>

    <?php if (empty($medical_history)): ?>
    <div class="text-center py-12">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-notes-medical text-gray-400 text-3xl"></i>
        </div>
        <p class="text-gray-600 text-lg mb-2">No medical history records yet</p>
        <p class="text-gray-500 text-sm mb-4">Start tracking medical events by adding the first record</p>
        <?php if ($can_update): ?>
        <button onclick="parent.openAddHistoryModalForEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', '<?php echo htmlspecialchars($employee['employee_id']); ?>')" 
                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add First Record
        </button>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
        <?php foreach ($medical_history as $record): ?>
        <div class="border-l-4 <?php 
            $type_colors = [
                'checkup' => 'border-blue-500 bg-blue-50',
                'diagnosis' => 'border-red-500 bg-red-50',
                'treatment' => 'border-green-500 bg-green-50',
                'vaccination' => 'border-purple-500 bg-purple-50',
                'lab_test' => 'border-yellow-500 bg-yellow-50',
                'consultation' => 'border-teal-500 bg-teal-50',
                'emergency' => 'border-orange-500 bg-orange-50',
                'follow_up' => 'border-indigo-500 bg-indigo-50'
            ];
            echo $type_colors[$record['record_type']] ?? 'border-gray-500 bg-gray-50';
        ?> p-4 rounded-lg hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold uppercase <?php
                        $badge_colors = [
                            'checkup' => 'bg-blue-600 text-white',
                            'diagnosis' => 'bg-red-600 text-white',
                            'treatment' => 'bg-green-600 text-white',
                            'vaccination' => 'bg-purple-600 text-white',
                            'lab_test' => 'bg-yellow-600 text-white',
                            'consultation' => 'bg-teal-600 text-white',
                            'emergency' => 'bg-orange-600 text-white',
                            'follow_up' => 'bg-indigo-600 text-white'
                        ];
                        echo $badge_colors[$record['record_type']] ?? 'bg-gray-600 text-white';
                    ?>">
                        <i class="fas <?php 
                            $icons = [
                                'checkup' => 'fa-stethoscope',
                                'diagnosis' => 'fa-diagnoses',
                                'treatment' => 'fa-procedures',
                                'vaccination' => 'fa-syringe',
                                'lab_test' => 'fa-vial',
                                'consultation' => 'fa-user-md',
                                'emergency' => 'fa-ambulance',
                                'follow_up' => 'fa-calendar-check'
                            ];
                            echo $icons[$record['record_type']] ?? 'fa-notes-medical';
                        ?> mr-1"></i>
                        <?php echo str_replace('_', ' ', $record['record_type']); ?>
                    </span>
                </div>
                <span class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-calendar mr-1"></i>
                    <?php echo date('F j, Y', strtotime($record['record_date'])); ?>
                </span>
            </div>
            
            <div class="space-y-2">
                <?php if ($record['chief_complaint']): ?>
                <div class="flex">
                    <span class="text-xs font-semibold text-gray-600 w-32">Chief Complaint:</span>
                    <p class="text-sm text-gray-900 flex-1"><?php echo htmlspecialchars($record['chief_complaint']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['diagnosis']): ?>
                <div class="flex">
                    <span class="text-xs font-semibold text-gray-600 w-32">Diagnosis:</span>
                    <p class="text-sm text-gray-900 flex-1"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['treatment']): ?>
                <div class="flex">
                    <span class="text-xs font-semibold text-gray-600 w-32">Treatment:</span>
                    <p class="text-sm text-gray-900 flex-1"><?php echo htmlspecialchars($record['treatment']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['medication_prescribed']): ?>
                <div class="flex">
                    <span class="text-xs font-semibold text-gray-600 w-32">Medication:</span>
                    <p class="text-sm text-gray-900 flex-1"><?php echo nl2br(htmlspecialchars($record['medication_prescribed'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($record['vital_signs']): ?>
                <div>
                    <span class="text-xs font-semibold text-gray-600">Vital Signs:</span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php 
                        $vitals = json_decode($record['vital_signs'], true);
                        if ($vitals):
                            if (isset($vitals['blood_pressure'])): ?>
                                <span class="px-2 py-1 bg-white rounded text-xs border border-gray-300">
                                    <i class="fas fa-heartbeat text-red-500 mr-1"></i>BP: <?php echo $vitals['blood_pressure']; ?>
                                </span>
                            <?php endif;
                            if (isset($vitals['heart_rate'])): ?>
                                <span class="px-2 py-1 bg-white rounded text-xs border border-gray-300">
                                    <i class="fas fa-heart text-pink-500 mr-1"></i>HR: <?php echo $vitals['heart_rate']; ?> bpm
                                </span>
                            <?php endif;
                            if (isset($vitals['temperature'])): ?>
                                <span class="px-2 py-1 bg-white rounded text-xs border border-gray-300">
                                    <i class="fas fa-thermometer-half text-orange-500 mr-1"></i>Temp: <?php echo $vitals['temperature']; ?>°C
                                </span>
                            <?php endif;
                            if (isset($vitals['weight'])): ?>
                                <span class="px-2 py-1 bg-white rounded text-xs border border-gray-300">
                                    <i class="fas fa-weight text-blue-500 mr-1"></i>Weight: <?php echo $vitals['weight']; ?> kg
                                </span>
                            <?php endif;
                        endif;
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($record['lab_results']): ?>
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <span class="text-xs font-semibold text-yellow-800">Lab Results:</span>
                    <p class="text-sm text-yellow-900 mt-1"><?php echo nl2br(htmlspecialchars($record['lab_results'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 text-xs text-gray-600">
                <div>
                    <?php if ($record['doctor_name']): ?>
                        <i class="fas fa-user-md mr-1"></i><?php echo htmlspecialchars($record['doctor_name']); ?>
                    <?php endif; ?>
                    <?php if ($record['clinic_hospital']): ?>
                        <span class="ml-3"><i class="fas fa-hospital mr-1"></i><?php echo htmlspecialchars($record['clinic_hospital']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($record['follow_up_date']): ?>
                <span class="text-orange-600 font-semibold">
                    <i class="fas fa-calendar-check mr-1"></i>Follow-up: <?php echo date('M j, Y', strtotime($record['follow_up_date'])); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
