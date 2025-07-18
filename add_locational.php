<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Define variables
$applicant_name = $developer_name = $developer_address = $project_name = "";
$right_over_land = $land_area = $building_area = $decision = "";
$location = $issue_date = $or_number = $amount_paid = $date_paid = $issued_at = "";
$encoded_by_user_id = $_SESSION["id"];
$errors = [];

$conditions = [];
for ($i = 1; $i <= 10; $i++) {
    $conditions['condition' . $i] = 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // === VALIDATION ===
    $applicant_name = trim($_POST['applicant_name']);
    if (empty($applicant_name)) $errors[] = "Applicant name is required.";

    $developer_name = trim($_POST['developer_name']);
    // Validation for other new fields...
    $developer_address = trim($_POST['developer_address']);
    $project_name = trim($_POST['project_name']);
    if(empty($project_name)) $errors[] = "Project Name is required.";

    $location = trim($_POST['location']);
    if(empty($location)) $errors[] = "Location is required.";

    $date_filed = trim($_POST['date_filed']);
    if(empty($date_filed)) {
        $errors[] = "Date Filed is required.";
    } else {
        $issue_date = $date_filed; // Set issue_date from date_filed
    }

    $right_over_land = trim($_POST['right_over_land']);
    $land_area = trim($_POST['land_area']);
    $building_area = trim($_POST['building_area']);
    $decision = trim($_POST['decision']);
    $or_number = trim($_POST['or_number']);
    $amount_paid = trim($_POST['amount_paid']);
    $date_paid = trim($_POST['date_paid']);
    $issued_at = trim($_POST['issued_at']);

    for ($i = 1; $i <= 10; $i++) {
        $conditions['condition' . $i] = isset($_POST['condition' . $i]) ? 1 : 0;
    }

    if (empty($errors)) {
        // Generate clearance number logic remains the same
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
                    } else { $next_no = 1; }
                } else { $next_no = 1; }
                mysqli_stmt_close($stmt);
                return $prefix . str_pad($next_no, 3, "0", STR_PAD_LEFT);
            }
            return $prefix . "001";
        }
        $clearance_number = generateClearanceNumber($link);

        // Expiration date logic remains the same
        $expiration_date_obj = new DateTime($issue_date);
        $expiration_date_obj->add(new DateInterval('P1Y'));
        $expiration_date = $expiration_date_obj->format('Y-m-d');

        $sql = "INSERT INTO locational_clearances (
                    applicant_name, developer_name, developer_address, location, issue_date, expiration_date, clearance_number,
                    project_name, right_over_land, land_area, building_area, decision,
                    or_number, amount_paid, date_paid, issued_at, encoded_by_user_id,
                    condition1_monitoring, condition2_non_compliance, condition3_other_agencies,
                    condition4_activity_applied_for, condition5_no_major_expansion,
                    condition6_not_cert_ownership, condition7_misrepresentation, condition8_commencement_period,
                    condition9_revoked, condition10_provisional
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssssssdssiiiiiiiiii",
                $applicant_name, $developer_name, $developer_address, $location, $issue_date, $expiration_date, $clearance_number,
                $project_name, $right_over_land, $land_area, $building_area, $decision,
                $or_number, $amount_paid, $date_paid, $issued_at, $encoded_by_user_id,
                $conditions['condition1'], $conditions['condition2'], $conditions['condition3'], $conditions['condition4'],
                $conditions['condition5'], $conditions['condition6'], $conditions['condition7'], $conditions['condition8'],
                $conditions['condition9'], $conditions['condition10']
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Locational Clearance created successfully.";
                header("location: manage_locational.php");
                exit;
            } else {
                $errors[] = "Database execution error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database statement preparation error: " . mysqli_error($link);
        }
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
    8 => "This Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of decision.",
    9 => "PROVISIONAL CLEARANCE ONLY.",
    10 => "The Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of issuance of this Decision."
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Locational Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 900px; }
        .form-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-column { display: flex; flex-direction: column; gap: 15px; }
        .full-width { grid-column: 1 / -1; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
            <h2>Add New Locational Clearance</h2>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="locationalForm">
                <div class="form-group">
                    <label>Date Filed:</label>
                    <input type="date" name="date_filed" class="form-control" required>
                </div>
                <hr>
                <div class="form-container">
                    <!-- Column 1 -->
                    <div class="form-column">
                        <div class="form-group">
                            <label>APPLICANT:</label>
                            <input type="text" name="applicant_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>NAME OF PROJECT:</label>
                            <input type="text" name="project_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>RIGHT OVER LAND:</label>
                            <input type="text" name="right_over_land" class="form-control">
                        </div>
                    </div>
                    <!-- Column 2 -->
                    <div class="form-column">
                        <div class="form-group">
                            <label>NAME OF DEVELOPER:</label>
                            <input type="text" name="developer_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>ADDRESS:</label>
                            <input type="text" name="developer_address" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>LOCATION:</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <div class="form-group" style="flex:1;">
                                <label>LAND AREA:</label>
                                <input type="text" name="land_area" class="form-control">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>BUILDING AREA:</label>
                                <input type="text" name="building_area" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top:20px;">
                    <label>DECISION:</label>
                    <textarea name="decision" class="form-control" rows="3"></textarea>
                </div>

                <fieldset class="conditions-fieldset full-width" style="margin-top:20px;">
                    <legend>Conditions</legend>
                    <!-- Tick all logic can be added here if needed -->
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div class="condition-item"><input type="checkbox" name="condition<?php echo $i; ?>" value="1"> <label><?php echo htmlspecialchars($condition_texts[$i] ?? 'Condition ' . $i); ?></label></div>
                    <?php endfor; ?>
                </fieldset>

                 <div class="form-container" style="margin-top:20px;">
                    <div class="form-column">
                        <div class="form-group">
                            <label>O.R. No.</label>
                            <input type="text" name="or_number" class="form-control">
                        </div>
                         <div class="form-group">
                            <label>Amount Paid</label>
                            <input type="number" step="0.01" name="amount_paid" class="form-control">
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label>Date Paid</label>
                            <input type="date" name="date_paid" class="form-control">
                        </div>
                         <div class="form-group">
                            <label>Issued at</label>
                            <input type="text" name="issued_at" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Submit">
                    <a href="manage_locational.php" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
