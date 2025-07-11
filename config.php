<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Replace with your database username
define('DB_PASSWORD', ''); // Replace with your database password
define('DB_NAME', 'user_management_db'); // Replace with your database name

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
