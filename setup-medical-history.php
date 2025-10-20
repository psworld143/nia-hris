<?php
/**
 * Setup Medical History Table and Seed Sample Data
 * Creates a comprehensive medical history tracking system
 */

require_once 'config/database.php';
date_default_timezone_set('Asia/Manila');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Medical History - NIA HRIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-heartbeat text-red-600 mr-2"></i>Setup Medical History System
                </h1>
                <p class="text-gray-600">Creating medical history table and seeding sample data</p>
            </div>

            <div class="space-y-2 font-mono text-sm">
<?php

// ======================
// 1. CREATE TABLE
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-database text-blue-500 mr-2'></i>Creating Medical History Table...</h3>";

$create_table = "CREATE TABLE IF NOT EXISTS employee_medical_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    record_date DATE NOT NULL,
    record_type ENUM('checkup', 'diagnosis', 'treatment', 'vaccination', 'lab_test', 'consultation', 'emergency', 'follow_up') NOT NULL DEFAULT 'checkup',
    chief_complaint VARCHAR(255) DEFAULT NULL,
    diagnosis TEXT DEFAULT NULL,
    treatment TEXT DEFAULT NULL,
    medication_prescribed TEXT DEFAULT NULL,
    lab_results TEXT DEFAULT NULL,
    vital_signs JSON DEFAULT NULL,
    doctor_name VARCHAR(100) DEFAULT NULL,
    clinic_hospital VARCHAR(200) DEFAULT NULL,
    follow_up_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_date (employee_id, record_date),
    INDEX idx_record_type (record_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $create_table)) {
    echo "<span class='success'>✓ Medical history table created successfully</span><br>";
} else {
    echo "<span class='error'>✗ Error creating table: " . mysqli_error($conn) . "</span><br>";
}

// ======================
// 2. GET ALL EMPLOYEES
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-users text-green-500 mr-2'></i>Fetching Employees...</h3>";

$employees_query = "SELECT id, first_name, last_name, employee_id FROM employees WHERE is_active = 1";
$employees_result = mysqli_query($conn, $employees_query);
$employees = [];

while ($row = mysqli_fetch_assoc($employees_result)) {
    $employees[] = $row;
}

echo "<span class='info'>→ Found " . count($employees) . " active employees</span><br>";

// ======================
// 3. SEED MEDICAL HISTORY
// ======================
echo "<h3 class='text-lg font-bold text-gray-900 mt-6 mb-3'><i class='fas fa-file-medical text-purple-500 mr-2'></i>Seeding Medical History Records...</h3>";

// Medical record templates for variety
$record_types = [
    'checkup' => [
        'complaints' => [
            'Annual physical examination',
            'Routine health checkup',
            'Pre-employment medical exam',
            'Wellness checkup',
            'Health maintenance visit'
        ],
        'diagnoses' => [
            'No significant findings',
            'Generally healthy',
            'Fit for work',
            'Good health status',
            'Normal physical exam'
        ]
    ],
    'diagnosis' => [
        'complaints' => [
            'Persistent headache',
            'Fatigue and weakness',
            'Fever and cough',
            'Back pain',
            'Joint pain'
        ],
        'diagnoses' => [
            'Tension headache',
            'Viral infection',
            'Upper respiratory tract infection',
            'Muscle strain',
            'Arthralgia'
        ]
    ],
    'vaccination' => [
        'complaints' => [
            'COVID-19 vaccination',
            'Flu vaccine',
            'Hepatitis B booster',
            'Tetanus booster',
            'Pneumonia vaccine'
        ],
        'diagnoses' => [
            'Vaccination completed',
            'Immunization administered',
            'Booster dose given',
            'Vaccination series completed',
            'Preventive immunization'
        ]
    ],
    'lab_test' => [
        'complaints' => [
            'Complete blood count',
            'Urinalysis',
            'Lipid profile',
            'Blood sugar test',
            'Chest X-ray'
        ],
        'diagnoses' => [
            'Results within normal limits',
            'All parameters normal',
            'Test results satisfactory',
            'No abnormalities detected',
            'Normal findings'
        ]
    ],
    'consultation' => [
        'complaints' => [
            'Health consultation',
            'Medical advice needed',
            'Follow-up consultation',
            'Second opinion',
            'Health guidance'
        ],
        'diagnoses' => [
            'Advice given',
            'Health education provided',
            'Lifestyle modification recommended',
            'Return if symptoms worsen',
            'Observation recommended'
        ]
    ]
];

$doctors = [
    'Dr. Maria Santos',
    'Dr. Jose Garcia',
    'Dr. Ana Reyes',
    'Dr. Pedro Cruz',
    'Dr. Carmen Ramos',
    'Dr. Miguel Fernandez'
];

$clinics = [
    'NIA Health Center',
    'Makati Medical Center',
    'St. Luke\'s Medical Center',
    'Asian Hospital and Medical Center',
    'The Medical City',
    'Manila Doctors Hospital'
];

$medications = [
    'Paracetamol 500mg - 1 tab every 6 hours as needed',
    'Amoxicillin 500mg - 1 cap 3x a day for 7 days',
    'Cetirizine 10mg - 1 tab once daily',
    'Ibuprofen 400mg - 1 tab every 8 hours after meals',
    'Multivitamins - 1 tab once daily',
    'None prescribed - advised rest and hydration',
    'Mefenamic acid 500mg - 1 cap every 8 hours',
    'Salbutamol inhaler - 2 puffs as needed'
];

$total_records = 0;

foreach ($employees as $employee) {
    $records_created = 0;
    
    // Create 10-15 random medical history records per employee
    $num_records = rand(10, 15);
    
    for ($i = 0; $i < $num_records; $i++) {
        // Random date within last 3 years
        $days_ago = rand(1, 1095); // 3 years
        $record_date = date('Y-m-d', strtotime("-$days_ago days"));
        
        // Random record type
        $type_keys = array_keys($record_types);
        $type = $type_keys[array_rand($type_keys)];
        
        // Get complaint and diagnosis based on type
        $complaint = $record_types[$type]['complaints'][array_rand($record_types[$type]['complaints'])];
        $diagnosis = $record_types[$type]['diagnoses'][array_rand($record_types[$type]['diagnoses'])];
        
        $doctor = $doctors[array_rand($doctors)];
        $clinic = $clinics[array_rand($clinics)];
        $medication = $medications[array_rand($medications)];
        
        // Vital signs as JSON
        $vital_signs = json_encode([
            'blood_pressure' => rand(110, 130) . '/' . rand(70, 85),
            'heart_rate' => rand(60, 90),
            'temperature' => rand(360, 375) / 10,
            'respiratory_rate' => rand(14, 20),
            'weight' => rand(50, 85),
            'height' => rand(150, 180)
        ]);
        
        // Treatment description
        $treatments = [
            'Rest and observation',
            'Medication prescribed',
            'Advised to return if symptoms persist',
            'Lifestyle modification recommended',
            'Follow-up in 2 weeks',
            'Continue current medications',
            'Referred to specialist',
            'Home care instructions given'
        ];
        $treatment = $treatments[array_rand($treatments)];
        
        // Follow-up date (some records have it, some don't)
        $follow_up = rand(0, 1) ? date('Y-m-d', strtotime($record_date . ' +' . rand(7, 30) . ' days')) : null;
        
        // Lab results (for lab tests)
        $lab_result = null;
        if ($type === 'lab_test') {
            $lab_result = "CBC: WBC 7.2, RBC 5.1, Hgb 14.5, Hct 42%\nUrinalysis: Normal\nOther parameters: Within normal limits";
        }
        
        // Insert record
        $notes = "Recorded during " . ucfirst($type) . " visit on " . date('F j, Y', strtotime($record_date));
        
        $insert_query = "INSERT INTO employee_medical_history (
            employee_id, record_date, record_type, chief_complaint, diagnosis, 
            treatment, medication_prescribed, lab_results, vital_signs, 
            doctor_name, clinic_hospital, follow_up_date, notes, recorded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "issssssssssss",
            $employee['id'], $record_date, $type, $complaint, $diagnosis,
            $treatment, $medication, $lab_result, $vital_signs,
            $doctor, $clinic, $follow_up, $notes
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $records_created++;
        }
    }
    
    $total_records += $records_created;
    echo "<span class='success'>✓ Created $records_created medical records for {$employee['first_name']} {$employee['last_name']}</span><br>";
}

// ======================
// 4. SUMMARY
// ======================
echo "<div class='mt-8 p-6 bg-green-50 border-l-4 border-green-500 rounded'>";
echo "<h3 class='text-lg font-bold text-green-800 mb-2'><i class='fas fa-check-circle mr-2'></i>Medical History Setup Completed!</h3>";
echo "<p class='text-green-700'>Successfully created medical history system with:</p>";
echo "<ul class='list-disc list-inside text-green-700 mt-2 space-y-1'>";
echo "<li>Medical History Table with comprehensive fields</li>";
echo "<li>" . count($employees) . " Employees processed</li>";
echo "<li>" . $total_records . " Medical history records created</li>";
echo "<li>Average " . round($total_records / max(count($employees), 1)) . " records per employee</li>";
echo "</ul>";
echo "</div>";

echo "<div class='mt-6 p-4 bg-blue-50 border border-blue-200 rounded'>";
echo "<h4 class='font-bold text-blue-900 mb-2'><i class='fas fa-info-circle text-blue-600 mr-2'></i>Record Types Created</h4>";
echo "<ul class='grid grid-cols-2 gap-2 text-sm text-blue-800 mt-2'>";
echo "<li><i class='fas fa-check text-green-600 mr-1'></i> Annual Checkups</li>";
echo "<li><i class='fas fa-syringe text-purple-600 mr-1'></i> Vaccinations</li>";
echo "<li><i class='fas fa-stethoscope text-red-600 mr-1'></i> Diagnoses</li>";
echo "<li><i class='fas fa-vial text-blue-600 mr-1'></i> Lab Tests</li>";
echo "<li><i class='fas fa-user-md text-teal-600 mr-1'></i> Consultations</li>";
echo "</ul>";
echo "</div>";

?>
            </div>

            <div class="mt-8 flex justify-center space-x-4">
                <a href="medical-records.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-heartbeat mr-2"></i>View Medical Records
                </a>
                <a href="admin-employee.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-users mr-2"></i>View Employees
                </a>
                <a href="index.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

