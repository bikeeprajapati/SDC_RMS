<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Clear any existing session
    session_unset();
    session_destroy();
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing admin query: " . $conn->error);
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get total students count
$sql = "SELECT COUNT(*) as total FROM students WHERE status = 'active'";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing students count query: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$total_students = $result->fetch_assoc()['total'];

// Get total grades count
$sql = "SELECT COUNT(*) as total FROM grades";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing grades count query: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$total_results = $result->fetch_assoc()['total'];

// Get recent grades
$sql = "SELECT g.*, s.first_name, s.last_name, c.course_name as program 
        FROM grades g 
        JOIN enrollments e ON g.enrollment_id = e.id
        JOIN students s ON e.student_id = s.id
        JOIN subjects sub ON e.subject_id = sub.id
        JOIN courses c ON sub.course_id = c.id
        ORDER BY g.created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing recent grades query: " . $conn->error);
}
$stmt->execute();
$recent_results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(45deg, #2c3e50, #1a252f);
            min-height: 100vh;
            padding: 2rem 0;
            position: fixed;
            width: 250px;
        }
        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }
        .sidebar-brand {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .sidebar-brand i {
            color: #0d6efd;
            margin-right: 0.5rem;
        }
        .nav-item {
            margin-bottom: 0.5rem;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .nav-link.active {
            color: white;
            background: #0d6efd;
        }
        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .dashboard-header {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .welcome-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .stats-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #0d6efd, #0a58ca);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    SDC RMS
                </a>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a href="results.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        Results
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item mt-5">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <div class="welcome-text">
                    Welcome back, <?php echo htmlspecialchars($admin['username']); ?>!
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stats-number"><?php echo $total_students; ?></div>
                        <div class="stats-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stats-number"><?php echo $total_results; ?></div>
                        <div class="stats-label">Total Results</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stats-number">4,567</div>
                        <div class="stats-label">Results Published</div>
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Symbol No</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th>Semester</th>
                                    <th>Total Marks</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($recent_results)): 
                                    $total_marks = $row['subject1'] + $row['subject2'] + $row['subject3'] + 
                                                 $row['subject4'] + $row['subject5'];
                                    $percentage = ($total_marks / 500) * 100;
                                    $grade = '';
                                    if ($percentage >= 90) $grade = 'A+';
                                    elseif ($percentage >= 80) $grade = 'A';
                                    elseif ($percentage >= 70) $grade = 'B+';
                                    elseif ($percentage >= 60) $grade = 'B';
                                    elseif ($percentage >= 50) $grade = 'C+';
                                    elseif ($percentage >= 40) $grade = 'C';
                                    else $grade = 'F';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['symbol_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['program']); ?></td>
                                    <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                    <td><?php echo $total_marks; ?>/500</td>
                                    <td><?php echo $grade; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 