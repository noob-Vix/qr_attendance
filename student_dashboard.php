<!-- student_dashboard.php -->
<?php
require_once 'config.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
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
                    <th>Status</th>
                </tr>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC, time DESC LIMIT 10");
                $stmt->execute([$student['student_id']]);
                while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>" . date('F d, Y', strtotime($row['date'])) . "</td>";
                    echo "<td>" . date('h:i A', strtotime($row['time'])) . "</td>";
                    echo "<td><span style='color: #28a745'>✓ Present</span></td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $student['student_id']; ?>",
            width: 128,
            height: 128
        });
    </script>
</body>
</html>