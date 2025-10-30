<?php
// Clean output buffers
while (ob_get_level()) { ob_end_clean(); }

session_start();
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Migration: Fix performance_reviews foreign key to employees</h1>";
echo "<p>This will update the foreign key constraint on performance_reviews.employee_id to reference employees(id).</p><hr>";

try {
    mysqli_begin_transaction($conn);

    // 1) Detect current foreign keys on performance_reviews
    echo "<h3>Step 1: Inspecting current constraints...</h3>";
    $fkRows = [];
    $sql = "SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'performance_reviews'
              AND COLUMN_NAME = 'employee_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL";
    if ($res = mysqli_query($conn, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) { $fkRows[] = $row; }
        mysqli_free_result($res);
    }

    if (!empty($fkRows)) {
        echo "<p>Found existing foreign keys:</p><ul>";
        foreach ($fkRows as $fk) {
            echo "<li>" . htmlspecialchars($fk['CONSTRAINT_NAME']) . " -> " . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . "(" . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No existing foreign key found on performance_reviews.employee_id.</p>";
    }

    // 2) Drop any existing FK that doesn't point to employees(id)
    foreach ($fkRows as $fk) {
        if (strtolower($fk['REFERENCED_TABLE_NAME']) !== 'employees' || strtolower($fk['REFERENCED_COLUMN_NAME']) !== 'id') {
            $constraint = $fk['CONSTRAINT_NAME'];
            $drop = "ALTER TABLE performance_reviews DROP FOREIGN KEY `$constraint`";
            if (!mysqli_query($conn, $drop)) {
                throw new Exception('Failed to drop foreign key ' . $constraint . ': ' . mysqli_error($conn));
            }
            echo "<p style='color: orange;'>Dropped incorrect FK: " . htmlspecialchars($constraint) . "</p>";
        }
    }

    // 3) Ensure index exists for employee_id
    echo "<h3>Step 2: Ensuring index on employee_id...</h3>";
    $idxCheck = mysqli_query($conn, "SHOW INDEX FROM performance_reviews WHERE Column_name = 'employee_id'");
    if ($idxCheck && mysqli_num_rows($idxCheck) === 0) {
        if (!mysqli_query($conn, "ALTER TABLE performance_reviews ADD INDEX idx_pr_employee_id (employee_id)")) {
            throw new Exception('Failed to add index on employee_id: ' . mysqli_error($conn));
        }
        echo "<p style='color: green;'>Index idx_pr_employee_id created.</p>";
    } else {
        echo "<p>Index on employee_id already exists.</p>";
    }

    // 4) Add correct FK to employees(id)
    echo "<h3>Step 3: Adding correct foreign key to employees(id)...</h3>";
    $addFk = "ALTER TABLE performance_reviews
              ADD CONSTRAINT fk_review_employee
              FOREIGN KEY (employee_id) REFERENCES employees(id)
              ON DELETE CASCADE ON UPDATE CASCADE";
    if (!mysqli_query($conn, $addFk)) {
        // If it fails because it already exists correctly, ignore
        if (strpos(mysqli_error($conn), 'Duplicate') === false && strpos(mysqli_error($conn), 'exists') === false) {
            throw new Exception('Failed to add foreign key: ' . mysqli_error($conn));
        } else {
            echo "<p>Foreign key already present.</p>";
        }
    } else {
        echo "<p style='color: green;'>Foreign key fk_review_employee added.</p>";
    }

    mysqli_commit($conn);
    echo "<hr><p style='color: green;'>Migration completed successfully.</p>";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<p style='color: red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>


