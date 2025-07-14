<?php
// Initialize the session
session_start();

// Check if the user is logged in. Any logged-in user can add a clearance.
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    // If not logged in, redirect to login page
    $_SESSION['error'] = "You must be logged in to encode a clearance.";
    header("location: login.php");
    exit;
}

require_once "config.php";

// Define variables and initialize
$applicant_name = $owner_name = $address = $date_filed = $issue_date = "";
$clearance_number = $expiration_date = $tax_declaration = $project_type = "";
$project_location = $purpose = $land_use_classification = $fees_paid = $or_number = "";
$signatory_name = "";
$encoded_by_user_id = $_SESSION["id"];

// Initialize conditions (all default to false, which is 0 for TINYINT/BOOLEAN in MySQL)
$conditions = [];
for ($i = 1; $i <= 8; $i++) {
    $conditions['condition' . $i] = 0;
}

$errors = [];

// Function to generate the next clearance number (LC-YYYY-NNN)
function generateClearanceNumber($link) {
    $current_year = date("Y");
    $sql = "SELECT clearance_number FROM locational_clearances WHERE clearance_number LIKE ? ORDER BY clearance_number DESC LIMIT 1";
    $prefix = "LC-" . $current_year . "-";

    if($stmt = mysqli_prepare($link, $sql)){
        $param_prefix_like = $prefix . "%";
        mysqli_stmt_bind_param($stmt, "s", $param_prefix_like);

        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_num_rows($stmt) > 0){
                mysqli_stmt_bind_result($stmt, $last_clearance_no);
                mysqli_stmt_fetch($stmt);
                $last_no = intval(str_replace($prefix, "", $last_clearance_no));
                $next_no = $last_no + 1;
            } else {
                $next_no = 1;
            }
        } else {
            $next_no = 1; // Fallback on execution error
        }
        mysqli_stmt_close($stmt);
        return $prefix . str_pad($next_no, 3, "0", STR_PAD_LEFT);
    }
    return $prefix . "001"; // Fallback if statement prep fails
}


if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate core fields (similar to add_zoning.php)
    $applicant_name = trim($_POST["applicant_name"]);
    if(empty($applicant_name)) $errors["applicant_name"] = "Applicant's name is required.";
    $owner_name = trim($_POST["owner_name"]);
    if(empty($owner_name)) $errors["owner_name"] = "Owner's name is required.";
    $address = trim($_POST["address"]);
    if(empty($address)) $errors["address"] = "Address is required.";
    $date_filed = trim($_POST["date_filed"]);
    if(empty($date_filed)){
        $errors["date_filed"] = "Date filed is required.";
    } else {
        $issue_date = $date_filed; // Issue date is same as date filed
        $expiration_date_obj = new DateTime($date_filed);
        $expiration_date_obj->add(new DateInterval('P1Y')); // Add 1 year
        $expiration_date = $expiration_date_obj->format('Y-m-d');
    }
    $tax_declaration = trim($_POST["tax_declaration"]); // Optional
    $project_type = trim($_POST["project_type"]);
    if(empty($project_type)) $errors["project_type"] = "Project type is required.";
    $project_location = trim($_POST["project_location"]);
    if(empty($project_location)) $errors["project_location"] = "Project location is required.";
    $purpose = trim($_POST["purpose"]); // Optional
    $land_use_classification = trim($_POST["land_use_classification"]); // Optional or make required
    $fees_paid = trim($_POST["fees_paid"]);
    if(!is_numeric($fees_paid) || $fees_paid < 0) $errors["fees_paid"] = "Valid fees paid amount is required.";
    if(empty($fees_paid) && $fees_paid !== '0') $errors["fees_paid"] = "Fees paid is required.";
    $or_number = trim($_POST["or_number"]);
    if(empty($or_number)) $errors["or_number"] = "O.R. Number is required.";
    $signatory_name = trim($_POST["signatory_name"]);
    if(empty($signatory_name)) $errors["signatory_name"] = "Signatory name is required.";

    // Process conditions checkboxes
    for ($i = 1; $i <= 8; $i++) {
        $conditions['condition' . $i] = isset($_POST['condition' . $i]) ? 1 : 0;
    }

    if(empty($errors)){
        $clearance_number = generateClearanceNumber($link);

        $sql = "INSERT INTO locational_clearances (
                    applicant_name, owner_name, address, date_filed, issue_date, clearance_number, expiration_date,
                    tax_declaration, project_type, project_location, purpose, land_use_classification,
                    fees_paid, or_number, signatory_name, encoded_by_user_id,
                    condition1_monitoring, condition2_non_compliance, condition3_other_agencies,
                    condition4_activity_applied_for, condition5_no_major_expansion,
                    condition6_not_cert_ownership, condition7_misrepresentation, condition8_commencement_period
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssssssssssdssiiiiiiiii",
                $applicant_name, $owner_name, $address, $date_filed, $issue_date, $clearance_number, $expiration_date,
                $tax_declaration, $project_type, $project_location, $purpose, $land_use_classification,
                $fees_paid, $or_number, $signatory_name, $encoded_by_user_id,
                $conditions['condition1'], $conditions['condition2'], $conditions['condition3'],
                $conditions['condition4'], $conditions['condition5'], $conditions['condition6'],
                $conditions['condition7'], $conditions['condition8']
            );

            if(mysqli_stmt_execute($stmt)){
                $_SESSION['message'] = "Locational Clearance (".$clearance_number.") added successfully!";
                // Redirect based on user role
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                    header("location: manage_locational.php");
                } else {
                    header("location: manage_locational_user.php");
                }
                exit;
            } else {
                $_SESSION['error'] = "Database execution error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
             $_SESSION['error'] = "Database statement preparation error: " . mysqli_error($link);
        }
    }

    // If errors, store them and data in session to repopulate form
    if(!empty($errors)) {
        $_SESSION['form_errors_lc'] = $errors;
        $_SESSION['form_data_lc'] = $_POST; // Includes conditions
        header("location: add_locational.php");
        exit;
    }
    mysqli_close($link);
} else {
    // Retrieve errors and form data from session if redirected from POST
    if(isset($_SESSION['form_errors_lc'])){
        $errors = $_SESSION['form_errors_lc'];
        unset($_SESSION['form_errors_lc']);
    }
    if(isset($_SESSION['form_data_lc'])){
        $form_data = $_SESSION['form_data_lc'];
        $applicant_name = $form_data['applicant_name'] ?? '';
        $owner_name = $form_data['owner_name'] ?? '';
        $address = $form_data['address'] ?? '';
        $date_filed = $form_data['date_filed'] ?? '';
        $tax_declaration = $form_data['tax_declaration'] ?? '';
        $project_type = $form_data['project_type'] ?? '';
        $project_location = $form_data['project_location'] ?? '';
        $purpose = $form_data['purpose'] ?? '';
        $land_use_classification = $form_data['land_use_classification'] ?? '';
        $fees_paid = $form_data['fees_paid'] ?? '';
        $or_number = $form_data['or_number'] ?? '';
        $signatory_name = $form_data['signatory_name'] ?? '';
        for ($i = 1; $i <= 8; $i++) {
            $conditions['condition' . $i] = isset($form_data['condition' . $i]) ? 1 : 0;
        }
        unset($_SESSION['form_data_lc']);
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
    <title>Add Locational Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
    </style>
    <script>
        function toggleAllConditions(source) {
            const checkboxes = document.querySelectorAll('.conditions-fieldset input[type="checkbox"]');
            for (let i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i] !== source) {
                    checkboxes[i].checked = source.checked;
                }
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyBtn = document.getElementById('copy_details_btn');
            const searchInput = document.getElementById('zoning_cert_search');
            const statusP = document.getElementById('copy_status');

            copyBtn.addEventListener('click', function() {
                const selectedValue = searchInput.value;
                if (!selectedValue) {
                    statusP.textContent = 'Please select a certificate from the list.';
                    statusP.style.color = 'red';
                    return;
                }

                // Find the selected option in the datalist to get its data-id
                const options = document.querySelectorAll('#zoning_certs option');
                let selectedId = null;
                for (const option of options) {
                    if (option.value === selectedValue) {
                        selectedId = option.getAttribute('data-id');
                        break;
                    }
                }

                if (!selectedId) {
                    statusP.textContent = 'Invalid certificate number. Please choose from the list.';
                    statusP.style.color = 'red';
                    return;
                }

                statusP.textContent = 'Copying details...';
                statusP.style.color = 'blue';

                // AJAX call to fetch details
                fetch('get_zoning_details.php?id=' + selectedId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        // Populate the form fields
                        document.querySelector('input[name="applicant_name"]').value = data.applicant_name || '';
                        document.querySelector('input[name="owner_name"]').value = data.owner_name || '';
                        document.querySelector('textarea[name="address"]').value = data.address || '';
                        document.querySelector('input[name="tax_declaration"]').value = data.tax_declaration || '';
                        document.querySelector('input[name="project_type"]').value = data.project_type || '';
                        document.querySelector('textarea[name="project_location"]').value = data.project_location || '';
                        document.querySelector('input[name="purpose"]').value = data.purpose || '';
                        // Note: land_use_classification is not in zoning certs, so it's not filled.

                        statusP.textContent = 'Details copied successfully!';
                        statusP.style.color = 'green';
                    })
                    .catch(error => {
                        statusP.textContent = 'Error: ' + error.message;
                        statusP.style.color = 'red';
                        console.error('There was a problem with the fetch operation:', error);
                    });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <nav>
            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <a href="main_dashboard.php">Dashboard</a>
                <a href="manage_zoning.php">Zoning Certificates</a>
                <a href="manage_locational.php">Locational Clearances</a>
                <div class="dropdown">
                    <a href="#" class="dropbtn">Admin Settings</a>
                    <div class="dropdown-content">
                        <a href="settings.php">Application Settings</a>
                        <a href="admin_dashboard.php">User Management</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="user_dashboard.php">My Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" style="float:right;">Sign Out</a>
        </nav>

        <div class="wrapper">
            <div class="copy-from-zoning" style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>Copy Details from Zoning Certificate</h4>
                <div class="form-group">
                    <label for="zoning_cert_search">Search by Zoning Certificate Number:</label>
                    <input list="zoning_certs" name="zoning_cert_search" id="zoning_cert_search" class="form-control" placeholder="Type to search...">
                    <datalist id="zoning_certs">
                        <?php
                        // Fetch all zoning certs for the datalist
                        $sql_certs = "SELECT id, certificate_number, applicant_name FROM zoning_certificates ORDER BY id DESC";
                        if ($result_certs = mysqli_query($link, $sql_certs)) {
                            while($row_cert = mysqli_fetch_assoc($result_certs)){
                                echo '<option value="' . htmlspecialchars($row_cert['certificate_number']) . '" data-id="' . $row_cert['id'] . '">';
                                echo htmlspecialchars($row_cert['applicant_name']);
                                echo '</option>';
                            }
                        }
                        ?>
                    </datalist>
                </div>
                <button type="button" class="btn" id="copy_details_btn" style="background-color:#17a2b8; color:white;">Copy Details</button>
                <p id="copy_status" style="margin-top:10px;"></p>
            </div>

            <h2>Add New Locational Clearance</h2>
            <p>Please fill this form to add a new locational clearance.</p>

            <?php
            if(!empty($_SESSION['error'])){
                echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            if(!empty($errors) && is_array($errors)){ // Display specific form validation errors
                echo '<div class="alert alert-danger">';
                foreach($errors as $field_error){ echo htmlspecialchars($field_error) . '<br>'; }
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
                </div>

                <fieldset class="conditions-fieldset full-width">
                    <legend>Conditions</legend>
                    <div class="condition-item">
                        <input type="checkbox" id="tick_all_conditions" onclick="toggleAllConditions(this)">
                        <label for="tick_all_conditions"><strong>Tick/Untick All</strong></label>
                    </div>
                    <hr>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div class="condition-item">
                        <input type="checkbox" name="condition<?php echo $i; ?>" id="condition<?php echo $i; ?>" value="1" <?php echo ($conditions['condition'.$i] == 1) ? 'checked' : ''; ?>>
                        <label for="condition<?php echo $i; ?>"><?php echo $i . ". " . htmlspecialchars($condition_texts[$i]); ?></label>
                    </div>
                    <?php endfor; ?>
                </fieldset>

                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Submit Clearance">
                    <a href="<?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ? 'manage_locational.php' : 'manage_locational_user.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
