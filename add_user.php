<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";

$username = $password = "";
$username_err = $password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else {
        // Check if username already exists
        $sql_check = "SELECT id FROM users WHERE username = ?";
        if($stmt_check = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt_check, "s", $param_check_username);
            $param_check_username = trim($_POST["username"]);
            if(mysqli_stmt_execute($stmt_check)){
                mysqli_stmt_store_result($stmt_check);
                if(mysqli_stmt_num_rows($stmt_check) == 1){
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                $_SESSION['error'] = "Oops! Something went wrong checking username. Please try again later.";
                header("location: admin_dashboard.php");
                exit;
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } elseif(strlen(trim($_POST["password"])) < 6){ // Example: Minimum password length
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err)){
        // Determine the admin status from the checkbox
        $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] == '1' ? 1 : 0;

        // Prepare an insert statement
        // In a real application, you MUST hash the password.
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, is_admin) VALUES (?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssi", $param_username, $param_password, $param_is_admin);

            // Set parameters
            $param_username = $username;
            $param_password = $password; // Store plain password for now. Replace with $hashed_password
            $param_is_admin = $is_admin;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "User added successfully.";
                header("location: admin_dashboard.php");
                exit;
            } else{
                $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
                header("location: admin_dashboard.php");
                exit;
            }
            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        // If there are errors, store them in session and redirect back
        $error_message = "";
        if(!empty($username_err)) $error_message .= $username_err . "<br>";
        if(!empty($password_err)) $error_message .= $password_err;
        $_SESSION['error'] = $error_message;
        header("location: admin_dashboard.php");
        exit;
    }
    // Close connection
    mysqli_close($link);
} else {
    // If not a POST request, redirect to dashboard
    header("location: admin_dashboard.php");
    exit;
}
?>
