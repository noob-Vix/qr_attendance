<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = trim($_POST["userID"]);
    $email = trim($_POST["email"]);
    
    // Check if it's a student (numeric ID)
    if (ctype_digit($userID)) {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND email = ?");
        $stmt->execute([$userID, $email]);
        $user = $stmt->fetch();
        $userType = 'student';
    } 
    // Check if it's a teacher (ID starts with T)
    else if (strpos($userID, "T") === 0) {
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? AND email = ?");
        $stmt->execute([$userID, $email]);
        $user = $stmt->fetch();
        $userType = 'teacher';
    }
    // Invalid ID format
    else {
        $_SESSION['error'] = "Invalid user ID format.";
        header("Location: forgot_password.php");
        exit();
    }
    
    // If user with matching ID and email was found
    if ($user) {
        // Generate a random token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store the reset token in the database
        $tableName = ($userType === 'student') ? 'students' : 'teachers';
        $idField = ($userType === 'student') ? 'student_id' : 'teacher_id';
        
        $stmt = $pdo->prepare("UPDATE $tableName SET reset_token = ?, reset_token_expiry = ? WHERE $idField = ?");
        $stmt->execute([$token, $expiry, $userID]);
        
        // In a real application, you would send an email with the reset link
        // For now, we'll just display the reset form
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_user_id'] = $userID;
        $_SESSION['reset_user_type'] = $userType;
        
        header("Location: password_reset_form.php");
        exit();
    } else {
        $_SESSION['error'] = "No account found with that ID and email combination.";
        header("Location: forgot_password.php");
        exit();
    }
} else {
    // If accessed directly without POST data
    $_SESSION['error'] = "Invalid request.";
    header("Location: forgot_password.php");
    exit();
}
?>
