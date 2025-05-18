<?php
require_once 'includes/session.php';
requireLogin();

// Redirect if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exam_types.php');
    exit();
}

// Get the action
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
    case 'update':
        // Validate required fields
        if (empty($_POST['type_name'])) {
            header('Location: exam_type_form.php?error=missing_fields');
            exit();
        }

        // Sanitize input
        $exam_type = [
            'type_name' => trim($_POST['type_name']),
            'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        try {
            // Check if type name already exists
            $stmt = $conn->prepare("SELECT id FROM exam_types WHERE type_name = ? AND id != ?");
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $stmt->bind_param("si", $exam_type['type_name'], $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                header('Location: exam_type_form.php?error=duplicate_name');
                exit();
            }

            // Begin transaction
            $conn->begin_transaction();

            if ($action === 'update' && isset($_POST['id'])) {
                // Update existing exam type
                $stmt = $conn->prepare("
                    UPDATE exam_types 
                    SET type_name = ?, 
                        description = ?, 
                        is_active = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "ssii",
                    $exam_type['type_name'],
                    $exam_type['description'],
                    $exam_type['is_active'],
                    $id
                );
            } else {
                // Insert new exam type
                $stmt = $conn->prepare("
                    INSERT INTO exam_types 
                    (type_name, description, is_active)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param(
                    "ssi",
                    $exam_type['type_name'],
                    $exam_type['description'],
                    $exam_type['is_active']
                );
            }

            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            header('Location: exam_types.php?success=' . ($action === 'update' ? 'updated' : 'created'));
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error processing exam type: " . $e->getMessage());
            header('Location: exam_type_form.php?error=db_error');
            exit();
        }
        break;

    case 'toggle_status':
        // Validate exam type ID
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            header('Location: exam_types.php');
            exit();
        }

        $id = (int)$_POST['id'];

        try {
            // Begin transaction
            $conn->begin_transaction();

            // Toggle the status
            $stmt = $conn->prepare("UPDATE exam_types SET is_active = NOT is_active WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            
            header('Location: exam_types.php?success=status_updated');
            exit();

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Error toggling exam type status: " . $e->getMessage());
            header('Location: exam_types.php?error=status_update_failed');
            exit();
        }
        break;

    default:
        header('Location: exam_types.php');
        exit();
} 