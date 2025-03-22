<?php
    session_start();
    $host = 'localhost';
    $dbname = 'qr_attendance';
    $username = 'root';
    $password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     // Create students table
     $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT PRIMARY KEY AUTO_INCREMENT,
        student_id VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        qr_code TEXT NOT NULL
    )");
    
    // Create admins table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    // Create teachers table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL
    )");

    // Create classes table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
    )");

    // Create class_students table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (student_id) REFERENCES students(student_id)
    )");

    // Create class_schedules table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        day_of_week INT NOT NULL,
        start_time TIME NOT NULL,
        grace_period INT NOT NULL DEFAULT 15,
        UNIQUE KEY unique_class_day (class_id, day_of_week),
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )");

    // Modify attendance table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) NOT NULL,
        class_id INT NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        status VARCHAR(10) DEFAULT 'absent',
        FOREIGN KEY (student_id) REFERENCES students(student_id),
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )");

    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admins (username, password) VALUES ('admin', '$admin_password')");
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>