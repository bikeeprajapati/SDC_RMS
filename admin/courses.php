<?php
require_once '../config/database.php';
require_once 'includes/header.php';

// Handle course activation/deactivation
if (isset($_POST['toggle_status']) && isset($_POST['course_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $_POST['course_id']);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header('Location: courses.php?msg=status_updated');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error updating course status: " . $e->getMessage());
        $error = "Error updating course status";
    }
}

// Fetch all courses
$courses = [];
try {
    $query = "
        SELECT c.*, 
            (SELECT COUNT(*) FROM students s WHERE s.course_id = c.id) as student_count,
            (SELECT COUNT(*) FROM subjects s WHERE s.course_id = c.id) as subject_count
        FROM courses c 
        ORDER BY c.course_name
    ";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error = "Error fetching courses";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Course Management</h2>
    <a href="course_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Course
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
            switch($_GET['msg']) {
                case 'added':
                    echo 'Course added successfully!';
                    break;
                case 'updated':
                    echo 'Course updated successfully!';
                    break;
                case 'deleted':
                    echo 'Course deleted successfully!';
                    break;
                case 'status_updated':
                    echo 'Course status updated successfully!';
                    break;
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>System</th>
                        <th>Duration</th>
                        <th>Terms/Year</th>
                        <th>Students</th>
                        <th>Subjects</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                                <small class="d-block text-muted"><?php echo htmlspecialchars($course['description']); ?></small>
                            </td>
                            <td><?php echo $course['is_semester_system'] ? 'Semester' : 'Year'; ?></td>
                            <td><?php echo $course['total_years']; ?> years</td>
                            <td><?php echo $course['terms_per_year']; ?></td>
                            <td><?php echo $course['student_count']; ?></td>
                            <td><?php echo $course['subject_count']; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $course['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="course_form.php?id=<?php echo $course['id']; ?>" class="btn btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="academic_terms.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info" title="Manage Terms">
                                        <i class="bi bi-list-check"></i>
                                    </a>
                                    <?php if ($course['student_count'] == 0 && $course['subject_count'] == 0): ?>
                                        <a href="course_delete.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this course?')" 
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No courses found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 