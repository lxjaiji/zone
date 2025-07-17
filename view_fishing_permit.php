<?php
session_start();

// Require login to access this page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Determine user role for displaying controls
$is_admin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;

$permit = null;
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);
    $sql = "SELECT fp.*, u.username as encoded_by FROM fishing_gear_permits fp LEFT JOIN users u ON fp.encoded_by_user_id = u.id WHERE fp.id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $permit = mysqli_fetch_assoc($result);
            } else {
                $_SESSION['error'] = "No permit found with that ID.";
                header("location: " . ($is_admin ? 'manage_fishing_permits.php' : 'manage_fishing_permits_user.php'));
                exit;
            }
        }
    }
} else {
    $_SESSION['error'] = "No permit ID specified.";
    header("location: " . ($is_admin ? 'manage_fishing_permits.php' : 'manage_fishing_permits_user.php'));
    exit;
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Fishing Permit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 800px; margin: 20px auto; }
        .detail-item { margin-bottom: 10px; }
        .detail-item strong { min-width: 150px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
             <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Fishing Permit Details</h2>
                <a href="<?php echo $is_admin ? 'manage_fishing_permits.php' : 'manage_fishing_permits_user.php'; ?>" class="btn">Back to List</a>
            </div>
            <hr>
            <?php if ($permit): ?>
                <div class="detail-item"><strong>Gear Type:</strong> <span><?php echo htmlspecialchars($permit['gear_type']); ?></span></div>
                <div class="detail-item"><strong>Owner:</strong> <span><?php echo htmlspecialchars($permit['owner_name']); ?></span></div>
                <div class="detail-item"><strong>Resident of:</strong> <span><?php echo htmlspecialchars($permit['owner_resident_of']); ?></span></div>
                <div class="detail-item"><strong>Located in:</strong> <span><?php echo htmlspecialchars($permit['location']); ?></span></div>
                <div class="detail-item"><strong>Date of Issuance:</strong> <span><?php echo date("F j, Y", strtotime($permit['issue_date'])); ?></span></div>
                <hr>
                <div class="detail-item"><strong>O.R. No.:</strong> <span><?php echo htmlspecialchars($permit['or_number']); ?></span></div>
                <div class="detail-item"><strong>Amount Paid:</strong> <span><?php echo number_format($permit['amount_paid'], 2); ?></span></div>
                <div class="detail-item"><strong>Date Paid:</strong> <span><?php echo date("F j, Y", strtotime($permit['date_paid'])); ?></span></div>
                <div class="detail-item"><strong>Issued at:</strong> <span><?php echo htmlspecialchars($permit['issued_at']); ?></span></div>
                <hr>
                <div class="detail-item"><strong>Encoded By:</strong> <span><?php echo htmlspecialchars($permit['encoded_by']); ?></span></div>
                <div class="detail-item"><strong>Date Encoded:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($permit['created_at'])); ?></span></div>
                 <div class="actions" style="text-align:right; margin-top:20px;">
                    <a href="print_fishing_permit.php?id=<?php echo $permit['id']; ?>" target="_blank" class="btn btn-info" style="background-color:#17a2b8; color:white; text-decoration:none;">Print</a>
                    <?php if($is_admin): ?>
                    <a href="edit_fishing_permit.php?id=<?php echo $permit['id']; ?>" class="btn btn-warning" style="background-color:#ffc107; color:black; text-decoration:none;">Edit</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>Permit details could not be loaded.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
