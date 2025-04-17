<?php
require_once 'config.php';

// Check if user is logged in as teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Process status update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $attendance_id = $_POST['attendance_id'];
    $new_status = $_POST['new_status'];
    
    // Verify teacher has permission to update this attendance record
    $verify_stmt = $pdo->prepare("
        SELECT a.id 
        FROM attendance a
        JOIN classes c ON a.class_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $verify_stmt->execute([$attendance_id, $teacher_id]);
    
    if ($verify_stmt->rowCount() > 0) {
        $update_stmt = $pdo->prepare("
            UPDATE attendance 
            SET status = ? 
            WHERE id = ?
        ");
        $update_stmt->execute([$new_status, $attendance_id]);
        $_SESSION['success'] = "Attendance status updated successfully";
    } else {
        $_SESSION['error'] = "You don't have permission to modify this attendance record";
    }
}

// Get teacher's classes
$classes_stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
$classes_stmt->execute([$teacher_id]);
$classes = $classes_stmt->fetchAll();

// Get selected class (if any)
$selected_class = isset($_GET['class_id']) ? $_GET['class_id'] : null;

// Verify the selected class belongs to this teacher
if ($selected_class) {
    $verify_class = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $verify_class->execute([$selected_class, $teacher_id]);
    if ($verify_class->rowCount() === 0) {
        // Class doesn't belong to this teacher
        $_SESSION['error'] = "You don't have permission to view this class";
        $selected_class = null;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="css/teacher_dashboard.css">
    <style>
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .status-ontime { color: #28a745; }
        .status-late { color: #ffc107; }
        .status-absent { color: #dc3545; }
        td form { margin: 0; }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .status-badge.on-time { background-color: #d4edda; color: #155724; }
        .status-badge.late { background-color: #fff3cd; color: #856404; }
        .status-badge.absent { background-color: #f8d7da; color: #721c24; }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Teacher Dashboard</h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="nav-links">
            <a href="scanner.php">Open Scanner</a>
            <a href="manage_classes.php">Manage Classes</a>
            <a href="manage_schedule.php<?php echo $selected_class ? '?class_id=' . htmlspecialchars($selected_class) : ''; ?>">Manage Schedule</a>
        </div>

        <?php if (count($classes) === 0): ?>
            <div class="message">
                You haven't created any classes yet. Please <a href="manage_classes.php">create a class</a> first.
            </div>
        <?php else: ?>
            <select class="class-select" onchange="window.location.href='?class_id=' + this.value">
                <option value="">Select a class</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= htmlspecialchars($class['id']) ?>" 
                        <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ($selected_class): ?>
            <?php
            // Get quick stats
            $total_students = $pdo->prepare("SELECT COUNT(*) FROM class_students WHERE class_id = ?");
            $total_students->execute([$selected_class]);
            $student_count = $total_students->fetchColumn();

            // Check attendance.class_id
            $present_today = $pdo->prepare("
                SELECT COUNT(DISTINCT a.student_id) 
                FROM attendance a 
                WHERE a.class_id = ? AND a.date = CURRENT_DATE()
            ");
            $present_today->execute([$selected_class]);
            $attendance_count = $present_today->fetchColumn();

            $attendance_rate = $student_count > 0 ? round(($attendance_count / $student_count) * 100) : 0;
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $student_count ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $attendance_count ?></div>
                    <div class="stat-label">Present Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $attendance_rate ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            </div>

            <div class="card">
            <h3>Today's Attendance</h3>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
                <?php
                // Get all students in the class, including those without attendance
                $stmt = $pdo->prepare("
                    SELECT 
                        cs.student_id,
                        s.name,
                        a.id as attendance_id,
                        a.time,
                        COALESCE(a.status, 'absent') as status
                    FROM class_students cs
                    JOIN students s ON cs.student_id = s.student_id
                    LEFT JOIN attendance a ON cs.student_id = a.student_id 
                        AND a.class_id = cs.class_id 
                        AND a.date = CURRENT_DATE()
                    WHERE cs.class_id = ?
                    ORDER BY s.name
                ");
                $stmt->execute([$selected_class]);
               
                while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . ($row['time'] ? htmlspecialchars(date('h:i A', strtotime($row['time']))) : 'N/A') . "</td>";
                    echo "<td>";
                    if ($row['attendance_id']) {
                        echo "<form method='POST' style='display: inline;'>";
                        echo "<input type='hidden' name='attendance_id' value='" . $row['attendance_id'] . "'>";
                        echo "<select name='new_status' onchange='this.form.submit()' class='status-select'>";
                        foreach (['on-time', 'late', 'absent'] as $status) {
                            $selected = $row['status'] === $status ? 'selected' : '';
                            echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                        }
                        echo "</select>";
                        echo "<input type='hidden' name='update_status' value='1'>";
                        echo "</form>";
                    } else {
                        echo "<span class='status-badge absent'>Absent</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    <?php endif; ?>
    </div>
</body>
</html>