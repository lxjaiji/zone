<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $user_id = trim($_GET["id"]);

    // Prepare a delete statement
    $sql = "DELETE FROM users WHERE id = ? AND is_admin = FALSE"; // Ensure we don't delete admins accidentally

    if($stmt = mysqli_prepare($link, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "i", $param_id);

        // Set parameters
        $param_id = $user_id;

        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            if(mysqli_stmt_affected_rows($stmt) > 0){
                $_SESSION['message'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Could not delete user. User not found or is an admin.";
            }
        } else{
            $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
        }
        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Error preparing delete statement.";
    }

    // Close connection
    mysqli_close($link);

    // Redirect to admin dashboard
    header("location: admin_dashboard.php");
    exit;
} else{
    // If ID parameter is missing, redirect
    $_SESSION['error'] = "No user ID specified for deletion.";
    header("location: admin_dashboard.php");
    exit;
}
?>
