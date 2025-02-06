<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $student_id = $_POST['qr_data'];
    
    try {
        // Check if student exists
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        if ($stmt->rowCount() > 0) {
            // Check if attendance already marked for today
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = CURRENT_DATE()");
            $stmt->execute([$student_id]);
            
            if ($stmt->rowCount() == 0) {
                // Mark attendance
                $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, time) VALUES (?, CURRENT_DATE(), CURRENT_TIME())");
                $stmt->execute([$student_id]);
                echo "✅ Attendance marked for Student ID: $student_id";
            } else {
                echo "⚠️ Attendance already marked for today";
            }
        } else {
            echo "❌ Student ID not found";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>