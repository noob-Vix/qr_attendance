<!-- student_dashboard.php -->
<?php
require_once 'config.php';

// Check if user is logged in as student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: index.php");
    exit();
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: Arial; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .qr-container { text-align: center; margin: 20px 0; }
        .attendance-list { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        .logout-btn { background: #f44336; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($student['name']); ?></h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="qr-container">
            <h3>Your Attendance QR Code</h3>
            <div id="qrcode"></div>
        </div>

        <div class="attendance-list">
            <h3>Your Attendance History</h3>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC, time DESC");
                $stmt->execute([$student['student_id']]);
                while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['time']) . "</td>";
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
