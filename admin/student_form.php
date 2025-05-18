<?php
require_once 'includes/header.php';

// Initialize student data
$student = [
    'id' => '',
    'roll_number' => '',
    'full_name' => '',
    'course_id' => '',
    'current_term_id' => '',
    'email' => '',
    'phone' => ''
];

$error = '';
$is_edit = false;

// Display error message if any
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'missing_fields':
            $error = 'All required fields must be filled out.';
            break;
        case 'duplicate_roll':
            $error = 'This roll number is already in use.';
            break;
        case 'db_error':
            $error = 'Database error occurred. Please try again.';
            break;
        case 'fetch_failed':
            $error = 'Failed to fetch student details.';
            break;
    }
}

// If editing existing student
if (isset($_GET['id'])) {
    $is_edit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($fetched_student = $result->fetch_assoc()) {
            $student = $fetched_student;
        } else {
            header('Location: students.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching student: " . $e->getMessage());
        header('Location: students.php?error=fetch_failed');
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
if (!empty($student['course_id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT id, term_name, term_number 
            FROM academic_terms 
            WHERE course_id = ? AND is_active = 1 
            ORDER BY term_number
        ");
        $stmt->bind_param("i", $student['course_id']);
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
    <h2><?php echo $is_edit ? 'Edit' : 'Add'; ?> Student</h2>
    <a href="students.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Students
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="studentForm" method="POST" action="process_student.php" class="needs-validation" novalidate>
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="roll_number" class="form-label">Roll Number *</label>
                    <input type="text" class="form-control" id="roll_number" name="roll_number" 
                           value="<?php echo htmlspecialchars($student['roll_number']); ?>" required>
                    <div class="invalid-feedback">Please enter a roll number.</div>
                </div>
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    <div class="invalid-feedback">Please enter the full name.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="course_id" class="form-label">Course *</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $student['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?> 
                                (<?php echo htmlspecialchars($course['course_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a course.</div>
                </div>
                <div class="col-md-6">
                    <label for="current_term_id" class="form-label">Current Term *</label>
                    <select class="form-select" id="current_term_id" name="current_term_id" required>
                        <option value="">Select Term</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" 
                                    <?php echo $student['current_term_id'] == $term['id'] ? 'selected' : ''; ?>>
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
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($student['email']); ?>">
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($student['phone']); ?>">
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $is_edit ? 'Update' : 'Save'; ?> Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.getElementById('studentForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});

// Dynamic term loading
document.getElementById('course_id').addEventListener('change', function() {
    const courseId = this.value;
    const termSelect = document.getElementById('current_term_id');
    
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
            const currentTermId = '<?php echo $student['current_term_id']; ?>';
            if (currentTermId) {
                termSelect.value = currentTermId;
            }
        })
        .catch(error => {
            console.error('Error fetching terms:', error);
            termSelect.innerHTML = '<option value="">Error loading terms</option>';
        });
});

// Trigger term loading on page load if course is selected (edit mode)
if (document.getElementById('course_id').value) {
    document.getElementById('course_id').dispatchEvent(new Event('change'));
}
</script>

<?php require_once 'includes/footer.php'; ?> 