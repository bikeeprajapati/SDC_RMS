<?php
require_once 'includes/header.php';

// Validate student ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: students.php?error=invalid_id');
    exit();
}

$student_id = (int)$_GET['id'];
$student = null;
$error = '';

try {
    // Fetch student details with related information
    $stmt = $conn->prepare("
        SELECT 
            s.*,
            co.course_name,
            co.course_code,
            t.term_name,
            t.term_number,
            (SELECT COUNT(*) FROM results r WHERE r.student_id = s.id) as result_count
        FROM students s
        LEFT JOIN courses co ON s.course_id = co.id
        LEFT JOIN academic_terms t ON s.current_term_id = t.id
        WHERE s.id = ?
    ");
    
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: students.php?error=not_found');
        exit();
    }
    
    $student = $result->fetch_assoc();
    
} catch (Exception $e) {
    error_log("Error fetching student details: " . $e->getMessage());
    $error = "Failed to fetch student details";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Details</h2>
    <div>
        <a href="student_form.php?id=<?php echo $student_id; ?>" class="btn btn-primary me-2">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="students.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Students
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Basic Information</h3>
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Roll Number:</th>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Full Name:</th>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo $student['email'] ? htmlspecialchars($student['email']) : '<em>Not provided</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo $student['phone'] ? htmlspecialchars($student['phone']) : '<em>Not provided</em>'; ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h3 class="card-title">Academic Information</h3>
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Course:</th>
                            <td>
                                <?php if ($student['course_name']): ?>
                                    <?php echo htmlspecialchars($student['course_name']); ?>
                                    (<?php echo htmlspecialchars($student['course_code']); ?>)
                                <?php else: ?>
                                    <em>Not assigned</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Current Term:</th>
                            <td>
                                <?php if ($student['term_name']): ?>
                                    <?php echo htmlspecialchars($student['term_name']); ?>
                                    (Term <?php echo htmlspecialchars($student['term_number']); ?>)
                                <?php else: ?>
                                    <em>Not set</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Results:</th>
                            <td>
                                <span class="badge bg-info"><?php echo $student['result_count']; ?></span>
                                <?php if ($student['result_count'] > 0): ?>
                                    <a href="results.php?student_id=<?php echo $student_id; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        View Results
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Additional sections can be added here for results, attendance, etc. -->
            
        </div>
    </div>
<?php endif; ?> 