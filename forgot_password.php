<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - QR Attendance System</title>
    <link rel="stylesheet" href="css/index.css">
    <meta http-equiv="cache-control" content="no-cache, must-revalidate, post-check=0, pre-check=0" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>📱 QR Attendance System</h1>
            </div>

            <div class="login-header">
                <h2>Forgot Password</h2>
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

            <div class="form-section active">
                <form method="post" action="reset_password.php">
                    <div class="form-group">
                        <label>User ID</label>
                        <input type="text" name="userID" required placeholder="Enter your user ID">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="Enter your registered email">
                    </div>
                    <button type="submit" class="btn">Reset Password</button>
                </form>
            </div>

            <div class="register-links">
                <a href="index.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
