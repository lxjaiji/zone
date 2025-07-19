<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// If an admin somehow lands here, redirect them to the admin dashboard (optional, or could show admin view)
if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true){
    // Option 1: Redirect admin to their more powerful page
    // header("location: manage_zoning.php");
    // exit;
    // Option 2: Allow admin to see this user view too (less likely needed)
}

require_once "config.php";
$link = get_db_connection();

$zoning_certificates_user = [];
// For now, users can view all certificates.
// To restrict to user-specific, schema needs applicant_user_id or similar.
// Then query would be: "SELECT ... WHERE applicant_user_id = ?" with $_SESSION['id']
$sql = "SELECT id, certificate_number, applicant_name, zoning_classification, date_filed, expiration_date FROM zoning_certificates ORDER BY date_filed DESC, id DESC";

// Pagination variables (same as admin page)
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$total_sql = "SELECT COUNT(*) FROM zoning_certificates"; // No user filter for now
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

$sql .= " LIMIT $offset, $records_per_page";

if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $zoning_certificates_user[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    $_SESSION['error_user_dash'] = "ERROR: Could not fetch zoning certificates. " . mysqli_error($link);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Zoning Certificates</title>
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
            <h2>Zoning Certificates</h2>
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

        <?php if(!empty($zoning_certificates_user)): ?>
        <table>
            <thead>
                <tr>
                    <th>Cert. No.</th>
                    <th>Applicant Name</th>
                    <th>Zoning Classification</th>
                    <th>Date Filed</th>
                    <th>Expiration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($zoning_certificates_user as $cert): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cert['certificate_number']); ?></td>
                    <td><?php echo htmlspecialchars($cert['applicant_name']); ?></td>
                    <td><?php echo htmlspecialchars($cert['zoning_classification']); ?></td>
                    <td><?php echo htmlspecialchars($cert['date_filed']); ?></td>
                    <td><?php echo htmlspecialchars($cert['expiration_date']); ?></td>
                    <td class="action-links">
                        <a href="view_zoning.php?id=<?php echo $cert['id']; ?>" title="View Details">View</a>
                        <a href="print_zoning.php?id=<?php echo $cert['id']; ?>" title="Print" target="_blank">Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination Links -->
        <div style="margin-top: 20px; text-align: center;">
             <?php if($total_pages > 1): ?>
                <?php if($page > 1): ?>
                    <a href="manage_zoning_user.php?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_zoning_user.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="manage_zoning_user.php?page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <?php if(!isset($_SESSION['error_user_dash'])): // Only show "no records" if there wasn't a DB error ?>
                <p class="text-center" style="margin-top:20px;">No zoning certificates found. <a href="add_zoning.php" class="btn btn-primary">Add New Certificate</a></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
