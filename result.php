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
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 13px !important;
                min-height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .card-header {
                display: none !important;
            }
            .btn {
                display: none !important;
            }
            .card-body {
                padding: 1rem !important;
                flex: 1 !important;
                display: flex !important;
                flex-direction: column !important;
            }
            .table {
                border-collapse: collapse !important;
                width: 100% !important;
                font-size: 12px !important;
                margin: 1rem 0 !important;
            }
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 6px !important;
                line-height: 1.3 !important;
            }
            .table-light {
                background-color: #f8f9fa !important;
            }
            .text-muted {
                color: #000 !important;
                font-size: 12px !important;
                font-weight: bold !important;
            }
            .card.bg-light {
                background-color: #fff !important;
                border: 1px solid #000 !important;
                margin-top: 1rem !important;
            }
            .mt-4, .my-5 {
                margin-top: 1rem !important;
            }
            .mb-4 {
                margin-bottom: 1rem !important;
            }
            .h4 {
                font-size: 1.4rem !important;
                margin-bottom: 0.5rem !important;
                font-weight: bold !important;
            }
            .h6 {
                font-size: 1rem !important;
                margin-bottom: 0.25rem !important;
                font-weight: bold !important;
            }
            .text-center {
                text-align: center !important;
            }
            .text-end {
                text-align: right !important;
            }
            .text-md-end {
                text-align: right !important;
            }
            .d-flex {
                display: block !important;
            }
            .justify-content-between {
                justify-content: normal !important;
            }
            .align-items-center {
                align-items: normal !important;
            }
            .mb-0 {
                margin-bottom: 0 !important;
            }
            .mb-3 {
                margin-bottom: 0.5rem !important;
            }
            .mt-4 {
                margin-top: 1rem !important;
            }
            .row {
                display: flex !important;
                margin: 0 !important;
                flex-wrap: wrap !important;
            }
            .col-md-4, .col-md-6 {
                width: 50% !important;
                margin-bottom: 0.5rem !important;
                padding: 0 0.5rem !important;
            }
            .table-responsive {
                overflow: visible !important;
                margin: 1rem 0 !important;
            }
            .bg-primary {
                background-color: #fff !important;
            }
            .text-white {
                color: #000 !important;
            }
            .bg-light {
                background-color: #fff !important;
            }
            .shadow {
                box-shadow: none !important;
            }
            .btn-primary {
                display: none !important;
            }
            p {
                margin-bottom: 0.5rem !important;
                line-height: 1.4 !important;
            }
            .campus-header {
                text-align: center !important;
                margin-bottom: 1.5rem !important;
                border-bottom: 2px solid #000 !important;
                padding-bottom: 0.5rem !important;
            }
            .campus-header h2 {
                font-size: 1.8rem !important;
                margin: 0 !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            .campus-header p {
                font-size: 1rem !important;
                margin: 0.25rem 0 !important;
                font-weight: 500 !important;
            }
            .result-footer {
                margin-top: 2rem !important;
                border-top: 1px solid #000 !important;
                padding-top: 0.5rem !important;
            }
            .result-footer p {
                font-weight: 500 !important;
            }
            .table thead th {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                text-align: center !important;
            }
            .table tfoot td {
                font-weight: bold !important;
            }
            .card.bg-light .card-body {
                padding: 1rem !important;
            }
            .card.bg-light .row {
                margin: 0 !important;
            }
            .card.bg-light .col-md-4 {
                margin-bottom: 0.5rem !important;
            }
            .card.bg-light .h4 {
                font-size: 1.2rem !important;
                margin-bottom: 0.25rem !important;
            }
            .card.bg-light .h6 {
                font-size: 0.9rem !important;
                margin-bottom: 0.15rem !important;
            }
            strong {
                font-weight: 600 !important;
            }
        }
    </style>
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
                <!-- Campus Header -->
                <div class="campus-header">
                    <h2>Shanker Dev Campus</h2>
                    <p>Putalisadak, Kathmandu</p>
                    <p>Result Management System</p>
                </div>

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
                                    <td><?php echo htmlspecialchars($result['remarks'] ?? ''); ?></td>
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

                <!-- Footer -->
                <div class="result-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0">Date: <?php echo date('d-m-Y'); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0">Controller of Examination</p>
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