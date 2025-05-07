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
    $class_id = (int)$_GET['id'];
    $sql = "UPDATE classes SET status = 'inactive' WHERE id = $class_id";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: manage_classes.php?deleted=1');
        exit();
    }
}

// Get all active classes with related data
$sql = "SELECT c.*, y.name as year_name, s.name as semester_name, sec.name as section_name 
        FROM classes c 
        JOIN years y ON c.year_id = y.id 
        JOIN semesters s ON c.semester_id = s.id 
        JOIN sections sec ON c.section_id = sec.id 
        WHERE c.status = 'active' 
        ORDER BY c.name";
$result = mysqli_query($conn, $sql);
$classes = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - SDC RMS</title>
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
                        <h5 class="mb-0">Manage Classes</h5>
                        <a href="add_class.php" class="btn btn-primary">Add New Class</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-success">Class has been deleted successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                            <div class="alert alert-success">Class has been added successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (empty($classes)): ?>
                            <p class="text-muted">No classes found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Section</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $class): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['year_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['semester_name']); ?></td>
                                                <td><?php echo htmlspecialchars($class['section_name']); ?></td>
                                                <td>
                                                    <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this class?')">
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
