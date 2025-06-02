<?php
// Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '12345';
$db = 'immuni_track';  // Database name

// Create a new connection to the MySQL database
$conn = new mysqli($host, $user, $pass, $db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
