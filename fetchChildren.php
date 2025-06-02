<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine if running locally or on a remote server
$is_local = true; // Set to true for local testing, false for production

if ($is_local) {
    // Local connection settings
    $servername = "localhost"; // For local development
    $username = "root"; // Local username
    $password = "12345"; // Local password
    $dbname = "immuni_track"; // Local database name
} else {
    // Remote connection settings for InfinityFree
    $servername = "sql211.infinityfree.com"; // Your database server name
    $username = "if0_37658390"; // Your database username
    $password = "LcuXqjdkTV07HiL"; // Your database password
    $dbname = "if0_37658390_immuni_track"; // Your full database name
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch child details and vaccination records for all parents
$sql = "
    SELECT 
        p.id AS parent_id,
        p.parent_name,
        p.address,
        p.phone_number,
        c.id AS child_id,
        c.first_name,
        c.last_name,
        v.vaccine_name,
        v.vaccination_date
    FROM 
        parents AS p
    JOIN 
        children AS c ON p.id = c.parent_id
    LEFT JOIN 
        vaccination_records AS v ON c.id = v.child_id
";

// Execute the query
$result = $conn->query($sql);

// Check for query execution errors
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch results into an array
$children = array();
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

// Close the connection
$conn->close();

// Set header for JSON response
header('Content-Type: application/json');
echo json_encode($children);
?>
