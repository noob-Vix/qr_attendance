<?php
require_once 'config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Process password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify passwords match
    if ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $student_id]);
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "Current password is incorrect";
        }
    }
}

// Process email update
if (isset($_POST['update_email'])) {
    $new_email = $_POST['new_email'];
    
    // Check if email is already in use
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND id != ?");
    $stmt->execute([$new_email, $student_id]);
    if ($stmt->rowCount() > 0) {
        $email_error = "Email already in use by another account";
    } else {
        // Update email
        $stmt = $pdo->prepare("UPDATE students SET email = ? WHERE id = ?");
        $stmt->execute([$new_email, $student_id]);
        $email_success = "Email updated successfully!";
    }
}

// Get student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Get student class information
$stmt = $pdo->prepare("
    SELECT c.id as class_id, c.name as class_name, t.name as teacher_name 
    FROM class_students cs
    JOIN classes c ON cs.class_id = c.id
    JOIN teachers t ON c.teacher_id = t.teacher_id
    WHERE cs.student_id = ?
");
$stmt->execute([$student['student_id']]);
$class = $stmt->fetch();

// Get class schedule if enrolled in a class
$schedule = [];
if ($class) {
    $stmt = $pdo->prepare("
        SELECT *, 
        CASE day_of_week 
            WHEN 1 THEN 'Sunday'
            WHEN 2 THEN 'Monday'
            WHEN 3 THEN 'Tuesday'
            WHEN 4 THEN 'Wednesday'
            WHEN 5 THEN 'Thursday'
            WHEN 6 THEN 'Friday'
            WHEN 7 THEN 'Saturday'
        END as day_name
        FROM class_schedules 
        WHERE class_id = ?
        ORDER BY day_of_week
    ");
    $stmt->execute([$class['class_id']]);
    $schedule = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="css/student_dashboard.css">

</head>
<body>
    <div class="container">
        <div class="header">
            <h2 class="welcome-text">Welcome back, <?php echo htmlspecialchars($student['name']); ?>! 👋</h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab('dashboard')">Dashboard</div>
            <div class="tab" onclick="openTab('profile')">Profile</div>
            <div class="tab" onclick="openTab('schedule')">Schedule</div>
        </div>

        <div id="dashboard" class="tab-content active">
            <?php
            // Calculate attendance statistics
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total_attendance,
                COUNT(DISTINCT DATE(date)) as days_attended,
                MAX(date) as last_attendance
                FROM attendance 
                WHERE student_id = ?");
            $stmt->execute([$student['student_id']]);
            $stats = $stmt->fetch();
            ?>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_attendance']; ?></div>
                    <div class="stat-label">Total Check-ins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['days_attended']; ?></div>
                    <div class="stat-label">Days Attended</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['last_attendance'] ? date('M d', strtotime($stats['last_attendance'])) : 'N/A'; ?></div>
                    <div class="stat-label">Last Attendance</div>
                </div>
            </div>

            <div class="qr-container">
                <h3 class="section-title">Your Attendance QR Code</h3>
                <p>Show this code to your teacher to mark your attendance</p>
                <div id="qrcode"></div>
            </div>

            <div class="attendance-list">
                <h3 class="section-title">Recent Attendance History</h3>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Teacher</th>
                        <th>Status</th>
                    </tr>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT a.*, c.name as class_name, t.name as teacher_name
                        FROM attendance a
                        LEFT JOIN classes c ON a.class_id = c.id
                        LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
                        WHERE a.student_id = ? 
                        ORDER BY a.date DESC, a.time DESC 
                        LIMIT 10
                    ");
                    $stmt->execute([$student['student_id']]);
                    $has_records = false;
                    
                    while ($row = $stmt->fetch()) {
                        $has_records = true;
                        echo "<tr>";
                        echo "<td>" . date('F d, Y', strtotime($row['date'])) . "</td>";
                        echo "<td>" . date('h:i A', strtotime($row['time'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                        
                        $status = $row['status'] ?: 'on-time';
                        $status_class = '';
                        $status_text = 'Unknown';
                        
                        if ($status == 'on-time') {
                            $status_class = 'on-time';
                            $status_text = 'On Time';
                        } elseif ($status == 'late') {
                            $status_class = 'late';
                            $status_text = 'Late';
                        } elseif ($status == 'absent') {
                            $status_class = 'absent';
                            $status_text = 'Absent';
                        }
                        
                        echo "<td><span class='status-badge $status_class'>$status_text</span></td>";
                        echo "</tr>";
                    }
                    
                    if (!$has_records) {
                        echo "<tr><td colspan='4' style='text-align:center; padding:15px;'>No attendance records found</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
         
        <!-- Profile Tab -->
        <div id="profile" class="tab-content">
            <div class="profile-section">
                <h3>Your Profile</h3>
                
                <?php if (isset($password_success)): ?>
                <div class="alert alert-success"><?php echo $password_success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($email_success)): ?>
                <div class="alert alert-success"><?php echo $email_success; ?></div>
                <?php endif; ?>
                
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? 'Not set'); ?></p>
                
                <?php if ($class): ?>
                <p><strong>Class:</strong> <span class="class-badge"><?php echo htmlspecialchars($class['class_name']); ?></span></p>
                <p><strong>Teacher:</strong> <?php echo htmlspecialchars($class['teacher_name']); ?></p>
                <?php else: ?>
                <p><em>You're not enrolled in any class yet.</em></p>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <button class="btn" onclick="openModal('passwordModal')">Change Password</button>
                    <button class="btn" onclick="openModal('emailModal')">Update Email</button>
                </div>
            </div>
        </div>
        
        <!-- Schedule Tab -->
        <div id="schedule" class="tab-content">
            <h3>Class Schedule</h3>
            
            <?php if (!$class): ?>
                <div class="no-schedule">
                    <p>You're not enrolled in any class yet.</p>
                </div>
            <?php elseif (empty($schedule)): ?>
                <div class="no-schedule">
                    <p>No schedule has been set for your class yet.</p>
                </div>
            <?php else: ?>
                <div class="schedule-container">
                    <?php foreach ($schedule as $day): ?>
                        <div class="schedule-item">
                            <div class="day-name"><?php echo htmlspecialchars($day['day_name']); ?></div>
                            <div class="class-info">
                                <div><strong>Class:</strong> <?php echo htmlspecialchars($class['class_name']); ?></div>
                                <div><strong>Start Time:</strong> <?php echo date('h:i A', strtotime($day['start_time'])); ?></div>
                                <div><strong>Late After:</strong> <?php echo date('h:i A', strtotime($day['start_time'] . ' + ' . $day['grace_period'] . ' minutes')); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('passwordModal')">&times;</span>
            <h3 class="modal-title">Change Password</h3>
            
            <?php if (isset($password_error)): ?>
            <div class="alert alert-danger"><?php echo $password_error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="change_password" class="modal-submit">Change Password</button>
            </form>
        </div>
    </div>

    <!-- Email Update Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('emailModal')">&times;</span>
            <h3 class="modal-title">Update Email</h3>
            
            <?php if (isset($email_error)): ?>
            <div class="alert alert-danger"><?php echo $email_error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="new_email">New Email Address</label>
                    <input type="email" id="new_email" name="new_email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="update_email" class="modal-submit">Update Email</button>
            </form>
        </div>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $student['student_id']; ?>",
            width: 128,
            height: 128
        });

        function openTab(tabName) {
            var i, tabContent, tabs;
            
            tabContent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabContent.length; i++) {
                tabContent[i].classList.remove("active");
            }
            
            tabs = document.getElementsByClassName("tab");
            for (i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        
        // Close modal if clicked outside
        window.onclick = function(event) {
            if (event.target.className === "modal") {
                event.target.style.display = "none";
            }
        }
        
        // Show modal if there was an error
        <?php if (isset($password_error)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('passwordModal');
        });
        <?php endif; ?>
        
        <?php if (isset($email_error)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('emailModal');
        });
        <?php endif; ?>
    </script>
</body>
</html>