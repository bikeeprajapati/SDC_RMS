<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config/database.php";

// Initialize variables
$symbol_no = $name = $program = "";
$symbol_err = $name_err = $program_err = "";
$success_msg = "";

// Process form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate symbol number
    if(empty(trim($_POST["symbol_no"]))){
        $symbol_err = "Please enter symbol number.";
    } elseif(!preg_match('/^[0-9]{5,10}$/', trim($_POST["symbol_no"]))){
        $symbol_err = "Symbol number must be 5-10 digits.";
    } else {
        $sql = "SELECT symbol_no FROM students WHERE symbol_no = ? AND symbol_no != ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_symbol, $param_old_symbol);
            $param_symbol = trim($_POST["symbol_no"]);
            $param_old_symbol = $_POST["old_symbol_no"];
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) > 0){
                    $symbol_err = "This symbol number already exists.";
                } else{
                    $symbol_no = trim($_POST["symbol_no"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate name
    if(empty(trim($_POST["name"]))){
        $name_err = "Please enter name.";
    } else{
        $name = trim($_POST["name"]);
    }
    
    // Validate program
    if(empty(trim($_POST["program"]))){
        $program_err = "Please select program.";
    } else{
        $program = trim($_POST["program"]);
    }
    
    // Check input errors before updating in database
    if(empty($symbol_err) && empty($name_err) && empty($program_err)){
        $sql = "UPDATE students SET symbol_no = ?, name = ?, program = ? WHERE symbol_no = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "ssss", $param_symbol, $param_name, $param_program, $param_old_symbol);
            
            $param_symbol = $symbol_no;
            $param_name = $name;
            $param_program = $program;
            $param_old_symbol = $_POST["old_symbol_no"];
            
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Student updated successfully!";
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Check existence of symbol_no parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $symbol_no = trim($_GET["id"]);
        
        // Prepare a select statement
        $sql = "SELECT * FROM students WHERE symbol_no = ?";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_symbol);
            $param_symbol = $symbol_no;
            
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result);
                    $symbol_no = $row["symbol_no"];
                    $name = $row["name"];
                    $program = $row["program"];
                } else{
                    header("location: students.php");
                    exit();
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else{
        header("location: students.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - SDC RMS</title>
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
                            <a class="nav-link active" href="students.php">
                                <i class="fas fa-user-graduate me-2"></i>Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="results.php">
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
                    <h2>Edit Student</h2>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Students
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
                            <input type="hidden" name="old_symbol_no" value="<?php echo $symbol_no; ?>">
                            <div class="mb-3">
                                <label for="symbol_no" class="form-label">Symbol Number</label>
                                <input type="text" class="form-control <?php echo (!empty($symbol_err)) ? 'is-invalid' : ''; ?>" 
                                       id="symbol_no" name="symbol_no" value="<?php echo $symbol_no; ?>">
                                <span class="invalid-feedback"><?php echo $symbol_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" 
                                       id="name" name="name" value="<?php echo $name; ?>">
                                <span class="invalid-feedback"><?php echo $name_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label for="program" class="form-label">Program</label>
                                <select class="form-select <?php echo (!empty($program_err)) ? 'is-invalid' : ''; ?>" 
                                        id="program" name="program">
                                    <option value="">Select Program</option>
                                    <option value="BSc CSIT" <?php echo ($program == "BSc CSIT") ? 'selected' : ''; ?>>BSc CSIT</option>
                                    <option value="BCA" <?php echo ($program == "BCA") ? 'selected' : ''; ?>>BCA</option>
                                    <option value="BIM" <?php echo ($program == "BIM") ? 'selected' : ''; ?>>BIM</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $program_err; ?></span>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Student</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 