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
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing admin query: " . $conn->error);
}
$stmt->bind_param("i", $admin_id);
if (!$stmt->execute()) {
    die("Error executing admin query: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
    die("Error getting admin result: " . $conn->error);
}
$admin = $result->fetch_assoc();
if (!$admin) {
    die("Admin not found");
}

// Get current semester
$current_semester_sql = "SELECT * FROM semesters WHERE status = 'active' ORDER BY id DESC LIMIT 1";
$current_semester_result = $conn->query($current_semester_sql);
if (!$current_semester_result) {
    die("Error getting current semester: " . $conn->error);
}
$current_semester = $current_semester_result->fetch_assoc();

// Get semester-wise statistics
$semester_stats_sql = "SELECT 
    s.name as semester_name,
    COUNT(DISTINCT e.student_id) as total_students,
    COUNT(DISTINCT e.subject_id) as total_subjects,
    AVG(g.grade_point) as avg_gpa
FROM semesters s
LEFT JOIN enrollments e ON s.id = e.semester_id
LEFT JOIN grades g ON e.id = g.enrollment_id
WHERE s.status = 'active'
GROUP BY s.id
ORDER BY s.id DESC";
$semester_stats = $conn->query($semester_stats_sql);
if (!$semester_stats) {
    die("Error getting semester statistics: " . $conn->error);
}

// Initialize statistics variables
$total_students = 0;
$total_subjects = 0;
$avg_gpa = 0;

if ($semester_stats->num_rows > 0) {
    $row = $semester_stats->fetch_assoc();
    $total_students = $row['total_students'];
    $total_subjects = $row['total_subjects'];
    $avg_gpa = $row['avg_gpa'];
}

// Get subject-wise performance
$subject_performance_sql = "SELECT 
    sub.subject_name,
    COUNT(DISTINCT e.student_id) as enrolled_students,
    AVG(g.grade_point) as avg_grade_point,
    COUNT(CASE WHEN g.grade_point >= 2.0 THEN 1 END) as passed_students
FROM subjects sub
LEFT JOIN enrollments e ON sub.id = e.subject_id
LEFT JOIN grades g ON e.id = g.enrollment_id
WHERE sub.status = 'active'
GROUP BY sub.id
ORDER BY avg_grade_point DESC";
$subject_performance = $conn->query($subject_performance_sql);
if (!$subject_performance) {
    die("Error getting subject performance: " . $conn->error);
}

// Get recent activities
$recent_activities_sql = "SELECT 
    al.id,
    al.user_type,
    al.action,
    al.created_at
FROM audit_logs al
ORDER BY al.created_at DESC
LIMIT 10";
$recent_activities = $conn->query($recent_activities_sql);
if (!$recent_activities) {
    die("Error getting recent activities: " . $conn->error);
}

// Get pending verifications
$pending_verifications_sql = "SELECT 
    g.*,
    e.student_id,
    sub.subject_name,
    sem.name as semester_name
FROM grades g
JOIN enrollments e ON g.enrollment_id = e.id
JOIN subjects sub ON e.subject_id = sub.id
JOIN semesters sem ON e.semester_id = sem.id
WHERE g.status = 'pending'
ORDER BY g.created_at DESC
LIMIT 5";
$pending_verifications = $conn->query($pending_verifications_sql);
if (!$pending_verifications) {
    die("Error getting pending verifications: " . $conn->error);
}

// Get recent notices
$recent_notices_sql = "SELECT * FROM notices ORDER BY created_at DESC LIMIT 5";
$recent_notices = $conn->query($recent_notices_sql);
if (!$recent_notices) {
    die("Error getting recent notices: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIM Department Dashboard - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-bottom: 1rem;
        }
        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .stats-card h5 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .stats-item {
            padding: 1rem;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        .stats-item:hover {
            transform: translateY(-5px);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: none;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .notice-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        .notice-card:hover {
            transform: translateY(-2px);
            transition: transform 0.3s ease;
        }
        .chart-container {
            height: 300px;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .quick-action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
        }
        .quick-action-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header px-4">
                <a href="dashboard.php" class="text-white text-decoration-none">
                    <h4><i class="fas fa-graduation-cap me-2"></i>BIM RMS</h4>
                </a>
            </div>
            <ul class="nav flex-column px-3">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-white active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <!-- Results -->
                <li class="nav-item">
                    <a href="#resultSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-chart-bar me-2"></i>Results
                    </a>
                    <div class="collapse submenu" id="resultSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="verify_results.php" class="nav-link text-white">
                                    <i class="fas fa-check me-2"></i>Verify Results
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="generate_reports.php" class="nav-link text-white">
                                    <i class="fas fa-file-alt me-2"></i>Generate Reports
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- Subjects -->
                <li class="nav-item">
                    <a href="#subjectSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-book me-2"></i>Subjects
                    </a>
                    <div class="collapse submenu" id="subjectSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="add_subject.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Subject
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="subjects.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>Manage Subjects
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- Grade Settings -->
                <li class="nav-item">
                    <a href="grade_settings.php" class="nav-link text-white">
                        <i class="fas fa-cog me-2"></i>Grade Settings
                    </a>
                </li>
                <!-- Notices -->
                <li class="nav-item">
                    <a href="#noticeSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-bell me-2"></i>Notices
                    </a>
                    <div class="collapse submenu" id="noticeSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="add_notice.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Notice
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="manage_notices.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>Manage Notices
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- System -->
                <li class="nav-item">
                    <a href="#systemSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-cog me-2"></i>System
                    </a>
                    <div class="collapse submenu" id="systemSubmenu">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a href="settings.php" class="nav-link text-white">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="logout.php" class="nav-link text-white">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="dashboard-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="welcome-text">
                                    <h4>Welcome, <?php echo isset($admin['first_name']) ? htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) : 'Admin'; ?></h4>
                                    <p class="mb-0">Current Semester: <?php echo htmlspecialchars($current_semester['name']); ?></p>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="semesterDropdown" data-bs-toggle="dropdown">
                                            <?php echo htmlspecialchars($current_semester['name']); ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php
                                            $semesters_sql = "SELECT * FROM semesters ORDER BY name";
                                            $semesters = $conn->query($semesters_sql);
                                            while ($semester = $semesters->fetch_assoc()):
                                            ?>
                                            <li><a class="dropdown-item" href="?semester=<?php echo $semester['id']; ?>"><?php echo htmlspecialchars($semester['name']); ?></a></li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newNoticeModal">
                                        <i class="fas fa-bell me-2"></i>New Notice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h5>Quick Actions</h5>
                            <div class="quick-actions">
                                <div class="quick-action-card">
                                    <i class="fas fa-check"></i>
                                    <h6>Verify Results</h6>
                                    <p class="text-muted mb-0"><?php echo $pending_verifications->num_rows; ?> pending</p>
                                </div>
                                <div class="quick-action-card">
                                    <i class="fas fa-file-alt"></i>
                                    <h6>Generate Reports</h6>
                                    <p class="text-muted mb-0">Custom reports</p>
                                </div>
                                <div class="quick-action-card">
                                    <i class="fas fa-book"></i>
                                    <h6>Manage Subjects</h6>
                                    <p class="text-muted mb-0"><?php echo $total_subjects; ?> subjects</p>
                                </div>
                                <div class="quick-action-card">
                                    <i class="fas fa-users"></i>
                                    <h6>Student Management</h6>
                                    <p class="text-muted mb-0"><?php echo $total_students; ?> students</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-item bg-primary text-white">
                            <h6>Total Students</h6>
                            <h3><?php echo number_format($total_students); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-item bg-success text-white">
                            <h6>Total Subjects</h6>
                            <h3><?php echo number_format($total_subjects); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-item bg-info text-white">
                            <h6>Average GPA</h6>
                            <h3><?php echo number_format($avg_gpa, 2); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-item bg-warning text-white">
                            <h6>Pending Verifications</h6>
                            <h3><?php echo number_format($pending_verifications->num_rows); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Subject Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="subjectPerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Student Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="studentPerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['user_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Notices</h5>
                            </div>
                            <div class="card-body">
                                <?php while ($notice = $recent_notices->fetch_assoc()): ?>
                                <div class="notice-card">
                                    <h6><?php echo htmlspecialchars($notice['title']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo isset($notice['description']) ? htmlspecialchars($notice['description']) : 'No description available'; ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($notice['created_at'])); ?></small>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Notice Modal -->
    <div class="modal fade" id="newNoticeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_notice.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <?php
                                $semesters_sql = "SELECT * FROM semesters ORDER BY name";
                                $semesters = $conn->query($semesters_sql);
                                while ($semester = $semesters->fetch_assoc()):
                                ?>
                                <option value="<?php echo $semester['id']; ?>"><?php echo htmlspecialchars($semester['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize all collapse elements
        document.addEventListener('DOMContentLoaded', function() {
            var collapseElements = document.querySelectorAll('.collapse');
            collapseElements.forEach(function(element) {
                new bootstrap.Collapse(element);
            });
        });

        // Initialize charts
        var ctx1 = document.getElementById('subjectPerformanceChart').getContext('2d');
        var subjectPerformanceChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $subject_performance->data_seek(0);
                    while ($row = $subject_performance->fetch_assoc()):
                        echo "'" . addslashes($row['subject_name']) . "',";
                    endwhile;
                    ?>
                ],
                datasets: [{
                    label: 'Average Grade Point',
                    data: [
                        <?php
                        $subject_performance->data_seek(0);
                        while ($row = $subject_performance->fetch_assoc()):
                            echo $row['avg_grade_point'] . ",";
                        endwhile;
                        ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 4.0
                    }
                }
            }
        });

        var ctx2 = document.getElementById('studentPerformanceChart').getContext('2d');
        var studentPerformanceChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    $semester_stats->data_seek(0);
                    while ($row = $semester_stats->fetch_assoc()):
                        echo "'" . addslashes($row['semester_name']) . "',";
                    endwhile;
                    ?>
                ],
                datasets: [{
                    label: 'Average GPA',
                    data: [
                        <?php
                        $semester_stats->data_seek(0);
                        while ($row = $semester_stats->fetch_assoc()):
                            echo $row['avg_gpa'] . ",";
                        endwhile;
                        ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 4.0
                    }
                }
            }
        });
    </script>
</body>
</html>            font-size: 1.5rem;
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
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .verification-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .submenu {
            padding-left: 2rem;
            display: none;
        }
        .submenu.active {
            display: block;
        }
        .nav-link[data-bs-toggle="collapse"] {
            position: relative;
        }
        .nav-link[data-bs-toggle="collapse"]::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            transition: transform 0.3s;
        }
        .nav-link[data-bs-toggle="collapse"][aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        .notice-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .notice-item .badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header px-4">
                <a href="dashboard.php" class="text-white text-decoration-none">
                    <h4><i class="fas fa-graduation-cap me-2"></i>BIM RMS</h4>
                </a>
            </div>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-white active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                
                <!-- Class Management -->
                <li class="nav-item">
                    <a href="#classSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-chalkboard me-2"></i>Class Management
                    </a>
                    <div class="collapse submenu" id="classSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="classes.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>All Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_class.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add New Class
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Subject Management -->
                <li class="nav-item">
                    <a href="#subjectSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-book me-2"></i>Subjects
                    </a>
                    <div class="collapse submenu" id="subjectSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="subjects.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>All Subjects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_subject.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add New Subject
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Student Management -->
                <li class="nav-item">
                    <a href="#studentSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                    <div class="collapse submenu" id="studentSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="students.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>All Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_student.php" class="nav-link text-white">
                                    <i class="fas fa-user-plus me-2"></i>Add New Student
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="import_students.php" class="nav-link text-white">
                                    <i class="fas fa-file-import me-2"></i>Import Students
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Result Management -->
                <li class="nav-item">
                    <a href="#resultSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-chart-bar me-2"></i>Results
                    </a>
                    <div class="collapse submenu" id="resultSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="results.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>All Results
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_result.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Result
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="import_results.php" class="nav-link text-white">
                                    <i class="fas fa-file-import me-2"></i>Import Results
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Notices -->
                <li class="nav-item">
                    <a href="#noticeSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-bullhorn me-2"></i>Notices
                    </a>
                    <div class="collapse submenu" id="noticeSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="notices.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>All Notices
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_notice.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Notice
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="#settingsSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                    <div class="collapse submenu" id="settingsSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="change_password.php" class="nav-link text-white">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="profile.php" class="nav-link text-white">
                                    <i class="fas fa-user-cog me-2"></i>Profile Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-white">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h4>Welcome, <?php echo htmlspecialchars($admin['username']); ?>!</h4>
                            <p class="text-muted">BIM Department Result Management System</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Current Semester</h6>
                                    <h3><?php echo htmlspecialchars($current_semester['name'] ?? 'N/A'); ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total Students</h6>
                                    <h3><?php echo $total_students; ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Pending Verifications</h6>
                                    <h3><?php echo $pending_verifications->num_rows; ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Average GPA</h6>
                                    <h3><?php echo number_format($avg_gpa, 2); ?></h3>
                                </div>
                                <div class="sidebar-header px-4">
                <a href="dashboard.php" class="text-white text-decoration-none">
                    <h4><i class="fas fa-graduation-cap me-2"></i>BIM RMS</h4>
                </a>
            </div>

            <ul class="nav flex-column px-3">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-white active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>

                <!-- Result Management -->
                <li class="nav-item">
                    <a href="#resultSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-chart-bar me-2"></i>Results
                    </a>
                    <div class="collapse submenu" id="resultSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="add_result.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Result
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="results.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>View Results
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="verify_results.php" class="nav-link text-white">
                                    <i class="fas fa-check me-2"></i>Verify Results
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="generate_reports.php" class="nav-link text-white">
                                    <i class="fas fa-file-pdf me-2"></i>Generate Reports
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Academic Management -->
                <li class="nav-item">
                    <a href="#academicSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-graduation-cap me-2"></i>Academics
                    </a>
                    <div class="collapse submenu" id="academicSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="manage_semesters.php" class="nav-link text-white">
                                    <i class="fas fa-calendar-alt me-2"></i>Semesters
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="manage_subjects.php" class="nav-link text-white">
                                    <i class="fas fa-book me-2"></i>Subjects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="manage_courses.php" class="nav-link text-white">
                                    <i class="fas fa-graduation-cap me-2"></i>Courses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="manage_classes.php" class="nav-link text-white">
                                    <i class="fas fa-chalkboard me-2"></i>Classes
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Student Management -->
                <li class="nav-item">
                    <a href="#studentSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                    <div class="collapse submenu" id="studentSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="students.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>View Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_student.php" class="nav-link text-white">
                                    <i class="fas fa-user-plus me-2"></i>Add Student
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="student_enrollments.php" class="nav-link text-white">
                                    <i class="fas fa-file-alt me-2"></i>Student Enrollments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="student_performance.php" class="nav-link text-white">
                                    <i class="fas fa-chart-bar me-2"></i>Student Performance
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Notice Management -->
                <li class="nav-item">
                    <a href="#noticeSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-bullhorn me-2"></i>Notices
                    </a>
                    <div class="collapse submenu" id="noticeSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="notices.php" class="nav-link text-white">
                                    <i class="fas fa-list me-2"></i>View Notices
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="add_notice.php" class="nav-link text-white">
                                    <i class="fas fa-plus me-2"></i>Add Notice
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="notice_categories.php" class="nav-link text-white">
                                    <i class="fas fa-tags me-2"></i>Notice Categories
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="notice_archive.php" class="nav-link text-white">
                                    <i class="fas fa-archive me-2"></i>Notice Archive
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- System Management -->
                <li class="nav-item">
                    <a href="#systemSubmenu" class="nav-link text-white" data-bs-toggle="collapse" role="button">
                        <i class="fas fa-cog me-2"></i>System
                    </a>
                    <div class="collapse submenu" id="systemSubmenu">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a href="manage_admins.php" class="nav-link text-white">
                                    <i class="fas fa-user-shield me-2"></i>Admin Management
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="grade_settings.php" class="nav-link text-white">
                                    <i class="fas fa-cog me-2"></i>Grade Settings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="audit_logs.php" class="nav-link text-white">
                                    <i class="fas fa-history me-2"></i>Audit Logs
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="backup_restore.php" class="nav-link text-white">
                                    <i class="fas fa-database me-2"></i>Backup & Restore
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a href="settings.php" class="nav-link text-white">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-white">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Result Management Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Result Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="add_result.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-2"></i>Add New Result
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="results.php" class="btn btn-info w-100">
                                    <i class="fas fa-eye me-2"></i>View All Results
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="verify_results.php" class="btn btn-primary w-100">
                                    <i class="fas fa-check me-2"></i>Verify Results
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="generate_reports.php" class="btn btn-warning w-100">
                                    <i class="fas fa-file-pdf me-2"></i>Generate Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Management Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Academic Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="manage_semesters.php" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-alt me-2"></i>Semesters
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_subjects.php" class="btn btn-info w-100">
                                    <i class="fas fa-book me-2"></i>Subjects
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_courses.php" class="btn btn-success w-100">
                                    <i class="fas fa-graduation-cap me-2"></i>Courses
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_classes.php" class="btn btn-warning w-100">
                                    <i class="fas fa-chalkboard me-2"></i>Classes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Management Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Student Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="add_student.php" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-2"></i>Add Student
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="students.php" class="btn btn-info w-100">
                                    <i class="fas fa-users me-2"></i>View Students
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="student_enrollments.php" class="btn btn-primary w-100">
                                    <i class="fas fa-file-alt me-2"></i>Student Enrollments
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="student_performance.php" class="btn btn-warning w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Student Performance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notice Management Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Notice Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="add_notice.php" class="btn btn-primary w-100">
                                    <i class="fas fa-bullhorn me-2"></i>Add Notice
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="notices.php" class="btn btn-info w-100">
                                    <i class="fas fa-eye me-2"></i>View Notices
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="notice_categories.php" class="btn btn-success w-100">
                                    <i class="fas fa-tags me-2"></i>Notice Categories
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="notice_archive.php" class="btn btn-warning w-100">
                                    <i class="fas fa-archive me-2"></i>Notice Archive
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Management Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">System Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="manage_admins.php" class="btn btn-primary w-100">
                                    <i class="fas fa-user-shield me-2"></i>Admin Management
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="grade_settings.php" class="btn btn-info w-100">
                                    <i class="fas fa-cog me-2"></i>Grade Settings
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="audit_logs.php" class="btn btn-success w-100">
                                    <i class="fas fa-history me-2"></i>Audit Logs
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="backup_restore.php" class="btn btn-warning w-100">
                                    <i class="fas fa-database me-2"></i>Backup & Restore
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h5>Students Management</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="add_student.php" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i>Add Student
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="students.php" class="btn btn-info w-100">
                                        <i class="fas fa-users me-2"></i>View Students
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="edit_student.php" class="btn btn-warning w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Student
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="delete_student.php" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Student
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card mt-4">
                            <h5>Results Management</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="add_result.php" class="btn btn-success w-100">
                                        <i class="fas fa-plus me-2"></i>Add Result
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="results.php" class="btn btn-info w-100">
                                        <i class="fas fa-eye me-2"></i>View Results
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="edit_result.php" class="btn btn-warning w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Result
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="delete_result.php" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Result
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card mt-4">
                            <h5>Subjects Management</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="add_subject.php" class="btn btn-primary w-100">
                                        <i class="fas fa-book me-2"></i>Add Subject
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="subjects.php" class="btn btn-info w-100">
                                        <i class="fas fa-list me-2"></i>View Subjects
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="edit_subject.php" class="btn btn-warning w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Subject
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="delete_subject.php" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Subject
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card mt-4">
                            <h5>Notices Management</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="add_notice.php" class="btn btn-primary w-100">
                                        <i class="fas fa-bullhorn me-2"></i>Add Notice
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="notices.php" class="btn btn-info w-100">
                                        <i class="fas fa-eye me-2"></i>View Notices
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="edit_notice.php" class="btn btn-warning w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Notice
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="delete_notice.php" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Notice
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="stats-card mt-4">
                            <h5>Classes Management</h5>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="add_class.php" class="btn btn-primary w-100">
                                        <i class="fas fa-chalkboard me-2"></i>Add Class
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="classes.php" class="btn btn-info w-100">
                                        <i class="fas fa-list me-2"></i>View Classes
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="edit_class.php" class="btn btn-warning w-100">
                                        <i class="fas fa-edit me-2"></i>Edit Class
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="delete_class.php" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Class
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Semester-wise Performance</h5>
                            <canvas id="semesterChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Subject-wise Performance</h5>
                            <canvas id="subjectChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities, Notices, and Pending Verifications -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Recent Activities</h5>
                            <div class="activity-list">
                                <?php while($activity = $recent_activities->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Recent Notices</h5>
                            <div class="notice-list">
                                <?php while($notice = $recent_notices->fetch_assoc()): ?>
                                <div class="notice-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                            <p class="mb-0 text-muted"><?php echo substr(htmlspecialchars($notice['content']), 0, 100) . '...'; ?></p>
                                        </div>
                                        <span class="badge bg-primary">
                                            <?php echo date('M d', strtotime($notice['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h5>Pending Verifications</h5>
                            <div class="verification-list">
                                <?php while($verification = $pending_verifications->fetch_assoc()): ?>
                                <div class="verification-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">Student ID: <?php echo htmlspecialchars($verification['student_id']); ?></h6>
                                            <p class="mb-0 text-muted">
                                                <?php echo htmlspecialchars($verification['subject_name']); ?> - 
                                                <?php echo htmlspecialchars($verification['semester_name']); ?>
                                            </p>
                                        </div>
                                        <a href="verify_result.php?id=<?php echo $verification['id']; ?>" 
                                           class="btn btn-sm btn-primary">Verify</a>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize all collapse elements
        var collapseElements = document.querySelectorAll('.collapse');
        collapseElements.forEach(function(element) {
            new bootstrap.Collapse(element, {
                toggle: false
            });
        });

        // Semester-wise Performance Chart
        const semesterCtx = document.getElementById('semesterChart').getContext('2d');
        if (semesterCtx) {
            new Chart(semesterCtx, {
                type: 'line',
                data: {
                    labels: <?php 
                        $semester_stats->data_seek(0);
                        $labels = [];
                        $data = [];
                        while($row = $semester_stats->fetch_assoc()) {
                            $labels[] = $row['semester_name'];
                            $data[] = $row['avg_gpa'];
                        }
                        echo json_encode($labels);
                    ?>,
                    datasets: [{
                        label: 'Average GPA',
                        data: <?php echo json_encode($data); ?>,
                        borderColor: '#0d6efd',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4.0
                        }
                    }
                }
            });
        }

        // Subject-wise Performance Chart
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        if (subjectCtx) {
            new Chart(subjectCtx, {
                type: 'bar',
                data: {
                    labels: <?php 
                        $subject_performance->data_seek(0);
                        $labels = [];
                        $data = [];
                        while($row = $subject_performance->fetch_assoc()) {
                            $labels[] = $row['subject_name'];
                            $data[] = $row['avg_grade_point'];
                        }
                        echo json_encode($labels);
                    ?>,
                    datasets: [{
                        label: 'Average Grade Point',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: '#0d6efd'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 4.0
                        }
                    }
                }
            });
        }
    </script>
</body>
</html> 