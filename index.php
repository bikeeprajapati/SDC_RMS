<?php
require_once 'config/database.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch recent notices
$notices = [];
try {
    $stmt = $conn->prepare("SELECT id, title, content, created_at FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $notices = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch(Exception $e) {
    error_log("Error fetching notices: " . $e->getMessage());
}

// Fetch active courses
$courses = [];
try {
    $stmt = $conn->prepare("SELECT id as course_id, course_code as code, course_name as name, is_semester_system as system_type FROM courses WHERE is_active = 1 ORDER BY course_name");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Fetch exam types
$exam_types = [];
try {
    $stmt = $conn->prepare("SELECT id as exam_type_id, type_name as name FROM exam_types WHERE is_active = 1 ORDER BY id");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $exam_types = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch(Exception $e) {
    error_log("Error fetching exam types: " . $e->getMessage());
}

// For debugging - check if we have data
echo "<!-- Debug Info:";
echo "\nNotices count: " . count($notices);
echo "\nCourses count: " . count($courses);
echo "\nExam Types count: " . count($exam_types);
echo "\n-->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Management System - Shanker Dev Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/tu-logo.png');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        .notice-board {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Shanker Dev Campus</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1>Result Management System</h1>
            <p class="lead">Check your semester results easily</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Result Check Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Check Your Result</h5>
                    </div>
                    <div class="card-body">
                        <form action="result.php" method="POST" id="resultForm">
                            <div class="mb-3">
                                <label for="course" class="form-label">Course</label>
                                <select class="form-select" id="course" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach($courses as $course): ?>
                                        <option value="<?php echo $course['course_id']; ?>" 
                                                data-system-type="<?php echo $course['system_type']; ?>">
                                            <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="roll_number" class="form-label">Roll Number</label>
                                <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                            </div>
                            <div class="mb-3">
                                <label for="academic_term" class="form-label">Academic Term</label>
                                <select class="form-select" id="academic_term" name="term_id" required disabled>
                                    <option value="">First select a course</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="exam_type" class="form-label">Exam Type</label>
                                <select class="form-select" id="exam_type" name="exam_type_id" required>
                                    <option value="">Select Exam Type</option>
                                    <?php foreach($exam_types as $type): ?>
                                        <option value="<?php echo $type['exam_type_id']; ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>View Result
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notice Board -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Notice Board</h5>
                    </div>
                    <div class="card-body notice-board">
                        <?php if(!empty($notices)): ?>
                            <?php foreach($notices as $notice): ?>
                                <div class="notice-item mb-4">
                                    <h6 class="text-primary"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                                    <small class="text-muted">Posted on: <?php echo date('d M Y', strtotime($notice['created_at'])); ?></small>
                                    <hr>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">No notices available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Shanker Dev Campus. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('course').addEventListener('change', function() {
        const courseSelect = this;
        const termSelect = document.getElementById('academic_term');
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        
        // Reset and disable term select if no course is selected
        if (!courseSelect.value) {
            termSelect.innerHTML = '<option value="">First select a course</option>';
            termSelect.disabled = true;
            return;
        }

        // Fetch academic terms for selected course
        fetch(`get_academic_terms.php?course_id=${courseSelect.value}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                termSelect.innerHTML = '<option value="">Select Academic Term</option>';
                data.forEach(term => {
                    const option = document.createElement('option');
                    option.value = term.id;
                    option.textContent = term.term_name;
                    termSelect.appendChild(option);
                });
                termSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                termSelect.innerHTML = '<option value="">Error loading terms</option>';
                termSelect.disabled = true;
            });
    });

    // Form validation
    document.getElementById('resultForm').addEventListener('submit', function(e) {
        const required = ['course', 'roll_number', 'academic_term', 'exam_type'];
        let isValid = true;

        required.forEach(field => {
            const element = document.getElementById(field);
            if (!element.value.trim()) {
                isValid = false;
                element.classList.add('is-invalid');
            } else {
                element.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
    </script>
</body>
</html> 