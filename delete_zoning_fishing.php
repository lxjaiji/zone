<?php
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    $_SESSION['error'] = "You must be logged in as an admin to perform this action.";
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $clearance_id = trim($_GET["id"]);

    // Prepare a delete statement
    $sql = "DELETE FROM zoning_fishing_clearances WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "i", $param_id);

        // Set parameters
        $param_id = $clearance_id;

        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            if(mysqli_stmt_affected_rows($stmt) > 0){
                $_SESSION['message'] = "Zoning Fishing Structure/Gear Clearance deleted successfully.";
            } else {
                $_SESSION['error'] = "Could not delete clearance. It might have been already deleted or the ID was invalid.";
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
    header("location: manage_zoning_fishing.php");
    exit;
} else{
    // If ID parameter is missing or empty
    $_SESSION['error'] = "Invalid request: No clearance ID specified for deletion.";
    header("location: manage_zoning_fishing.php");
    exit;
}
?>
