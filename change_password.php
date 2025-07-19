<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

$current_password = $new_password = $confirm_password = "";
$errors = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate current password
    if(empty($current_password)){
        $errors['current_password'] = "Please enter your current password.";
    }

    // Validate new password
    if(empty($new_password)){
        $errors['new_password'] = "Please enter a new password.";
    } elseif(strlen($new_password) < 6){
        $errors['new_password'] = "Password must have at least 6 characters.";
    }

    // Validate confirm password
    if(empty($confirm_password)){
        $errors['confirm_password'] = "Please confirm the new password.";
    } elseif(empty($errors['new_password']) && ($new_password != $confirm_password)){
        $errors['confirm_password'] = "Password did not match.";
    }

    if(empty($errors)){
        // Check if current password is correct
        $sql = "SELECT password FROM users WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($current_password, $hashed_password)){
                            // Current password is correct, update the password
                            $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                            if($stmt_update = mysqli_prepare($link, $sql_update)){
                                $param_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                                mysqli_stmt_bind_param($stmt_update, "si", $param_new_password, $_SESSION['id']);
                                if(mysqli_stmt_execute($stmt_update)){
                                    $_SESSION['message'] = "Your password has been changed successfully.";
                                    header("location: user_dashboard.php");
                                    exit;
                                } else {
                                    $_SESSION['error'] = "Oops! Something went wrong. Please try again later.";
                                }
                                mysqli_stmt_close($stmt_update);
                            }
                        } else {
                            $errors['current_password'] = "The password you entered was not valid.";
                        }
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper{ width: 360px; padding: 20px; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
            <h2>Change Password</h2>
            <p>Please fill out this form to change your password.</p>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control <?php echo (!empty($errors['current_password'])) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $errors['current_password']; ?></span>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?php echo (!empty($errors['new_password'])) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $errors['new_password']; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($errors['confirm_password'])) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $errors['confirm_password']; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a class="btn btn-link" href="user_dashboard.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
