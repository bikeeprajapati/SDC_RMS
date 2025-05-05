<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get subject details
$sql = "SELECT * FROM subjects WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subject = $result->fetch_assoc();
    
    if (!$subject) {
        header("Location: subjects.php?error=Subject not found");
        exit();
    }
} else {
    header("Location: subjects.php?error=Error fetching subject");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = $_POST['subject_name'];
    $subject_code = $_POST['subject_code'];
    $credits = $_POST['credits'];
    $semester = $_POST['semester'];
    $year = $_POST['year'];
    $status = isset($_POST['status']) ? 'active' : 'inactive';

    $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, credits = ?, semester = ?, year = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssiisii", $subject_name, $subject_code, $credits, $semester, $year, $status, $subject_id);
        if ($stmt->execute()) {
            header("Location: subjects.php?success=Subject updated successfully");
            exit();
        } else {
            $error = "Error updating subject: " . $conn->error;
        }
    } else {
        $error = "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4>Edit Subject</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="subject_name" class="form-label">Subject Name</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject_code" class="form-label">Subject Code</label>
                                <input type="text" class="form-control" id="subject_code" name="subject_code" value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="credits" class="form-label">Credits</label>
                                <input type="number" class="form-control" id="credits" name="credits" value="<?php echo htmlspecialchars($subject['credits']); ?>" min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required>
                                    <option value="1" <?php echo $subject['semester'] == 1 ? 'selected' : ''; ?>>1st Semester</option>
                                    <option value="2" <?php echo $subject['semester'] == 2 ? 'selected' : ''; ?>>2nd Semester</option>
                                    <option value="3" <?php echo $subject['semester'] == 3 ? 'selected' : ''; ?>>3rd Semester</option>
                                    <option value="4" <?php echo $subject['semester'] == 4 ? 'selected' : ''; ?>>4th Semester</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year" required>
                                    <option value="1" <?php echo $subject['year'] == 1 ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo $subject['year'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $subject['year'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $subject['year'] == 4 ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" <?php echo $subject['status'] == 'active' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Subject</button>
                            <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
