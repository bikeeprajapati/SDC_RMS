<?php
require_once 'includes/header.php';

// Initialize filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? '',
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
    $where_conditions[] = '(type_name LIKE ? OR description LIKE ?)';
    $search_term = '%' . $filters['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'ss';
}

if ($filters['status'] !== '') {
    $where_conditions[] = 'is_active = ?';
    $params[] = $filters['status'] === 'active' ? 1 : 0;
    $param_types .= 'i';
}

// Fetch total records for pagination
$count_query = "SELECT COUNT(*) as total FROM exam_types WHERE " . implode(' AND ', $where_conditions);
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
    error_log("Error counting exam types: " . $e->getMessage());
    $error = "Error fetching exam type count";
}

// Calculate total pages
$total_pages = ceil($total_records / $filters['per_page']);
$filters['page'] = min($filters['page'], max(1, $total_pages));

// Fetch exam types with result count
$exam_types = [];
try {
    $query = "
        SELECT et.*, 
            (SELECT COUNT(*) FROM results r WHERE r.exam_type_id = et.id) as result_count
        FROM exam_types et
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY et.type_name
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
        $exam_types[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching exam types: " . $e->getMessage());
    $error = "Error fetching exam types";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Exam Type Management</h2>
    <a href="exam_type_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Exam Type
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Type Name or Description">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="exam_types.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Exam Types List -->
<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type Name</th>
                        <th>Description</th>
                        <th>Results</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exam_types)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No exam types found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exam_types as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['type_name']); ?></td>
                                <td><?php echo htmlspecialchars($type['description']); ?></td>
                                <td><?php echo $type['result_count']; ?></td>
                                <td>
                                    <form method="POST" action="process_exam_type.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $type['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-<?php echo $type['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="exam_type_form.php?id=<?php echo $type['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($type['result_count'] == 0): ?>
                                            <a href="exam_type_delete.php?id=<?php echo $type['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this exam type?')"
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

<?php require_once 'includes/footer.php'; ?> 