<?php
// Initialize the session
session_start();

// Check if the user is logged in. Any logged-in user can add a certificate.
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    // If not logged in, redirect to login page
    $_SESSION['error'] = "You must be logged in to encode a certificate.";
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Define variables and initialize with empty values
$applicant_name = $owner_name = $address = $date_filed = $issue_date = "";
$certificate_number = $expiration_date = $tax_declaration = $project_type = "";
$project_location = $purpose = $zoning_classification = $fees_paid = $or_number = "";
$encoded_by_user_id = $_SESSION["id"];

$encoded_by_user_id = $_SESSION["id"]; // Get admin user ID from session

$errors = [];

// Function to generate the next certificate number
function generateCertificateNumber($link) {
    $current_year = date("Y");
    $sql = "SELECT certificate_number FROM zoning_certificates WHERE certificate_number LIKE ? ORDER BY certificate_number DESC LIMIT 1";
    $prefix = "ZC-" . $current_year . "-";

    if($stmt = mysqli_prepare($link, $sql)){
        $param_prefix_like = $prefix . "%";
        mysqli_stmt_bind_param($stmt, "s", $param_prefix_like);

        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0){
                mysqli_stmt_bind_result($stmt, $last_cert_no);
                mysqli_stmt_fetch($stmt);
                $last_no = intval(str_replace($prefix, "", $last_cert_no));
                $next_no = $last_no + 1;
            } else {
                $next_no = 1;
            }
        } else {
            // Error, default to 1, or handle more gracefully
            $next_no = 1;
        }
        mysqli_stmt_close($stmt);
        return $prefix . str_pad($next_no, 3, "0", STR_PAD_LEFT);
    }
    return $prefix . "001"; // Fallback
}


if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate Applicant Name
    $applicant_name = trim($_POST["applicant_name"]);
    if(empty($applicant_name)){
        $errors["applicant_name"] = "Please enter applicant's name.";
    }

    // Validate Owner Name
    $owner_name = trim($_POST["owner_name"]);
    if(empty($owner_name)){
        $errors["owner_name"] = "Please enter owner's name.";
    }

    // Validate Address
    $address = trim($_POST["address"]);
    if(empty($address)){
        $errors["address"] = "Please enter address.";
    }

    // Validate Date Filed
    $date_filed = trim($_POST["date_filed"]);
    if(empty($date_filed)){
        $errors["date_filed"] = "Please enter date filed.";
    } else {
        // Calculate Issue Date and Expiration Date
        $issue_date = $date_filed;
        $expiration_date_obj = new DateTime($date_filed);
        $expiration_date_obj->add(new DateInterval('P1Y')); // Add 1 year
        $expiration_date = $expiration_date_obj->format('Y-m-d');
    }

    // Tax Declaration (Optional)
    $tax_declaration = trim($_POST["tax_declaration"]);

    // Validate Project Type
    $project_type = trim($_POST["project_type"]);
    if(empty($project_type)){
        $errors["project_type"] = "Please enter project type.";
    }

    // Validate Project Location
    $project_location = trim($_POST["project_location"]);
    if(empty($project_location)){
        $errors["project_location"] = "Please enter project location.";
    }

    // Purpose (Optional)
    $purpose = trim($_POST["purpose"]);

    // Validate Zoning Classification
    $zoning_classification = trim($_POST["zoning_classification"]);
    if(empty($zoning_classification)){
        $errors["zoning_classification"] = "Please select zoning classification.";
    }

    // Validate Fees Paid
    $fees_paid = trim($_POST["fees_paid"]);
    if(empty($fees_paid) || !is_numeric($fees_paid) || $fees_paid < 0){
        $errors["fees_paid"] = "Please enter a valid amount for fees paid.";
    }

    // Validate O.R. Number
    $or_number = trim($_POST["or_number"]);
    if(empty($or_number)){
        $errors["or_number"] = "Please enter O.R. Number.";
    }

    // If no validation errors, proceed to generate certificate number and insert
    if(empty($errors)){
        $certificate_number = generateCertificateNumber($link);

        $sql = "INSERT INTO zoning_certificates (applicant_name, owner_name, address, date_filed, issue_date, certificate_number, expiration_date, tax_declaration, project_type, project_location, purpose, zoning_classification, fees_paid, or_number, encoded_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssssssdsi",
                $applicant_name, $owner_name, $address, $date_filed, $issue_date,
                $certificate_number, $expiration_date, $tax_declaration, $project_type,
                $project_location, $purpose, $zoning_classification, $fees_paid,
                $or_number, $encoded_by_user_id
            );

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "Zoning Certificate (".$certificate_number.") added successfully!";
                // Redirect based on user role
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                    header("location: manage_zoning.php");
                } else {
                    header("location: manage_zoning_user.php");
                }
                exit;
            } else {
                $_SESSION['error'] = "Database Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
             $_SESSION['error'] = "Error preparing statement: " . mysqli_error($link);
        }
    }
    // If there were errors, they will be displayed on the form
    if(!empty($errors) && empty($_SESSION['error'])) { // Avoid overwriting DB error
        $_SESSION['form_errors'] = $errors; // Store errors in session to display on form
        // Store submitted values in session to repopulate form
        $_SESSION['form_data'] = $_POST;
        header("location: add_zoning.php"); // Redirect back to the form
        exit;
    }


    mysqli_close($link);
} else {
    // Retrieve errors and form data from session if redirected from POST
    if(isset($_SESSION['form_errors'])){
        $errors = $_SESSION['form_errors'];
        unset($_SESSION['form_errors']);
    }
    if(isset($_SESSION['form_data'])){
        $form_data = $_SESSION['form_data'];
        // Repopulate variables from session data
        $applicant_name = $form_data['applicant_name'] ?? '';
        $owner_name = $form_data['owner_name'] ?? '';
        $address = $form_data['address'] ?? '';
        $date_filed = $form_data['date_filed'] ?? '';
        $tax_declaration = $form_data['tax_declaration'] ?? '';
        $project_type = $form_data['project_type'] ?? '';
        $project_location = $form_data['project_location'] ?? '';
        $purpose = $form_data['purpose'] ?? '';
        $zoning_classification = $form_data['zoning_classification'] ?? '';
        $fees_paid = $form_data['fees_paid'] ?? '';
        $or_number = $form_data['or_number'] ?? '';
        $signatory_name = $form_data['signatory_name'] ?? $signatory_name; // Keep pre-filled if available
        unset($_SESSION['form_data']);
    }
}
$zoning_classifications = ['Residential', 'Commercial', 'Agro-Industrial', 'Agricultural', 'Institutional'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Zoning Certificate</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 0; } /* Remove default margin if using grid gap */
        .full-width { grid-column: 1 / -1; }
        .wrapper { max-width: 800px; margin: 20px auto; padding:20px; }
        .btn-secondary { background-color: #6c757d; border-color: #6c757d; color:white; text-decoration:none; padding: 0.375rem 0.75rem;}
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>

        <div class="wrapper">
            <h2>Add New Zoning Certificate</h2>
            <p>Please fill this form to add a new zoning certificate.</p>

            <?php
            if(!empty($_SESSION['error'])){ // Display general errors
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
                            <?php foreach($zoning_classifications as $zc): ?>
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
                </div>
                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="<?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ? 'manage_zoning.php' : 'manage_zoning_user.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
