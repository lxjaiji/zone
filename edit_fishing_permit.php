<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Initialize variables
$gear_type = $owner_name = $owner_resident_of = $location = "";
$issue_date = $or_number = $amount_paid = $date_paid = $issued_at = "";
$permit_id = 0;
$errors = [];

// Get ID from URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $permit_id = trim($_GET["id"]);
} else {
    $_SESSION['error'] = "No permit ID specified.";
    header("location: manage_fishing_permits.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $permit_id = $_POST['id'];

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

    if (empty($errors)) {
        $sql = "UPDATE fishing_gear_permits SET gear_type=?, owner_name=?, owner_resident_of=?, location=?, issue_date=?, or_number=?, amount_paid=?, date_paid=?, issued_at=? WHERE id=?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssdssi",
                $gear_type, $owner_name, $owner_resident_of, $location, $issue_date,
                $or_number, $amount_paid, $date_paid, $issued_at, $permit_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Fishing Permit updated successfully!";
                header("location: manage_fishing_permits.php");
                exit;
            } else {
                $errors[] = "Database error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database statement preparation error.";
        }
    }
} else {
    // Fetch existing data for the form
    $sql = "SELECT * FROM fishing_gear_permits WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $permit_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $permit = mysqli_fetch_assoc($result);
                $gear_type = $permit['gear_type'];
                $owner_name = $permit['owner_name'];
                $owner_resident_of = $permit['owner_resident_of'];
                $location = $permit['location'];
                $issue_date = $permit['issue_date'];
                $or_number = $permit['or_number'];
                $amount_paid = $permit['amount_paid'];
                $date_paid = $permit['date_paid'];
                $issued_at = $permit['issued_at'];
            } else {
                $_SESSION['error'] = "No permit found with that ID.";
                header("location: manage_fishing_permits.php");
                exit;
            }
        }
    }
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Fishing Permit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrapper { max-width: 700px; margin: 20px auto; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="wrapper">
            <h2>Edit Fishing Structure/Gear Permit</h2>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $permit_id; ?>">
                <div class="form-group">
                    <label>Fishing Structure/Gear</label>
                    <select name="gear_type" class="form-control" required>
                        <option value="">-- Select Type --</option>
                        <option value="Taba" <?php if($gear_type == 'Taba') echo 'selected'; ?>>Taba</option>
                        <option value="Bentahan" <?php if($gear_type == 'Bentahan') echo 'selected'; ?>>Bentahan</option>
                        <option value="Fish Cage" <?php if($gear_type == 'Fish Cage') echo 'selected'; ?>>Fish Cage</option>
                        <option value="Talabahan" <?php if($gear_type == 'Talabahan') echo 'selected'; ?>>Talabahan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Owner</label>
                    <input type="text" name="owner_name" class="form-control" value="<?php echo htmlspecialchars($owner_name); ?>" required>
                </div>
                 <div class="form-group">
                    <label>Resident of</label>
                    <input type="text" name="owner_resident_of" class="form-control" value="<?php echo htmlspecialchars($owner_resident_of); ?>">
                </div>
                <div class="form-group">
                    <label>Located in</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" required>
                </div>
                <div class="form-group">
                    <label>Date of Issuance</label>
                    <input type="date" name="issue_date" class="form-control" value="<?php echo htmlspecialchars($issue_date); ?>" required>
                </div>
                 <div class="form-group">
                    <label>O.R. No.</label>
                    <input type="text" name="or_number" class="form-control" value="<?php echo htmlspecialchars($or_number); ?>">
                </div>
                 <div class="form-group">
                    <label>Amount Paid</label>
                    <input type="number" step="0.01" name="amount_paid" class="form-control" value="<?php echo htmlspecialchars($amount_paid); ?>">
                </div>
                <div class="form-group">
                    <label>Date Paid</label>
                    <input type="date" name="date_paid" class="form-control" value="<?php echo htmlspecialchars($date_paid); ?>">
                </div>
                 <div class="form-group">
                    <label>Issued at</label>
                    <input type="text" name="issued_at" class="form-control" value="<?php echo htmlspecialchars($issued_at); ?>">
                </div>
                <div class="form-group" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Update Permit">
                    <a href="manage_fishing_permits.php" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
