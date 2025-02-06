<!-- login_process.php -->
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    
    if ($type === 'student') {
        $student_id = $_POST['student_id'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if ($student && password_verify($password, $student['password'])) {
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['student_id'] = $student['student_id'];
            header("Location: student_dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid student ID or password";
            header("Location: index.php");
            exit();
        }
    } 
    else if ($type === 'admin') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_type'] = 'admin';
            header("Location: teacher_dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid admin credentials";
            header("Location: index.php");
            exit();
        }
    }
}
?>