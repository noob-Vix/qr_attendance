<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="css/register.css">
    <style>
        .tab-container {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
        .tab.active {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }
        .form-container {
            display: none;
        }
        .form-container.active {
            display: block;
        }
        #qrcode {
            display: none;
            margin: 20px auto;
            text-align: center;
        }
        .success-message {
            padding: 15px;
            background-color: #4ade80;
            color: white;
            text-align: center;
            margin: 15px 0;
            border-radius: 5px;
            display: none;
        }
        .error-message {
            padding: 15px;
            background-color: #ef4444;
            color: white;
            text-align: center;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registration System</h2>
        
        <div class="tab-container">
            <div class="tab active" id="student-tab" onclick="switchTab('student')">Student Registration</div>
            <div class="tab" id="teacher-tab" onclick="switchTab('teacher')">Teacher Registration</div>
        </div>
        
        <div id="message-container"></div>
        
        <!-- Student Registration Form -->
        <div class="form-container active" id="student-form">
            <form id="studentRegistrationForm" method="post">
                <input type="hidden" name="user_type" value="student">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" required placeholder="Enter your student ID">
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Enter your email address">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Choose a strong password" minlength="8">
                    <small style="color: #666;">Minimum 8 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit">Create Student Account</button>
            </form>
        </div>
        
        <!-- Teacher Registration Form -->
        <div class="form-container" id="teacher-form">
            <form id="teacherRegistrationForm" method="post">
                <input type="hidden" name="user_type" value="teacher">
                <div class="form-group">
                    <label>Teacher ID:</label>
                    <input type="text" name="teacher_id" required pattern="T\d{4}" placeholder="Enter your teacher ID"
                           title="Teacher ID must start with T followed by 4 digits (e.g., T1234)">
                    <small style="color: #666;">Format: T followed by 4 digits (e.g., T1234)</small>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="name" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required placeholder="Enter your email address">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required minlength="8" placeholder="Choose a strong password">
                    <small style="color: #666;">Minimum 8 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit">Create Teacher Account</button>
            </form>
        </div>
        
        <div id="qrcode"></div>
        <a href="index.php" class="back-link">← Back to Login</a>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = false;
        $error_message = '';
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            $error = true;
            $error_message = 'Passwords do not match';
        }
        
        // Different processing based on user type
        if (!$error) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($_POST['user_type'] === 'student') {
                // Process student registration
                $student_id = $_POST['student_id'];
                $qr_data = $student_id;
                
                try {
                    // Check if student ID already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                    if ($stmt->fetchColumn() > 0) {
                        echo '<div class="error-message">Student ID already exists</div>';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, password, qr_code) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$student_id, $name, $email, $hashed_password, $qr_data])) {
                            echo '<div class="success-message">Student registration successful! Your QR code is ready.</div>';
                            echo '<script>
                                document.getElementById("qrcode").style.display = "block";
                                new QRCode(document.getElementById("qrcode"), {
                                    text: "' . $qr_data . '",
                                    width: 128,
                                    height: 128,
                                    colorDark: "#4361ee",
                                    colorLight: "#ffffff",
                                });
                                
                                // Show the student form tab
                                document.getElementById("student-tab").click();
                            </script>';
                        }
                    }
                } catch(PDOException $e) {
                    echo '<div class="error-message">Registration failed: ' . $e->getMessage() . '</div>';
                }
            } else if ($_POST['user_type'] === 'teacher') {
                // Process teacher registration
                $teacher_id = $_POST['teacher_id'];
                
                // Validate teacher ID format
                if (!preg_match('/^T\d{4}$/', $teacher_id)) {
                    echo '<div class="error-message">Teacher ID must be in format T followed by 4 digits (e.g., T1234)</div>';
                } else {
                    try {
                        // Check if teacher ID already exists
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ?");
                        $stmt->execute([$teacher_id]);
                        if ($stmt->fetchColumn() > 0) {
                            echo '<div class="error-message">Teacher ID already exists</div>';
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, name, email, password) VALUES (?, ?, ?, ?)");
                            if ($stmt->execute([$teacher_id, $name, $email, $hashed_password])) {
                                echo '<div class="success-message">Teacher registration successful! You can now login.</div>';
                                echo '<script>
                                    // Show the teacher form tab
                                    document.getElementById("teacher-tab").click();
                                </script>';
                            }
                        }
                    } catch(PDOException $e) {
                        echo '<div class="error-message">Registration failed: ' . $e->getMessage() . '</div>';
                    }
                }
            }
        } else {
            echo '<div class="error-message">' . $error_message . '</div>';
        }
    }
    ?>
    
    <script>
        function switchTab(type) {
            // Hide all forms
            document.querySelectorAll('.form-container').forEach(form => {
                form.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab and form
            document.getElementById(type + '-tab').classList.add('active');
            document.getElementById(type + '-form').classList.add('active');
            
            // Hide QR code when switching tabs
            document.getElementById('qrcode').style.display = 'none';
            document.getElementById('qrcode').innerHTML = '';
        }
        
        // Add form validation if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Student form validation
            document.getElementById('studentRegistrationForm').addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    document.getElementById('message-container').innerHTML = '<div class="error-message">Passwords do not match</div>';
                }
            });
            
            // Teacher form validation
            document.getElementById('teacherRegistrationForm').addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                const teacherId = this.querySelector('input[name="teacher_id"]').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    document.getElementById('message-container').innerHTML = '<div class="error-message">Passwords do not match</div>';
                    return;
                }
                
                if (!teacherId.match(/^T\d{4}$/)) {
                    e.preventDefault();
                    document.getElementById('message-container').innerHTML = '<div class="error-message">Teacher ID must be in format T followed by 4 digits (e.g., T1234)</div>';
                }
            });
        });
    </script>
</body>
</html>