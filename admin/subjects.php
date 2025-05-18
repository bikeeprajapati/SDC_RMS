<?php
require_once 'includes/header.php';

// Initialize filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'course_id' => $_GET['course_id'] ?? '',
    'term_id' => $_GET['term_id'] ?? '',
    'subject_type' => $_GET['subject_type'] ?? '',
    'page' => max(1, $_GET['page'] ?? 1),
    'per_page' => 10
];

// Calculate offset for pagination
$offset = ($filters['page'] - 1) * $filters['per_page'];

// Prepare WHERE clause
$where_conditions = ['1=1'];
$params = [];
$param_types = '';

if (!empty($filters['search'])) {
    $where_conditions[] = '(s.subject_code LIKE ? OR s.subject_name LIKE ?)';
    $search_term = '%' . $filters['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

if (!empty($filters['course_id'])) {
    $where_conditions[] = 's.course_id = ?';
    $params[] = $filters['course_id'];
    $param_types .= 'i';
}

if (!empty($filters['term_id'])) {
    $where_conditions[] = 's.term_id = ?';
    $params[] = $filters['term_id'];
    $param_types .= 'i';
}

if (!empty($filters['subject_type'])) {
    $where_conditions[] = 's.subject_type = ?';
    $params[] = $filters['subject_type'];
    $param_types .= 's';
}

// Fetch total records for pagination
$count_query = "SELECT COUNT(*) as total FROM subjects s WHERE " . implode(' AND ', $where_conditions);
$total_records = 0;

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
    error_log("Error counting subjects: " . $e->getMessage());
    $error = "Error fetching subject count";
}

// Calculate total pages
$total_pages = ceil($total_records / $filters['per_page']);
$filters['page'] = min($filters['page'], max(1, $total_pages));

// Fetch subjects with course and term information
$subjects = [];
try {
    $query = "
        SELECT s.*, 
            c.course_name,
            c.course_code,
            at.term_name,
            (SELECT COUNT(*) FROM results r WHERE r.subject_id = s.id) as result_count
        FROM subjects s
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN academic_terms at ON s.term_id = at.id
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY s.subject_code
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
        $subjects[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
    $error = "Error fetching subjects";
}

// Fetch active courses for filter
$courses = [];
try {
    $result = $conn->query("SELECT id, course_name, course_code FROM courses WHERE is_active = 1 ORDER BY course_name");
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}

// Fetch terms based on selected course
$terms = [];
if (!empty($filters['course_id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT id, term_name, term_number 
            FROM academic_terms 
            WHERE course_id = ? AND is_active = 1 
            ORDER BY term_number
        ");
        $stmt->bind_param("i", $filters['course_id']);
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
    <h2>Subject Management</h2>
    <a href="subject_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Subject
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Subject Code or Name">
            </div>
            <div class="col-md-3">
                <label for="course_id" class="form-label">Course</label>
                <select class="form-select" id="course_id" name="course_id">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" 
                                <?php echo $filters['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?> 
                            (<?php echo htmlspecialchars($course['course_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="term_id" class="form-label">Term</label>
                <select class="form-select" id="term_id" name="term_id" <?php echo empty($terms) ? 'disabled' : ''; ?>>
                    <option value="">All Terms</option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?php echo $term['id']; ?>" 
                                <?php echo $filters['term_id'] == $term['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($term['term_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="subject_type" class="form-label">Type</label>
                <select class="form-select" id="subject_type" name="subject_type">
                    <option value="">All Types</option>
                    <option value="IT" <?php echo $filters['subject_type'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                    <option value="Management" <?php echo $filters['subject_type'] === 'Management' ? 'selected' : ''; ?>>Management</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="subjects.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Subjects List -->
<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Term</th>
                        <th>Type</th>
                        <th>Max Marks</th>
                        <th>Results</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No subjects found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($subject['course_code']); ?>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($subject['course_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($subject['term_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $subject['subject_type'] === 'IT' ? 'info' : 'warning'; ?>">
                                        <?php echo htmlspecialchars($subject['subject_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($subject['max_marks']); ?></td>
                                <td><?php echo $subject['result_count']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="subject_form.php?id=<?php echo $subject['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($subject['result_count'] == 0): ?>
                                            <a href="subject_delete.php?id=<?php echo $subject['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this subject?')"
                                               title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
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
// Dynamic term loading
document.getElementById('course_id').addEventListener('change', function() {
    const courseId = this.value;
    const termSelect = document.getElementById('term_id');
    
    // Clear current options
    termSelect.innerHTML = '<option value="">All Terms</option>';
    termSelect.disabled = !courseId;
    
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
                option.textContent = term.term_name;
                termSelect.appendChild(option);
            });
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