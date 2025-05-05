<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get all grade points
$grade_points_sql = "SELECT * FROM grade_points ORDER BY min_marks DESC";
$grade_points = $conn->query($grade_points_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_grade'])) {
        // Add new grade point
        $min_marks = $_POST['min_marks'];
        $max_marks = $_POST['max_marks'];
        $grade_point = $_POST['grade_point'];
        $letter_grade = $_POST['letter_grade'];
        $status = isset($_POST['status']) ? 'active' : 'inactive';

        $sql = "INSERT INTO grade_points (min_marks, max_marks, grade_point, letter_grade, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ddssi", $min_marks, $max_marks, $grade_point, $letter_grade, $status);
            if ($stmt->execute()) {
                header("Location: grade_settings.php?success=Grade point added successfully");
                exit();
            } else {
                $error = "Error adding grade point: " . $conn->error;
            }
        } else {
            $error = "Error preparing statement: " . $conn->error;
        }
    }

    if (isset($_POST['update_grade'])) {
        // Update grade point
        $id = $_POST['id'];
        $min_marks = $_POST['min_marks'];
        $max_marks = $_POST['max_marks'];
        $grade_point = $_POST['grade_point'];
        $letter_grade = $_POST['letter_grade'];
        $status = isset($_POST['status']) ? 'active' : 'inactive';

        $sql = "UPDATE grade_points SET min_marks = ?, max_marks = ?, grade_point = ?, letter_grade = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ddssii", $min_marks, $max_marks, $grade_point, $letter_grade, $status, $id);
            if ($stmt->execute()) {
                header("Location: grade_settings.php?success=Grade point updated successfully");
                exit();
            } else {
                $error = "Error updating grade point: " . $conn->error;
            }
        } else {
            $error = "Error preparing statement: " . $conn->error;
        }
    }

    if (isset($_POST['delete_grade'])) {
        // Delete grade point
        $id = $_POST['id'];
        $sql = "DELETE FROM grade_points WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: grade_settings.php?success=Grade point deleted successfully");
                exit();
            } else {
                $error = "Error deleting grade point: " . $conn->error;
            }
        } else {
            $error = "Error preparing statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Settings - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Grade Settings</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                            <i class="fas fa-plus me-2"></i>Add Grade Point
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($grade_points->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Grade Point</th>
                                            <th>Letter Grade</th>
                                            <th>Minimum Marks</th>
                                            <th>Maximum Marks</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $grade_points->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['grade_point']); ?></td>
                                            <td><?php echo htmlspecialchars($row['letter_grade']); ?></td>
                                            <td><?php echo htmlspecialchars($row['min_marks']); ?></td>
                                            <td><?php echo htmlspecialchars($row['max_marks']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $row['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editGradeModal<?php echo $row['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this grade point?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="delete_grade" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editGradeModal<?php echo $row['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Grade Point</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="update_grade" value="1">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Grade Point</label>
                                                                <input type="number" step="0.1" class="form-control" name="grade_point" 
                                                                       value="<?php echo htmlspecialchars($row['grade_point']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Letter Grade</label>
                                                                <input type="text" class="form-control" name="letter_grade" 
                                                                       value="<?php echo htmlspecialchars($row['letter_grade']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Minimum Marks</label>
                                                                <input type="number" class="form-control" name="min_marks" 
                                                                       value="<?php echo htmlspecialchars($row['min_marks']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Maximum Marks</label>
                                                                <input type="number" class="form-control" name="max_marks" 
                                                                       value="<?php echo htmlspecialchars($row['max_marks']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="status" name="status" 
                                                                           <?php echo $row['status'] === 'active' ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="status">
                                                                        Active
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No grade points found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Grade Modal -->
    <div class="modal fade" id="addGradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Grade Point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="">
                        <input type="hidden" name="add_grade" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Grade Point</label>
                            <input type="number" step="0.1" class="form-control" name="grade_point" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Letter Grade</label>
                            <input type="text" class="form-control" name="letter_grade" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Minimum Marks</label>
                            <input type="number" class="form-control" name="min_marks" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maximum Marks</label>
                            <input type="number" class="form-control" name="max_marks" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">
                                    Active
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Grade Point</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
