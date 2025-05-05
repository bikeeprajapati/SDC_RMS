<?php
session_start();
require_once '../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM admins WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['user_type'] = 'admin';

            // Update last login
            $update = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $update->bind_param("i", $admin['id']);
            $update->execute();

            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (86400 * 30); // 30 days
                
                $update = $conn->prepare("UPDATE admins SET remember_token = ? WHERE id = ?");
                $update->bind_param("si", $token, $admin['id']);
                $update->execute();
                
                setcookie('remember_token', $token, $expires, '/', '', true, true);
            }

            // Log the login
            $ip = $_SERVER['REMOTE_ADDR'];
            $log = $conn->prepare("INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, ip_address) VALUES (?, 'admin', 'login', 'admins', ?, ?)");
            $log->bind_param("iis", $admin['id'], $admin['id'], $ip);
            $log->execute();

            header("Location: ../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid password!";
        }
    } else {
        $_SESSION['error'] = "Invalid username or account is inactive!";
    }

    $stmt->close();
    header("Location: ../index.php");
    exit();
} else {
    header("Location: ../index.php");
    exit();
}
?> 