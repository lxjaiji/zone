<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Optional: Redirect admin if they land here
if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true){
    // header("location: manage_locational.php");
    // exit;
}

require_once "config.php";
$link = get_db_connection();

$locational_clearances_user = [];
// For now, users can view all clearances.
// To restrict, schema needs applicant_user_id or similar.
$sql = "SELECT id, clearance_number, applicant_name, project_type, date_filed, expiration_date FROM locational_clearances ORDER BY date_filed DESC, id DESC";

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$total_sql = "SELECT COUNT(*) FROM locational_clearances";
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

$sql .= " LIMIT $offset, $records_per_page";

if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $locational_clearances_user[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    $_SESSION['error_user_dash_lc'] = "ERROR: Could not fetch locational clearances. " . mysqli_error($link);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Locational Clearances</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
            <h2>Locational Clearances</h2>
        </div>

        <?php
        if(isset($_SESSION['message_user_dash_lc'])){
            echo '<p class="alert alert-success" style="text-align:center;">'.$_SESSION['message_user_dash_lc'].'</p>';
            unset($_SESSION['message_user_dash_lc']);
        }
        if(isset($_SESSION['error_user_dash_lc'])){
            echo '<p class="alert alert-danger" style="text-align:center;">'.$_SESSION['error_user_dash_lc'].'</p>';
            unset($_SESSION['error_user_dash_lc']);
        }
        ?>

        <?php if(!empty($locational_clearances_user)): ?>
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
                <?php foreach($locational_clearances_user as $lc): ?>
                <tr>
                    <td><?php echo htmlspecialchars($lc['clearance_number']); ?></td>
                    <td><?php echo htmlspecialchars($lc['applicant_name']); ?></td>
                    <td><?php echo htmlspecialchars($lc['project_type']); ?></td>
                    <td><?php echo htmlspecialchars($lc['date_filed']); ?></td>
                    <td><?php echo htmlspecialchars($lc['expiration_date']); ?></td>
                    <td class="action-links">
                        <a href="view_locational.php?id=<?php echo $lc['id']; ?>" title="View Details">View</a>
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
                    <a href="manage_locational_user.php?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_locational_user.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="manage_locational_user.php?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
             <?php if(!isset($_SESSION['error_user_dash_lc'])): ?>
                <p class="text-center" style="margin-top:20px;">No locational clearances found.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
