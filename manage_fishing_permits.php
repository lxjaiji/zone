<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

$permits = [];
$sql = "SELECT id, gear_type, owner_name, location, issue_date FROM fishing_gear_permits ORDER BY issue_date DESC, id DESC";

// Pagination logic
$records_per_page = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$total_sql = "SELECT COUNT(*) FROM fishing_gear_permits";
$total_result = mysqli_query($link, $total_sql);
$total_rows = mysqli_fetch_array($total_result)[0];
$total_pages = ceil($total_rows / $records_per_page);

$sql .= " LIMIT $offset, $records_per_page";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $permits[] = $row;
    }
} else {
    $_SESSION['error'] = "Error fetching fishing permits: " . mysqli_error($link);
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Fishing Permits</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmDeleteFishingPermit(permitId) {
            if (confirm("Are you sure you want to delete this fishing permit? This action cannot be undone.")) {
                window.location.href = 'delete_fishing_permit.php?id=' + permitId;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>

        <div class="page-header" style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
            <h2>Manage Fishing Permits</h2>
            <a href="add_fishing_permit.php" class="btn btn-success" style="background-color: #28a745; border-color: #28a745; color:white; text-decoration:none; padding: 10px 15px; border-radius:5px;">Add New Permit</a>
        </div>

        <?php
        if (isset($_SESSION['message'])) {
            echo '<p class="alert alert-success" style="text-align:center;">' . $_SESSION['message'] . '</p>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<p class="alert alert-danger" style="text-align:center;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>

        <?php if (!empty($permits)): ?>
        <table>
            <thead>
                <tr>
                    <th>Gear Type</th>
                    <th>Owner</th>
                    <th>Location</th>
                    <th>Issue Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permits as $permit): ?>
                <tr>
                    <td><?php echo htmlspecialchars($permit['gear_type']); ?></td>
                    <td><?php echo htmlspecialchars($permit['owner_name']); ?></td>
                    <td><?php echo htmlspecialchars($permit['location']); ?></td>
                    <td><?php echo date("F j, Y", strtotime($permit['issue_date'])); ?></td>
                    <td class="action-links">
                        <a href="view_fishing_permit.php?id=<?php echo $permit['id']; ?>">View</a>
                        <a href="edit_fishing_permit.php?id=<?php echo $permit['id']; ?>">Edit</a>
                        <a href="#" onclick="confirmDeleteFishingPermit(<?php echo $permit['id']; ?>); return false;" class="delete">Delete</a>
                        <a href="print_fishing_permit.php?id=<?php echo $permit['id']; ?>" target="_blank">Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination Links -->
        <div style="margin-top: 20px; text-align: center;">
            <?php if($total_pages > 1): ?>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="manage_fishing_permits.php?page=<?php echo $i; ?>" style="<?php if($i == $page) echo 'font-weight:bold;'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <p class="text-center" style="margin-top:20px;">No fishing permits found. <a href="add_fishing_permit.php">Add one now</a>.</p>
        <?php endif; ?>
    </div>
</body>
</html>
