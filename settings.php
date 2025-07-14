<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

$settings = [];
$errors = [];
$success_msg = "";

// Function to fetch all settings
function get_settings($link) {
    $settings_data = [];
    $sql = "SELECT setting_key, setting_value FROM settings";
    if ($result = mysqli_query($link, $sql)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings_data[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings_data;
}

// Function to update a setting
function update_setting($link, $key, $value) {
    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $value, $key);
        return mysqli_stmt_execute($stmt);
    }
    return false;
}

// Handle file upload
function handle_logo_upload($file_input_name, $setting_key, &$errors, $link) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_name = basename($_FILES[$file_input_name]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES[$file_input_name]["tmp_name"]);
        if ($check === false) {
            $errors[] = "File uploaded for " . htmlspecialchars($file_input_name) . " is not an image.";
            return;
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed for logos.";
            return;
        }

        // Move file and update setting
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            update_setting($link, $setting_key, $target_file);
        } else {
            $errors[] = "Sorry, there was an error uploading your " . htmlspecialchars($file_input_name) . " file.";
        }
    }
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update text settings
    $province_name = trim($_POST['province_name']);
    $municipality_name = trim($_POST['municipality_name']);
    $default_signatory_name = trim($_POST['default_signatory_name']);

    if(empty($province_name)) $errors[] = "Province name cannot be empty.";
    if(empty($municipality_name)) $errors[] = "Municipality name cannot be empty.";

    if(empty($errors)) {
        update_setting($link, 'province_name', $province_name);
        update_setting($link, 'municipality_name', $municipality_name);
        update_setting($link, 'default_signatory_name', $default_signatory_name);

        // Handle logo uploads
        handle_logo_upload('municipality_logo', 'municipality_logo_path', $errors, $link);

        if(empty($errors)) {
            $success_msg = "Settings updated successfully!";
        }
    }
}

// Fetch current settings to display on the page
$settings = get_settings($link);
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application Settings</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-wrapper { max-width: 800px; margin: 20px auto; }
        .logo-preview { max-width: 100px; max-height: 100px; border: 1px solid #ddd; padding: 5px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="settings-wrapper">
            <h2>Application Settings</h2>
            <p>Customize the details that appear on printed certificates and clearances.</p>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Province Name</label>
                    <input type="text" name="province_name" class="form-control" value="<?php echo htmlspecialchars($settings['province_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Municipality/City Name</label>
                    <input type="text" name="municipality_name" class="form-control" value="<?php echo htmlspecialchars($settings['municipality_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Default Signatory Name</label>
                    <input type="text" name="default_signatory_name" class="form-control" value="<?php echo htmlspecialchars($settings['default_signatory_name'] ?? ''); ?>">
                    <p><small>This name will be pre-filled on new certificates and clearances.</small></p>
                </div>
                <hr>
                 <div class="form-group">
                    <label>Municipality/City Logo</label>
                    <input type="file" name="municipality_logo" class="form-control">
                    <p><small>Current Logo:</small></p>
                    <img src="<?php echo htmlspecialchars($settings['municipality_logo_path'] ?? ''); ?>?t=<?php echo time(); ?>" alt="Municipality Logo" class="logo-preview">
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <input type="submit" class="btn btn-primary" value="Save Settings">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
