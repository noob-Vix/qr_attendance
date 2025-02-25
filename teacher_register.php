<!-- teacher_register.php -->
<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Registration</title>
    <link rel="stylesheet" href="css/teacher_register.css">
</head>
<body>
    <div class="container">
        <h2>Teacher Registration</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teacher_id = $_POST['teacher_id'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            $error = false;
            $error_message = '';
            
            // Validate teacher ID format (you can modify this based on your requirements)
            if (!preg_match('/^T\d{4}$/', $teacher_id)) {
                $error = true;
                $error_message = 'Teacher ID must be in format T followed by 4 digits (e.g., T1234)';
            }
            
            // Check if passwords match
            if ($password !== $confirm_password) {
                $error = true;
                $error_message = 'Passwords do not match';
            }
            
            // Check if teacher ID already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = true;
                $error_message = 'Teacher ID already exists';
            }
            
            if (!$error) {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, name, email, password) VALUES (?, ?, ?, ?)");
                    if($stmt->execute([$teacher_id, $name, $email, $hashed_password])) {
                        echo '<div class="success">Registration successful! You can now login.</div>';
                    }
                } catch(PDOException $e) {
                    echo '<div class="error">Registration failed: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="error">' . $error_message . '</div>';
            }
        }
        ?>

        <form method="post">
            <div class="form-group">
                <label>Teacher ID:</label>
                <input type="text" name="teacher_id" required pattern="T\d{4}" 
                       title="Teacher ID must start with T followed by 4 digits (e.g., T1234)">
                <small style="color: #666;">Format: T followed by 4 digits (e.g., T1234)</small>
            </div>
            
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required minlength="8">
                <small style="color: #666;">Minimum 8 characters</small>
            </div>
            
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <a href="index.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>