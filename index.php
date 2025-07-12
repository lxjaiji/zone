<?php
// Check if user is already logged in and redirect accordingly
session_start();

// Include config file to get database connection
require_once "config.php";

// Fetch the municipality logo path
$logo_path = 'logo_placeholder.png'; // Default
$sql_logo = "SELECT setting_value FROM settings WHERE setting_key = 'municipality_logo_path' LIMIT 1";
if($result_logo = mysqli_query($link, $sql_logo)){
    if(mysqli_num_rows($result_logo) == 1){
        $row_logo = mysqli_fetch_assoc($result_logo);
        $logo_path = $row_logo['setting_value'];
    }
}
// It's okay to close the connection here as it's only needed for the logo path
mysqli_close($link);


if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true){
        header("location: admin_dashboard.php");
        exit;
    } else {
        // Optional: Redirect non-admin users to a different dashboard if implemented
        // header("location: user_dashboard.php");
        // For now, if logged in but not admin, could redirect to login or a generic page
        // Or, keep them on index but change content (though simpler to redirect to login if no user page yet)
        header("location: login.php"); // Or a simple "logged_in.php" page
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Zoning and Locational Clearance System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            background-color: #f0f2f5; /* A slightly different background for the landing page */
        }
        .landing-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 450px;
        }
        .logo-container img {
            max-width: 150px; /* Adjust as needed */
            margin-bottom: 25px;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .btn-proceed {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }
        .btn-proceed:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="logo-container">
            <img src="<?php echo htmlspecialchars($logo_path); ?>?t=<?php echo time(); ?>" alt="System Logo">
        </div>
        <h1>Zoning and Locational Clearance System</h1>
        <p>Manage and track your applications efficiently.</p>
        <a href="login.php" class="btn btn-proceed">Proceed to Login</a>
    </div>
</body>
</html>
