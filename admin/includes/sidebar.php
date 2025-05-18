<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <h5 class="mb-0">RMS Admin</h5>
    </div>
    <div class="sidebar-menu p-2">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'courses.php' ? 'active' : ''; ?>" 
                   href="courses.php">
                    <i class="bi bi-book me-2"></i> Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'students.php' ? 'active' : ''; ?>" 
                   href="students.php">
                    <i class="bi bi-people me-2"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'subjects.php' ? 'active' : ''; ?>" 
                   href="subjects.php">
                    <i class="bi bi-journal-text me-2"></i> Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>" 
                   href="results.php">
                    <i class="bi bi-card-checklist me-2"></i> Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'exam_types.php' ? 'active' : ''; ?>" 
                   href="exam_types.php">
                    <i class="bi bi-list-check me-2"></i> Exam Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>" 
                   href="notices.php">
                    <i class="bi bi-bell me-2"></i> Notices
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" 
                   href="settings.php">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
}

.sidebar-menu .nav-link {
    color: rgba(255,255,255,.8);
    padding: 0.8rem 1rem;
    border-radius: 0.25rem;
    transition: all 0.3s;
}

.sidebar-menu .nav-link:hover {
    color: #fff;
    background-color: rgba(255,255,255,.1);
}

.sidebar-menu .nav-link.active {
    color: #fff;
    background-color: #0d6efd;
}

.main-content {
    margin-left: 250px;
}
</style> 