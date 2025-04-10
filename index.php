<!-- index.php -->
<!DOCTYPE html>
<html>
<head>
    <title>QR Attendance System</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="container">
        
        <div class="card">
            <div class="logo">
                <h1>📱 QR Attendance System</h1>
            </div>

            <div class="login-header">
                <h2>Login</h2>
            </div>
            
            <?php
            session_start();
            if (isset($_SESSION['error'])) {
                echo '<div class="error">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['success'])) {
                echo '<div class="success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>

            <div id="loginForm" class="form-section active">
                <form method="post" action="login_process.php">
                    <input type="hidden" name="type">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" name="userID" required placeholder="Enter your user ID">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>


            <div class="register-links">
                <a href="register.php">Register as Student</a>
                <a href="teacher_register.php">Register as Teacher</a>
            </div>
        </div>
    </div>

</body>
</html>