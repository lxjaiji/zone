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

$certificate = null;
$id = 0;

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    $sql = "SELECT zc.*, u.username as encoded_by_username
            FROM zoning_certificates zc
            LEFT JOIN users u ON zc.encoded_by_user_id = u.id
            WHERE zc.id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $certificate = mysqli_fetch_assoc($result);
            } else {
                $_SESSION['error'] = "No certificate found with ID: $id.";
                header("location: manage_zoning.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Oops! Something went wrong while fetching certificate data.";
            header("location: manage_zoning.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($link);
        header("location: manage_zoning.php");
        exit;
    }
    mysqli_close($link);
} else {
    $_SESSION['error'] = "Invalid request: No ID specified.";
    header("location: manage_zoning.php");
    exit;
}

if ($certificate === null) {
    // Should have been caught above, but as a fallback
    $_SESSION['error'] = "Certificate could not be loaded.";
    header("location: manage_zoning.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Zoning Certificate - <?php echo htmlspecialchars($certificate['certificate_number']); ?></title>
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
                <h2>Zoning Certificate Details</h2>
                <span style="font-size: 1.2em; font-weight:bold;"><?php echo htmlspecialchars($certificate['certificate_number']); ?></span>
            </div>
            <hr>

            <div class="detail-grid">
                <div class="detail-item"><strong>Applicant's Name:</strong> <span><?php echo htmlspecialchars($certificate['applicant_name']); ?></span></div>
                <div class="detail-item"><strong>Owner's Name:</strong> <span><?php echo htmlspecialchars($certificate['owner_name']); ?></span></div>
                <div class="detail-item full-width"><strong>Address:</strong> <span><?php echo nl2br(htmlspecialchars($certificate['address'])); ?></span></div>

                <div class="detail-item"><strong>Date Filed:</strong> <span><?php echo date("F j, Y", strtotime($certificate['date_filed'])); ?></span></div>
                <div class="detail-item"><strong>Issue Date:</strong> <span><?php echo date("F j, Y", strtotime($certificate['issue_date'])); ?></span></div>
                <div class="detail-item"><strong>Expiration Date:</strong> <span><?php echo date("F j, Y", strtotime($certificate['expiration_date'])); ?></span></div>

                <div class="detail-item"><strong>Tax Declaration No.:</strong> <span><?php echo htmlspecialchars($certificate['tax_declaration'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Lot No.:</strong> <span><?php echo htmlspecialchars($certificate['lot_no'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Land Area (sqm):</strong> <span><?php echo htmlspecialchars($certificate['land_area'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Type of Project:</strong> <span><?php echo htmlspecialchars($certificate['project_type']); ?></span></div>
                <div class="detail-item full-width"><strong>Location of Project:</strong> <span><?php echo nl2br(htmlspecialchars($certificate['project_location'])); ?></span></div>

                <div class="detail-item full-width"><strong>Purpose:</strong> <span><?php echo nl2br(htmlspecialchars($certificate['purpose'] ?: 'N/A')); ?></span></div>
                <div class="detail-item"><strong>Zoning Classification:</strong> <span><?php echo htmlspecialchars($certificate['zoning_classification']); ?></span></div>

                <div class="detail-item"><strong>Fees Paid (PHP):</strong> <span><?php echo number_format($certificate['fees_paid'], 2); ?></span></div>
                <div class="detail-item"><strong>O.R. Number:</strong> <span><?php echo htmlspecialchars($certificate['or_number']); ?></span></div>

                <div class="detail-item"><strong>Encoded By:</strong> <span><?php echo htmlspecialchars($certificate['encoded_by_username'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Date Encoded:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($certificate['created_at'])); ?></span></div>
                <div class="detail-item"><strong>Last Updated:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($certificate['updated_at'])); ?></span></div>
            </div>

            <div class="actions">
                <?php if($is_admin_view): ?>
                    <a href="manage_zoning.php" class="btn btn-back">Back to Admin List</a>
                    <a href="edit_zoning.php?id=<?php echo $id; ?>" class="btn btn-edit">Edit</a>
                <?php else: ?>
                    <a href="manage_zoning_user.php" class="btn btn-back">Back to List</a>
                <?php endif; ?>
                <a href="print_zoning.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-print">Print Certificate</a>
            </div>
        </div>
    </div>
</body>
</html>
