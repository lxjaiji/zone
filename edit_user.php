<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

$username = "";
$is_admin = 0;
$password = "";
$confirm_password = "";
$user_id = 0;

$username_err = $password_err = $confirm_password_err = "";
$update_success_msg = "";

// Check for user ID in URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $user_id = trim($_GET["id"]);

    // Fetch user data on initial GET request
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $sql = "SELECT username, is_admin FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_assoc($result);
                    $username = $row["username"];
                    $is_admin = $row["is_admin"];
                } else {
                    $_SESSION['error'] = "No user found with that ID.";
                    header("location: admin_dashboard.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Error fetching user data.";
                header("location: admin_dashboard.php");
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $_SESSION['error'] = "No user ID specified.";
    header("location: admin_dashboard.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["id"];
    $username = $_POST["username"]; // Username is readonly, but get it for messages
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Password validation (only if new password is provided)
    if (!empty(trim($_POST["password"]))) {
        $password = trim($_POST["password"]);
        if (strlen($password) < 6) {
            $password_err = "Password must have at least 6 characters.";
        }

        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($confirm_password)) {
            $confirm_password_err = "Please confirm password.";
        } else {
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "Passwords did not match.";
            }
        }
    }

    // If no errors, proceed with update
    if (empty($password_err) && empty($confirm_password_err)) {
        // If password is not being changed
        if (empty($password)) {
            $sql = "UPDATE users SET is_admin = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ii", $is_admin, $user_id);
            }
        } else { // If password is being changed
            // IMPORTANT: HASH THE NEW PASSWORD
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, is_admin = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sii", $hashed_password, $is_admin, $user_id);
            }
        }

        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "User '" . htmlspecialchars($username) . "' updated successfully.";
                header("location: admin_dashboard.php");
                exit;
            } else {
                $username_err = "Something went wrong. Please try again later.";
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
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav>
            <a href="main_dashboard.php">Dashboard</a>
            <a href="manage_zoning.php">Zoning Certificates</a>
            <a href="manage_locational.php">Locational Clearances</a>
            <div class="dropdown">
                <a href="#" class="dropbtn">Admin Settings</a>
                <div class="dropdown-content">
                    <a href="settings.php">Application Settings</a>
                    <a href="admin_dashboard.php">User Management</a>
                </div>
            </div>
            <a href="logout.php" style="float:right;">Sign Out</a>
        </nav>
        <div class="wrapper" style="max-width: 600px; margin: 20px auto;">
            <h2>Edit User: <?php echo htmlspecialchars($username); ?></h2>
            <p>Modify user details below. Leave password fields blank to keep the current password.</p>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username_display" class="form-control" value="<?php echo htmlspecialchars($username); ?>" readonly style="background-color:#e9ecef;">
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="is_admin_checkbox">
                        <input type="checkbox" name="is_admin" id="is_admin_checkbox" value="1" <?php echo ($is_admin == 1) ? 'checked' : ''; ?>>
                        Make this user an Administrator
                    </label>
                </div>

                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update User">
                    <a href="admin_dashboard.php" class="btn btn-secondary" style="background-color: #6c757d; color:white; text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
