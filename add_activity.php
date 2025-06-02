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
    header("Location: login-user.php");
    exit();
}

// Get the user's barangay ID from the session
$email = $_SESSION['email'];
$sql = "SELECT barangay_id FROM usertable WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$barangayId = $result->fetch_assoc()['barangay_id'];

// Check if form data was sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_activity') {
    // Get form data and sanitize
    $activityName = $conn->real_escape_string($_POST['activity_name']);
    $activityDate = $conn->real_escape_string($_POST['activity_date']);
    $activityDescription = $conn->real_escape_string($_POST['activity_description']);
    $activityTime = $conn->real_escape_string($_POST['activity_time']);
    $activityLocation = $conn->real_escape_string($_POST['activity_location']);
    $patients = $conn->real_escape_string($_POST['patients']);

    // Insert new activity into the database
    $query = "INSERT INTO activities (activity_name, activity_date, activity_description, barangay_id, activity_time, activity_location, patients) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssisss', $activityName, $activityDate, $activityDescription, $barangayId, $activityTime, $activityLocation, $patients);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'failure';
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>
