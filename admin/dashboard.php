<?php
session_start();

if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config/database.php";

// Get total students count
$sql = "SELECT COUNT(*) as total FROM students";
$result = mysqli_query($conn, $sql);
$total_students = mysqli_fetch_assoc($result)['total'];

// Get total results count
$sql = "SELECT COUNT(*) as total FROM results";
$result = mysqli_query($conn, $sql);
$total_results = mysqli_fetch_assoc($result)['total'];

// Get recent results
$sql = "SELECT r.*, s.name as student_name 
        FROM results r 
        JOIN students s ON r.roll_number = s.roll_number 
        ORDER BY r.id DESC LIMIT 5";
$recent_results = mysqli_query($conn, $sql);

// Get semester-wise statistics
$sql = "SELECT semester, COUNT(*) as count 
        FROM results 
        GROUP BY semester 
        ORDER BY semester";
$semester_stats = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Result Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="../assets/css/base.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/result.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #224abe;
            --accent-color: #36b9cc;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav-item {
            margin: 0.5rem 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .topbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-radius: 10px;
        }

        .welcome-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .recent-results {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .table {
            margin: 0;
        }

        .table th {
            font-weight: 600;
            color: #6c757d;
        }

        .grade-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .grade-a-plus { background: var(--success-color); color: white; }
        .grade-a { background: var(--success-color); color: white; }
        .grade-b { background: var(--accent-color); color: white; }
        .grade-c { background: var(--warning-color); color: white; }
        .grade-d { background: var(--warning-color); color: white; }
        .grade-f { background: var(--danger-color); color: white; }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .btn-logout {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-graduation-cap me-2"></i>
                RMS Admin
            </a>
        </div>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="students.php">
                    <i class="fas fa-user-graduate"></i>
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="results.php">
                    <i class="fas fa-chart-bar"></i>
                    Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_result.php">
                    <i class="fas fa-plus-circle"></i>
                    Add Result
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <div class="welcome-text">
                Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?>!
            </div>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-6" data-aos="fade-up">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_results; ?></div>
                    <div class="stat-label">Total Results</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Results -->
            <div class="col-md-8" data-aos="fade-up" data-aos-delay="200">
                <div class="recent-results">
                    <h5 class="mb-4">Recent Results</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($recent_results)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($row['marks']); ?></td>
                                    <td>
                                        <span class="grade-badge grade-<?php echo strtolower($row['grade']); ?>">
                                            <?php echo htmlspecialchars($row['grade']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Semester Statistics -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="chart-container">
                    <h5 class="mb-4">Semester-wise Results</h5>
                    <canvas id="semesterChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });

        // Semester Chart
        const ctx = document.getElementById('semesterChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    mysqli_data_seek($semester_stats, 0);
                    while($row = mysqli_fetch_assoc($semester_stats)) {
                        echo "'Semester " . $row['semester'] . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        mysqli_data_seek($semester_stats, 0);
                        while($row = mysqli_fetch_assoc($semester_stats)) {
                            echo $row['count'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b',
                        '#858796',
                        '#5a5c69',
                        '#2e59d9'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 