<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get semesters for dropdown
$semester_sql = "SELECT id, name FROM semesters WHERE status = 'active' ORDER BY name";
$semesters = $conn->query($semester_sql);

// Get subjects for dropdown
$subject_sql = "SELECT id, subject_name FROM subjects WHERE status = 'active' ORDER BY subject_name";
$subjects = $conn->query($subject_sql);

// Get classes for dropdown
$class_sql = "SELECT id, name FROM classes WHERE status = 'active' ORDER BY name";
$classes = $conn->query($class_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'];
    $semester_id = $_POST['semester_id'];
    $class_id = $_POST['class_id'];
    $subject_id = isset($_POST['subject_id']) ? $_POST['subject_id'] : null;

    // Generate report based on type
    switch ($report_type) {
        case 'semester':
            // Get semester-wise report
            $sql = "SELECT 
                s.first_name,
                s.last_name,
                sub.subject_name,
                g.total_marks,
                g.grade_point,
                g.letter_grade
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            JOIN subjects sub ON e.subject_id = sub.id
            JOIN grades g ON e.id = g.enrollment_id
            WHERE e.semester_id = ? AND s.class_id = ?
            ORDER BY s.first_name, s.last_name, sub.subject_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $semester_id, $class_id);
            break;

        case 'subject':
            // Get subject-wise report
            $sql = "SELECT 
                s.first_name,
                s.last_name,
                sub.subject_name,
                g.total_marks,
                g.grade_point,
                g.letter_grade
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            JOIN subjects sub ON e.subject_id = sub.id
            JOIN grades g ON e.id = g.enrollment_id
            WHERE e.semester_id = ? AND s.class_id = ? AND sub.id = ?
            ORDER BY s.first_name, s.last_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $semester_id, $class_id, $subject_id);
            break;

        case 'student':
            // Get student-wise report
            $sql = "SELECT 
                s.first_name,
                s.last_name,
                sub.subject_name,
                g.total_marks,
                g.grade_point,
                g.letter_grade
            FROM students s
            JOIN enrollments e ON s.id = e.student_id
            JOIN subjects sub ON e.subject_id = sub.id
            JOIN grades g ON e.id = g.enrollment_id
            WHERE e.semester_id = ? AND s.class_id = ?
            GROUP BY s.id
            ORDER BY s.first_name, s.last_name";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $semester_id, $class_id);
            break;
    }

    if ($stmt) {
        $stmt->execute();
        $results = $stmt->get_result();
        
        // Generate PDF report
        require_once '../vendor/autoload.php';
        use Dompdf\Dompdf;

        $dompdf = new Dompdf();
        
        $html = "<html><body>
            <h1>Result Report</h1>
            <h3>Report Type: " . ucfirst($report_type) . "</h3>
            <h4>Semester: " . $semester['name'] . "</h4>
            <table border='1' cellpadding='5'>
                <tr>
                    <th>Student Name</th>
                    <th>Subject</th>
                    <th>Total Marks</th>
                    <th>Grade Point</th>
                    <th>Letter Grade</th>
                </tr>";

        while($row = $results->fetch_assoc()) {
            $html .= "<tr>
                <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                <td>" . htmlspecialchars($row['subject_name']) . "</td>
                <td>" . htmlspecialchars($row['total_marks']) . "</td>
                <td>" . htmlspecialchars($row['grade_point']) . "</td>
                <td>" . htmlspecialchars($row['letter_grade']) . "</td>
            </tr>";
        }

        $html .= "</table></body></html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = "result_report_" . date('Y-m-d_H-i-s') . ".pdf";
        $dompdf->stream($filename);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Generate Reports</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Report Type</label>
                                    <select class="form-select" name="report_type" required>
                                        <option value="semester">Semester-wise</option>
                                        <option value="subject">Subject-wise</option>
                                        <option value="student">Student-wise</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Semester</label>
                                    <select class="form-select" name="semester_id" required>
                                        <option value="">Select Semester</option>
                                        <?php while($semester = $semesters->fetch_assoc()): ?>
                                            <option value="<?php echo $semester['id']; ?>"><?php echo htmlspecialchars($semester['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Class</label>
                                    <select class="form-select" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php while($class = $classes->fetch_assoc()): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 subject-select" style="display: none;">
                                    <label class="form-label">Subject</label>
                                    <select class="form-select" name="subject_id">
                                        <option value="">Select Subject</option>
                                        <?php while($subject = $subjects->fetch_assoc()): ?>
                                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportTypeSelect = document.querySelector('select[name="report_type"]');
            const subjectSelect = document.querySelector('.subject-select');

            reportTypeSelect.addEventListener('change', function() {
                subjectSelect.style.display = this.value === 'subject' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
