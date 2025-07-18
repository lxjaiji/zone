<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

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
$conditions_data = [];

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);
    $sql = "SELECT lc.*, u.username as encoded_by_username
            FROM locational_clearances lc
            LEFT JOIN users u ON lc.encoded_by_user_id = u.id
            WHERE lc.id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $permit = mysqli_fetch_assoc($result);
                for ($i = 1; $i <= 10; $i++) {
                    $key = 'condition' . $i;
                    $db_key = $key . ( $i==1 ? '_monitoring' : ($i==2 ? '_non_compliance' : ($i==3 ? '_other_agencies' : ($i==4 ? '_activity_applied_for' : ($i==5 ? '_no_major_expansion' : ($i==6 ? '_not_cert_ownership' : ($i==7 ? '_misrepresentation' : ($i==8 ? '_commencement_period' : ($i==9 ? '_revoked' : '_provisional')))))))));
                    $conditions_data[$key] = $permit[$db_key] ?? 0;
                }
            }
        }
    }
}

if ($permit === null) { die("Error: Permit data could not be loaded."); }
mysqli_close($link);

function formatDatePrint($dateStr) {
    return empty($dateStr) || $dateStr == '0000-00-00' ? 'N/A' : date("F j, Y", strtotime($dateStr));
}

$condition_texts = [
    1 => "All Conditions stipulated herein form part of this Decision and are subject to monitoring.",
    2 => "Non-compliance therewith shall cause cancellation or legal action.",
    3 => "The applicable requirements of other agencies and applicable provision of existing laws shall be complied with.",
    4 => "No activity other than the applied for shall be conducted with the project site.",
    5 => "No major expansion, alteration and/or improvement shall be introduced without prior notice from this office.",
    6 => "This Decision shall not be construed as a certification of this office as to the ownership by the applicant of land subject of this decision.",
    7 => "Any misrepresentation. false statement, or allegations material to the issuance of this decision shall be sufficient cause for its revocation.",
    8 => "This Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of decision.",
    9 => "PROVISIONAL CLEARANCE ONLY.",
    10 => "The Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of issuance of this Decision."
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Locational Clearance - <?php echo htmlspecialchars($permit['clearance_number']); ?></title>
    <style>
        body { font-family: "Bookman Old Style", serif; font-size: 12pt; line-height: 1; color: #333; margin: 0.5in; }
        .print-container { width: 100%; margin: auto; }
        .header { text-align: center; }
        .header img { max-height: 80px; }
        h1, h2 { text-align: center; margin: 5px 0; }
        h1 { font-size: 16pt; }
        h2 { font-size: 14pt; margin-bottom: 20px; text-transform: uppercase; }
        .two-column-layout { display: flex; width: 100%; margin: 15px 0; border-collapse: collapse; border: 1px solid black; }
        .column { width: 50%; padding: 10px; }
        .column-left { border-right: 1px solid black; }
        .field-label { font-weight: bold; }
        .field-value { border-bottom: 1px solid #555; padding: 2px 5px; min-height: 1.2em; }
        .field-group { margin-bottom: 12px; }
        .decision-text { text-align: justify; margin-top: 15px; }
        .conditions-list { list-style-type: none; padding-left: 0; margin-top: 15px; font-size: 10pt; line-height: 1.2; }
        .conditions-list li { margin-bottom: 5px; }
        .footer-section { margin-top: 30px; }
        .signature-block { width: 300px; margin: 60px 0 0 auto; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-bottom: 5px; }
        .signature-name { font-weight: bold; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print();">
    <div class="print-container">
        <div class="header">
            <img src="<?php echo htmlspecialchars($app_settings['municipality_logo_path'] ?? ''); ?>" alt="Municipality Logo">
            <p>Republic of the Philippines</p>
            <p>Province of <?php echo htmlspecialchars($app_settings['province_name'] ?? '[Province]'); ?></p>
            <p>Municipality of <?php echo htmlspecialchars($app_settings['municipality_name'] ?? '[Municipality]'); ?></p>
            <h1>LOCATIONAL CLEARANCE</h1>
        </div>

        <div class="two-column-layout">
            <div class="column column-left">
                <div class="field-group"><div class="field-label">APPLICANT:</div><div class="field-value"><?php echo htmlspecialchars($permit['applicant_name']); ?></div></div>
                <div class="field-group"><div class="field-label">ADDRESS:</div><div class="field-value"><?php echo htmlspecialchars($permit['applicant_address']); ?></div></div>
                <div class="field-group"><div class="field-label">NAME OF PROJECT:</div><div class="field-value"><?php echo htmlspecialchars($permit['project_name']); ?></div></div>
                <div class="field-group"><div class="field-label">RIGHT OVER LAND:</div><div class="field-value"><?php echo htmlspecialchars($permit['right_over_land']); ?></div></div>
            </div>
            <div class="column">
                <div class="field-group"><div class="field-label">NAME OF DEVELOPER:</div><div class="field-value"><?php echo htmlspecialchars($permit['developer_name']); ?></div></div>
                <div class="field-group"><div class="field-label">ADDRESS:</div><div class="field-value"><?php echo htmlspecialchars($permit['developer_address']); ?></div></div>
                <div class="field-group"><div class="field-label">LOCATION:</div><div class="field-value"><?php echo htmlspecialchars($permit['location']); ?></div></div>
                <div style="display: flex; gap: 10px;">
                    <div class="field-group" style="flex: 1;"><div class="field-label">LAND AREA:</div><div class="field-value"><?php echo htmlspecialchars($permit['land_area']); ?></div></div>
                    <div class="field-group" style="flex: 1;"><div class="field-label">BUILDING AREA:</div><div class="field-value"><?php echo htmlspecialchars($permit['building_area']); ?></div></div>
                </div>
            </div>
        </div>

        <div class="decision-text">
            <strong>DECISION:</strong> <?php echo nl2br(htmlspecialchars($permit['decision'])); ?>
        </div>

        <div class="conditions-list">
            <p>The foregoing clearance is granted subject to the following conditions:</p>
            <ol style="padding-left: 20px;">
                <?php for($i = 1; $i <= 10; $i++): ?>
                    <?php if(!empty($condition_texts[$i])): ?>
                    <li><?php echo htmlspecialchars($condition_texts[$i]); ?></li>
                    <?php endif; ?>
                <?php endfor; ?>
            </ol>
        </div>

        <div class="footer-section">
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
