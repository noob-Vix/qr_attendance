<?php
require_once 'config.php';

// Check if valid reset session exists
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_user_type'])) {
    $_SESSION['error'] = "Invalid password reset session.";
    header("Location: forgot_password.php");
    exit();
}

$token = $_SESSION['reset_token'];
$userID = $_SESSION['reset_user_id'];
$userType = $_SESSION['reset_user_type'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - QR Attendance System</title>
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
                <h2>Set New Password</h2>
            </div>
            
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <div class="form-section active">
                <form method="post" action="update_password.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="userID" value="<?php echo htmlspecialchars($userID); ?>">
                    <input type="hidden" name="userType" value="<?php echo htmlspecialchars($userType); ?>">
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required placeholder="Enter new password" minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password" minlength="8">
                    </div>
                    <button type="submit" class="btn">Update Password</button>
                </form>
            </div>

            <div class="register-links">
                <a href="index.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
