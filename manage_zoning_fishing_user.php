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
    header("location: manage_zoning_fishing.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

$zoning_fishing_clearances_user = [];
// For now, users can view all clearances.
$sql = "SELECT id, owner, fishing_structure_gear, date_of_issuance FROM zoning_fishing_clearances ORDER BY date_of_issuance DESC, id DESC";

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$total_sql = "SELECT COUNT(*) FROM zoning_fishing_clearances";
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

$sql .= " LIMIT $offset, $records_per_page";

if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $zoning_fishing_clearances_user[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    $_SESSION['error_user_dash'] = "ERROR: Could not fetch zoning clearances. " . mysqli_error($link);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Zoning Fishing Structure/Gear Clearances</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Basic styling, can be expanded or use more from style.css */
        .user-view-container { width: 90%; margin: 20px auto; padding: 15px; background-color: #fff; border-radius: 8px; }
        .page-header-user { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header-user h2 { margin: 0; }
        .btn-back-user {
            text-decoration: none;
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
        }
         .btn-back-user:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="page-header-user" style="margin-top:20px;">
            <h2>Zoning Fishing Structure/Gear Clearances</h2>
        </div>

        <?php
        if(isset($_SESSION['message_user_dash'])){ // For messages specific to this page flow
            echo '<p class="alert alert-success" style="text-align:center;">'.$_SESSION['message_user_dash'].'</p>';
            unset($_SESSION['message_user_dash']);
        }
        if(isset($_SESSION['error_user_dash'])){
            echo '<p class="alert alert-danger" style="text-align:center;">'.$_SESSION['error_user_dash'].'</p>';
            unset($_SESSION['error_user_dash']);
        }
        ?>

        <?php if(!empty($zoning_fishing_clearances_user)): ?>
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
                <?php foreach($zoning_fishing_clearances_user as $clearance): ?>
                <tr>
                    <td><?php echo htmlspecialchars($clearance['owner']); ?></td>
                    <td><?php echo htmlspecialchars($clearance['fishing_structure_gear']); ?></td>
                    <td><?php echo htmlspecialchars($clearance['date_of_issuance']); ?></td>
                    <td class="action-links">
                        <a href="view_zoning_fishing.php?id=<?php echo $clearance['id']; ?>" title="View Details">View</a>
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
                    <a href="manage_zoning_fishing_user.php?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_zoning_fishing_user.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="manage_zoning_fishing_user.php?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <?php if(!isset($_SESSION['error_user_dash'])): // Only show "no records" if there wasn't a DB error ?>
                <p class="text-center" style="margin-top:20px;">No zoning fishing structure/gear clearances found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
