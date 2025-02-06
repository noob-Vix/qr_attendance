<!-- admin_dashboard.php -->
<?php
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .nav-links { margin: 20px 0; }
        .nav-links a { margin-right: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; }
        .logout-btn { background: #f44336; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Admin Dashboard</h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="nav-links">
            <a href="scanner.php">Open Scanner</a>
        </div>

        <h3>All Students</h3>
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Last Attendance</th>
            </tr>
            <?php
            $stmt = $pdo->query("
                SELECT s.*, MAX(a.date) as last_attendance 
                FROM students s 
                LEFT JOIN attendance a ON s.student_id = a.student_id 
                GROUP BY s.id
            ");
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                echo "<td>" . ($row['last_attendance'] ?? 'Never') . "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <h3>Today's Attendance</h3>
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Time</th>
            </tr>
            <?php
            $stmt = $pdo->query("
                SELECT a.*, s.name 
                FROM attendance a 
                JOIN students s ON a.student_id = s.student_id 
                WHERE a.date = CURRENT_DATE()
                ORDER BY a.time DESC
            ");
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['time']) . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>