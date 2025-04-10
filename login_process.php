<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = trim($_POST["userID"]);
    
    if (ctype_digit($userID)) {
        $student_id = $_POST['userID'];
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
            $_SESSION['error'] = "Invalid student ID or password. Please check your credentials and try again.";
            header("Location: index.php");
            exit();
        }
    } 
    else if (strpos($userID, "T") === 0) {
        $teacher_id = $_POST['userID'];
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();
        
        if ($teacher && password_verify($password, $teacher['password'])) {
            $_SESSION['user_id'] = $teacher['id'];
            $_SESSION['user_type'] = 'teacher';
            $_SESSION['teacher_id'] = $teacher['teacher_id'];
            $_SESSION['teacher_name'] = $teacher['name'];
            header("Location: teacher_dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid teacher ID or password. Please check your credentials and try again.";
            header("Location: index.php");
            exit();
        }
    }
    else {
        $_SESSION['error'] = "Invalid teacher ID or password. Please check your credentials and try again.";
        header("Location: index.php");
        exit();
    }
}

// If somehow got here without proper POST data
$_SESSION['error'] = "Invalid login attempt. Please try again.";
header("Location: index.php");
exit();
?>