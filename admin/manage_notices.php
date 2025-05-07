<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $notice_id = (int)$_GET['id'];
    $sql = "UPDATE notices SET status = 'inactive' WHERE id = $notice_id";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: manage_notices.php?deleted=1');
        exit();
    }
}

// Get all active notices
$sql = "SELECT n.*, a.full_name as created_by_name 
        FROM notices n 
        JOIN admin a ON n.created_by = a.id 
        WHERE n.status = 'active' 
        ORDER BY n.created_at DESC";
$result = mysqli_query($conn, $sql);
$notices = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notices - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manage Notices</h5>
                        <a href="add_notice.php" class="btn btn-primary">Add New Notice</a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                            <div class="alert alert-success">Notice has been deleted successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                            <div class="alert alert-success">Notice has been added successfully.</div>
                        <?php endif; ?>
                        
                        <?php if (empty($notices)): ?>
                            <p class="text-muted">No notices found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notices as $notice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($notice['title']); ?></td>
                                                <td><?php echo htmlspecialchars($notice['created_by_name']); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($notice['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_notice.php?id=<?php echo $notice['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_notice.php?id=<?php echo $notice['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=1&id=<?php echo $notice['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this notice?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
