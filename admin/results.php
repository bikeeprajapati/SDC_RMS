<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config/database.php";

// Initialize variables
$symbol_no = $semester = $subject1 = $subject2 = $subject3 = $subject4 = $subject5 = "";
$symbol_err = $semester_err = $subject_err = "";
$success_msg = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate symbol number
    if(empty(trim($_POST["symbol_no"]))){
        $symbol_err = "Please select student.";
    } else {
        $symbol_no = trim($_POST["symbol_no"]);
    }
    
    // Validate semester
    if(empty(trim($_POST["semester"]))){
        $semester_err = "Please select semester.";
    } else {
        $semester = trim($_POST["semester"]);
    }
    
    // Validate subjects
    $subjects = ['subject1', 'subject2', 'subject3', 'subject4', 'subject5'];
    $subject_err = "";
    foreach($subjects as $subject) {
        if(empty(trim($_POST[$subject])) || !is_numeric($_POST[$subject]) || $_POST[$subject] < 0 || $_POST[$subject] > 100){
            $subject_err = "Please enter valid marks (0-100) for all subjects.";
            break;
        }
    }
    
    // Check input errors before inserting in database
    if(empty($symbol_err) && empty($semester_err) && empty($subject_err)){
        // Check if result already exists
        $sql = "SELECT id FROM results WHERE symbol_no = ? AND semester = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_symbol, $param_semester);
            $param_symbol = $symbol_no;
            $param_semester = $semester;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) > 0){
                    $subject_err = "Result already exists for this student and semester.";
                } else {
                    // Insert new result
                    $sql = "INSERT INTO results (symbol_no, semester, subject1, subject2, subject3, subject4, subject5) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "siiiiii", $param_symbol, $param_semester, 
                            $param_subject1, $param_subject2, $param_subject3, $param_subject4, $param_subject5);
                        
                        $param_symbol = $symbol_no;
                        $param_semester = $semester;
                        $param_subject1 = $_POST['subject1'];
                        $param_subject2 = $_POST['subject2'];
                        $param_subject3 = $_POST['subject3'];
                        $param_subject4 = $_POST['subject4'];
                        $param_subject5 = $_POST['subject5'];
                        
                        if(mysqli_stmt_execute($stmt)){
                            $success_msg = "Result added successfully!";
                            $symbol_no = $semester = $subject1 = $subject2 = $subject3 = $subject4 = $subject5 = "";
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all results with student details
$sql = "SELECT r.*, s.name, s.program 
        FROM results r 
        JOIN students s ON r.symbol_no = s.symbol_no 
        ORDER BY r.created_at DESC";
$results = mysqli_query($conn, $sql);

// Get all students for dropdown
$sql = "SELECT symbol_no, name FROM students ORDER BY name";
$students = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.2);
        }
        .main-content {
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">SDC RMS</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate me-2"></i>Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="results.php">
                                <i class="fas fa-chart-bar me-2"></i>Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Results</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResultModal">
                        <i class="fas fa-plus me-2"></i>Add New Result
                    </button>
                </div>

                <?php if(!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Results Table -->
                <div class="card">
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($results)): 
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
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['program']); ?></td>
                                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                        <td><?php echo $total_marks; ?>/500</td>
                                        <td><?php echo $grade; ?></td>
                                        <td>
                                            <a href="edit_result.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_result.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this result?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Result Modal -->
    <div class="modal fade" id="addResultModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="symbol_no" class="form-label">Student</label>
                                <select class="form-select <?php echo (!empty($symbol_err)) ? 'is-invalid' : ''; ?>" 
                                        id="symbol_no" name="symbol_no">
                                    <option value="">Select Student</option>
                                    <?php while($student = mysqli_fetch_assoc($students)): ?>
                                        <option value="<?php echo $student['symbol_no']; ?>" 
                                                <?php echo ($symbol_no == $student['symbol_no']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['name'] . ' (' . $student['symbol_no'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $symbol_err; ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select <?php echo (!empty($semester_err)) ? 'is-invalid' : ''; ?>" 
                                        id="semester" name="semester">
                                    <option value="">Select Semester</option>
                                    <?php for($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($semester == $i) ? 'selected' : ''; ?>>
                                            Semester <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $semester_err; ?></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="subject1" class="form-label">Subject 1</label>
                                <input type="number" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" 
                                       id="subject1" name="subject1" min="0" max="100" value="<?php echo $subject1; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subject2" class="form-label">Subject 2</label>
                                <input type="number" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" 
                                       id="subject2" name="subject2" min="0" max="100" value="<?php echo $subject2; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="subject3" class="form-label">Subject 3</label>
                                <input type="number" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" 
                                       id="subject3" name="subject3" min="0" max="100" value="<?php echo $subject3; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subject4" class="form-label">Subject 4</label>
                                <input type="number" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" 
                                       id="subject4" name="subject4" min="0" max="100" value="<?php echo $subject4; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="subject5" class="form-label">Subject 5</label>
                                <input type="number" class="form-control <?php echo (!empty($subject_err)) ? 'is-invalid' : ''; ?>" 
                                       id="subject5" name="subject5" min="0" max="100" value="<?php echo $subject5; ?>">
                            </div>
                        </div>
                        <?php if(!empty($subject_err)): ?>
                            <div class="alert alert-danger"><?php echo $subject_err; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Result</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 