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
$link = get_db_connection();

// Fetch all zoning fishing clearances
$zoning_fishing_clearances = [];
$sql = "SELECT id, owner, fishing_structure_gear, date_of_issuance FROM zoning_fishing_clearances ORDER BY date_of_issuance DESC, id DESC";

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_sql = "SELECT COUNT(*) FROM zoning_fishing_clearances";
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

// Modify SQL to include LIMIT and OFFSET
$sql .= " LIMIT $offset, $records_per_page";

if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $zoning_fishing_clearances[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Zoning Fishing Structure/Gear Clearances</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmDelete(clearanceId) {
            if (confirm("Are you sure you want to delete this zoning clearance? This action cannot be undone.")) {
                window.location.href = 'delete_zoning_fishing.php?id=' + clearanceId;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>

        <div class="page-header" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
            <h2>Manage Zoning Fishing Structure/Gear Clearances</h2>
            <a href="add_zoning_fishing.php" class="btn btn-success" style="background-color: #28a745; border-color: #28a745; color:white; text-decoration:none; padding: 10px 15px; border-radius:5px;">Add New Clearance</a>
        </div>

        <?php
        if(isset($_SESSION['message'])){
            echo '<p class="alert alert-success" style="text-align:center;">'.$_SESSION['message'].'</p>';
            unset($_SESSION['message']);
        }
        if(isset($_SESSION['error'])){
            echo '<p class="alert alert-danger" style="text-align:center;">'.$_SESSION['error'].'</p>';
            unset($_SESSION['error']);
        }
        ?>

        <?php if(!empty($zoning_fishing_clearances)): ?>
        <table>
            <thead>
                <tr>
                    <th>Owner</th>
                    <th>Fishing Structure/Gear</th>
                    <th>Date of Issuance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($zoning_fishing_clearances as $clearance): ?>
                <tr>
                    <td><?php echo htmlspecialchars($clearance['owner']); ?></td>
                    <td><?php echo htmlspecialchars($clearance['fishing_structure_gear']); ?></td>
                    <td><?php echo htmlspecialchars($clearance['date_of_issuance']); ?></td>
                    <td class="action-links">
                        <a href="view_zoning_fishing.php?id=<?php echo $clearance['id']; ?>" title="View Details">View</a>
                        <a href="edit_zoning_fishing.php?id=<?php echo $clearance['id']; ?>" title="Edit">Edit</a>
                        <a href="#" onclick="confirmDelete(<?php echo $clearance['id']; ?>); return false;" class="delete" title="Delete">Delete</a>
                        <a href="print_zoning_fishing.php?id=<?php echo $clearance['id']; ?>" title="Print" target="_blank">Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination Links -->
        <div style="margin-top: 20px; text-align: center;">
            <?php if($total_pages > 1): ?>
                <?php if($page > 1): ?>
                    <a href="manage_zoning_fishing.php?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_zoning_fishing.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="manage_zoning_fishing.php?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-center" style="margin-top:20px;">No zoning fishing structure/gear clearances found. <a href="add_zoning_fishing.php">Add one now</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
