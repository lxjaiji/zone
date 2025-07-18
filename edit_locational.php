<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Initialize variables
$applicant_name = $applicant_address = $developer_name = $developer_address = $project_name = "";
$right_over_land = $land_area = $building_area = $decision = "";
$location = $issue_date = $or_number = $amount_paid = $date_paid = $issued_at = "";
$permit_id = 0;
$errors = [];

$conditions_db = [];
for ($i = 1; $i <= 10; $i++) {
    $conditions_db['condition' . $i] = 0;
}

if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);
} else {
    header("location: manage_locational.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $permit_id = $_POST['id'];
    // === VALIDATION ===
    $applicant_name = trim($_POST['applicant_name']);
    if (empty($applicant_name)) $errors[] = "Applicant name is required.";

    $applicant_address = trim($_POST['applicant_address']);
    $developer_name = trim($_POST['developer_name']);
    $developer_address = trim($_POST['developer_address']);
    $project_name = trim($_POST['project_name']);
    if(empty($project_name)) $errors[] = "Project Name is required.";

    $project_location = trim($_POST['project_location']);
    if(empty($project_location)) $errors[] = "Project Location is required.";

    $issue_date = trim($_POST['issue_date']);
    if(empty($issue_date)) $errors[] = "Issue date is required.";

    $right_over_land = trim($_POST['right_over_land']);
    $land_area = trim($_POST['land_area']);
    $building_area = trim($_POST['building_area']);
    $decision = trim($_POST['decision']);
    $or_number = trim($_POST['or_number']);
    $amount_paid = trim($_POST['amount_paid']);
    $date_paid = trim($_POST['date_paid']);
    $issued_at = trim($_POST['issued_at']);

    for ($i = 1; $i <= 10; $i++) {
        $conditions_db['condition' . $i] = isset($_POST['condition' . $i]) ? 1 : 0;
    }

    if (empty($errors)) {
        $expiration_date_obj = new DateTime($issue_date);
        $expiration_date_obj->add(new DateInterval('P1Y'));
        $expiration_date = $expiration_date_obj->format('Y-m-d');

        $sql = "UPDATE locational_clearances SET
                    applicant_name=?, applicant_address=?, developer_name=?, developer_address=?, project_location=?, issue_date=?, expiration_date=?,
                    project_name=?, right_over_land=?, land_area=?, building_area=?, decision=?,
                    or_number=?, amount_paid=?, date_paid=?, issued_at=?,
                    condition1_monitoring=?, condition2_non_compliance=?, condition3_other_agencies=?,
                    condition4_activity_applied_for=?, condition5_no_major_expansion=?,
                    condition6_not_cert_ownership=?, condition7_misrepresentation=?, condition8_commencement_period=?,
                    condition9_revoked=?, condition10_provisional=?
                WHERE id=?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssssssssdssiiiiiiiiiii",
                $applicant_name, $applicant_address, $developer_name, $developer_address, $project_location, $issue_date, $expiration_date,
                $project_name, $right_over_land, $land_area, $building_area, $decision,
                $or_number, $amount_paid, $date_paid, $issued_at,
                $conditions_db['condition1'], $conditions_db['condition2'], $conditions_db['condition3'], $conditions_db['condition4'],
                $conditions_db['condition5'], $conditions_db['condition6'], $conditions_db['condition7'], $conditions_db['condition8'],
                $conditions_db['condition9'], $conditions_db['condition10'],
                $permit_id
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Locational Clearance updated successfully.";
                header("location: manage_locational.php");
                exit;
            } else {
                $errors[] = "Database execution error: " . mysqli_stmt_error($stmt);
            }
        }
    }
} else {
    // Fetch existing data
    $sql = "SELECT * FROM locational_clearances WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $permit = mysqli_fetch_assoc($result);
                $applicant_name = $permit['applicant_name'];
                $applicant_address = $permit['applicant_address'];
                $developer_name = $permit['developer_name'];
                $developer_address = $permit['developer_address'];
                $project_name = $permit['project_name'];
                $right_over_land = $permit['right_over_land'];
                $land_area = $permit['land_area'];
                $building_area = $permit['building_area'];
                $decision = $permit['decision'];
                $project_location = $permit['project_location'];
                $issue_date = $permit['issue_date'];
                $or_number = $permit['or_number'];
                $amount_paid = $permit['amount_paid'];
                $date_paid = $permit['date_paid'];
                $issued_at = $permit['issued_at'];
                for ($i = 1; $i <= 10; $i++) {
                    $key = 'condition' . $i;
                    $db_key = $key . ( $i==1 ? '_monitoring' : ($i==2 ? '_non_compliance' : ($i==3 ? '_other_agencies' : ($i==4 ? '_activity_applied_for' : ($i==5 ? '_no_major_expansion' : ($i==6 ? '_not_cert_ownership' : ($i==7 ? '_misrepresentation' : ($i==8 ? '_commencement_period' : ($i==9 ? '_revoked' : '_provisional')))))))));
                    $conditions_db[$key] = $permit[$db_key] ?? 0;
                }
            }
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
    9 => "The Decision shall be considered automatically revoked if project is not commenced within one (1) year from the date of issuance of this Decision.",
    10 => "PROVISIONAL CLEARANCE ONLY."
];

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Locational Clearance</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 900px; margin: 20px auto; }
        .form-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-column { display: flex; flex-direction: column; gap: 15px; }
        .form-column:first-child { padding-right: 20px; border-right: 1px solid #ddd; }
        .full-width { grid-column: 1 / -1; }
    </style>
     <script>
        function toggleAllConditions(source) {
            const checkboxes = document.querySelectorAll('.condition-checkbox');
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
            <h2>Edit Locational Clearance</h2>
            <hr>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $permit_id; ?>">
                <div class="form-group"><label>Date of Issuance:</label><input type="date" name="issue_date" class="form-control" value="<?php echo htmlspecialchars($issue_date); ?>" required></div>
                <hr>
                <div class="form-container">
                    <!-- Column 1 -->
                    <div class="form-column">
                        <div class="form-group"><label>APPLICANT:</label><input type="text" name="applicant_name" class="form-control" value="<?php echo htmlspecialchars($applicant_name); ?>" required></div>
                        <div class="form-group"><label>ADDRESS:</label><input type="text" name="applicant_address" class="form-control" value="<?php echo htmlspecialchars($applicant_address); ?>"></div>
                        <div class="form-group"><label>NAME OF PROJECT:</label><input type="text" name="project_name" class="form-control" value="<?php echo htmlspecialchars($project_name); ?>" required></div>
                        <div class="form-group"><label>RIGHT OVER LAND:</label><input type="text" name="right_over_land" class="form-control" value="<?php echo htmlspecialchars($right_over_land); ?>"></div>
                    </div>
                    <!-- Column 2 -->
                    <div class="form-column">
                        <div class="form-group"><label>NAME OF DEVELOPER:</label><input type="text" name="developer_name" class="form-control" value="<?php echo htmlspecialchars($developer_name); ?>"></div>
                        <div class="form-group"><label>ADDRESS:</label><input type="text" name="developer_address" class="form-control" value="<?php echo htmlspecialchars($developer_address); ?>"></div>
                        <div class="form-group"><label>PROJECT LOCATION:</label><input type="text" name="project_location" class="form-control" value="<?php echo htmlspecialchars($project_location); ?>" required></div>
                        <div style="display:flex; gap:10px;">
                            <div class="form-group" style="flex:1;"><label>LAND AREA:</label><input type="text" name="land_area" class="form-control" value="<?php echo htmlspecialchars($land_area); ?>"></div>
                            <div class="form-group" style="flex:1;"><label>BUILDING AREA:</label><input type="text" name="building_area" class="form-control" value="<?php echo htmlspecialchars($building_area); ?>"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top:20px;">
                    <label>DECISION:</label>
                    <select name="decision" class="form-control">
                        <option value="Granted" <?php if($decision == 'Granted') echo 'selected'; ?>>Granted</option>
                        <option value="Denied" <?php if($decision == 'Denied') echo 'selected'; ?>>Denied</option>
                        <option value="Appeal" <?php if($decision == 'Appeal') echo 'selected'; ?>>Appeal</option>
                        <option value="Other Consideration" <?php if($decision == 'Other Consideration') echo 'selected'; ?>>Other Consideration</option>
                    </select>
                </div>

                <fieldset class="conditions-fieldset full-width" style="margin-top:20px;">
                    <legend>Conditions</legend>
                    <div class="condition-item"><input type="checkbox" id="tick_all_conditions" onclick="toggleAllConditions(this)"><label for="tick_all_conditions"><strong>Tick/Untick All</strong></label></div>
                    <hr>
                    <?php foreach ($condition_texts as $index => $text): ?>
                        <div class="condition-item"><input type="checkbox" class="condition-checkbox" name="condition<?php echo $index; ?>" value="1" <?php echo ($conditions_db['condition'.$index] ?? 0) ? 'checked' : ''; ?>> <label><?php echo htmlspecialchars($text); ?></label></div>
                    <?php endforeach; ?>
                </fieldset>

                 <div class="form-container" style="margin-top:20px;">
                    <div class="form-column">
                        <div class="form-group"><label>O.R. No.</label><input type="text" name="or_number" class="form-control" value="<?php echo htmlspecialchars($or_number); ?>"></div>
                        <div class="form-group"><label>Amount Paid</label><input type="number" step="0.01" name="amount_paid" class="form-control" value="<?php echo htmlspecialchars($amount_paid); ?>"></div>
                    </div>
                    <div class="form-column">
                        <div class="form-group"><label>Date Paid</label><input type="date" name="date_paid" class="form-control" value="<?php echo htmlspecialchars($date_paid); ?>"></div>
                        <div class="form-group"><label>Issued at</label><input type="text" name="issued_at" class="form-control" value="<?php echo htmlspecialchars($issued_at); ?>"></div>
                    </div>
                </div>

                <div class="form-group full-width" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Update">
                    <a href="manage_locational.php" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
