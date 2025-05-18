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
    $where_conditions[] = '(title LIKE ? OR content LIKE ?)';
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
$count_query = "SELECT COUNT(*) as total FROM notices WHERE " . implode(' AND ', $where_conditions);
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
    error_log("Error counting notices: " . $e->getMessage());
    $error = "Error fetching notice count";
}

// Calculate total pages
$total_pages = ceil($total_records / $filters['per_page']);
$filters['page'] = min($filters['page'], max(1, $total_pages));

// Fetch notices
$notices = [];
try {
    $query = "
        SELECT * FROM notices 
        WHERE " . implode(' AND ', $where_conditions) . "
        ORDER BY created_at DESC
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
        $notices[] = $row;
    }
} catch(Exception $e) {
    error_log("Error fetching notices: " . $e->getMessage());
    $error = "Error fetching notices";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Notice Management</h2>
    <a href="notice_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Notice
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
                       placeholder="Title or Content">
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
                    <a href="notices.php" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notices List -->
<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notices)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No notices found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                <td>
                                    <?php 
                                    $content_preview = strip_tags($notice['content']);
                                    echo htmlspecialchars(strlen($content_preview) > 100 ? 
                                        substr($content_preview, 0, 100) . '...' : 
                                        $content_preview); 
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" action="process_notice.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-<?php echo $notice['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $notice['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($notice['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="notice_form.php?id=<?php echo $notice['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="notice_delete.php?id=<?php echo $notice['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this notice?')"
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

<?php require_once 'includes/footer.php'; ?>