<?php
session_start();
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

// Create database connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(["error" => "User not logged in."]);
    exit();
}

// Get barangay ID from the user's session
$email = $_SESSION['email'];
$sql = "SELECT barangay_id FROM usertable WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $barangayId = $user['barangay_id'];
} else {
    echo json_encode(["error" => "Barangay ID not found."]);
    exit();
}

// Insert new event if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activityName = $_POST['activity_name'];
    $activityDate = $_POST['activity_date'];
    $activityDescription = $_POST['activity_description'];

    $insertQuery = "INSERT INTO activities (activity_name, activity_date, activity_description, barangay_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('sssi', $activityName, $activityDate, $activityDescription, $barangayId);
    $stmt->execute();
}

// Fetch updated list of recent activities
$activitiesQuery = "SELECT activity_name, activity_date, activity_description 
                    FROM activities 
                    WHERE barangay_id = ? 
                    ORDER BY activity_date DESC 
                    LIMIT 10";
$stmt = $conn->prepare($activitiesQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Return recent activities as JSON
echo json_encode($activities);

// Close connections
$stmt->close();
$conn->close();
?>
