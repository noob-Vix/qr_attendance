<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Registration</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; margin: 0 auto; }
        .form-group { margin: 15px 0; }
        input[type="text"], input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 8px;
            margin: 5px 0;
        }
        button { 
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #qrcode { margin: 20px 0; text-align: center; }
    </style>
</head>
<body>
    <h2>Student Registration</h2>
    <form id="registrationForm" method="post">
        <div class="form-group">
            <label>Student ID:</label>
            <input type="text" name="student_id" required>
        </div>
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Register</button>
    </form>
    <div id="qrcode"></div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $_POST['student_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Simple QR data - just use student ID
        $qr_data = $student_id;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, password, qr_code) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$student_id, $name, $email, $password, $qr_data])) {
                echo "<script>
                    new QRCode(document.getElementById('qrcode'), {
                        text: '$qr_data',
                        width: 128,
                        height: 128
                    });
                    alert('Registration successful! Please save your QR code.');
                </script>";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    ?>
</body>
</html>