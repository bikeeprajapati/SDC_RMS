<?php
require_once __DIR__ . '/session.php';
requireLogin();

$currentAdmin = getCurrentAdmin();

// Get current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMS Admin - Shanker Dev Campus</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            padding-top: 60px; /* Height of navbar */
            background-color: #f8f9fa;
        }
        
        /* Navbar styles */
        .navbar {
            height: 60px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            z-index: 1030;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 60px; /* Start below navbar */
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 1px 0 10px rgba(0,0,0,.1);
            overflow-y: auto;
            z-index: 1020;
        }
        
        .sidebar .nav-link {
            color: #333;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        
        .sidebar .nav-link.active {
            background-color: #e9ecef;
            color: #0d6efd;
            font-weight: 500;
        }
        
        .sidebar .nav-link i {
            width: 1.25rem;
            text-align: center;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: calc(100vh - 60px);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            body.sidebar-open .main-content {
                opacity: 0.5;
            }
        }
        
        /* Utility classes */
        .navbar-brand img {
            height: 40px;
            width: auto;
        }
        
        .dropdown-menu {
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link d-lg-none me-2" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/tu-logo.png" alt="Shanker Dev Campus">
            </a>
            <div class="ms-auto">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <span class="ms-1"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'courses.php' ? 'active' : ''; ?>" href="courses.php">
                    <i class="bi bi-book"></i>Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="bi bi-people"></i>Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>" href="subjects.php">
                    <i class="bi bi-journal-text"></i>Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'results.php' ? 'active' : ''; ?>" href="results.php">
                    <i class="bi bi-card-checklist"></i>Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'exam_types.php' ? 'active' : ''; ?>" href="exam_types.php">
                    <i class="bi bi-calendar-check"></i>Exam Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>" href="notices.php">
                    <i class="bi bi-megaphone"></i>Notices
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container-fluid px-0">

<!-- Add necessary JavaScript at the bottom of the file -->
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.body.classList.toggle('sidebar-open');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (window.innerWidth <= 768 && 
        sidebar.classList.contains('show') && 
        !sidebar.contains(event.target) && 
        !sidebarToggle.contains(event.target)) {
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }
});
</script> 