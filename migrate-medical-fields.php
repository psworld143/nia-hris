<?php
/**
 * Migration: Add Medical Record Fields to Employees Table
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Medical Fields Migration</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2 { color: #333; border-bottom: 3px solid #9c27b0; padding-bottom: 10px; }
    .success { color: #4CAF50; } .error { color: #f44336; }
    .info { background: #e1bee7; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h2>🏥 Medical Record Fields Migration</h2>";

$fields_to_add = [
    "blood_type VARCHAR(10) DEFAULT NULL COMMENT 'Employee blood type'",
    "medical_conditions TEXT DEFAULT NULL COMMENT 'Known medical conditions'",
    "allergies TEXT DEFAULT NULL COMMENT 'Known allergies'",
    "medications TEXT DEFAULT NULL COMMENT 'Current medications'",
    "last_medical_checkup DATE DEFAULT NULL COMMENT 'Date of last medical checkup'",
    "medical_notes TEXT DEFAULT NULL COMMENT 'Additional medical notes'",
    "emergency_contact_name VARCHAR(100) DEFAULT NULL COMMENT 'Emergency contact person'",
    "emergency_contact_number VARCHAR(20) DEFAULT NULL COMMENT 'Emergency contact phone'",
];

echo "<h3>Step 1: Checking existing columns</h3>";

$existing_columns = [];
$columns_result = mysqli_query($conn, "SHOW COLUMNS FROM employees");
while ($col = mysqli_fetch_assoc($columns_result)) {
    $existing_columns[] = $col['Field'];
}

echo "<h3>Step 2: Adding medical record fields</h3>";

foreach ($fields_to_add as $field) {
    $field_name = explode(' ', $field)[0];
    
    if (in_array($field_name, $existing_columns)) {
        echo "⊗ Field <strong>$field_name</strong> already exists<br>";
    } else {
        $alter_query = "ALTER TABLE employees ADD COLUMN $field";
        if (mysqli_query($conn, $alter_query)) {
            echo "<span class='success'>✓ Added field: <strong>$field_name</strong></span><br>";
        } else {
            echo "<span class='error'>✗ Error adding $field_name: " . mysqli_error($conn) . "</span><br>";
        }
    }
}

echo "<hr><h3 class='success'>✓ Migration Complete!</h3>";
echo "<a href='medical-records.php' style='display:inline-block; padding:10px 20px; background:#9c27b0; color:white; text-decoration:none; border-radius:5px; margin-top:10px;'>Go to Medical Records</a>";

echo "</div></body></html>";
mysqli_close($conn);
?>

