<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Fetch all locational clearances
$locational_clearances = [];
$sql = "SELECT id, clearance_number, applicant_name, project_type, date_filed, expiration_date FROM locational_clearances ORDER BY date_filed DESC, id DESC";

// Pagination variables
$records_per_page = 10; // Or get from a config file
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_sql = "SELECT COUNT(*) FROM locational_clearances";
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

// Modify SQL to include LIMIT and OFFSET
$sql .= " LIMIT $offset, $records_per_page";


if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $locational_clearances[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    // Store error in session to display on page, or log it
    $_SESSION['error'] = "ERROR: Could not execute query to fetch locational clearances. " . mysqli_error($link);
}

// mysqli_close($link); // Keep connection open for other operations or includes
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Locational Clearances</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmDeleteLocational(clearanceId) {
            if (confirm("Are you sure you want to delete this locational clearance? This action cannot be undone.")) {
                window.location.href = 'delete_locational.php?id=' + clearanceId;
            }
        }
    </script>
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

        <div class="page-header" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
            <h2>Manage Locational Clearances</h2>
            <a href="add_locational.php" class="btn btn-success" style="background-color: #28a745; border-color: #28a745; color:white; text-decoration:none; padding: 10px 15px; border-radius:5px;">Add New Clearance</a>
        </div>

        <?php
        if(isset($_SESSION['message'])){
            echo '<p class="alert alert-success" style="text-align:center;">'.$_SESSION['message'].'</p>';
            unset($_SESSION['message']);
        }
        if(isset($_SESSION['error'])){ // Display errors, e.g., from DB query failure
            echo '<p class="alert alert-danger" style="text-align:center;">'.$_SESSION['error'].'</p>';
            unset($_SESSION['error']);
        }
        ?>

        <?php if(!empty($locational_clearances)): ?>
        <table>
            <thead>
                <tr>
                    <th>Clearance No.</th>
                    <th>Applicant Name</th>
                    <th>Project Type</th>
                    <th>Date Filed</th>
                    <th>Expiration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($locational_clearances as $lc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($lc['clearance_number']); ?></td>
                    <td><?php echo htmlspecialchars($lc['applicant_name']); ?></td>
                    <td><?php echo htmlspecialchars($lc['project_type']); ?></td>
                    <td><?php echo htmlspecialchars($lc['date_filed']); ?></td>
                    <td><?php echo htmlspecialchars($lc['expiration_date']); ?></td>
                    <td class="action-links">
                        <a href="view_locational.php?id=<?php echo $lc['id']; ?>" title="View Details">View</a>
                        <a href="edit_locational.php?id=<?php echo $lc['id']; ?>" title="Edit">Edit</a>
                        <a href="#" onclick="confirmDeleteLocational(<?php echo $lc['id']; ?>); return false;" class="delete" title="Delete">Delete</a>
                        <a href="print_locational.php?id=<?php echo $lc['id']; ?>" title="Print" target="_blank">Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination Links -->
        <div style="margin-top: 20px; text-align: center;">
            <?php if($total_pages > 1): ?>
                <?php if($page > 1): ?>
                    <a href="manage_locational.php?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_locational.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="manage_locational.php?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-center" style="margin-top:20px;">No locational clearances found. <a href="add_locational.php">Add one now</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
