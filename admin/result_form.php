<?php
require_once 'includes/header.php';
require_once 'includes/GradeCalculator.php';

// Initialize variables
$result = [
    'id' => null,
    'student_id' => '',
    'subject_id' => '',
    'exam_type_id' => '',
    'term_id' => '',
    'marks' => '',
    'grade' => '',
    'grade_point' => '',
    'remarks' => ''
];

// If editing existing result
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM results WHERE id = ?
        ");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $db_result = $stmt->get_result()->fetch_assoc();
        
        if ($db_result) {
            $result = array_merge($result, $db_result);
        } else {
            header('Location: results.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching result: " . $e->getMessage());
        $error = "Error fetching result details";
    }
}

// Fetch students
$students = [];
try {
    $stmt = $conn->query("
        SELECT s.id, s.roll_number, s.full_name, c.course_code, at.term_name
        FROM students s
        JOIN courses c ON s.course_id = c.id
        JOIN academic_terms at ON s.current_term_id = at.id
        ORDER BY s.roll_number
    ");
    $students = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

// Fetch subjects
$subjects = [];
try {
    $stmt = $conn->query("
        SELECT s.*, c.course_code, at.term_name
        FROM subjects s
        JOIN courses c ON s.course_id = c.id
        JOIN academic_terms at ON s.term_id = at.id
        ORDER BY s.subject_code
    ");
    $subjects = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
}

// Fetch exam types
$exam_types = [];
try {
    $stmt = $conn->query("SELECT * FROM exam_types WHERE is_active = 1 ORDER BY type_name");
    $exam_types = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching exam types: " . $e->getMessage());
}

// Display success/error messages
$message = '';
$message_type = '';

if (isset($_GET['error'])) {
    $message_type = 'danger';
    switch($_GET['error']) {
        case 'missing_fields':
            $message = 'Please fill in all required fields.';
            break;
        case 'invalid_marks':
            $message = 'Marks must be between 0 and 100.';
            break;
        case 'duplicate':
            $message = 'Result already exists for this student, subject and exam type.';
            break;
        case 'db_error':
            $message = 'Database error occurred. Please try again.';
            break;
    }
}

if (isset($_GET['success'])) {
    $message_type = 'success';
    $message = 'Result saved successfully.';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $result['id'] ? 'Edit Result' : 'Add New Result'; ?></h2>
    <a href="results.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Results
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="process_result.php" method="POST" class="needs-validation" novalidate>
            <?php if ($result['id']): ?>
                <input type="hidden" name="id" value="<?php echo $result['id']; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student *</label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"
                                    <?php echo $result['student_id'] == $student['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name'] . 
                                    ' (' . $student['course_code'] . ' - ' . $student['term_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a student.</div>
                </div>

                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Subject *</label>
                    <select class="form-select" id="subject_id" name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>"
                                    <?php echo $result['subject_id'] == $subject['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name'] . 
                                    ' (' . $subject['course_code'] . ' - ' . $subject['term_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a subject.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="exam_type_id" class="form-label">Exam Type *</label>
                    <select class="form-select" id="exam_type_id" name="exam_type_id" required>
                        <option value="">Select Exam Type</option>
                        <?php foreach ($exam_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>"
                                    <?php echo $result['exam_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select an exam type.</div>
                </div>

                <div class="col-md-6">
                    <label for="marks" class="form-label">Marks *</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="marks" name="marks" 
                               value="<?php echo htmlspecialchars($result['marks']); ?>"
                               min="0" step="0.01" required>
                        <span class="input-group-text" id="max-marks-text">/ -</span>
                    </div>
                    <div class="invalid-feedback">Please enter valid marks.</div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea class="form-control" id="remarks" name="remarks" rows="2"><?php 
                        echo htmlspecialchars($result['remarks']); 
                    ?></textarea>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Result
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.querySelector('.needs-validation').addEventListener('submit', event => {
    if (!event.target.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    event.target.classList.add('was-validated');
});

// Store subjects data for client-side use
const subjects = <?php echo json_encode($subjects); ?>;

// Update max marks when subject changes
document.getElementById('subject_id').addEventListener('change', function() {
    const subjectId = this.value;
    const marksInput = document.getElementById('marks');
    const maxMarksText = document.getElementById('max-marks-text');
    
    if (subjectId) {
        const subject = subjects.find(s => s.id == subjectId);
        if (subject) {
            marksInput.max = subject.max_marks;
            maxMarksText.textContent = `/ ${subject.max_marks}`;
            // Update grade if marks already entered
            if (marksInput.value) {
                calculateGrade(marksInput.value, subject.max_marks);
            }
        }
    } else {
        marksInput.max = '';
        maxMarksText.textContent = '/ -';
    }
});

// Auto-calculate grade and grade point
document.getElementById('marks').addEventListener('change', function() {
    const subjectId = document.getElementById('subject_id').value;
    if (subjectId) {
        const subject = subjects.find(s => s.id == subjectId);
        if (subject) {
            calculateGrade(this.value, subject.max_marks);
        }
    }
});

function calculateGrade(marks, maxMarks) {
    if (marks >= 0 && marks <= maxMarks) {
        fetch(`get_grade.php?marks=${marks}&max_marks=${maxMarks}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('grade').value = data.grade;
                    document.getElementById('grade_point').value = data.grade_point;
                }
            });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 