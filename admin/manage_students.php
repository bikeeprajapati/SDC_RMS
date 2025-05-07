<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $student_id = (int)$_GET['id'];
    
    // Update student status to inactive
    $sql = "UPDATE students SET status = 'inactive' WHERE id = $student_id";
    
    if (mysqli_query($conn, $sql)) {
        // Also update related records
        $sql = "UPDATE student_classes SET status = 'inactive' WHERE student_id = $student_id";
        mysqli_query($conn, $sql);
        
        header('Location: manage_students.php?deleted=1');
        exit();
    }
}

// Get all active students with related data
$sql = "SELECT s.*, c.name as class_name, y.name as year_name, 
               s2.name as semester_name, sec.name as section_name
        FROM students s 
        LEFT JOIN student_classes sc ON s.id = sc.student_id 
        LEFT JOIN classes c ON sc.class_id = c.id 
        LEFT JOIN years y ON c.year_id = y.id 
        LEFT JOIN semesters s2 ON c.semester_id = s2.id 
        LEFT JOIN sections sec ON c.section_id = sec.id 
        WHERE s.status = 'active' 
        ORDER BY s.last_name, s.first_name";
$result = mysqli_query($conn, $sql);
$students = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - SDC RMS</title>
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
                        <h5 class="mb-0">Manage Students</h5>
                        <a href="add_student.php" class="btn btn-primary">Add New Student</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-success">Student has been deleted successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                            <div class="alert alert-success">Student has been added successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (empty($students)): ?>
                            <p class="text-muted">No students found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Class</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($student['class_name']) {
                                                        echo htmlspecialchars(
                                                            $student['class_name'] . ' (' . 
                                                            $student['year_name'] . ', ' .
                                                            $student['semester_name'] . ', ' .
                                                            $student['section_name'] . ')'
                                                        );
                                                    } else {
                                                        echo 'No class assigned';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
