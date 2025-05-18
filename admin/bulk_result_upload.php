<?php
require_once 'includes/header.php';

// Fetch exam types
$exam_types = [];
try {
    $stmt = $conn->query("SELECT id, type_name FROM exam_types WHERE is_active = 1 ORDER BY type_name");
    $exam_types = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching exam types: " . $e->getMessage());
}

// Fetch courses
$courses = [];
try {
    $stmt = $conn->query("SELECT id, course_name, course_code FROM courses WHERE is_active = 1 ORDER BY course_name");
    $courses = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bulk Result Upload</h2>
    <a href="results.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Results
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Results uploaded successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
            switch($_GET['error']) {
                case 'no_file':
                    echo 'Please select a file to upload.';
                    break;
                case 'invalid_format':
                    echo 'Invalid file format. Please upload a CSV file.';
                    break;
                case 'invalid_data':
                    echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Invalid data in CSV file. Please check the format and try again.';
                    break;
                case 'upload_failed':
                    echo 'Failed to upload file. Please try again.';
                    break;
                case 'invalid_request':
                    echo 'Please select course, term and exam type.';
                    break;
                case 'template_generation_failed':
                    echo 'Failed to generate template. Please ensure there are active students and subjects for the selected course and term.';
                    break;
                default:
                    echo 'An error occurred. Please try again.';
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Instructions</h5>
        <ol class="mb-4">
            <li>Download the CSV template for the selected course and exam type</li>
            <li>Fill in the results data in the template</li>
            <li>Upload the completed CSV file</li>
            <li>Review any errors and fix if necessary</li>
        </ol>

        <!-- Template Download Form -->
        <form method="GET" action="generate_result_template.php" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?> 
                                (<?php echo htmlspecialchars($course['course_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="term_id" class="form-label">Term</label>
                    <select class="form-select" id="term_id" name="term_id" required disabled>
                        <option value="">Select Course First</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="exam_type_id" class="form-label">Exam Type</label>
                    <select class="form-select" id="exam_type_id" name="exam_type_id" required>
                        <option value="">Select Exam Type</option>
                        <?php foreach ($exam_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download"></i> Download Template
                    </button>
                </div>
            </div>
        </form>

        <hr class="my-4">

        <!-- Result Upload Form -->
        <form method="POST" action="process_bulk_results.php" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="result_file" class="form-label">Upload Results CSV</label>
                    <input type="file" class="form-control" id="result_file" name="result_file" 
                           accept=".csv" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload"></i> Upload Results
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Dynamic term loading based on course selection
document.getElementById('course_id').addEventListener('change', function() {
    const courseId = this.value;
    const termSelect = document.getElementById('term_id');
    const downloadBtn = document.querySelector('button[type="submit"]');
    
    if (courseId) {
        // Enable term select
        termSelect.disabled = false;
        
        // Fetch terms for selected course
        fetch(`get_terms.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                termSelect.innerHTML = '<option value="">Select Term</option>';
                data.forEach(term => {
                    termSelect.innerHTML += `
                        <option value="${term.id}">
                            ${term.term_name} (Term ${term.term_number})
                        </option>
                    `;
                });
            })
            .catch(error => {
                console.error('Error fetching terms:', error);
                termSelect.innerHTML = '<option value="">Error loading terms</option>';
            });
    } else {
        // Disable and reset term select
        termSelect.disabled = true;
        termSelect.innerHTML = '<option value="">Select Course First</option>';
        downloadBtn.disabled = true;
    }
});

// Check data availability before enabling download
const templateForm = document.querySelector('form[action="generate_result_template.php"]');
templateForm.addEventListener('change', function() {
    const courseId = document.getElementById('course_id').value;
    const termId = document.getElementById('term_id').value;
    const examTypeId = document.getElementById('exam_type_id').value;
    const downloadBtn = this.querySelector('button[type="submit"]');

    // Remove any existing alerts
    const existingAlerts = this.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    if (courseId && termId && examTypeId) {
        // Check if data exists
        fetch(`check_data.php?course_id=${courseId}&term_id=${termId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to check data availability');
                }
                
                if (data.student_count > 0 && data.subject_count > 0) {
                    downloadBtn.disabled = false;
                } else {
                    downloadBtn.disabled = true;
                    let message = '';
                    if (data.student_count === 0 && data.subject_count === 0) {
                        message = 'No students and subjects found for the selected course and term. Please add both before generating the template.';
                    } else if (data.student_count === 0) {
                        message = 'No students found for the selected course and term. Please add students before generating the template.';
                    } else {
                        message = 'No subjects found for the selected course and term. Please add subjects before generating the template.';
                    }
                    
                    const warning = document.createElement('div');
                    warning.className = 'alert alert-warning alert-dismissible fade show mt-3';
                    warning.innerHTML = `
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    this.appendChild(warning);
                }
            })
            .catch(error => {
                console.error('Error checking data:', error);
                downloadBtn.disabled = true;
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                errorAlert.innerHTML = `
                    Failed to check data availability. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                this.appendChild(errorAlert);
            });
    } else {
        downloadBtn.disabled = true;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 