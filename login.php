<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true){
    header("location: main_dashboard.php");
    exit;
}

// Include config file
require_once "config.php";
$link = get_db_connection();

// Fetch the municipality logo path
$logo_path_login = 'logo_placeholder.png'; // Default
$sql_logo_login = "SELECT setting_value FROM settings WHERE setting_key = 'municipality_logo_path' LIMIT 1";
if($result_logo_login = mysqli_query($link, $sql_logo_login)){
    if(mysqli_num_rows($result_logo_login) == 1){
        $row_logo_login = mysqli_fetch_assoc($result_logo_login);
        $logo_path_login = $row_logo_login['setting_value'];
    }
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);

                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $is_admin);
                    if(mysqli_stmt_fetch($stmt)){
                        // Verify password
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            // session_start(); // Already started at the top

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["is_admin"] = (bool)$is_admin;

                            // Redirect user based on admin status
                            if($_SESSION["is_admin"]){
                                header("location: main_dashboard.php");
                            } else {
                                header("location: user_dashboard.php");
                            }
                            exit; // Crucial to stop script execution after redirect
                        } else{
                            // Password is not valid
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 120px; /* Adjust as needed */
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($logo_path_login); ?>?t=<?php echo time(); ?>" alt="System Logo">
        </div>
        <h2>Admin Login</h2>
        <p>Please fill in your credentials to login.</p>

        <?php
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
