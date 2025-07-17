<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Define variables and initialize with empty values from DB or POST
$fishing_structure_gear = $owner = $resident_of = $located_in = $date_of_issuance = "";
$signatory = $or_number = $amount_paid = $date_paid = $issued_at = "";
$id = 0;

$errors = [];

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $id = intval($_POST["id"]);

    // Validate Fishing Structure/Gear
    $fishing_structure_gear = trim($_POST["fishing_structure_gear"]);
    if(empty($fishing_structure_gear)){
        $errors["fishing_structure_gear"] = "Please select a fishing structure/gear.";
    }

    // Validate Owner
    $owner = trim($_POST["owner"]);
    if(empty($owner)){ $errors["owner"] = "Please enter owner's name."; }

    // Validate Resident of
    $resident_of = trim($_POST["resident_of"]);
    if(empty($resident_of)){ $errors["resident_of"] = "Please enter the owner's residence."; }

    // Validate Located in
    $located_in = trim($_POST["located_in"]);
    if(empty($located_in)){ $errors["located_in"] = "Please enter the location of the structure/gear."; }

    // Validate Date of Issuance
    $date_of_issuance = trim($_POST["date_of_issuance"]);
    if(empty($date_of_issuance)){ $errors["date_of_issuance"] = "Please enter the date of issuance."; }

    // Validate Signatory
    $signatory = trim($_POST["signatory"]);
    if(empty($signatory)){ $errors["signatory"] = "Please enter the signatory."; }

    // Validate O.R. Number
    $or_number = trim($_POST["or_number"]);
    if(empty($or_number)){ $errors["or_number"] = "Please enter O.R. Number."; }

    // Validate Amount Paid
    $amount_paid = trim($_POST["amount_paid"]);
    if(!is_numeric($amount_paid) || $amount_paid < 0){ $errors["amount_paid"] = "Please enter a valid amount paid."; }
    if(empty($amount_paid) && $amount_paid !== '0'){ $errors["amount_paid"] = "Amount paid cannot be empty.";}

    // Validate Date Paid
    $date_paid = trim($_POST["date_paid"]);
    if(empty($date_paid)){ $errors["date_paid"] = "Please enter the date of payment."; }

    // Validate Issued at
    $issued_at = trim($_POST["issued_at"]);
    if(empty($issued_at)){ $errors["issued_at"] = "Please enter the place of issuance."; }


    if(empty($errors)){
        $sql = "UPDATE zoning_fishing_clearances SET fishing_structure_gear=?, owner=?, resident_of=?, located_in=?, date_of_issuance=?, signatory=?, or_number=?, amount_paid=?, date_paid=?, issued_at=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssdsssi",
                $fishing_structure_gear, $owner, $resident_of, $located_in, $date_of_issuance,
                $signatory, $or_number, $amount_paid, $date_paid, $issued_at, $id
            );

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "Zoning Fishing Structure/Gear Clearance updated successfully!";
                header("location: manage_zoning_fishing.php");
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
        header("location: edit_zoning_fishing.php?id=" . $id); // Redirect back to the edit form
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
            $fishing_structure_gear = $form_data['fishing_structure_gear'] ?? '';
            $owner = $form_data['owner'] ?? '';
            $resident_of = $form_data['resident_of'] ?? '';
            $located_in = $form_data['located_in'] ?? '';
            $date_of_issuance = $form_data['date_of_issuance'] ?? '';
            $signatory = $form_data['signatory'] ?? '';
            $or_number = $form_data['or_number'] ?? '';
            $amount_paid = $form_data['amount_paid'] ?? '';
            $date_paid = $form_data['date_paid'] ?? '';
            $issued_at = $form_data['issued_at'] ?? '';
            unset($_SESSION['form_data']);
        } else {
            // If not from a failed POST, fetch from DB
            $sql = "SELECT * FROM zoning_fishing_clearances WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $param_id);
                $param_id = $id;

                if(mysqli_stmt_execute($stmt)){
                    $result = mysqli_stmt_get_result($stmt);
                    if(mysqli_num_rows($result) == 1){
                        $row = mysqli_fetch_assoc($result);
                        $fishing_structure_gear = $row["fishing_structure_gear"];
                        $owner = $row["owner"];
                        $resident_of = $row["resident_of"];
                        $located_in = $row["located_in"];
                        $date_of_issuance = $row["date_of_issuance"];
                        $signatory = $row["signatory"];
                        $or_number = $row["or_number"];
                        $amount_paid = $row["amount_paid"];
                        $date_paid = $row["date_paid"];
                        $issued_at = $row["issued_at"];
                    } else{
                        $_SESSION['error'] = "No record found with that ID.";
                        header("location: manage_zoning_fishing.php");
                        exit;
                    }
                } else{
                    $_SESSION['error'] = "Oops! Something went wrong fetching data.";
                    header("location: manage_zoning_fishing.php");
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }
    } else{
        $_SESSION['error'] = "Invalid request. No ID specified.";
        header("location: manage_zoning_fishing.php");
        exit;
    }
}
$fishing_structure_gear_options = ['Taba', 'Bentahan', 'Fish Cage', 'Talabahan'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Zoning Fishing Structure/Gear Clearance</title>
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
        <?php include 'navigation.php'; ?>

        <div class="wrapper">
            <h2>Edit Zoning Fishing Structure/Gear Clearance</h2>

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

                <div class="form-grid">
                <div class="form-group">
                        <label>Fishing Structure/Gear</label>
                        <select name="fishing_structure_gear" class="form-control <?php echo (!empty($errors['fishing_structure_gear'])) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select Structure/Gear...</option>
                            <?php foreach($fishing_structure_gear_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($fishing_structure_gear == $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Owner</label>
                        <input type="text" name="owner" class="form-control <?php echo (!empty($errors['owner'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($owner); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Resident of</label>
                        <textarea name="resident_of" class="form-control <?php echo (!empty($errors['resident_of'])) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($resident_of); ?></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Located in</label>
                        <textarea name="located_in" class="form-control <?php echo (!empty($errors['located_in'])) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($located_in); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Date of Issuance</label>
                        <input type="date" name="date_of_issuance" class="form-control <?php echo (!empty($errors['date_of_issuance'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date_of_issuance); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Signatory</label>
                        <input type="text" name="signatory" class="form-control <?php echo (!empty($errors['signatory'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($signatory); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>O.R. Number</label>
                        <input type="text" name="or_number" class="form-control <?php echo (!empty($errors['or_number'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($or_number); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Amount Paid (PHP)</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control <?php echo (!empty($errors['amount_paid'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($amount_paid); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date Paid</label>
                        <input type="date" name="date_paid" class="form-control <?php echo (!empty($errors['date_paid'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date_paid); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Issued at</label>
                        <input type="text" name="issued_at" class="form-control <?php echo (!empty($errors['issued_at'])) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($issued_at); ?>" required>
                    </div>
                </div>
                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Update Clearance">
                    <a href="manage_zoning_fishing.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($link); ?>
