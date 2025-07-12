<?php
// Initialize the session
session_start();

// Check if the user is logged in. Both admin and regular users can print.
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ){
     $_SESSION['error'] = "You need to be logged in to print this page.";
     header("location: login.php");
     exit;
}

require_once "config.php";

$clearance = null;
$id = 0;
$conditions_data = [];

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    $sql = "SELECT * FROM locational_clearances WHERE id = ?"; // Fetches all fields

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $clearance = mysqli_fetch_assoc($result);
                // Populate conditions
                $conditions_data['condition1'] = $clearance['condition1_monitoring'];
                $conditions_data['condition2'] = $clearance['condition2_non_compliance'];
                $conditions_data['condition3'] = $clearance['condition3_other_agencies'];
                $conditions_data['condition4'] = $clearance['condition4_activity_applied_for'];
                $conditions_data['condition5'] = $clearance['condition5_no_major_expansion'];
                $conditions_data['condition6'] = $clearance['condition6_not_cert_ownership'];
                $conditions_data['condition7'] = $clearance['condition7_misrepresentation'];
                $conditions_data['condition8'] = $clearance['condition8_commencement_period'];
            } else {
                die("Error: No Locational Clearance found with ID $id.");
            }
        } else {
            die("Error: Database query failed for Locational Clearance.");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error: Database statement preparation failed for Locational Clearance.");
    }
    mysqli_close($link);
} else {
    die("Error: Invalid request. No ID specified for printing Locational Clearance.");
}

if ($clearance === null) {
    die("Error: Locational Clearance data could not be loaded.");
}

function formatDatePrint($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'N/A';
    return date("F j, Y", strtotime($dateStr));
}

$condition_texts_print = [
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
    <title>Print Locational Clearance - <?php echo htmlspecialchars($clearance['clearance_number']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.5; color: #333; font-size: 11pt; } /* Typical print font size */
        .print-container { width: 100%; max-width: 780px; /* Approx Letter/A4 width with margins */ margin: auto; padding: 15px; }
        h1, h2, h3 { text-align: center; margin-bottom: 15px; }
        h1 { font-size: 16pt; margin-bottom: 5px; }
        h2 { font-size: 14pt; margin-bottom: 10px; }
        h3 { font-size: 12pt; text-transform: uppercase; margin-top:0; }
        .header-section p { margin: 1px 0; font-size: 10pt; text-align: center; }
        .header-section { text-align: center; margin-bottom: 25px; }
        .content-section p, .content-section div { margin-bottom: 10px; }
        .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .details-table td, .details-table th { padding: 6px; border: 1px solid #777; text-align: left; vertical-align: top; }
        .details-table th { font-weight: bold; width: 30%; background-color: #f0f0f0; }
        .conditions-list { list-style-type: none; padding-left: 0; margin-top: 10px; }
        .conditions-list li { margin-bottom: 5px; display: flex; }
        .conditions-list .condition-marker { margin-right: 8px; font-weight: bold; } /* For ✓ or ✗ */
        .footer-section { margin-top: 30px; padding-top: 15px; /*border-top: 1px solid #555;*/ }
        .signature-block { margin-top: 50px; text-align: right; padding-right:30px;}
        .signature-line { border-top: 1px solid #000; width: 280px; margin: 40px 0 5px auto; }
        .signature-name { font-weight: bold; text-align: center; }
        .signature-title { text-align: center; font-size:10pt; }
        .important-note { margin-top: 25px; font-style: italic; font-size: 9pt; color: #444; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        @media print {
            body { margin: 0.5in; /* Standard print margin */ font-size: 10pt; }
            .print-container { border: none; box-shadow: none; width: 100%; max-width: 100%; padding: 0; }
            .no-print { display: none; }
            .details-table th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } /* Ensure background prints */
        }
    </style>
</head>
<body onload="window.print();">
    <div class="print-container">
        <div class="header-section">
            <!-- User should customize LGU details -->
            <p>Republic of the Philippines</p>
            <p>Province of [YOUR PROVINCE]</p>
            <p>Municipality/City of [YOUR MUNICIPALITY/CITY]</p>
            <p>OFFICE OF THE MUNICIPAL PLANNING AND DEVELOPMENT COORDINATOR / ZONING ADMINISTRATOR</p>
            <hr style="border-top: 1px solid #000; margin-top:10px; margin-bottom:10px;">
            <h2>LOCATIONAL CLEARANCE</h2>
        </div>

        <div class="content-section">
            <p class="text-right">LC No.: <span class="bold"><?php echo htmlspecialchars($clearance['clearance_number']); ?></span></p>
            <p class="text-right">Date Issued: <?php echo formatDatePrint($clearance['issue_date']); ?></p>

            <p>TO WHOM IT MAY CONCERN:</p>
            <p style="text-indent: 2em;">This Locational Clearance is hereby granted to <span class="bold"><?php echo htmlspecialchars($clearance['applicant_name']); ?></span> (Applicant) / <span class="bold"><?php echo htmlspecialchars($clearance['owner_name']); ?></span> (Owner) for the project described as follows, located at <?php echo htmlspecialchars($clearance['address']); ?>.</p>

            <table class="details-table">
                <tr>
                    <th>Project Type:</th>
                    <td><?php echo htmlspecialchars($clearance['project_type']); ?></td>
                </tr>
                <tr>
                    <th>Project Location:</th>
                    <td><?php echo nl2br(htmlspecialchars($clearance['project_location'])); ?></td>
                </tr>
                <tr>
                    <th>Purpose of Application:</th>
                    <td><?php echo nl2br(htmlspecialchars($clearance['purpose'] ?: 'N/A')); ?></td>
                </tr>
                 <tr>
                    <th>Land Use Classification (Per Zoning Ordinance):</th>
                    <td><?php echo htmlspecialchars($clearance['land_use_classification'] ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Tax Declaration No.:</th>
                    <td><?php echo htmlspecialchars($clearance['tax_declaration'] ?: 'N/A'); ?></td>
                </tr>
            </table>

            <p>This Clearance is issued based on the documents submitted and subject to the following conditions:</p>
            <ul class="conditions-list">
                <?php for($i = 1; $i <= 8; $i++): ?>
                <li>
                    <span class="condition-marker"><?php echo ($conditions_data['condition'.$i] == 1) ? '&#10003;' : '&nbsp; '; // Check or space ?></span> <!-- Using checkmark or space for visual cue -->
                    <?php echo $i . ". " . htmlspecialchars($condition_texts_print[$i]); ?>
                </li>
                <?php endfor; ?>
            </ul>

            <p>This Locational Clearance is valid until <span class="bold"><?php echo formatDatePrint($clearance['expiration_date']); ?></span>, unless sooner revoked for just cause or non-compliance with the conditions set forth.</p>

            <p>Official Receipt No.: <span class="bold"><?php echo htmlspecialchars($clearance['or_number']); ?></span></p>
            <p>Amount Paid: PHP <span class="bold"><?php echo number_format($clearance['fees_paid'], 2); ?></span></p>
            <p>Date Filed: <?php echo formatDatePrint($clearance['date_filed']); ?></p>
        </div>

        <div class="footer-section">
             <p>Issued this <?php echo date("jS", strtotime($clearance['issue_date'])); ?> day of <?php echo date("F, Y", strtotime($clearance['issue_date'])); ?> at [Your Municipality/City], [Your Province].</p>
            <div class="signature-block">
                <div class="signature-line"></div>
                <p class="signature-name text-center"><?php echo strtoupper(htmlspecialchars($clearance['signatory_name'] ?: '[SIGNATORY NAME]')); ?></p>
                <p class="signature-title text-center">MPDC / Zoning Administrator</p> <!-- User should customize title -->
            </div>
        </div>

        <div class="important-note">
            <p><strong>Note:</strong> This Locational Clearance is not a building permit. It is one of the requirements for the issuance of a Building Permit and Business Permit. Not valid without the official seal of the issuing office.</p>
        </div>
        <div class="no-print" style="margin-top:20px; text-align:center;">
            <button onclick="window.print();">Print Again</button>
            <button onclick="window.close();">Close</button>
        </div>
    </div>
</body>
</html>
