<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";

// Define variables and initialize with empty values from DB or POST
$applicant_name = $owner_name = $address = $date_filed = $issue_date = "";
$certificate_number = $expiration_date = $tax_declaration = $project_type = "";
$project_location = $purpose = $zoning_classification = $fees_paid = $or_number = "";
$id = 0;

$errors = [];

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = intval($_POST["id"]);

    // Validate Applicant Name
    $applicant_name = trim($_POST["applicant_name"]);
    if(empty($applicant_name)){ $errors["applicant_name"] = "Please enter applicant's name."; }

    // Validate Owner Name
    $owner_name = trim($_POST["owner_name"]);
    if(empty($owner_name)){ $errors["owner_name"] = "Please enter owner's name."; }

    // Validate Address
    $address = trim($_POST["address"]);
    if(empty($address)){ $errors["address"] = "Please enter address."; }

    // Validate Date Filed
    $date_filed = trim($_POST["date_filed"]);
    if(empty($date_filed)){
        $errors["date_filed"] = "Please enter date filed.";
    } else {
        $issue_date = $date_filed; // Issue date is same as date filed
        $expiration_date_obj = new DateTime($date_filed);
        $expiration_date_obj->add(new DateInterval('P1Y')); // Add 1 year
        $expiration_date = $expiration_date_obj->format('Y-m-d');
    }

    // Certificate number is not editable directly but fetched for reference
    $certificate_number = trim($_POST["certificate_number"]);

    // Tax Declaration (Optional)
    $tax_declaration = trim($_POST["tax_declaration"]);

    // Validate Project Type
    $project_type = trim($_POST["project_type"]);
    if(empty($project_type)){ $errors["project_type"] = "Please enter project type."; }

    // Validate Project Location
    $project_location = trim($_POST["project_location"]);
    if(empty($project_location)){ $errors["project_location"] = "Please enter project location."; }

    // Purpose (Optional)
    $purpose = trim($_POST["purpose"]);

    // Validate Zoning Classification
    $zoning_classification = trim($_POST["zoning_classification"]);
    if(empty($zoning_classification)){ $errors["zoning_classification"] = "Please select zoning classification."; }

    // Validate Fees Paid
    $fees_paid = trim($_POST["fees_paid"]);
    if(!is_numeric($fees_paid) || $fees_paid < 0){ $errors["fees_paid"] = "Please enter a valid amount for fees paid."; }
    if(empty($fees_paid) && $fees_paid !== '0'){ $errors["fees_paid"] = "Fees paid cannot be empty.";}


    // Validate O.R. Number
    $or_number = trim($_POST["or_number"]);
    if(empty($or_number)){ $errors["or_number"] = "Please enter O.R. Number."; }

    if(empty($errors)){
        $sql = "UPDATE zoning_certificates SET applicant_name=?, owner_name=?, address=?, date_filed=?, issue_date=?, expiration_date=?, tax_declaration=?, project_type=?, project_location=?, purpose=?, zoning_classification=?, fees_paid=?, or_number=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssssssssdssi",
                $applicant_name, $owner_name, $address, $date_filed, $issue_date,
                $expiration_date, $tax_declaration, $project_type,
                $project_location, $purpose, $zoning_classification, $fees_paid,
                $or_number, $id
            );

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "Zoning Certificate (".$certificate_number.") updated successfully!";
                header("location: manage_zoning.php");
                exit;
            } else {
                $_SESSION['error'] = "Oops! Something went wrong. Please try again later. Error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = "Error preparing update statement: " . mysqli_error($link);
        }
    }
    // If there were errors, they will be displayed on the form. Store in session to show after redirect.
    if(!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Keep submitted data
        header("location: edit_zoning.php?id=" . $id); // Redirect back to the edit form
        exit;
    }
    mysqli_close($link);

} else {
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);

        // Retrieve errors and form data from session if redirected from POST's error handling
        if(isset($_SESSION['form_errors'])){
            $errors = $_SESSION['form_errors'];
            unset($_SESSION['form_errors']);
        }
        if(isset($_SESSION['form_data']) && $_SESSION['form_data']['id'] == $id){ // Ensure data is for current ID
            $form_data = $_SESSION['form_data'];
            $applicant_name = $form_data['applicant_name'] ?? '';
            $owner_name = $form_data['owner_name'] ?? '';
            $address = $form_data['address'] ?? '';
            $date_filed = $form_data['date_filed'] ?? '';
            // issue_date and expiration_date are derived from date_filed
            $certificate_number = $form_data['certificate_number'] ?? ''; // Get from hidden field
            $tax_declaration = $form_data['tax_declaration'] ?? '';
            $project_type = $form_data['project_type'] ?? '';
            $project_location = $form_data['project_location'] ?? '';
            $purpose = $form_data['purpose'] ?? '';
            $zoning_classification = $form_data['zoning_classification'] ?? '';
            $fees_paid = $form_data['fees_paid'] ?? '';
            $or_number = $form_data['or_number'] ?? '';
            unset($_SESSION['form_data']);
        } else {
            // If not from a failed POST, fetch from DB
            $sql = "SELECT * FROM zoning_certificates WHERE id = ?";
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
                        $issue_date = $row["issue_date"];
                        $certificate_number = $row["certificate_number"];
                        $expiration_date = $row["expiration_date"];
                        $tax_declaration = $row["tax_declaration"];
                        $project_type = $row["project_type"];
                        $project_location = $row["project_location"];
                        $purpose = $row["purpose"];
                        $zoning_classification = $row["zoning_classification"];
                        $fees_paid = $row["fees_paid"];
                        $or_number = $row["or_number"];
                    } else{
                        $_SESSION['error'] = "No record found with that ID.";
                        header("location: manage_zoning.php");
                        exit;
                    }
                } else{
                    $_SESSION['error'] = "Oops! Something went wrong fetching data.";
                    header("location: manage_zoning.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }
        // mysqli_close($link); // Don't close if form needs to be displayed
    } else{
        $_SESSION['error'] = "Invalid request. No ID specified.";
        header("location: manage_zoning.php");
        exit;
    }
}
$zoning_classifications_enum = ['Residential', 'Commercial', 'Agro-Industrial', 'Agricultural', 'Institutional'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Zoning Certificate</title>
    <link rel="stylesheet" href="style.css">
     <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 0; }
        .full-width { grid-column: 1 / -1; }
        .wrapper { max-width: 800px; margin: 20px auto; padding:20px; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
        .info-field { background-color: #e9ecef; padding: .375rem .75rem; border-radius: .25rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="admin_dashboard.php">User Management</a>
            <a href="manage_zoning.php">Zoning Certificates</a>
            <!-- <a href="manage_locational.php">Locational Clearances</a> -->
            <a href="logout.php" style="float:right; margin-right:20px;">Sign Out</a>
        </nav>

        <div class="wrapper">
            <h2>Edit Zoning Certificate</h2>
            <p>Update the details for certificate number: <b><?php echo htmlspecialchars($certificate_number); ?></b></p>

            <?php
            if(!empty($_SESSION['error'])){ // Display general errors from POST or GET
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            // Display specific form validation errors if any
            if(!empty($errors) && is_array($errors)){
                echo '<div class="alert alert-danger">';
                foreach($errors as $field_error){
                    echo htmlspecialchars($field_error) . '<br>';
                }
                echo '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                <input type="hidden" name="certificate_number" value="<?php echo htmlspecialchars($certificate_number); ?>"/>

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
                        <small>Issue date and Expiration date will be updated based on this.</small>
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
                    <div class="form-group full-width">
                        <label>Purpose</label>
                        <input type="text" name="purpose" class="form-control" value="<?php echo htmlspecialchars($purpose); ?>">
                    </div>
                    <div class="form-group">
                        <label>Zoning Classification</label>
                        <select name="zoning_classification" class="form-control <?php echo (!empty($errors['zoning_classification'])) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select Classification...</option>
                            <?php foreach($zoning_classifications_enum as $zc): ?>
                            <option value="<?php echo $zc; ?>" <?php echo ($zoning_classification == $zc) ? 'selected' : ''; ?>><?php echo $zc; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <label>Issue Date (Auto-updated)</label>
                        <input type="text" class="form-control info-field" value="<?php echo htmlspecialchars($issue_date); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Expiration Date (Auto-updated)</label>
                        <input type="text" class="form-control info-field" value="<?php echo htmlspecialchars($expiration_date); ?>" readonly>
                    </div>
                </div>
                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Update Certificate">
                    <a href="manage_zoning.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
