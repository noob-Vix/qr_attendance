<?php
require_once 'config.php';

try {
    // First check if status column exists
    $result = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'status'");
    if ($result->rowCount() == 0) {
        // Add status column
        $pdo->exec("ALTER TABLE attendance ADD COLUMN status VARCHAR(10) DEFAULT 'absent'");
        echo "Successfully added status column<br>";
    }

    // Now check if is_late and minutes_late columns exist and remove them
    $result = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'is_late'");
    if ($result->rowCount() > 0) {
        $pdo->exec("ALTER TABLE attendance DROP COLUMN is_late");
        echo "Successfully removed is_late column<br>";
    }

    $result = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'minutes_late'");
    if ($result->rowCount() > 0) {
        $pdo->exec("ALTER TABLE attendance DROP COLUMN minutes_late");
        echo "Successfully removed minutes_late column<br>";
    }

    // Update existing records to have a status
    $pdo->exec("UPDATE attendance SET status = 'on-time' WHERE (status IS NULL OR status = '') AND is_late = 0");
    $pdo->exec("UPDATE attendance SET status = 'late' WHERE (status IS NULL OR status = '') AND is_late = 1");
    
    echo "Database update completed successfully";
} catch(PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
