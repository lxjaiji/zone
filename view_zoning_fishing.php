<?php
// Initialize the session
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ){
     $_SESSION['error'] = "You need to be logged in to view this page.";
     header("location: login.php");
     exit;
}

require_once "config.php";
$link = get_db_connection();

$clearance = null;
$id = 0;

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    $sql = "SELECT zc.*, u.username as encoded_by_username
            FROM zoning_fishing_clearances zc
            LEFT JOIN users u ON zc.encoded_by_user_id = u.id
            WHERE zc.id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $clearance = mysqli_fetch_assoc($result);
            } else {
                $_SESSION['error'] = "No clearance found with ID: $id.";
                header("location: manage_zoning_fishing.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Oops! Something went wrong while fetching clearance data.";
            header("location: manage_zoning_fishing.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($link);
        header("location: manage_zoning_fishing.php");
        exit;
    }
    mysqli_close($link);
} else {
    $_SESSION['error'] = "Invalid request: No ID specified.";
    header("location: manage_zoning_fishing.php");
    exit;
}

if ($clearance === null) {
    // Should have been caught above, but as a fallback
    $_SESSION['error'] = "Clearance could not be loaded.";
    header("location: manage_zoning_fishing.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Zoning Fishing Structure/Gear Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 800px; margin: 20px auto; padding:20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top:20px;}
        .detail-item { padding: 10px; border: 1px solid #eee; border-radius: 4px; background-color: #f9f9f9; }
        .detail-item strong { display: block; color: #333; margin-bottom: 5px; }
        .detail-item span { color: #555; }
        .actions { margin-top: 30px; text-align: right; }
        .actions .btn { margin-left: 10px; }
        .btn-print { background-color: #17a2b8; border-color: #17a2b8; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
        .btn-edit { background-color: #ffc107; border-color: #ffc107; color:black; text-decoration:none; padding: 0.375rem 0.75rem;}
        .btn-back { background-color: #6c757d; border-color: #6c757d; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <?php
        // Determine if admin or regular user for navigation and controls
        $is_admin_view = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;
        ?>

        <div class="wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Zoning Fishing Structure/Gear Clearance Details</h2>
            </div>
            <hr>

            <div class="detail-grid">
                <div class="detail-item"><strong>Fishing Structure/Gear:</strong> <span><?php echo htmlspecialchars($clearance['fishing_structure_gear']); ?></span></div>
                <div class="detail-item"><strong>Owner:</strong> <span><?php echo htmlspecialchars($clearance['owner']); ?></span></div>
                <div class="detail-item full-width"><strong>Resident of:</strong> <span><?php echo nl2br(htmlspecialchars($clearance['resident_of'])); ?></span></div>
                <div class="detail-item full-width"><strong>Located in:</strong> <span><?php echo nl2br(htmlspecialchars($clearance['located_in'])); ?></span></div>

                <div class="detail-item"><strong>Date of Issuance:</strong> <span><?php echo date("F j, Y", strtotime($clearance['date_of_issuance'])); ?></span></div>
                <div class="detail-item"><strong>Signatory:</strong> <span><?php echo htmlspecialchars($clearance['signatory']); ?></span></div>

                <div class="detail-item"><strong>O.R. Number:</strong> <span><?php echo htmlspecialchars($clearance['or_number']); ?></span></div>
                <div class="detail-item"><strong>Amount Paid (PHP):</strong> <span><?php echo number_format($clearance['amount_paid'], 2); ?></span></div>
                <div class="detail-item"><strong>Date Paid:</strong> <span><?php echo date("F j, Y", strtotime($clearance['date_paid'])); ?></span></div>
                <div class="detail-item"><strong>Issued at:</strong> <span><?php echo htmlspecialchars($clearance['issued_at']); ?></span></div>

                <div class="detail-item"><strong>Encoded By:</strong> <span><?php echo htmlspecialchars($clearance['encoded_by_username'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Date Encoded:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($clearance['created_at'])); ?></span></div>
                <div class="detail-item"><strong>Last Updated:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($clearance['updated_at'])); ?></span></div>
            </div>

            <div class="actions">
                <?php if($is_admin_view): ?>
                    <a href="manage_zoning_fishing.php" class="btn btn-back">Back to Admin List</a>
                    <a href="edit_zoning_fishing.php?id=<?php echo $id; ?>" class="btn btn-edit">Edit</a>
                <?php else: ?>
                    <a href="manage_zoning_fishing_user.php" class="btn btn-back">Back to List</a>
                <?php endif; ?>
                <a href="print_zoning_fishing.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-print">Print Clearance</a>
            </div>
        </div>
    </div>
</body>
</html>
