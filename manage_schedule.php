<?php
require_once 'config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
     header("Location: index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle schedule deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $schedule_id = $_POST['schedule_id'];
        $class_id = $_POST['class_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM class_schedules WHERE id = ? AND class_id = ?");
            $stmt->execute([$schedule_id, $class_id]);
            $message = "Schedule deleted successfully";
        } catch(PDOException $e) {
            $error = "Error deleting schedule: " . $e->getMessage();
        }
    } 
    // Handle schedule updates
    else if (isset($_POST['day']) && isset($_POST['start_time']) && isset($_POST['grace_period'])) {
        $class_id = $_POST['class_id'];
        $day = $_POST['day'];
        $start_time = $_POST['start_time'];
        $grace_period = $_POST['grace_period'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO class_schedules (class_id, day_of_week, start_time, grace_period)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE start_time = ?, grace_period = ?
            ");
            $stmt->execute([$class_id, $day, $start_time, $grace_period, $start_time, $grace_period]);
            $message = "Schedule updated successfully";
        } catch(PDOException $e) {
            $error = "Error updating schedule: " . $e->getMessage();
        }
    }
}
// Handle schedule deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $schedule_id = $_POST['schedule_id'];
    $class_id = $_POST['class_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM class_schedules WHERE id = ? AND class_id = ?");
        $stmt->execute([$schedule_id, $class_id]);
        $message = "Schedule deleted successfully";
    } catch(PDOException $e) {
        $error = "Error deleting schedule: " . $e->getMessage();
    }
}

// Get existing schedule
$class_id = $_GET['class_id'] ?? null;
$schedules = [];  // Initialize empty array
if ($class_id) {
    $stmt = $pdo->prepare("SELECT * FROM class_schedules WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Class Schedule Management</title>
    <link rel="stylesheet" href="css/teacher_dashboard.css">
</head>
<body>
    <div class="container">
    <div class="header">
            <h2>Class Schedule Management</h2>
            <a href="teacher_dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>
        <?php if ($class_id): ?>
            <div class="card">
                <?php if (isset($message)): ?>
                    <div class="success-message"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                    <div class="form-group">
                        <label>Day of Week:</label>
                        <select name="day" required>
                            <option value="1">Sunday</option>
                            <option value="2">Monday</option>
                            <option value="3">Tuesday</option>
                            <option value="4">Wednesday</option>
                            <option value="5">Thursday</option>
                            <option value="6">Friday</option>
                            <option value="7">Saturday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Time:</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>Grace Period (minutes):</label>
                        <input type="number" name="grace_period" min="0" max="60" value="15" required>
                    </div>
                    <button type="submit">Update Schedule</button>
                </form>

                <h4>Current Schedule</h4>
                <table>
                    <tr>
                        <th>Day</th>
                        <th>Start Time</th>
                        <th>Grace Period</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    foreach ($schedules as $schedule) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($days[$schedule['day_of_week'] - 1]) . "</td>";
                        echo "<td>" . htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) . "</td>";
                        echo "<td>" . htmlspecialchars($schedule['grace_period']) . " minutes</td>";
                        echo "<td>
                                <form method='post' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this schedule?\")'>
                                    <input type='hidden' name='schedule_id' value='" . $schedule['id'] . "'>
                                    <input type='hidden' name='class_id' value='" . $class_id . "'>
                                    <input type='hidden' name='action' value='delete'>
                                    <button type='submit' class='delete-btn'>Delete</button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>
        <?php else: ?>
            <p>Please select a class to manage its schedule.</p>
        <?php endif; ?>
    </div>
</body>
</html>