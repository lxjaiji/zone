<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Replace with your database username
define('DB_PASSWORD', ''); // Replace with your database password
define('DB_NAME', 'user_management_db'); // Replace with your database name

/*
* Function to create and return a database connection.
*/
function get_db_connection(){
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if($link === false){
        // In a real app, you might log this error instead of dying
        die("ERROR: Could not connect. " . mysqli_connect_error());
    }

    return $link;
}
?>
