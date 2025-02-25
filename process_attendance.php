<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $student_id = $_POST['qr_data'];
    
    // Verify teacher is logged in
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher' || !isset($_SESSION['teacher_id'])) {
        echo "❌ Unauthorized access";
        exit;
    }

    $teacher_id = $_SESSION['teacher_id'];
    
    try {
        // Check if student exists and get their classes
        $stmt = $pdo->prepare("
            SELECT cs.class_id, s.* 
            FROM students s
            JOIN class_students cs ON s.student_id = cs.student_id 
            JOIN classes c ON cs.class_id = c.id
            WHERE s.student_id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$student_id, $teacher_id]);
        
        if ($stmt->rowCount() > 0) {
            $student = $stmt->fetch();
            
            // Get today's schedule
            $scheduleStmt = $pdo->prepare("
                SELECT cs.* 
                FROM class_schedules cs
                JOIN classes c ON cs.class_id = c.id
                WHERE c.teacher_id = ? 
                AND cs.class_id = ?
                AND cs.day_of_week = DAYOFWEEK(CURRENT_DATE())
            ");
            $scheduleStmt->execute([$teacher_id, $student['class_id']]);
            $schedule = $scheduleStmt->fetch();
            
            if (!$schedule) {
                echo "❌ No class scheduled for today with this teacher";
                exit;
            }

            // Check if attendance already marked
            $stmt = $pdo->prepare("
                SELECT * FROM attendance 
                WHERE student_id = ? 
                AND class_id = ? 
                AND date = CURRENT_DATE()
            ");
            $stmt->execute([$student_id, $student['class_id']]);
            
            if ($stmt->rowCount() == 0) {
                // Get current time
                $currentTime = new DateTime();
                $startTime = new DateTime($schedule['start_time']);
                $graceTime = clone $startTime;
                $graceTime->modify('+' . $schedule['grace_period'] . ' minutes');
                
                // Determine status based on time
                $status = $currentTime <= $graceTime ? 'on-time' : 'late';
                
                // Mark attendance
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, class_id, date, time, status) 
                    VALUES (?, ?, CURRENT_DATE(), CURRENT_TIME(), ?)
                ");
                
                $stmt->execute([$student_id, $student['class_id'], $status]);
                
                echo $status === 'on-time' ? 
                    "✅ On-time attendance marked for Student ID: $student_id" :
                    "⚠️ Late attendance marked for Student ID: $student_id";
            } else {
                echo "⚠️ Attendance already marked for this class today";
            }
        } else {
            echo "❌ Student not found in any of your classes";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>