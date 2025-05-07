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
    $grade_id = (int)$_GET['id'];
    
    // Get enrollment ID first
    $sql = "SELECT enrollment_id FROM grades WHERE id = $grade_id";
    $result = mysqli_query($conn, $sql);
    $grade = mysqli_fetch_assoc($result);
    
    if ($grade) {
        $enrollment_id = $grade['enrollment_id'];
        
        // Update grade status
        $sql = "UPDATE grades SET status = 'inactive' WHERE id = $grade_id";
        mysqli_query($conn, $sql);
        
        // Update enrollment status
        $sql = "UPDATE enrollments SET status = 'dropped' WHERE id = $enrollment_id";
        mysqli_query($conn, $sql);
        
        header('Location: manage_results.php?deleted=1');
        exit();
    }
}

// Get all active results with related data
$sql = "SELECT g.*, e.student_id, e.subject_id, e.academic_year, e.semester,
               s.first_name, s.last_name, sub.subject_name, sub.units
        FROM grades g 
        JOIN enrollments e ON g.enrollment_id = e.id 
        JOIN students s ON e.student_id = s.id 
        JOIN subjects sub ON e.subject_id = sub.id 
        WHERE g.status = 'approved' 
        ORDER BY s.last_name, s.first_name, sub.subject_name";
$result = mysqli_query($conn, $sql);
$results = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results - SDC RMS</title>
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
                        <h5 class="mb-0">Manage Results</h5>
                        <a href="add_result.php" class="btn btn-primary">Add New Result</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-success">Result has been deleted successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                            <div class="alert alert-success">Result has been added successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (empty($results)): ?>
                            <p class="text-muted">No results found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Subject</th>
                                            <th>Academic Year</th>
                                            <th>Semester</th>
                                            <th>Midterm Grade</th>
                                            <th>Final Grade</th>
                                            <th>Overall Grade</th>
                                            <th>Remarks</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars(
                                                        $result['first_name'] . ' ' . $result['last_name']
                                                    ); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($result['subject_name']); ?>
                                                    (<?php echo $result['units']; ?> units)
                                                </td>
                                                <td><?php echo htmlspecialchars($result['academic_year']); ?></td>
                                                <td><?php echo htmlspecialchars($result['semester']); ?></td>
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
                                                <td>
                                                    <a href="view_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $result['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this result?')">
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
