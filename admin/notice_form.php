<?php
require_once 'includes/header.php';

// Initialize notice data
$notice = [
    'id' => '',
    'title' => '',
    'content' => '',
    'is_active' => 1
];

$error = '';
$is_edit = false;

// Display error message if any
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'missing_fields':
            $error = 'All required fields must be filled out.';
            break;
        case 'db_error':
            $error = 'Database error occurred. Please try again.';
            break;
        case 'fetch_failed':
            $error = 'Failed to fetch notice details.';
            break;
    }
}

// If editing existing notice
if (isset($_GET['id'])) {
    $is_edit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($fetched_notice = $result->fetch_assoc()) {
            $notice = $fetched_notice;
        } else {
            header('Location: notices.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching notice: " . $e->getMessage());
        header('Location: notices.php?error=fetch_failed');
        exit();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $is_edit ? 'Edit' : 'Add'; ?> Notice</h2>
    <a href="notices.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Notices
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="noticeForm" method="POST" action="process_notice.php" class="needs-validation" novalidate>
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">

            <div class="mb-3">
                <label for="title" class="form-label">Title *</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($notice['title']); ?>" required>
                <div class="invalid-feedback">Please enter a title.</div>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content *</label>
                <textarea class="form-control" id="content" name="content" 
                          rows="10" style="resize: vertical;" required><?php echo htmlspecialchars($notice['content']); ?></textarea>
                <div class="invalid-feedback">Please enter the notice content.</div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                           <?php echo $notice['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $is_edit ? 'Update' : 'Save'; ?> Notice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.getElementById('noticeForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>

<?php require_once 'includes/footer.php'; ?> 