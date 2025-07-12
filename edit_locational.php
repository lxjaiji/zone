<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";

// Define variables
$applicant_name = $owner_name = $address = $date_filed = $issue_date = "";
$clearance_number = $expiration_date = $tax_declaration = $project_type = "";
$project_location = $purpose = $land_use_classification = $fees_paid = $or_number = "";
$signatory_name = "";
$id = 0;

$conditions_db = []; // To store condition values from DB or POST
for ($i = 1; $i <= 8; $i++) {
    $conditions_db['condition' . $i] = 0;
}

$errors = [];

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = intval($_POST["id"]);

    // Validate core fields
    $applicant_name = trim($_POST["applicant_name"]);
    if(empty($applicant_name)) $errors["applicant_name"] = "Applicant's name is required.";
    // ... (add all other field validations similar to add_locational.php) ...
    $owner_name = trim($_POST["owner_name"]);
    if(empty($owner_name)) $errors["owner_name"] = "Owner's name is required.";
    $address = trim($_POST["address"]);
    if(empty($address)) $errors["address"] = "Address is required.";
    $date_filed = trim($_POST["date_filed"]);
    if(empty($date_filed)){
        $errors["date_filed"] = "Date filed is required.";
    } else {
        $issue_date = $date_filed;
        $expiration_date_obj = new DateTime($date_filed);
        $expiration_date_obj->add(new DateInterval('P1Y'));
        $expiration_date = $expiration_date_obj->format('Y-m-d');
    }
    $clearance_number = trim($_POST["clearance_number_display"]); // For display, not directly edited
    $tax_declaration = trim($_POST["tax_declaration"]);
    $project_type = trim($_POST["project_type"]);
    if(empty($project_type)) $errors["project_type"] = "Project type is required.";
    $project_location = trim($_POST["project_location"]);
    if(empty($project_location)) $errors["project_location"] = "Project location is required.";
    $purpose = trim($_POST["purpose"]);
    $land_use_classification = trim($_POST["land_use_classification"]);
    $fees_paid = trim($_POST["fees_paid"]);
    if(!is_numeric($fees_paid) || $fees_paid < 0) $errors["fees_paid"] = "Valid fees paid is required.";
    if(empty($fees_paid) && $fees_paid !=='0') $errors["fees_paid"] = "Fees paid is required.";
    $or_number = trim($_POST["or_number"]);
    if(empty($or_number)) $errors["or_number"] = "O.R. Number is required.";
    $signatory_name = trim($_POST["signatory_name"]);
    if(empty($signatory_name)) $errors["signatory_name"] = "Signatory name is required.";

    // Process conditions checkboxes
    for ($i = 1; $i <= 8; $i++) {
        $conditions_db['condition' . $i] = isset($_POST['condition' . $i]) ? 1 : 0;
    }

    if(empty($errors)){
        $sql = "UPDATE locational_clearances SET
                    applicant_name=?, owner_name=?, address=?, date_filed=?, issue_date=?, expiration_date=?,
                    tax_declaration=?, project_type=?, project_location=?, purpose=?, land_use_classification=?,
                    fees_paid=?, or_number=?, signatory_name=?, updated_at=CURRENT_TIMESTAMP,
                    condition1_monitoring=?, condition2_non_compliance=?, condition3_other_agencies=?,
                    condition4_activity_applied_for=?, condition5_no_major_expansion=?,
                    condition6_not_cert_ownership=?, condition7_misrepresentation=?, condition8_commencement_period=?
                WHERE id=?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssssssdsiiiiiiiiii",
                $applicant_name, $owner_name, $address, $date_filed, $issue_date, $expiration_date,
                $tax_declaration, $project_type, $project_location, $purpose, $land_use_classification,
                $fees_paid, $or_number, $signatory_name,
                $conditions_db['condition1'], $conditions_db['condition2'], $conditions_db['condition3'],
                $conditions_db['condition4'], $conditions_db['condition5'], $conditions_db['condition6'],
                $conditions_db['condition7'], $conditions_db['condition8'],
                $id
            );

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "Locational Clearance (".$clearance_number.") updated successfully!";
                header("location: manage_locational.php");
                exit;
            } else {
                $_SESSION['error'] = "Database update error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = "Database statement prep error: " . mysqli_error($link);
        }
    }
    // If errors, store them and data in session to repopulate form
    if(!empty($errors)) {
        $_SESSION['form_errors_lc_edit'] = $errors;
        $_SESSION['form_data_lc_edit'] = $_POST; // Keep submitted data
        header("location: edit_locational.php?id=" . $id); // Redirect back
        exit;
    }
    mysqli_close($link);

} else { // Not a POST request, so fetch data for editing
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);

        // Check for form data from a failed POST redirect
        if(isset($_SESSION['form_errors_lc_edit']) && isset($_SESSION['form_data_lc_edit']) && $_SESSION['form_data_lc_edit']['id'] == $id) {
            $errors = $_SESSION['form_errors_lc_edit'];
            $form_data = $_SESSION['form_data_lc_edit'];

            $applicant_name = $form_data['applicant_name'] ?? '';
            $owner_name = $form_data['owner_name'] ?? '';
            $address = $form_data['address'] ?? '';
            $date_filed = $form_data['date_filed'] ?? '';
            // issue_date and expiration_date derived from date_filed
            $clearance_number = $form_data['clearance_number_display'] ?? ''; // Get from hidden field
            $tax_declaration = $form_data['tax_declaration'] ?? '';
            $project_type = $form_data['project_type'] ?? '';
            $project_location = $form_data['project_location'] ?? '';
            $purpose = $form_data['purpose'] ?? '';
            $land_use_classification = $form_data['land_use_classification'] ?? '';
            $fees_paid = $form_data['fees_paid'] ?? '';
            $or_number = $form_data['or_number'] ?? '';
            $signatory_name = $form_data['signatory_name'] ?? '';
            for ($i = 1; $i <= 8; $i++) {
                $conditions_db['condition' . $i] = isset($form_data['condition' . $i]) ? 1 : 0;
            }
            unset($_SESSION['form_errors_lc_edit']);
            unset($_SESSION['form_data_lc_edit']);
        } else {
            // Fetch from DB
            $sql = "SELECT * FROM locational_clearances WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $param_id);
                $param_id = $id;

                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    if(mysqli_num_rows($result) == 1){
                        $row = mysqli_fetch_assoc($result);
                        $applicant_name = $row["applicant_name"];
                        $owner_name = $row["owner_name"];
                        $address = $row["address"];
                        $date_filed = $row["date_filed"];
                        $issue_date = $row["issue_date"]; // Will be recalculated on POST based on date_filed
                        $clearance_number = $row["clearance_number"];
                        $expiration_date = $row["expiration_date"]; // Will be recalculated
                        $tax_declaration = $row["tax_declaration"];
                        $project_type = $row["project_type"];
                        $project_location = $row["project_location"];
                        $purpose = $row["purpose"];
                        $land_use_classification = $row["land_use_classification"];
                        $fees_paid = $row["fees_paid"];
                        $or_number = $row["or_number"];
                        $signatory_name = $row["signatory_name"];
                        for ($i = 1; $i <= 8; $i++) {
                            $conditions_db['condition' . $i] = $row['condition' . $i . '_monitoring'] ?? ($row['condition' . $i] ?? 0); // Adjust field names if they differ in DB
                        }
                         // Correcting condition field names from schema
                        $conditions_db['condition1'] = $row['condition1_monitoring'];
                        $conditions_db['condition2'] = $row['condition2_non_compliance'];
                        $conditions_db['condition3'] = $row['condition3_other_agencies'];
                        $conditions_db['condition4'] = $row['condition4_activity_applied_for'];
                        $conditions_db['condition5'] = $row['condition5_no_major_expansion'];
                        $conditions_db['condition6'] = $row['condition6_not_cert_ownership'];
                        $conditions_db['condition7'] = $row['condition7_misrepresentation'];
                        $conditions_db['condition8'] = $row['condition8_commencement_period'];


                    } else {
                        $_SESSION['error'] = "No record found with ID: $id.";
                        header("location: manage_locational.php");
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "Error fetching data: " . mysqli_error($link);
                    header("location: manage_locational.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }
        // mysqli_close($link); // Keep open for the form display
    } else {
        $_SESSION['error'] = "Invalid request: No ID specified.";
        header("location: manage_locational.php");
        exit;
    }
}

$condition_texts = [
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
    <title>Edit Locational Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Re-use styles from add_locational.php if they are identical */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 0; }
        .full-width { grid-column: 1 / -1; }
        .wrapper { max-width: 900px; margin: 20px auto; padding:20px; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
        .conditions-fieldset { border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 4px; }
        .conditions-fieldset legend { font-weight: bold; padding: 0 10px; }
        .condition-item { margin-bottom: 10px; display: flex; align-items: flex-start; }
        .condition-item input[type="checkbox"] { margin-right: 10px; margin-top: 5px; }
        .condition-item label { flex: 1; }
        .info-field { background-color: #e9ecef; padding: .375rem .75rem; border-radius: .25rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="admin_dashboard.php">User Management</a>
            <a href="manage_zoning.php">Zoning Certificates</a>
            <a href="manage_locational.php">Locational Clearances</a>
            <a href="settings.php">Settings</a>
            <a href="logout.php" style="float:right; margin-right:20px;">Sign Out</a>
        </nav>

        <div class="wrapper">
            <h2>Edit Locational Clearance</h2>
            <p>Update details for Clearance No: <b><?php echo htmlspecialchars($clearance_number); ?></b></p>

            <?php
            if(!empty($_SESSION['error'])){
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            if(!empty($errors) && is_array($errors)){
                echo '<div class="alert alert-danger">';
                foreach($errors as $field_error){ echo htmlspecialchars($field_error) . '<br>'; }
                echo '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="clearance_number_display" value="<?php echo htmlspecialchars($clearance_number); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Applicant's Name</label>
                        <input type="text" name="applicant_name" class="form-control <?php echo (!empty($errors['applicant_name'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($applicant_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Owner's Name</label>
                        <input type="text" name="owner_name" class="form-control <?php echo (!empty($errors['owner_name'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($owner_name); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" class="form-control <?php echo (!empty($errors['address'])) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Date Filed</label>
                        <input type="date" name="date_filed" class="form-control <?php echo (!empty($errors['date_filed'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date_filed); ?>" required>
                         <small>Issue & Expiration date auto-update.</small>
                    </div>
                     <div class="form-group">
                        <label>Tax Declaration No.</label>
                        <input type="text" name="tax_declaration" class="form-control" value="<?php echo htmlspecialchars($tax_declaration); ?>">
                    </div>
                    <div class="form-group">
                        <label>Type of Project</label>
                        <input type="text" name="project_type" class="form-control <?php echo (!empty($errors['project_type'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($project_type); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Location of Project</label>
                        <textarea name="project_location" class="form-control <?php echo (!empty($errors['project_location'])) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($project_location); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Purpose</label>
                        <input type="text" name="purpose" class="form-control" value="<?php echo htmlspecialchars($purpose); ?>">
                    </div>
                    <div class="form-group">
                        <label>Land Use Classification</label>
                        <input type="text" name="land_use_classification" class="form-control" value="<?php echo htmlspecialchars($land_use_classification); ?>">
                    </div>
                    <div class="form-group">
                        <label>Fees Paid (PHP)</label>
                        <input type="number" step="0.01" name="fees_paid" class="form-control <?php echo (!empty($errors['fees_paid'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($fees_paid); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>O.R. Number</label>
                        <input type="text" name="or_number" class="form-control <?php echo (!empty($errors['or_number'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($or_number); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Signatory Name (e.g., MPDC/Zoning Admin)</label>
                        <input type="text" name="signatory_name" class="form-control <?php echo (!empty($errors['signatory_name'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($signatory_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Issue Date (Auto)</label>
                        <input type="text" class="form-control info-field" value="<?php echo htmlspecialchars( (new DateTime($date_filed ?: 'now'))->format('Y-m-d') ); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Expiration Date (Auto)</label>
                        <input type="text" class="form-control info-field" value="<?php echo htmlspecialchars( (new DateTime($date_filed ?: 'now'))->add(new DateInterval('P1Y'))->format('Y-m-d') ); ?>" readonly>
                    </div>
                </div>

                <fieldset class="conditions-fieldset full-width">
                    <legend>Conditions</legend>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div class="condition-item">
                        <input type="checkbox" name="condition<?php echo $i; ?>" id="condition<?php echo $i; ?>" value="1" <?php echo ($conditions_db['condition'.$i] == 1) ? 'checked' : ''; ?>>
                        <label for="condition<?php echo $i; ?>"><?php echo $i . ". " . htmlspecialchars($condition_texts[$i]); ?></label>
                    </div>
                    <?php endfor; ?>
                </fieldset>

                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Update Clearance">
                    <a href="manage_locational.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
