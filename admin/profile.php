<?php
require_once 'includes/header.php';

// Get admin details
$admin = [];
try {
    $stmt = $conn->prepare("SELECT id, username, full_name FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
} catch(Exception $e) {
    error_log("Error fetching admin details: " . $e->getMessage());
    $error = "Error fetching profile details";
}

// Display success/error messages
$message = '';
$message_type = '';

if (isset($_GET['success'])) {
    $message_type = 'success';
    switch($_GET['success']) {
        case 'profile':
            $message = 'Profile details updated successfully.';
            break;
        case 'password':
            $message = 'Password changed successfully.';
            break;
    }
}

if (isset($_GET['error'])) {
    $message_type = 'danger';
    switch($_GET['error']) {
        case 'current_password':
            $message = 'Current password is incorrect.';
            break;
        case 'password_match':
            $message = 'New passwords do not match.';
            break;
        case 'db_error':
            $message = 'Database error occurred. Please try again.';
            break;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Profile Management</h2>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Details -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Profile Details</h5>
            </div>
            <div class="card-body">
                <form action="process_profile.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        <div class="invalid-feedback">Please enter a username.</div>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form action="process_profile.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" required minlength="6">
                        <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                        <div class="invalid-feedback">Please confirm your new password.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Check if this is the password form
        if (form.querySelector('#new_password')) {
            const newPassword = form.querySelector('#new_password').value;
            const confirmPassword = form.querySelector('#confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                event.preventDefault();
                form.querySelector('#confirm_password').setCustomValidity('Passwords do not match');
            } else {
                form.querySelector('#confirm_password').setCustomValidity('');
            }
        }
        
        form.classList.add('was-validated');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 