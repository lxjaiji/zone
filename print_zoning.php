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

$certificate = null;
$id = 0;

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    // Fetch certificate details, could also join with users table if 'encoded by' is needed on printout
    $sql = "SELECT * FROM zoning_certificates WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;

        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $certificate = mysqli_fetch_assoc($result);
            } else {
                die("Error: No certificate found with ID $id. Cannot generate print view.");
            }
        } else {
            die("Error: Database query failed. Cannot generate print view.");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Error: Database statement preparation failed. Cannot generate print view.");
    }
    mysqli_close($link);
} else {
    die("Error: Invalid request. No ID specified for printing.");
}

if ($certificate === null) {
    die("Error: Certificate data could not be loaded. Cannot generate print view.");
}

// Helper function for date formatting
function formatDate($dateStr) {
    if (empty($dateStr) || $dateStr == '0000-00-00') return 'N/A';
    return date("F j, Y", strtotime($dateStr));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Zoning Certificate - <?php echo htmlspecialchars($certificate['certificate_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        .print-container {
            width: 100%;
            max-width: 800px; /* Adjust as per typical paper size like A4 or Letter */
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        h1, h2, h3 { text-align: center; margin-bottom: 20px; }
        h1 { font-size: 1.8em; }
        h2 { font-size: 1.5em; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        td, th {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            width: 35%; /* Label column width */
            font-weight: bold;
        }
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-section .logo { /* Placeholder for a logo */
            max-height: 80px;
            margin-bottom: 10px;
        }
        .header-section p {
            margin: 2px 0;
        }
        .footer-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #000;
            text-align: center; /* Or specific signature lines */
        }
        .signature-area {
            margin-top: 60px;
            display: flex;
            justify-content: space-around; /* For multiple signatures */
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px; /* Adjust width as needed */
            margin: 0 auto; /* Center single line */
            margin-top: 5px; /* Space above name */
            text-align: center;
        }
        .signature-name {
            margin-top: 5px;
            font-weight: bold;
        }
        .important-note {
            margin-top: 30px;
            font-style: italic;
            font-size: 0.9em;
            color: #555;
        }

        @media print {
            body { margin: 0; /* Reset margins for printing */ }
            .print-container {
                border: none;
                box-shadow: none;
                width: 100%;
                max-width: 100%; /* Use full page width for printing */
                padding: 0;
            }
            .no-print { display: none; } /* For elements not to be printed */
        }
    </style>
</head>
<body onload="window.print();">
    <div class="print-container">
        <div class="header-section">
            <!-- <img src="path/to/your/logo.png" alt="Logo" class="logo"> -->
            <p>Republic of the Philippines</p>
            <p>Province of [Your Province]</p>
            <p>Municipality/City of [Your Municipality/City]</p>
            <h3>OFFICE OF THE ZONING ADMINISTRATOR</h3>
            <hr>
            <h1>ZONING CERTIFICATE</h1>
        </div>

        <p style="text-align:right;">Certificate No.: <strong><?php echo htmlspecialchars($certificate['certificate_number']); ?></strong></p>
        <p style="text-align:right;">Date Issued: <?php echo formatDate($certificate['issue_date']); ?></p>

        <p>TO WHOM IT MAY CONCERN:</p>
        <p style="text-indent: 2em;">This is to certify that the project described below, under the application of <strong><?php echo htmlspecialchars($certificate['applicant_name']); ?></strong> (Applicant) / <strong><?php echo htmlspecialchars($certificate['owner_name']); ?></strong> (Owner), with address at <?php echo htmlspecialchars($certificate['address']); ?>, has been found to be in accordance with the Zoning Ordinance of this [Municipality/City].</p>

        <table>
            <tr>
                <th>Type of Project:</th>
                <td><?php echo htmlspecialchars($certificate['project_type']); ?></td>
            </tr>
            <tr>
                <th>Location of Project:</th>
                <td><?php echo nl2br(htmlspecialchars($certificate['project_location'])); ?></td>
            </tr>
            <tr>
                <th>Purpose:</th>
                <td><?php echo nl2br(htmlspecialchars($certificate['purpose'] ?: 'N/A')); ?></td>
            </tr>
            <tr>
                <th>Zoning Classification:</th>
                <td><?php echo htmlspecialchars($certificate['zoning_classification']); ?></td>
            </tr>
            <tr>
                <th>Tax Declaration No.:</th>
                <td><?php echo htmlspecialchars($certificate['tax_declaration'] ?: 'N/A'); ?></td>
            </tr>
        </table>

        <p>This certification is issued for the purpose of <strong><?php echo htmlspecialchars($certificate['purpose'] ?: 'Securing Locational Clearance / Building Permit / Business Permit, etc.'); ?></strong> and is subject to the conditions stipulated in the Zoning Ordinance and other applicable laws, rules, and regulations.</p>

        <p>This Zoning Certificate is valid until <strong><?php echo formatDate($certificate['expiration_date']); ?></strong>, unless sooner revoked for cause.</p>

        <p>Paid under O.R. No.: <strong><?php echo htmlspecialchars($certificate['or_number']); ?></strong></p>
        <p>Amount Paid: PHP <strong><?php echo number_format($certificate['fees_paid'], 2); ?></strong></p>
        <p>Date Filed: <?php echo formatDate($certificate['date_filed']); ?></p>


        <div class="footer-section">
            <p>Issued this <?php echo date("jS", strtotime($certificate['issue_date'])); ?> day of <?php echo date("F, Y", strtotime($certificate['issue_date'])); ?> at [Your Municipality/City], [Your Province].</p>

            <div style="margin-top: 80px; text-align: right; padding-right: 50px;">
                 <div class="signature-line" style="margin: 0 0 0 auto;"></div>
                 <p class="signature-name" style="text-align:center;">[ZONING ADMINISTRATOR'S NAME]</p>
                 <p style="text-align:center;">Zoning Administrator</p>
            </div>
        </div>

        <div class="important-note">
            <p><strong>Note:</strong> This certificate does not exempt the applicant from complying with other requirements from other government agencies. Any alteration or erasure on this certificate renders it null and void.</p>
        </div>
        <button class="no-print" onclick="window.print();" style="margin-top:20px;">Print Again</button>
        <button class="no-print" onclick="window.close();" style="margin-top:20px;">Close</button>
    </div>
</body>
</html>
