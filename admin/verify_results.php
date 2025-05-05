<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get pending verifications
$pending_sql = "SELECT 
    g.id as grade_id,
    s.first_name,
    s.last_name,
    sub.subject_name,
    g.total_marks,
    g.grade_point,
    g.letter_grade,
    g.status as grade_status,
    rv.status as verification_status,
    rv.remarks
FROM grades g
LEFT JOIN result_verifications rv ON g.id = rv.grade_id
JOIN enrollments e ON g.enrollment_id = e.id
JOIN students s ON e.student_id = s.id
JOIN subjects sub ON e.subject_id = sub.id
WHERE g.status = 'pending' AND rv.status = 'pending'
ORDER BY s.first_name, s.last_name";

$pending_results = $conn->query($pending_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade_id = $_POST['grade_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    $admin_id = $_SESSION['user_id'];

    // Update grade status
    $grade_sql = "UPDATE grades SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?";
    $grade_stmt = $conn->prepare($grade_sql);
    
    if ($grade_stmt) {
        $grade_stmt->bind_param("sii", $status, $admin_id, $grade_id);
        if ($grade_stmt->execute()) {
            // Update verification status
            $verification_sql = "UPDATE result_verifications SET status = ?, remarks = ?, verified_by = ?, verified_at = NOW() WHERE grade_id = ?";
            $verification_stmt = $conn->prepare($verification_sql);
            
            if ($verification_stmt) {
                $verification_stmt->bind_param("sisi", $status, $remarks, $admin_id, $grade_id);
                if ($verification_stmt->execute()) {
                    header("Location: verify_results.php?success=Result verified successfully");
                    exit();
                } else {
                    $error = "Error updating verification: " . $conn->error;
                }
            } else {
                $error = "Error preparing verification statement: " . $conn->error;
            }
        } else {
            $error = "Error updating grade: " . $conn->error;
        }
    } else {
        $error = "Error preparing grade statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Results - SDC RMS</title>
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
                    <div class="card-header">
                        <h4>Verify Results</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($pending_results->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Subject</th>
                                            <th>Total Marks</th>	h>
                                            <th>Grade Point</th>
                                            <th>Letter Grade</th>
                                            <th>Verification Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $pending_results->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['total_marks']); ?></td>
                                            <td><?php echo htmlspecialchars($row['grade_point']); ?></td>
                                            <td><?php echo htmlspecialchars($row['letter_grade']); ?></td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo htmlspecialchars($row['verification_status']); ?></span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#verifyModal<?php echo $row['grade_id']; ?>">
                                                    Verify
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Verification Modal -->
                                        <div class="modal fade" id="verifyModal<?php echo $row['grade_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Verify Result</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="grade_id" value="<?php echo $row['grade_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select class="form-select" name="status" required>
                                                                    <option value="approved">Approve</option>
                                                                    <option value="rejected">Reject</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Remarks</label>
                                                                <textarea class="form-control" name="remarks" rows="3"><?php echo htmlspecialchars($row['remarks']); ?></textarea>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-primary">Submit</button>
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
                            <div class="alert alert-info">No pending verifications found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
