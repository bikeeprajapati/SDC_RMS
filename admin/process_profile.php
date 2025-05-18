<?php
require_once 'includes/session.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit();
}

// Get the action
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profile':
        // Validate required fields
        if (empty($_POST['username']) || empty($_POST['full_name'])) {
            header('Location: profile.php?error=missing_fields');
            exit();
        }

        // Sanitize input
        $profile = [
            'username' => trim($_POST['username']),
            'full_name' => trim($_POST['full_name'])
        ];

        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $profile['username'], $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                header('Location: profile.php?error=username_exists');
                exit();
            }

            // Begin transaction
            $conn->begin_transaction();

            // Update profile
            $stmt = $conn->prepare("
                UPDATE admin 
                SET username = ?, 
                    full_name = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "ssi",
                $profile['username'],
                $profile['full_name'],
                $_SESSION['admin_id']
            );
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Location: profile.php?success=profile');
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error updating profile: " . $e->getMessage());
            header('Location: profile.php?error=db_error');
            exit();
        }
        break;

    case 'change_password':
        // Validate required fields
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
            header('Location: profile.php?error=missing_fields');
            exit();
        }

        // Validate password match
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            header('Location: profile.php?error=password_match');
            exit();
        }

        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if (!password_verify($_POST['current_password'], $admin['password'])) {
                header('Location: profile.php?error=current_password');
                exit();
            }

            // Begin transaction
            $conn->begin_transaction();

            // Update password
            $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password_hash, $_SESSION['admin_id']);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Location: profile.php?success=password');
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error changing password: " . $e->getMessage());
            header('Location: profile.php?error=db_error');
            exit();
        }
        break;

    default:
        header('Location: profile.php');
        exit();
} 