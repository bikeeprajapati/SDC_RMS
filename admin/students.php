<?php
require_once 'includes/header.php';

// Initialize filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'course_id' => $_GET['course_id'] ?? '',
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
    $where_conditions[] = '(s.roll_number LIKE ? OR s.full_name LIKE ? OR s.email LIKE ?)';
    $search_term = '%' . $filters['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'sss';
}

if (!empty($filters['course_id'])) {
    $where_conditions[] = 's.course_id = ?';
    $params[] = $filters['course_id'];
    $param_types .= 'i';
}

// Fetch total records for pagination
$count_query = "SELECT COUNT(*) as total FROM students s WHERE " . implode(' AND ', $where_conditions);
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
    error_log("Error counting students: " . $e->getMessage());
    $error = "Error fetching student count";
}

// Calculate total pages
$total_pages = ceil($total_records / $filters['per_page']);
$filters['page'] = min($filters['page'], max(1, $total_pages));

// Fetch students with course and term information
$students = [];
try {
    $query = "
        SELECT s.*, 
            c.course_name,
            c.course_code,
            at.term_name as current_term,
            (SELECT COUNT(*) FROM results r WHERE r.student_id = s.id) as result_count
        FROM students s
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN academic_terms at ON s.current_term_id = at.id
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY s.roll_number
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
        $students[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $error = "Error fetching students";
}

// Fetch courses for filter
$courses = [];
try {
    $result = $conn->query("SELECT id, course_name, course_code FROM courses WHERE is_active = 1 ORDER BY course_name");
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Student Management</h2>
    <a href="student_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Student
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
            switch($_GET['msg']) {
                case 'added':
                    echo 'Student added successfully!';
                    break;
                case 'updated':
                    echo 'Student updated successfully!';
                    break;
                case 'deleted':
                    echo 'Student deleted successfully!';
                    break;
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
            switch($_GET['error']) {
                case 'invalid_request':
                    echo 'Invalid request. Please try again.';
                    break;
                case 'not_found':
                    echo 'Student not found.';
                    break;
                case 'has_results':
                    echo 'Cannot delete student with existing results.';
                    break;
                case 'delete_failed':
                    echo 'Failed to delete student. Please try again.';
                    break;
                default:
                    echo 'An error occurred. Please try again.';
            }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Roll No, Name, or Email">
            </div>
            <div class="col-md-4">
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
            <div class="col-md-4 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Roll No.</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Current Term</th>
                        <th>Contact</th>
                        <th>Results</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($student['course_name']); ?>
                                <small class="d-block text-muted">
                                    <?php echo htmlspecialchars($student['course_code']); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($student['current_term'] ?? 'Not Set'); ?></td>
                            <td>
                                <?php if ($student['email']): ?>
                                    <small class="d-block">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($student['phone']): ?>
                                    <small class="d-block">
                                        <i class="bi bi-phone"></i> <?php echo htmlspecialchars($student['phone']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $student['result_count']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="student_view.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="student_form.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($student['result_count'] == 0): ?>
                                        <form method="POST" action="student_delete.php" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this student?')">
                                            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No students found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $filters['page'] <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $filters['page'] - 1; ?>&search=<?php echo urlencode($filters['search']); ?>&course_id=<?php echo $filters['course_id']; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $filters['page'] == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($filters['search']); ?>&course_id=<?php echo $filters['course_id']; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $filters['page'] >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $filters['page'] + 1; ?>&search=<?php echo urlencode($filters['search']); ?>&course_id=<?php echo $filters['course_id']; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 