<?php
session_start();

// Require login to access this page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Determine user role for displaying controls
$is_admin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;

$search_query = "";
$zoning_results = [];
$locational_results = [];

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_query = trim($_GET['query']);
    $search_param = "%" . $search_query . "%";

    // --- Search Zoning Certificates ---
    $sql_zoning = "SELECT id, certificate_number, applicant_name, owner_name, zoning_classification, project_location
                   FROM zoning_certificates
                   WHERE applicant_name LIKE ?
                   OR owner_name LIKE ?
                   OR zoning_classification LIKE ?
                   OR project_location LIKE ?";

    if ($stmt_zoning = mysqli_prepare($link, $sql_zoning)) {
        mysqli_stmt_bind_param($stmt_zoning, "ssss", $search_param, $search_param, $search_param, $search_param);
        if (mysqli_stmt_execute($stmt_zoning)) {
            $result_zoning = mysqli_stmt_get_result($stmt_zoning);
            while ($row = mysqli_fetch_assoc($result_zoning)) {
                $zoning_results[] = $row;
            }
        } else {
            echo "Error executing zoning search."; // Handle error
        }
        mysqli_stmt_close($stmt_zoning);
    }

    // --- Search Locational Clearances ---
    // Note: 'type of zone' is interpreted as land_use_classification for locational
    $sql_locational = "SELECT id, clearance_number, applicant_name, owner_name, land_use_classification, project_location
                       FROM locational_clearances
                       WHERE applicant_name LIKE ?
                       OR owner_name LIKE ?
                       OR land_use_classification LIKE ?
                       OR project_location LIKE ?";

    if ($stmt_locational = mysqli_prepare($link, $sql_locational)) {
        mysqli_stmt_bind_param($stmt_locational, "ssss", $search_param, $search_param, $search_param, $search_param);
        if (mysqli_stmt_execute($stmt_locational)) {
            $result_locational = mysqli_stmt_get_result($stmt_locational);
            while ($row = mysqli_fetch_assoc($result_locational)) {
                $locational_results[] = $row;
            }
        } else {
            echo "Error executing locational search."; // Handle error
        }
        mysqli_stmt_close($stmt_locational);
    }
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav>
            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="manage_zoning.php">Zoning Certificates</a>
                <a href="manage_locational.php">Locational Clearances</a>
                <a href="settings.php">Settings</a>
            <?php else: ?>
                <a href="user_dashboard.php">My Dashboard</a>
            <?php endif; ?>
            <a href="logout.php" style="float:right; margin-right:20px;">Sign Out</a>
        </nav>

        <div class="wrapper" style="max-width: 90%; margin: 20px auto;">
            <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p><a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">Back to Dashboard</a></p>
            <hr>

            <!-- Zoning Certificate Results -->
            <h3>Zoning Certificates Found: <?php echo count($zoning_results); ?></h3>
            <?php if (!empty($zoning_results)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cert. No.</th>
                            <th>Applicant/Owner</th>
                            <th>Zoning Classification</th>
                            <th>Project Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zoning_results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['certificate_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['applicant_name'] . ' / ' . $row['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['zoning_classification']); ?></td>
                            <td><?php echo htmlspecialchars($row['project_location']); ?></td>
                            <td class="action-links">
                                <a href="view_zoning.php?id=<?php echo $row['id']; ?>">View</a>
                                <a href="print_zoning.php?id=<?php echo $row['id']; ?>" target="_blank">Print</a>
                                <?php if ($is_admin): ?>
                                    <a href="edit_zoning.php?id=<?php echo $row['id']; ?>">Edit</a>
                                    <a href="#" onclick="confirmDelete(<?php echo $row['id']; ?>); return false;" class="delete">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No matching zoning certificates found.</p>
            <?php endif; ?>

            <hr style="margin-top: 40px;">

            <!-- Locational Clearance Results -->
            <h3>Locational Clearances Found: <?php echo count($locational_results); ?></h3>
            <?php if (!empty($locational_results)): ?>
                 <table>
                    <thead>
                        <tr>
                            <th>Clearance No.</th>
                            <th>Applicant/Owner</th>
                            <th>Land Use Classification</th>
                            <th>Project Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locational_results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['clearance_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['applicant_name'] . ' / ' . $row['owner_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['land_use_classification']); ?></td>
                            <td><?php echo htmlspecialchars($row['project_location']); ?></td>
                            <td class="action-links">
                                <a href="view_locational.php?id=<?php echo $row['id']; ?>">View</a>
                                <a href="print_locational.php?id=<?php echo $row['id']; ?>" target="_blank">Print</a>
                                <?php if ($is_admin): ?>
                                    <a href="edit_locational.php?id=<?php echo $row['id']; ?>">Edit</a>
                                     <a href="#" onclick="confirmDeleteLocational(<?php echo $row['id']; ?>); return false;" class="delete">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No matching locational clearances found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- JS for delete confirmations -->
    <script>
        function confirmDelete(certificateId) {
            if (confirm("Are you sure you want to delete this zoning certificate? This action cannot be undone.")) {
                window.location.href = 'delete_zoning.php?id=' + certificateId;
            }
        }
        function confirmDeleteLocational(clearanceId) {
            if (confirm("Are you sure you want to delete this locational clearance? This action cannot be undone.")) {
                window.location.href = 'delete_locational.php?id=' + clearanceId;
            }
        }
    </script>
</body>
</html>
