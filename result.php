<?php
require_once 'config/database.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$required_fields = ['course_id', 'roll_number', 'term_id', 'exam_type_id'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: index.php');
        exit;
    }
}

try {
    // Sanitize inputs
    $course_id = (int)$_POST['course_id'];
    $roll_number = trim($_POST['roll_number']);
    $term_id = (int)$_POST['term_id'];
    $exam_type_id = (int)$_POST['exam_type_id'];

    // Get student details
    $stmt = $conn->prepare("
        SELECT s.*, c.course_name, c.course_code 
        FROM students s
        JOIN courses c ON s.course_id = c.id
        WHERE s.roll_number = ? AND s.course_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Student query prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("si", $roll_number, $course_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Student query execute failed: " . $stmt->error);
    }
    
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        $_SESSION['error'] = 'Student not found';
        header('Location: index.php');
        exit;
    }

    // Get result details
    $stmt = $conn->prepare("
        SELECT r.*, s.subject_code, s.subject_name, s.max_marks,
               et.type_name as exam_type_name, at.term_name
        FROM results r
        JOIN subjects s ON r.subject_id = s.id
        JOIN exam_types et ON r.exam_type_id = et.id
        JOIN academic_terms at ON r.term_id = at.id
        WHERE r.student_id = ? AND r.term_id = ? AND r.exam_type_id = ?
        ORDER BY s.subject_code
    ");
    
    if (!$stmt) {
        throw new Exception("Results query prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iii", $student['id'], $term_id, $exam_type_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Results query execute failed: " . $stmt->error);
    }
    
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($results)) {
        $_SESSION['error'] = 'No results found for the selected criteria';
        header('Location: index.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Error in result.php: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while fetching the results: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result - <?php echo htmlspecialchars($student['roll_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Result Details</h4>
                    <button onclick="window.print()" class="btn btn-light btn-sm">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Student Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-3">Student Information</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>
                        <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_code'] . ' - ' . $student['course_name']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="text-muted mb-3">Examination Details</h5>
                        <p><strong>Term:</strong> <?php echo htmlspecialchars($results[0]['term_name']); ?></p>
                        <p><strong>Exam Type:</strong> <?php echo htmlspecialchars($results[0]['exam_type_name']); ?></p>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th class="text-center">Max Marks</th>
                                <th class="text-center">Marks Obtained</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Grade Point</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_marks = 0;
                            $total_max_marks = 0;
                            $total_grade_points = 0;
                            $subject_count = 0;
                            
                            foreach ($results as $result): 
                                $total_marks += $result['marks'];
                                $total_max_marks += $result['max_marks'];
                                $total_grade_points += $result['grade_point'];
                                $subject_count++;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                    <td class="text-center"><?php echo $result['max_marks']; ?></td>
                                    <td class="text-center"><?php echo $result['marks']; ?></td>
                                    <td class="text-center"><?php echo $result['grade']; ?></td>
                                    <td class="text-center"><?php echo number_format($result['grade_point'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($result['remarks']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                <td class="text-center"><?php echo $total_max_marks; ?></td>
                                <td class="text-center"><?php echo $total_marks; ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Result Summary -->
                <div class="card bg-light mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="text-muted">Total Marks</h6>
                                <p class="h4"><?php echo $total_marks . ' / ' . $total_max_marks; ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted">Percentage</h6>
                                <p class="h4"><?php echo number_format(($total_marks / $total_max_marks) * 100, 2); ?>%</p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted">GPA</h6>
                                <p class="h4"><?php echo number_format($total_grade_points / $subject_count, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary">Check Another Result</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 