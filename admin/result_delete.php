<?php
require_once 'includes/session.php';
requireLogin();

// Validate result ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: results.php');
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Delete result
    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Result not found");
    }

    // Commit transaction
    $conn->commit();

    header('Location: results.php?success=deleted');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error deleting result: " . $e->getMessage());
    header('Location: results.php?error=delete_failed');
    exit();
} 