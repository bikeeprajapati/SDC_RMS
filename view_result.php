<?php
session_start();
require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roll_number = trim($_POST["roll_number"]);
    $semester = trim($_POST["semester"]);
    
    $sql = "SELECT s.name, s.roll_number, r.subject, r.marks, r.grade, r.semester 
            FROM students s 
            JOIN results r ON s.roll_number = r.roll_number 
            WHERE s.roll_number = ? AND r.semester = ?";
            
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $roll_number, $semester);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) > 0) {
                $student_info = mysqli_fetch_assoc($result);
                $total_marks = 0;
                $total_subjects = 0;
                mysqli_data_seek($result, 0);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Result - Result Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="assets/css/base.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <link href="assets/css/result.css" rel="stylesheet">
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

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .result-container {
            padding: 2rem 0;
        }

        .result-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .result-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .result-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></svg>') repeat;
            opacity: 0.1;
        }

        .student-info {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin: -2rem 2rem 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }

        .result-table {
            margin: 0;
        }

        .result-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .grade-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .grade-a-plus { background: var(--success-color); color: white; }
        .grade-a { background: var(--success-color); color: white; }
        .grade-b { background: var(--accent-color); color: white; }
        .grade-c { background: var(--warning-color); color: white; }
        .grade-d { background: var(--warning-color); color: white; }
        .grade-f { background: var(--danger-color); color: white; }

        .btn-back {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78,115,223,0.4);
            color: white;
        }

        .performance-indicator {
            width: 150px;
            height: 150px;
            position: relative;
            margin: 0 auto;
        }

        .performance-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(var(--primary-color) 0% var(--percentage), #e9ecef var(--percentage) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .performance-circle::before {
            content: '';
            position: absolute;
            width: 80%;
            height: 80%;
            background: white;
            border-radius: 50%;
        }

        .performance-text {
            position: relative;
            z-index: 1;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Result Management System
            </a>
        </div>
    </nav>

    <!-- Result Container -->
    <div class="result-container">
        <div class="container">
            <?php if(isset($student_info)): ?>
                <div class="result-card" data-aos="fade-up">
                    <div class="result-header">
                        <h2 class="mb-0">Result Card</h2>
                    </div>
                    
                    <div class="student-info">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-3"><?php echo htmlspecialchars($student_info['name']); ?></h4>
                                <p class="mb-1"><strong>Roll Number:</strong> <?php echo htmlspecialchars($student_info['roll_number']); ?></p>
                                <p class="mb-0"><strong>Semester:</strong> <?php echo htmlspecialchars($student_info['semester']); ?></p>
                            </div>
                            <div class="col-md-6 text-center">
                                <div class="performance-indicator">
                                    <div class="performance-circle" style="--percentage: <?php 
                                        $percentage = ($total_marks / ($total_subjects * 100)) * 100;
                                        echo $percentage;
                                    ?>%">
                                        <div class="performance-text">
                                            <?php echo round($percentage); ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover result-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($result, 0);
                                    while($row = mysqli_fetch_assoc($result)): 
                                        $total_marks += $row['marks'];
                                        $total_subjects++;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($row['marks']); ?></td>
                                        <td>
                                            <span class="grade-badge grade-<?php echo strtolower($row['grade']); ?>">
                                                <?php echo htmlspecialchars($row['grade']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th><?php echo $total_marks; ?></th>
                                        <th>
                                            <?php 
                                            $percentage = ($total_marks / ($total_subjects * 100)) * 100;
                                            $grade = "";
                                            if($percentage >= 90) $grade = "A+";
                                            elseif($percentage >= 80) $grade = "A";
                                            elseif($percentage >= 70) $grade = "B";
                                            elseif($percentage >= 60) $grade = "C";
                                            elseif($percentage >= 50) $grade = "D";
                                            else $grade = "F";
                                            ?>
                                            <span class="grade-badge grade-<?php echo strtolower($grade); ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    No result found for the given roll number and semester.
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html> 