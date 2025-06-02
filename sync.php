<?php
// Determine if running on localhost
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost'); // Set to true if running locally

if ($is_local) {
    // Local database connection settings
    $servername = "localhost"; // Localhost
    $username = "root"; // Local username
    $password = "12345"; // Local password
    $dbname = "immuni_track"; // Local database name
} else {
    // Remote database connection settings
    $servername = "sql211.infinityfree.com"; // Remote server name
    $username = "if0_37637558"; // Remote username
    $password = "Y2iL85uzkjZaYT1"; // Remote password
    $dbname = "if0_37637558_immuni_track"; // Remote database name
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Log the connection status
echo $is_local ? "Connected to local database successfully.<br>" : "Connected to remote database successfully.<br>";

// Syncing the `children` table
$local_conn = new mysqli("localhost", "root", "12345", "immuni_track"); // Local connection for data fetching

if ($local_conn->connect_error) {
    die("Local connection failed: " . $local_conn->connect_error);
}

// Fetch records from local database
$local_result = $local_conn->query("SELECT * FROM children");

if (!$local_result) {
    die("Local query failed: " . $local_conn->error);
}

$total_records = $local_result->num_rows;
echo "Total records found in local database: $total_records.<br>";

$count = 0; // To keep track of successfully synced records

while ($row = $local_result->fetch_assoc()) {
    // Insert into remote database if not exists or update if exists
    $stmt = $conn->prepare("INSERT INTO children (id, parent_id, first_name, last_name) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE parent_id=?, first_name=?, last_name=?");
    if ($stmt) {
        $stmt->bind_param("iissssi", $row['id'], $row['parent_id'], $row['first_name'], $row['last_name'], $row['parent_id'], $row['first_name'], $row['last_name']);
        if ($stmt->execute()) {
            $count++;
        } else {
            echo "Failed to execute statement for ID: {$row['id']} - Error: " . $stmt->error . "<br>";
        }
    } else {
        echo "Failed to prepare statement for ID: {$row['id']} - Error: " . $conn->error . "<br>";
    }
}

// Log the total number of records synced
echo "Total records synchronized: $count.<br>";

// Close connections
$local_conn->close();
$conn->close();
?>
