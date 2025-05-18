<?php
require_once 'includes/header.php';

// Validate course ID
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: courses.php');
    exit();
}

$course_id = (int)$_GET['course_id'];
$course = null;
$terms = [];
$error = '';

// Fetch course details
try {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($course = $result->fetch_assoc()) {
        // Fetch academic terms
        $stmt = $conn->prepare("
            SELECT t.*, 
                (SELECT COUNT(*) FROM results r WHERE r.term_id = t.id) as result_count
            FROM academic_terms t 
            WHERE t.course_id = ? 
            ORDER BY t.term_number
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($term = $result->fetch_assoc()) {
            $terms[] = $term;
        }
    } else {
        header('Location: courses.php');
        exit();
    }
} catch(Exception $e) {
    error_log("Error fetching course/terms: " . $e->getMessage());
    $error = "Error fetching course details";
}

// Handle term activation/deactivation
if (isset($_POST['toggle_status']) && isset($_POST['term_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE academic_terms SET is_active = NOT is_active WHERE id = ? AND course_id = ?");
        $stmt->bind_param("ii", $_POST['term_id'], $course_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header('Location: academic_terms.php?course_id=' . $course_id . '&msg=status_updated');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error updating term status: " . $e->getMessage());
        $error = "Error updating term status";
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Academic Terms</h2>
        <p class="text-muted mb-0">
            <?php echo htmlspecialchars($course['course_name']); ?> 
            (<?php echo htmlspecialchars($course['course_code']); ?>)
        </p>
    </div>
    <a href="courses.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Courses
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
            switch($_GET['msg']) {
                case 'status_updated':
                    echo 'Term status updated successfully!';
                    break;
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Term Number</th>
                        <th>Term Name</th>
                        <th>Results</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $term): ?>
                        <tr>
                            <td><?php echo $term['term_number']; ?></td>
                            <td><?php echo htmlspecialchars($term['term_name']); ?></td>
                            <td><?php echo $term['result_count']; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="term_id" value="<?php echo $term['id']; ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $term['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $term['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($term['is_active']): ?>
                                        <a href="manage_results.php?term_id=<?php echo $term['id']; ?>" 
                                           class="btn btn-primary" title="Manage Results">
                                            <i class="bi bi-card-checklist"></i> Results
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($terms)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No terms found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Course Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>System Type:</strong> <?php echo $course['is_semester_system'] ? 'Semester System' : 'Year System'; ?></p>
                <p><strong>Total Years:</strong> <?php echo $course['total_years']; ?></p>
                <p><strong>Terms per Year:</strong> <?php echo $course['terms_per_year']; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Total Terms:</strong> <?php echo count($terms); ?></p>
                <p><strong>Active Terms:</strong> <?php echo array_reduce($terms, function($count, $term) { return $count + ($term['is_active'] ? 1 : 0); }, 0); ?></p>
                <p><strong>Course Status:</strong> <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?></p>
            </div>
        </div>
        <?php if ($course['description']): ?>
            <div class="mt-3">
                <strong>Description:</strong><br>
                <?php echo nl2br(htmlspecialchars($course['description'])); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>