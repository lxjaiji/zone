<?php
// This file assumes a session has already been started on the parent page.
// It also assumes config.php has been included for a database link if needed,
// but for simplicity, we'll establish a connection here.

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config.php";

// Get a dedicated database connection for the navigation
$nav_link = get_db_connection();

// Fetch the municipality logo path
$logo_path_nav = 'uploads/default_logo.png'; // Default
$sql_logo_nav = "SELECT setting_value FROM settings WHERE setting_key = 'municipality_logo_path' LIMIT 1";
if ($result_logo_nav = mysqli_query($nav_link, $sql_logo_nav)) {
    if (mysqli_num_rows($result_logo_nav) == 1) {
        $row_logo_nav = mysqli_fetch_assoc($result_logo_nav);
        $logo_path_nav = $row_logo_nav['setting_value'];
    }
}
// Close the dedicated connection
mysqli_close($nav_link);

$is_admin_nav = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>

<nav>
    <a href="<?php echo $is_admin_nav ? 'main_dashboard.php' : 'user_dashboard.php'; ?>" class="nav-logo-link">
        <img src="<?php echo htmlspecialchars($logo_path_nav); ?>" alt="Logo" class="nav-logo">
    </a>

    <?php if ($is_admin_nav): ?>
        <!-- Admin Navigation -->
        <a href="main_dashboard.php">Dashboard</a>
        <a href="manage_zoning.php">Zoning Certificates</a>
        <a href="manage_locational.php">Locational Clearances</a>
        <a href="manage_fishing_permits.php">Fishing Permits</a>
        <div class="dropdown">
            <a href="#" class="dropbtn">Admin Settings</a>
            <div class="dropdown-content">
                <a href="settings.php">Application Settings</a>
                <a href="admin_dashboard.php">User Management</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Regular User Navigation -->
        <a href="user_dashboard.php">Dashboard</a>
        <a href="manage_zoning_user.php">Zoning Certificates</a>
        <a href="manage_locational_user.php">Locational Clearances</a>
        <a href="manage_fishing_permits_user.php">Fishing Permits</a>
        <a href="change_password.php">Change Password</a>
    <?php endif; ?>

    <a href="logout.php" style="float:right;">Sign Out</a>
</nav>
