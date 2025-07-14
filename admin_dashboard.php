<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an admin, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Fetch all non-admin users
$users = [];
$sql = "SELECT id, username FROM users WHERE is_admin = FALSE ORDER BY username ASC";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $users[] = $row;
        }
        mysqli_free_result($result);
    }
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
}

// Close connection (will be reopened by included files if needed)
// mysqli_close($link); // Commenting out as add_user.php and delete_user.php will need it.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function confirmDelete(userId) {
            if (confirm("Are you sure you want to delete this user?")) {
                window.location.href = 'delete_user.php?id=' + userId;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>
        <div class="page-header" style="margin-top: 20px;">
            <h1>User Management</h1>
        </div>

        <h2>Manage Existing Users</h2>

        <h3>Add New User</h3>
        <form action="add_user.php" method="post" class="wrapper" style="width:auto; margin-bottom: 20px; background-color: #f9f9f9; padding: 15px;">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="is_admin_checkbox">
                    <input type="checkbox" name="is_admin" id="is_admin_checkbox" value="1">
                    Make this user an Administrator
                </label>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Add User">
            </div>
        </form>

        <h3>Existing Users</h3>
        <?php if(!empty($users)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="action-links">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>">Edit</a>
                        <a href="#" onclick="confirmDelete(<?php echo $user['id']; ?>); return false;" class="delete">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No users found.</p>
        <?php endif; ?>

        <?php
        // Display messages if any
        if(isset($_SESSION['message'])){
            echo '<p class="alert alert-success" style="margin-top:20px; text-align:center;">'.$_SESSION['message'].'</p>';
            unset($_SESSION['message']); // Clear the message after displaying
        }
        if(isset($_SESSION['error'])){
            echo '<p class="alert alert-danger" style="margin-top:20px; text-align:center;">'.$_SESSION['error'].'</p>';
            unset($_SESSION['error']); // Clear the error after displaying
        }
        ?>
    </div>
</body>
</html>
