<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get all years, semesters, and sections
$years_query = "SELECT * FROM years WHERE status = 'active' ORDER BY id";
$semesters_query = "SELECT * FROM semesters WHERE status = 'active' ORDER BY id";
$sections_query = "SELECT * FROM sections WHERE status = 'active' ORDER BY id";

$years = mysqli_fetch_all(mysqli_query($conn, $years_query), MYSQLI_ASSOC);
$semesters = mysqli_fetch_all(mysqli_query($conn, $semesters_query), MYSQLI_ASSOC);
$sections = mysqli_fetch_all(mysqli_query($conn, $sections_query), MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $year_id = (int)$_POST['year_id'];
    $semester_id = (int)$_POST['semester_id'];
    $section_id = (int)$_POST['section_id'];

    $sql = "INSERT INTO classes (name, year_id, semester_id, section_id) 
            VALUES ('$name', $year_id, $semester_id, $section_id)";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: manage_classes.php?success=1');
        exit();
    } else {
        $error = "Error adding class: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Class - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Class</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Class Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="year_id" class="form-label">Year</label>
                                <select class="form-select" id="year_id" name="year_id" required>
                                    <option value="">Select Year</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year['id']; ?>">
                                            <?php echo htmlspecialchars($year['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="semester_id" class="form-label">Semester</label>
                                <select class="form-select" id="semester_id" name="semester_id" required>
                                    <option value="">Select Semester</option>
                                    <?php foreach ($semesters as $semester): ?>
                                        <option value="<?php echo $semester['id']; ?>">
                                            <?php echo htmlspecialchars($semester['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="section_id" class="form-label">Section</label>
                                <select class="form-select" id="section_id" name="section_id" required>
                                    <option value="">Select Section</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>">
                                            <?php echo htmlspecialchars($section['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Class</button>
                            <a href="manage_classes.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
