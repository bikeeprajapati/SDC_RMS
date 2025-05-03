<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config/database.php";

// Process delete operation after confirmation
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Get URL parameter
    $symbol_no = trim($_GET["id"]);
    
    // Prepare a delete statement
    $sql = "DELETE FROM students WHERE symbol_no = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $param_symbol);
        $param_symbol = $symbol_no;
        
        if(mysqli_stmt_execute($stmt)){
            // Records deleted successfully. Redirect to students page
            header("location: students.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: students.php");
    exit();
}
?> 