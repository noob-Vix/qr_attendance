<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $userID = $_POST['userID'];
    $userType = $_POST['userType'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: password_reset_form.php");
        exit();
    }
    
    // Validate password strength
    if (strlen($newPassword) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long.";
        header("Location: password_reset_form.php");
        exit();
    }
    
    // Determine which table to update
    $tableName = ($userType === 'student') ? 'students' : 'teachers';
    $idField = ($userType === 'student') ? 'student_id' : 'teacher_id';
    
    // Check if token is valid and not expired
    $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE $idField = ? AND reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->execute([$userID, $token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password and clear the reset token
        $stmt = $pdo->prepare("UPDATE $tableName SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE $idField = ?");
        $stmt->execute([$hashedPassword, $userID]);
        
        // Clear the reset session
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_user_type']);
        
        $_SESSION['success'] = "Your password has been updated successfully. You can now login with your new password.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired password reset token.";
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
