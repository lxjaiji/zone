<?php
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    $_SESSION['error'] = "You must be logged in as an admin to perform this action.";
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $clearance_id = trim($_GET["id"]);

    // Fetch the clearance number for the message before deleting
    $clearance_num_display = "N/A";
    $sql_fetch = "SELECT clearance_number FROM locational_clearances WHERE id = ?";
    if($stmt_fetch = mysqli_prepare($link, $sql_fetch)){
        mysqli_stmt_bind_param($stmt_fetch, "i", $clearance_id);
        if(mysqli_stmt_execute($stmt_fetch)){
            mysqli_stmt_bind_result($stmt_fetch, $fetched_num);
            if(mysqli_stmt_fetch($stmt_fetch)){
                $clearance_num_display = $fetched_num;
            }
        }
        mysqli_stmt_close($stmt_fetch);
    }

    // Prepare a delete statement
    $sql = "DELETE FROM locational_clearances WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $clearance_id;

        if(mysqli_stmt_execute($stmt)){
            if(mysqli_stmt_affected_rows($stmt) > 0){
                $_SESSION['message'] = "Locational Clearance '" . htmlspecialchars($clearance_num_display) . "' deleted successfully.";
            } else {
                $_SESSION['error'] = "Could not delete clearance. It might have been already deleted or the ID was invalid.";
            }
        } else{
            $_SESSION['error'] = "Oops! Something went wrong while deleting. Error: " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Error preparing delete statement: " . mysqli_error($link);
    }

    mysqli_close($link);
    header("location: manage_locational.php");
    exit;
} else {
    $_SESSION['error'] = "Invalid request: No clearance ID specified for deletion.";
    header("location: manage_locational.php");
    exit;
}
?>
