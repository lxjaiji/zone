<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Function to fetch all settings
function get_all_settings($link) {
    $settings_data = [];
    $sql = "SELECT setting_key, setting_value FROM settings";
    if ($result = mysqli_query($link, $sql)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings_data[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings_data;
}

$app_settings = get_all_settings($link);
$permit = null;
$permit_id = 0;

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);
    $sql = "SELECT fp.*, u.username as encoded_by_username
            FROM fishing_gear_permits fp
            LEFT JOIN users u ON fp.encoded_by_user_id = u.id
            WHERE fp.id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $permit = mysqli_fetch_assoc($result);
            }
        }
    }
}

if ($permit === null) {
    die("Error: Permit data could not be loaded.");
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Fishing Permit</title>
    <style>
        body { font-family: "Bookman Old Style", serif; font-size: 12pt; line-height: 1.2; }
        .print-container { width: 100%; max-width: 800px; margin: auto; padding: 20px; }
        .header { text-align: center; }
        .header img { max-height: 80px; }
        h1, h2 { text-align: center; margin: 5px 0; }
        h1 { font-size: 16pt; }
        h2 { font-size: 14pt; margin-bottom: 20px; }
        .content p { margin: 5px 0; }
        .content .field { display: flex; margin: 10px 0; }
        .content .field .label { font-weight: bold; min-width: 150px; }
        .footer { margin-top: 50px; }
        .signature-block {
             width: 300px;
             margin: 80px 0 0 auto; /* Aligned to the right */
             text-align: center;
        }
        .signature-line { border-top: 1px solid #000; margin-bottom: 5px; }
        .signature-name { font-weight: bold; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="print-container">
        <div class="header">
            <img src="<?php echo htmlspecialchars($app_settings['municipality_logo_path'] ?? ''); ?>" alt="Municipality Logo">
            <p>Republic of the Philippines</p>
            <p>Province of <?php echo htmlspecialchars($app_settings['province_name'] ?? '[Province]'); ?></p>
            <p>Municipality of <?php echo htmlspecialchars($app_settings['municipality_name'] ?? '[Municipality]'); ?></p>
            <h1>Zoning Permit</h1>
            <h2>FISHING STRUCTURE / GEAR</h2>
        </div>

        <div class="content">
            <div class="field"><div class="label">Structure/Gear:</div><div><?php echo htmlspecialchars($permit['gear_type']); ?></div></div>
            <div class="field"><div class="label">Owner:</div><div><?php echo htmlspecialchars($permit['owner_name']); ?></div></div>
            <div class="field"><div class="label">Resident of:</div><div><?php echo htmlspecialchars($permit['owner_resident_of']); ?></div></div>
            <div class="field"><div class="label">Located in:</div><div><?php echo htmlspecialchars($permit['location']); ?></div></div>
            <div class="field"><div class="label">Date of Issuance:</div><div><?php echo date("F j, Y", strtotime($permit['issue_date'])); ?></div></div>
            <hr>
            <div class="field"><div class="label">O.R. No.:</div><div><?php echo htmlspecialchars($permit['or_number']); ?></div></div>
            <div class="field"><div class="label">Amount Paid:</div><div><?php echo number_format($permit['amount_paid'], 2); ?></div></div>
            <div class="field"><div class="label">Date:</div><div><?php echo date("F j, Y", strtotime($permit['date_paid'])); ?></div></div>
            <div class="field"><div class="label">Issued at:</div><div><?php echo htmlspecialchars($permit['issued_at']); ?></div></div>
            <div class="field"><div class="label">Encoded By:</div><div><?php echo htmlspecialchars($permit['encoded_by_username']); ?></div></div>
        </div>

        <div class="footer">
            <div class="signature-block">
                <div class="signature-line"></div>
                <p class="signature-name"><?php echo strtoupper(htmlspecialchars($app_settings['default_signatory_name'] ?? '[SIGNATORY NAME]')); ?></p>
            </div>
        </div>

         <div class="no-print" style="margin-top:20px; text-align:center;">
            <button onclick="window.print();">Print Again</button>
            <button onclick="window.close();">Close</button>
        </div>
    </div>
</body>
</html>
