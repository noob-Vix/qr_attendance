<?php
require_once 'config.php';

// Check if user is logged in as teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Handle class creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_class') {
        $class_name = trim($_POST['class_name']);
        $stmt = $pdo->prepare("INSERT INTO classes (teacher_id, name) VALUES (?, ?)");
        $stmt->execute([$teacher_id, $class_name]);
    } 
    elseif ($_POST['action'] === 'add_student') {
        $class_id = $_POST['class_id'];
        $student_id = $_POST['student_id'];
        
        // Check if student exists
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->rowCount() > 0) {
            // Check if student is already in the class
            $stmt = $pdo->prepare("SELECT * FROM class_students WHERE class_id = ? AND student_id = ?");
            $stmt->execute([$class_id, $student_id]);
            if ($stmt->rowCount() == 0) {
                $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
                $stmt->execute([$class_id, $student_id]);
                $_SESSION['success'] = "Student added successfully";
            } else {
                $_SESSION['error'] = "Student is already in this class";
            }
        } else {
            $_SESSION['error'] = "Student ID not found";
        }
    }
    elseif ($_POST['action'] === 'remove_student') {
        $class_id = $_POST['class_id'];
        $student_id = $_POST['student_id'];
        
        $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$class_id, $student_id]);
    }
    
    header("Location: manage_classes.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Classes</title>
    <link rel="stylesheet" href="css/manage.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Manage Classes</h2>
            <a href="teacher_dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <!-- Create new class form -->
        <div class="class-card">
            <h3>Create New Class</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_class">
                <div class="form-group">
                    <input type="text" name="class_name" placeholder="Enter class name" required>
                    <button type="submit">Create Class</button>
                </div>
            </form>
        </div>

        <!-- List of existing classes -->
        <?php
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $classes = $stmt->fetchAll();

        foreach ($classes as $class):
        ?>
        <div class="class-card">
            <h3><?= htmlspecialchars($class['name']) ?></h3>
            
            <!-- Add student form -->
            <form method="post" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_student">
                <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                <div class="form-group">
                    <input type="text" name="student_id" placeholder="Enter student ID" required>
                    <button type="submit">Add Student</button>
                </div>
            </form>

            <!-- List of students in this class -->
            <h4>Enrolled Students</h4>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                <?php
                $stmt = $pdo->prepare("
                    SELECT s.* 
                    FROM students s
                    JOIN class_students cs ON s.student_id = cs.student_id
                    WHERE cs.class_id = ?
                    ORDER BY s.name
                ");
                $stmt->execute([$class['id']]);
                $students = $stmt->fetchAll();

                foreach ($students as $student):
                ?>
                <tr>
                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= htmlspecialchars($student['email']) ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="remove_student">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                            <button type="submit" class="remove-btn" onclick="return confirm('Are you sure you want to remove this student?')">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>