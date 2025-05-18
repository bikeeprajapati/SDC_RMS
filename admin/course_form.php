<?php
require_once 'includes/header.php';

// Initialize variables
$course = [
    'id' => '',
    'course_code' => '',
    'course_name' => '',
    'is_semester_system' => 1,
    'total_years' => '',
    'terms_per_year' => '2',
    'description' => '',
    'is_active' => 1
];

$error = '';
$is_edit = false;

// If editing existing course
if (isset($_GET['id'])) {
    $is_edit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($fetched_course = $result->fetch_assoc()) {
            $course = $fetched_course;
        } else {
            header('Location: courses.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching course: " . $e->getMessage());
        header('Location: courses.php?error=fetch_failed');
        exit();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $is_edit ? 'Edit' : 'Add'; ?> Course</h2>
    <a href="courses.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Courses
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div id="alert-container"></div>

        <form id="courseForm" class="needs-validation" novalidate>
            <?php if ($is_edit): ?>
                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                <input type="hidden" name="original_is_semester" value="<?php echo $course['is_semester_system']; ?>">
                <input type="hidden" name="original_total_years" value="<?php echo $course['total_years']; ?>">
                <input type="hidden" name="original_terms_per_year" value="<?php echo $course['terms_per_year']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="course_code" class="form-label">Course Code *</label>
                <input type="text" class="form-control" id="course_code" name="course_code" 
                       value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="course_name" class="form-label">Course Name *</label>
                <input type="text" class="form-control" id="course_name" name="course_name" 
                       value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_semester_system" name="is_semester_system"
                           <?php echo $course['is_semester_system'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_semester_system">
                        Semester System (unchecked for Year System)
                    </label>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="total_years" class="form-label">Total Years *</label>
                    <input type="number" class="form-control" id="total_years" name="total_years" 
                           value="<?php echo $course['total_years']; ?>" min="1" max="6" required>
                </div>
                <div class="col-md-6">
                    <label for="terms_per_year" class="form-label">Terms Per Year *</label>
                    <input type="number" class="form-control" id="terms_per_year" name="terms_per_year" 
                           value="<?php echo $course['terms_per_year']; ?>" min="1" max="4" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($course['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                           <?php echo $course['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-save"></i> <span><?php echo $is_edit ? 'Update' : 'Save'; ?> Course</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('courseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = document.getElementById('submitBtn');
    const alertContainer = document.getElementById('alert-container');
    const btnText = submitBtn.querySelector('span');
    const originalBtnText = btnText.textContent;
    
    // Disable form submission
    submitBtn.disabled = true;
    btnText.textContent = 'Processing...';
    
    // Clear previous alerts
    alertContainer.innerHTML = '';
    
    fetch('process_course.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alertContainer.innerHTML = `
                <div class="alert alert-success">
                    ${data.message}
                </div>
            `;
            
            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            // Show error message
            alertContainer.innerHTML = `
                <div class="alert alert-danger">
                    ${data.message}
                </div>
            `;
            
            // Re-enable form submission
            submitBtn.disabled = false;
            btnText.textContent = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertContainer.innerHTML = `
            <div class="alert alert-danger">
                An unexpected error occurred. Please try again.
            </div>
        `;
        
        // Re-enable form submission
        submitBtn.disabled = false;
        btnText.textContent = originalBtnText;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 