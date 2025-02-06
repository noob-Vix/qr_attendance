<!DOCTYPE html>
<html>
<head>
    <title>View Attendance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width: 800px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Attendance Records</h2>
    <table>
        <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Date</th>
            <th>Time</th>
        </tr>
        <?php
        require_once 'config.php';
        
        try {
            $stmt = $pdo->query("
                SELECT a.*, s.name 
                FROM attendance a 
                JOIN students s ON a.student_id = s.student_id 
                ORDER BY a.date DESC, a.time DESC
            ");
            
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['time']) . "</td>";
                echo "</tr>";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>
    </table>
</body>
</html>