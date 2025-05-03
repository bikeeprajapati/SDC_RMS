<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config/database.php";

// Initialize variables
$id = $symbol_no = $semester = $subject1 = $subject2 = $subject3 = $subject4 = $subject5 = "";
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
    
    // Check input errors before updating in database
    if(empty($symbol_err) && empty($semester_err) && empty($subject_err)){
        // Check if result already exists for another student
        $sql = "SELECT id FROM results WHERE symbol_no = ? AND semester = ? AND id != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sii", $param_symbol, $param_semester, $param_id);
            $param_symbol = $symbol_no;
            $param_semester = $semester;
            $param_id = $_POST["id"];
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) > 0){
                    $subject_err = "Result already exists for this student and semester.";
                } else {
                    // Update result
                    $sql = "UPDATE results SET symbol_no = ?, semester = ?, subject1 = ?, subject2 = ?, subject3 = ?, subject4 = ?, subject5 = ? WHERE id = ?";
                    if($stmt = mysqli_prepare($conn, $sql)){
                        mysqli_stmt_bind_param($stmt, "siiiiiii", $param_symbol, $param_semester, 
                            $param_subject1, $param_subject2, $param_subject3, $param_subject4, $param_subject5, $param_id);
                        
                        $param_symbol = $symbol_no;
                        $param_semester = $semester;
                        $param_subject1 = $_POST['subject1'];
                        $param_subject2 = $_POST['subject2'];
                        $param_subject3 = $_POST['subject3'];
                        $param_subject4 = $_POST['subject4'];
                        $param_subject5 = $_POST['subject5'];
                        $param_id = $_POST["id"];
                        
                        if(mysqli_stmt_execute($stmt)){
                            $success_msg = "Result updated successfully!";
                        } else {
                            echo "Oops! Something went wrong. Please try again later.";
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $id = trim($_GET["id"]);
        
        // Prepare a select statement
        $sql = "SELECT * FROM results WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result);
                    $id = $row["id"];
                    $symbol_no = $row["symbol_no"];
                    $semester = $row["semester"];
                    $subject1 = $row["subject1"];
                    $subject2 = $row["subject2"];
                    $subject3 = $row["subject3"];
                    $subject4 = $row["subject4"];
                    $subject5 = $row["subject5"];
                } else{
                    header("location: results.php");
                    exit();
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else{
        header("location: results.php");
        exit();
    }
}

// Get all students for dropdown
$sql = "SELECT symbol_no, name FROM students ORDER BY name";
$students = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Result - SDC RMS</title>
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
                            <a class="nav-link" href="dashboard.php">
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
                    <h2>Edit Result</h2>
                    <a href="results.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Results
                    </a>
                </div>

                <?php if(!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
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
                            <button type="submit" class="btn btn-primary">Update Result</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 