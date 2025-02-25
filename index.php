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

            <div class="tabs">
                <div class="tab active" onclick="switchTab('student')">Student</div>
                <div class="tab" onclick="switchTab('teacher')">Teacher</div>
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

            <div id="studentForm" class="form-section active">
                <form method="post" action="login_process.php">
                    <input type="hidden" name="type" value="student">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id" required placeholder="Enter your student ID">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>

            <div id="teacherForm" class="form-section">
                <form method="post" action="login_process.php">
                    <input type="hidden" name="type" value="teacher">
                    <div class="form-group">
                        <label>Teacher ID</label>
                        <input type="text" name="teacher_id" required placeholder="Enter your teacher ID">
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

    <script>
    function switchTab(type) {
        // Update tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Update forms
        document.querySelectorAll('.form-section').forEach(form => {
            form.classList.remove('active');
        });
        
        if (type === 'student') {
            document.getElementById('studentForm').classList.add('active');
        } else {
            document.getElementById('teacherForm').classList.add('active');
        }
    }
    </script>
</body>
</html>