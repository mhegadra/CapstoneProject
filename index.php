<?php
// db.php
$servername = "sql207.byetcluster.com";
$username = "if0_37835012";
$password = "ki9Jzd8Rn93QoC";
$dbname = "if0_37835012_immuni_track";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
