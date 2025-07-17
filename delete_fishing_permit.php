<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);

    $sql = "DELETE FROM fishing_gear_permits WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);

        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['message'] = "Fishing Permit deleted successfully.";
            } else {
                $_SESSION['error'] = "Could not delete permit. It might have already been deleted.";
            }
        } else {
            $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Error preparing delete statement.";
    }

    mysqli_close($link);
    header("location: manage_fishing_permits.php");
    exit;
} else {
    $_SESSION['error'] = "No permit ID specified for deletion.";
    header("location: manage_fishing_permits.php");
    exit;
}
?>
