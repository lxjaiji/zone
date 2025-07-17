<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Define variables and initialize
$gear_type = $owner_name = $owner_resident_of = $location = "";
$issue_date = $or_number = $amount_paid = $date_paid = $issued_at = "";
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation
    $gear_type = trim($_POST['gear_type']);
    if (empty($gear_type)) $errors[] = "Fishing Structure/Gear type is required.";

    $owner_name = trim($_POST['owner_name']);
    if (empty($owner_name)) $errors[] = "Owner name is required.";

    $location = trim($_POST['location']);
    if (empty($location)) $errors[] = "Location is required.";

    $issue_date = trim($_POST['issue_date']);
    if (empty($issue_date)) $errors[] = "Date of Issuance is required.";

    // Optional fields
    $owner_resident_of = trim($_POST['owner_resident_of']);
    $or_number = trim($_POST['or_number']);
    $amount_paid = trim($_POST['amount_paid']);
    $date_paid = trim($_POST['date_paid']);
    $issued_at = trim($_POST['issued_at']);
    $encoded_by_user_id = $_SESSION['id'];

    if (empty($errors)) {
        $sql = "INSERT INTO fishing_gear_permits (gear_type, owner_name, owner_resident_of, location, issue_date, or_number, amount_paid, date_paid, issued_at, encoded_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssdssi",
                $gear_type, $owner_name, $owner_resident_of, $location, $issue_date,
                $or_number, $amount_paid, $date_paid, $issued_at, $encoded_by_user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Fishing Permit added successfully!";
                // Redirect based on role
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
                    header("location: manage_fishing_permits.php");
                } else {
                    header("location: manage_fishing_permits_user.php");
                }
                exit;
            } else {
                $errors[] = "Database error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database statement preparation error.";
        }
    }
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Fishing Permit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 700px; margin: 20px auto; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
            <h2>Encode New Fishing Structure/Gear Permit</h2>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Fishing Structure/Gear</label>
                    <select name="gear_type" class="form-control" required>
                        <option value="">-- Select Type --</option>
                        <option value="Taba">Taba</option>
                        <option value="Bentahan">Bentahan</option>
                        <option value="Fish Cage">Fish Cage</option>
                        <option value="Talabahan">Talabahan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Owner</label>
                    <input type="text" name="owner_name" class="form-control" required>
                </div>
                 <div class="form-group">
                    <label>Resident of</label>
                    <input type="text" name="owner_resident_of" class="form-control">
                </div>
                <div class="form-group">
                    <label>Located in</label>
                    <input type="text" name="location" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Date of Issuance</label>
                    <input type="date" name="issue_date" class="form-control" required>
                </div>
                 <div class="form-group">
                    <label>O.R. No.</label>
                    <input type="text" name="or_number" class="form-control">
                </div>
                 <div class="form-group">
                    <label>Amount Paid</label>
                    <input type="number" step="0.01" name="amount_paid" class="form-control">
                </div>
                <div class="form-group">
                    <label>Date Paid</label>
                    <input type="date" name="date_paid" class="form-control">
                </div>
                 <div class="form-group">
                    <label>Issued at</label>
                    <input type="text" name="issued_at" class="form-control">
                </div>
                <div class="form-group" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Submit Permit">
                    <a href="<?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ? 'manage_fishing_permits.php' : 'manage_fishing_permits_user.php'; ?>" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
