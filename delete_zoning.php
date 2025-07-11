<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    $_SESSION['error'] = "You must be logged in as an admin to perform this action.";
    // Try to redirect to login, but if headers already sent, this might not work.
    // It's better to check this before any output on pages that link here.
    header("location: login.php");
    exit;
}

require_once "config.php";

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $certificate_id = trim($_GET["id"]);

    // First, fetch the certificate number for the message before deleting
    $cert_number = "N/A";
    $sql_fetch = "SELECT certificate_number FROM zoning_certificates WHERE id = ?";
    if($stmt_fetch = mysqli_prepare($link, $sql_fetch)){
        mysqli_stmt_bind_param($stmt_fetch, "i", $certificate_id);
        if(mysqli_stmt_execute($stmt_fetch)){
            mysqli_stmt_bind_result($stmt_fetch, $fetched_cert_number);
            if(mysqli_stmt_fetch($stmt_fetch)){
                $cert_number = $fetched_cert_number;
            }
        }
        mysqli_stmt_close($stmt_fetch);
    }


    // Prepare a delete statement
    $sql = "DELETE FROM zoning_certificates WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "i", $param_id);

        // Set parameters
        $param_id = $certificate_id;

        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            if(mysqli_stmt_affected_rows($stmt) > 0){
                $_SESSION['message'] = "Zoning Certificate '" . htmlspecialchars($cert_number) . "' deleted successfully.";
            } else {
                $_SESSION['error'] = "Could not delete certificate. It might have been already deleted or the ID was invalid.";
            }
        } else{
            $_SESSION['error'] = "Oops! Something went wrong while deleting. Please try again later. Error: " . mysqli_error($link);
        }
        // Close statement
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Error preparing delete statement: " . mysqli_error($link);
    }

    // Close connection
    mysqli_close($link);

    // Redirect to management page
    header("location: manage_zoning.php");
    exit;
} else{
    // If ID parameter is missing or empty
    $_SESSION['error'] = "Invalid request: No certificate ID specified for deletion.";
    header("location: manage_zoning.php");
    exit;
}
?>
