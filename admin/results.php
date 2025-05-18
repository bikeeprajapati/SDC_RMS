<?php
require_once 'includes/header.php';
require_once 'includes/GradeCalculator.php';

// Initialize filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'course' => $_GET['course'] ?? '',
    'exam_type' => $_GET['exam_type'] ?? '',
    'subject' => $_GET['subject'] ?? '',
    'term' => $_GET['term'] ?? '',
    'page' => max(1, $_GET['page'] ?? 1),
    'per_page' => 10
];

// Calculate offset for pagination
$offset = ($filters['page'] - 1) * $filters['per_page'];

// Fetch exam types for filter
$exam_types = [];
try {
    $stmt = $conn->query("SELECT id, type_name FROM exam_types ORDER BY type_name");
    $exam_types = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching exam types: " . $e->getMessage());
}

// Fetch courses for filter
$courses = [];
try {
    $stmt = $conn->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name");
    $courses = $stmt->fetch_all(MYSQLI_ASSOC);
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Prepare WHERE clause
$where_conditions = ['1=1'];
$params = [];
$param_types = '';

if (!empty($filters['search'])) {
    $where_conditions[] = '(s.roll_number LIKE ? OR s.full_name LIKE ?)';
    $search_term = '%' . $filters['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

if (!empty($filters['course'])) {
    $where_conditions[] = 's.course_id = ?';
    $params[] = $filters['course'];
    $param_types .= 'i';
}

if (!empty($filters['exam_type'])) {
    $where_conditions[] = 'r.exam_type_id = ?';
    $params[] = $filters['exam_type'];
    $param_types .= 'i';
}

if (!empty($filters['subject'])) {
    $where_conditions[] = 'r.subject_id = ?';
    $params[] = $filters['subject'];
    $param_types .= 'i';
}

if (!empty($filters['term'])) {
    $where_conditions[] = 'r.term_id = ?';
    $params[] = $filters['term'];
    $param_types .= 'i';
}

// Count total records
$count_query = "
    SELECT COUNT(DISTINCT r.id) as total 
    FROM results r
    JOIN students s ON r.student_id = s.id
    WHERE " . implode(' AND ', $where_conditions);

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_records = $result->fetch_assoc()['total'];
    } else {
        $result = $conn->query($count_query);
        $total_records = $result->fetch_assoc()['total'];
    }
} catch(Exception $e) {
    error_log("Error counting results: " . $e->getMessage());
    $error = "Error fetching results count";
}

// Calculate total pages
$total_pages = ceil($total_records / $filters['per_page']);
$filters['page'] = min($filters['page'], max(1, $total_pages));

// Fetch results
$results = [];
try {
    $query = "
        SELECT r.*, 
            s.roll_number,
            s.full_name as student_name,
            sub.subject_code,
            sub.subject_name,
            et.type_name as exam_type_name,
            CONCAT(c.course_code, ' - ', at.term_name) as term_name,
            a.full_name as created_by_name
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN subjects sub ON r.subject_id = sub.id
        JOIN exam_types et ON r.exam_type_id = et.id
        JOIN academic_terms at ON r.term_id = at.id
        JOIN courses c ON at.course_id = c.id
        JOIN admin a ON r.created_by = a.id
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";

    // Add pagination parameters
    $params[] = $filters['per_page'];
    $params[] = $offset;
    $param_types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching results: " . $e->getMessage());
    $error = "Error fetching results";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Results Management</h2>
    <div>
        <a href="result_form.php" class="btn btn-primary me-2">
            <i class="bi bi-plus-lg"></i> Add Result
        </a>
        <a href="bulk_result_upload.php" class="btn btn-success">
            <i class="bi bi-upload"></i> Upload Bulk Results
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search Student</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Roll Number or Name">
            </div>
            <div class="col-md-3">
                <label for="course" class="form-label">Course</label>
                <select class="form-select" id="course" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" 
                                <?php echo $filters['course'] == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="term" class="form-label">Term</label>
                <select class="form-select" id="term" name="term" <?php echo empty($filters['course']) ? 'disabled' : ''; ?>>
                    <option value="">All Terms</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="subject" class="form-label">Subject</label>
                <select class="form-select" id="subject" name="subject" <?php echo empty($filters['term']) ? 'disabled' : ''; ?>>
                    <option value="">All Subjects</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exam_type" class="form-label">Exam Type</label>
                <select class="form-select" id="exam_type" name="exam_type">
                    <option value="">All Exam Types</option>
                    <?php foreach ($exam_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" 
                                <?php echo $filters['exam_type'] == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="results.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results List -->
<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Exam Type</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No results found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['subject_code'] . ' - ' . $result['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['term_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['exam_type_name']); ?></td>
                                <td><?php echo number_format($result['marks'], 2); ?></td>
                                <td><?php echo htmlspecialchars($result['grade']); ?></td>
                                <td><?php echo number_format($result['grade_point'], 2); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="result_form.php?id=<?php echo $result['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="result_delete.php?id=<?php echo $result['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this result?')"
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $filters['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] - 1])); ?>">
                            Previous
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $filters['page'] == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $filters['page'] >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page'] + 1])); ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to load terms for a course
function loadTerms(courseId, selectedTermId = '') {
    const termSelect = document.getElementById('term');
    termSelect.disabled = !courseId;
    
    if (!courseId) {
        termSelect.innerHTML = '<option value="">All Terms</option>';
        return;
    }

    fetch(`get_terms.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(data => {
            termSelect.innerHTML = '<option value="">All Terms</option>';
            data.forEach(term => {
                const option = document.createElement('option');
                option.value = term.id;
                option.textContent = `${term.term_name} (Term ${term.term_number})`;
                option.selected = term.id == selectedTermId;
                termSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading terms:', error);
            termSelect.innerHTML = '<option value="">Error loading terms</option>';
        });
}

// Function to load subjects for a course and term
function loadSubjects(courseId, termId, selectedSubjectId = '') {
    const subjectSelect = document.getElementById('subject');
    subjectSelect.disabled = !courseId || !termId;
    
    if (!courseId || !termId) {
        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
        return;
    }

    fetch(`get_subjects.php?course_id=${courseId}&term_id=${termId}`)
        .then(response => response.json())
        .then(data => {
            subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            data.forEach(subject => {
                const option = document.createElement('option');
                option.value = subject.id;
                option.textContent = `${subject.subject_code} - ${subject.subject_name}`;
                option.selected = subject.id == selectedSubjectId;
                subjectSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading subjects:', error);
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
        });
}

// Event listeners
document.getElementById('course').addEventListener('change', function() {
    loadTerms(this.value);
    document.getElementById('subject').innerHTML = '<option value="">All Subjects</option>';
    document.getElementById('subject').disabled = true;
});

document.getElementById('term').addEventListener('change', function() {
    const courseId = document.getElementById('course').value;
    loadSubjects(courseId, this.value);
});

// Initialize filters if values are selected
const selectedCourse = '<?php echo $filters['course']; ?>';
const selectedTerm = '<?php echo $filters['term']; ?>';
const selectedSubject = '<?php echo $filters['subject']; ?>';

if (selectedCourse) {
    loadTerms(selectedCourse, selectedTerm);
    if (selectedTerm) {
        loadSubjects(selectedCourse, selectedTerm, selectedSubject);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 