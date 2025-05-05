<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Delete subject
$sql = "DELETE FROM subjects WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $subject_id);
    if ($stmt->execute()) {
        header("Location: subjects.php?success=Subject deleted successfully");
        exit();
    } else {
        header("Location: subjects.php?error=Error deleting subject: " . $conn->error);
        exit();
    }
} else {
    header("Location: subjects.php?error=Error preparing statement: " . $conn->error);
    exit();
}
?>
