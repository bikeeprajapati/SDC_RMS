<?php
require_once 'includes/header.php';

// Initialize subject data
$subject = [
    'id' => '',
    'subject_code' => '',
    'subject_name' => '',
    'course_id' => '',
    'term_id' => '',
    'subject_type' => '',
    'max_marks' => ''
];

$error = '';
$is_edit = false;

// Display error message if any
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'missing_fields':
            $error = 'All required fields must be filled out.';
            break;
        case 'duplicate_code':
            $error = 'This subject code is already in use.';
            break;
        case 'db_error':
            $error = 'Database error occurred. Please try again.';
            break;
        case 'fetch_failed':
            $error = 'Failed to fetch subject details.';
            break;
    }
}

// If editing existing subject
if (isset($_GET['id'])) {
    $is_edit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($fetched_subject = $result->fetch_assoc()) {
            $subject = $fetched_subject;
        } else {
            header('Location: subjects.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching subject: " . $e->getMessage());
        header('Location: subjects.php?error=fetch_failed');
        exit();
    }
}

// Fetch active courses
$courses = [];
try {
    $result = $conn->query("SELECT id, course_name, course_code FROM courses WHERE is_active = 1 ORDER BY course_name");
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error = "Error fetching courses";
}

// Fetch terms based on selected course
$terms = [];
if (!empty($subject['course_id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT id, term_name, term_number 
            FROM academic_terms 
            WHERE course_id = ? AND is_active = 1 
            ORDER BY term_number
        ");
        $stmt->bind_param("i", $subject['course_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $terms[] = $row;
        }
    } catch(Exception $e) {
        error_log("Error fetching terms: " . $e->getMessage());
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $is_edit ? 'Edit' : 'Add'; ?> Subject</h2>
    <a href="subjects.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Subjects
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="subjectForm" method="POST" action="process_subject.php" class="needs-validation" novalidate>
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="subject_code" class="form-label">Subject Code *</label>
                    <input type="text" class="form-control" id="subject_code" name="subject_code" 
                           value="<?php echo htmlspecialchars($subject['subject_code']); ?>" required>
                    <div class="invalid-feedback">Please enter a subject code.</div>
                </div>
                <div class="col-md-6">
                    <label for="subject_name" class="form-label">Subject Name *</label>
                    <input type="text" class="form-control" id="subject_name" name="subject_name" 
                           value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
                    <div class="invalid-feedback">Please enter the subject name.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="course_id" class="form-label">Course *</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $subject['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?> 
                                (<?php echo htmlspecialchars($course['course_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a course.</div>
                </div>
                <div class="col-md-6">
                    <label for="term_id" class="form-label">Term *</label>
                    <select class="form-select" id="term_id" name="term_id" required>
                        <option value="">Select Term</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" 
                                    <?php echo $subject['term_id'] == $term['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($term['term_name']); ?> 
                                (Term <?php echo htmlspecialchars($term['term_number']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a term.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="subject_type" class="form-label">Subject Type *</label>
                    <select class="form-select" id="subject_type" name="subject_type" required>
                        <option value="">Select Type</option>
                        <option value="IT" <?php echo $subject['subject_type'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                        <option value="Management" <?php echo $subject['subject_type'] === 'Management' ? 'selected' : ''; ?>>Management</option>
                    </select>
                    <div class="invalid-feedback">Please select a subject type.</div>
                </div>
                <div class="col-md-6">
                    <label for="max_marks" class="form-label">Maximum Marks *</label>
                    <input type="number" class="form-control" id="max_marks" name="max_marks" 
                           value="<?php echo htmlspecialchars($subject['max_marks']); ?>" 
                           min="1" max="1000" required>
                    <div class="invalid-feedback">Please enter maximum marks (1-1000).</div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $is_edit ? 'Update' : 'Save'; ?> Subject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.getElementById('subjectForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});

// Dynamic term loading
document.getElementById('course_id').addEventListener('change', function() {
    const courseId = this.value;
    const termSelect = document.getElementById('term_id');
    
    // Clear current options
    termSelect.innerHTML = '<option value="">Select Term</option>';
    
    if (!courseId) {
        return;
    }
    
    // Fetch terms for selected course
    fetch(`ajax/get_terms.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            
            // Add new options
            data.terms.forEach(term => {
                const option = document.createElement('option');
                option.value = term.id;
                option.textContent = `${term.term_name} (Term ${term.term_number})`;
                termSelect.appendChild(option);
            });
            
            // If editing, try to restore previously selected term
            const currentTermId = '<?php echo $subject['term_id']; ?>';
            if (currentTermId) {
                termSelect.value = currentTermId;
            }
        })
        .catch(error => {
            console.error('Error fetching terms:', error);
            termSelect.innerHTML = '<option value="">Error loading terms</option>';
        });
});

// Trigger term loading on page load if course is selected
if (document.getElementById('course_id').value) {
    document.getElementById('course_id').dispatchEvent(new Event('change'));
}
</script>

<?php require_once 'includes/footer.php'; ?> 