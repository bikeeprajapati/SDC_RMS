<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get all active students
$students_query = "SELECT s.*, c.name as class_name 
                  FROM students s 
                  LEFT JOIN student_classes sc ON s.id = sc.student_id 
                  LEFT JOIN classes c ON sc.class_id = c.id 
                  WHERE s.status = 'active'";
$students = mysqli_fetch_all(mysqli_query($conn, $students_query), MYSQLI_ASSOC);

// Get all active subjects
$subjects_query = "SELECT * FROM subjects WHERE status = 'active' ORDER BY subject_name";
$subjects = mysqli_fetch_all(mysqli_query($conn, $subjects_query), MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    $midterm_grade = (float)$_POST['midterm_grade'];
    $final_grade = (float)$_POST['final_grade'];
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);

    // Calculate overall grade and remarks
    $overall_grade = ($midterm_grade + $final_grade) / 2;
    $remarks = ($overall_grade >= 50) ? 'Passed' : 'Failed';

    // Check if enrollment exists
    $enrollment_sql = "INSERT INTO enrollments (student_id, subject_id, academic_year, semester) 
                      VALUES ($student_id, $subject_id, '$academic_year', '$semester')";
    
    if (mysqli_query($conn, $enrollment_sql)) {
        $enrollment_id = mysqli_insert_id($conn);
        
        // Insert grade
        $grade_sql = "INSERT INTO grades (enrollment_id, midterm_grade, final_grade, remarks) 
                     VALUES ($enrollment_id, $midterm_grade, $final_grade, '$remarks')";
        
        if (mysqli_query($conn, $grade_sql)) {
            header('Location: manage_results.php?success=1');
            exit();
        } else {
            $error = "Error adding grade: " . mysqli_error($conn);
        }
    } else {
        $error = "Error creating enrollment: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Result - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Result</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            (<?php echo htmlspecialchars($student['class_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester">1st Semester</option>
                                        <option value="2nd Semester">2nd Semester</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="midterm_grade" class="form-label">Midterm Grade</label>
                                    <input type="number" class="form-control" id="midterm_grade" name="midterm_grade" 
                                           min="0" max="100" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="final_grade" class="form-label">Final Grade</label>
                                    <input type="number" class="form-control" id="final_grade" name="final_grade" 
                                           min="0" max="100" step="0.01" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Result</button>
                            <a href="manage_results.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
