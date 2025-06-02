<?php
header('Content-Type: application/json');
session_start();

$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, activity_name, activity_date, activity_description, activity_location, target_audience FROM activities";
$result = $conn->query($sql);

$activities = [];

while ($row = $result->fetch_assoc()) {
    $activities[] = [
        'id' => $row['id'],
        'title' => $row['activity_name'],
        'start' => $row['activity_date'],
        'description' => $row['activity_description'],
        'location' => $row['activity_location'],
        'audience' => $row['target_audience'],
    ];
}

$conn->close();

echo json_encode($activities);
?>
