<?php
require_once "../config/database.php";

$error = "";
$results = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $symbol_no = trim($_POST["symbol_no"]);
    $semester = trim($_POST["semester"]);
    
    if (empty($symbol_no) || empty($semester)) {
        $error = "Please enter both symbol number and semester.";
    } else {
        $sql = "SELECT r.*, s.name as student_name 
                FROM results r 
                LEFT JOIN students s ON r.symbol_no = s.symbol_no 
                WHERE r.symbol_no = ? AND r.semester = ?";
                
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $symbol_no, $semester);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) > 0) {
                    $results = mysqli_fetch_assoc($result);
                } else {
                    $error = "No results found for the given symbol number and semester.";
                }
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Results - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .result-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .result-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .result-card .card-header {
            background: #343a40;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem;
        }
        .grade-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
        .grade-a-plus { background: #28a745; color: white; }
        .grade-a { background: #5cb85c; color: white; }
        .grade-b-plus { background: #17a2b8; color: white; }
        .grade-b { background: #007bff; color: white; }
        .grade-c-plus { background: #ffc107; color: black; }
        .grade-c { background: #fd7e14; color: white; }
        .grade-f { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-container">
            <h2 class="text-center mb-4">Student Results</h2>
            
            <form method="post" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="symbol_no">Symbol Number</label>
                            <input type="text" class="form-control" id="symbol_no" name="symbol_no" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="semester">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">View Results</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($results): 
                $total_marks = $results['subject1'] + $results['subject2'] + $results['subject3'] + 
                             $results['subject4'] + $results['subject5'];
                $percentage = ($total_marks / 500) * 100;
                $grade = '';
                $grade_class = '';
                
                if ($percentage >= 90) {
                    $grade = 'A+';
                    $grade_class = 'grade-a-plus';
                }
                elseif ($percentage >= 80) {
                    $grade = 'A';
                    $grade_class = 'grade-a';
                }
                elseif ($percentage >= 70) {
                    $grade = 'B+';
                    $grade_class = 'grade-b-plus';
                }
                elseif ($percentage >= 60) {
                    $grade = 'B';
                    $grade_class = 'grade-b';
                }
                elseif ($percentage >= 50) {
                    $grade = 'C+';
                    $grade_class = 'grade-c-plus';
                }
                elseif ($percentage >= 40) {
                    $grade = 'C';
                    $grade_class = 'grade-c';
                }
                else {
                    $grade = 'F';
                    $grade_class = 'grade-f';
                }
            ?>
                <div class="card result-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Semester <?php echo htmlspecialchars($results['semester']); ?></h5>
                        <span class="grade-badge <?php echo $grade_class; ?>"><?php echo $grade; ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Student Name</h6>
                                <p><?php echo htmlspecialchars($results['student_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Symbol Number</h6>
                                <p><?php echo htmlspecialchars($results['symbol_no']); ?></p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Subject 1</td>
                                        <td><?php echo htmlspecialchars($results['subject1']); ?></td>
                                        <td><?php echo number_format(($results['subject1'] / 100) * 100, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Subject 2</td>
                                        <td><?php echo htmlspecialchars($results['subject2']); ?></td>
                                        <td><?php echo number_format(($results['subject2'] / 100) * 100, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Subject 3</td>
                                        <td><?php echo htmlspecialchars($results['subject3']); ?></td>
                                        <td><?php echo number_format(($results['subject3'] / 100) * 100, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Subject 4</td>
                                        <td><?php echo htmlspecialchars($results['subject4']); ?></td>
                                        <td><?php echo number_format(($results['subject4'] / 100) * 100, 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td>Subject 5</td>
                                        <td><?php echo htmlspecialchars($results['subject5']); ?></td>
                                        <td><?php echo number_format(($results['subject5'] / 100) * 100, 1); ?>%</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-primary">
                                        <th>Total</th>
                                        <th><?php echo $total_marks; ?>/500</th>
                                        <th><?php echo number_format($percentage, 1); ?>%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 