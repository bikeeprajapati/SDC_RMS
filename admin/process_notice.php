<?php
require_once 'includes/session.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: notices.php');
    exit();
}

// Get the action
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
    case 'update':
        // Validate required fields
        if (empty($_POST['title']) || empty($_POST['content'])) {
            header('Location: notice_form.php?error=missing_fields');
            exit();
        }

        // Sanitize input
        $notice = [
            'title' => trim($_POST['title']),
            'content' => trim($_POST['content']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        try {
            // Begin transaction
            $conn->begin_transaction();

            if ($action === 'update' && isset($_POST['id'])) {
                // Update existing notice
                $stmt = $conn->prepare("
                    UPDATE notices 
                    SET title = ?, 
                        content = ?, 
                        is_active = ?
                    WHERE id = ?
                ");
                $id = (int)$_POST['id'];
                $stmt->bind_param(
                    "ssii",
                    $notice['title'],
                    $notice['content'],
                    $notice['is_active'],
                    $id
                );
            } else {
                // Insert new notice
                $stmt = $conn->prepare("
                    INSERT INTO notices 
                    (title, content, is_active)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssi",
                    $notice['title'],
                    $notice['content'],
                    $notice['is_active']
                );
            }

            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Location: notices.php?success=' . ($action === 'update' ? 'updated' : 'created'));
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error processing notice: " . $e->getMessage());
            header('Location: notice_form.php?error=db_error');
            exit();
        }
        break;

    case 'toggle_status':
        // Validate notice ID
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            header('Location: notices.php');
            exit();
        }

        $id = (int)$_POST['id'];

        try {
            // Begin transaction
            $conn->begin_transaction();

            // Toggle the status
            $stmt = $conn->prepare("UPDATE notices SET is_active = NOT is_active WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            
            header('Location: notices.php?success=status_updated');
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error toggling notice status: " . $e->getMessage());
            header('Location: notices.php?error=status_update_failed');
            exit();
        }
        break;

    default:
        header('Location: notices.php');
        exit();
} 