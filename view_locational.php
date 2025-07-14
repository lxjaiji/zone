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
$conditions_from_db = [];

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    $sql = "SELECT lc.*, u.username as encoded_by_username
            FROM locational_clearances lc
            LEFT JOIN users u ON lc.encoded_by_user_id = u.id
            WHERE lc.id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $clearance = mysqli_fetch_assoc($result);
                // Populate conditions
                $conditions_from_db['condition1'] = $clearance['condition1_monitoring'];
                $conditions_from_db['condition2'] = $clearance['condition2_non_compliance'];
                $conditions_from_db['condition3'] = $clearance['condition3_other_agencies'];
                $conditions_from_db['condition4'] = $clearance['condition4_activity_applied_for'];
                $conditions_from_db['condition5'] = $clearance['condition5_no_major_expansion'];
                $conditions_from_db['condition6'] = $clearance['condition6_not_cert_ownership'];
                $conditions_from_db['condition7'] = $clearance['condition7_misrepresentation'];
                $conditions_from_db['condition8'] = $clearance['condition8_commencement_period'];
            } else {
                $_SESSION['error'] = "No locational clearance found with ID: $id.";
                header("location: manage_locational.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Database error fetching clearance: " . mysqli_error($link);
            header("location: manage_locational.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Database statement prep error: " . mysqli_error($link);
        header("location: manage_locational.php");
        exit;
    }
    mysqli_close($link);
} else {
    $_SESSION['error'] = "Invalid request: No ID specified.";
    header("location: manage_locational.php");
    exit;
}

if ($clearance === null) {
    $_SESSION['error'] = "Locational Clearance data could not be loaded.";
    header("location: manage_locational.php");
    exit;
}

$condition_texts_view = [
    1 => "All Conditions stipulated herein form part of this Decision and are subject to monitoring.",
    2 => "Non-compliance therewith shall cause cancellation or legal action.",
    3 => "The applicable requirements of other agencies and applicable provision of existing laws shall be complied with.",
    4 => "No activity other than the applied for shall be conducted with the project site.",
    5 => "No major expansion, alteration and/or improvement shall be introduced without prior notice from this office.",
    6 => "This Decision shall not be construed as a certification of this office as to the ownership by the applicant of land subject of this decision.",
    7 => "Any misrepresentation. false statement, or allegations material to the issuance of this decision shall be sufficient cause for its revocation.",
    8 => "This Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of issuance of this Decision."
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Locational Clearance - <?php echo htmlspecialchars($clearance['clearance_number']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Reuse styles from view_zoning.php or define new ones */
        .wrapper { max-width: 900px; margin: 20px auto; padding:20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 15px; margin-top:20px;}
        .detail-item { padding: 10px; border: 1px solid #eee; border-radius: 4px; background-color: #f9f9f9; }
        .detail-item strong { display: block; color: #333; margin-bottom: 5px; }
        .detail-item span, .detail-item div { color: #555; }
        .actions { margin-top: 30px; text-align: right; }
        .actions .btn { margin-left: 10px; }
        .btn-print { background-color: #17a2b8; border-color: #17a2b8; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
        .btn-edit { background-color: #ffc107; border-color: #ffc107; color:black; text-decoration:none; padding: 0.375rem 0.75rem;}
        .btn-back { background-color: #6c757d; border-color: #6c757d; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
        .conditions-view-list { list-style-type: none; padding-left: 0; }
        .conditions-view-list li { margin-bottom: 8px; display: flex; align-items: flex-start; }
        .conditions-view-list .status-icon { margin-right: 8px; font-size: 1.2em; }
        .status-yes { color: green; }
        .status-no { color: red; }
        .full-width-grid { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <?php
        $is_admin_view_lc = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;
        ?>

        <div class="wrapper">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h2>Locational Clearance Details</h2>
                <span style="font-size: 1.2em; font-weight:bold;"><?php echo htmlspecialchars($clearance['clearance_number']); ?></span>
            </div>
            <hr>

            <div class="detail-grid">
                <div class="detail-item"><strong>Applicant's Name:</strong> <span><?php echo htmlspecialchars($clearance['applicant_name']); ?></span></div>
                <div class="detail-item"><strong>Owner's Name:</strong> <span><?php echo htmlspecialchars($clearance['owner_name']); ?></span></div>
                <div class="detail-item full-width-grid"><strong>Address:</strong> <span><?php echo nl2br(htmlspecialchars($clearance['address'])); ?></span></div>

                <div class="detail-item"><strong>Date Filed:</strong> <span><?php echo date("F j, Y", strtotime($clearance['date_filed'])); ?></span></div>
                <div class="detail-item"><strong>Issue Date:</strong> <span><?php echo date("F j, Y", strtotime($clearance['issue_date'])); ?></span></div>
                <div class="detail-item"><strong>Expiration Date:</strong> <span><?php echo date("F j, Y", strtotime($clearance['expiration_date'])); ?></span></div>

                <div class="detail-item"><strong>Tax Declaration No.:</strong> <span><?php echo htmlspecialchars($clearance['tax_declaration'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Type of Project:</strong> <span><?php echo htmlspecialchars($clearance['project_type']); ?></span></div>
                <div class="detail-item full-width-grid"><strong>Location of Project:</strong> <span><?php echo nl2br(htmlspecialchars($clearance['project_location'])); ?></span></div>

                <div class="detail-item"><strong>Purpose:</strong> <span><?php echo nl2br(htmlspecialchars($clearance['purpose'] ?: 'N/A')); ?></span></div>
                <div class="detail-item"><strong>Land Use Classification:</strong> <span><?php echo htmlspecialchars($clearance['land_use_classification'] ?: 'N/A'); ?></span></div>

                <div class="detail-item"><strong>Fees Paid (PHP):</strong> <span><?php echo number_format($clearance['fees_paid'], 2); ?></span></div>
                <div class="detail-item"><strong>O.R. Number:</strong> <span><?php echo htmlspecialchars($clearance['or_number']); ?></span></div>

                <div class="detail-item"><strong>Encoded By:</strong> <span><?php echo htmlspecialchars($clearance['encoded_by_username'] ?: 'N/A'); ?></span></div>
                <div class="detail-item"><strong>Date Encoded:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($clearance['created_at'])); ?></span></div>
                <div class="detail-item"><strong>Last Updated:</strong> <span><?php echo date("F j, Y, g:i a", strtotime($clearance['updated_at'])); ?></span></div>
            </div>

            <div class="detail-item full-width-grid" style="margin-top:20px;">
                <strong>Conditions:</strong>
                <ul class="conditions-view-list">
                    <?php for($i = 1; $i <= 8; $i++): ?>
                    <li>
                        <?php if($conditions_from_db['condition'.$i] == 1): ?>
                            <span class="status-icon status-yes">&#10004;</span> <!-- Check mark -->
                        <?php else: ?>
                            <span class="status-icon status-no">&#10008;</span> <!-- X mark -->
                        <?php endif; ?>
                        <div><?php echo $i . ". " . htmlspecialchars($condition_texts_view[$i]); ?></div>
                    </li>
                    <?php endfor; ?>
                </ul>
            </div>


            <div class="actions">
                <?php if($is_admin_view_lc): ?>
                    <a href="manage_locational.php" class="btn btn-back">Back to Admin List</a>
                    <a href="edit_locational.php?id=<?php echo $id; ?>" class="btn btn-edit">Edit</a>
                <?php else: ?>
                     <a href="manage_locational_user.php" class="btn btn-back">Back to List</a>
                <?php endif; ?>
                <a href="print_locational.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-print">Print Clearance</a>
            </div>
        </div>
    </div>
</body>
</html>
