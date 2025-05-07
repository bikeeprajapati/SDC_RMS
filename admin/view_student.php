<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get student ID from URL
if (!isset($_GET['id'])) {
    header('Location: manage_students.php');
    exit();
}

$student_id = (int)$_GET['id'];

// Get student details
$sql = "SELECT s.*, c.name as class_name, y.name as year_name, 
               s2.name as semester_name, sec.name as section_name
        FROM students s 
        LEFT JOIN student_classes sc ON s.id = sc.student_id 
        LEFT JOIN classes c ON sc.class_id = c.id 
        LEFT JOIN years y ON c.year_id = y.id 
        LEFT JOIN semesters s2 ON c.semester_id = s2.id 
        LEFT JOIN sections sec ON c.section_id = sec.id 
        WHERE s.id = $student_id";
$result = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($result);

// Get student's results
$results_sql = "SELECT g.*, e.subject_id, sub.subject_name, sub.units,
                      sub.year_level, sub.semester as subject_semester
               FROM grades g 
               JOIN enrollments e ON g.enrollment_id = e.id 
               JOIN subjects sub ON e.subject_id = sub.id 
               WHERE e.student_id = $student_id AND g.status = 'approved'
               ORDER BY sub.subject_name";
$results = mysqli_fetch_all(mysqli_query($conn, $results_sql), MYSQLI_ASSOC);

// Get student's attendance (if applicable)
$attendance_sql = "SELECT COUNT(*) as total_classes, 
                         SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                  FROM attendance 
                  WHERE student_id = $student_id";
$attendance = mysqli_fetch_assoc(mysqli_query($conn, $attendance_sql));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Student Details</h5>
                        <div>
                            <a href="edit_student.php?id=<?php echo $student_id; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="manage_students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Basic Information</h5>
                                <div class="mb-3">
                                    <strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($student['phone']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Class Information</h5>
                                <?php if ($student['class_name']): ?>
                                    <div class="mb-3">
                                        <strong>Class:</strong> <?php echo htmlspecialchars($student['class_name']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Year:</strong> <?php echo htmlspecialchars($student['year_name']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Semester:</strong> <?php echo htmlspecialchars($student['semester_name']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Section:</strong> <?php echo htmlspecialchars($student['section_name']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No class assigned yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Academic Performance</h5>
                            <?php if (empty($results)): ?>
                                <div class="alert alert-info">
                                    No results found for this student.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Units</th>
                                                <th>Midterm Grade</th>
                                                <th>Final Grade</th>
                                                <th>Overall Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['subject_name']); ?></td>
                                                    <td><?php echo $result['units']; ?></td>
                                                    <td><?php echo number_format($result['midterm_grade'], 2); ?></td>
                                                    <td><?php echo number_format($result['final_grade'], 2); ?></td>
                                                    <td>
                                                        <?php 
                                                        $overall = ($result['midterm_grade'] + $result['final_grade']) / 2;
                                                        echo number_format($overall, 2);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $overall = ($result['midterm_grade'] + $result['final_grade']) / 2;
                                                        echo ($overall >= 50) ? 'Passed' : 'Failed';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($attendance): ?>
                            <div class="mt-4">
                                <h5>Attendance</h5>
                                <div class="alert alert-info">
                                    Total Classes: <?php echo $attendance['total_classes']; ?>
                                    <br>
                                    Present: <?php echo $attendance['present']; ?>
                                    <br>
                                    Attendance Rate: <?php echo number_format(($attendance['total_classes'] > 0 ? $attendance['present'] / $attendance['total_classes'] * 100 : 0), 2); ?>%
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
