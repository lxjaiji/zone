<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// If an admin somehow lands here, redirect them to the admin dashboard
if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true){
    header("location: admin_dashboard.php");
    exit;
}

// User specific data can be fetched here if needed in the future
$username = htmlspecialchars($_SESSION["username"]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .dashboard-header h1 {
            display: inline;
        }
        .dashboard-header a.logout-btn {
            float: right;
            text-decoration: none;
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .dashboard-header a.logout-btn:hover {
            background-color: #c82333;
        }
        .dashboard-links {
            display: flex;
            justify-content: space-around; /* Or space-evenly */
            flex-wrap: wrap;
            gap: 20px;
        }
        .dashboard-link-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            width: calc(50% - 40px); /* Two cards per row, accounting for gap */
            min-width: 250px; /* Minimum width for smaller screens */
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .dashboard-link-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .dashboard-link-card p {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        .dashboard-link-card a.btn-view {
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .dashboard-link-card a.btn-view:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <a href="logout.php" class="logout-btn">Sign Out</a>
            <h1>Welcome, <?php echo $username; ?>!</h1>
            <p>This is your personal dashboard to view certificates and clearances.</p>
        </div>

        <div class="dashboard-links">
            <div class="dashboard-link-card">
                <h3>Zoning Certificates</h3>
                <p>View and print your Zoning Certificates.</p>
                <!-- This will eventually link to a page that lists certificates accessible to this user -->
                <!-- For now, it can link to manage_zoning.php which will need to be adapted for user view -->
                <a href="manage_zoning_user.php" class="btn-view">View Zoning Certificates</a>
            </div>

            <div class="dashboard-link-card">
                <h3>Locational Clearances</h3>
                <p>View and print your Locational Clearances.</p>
                <!-- Similar to above, this will link to a user-specific list page -->
                <a href="manage_locational_user.php" class="btn-view">View Locational Clearances</a>
            </div>
        </div>

        <?php
        // Display messages if any (e.g., from a failed action on a linked page)
        if(isset($_SESSION['message'])){
            echo '<p class="alert alert-success" style="margin-top:20px; text-align:center;">'.$_SESSION['message'].'</p>';
            unset($_SESSION['message']);
        }
        if(isset($_SESSION['error'])){
            echo '<p class="alert alert-danger" style="margin-top:20px; text-align:center;">'.$_SESSION['error'].'</p>';
            unset($_SESSION['error']);
        }
        ?>
    </div>
</body>
</html>
