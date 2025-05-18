<?php
require_once 'includes/header.php';

// Initialize exam type data
$exam_type = [
    'id' => '',
    'type_name' => '',
    'description' => '',
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
        case 'duplicate_name':
            $error = 'This exam type name is already in use.';
            break;
        case 'db_error':
            $error = 'Database error occurred. Please try again.';
            break;
        case 'fetch_failed':
            $error = 'Failed to fetch exam type details.';
            break;
    }
}

// If editing existing exam type
if (isset($_GET['id'])) {
    $is_edit = true;
    try {
        $stmt = $conn->prepare("SELECT * FROM exam_types WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($fetched_type = $result->fetch_assoc()) {
            $exam_type = $fetched_type;
        } else {
            header('Location: exam_types.php');
            exit();
        }
    } catch(Exception $e) {
        error_log("Error fetching exam type: " . $e->getMessage());
        header('Location: exam_types.php?error=fetch_failed');
        exit();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?php echo $is_edit ? 'Edit' : 'Add'; ?> Exam Type</h2>
    <a href="exam_types.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Exam Types
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="examTypeForm" method="POST" action="process_exam_type.php" class="needs-validation" novalidate>
            <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?php echo $exam_type['id']; ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">

            <div class="mb-3">
                <label for="type_name" class="form-label">Type Name *</label>
                <input type="text" class="form-control" id="type_name" name="type_name" 
                       value="<?php echo htmlspecialchars($exam_type['type_name']); ?>" required>
                <div class="invalid-feedback">Please enter a type name.</div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($exam_type['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                           <?php echo $exam_type['is_active'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $is_edit ? 'Update' : 'Save'; ?> Exam Type
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.getElementById('examTypeForm').addEventListener('submit', function(event) {
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>

<?php require_once 'includes/footer.php'; ?> 