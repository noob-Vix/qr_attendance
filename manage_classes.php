<?php
require_once 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if PhpSpreadsheet is installed
$phpspreadsheet_installed = class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet');

// Check login and redirect if not a teacher
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Handle file import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import' && $phpspreadsheet_installed) {
    handleImport($pdo, $teacher_id);
}

// Handle class export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export' && $phpspreadsheet_installed) {
    handleExport($pdo, $teacher_id);
}

// Handle other class actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'create_class':
            $class_name = trim($_POST['class_name']);
            $stmt = $pdo->prepare("INSERT INTO classes (teacher_id, name) VALUES (?, ?)");
            $stmt->execute([$teacher_id, $class_name]);
            $_SESSION['success'] = "Class created successfully";
            break;
            
        case 'add_student':
            addStudent($pdo, $_POST['class_id'], $_POST['student_id'], $teacher_id);
            break;
            
        case 'remove_student':
            $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ? AND student_id = ?");
            $stmt->execute([$_POST['class_id'], $_POST['student_id']]);
            $_SESSION['success'] = "Student removed successfully";
            break;
            
        case 'delete_class':
            deleteClass($pdo, $_POST['class_id'], $teacher_id);
            break;
    }
    
    if (!in_array($_POST['action'], ['export', 'import'])) {
        header("Location: manage_classes.php");
        exit();
    }
}

// Helper functions
function addStudent($pdo, $class_id, $student_id, $teacher_id) {
    // Verify class belongs to teacher
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Invalid class selected";
        return;
    }
    
    // Check if student exists
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    if ($stmt->rowCount() > 0) {
        // Check if student is already in one of THIS teacher's classes
        $stmt = $pdo->prepare("
            SELECT cs.class_id, c.name 
            FROM class_students cs
            JOIN classes c ON cs.class_id = c.id
            WHERE cs.student_id = ? AND c.teacher_id = ?
        ");
        $stmt->execute([$student_id, $teacher_id]);
        $existing_enrollment = $stmt->fetch();
        
        if ($existing_enrollment) {
            if ($existing_enrollment['class_id'] == $class_id) {
                $_SESSION['error'] = "Student is already enrolled in this class";
            } else {
                $_SESSION['error'] = "Student is already enrolled in your class: " . $existing_enrollment['name'] . 
                                    ". A student can only be enrolled in one class per teacher.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
            $stmt->execute([$class_id, $student_id]);
            $_SESSION['success'] = "Student added successfully";
        }
    } else {
        $_SESSION['error'] = "Student ID not found";
    }
}

function deleteClass($pdo, $class_id, $teacher_id) {
    // Begin transaction to ensure all operations succeed or fail together
    $pdo->beginTransaction();
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        if ($stmt->rowCount() > 0) {
            // Delete attendance records for this class
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            // Delete schedules for this class
            $stmt = $pdo->prepare("DELETE FROM class_schedules WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            // Delete students from class
            $stmt = $pdo->prepare("DELETE FROM class_students WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            // Delete class
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->execute([$class_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Class deleted successfully";
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = "You don't have permission to delete this class";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting class: " . $e->getMessage();
    }
}

function handleImport($pdo, $teacher_id) {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Error uploading file. Please try again.";
        header("Location: manage_classes.php");
        exit();
    }

    $class_id = $_POST['class_id'];
    
    // Validate class ownership
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Invalid class selected";
        header("Location: manage_classes.php");
        exit();
    }
    
    // Process file
    $file_extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['xlsx', 'xls', 'csv'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        $_SESSION['error'] = "Invalid file format. Please upload an Excel file (.xlsx, .xls) or CSV file.";
        header("Location: manage_classes.php");
        exit();
    }
    
    try {
        if ($file_extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($file_extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        
        $spreadsheet = $reader->load($_FILES['excel_file']['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row if it exists
        $start_row = (isset($_POST['has_header']) && $_POST['has_header'] == 1) ? 1 : 0;
        
        // Begin transaction
        $pdo->beginTransaction();
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        for ($i = $start_row; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty($row[0])) continue;
            
            $student_id = trim($row[0]);
            
            // Check if student exists
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                // Create new student if we have enough data
                if (!empty($row[1]) && !empty($row[2])) {
                    $name = trim($row[1]);
                    $email = trim($row[2]);
                    $default_password = password_hash('changeme123', PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, password) VALUES (?, ?, ?, ?)");
                    if (!$stmt->execute([$student_id, $name, $email, $default_password])) {
                        $errors[] = "Failed to create student: $student_id";
                        continue;
                    }
                } else {
                    $errors[] = "Incomplete data for student: $student_id";
                    $skipped++;
                    continue;
                }
            }
            
            // Check if student is already enrolled in one of this teacher's classes
            $stmt = $pdo->prepare("
                SELECT c.name 
                FROM class_students cs 
                JOIN classes c ON cs.class_id = c.id 
                WHERE cs.student_id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$student_id, $teacher_id]);
            $existing_class = $stmt->fetch();
            
            if ($existing_class) {
                $errors[] = "Student $student_id is already enrolled in your class: " . $existing_class['name'];
                $skipped++;
                continue;
            }
            
            // Add student to class
            $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
            if ($stmt->execute([$class_id, $student_id])) {
                $imported++;
            } else {
                $errors[] = "Failed to enroll student: $student_id";
                $skipped++;
            }
        }
        
        $pdo->commit();
        
        $message = "Successfully imported $imported students.";
        if ($skipped > 0) {
            $message .= " Skipped $skipped students.";
        }
        
        $_SESSION['success'] = $message;
        
        if (!empty($errors)) {
            $_SESSION['import_errors'] = $errors;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing file: " . $e->getMessage();
    }
    
    header("Location: manage_classes.php");
    exit();
}

function handleExport($pdo, $teacher_id) {
    $class_id = $_POST['class_id'];
    
    // Validate class ownership
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $teacher_id]);
    $class = $stmt->fetch();
    
    if (!$class) {
        $_SESSION['error'] = "Invalid class selected";
        header("Location: manage_classes.php");
        exit();
    }
    
    // Get students in the class
    $stmt = $pdo->prepare("
        SELECT s.student_id, s.name, s.email 
        FROM students s
        JOIN class_students cs ON s.student_id = cs.student_id
        WHERE cs.class_id = ?
        ORDER BY s.name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
    
    // Create spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $sheet->setCellValue('A1', 'Student ID');
    $sheet->setCellValue('B1', 'Name');
    $sheet->setCellValue('C1', 'Email');
    
    // Add data
    $row = 2;
    foreach ($students as $student) {
        $sheet->setCellValue('A' . $row, $student['student_id']);
        $sheet->setCellValue('B' . $row, $student['name']);
        $sheet->setCellValue('C' . $row, $student['email']);
        $row++;
    }
    
    // Auto size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $class['name'] . '_roster.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Classes</title>
    <link rel="stylesheet" href="css/manage.css">
    <style>
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Manage Classes</h2>
            <a href="teacher_dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>

        <?php
        // Get teacher name
        $stmt = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch();
        if ($teacher) {
            echo '<div class="teacher-name">Teacher: ' . htmlspecialchars($teacher['name']) . '</div>';
        }
        ?>

        <div class="info-banner">
            <strong>Note:</strong> Students can be enrolled in multiple classes from different teachers, but only in one class per teacher.
        </div>

        <?php if (!$phpspreadsheet_installed): ?>
        <div class="excel-status">
            <strong>Excel Import/Export is not available.</strong> PhpSpreadsheet library is not installed.
        </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['import_errors']) && !empty($_SESSION['import_errors'])) {
            echo '<button type="button" class="collapsible">Show Import Errors (' . count($_SESSION['import_errors']) . ')</button>';
            echo '<div class="content">';
            echo '<div class="error-list"><ul>';
            foreach ($_SESSION['import_errors'] as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div></div>';
            unset($_SESSION['import_errors']);
        }
        ?>

        <!-- Create new class form -->
        <div class="class-card">
            <h3>Create New Class</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_class">
                <div class="form-group">
                    <input type="text" name="class_name" placeholder="Enter class name" required>
                    <button type="submit">Create Class</button>
                </div>
            </form>
        </div>

        <!-- List of classes -->
        <?php
        // Get only THIS teacher's classes
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY name");
        $stmt->execute([$teacher_id]);
        $classes = $stmt->fetchAll();

        if (count($classes) === 0) {
            echo '<div class="class-card"><p class="no-students">You haven\'t created any classes yet.</p></div>';
        }

        foreach ($classes as $class):
        ?>
        <div class="class-card">
            <div class="class-header">
                <h3><?= htmlspecialchars($class['name']) ?></h3>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this class? All student enrollments will be removed.');">
                    <input type="hidden" name="action" value="delete_class">
                    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                    <button type="submit" class="delete-class-btn">Delete Class</button>
                </form>
            </div>
            
            <div class="controls-container">
                <!-- Add student form -->
                <form method="post">
                    <input type="hidden" name="action" value="add_student">
                    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                    <div class="form-group">
                        <input type="text" name="student_id" placeholder="Enter student ID" required>
                        <button type="submit">Add Student</button>
                    </div>
                </form>

                <?php if ($phpspreadsheet_installed): ?>
                <!-- Export class button -->
                <form method="post" class="export-form">
                    <input type="hidden" name="action" value="export">
                    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                    <button type="submit">Export to Excel</button>
                </form>
                <?php endif; ?>
            </div>

            <?php if ($phpspreadsheet_installed): ?>
            <!-- Import students section -->
            <button type="button" class="collapsible">Import Students from Excel</button>
            <div class="content">
                <div class="import-export-container">
                    <form method="post" enctype="multipart/form-data" class="import-form">
                        <input type="hidden" name="action" value="import">
                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                        <div class="form-group">
                            <label for="excel_file">Select Excel file:</label>
                            <input type="file" name="excel_file" id="excel_file" required accept=".xlsx,.xls,.csv">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="has_header" value="1" checked> 
                                File has header row
                            </label>
                        </div>
                        <div>
                            <p><strong>Format:</strong> Column A: Student ID, Column B: Full Name, Column C: Email</p>
                            <p><small>New students will be created with default password: changeme123</small></p>
                        </div>
                        <button type="submit">Import Students</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- List of students -->
            <h4>Enrolled Students</h4>
            <?php
            $stmt = $pdo->prepare("
                SELECT s.* 
                FROM students s
                JOIN class_students cs ON s.student_id = cs.student_id
                WHERE cs.class_id = ?
                ORDER BY s.name
            ");
            $stmt->execute([$class['id']]);
            $students = $stmt->fetchAll();

            if (count($students) === 0) {
                echo '<p class="no-students">No students enrolled in this class.</p>';
            } else {
            ?>
            <table>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?= htmlspecialchars($student['student_id']) ?></td>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= htmlspecialchars($student['email']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="remove_student">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                            <button type="submit" class="remove-btn" onclick="return confirm('Are you sure you want to remove this student?')">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php } ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set up collapsible sections
            var coll = document.getElementsByClassName("collapsible");
            for (var i = 0; i < coll.length; i++) {
                coll[i].addEventListener("click", function() {
                    this.classList.toggle("active-collapsible");
                    var content = this.nextElementSibling;
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                    } else {
                        content.style.maxHeight = content.scrollHeight + "px";
                    }
                });
            }

            // Auto-expand error list if present
            var errorList = document.querySelector(".collapsible");
            if (errorList) {
                errorList.click();
            }
        });
    </script>
</body>
</html>