<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get grade ID from URL
if (!isset($_GET['id'])) {
    header('Location: manage_results.php');
    exit();
}

$grade_id = (int)$_GET['id'];

// Get grade details with related information
$sql = "SELECT g.*, e.*, s.first_name, s.last_name, s.email, s.phone,
               sub.subject_name, sub.units, sub.year_level, sub.semester as subject_semester,
               y.name as year_name, s2.name as semester_name, sec.name as section_name
        FROM grades g 
        JOIN enrollments e ON g.enrollment_id = e.id 
        JOIN students s ON e.student_id = s.id 
        JOIN subjects sub ON e.subject_id = sub.id 
        LEFT JOIN classes c ON c.id = (SELECT class_id FROM student_classes WHERE student_id = s.id)
        LEFT JOIN years y ON c.year_id = y.id 
        LEFT JOIN semesters s2 ON c.semester_id = s2.id 
        LEFT JOIN sections sec ON c.section_id = sec.id 
        WHERE g.id = $grade_id";
$result = mysqli_query($conn, $sql);
$grade = mysqli_fetch_assoc($result);

if (!$grade) {
    header('Location: manage_results.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Result - SDC RMS</title>
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
                        <h5 class="mb-0">Result Details</h5>
                        <div>
                            <a href="edit_result.php?id=<?php echo $grade_id; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="manage_results.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Student Information</h5>
                                <div class="mb-3">
                                    <strong>Student ID:</strong> <?php echo htmlspecialchars($grade['student_id']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($grade['email']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($grade['phone']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Subject Information</h5>
                                <div class="mb-3">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($grade['subject_name']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Units:</strong> <?php echo $grade['units']; ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Year Level:</strong> <?php echo htmlspecialchars($grade['year_level']); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Semester:</strong> <?php echo htmlspecialchars($grade['subject_semester']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Grade Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Academic Year:</strong> <?php echo htmlspecialchars($grade['academic_year']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Semester:</strong> <?php echo htmlspecialchars($grade['semester']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Midterm Grade:</strong> <?php echo number_format($grade['midterm_grade'], 2); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Final Grade:</strong> <?php echo number_format($grade['final_grade'], 2); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <h6>Overall Performance</h6>
                                <div class="alert alert-info">
                                    <strong>Overall Grade:</strong> <?php 
                                    $overall = ($grade['midterm_grade'] + $grade['final_grade']) / 2;
                                    echo number_format($overall, 2);
                                    ?>
                                    <br>
                                    <strong>Remarks:</strong> <?php 
                                    echo ($overall >= 50) ? 'Passed' : 'Failed';
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($grade['year_name'] || $grade['semester_name'] || $grade['section_name']): ?>
                            <div class="mt-4">
                                <h5>Class Information</h5>
                                <div class="alert alert-info">
                                    <strong>Year:</strong> <?php echo htmlspecialchars($grade['year_name']); ?>
                                    <br>
                                    <strong>Semester:</strong> <?php echo htmlspecialchars($grade['semester_name']); ?>
                                    <br>
                                    <strong>Section:</strong> <?php echo htmlspecialchars($grade['section_name']); ?>
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
