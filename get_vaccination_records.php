<?php
// Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '12345';
$db = 'immuni_track';  // Database name

// Ensure these variables are defined
if (empty($username) || empty($password) || empty($db_name)) {
    die("Database connection parameters are not set.");
}

// Create connection
$conn = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parent number from the URL parameters
$parent_number = $_GET['parent_number'];

// Prepare SQL query to get user and vaccination records
$sql = "SELECT children.name AS child_name, vaccinations.vaccine_name, vaccinations.date
        FROM children 
        JOIN vaccinations ON children.id = vaccinations.child_id 
        WHERE children.parent_number = ?";

// Prepare and bind
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $parent_number); // Assuming parent_number is a string

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

$vaccine_records = [];

// Fetch data and store in an array
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vaccine_records[] = $row; // Store each record in the array
    }
} else {
    echo json_encode([]); // Return an empty JSON array if no records found
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json'); // Set the content type to JSON
echo json_encode($vaccine_records); // Output the JSON data
?>
