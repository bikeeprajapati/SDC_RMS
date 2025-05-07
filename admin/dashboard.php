<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/DashboardController.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$controller = new DashboardController($conn);
$stats = $controller->getDashboardStats();

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $upload_dir = __DIR__ . '/../uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['csv_file'];
    $temp_file = $file['tmp_name'];
    $file_name = 'results_' . date('Y-m-d_H-i-s') . '.csv';
    $target_file = $upload_dir . $file_name;
    
    if (move_uploaded_file($temp_file, $target_file)) {
        $result = $controller->importResults($target_file);
        $_SESSION['message'] = "Successfully imported {$result['success']} records. " . 
            (count($result['errors']) > 0 ? "Errors: " . implode(", ", $result['errors']) : "");
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="active">
            <div class="sidebar-header">
                <h3>SDC RMS</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#classMenu">
                        <i class="fas fa-chalkboard"></i> Classes <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="classMenu">
                        <li><a href="add_class.php"><i class="fas fa-plus"></i> Create Class</a></li>
                        <li><a href="manage_classes.php"><i class="fas fa-list"></i> Manage Classes</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#subjectMenu">
                        <i class="fas fa-book"></i> Subjects <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="subjectMenu">
                        <li><a href="add_subject.php"><i class="fas fa-plus"></i> Create Subject</a></li>
                        <li><a href="manage_subjects.php"><i class="fas fa-list"></i> Manage Subjects</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#studentMenu">
                        <i class="fas fa-user-graduate"></i> Students <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="studentMenu">
                        <li><a href="add_student.php"><i class="fas fa-plus"></i> Add Student</a></li>
                        <li><a href="manage_students.php"><i class="fas fa-list"></i> Manage Students</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#resultMenu">
                        <i class="fas fa-chart-bar"></i> Results <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="resultMenu">
                        <li><a href="add_result.php"><i class="fas fa-plus"></i> Add Result</a></li>
                        <li><a href="manage_results.php"><i class="fas fa-list"></i> Manage Results</a></li>
                    </ul>
                </li>

                <li class="dropdown">
                    <a href="#" data-bs-toggle="collapse" data-bs-target="#noticeMenu">
                        <i class="fas fa-bell"></i> Notices <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="noticeMenu">
                        <li><a href="add_notice.php"><i class="fas fa-plus"></i> Add Notice</a></li>
                        <li><a href="manage_notices.php"><i class="fas fa-list"></i> Manage Notices</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-3">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="notificationDropdown">
                                <li><a class="dropdown-item" href="#">No new notifications</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                                <li><a href="settings.php">Settings</a></li>
                                <li><a href="reset_password.php">Reset Password</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row mt-4">
                    <!-- Statistics Cards -->
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Total Students</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_students']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Total Classes</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_classes']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Total Subjects</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_subjects']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Recent Activities</h5>
                                <div class="list-group">
                                    <?php foreach ($stats['recent_activities'] as $activity): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CSV Upload Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Import Results via CSV</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Select CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="form-text">
                                    CSV format: student_id, subject_code, grade
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Import Results</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
        });
    </script>
</body>
</html>
