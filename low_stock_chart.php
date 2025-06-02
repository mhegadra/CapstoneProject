<?php


if (!isset($_SESSION['email'])) {
    // Ensure user is logged in
    header('Location: login-user.php');
    exit();
}

// Database connection details
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data from database
$sql = "SELECT b.barangay_name, i.vaccine_name, i.stock
        FROM inventory i
        INNER JOIN barangay b ON i.barangay_id = b.barangay_id
        WHERE i.stock <= 50"; // Assuming 50 is the threshold for low stock

$result = $conn->query($sql);

$barangayData = [];
$vaccineNames = []; // Initialize the array for vaccine names

try {
    // Query to fetch vaccine names with vaccine_id between 1 and 7
    $query = $pdo->query("SELECT DISTINCT vaccine_id, vaccine_name FROM inventory WHERE vaccine_id BETWEEN 1 AND 7");

    // Map vaccine IDs to their short names
    $shortNames = [
        1 => 'BCG',
        2 => 'HBV',
        3 => 'Pentavalent',
        4 => 'OPV',
        5 => 'PCV',
        6 => 'MMR',
        7 => 'IPV'
    ];

    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $vaccineId = $row['vaccine_id'];
        if (array_key_exists($vaccineId, $shortNames)) {
            $vaccineNames[] = $shortNames[$vaccineId]; // Use the short form of the vaccine name
        }
    }

    // Query to fetch stock levels for each barangay and vaccine
    $barangayQuery = $pdo->query("SELECT 
            inventory.barangay_id, 
            barangay.barangay_name, 
            inventory.vaccine_id, 
            SUM(inventory.stock) AS total_stock 
        FROM inventory 
        INNER JOIN barangay ON inventory.barangay_id = barangay.barangay_id 
        WHERE inventory.vaccine_id BETWEEN 1 AND 7 AND inventory.barangay_id IN (4, 5) 
        GROUP BY inventory.barangay_id, inventory.vaccine_id");

    while ($row = $barangayQuery->fetch(PDO::FETCH_ASSOC)) {
        $barangayId = $row['barangay_id'];
        $barangayName = $row['barangay_name'];
        $vaccineId = $row['vaccine_id'];
        $stock = $row['total_stock'];

        $barangayLabels[$barangayId] = $barangayName;

        if (!isset($barangayData[$barangayId])) {
            $barangayData[$barangayId] = array_fill(0, 7, 0); // Initialize array for each barangay
        }
        $barangayData[$barangayId][$vaccineId - 1] = $stock; // Map stock to the correct vaccine index
    }
} catch (PDOException $e) {
    // Handle database query errors
    echo "Database error: " . $e->getMessage();
    exit();
}

// Output variables for Chart.js
$vaccineNamesJson = json_encode($vaccineNames);
$barangayDataJson = json_encode($barangayData);
$barangayLabelsJson = json_encode($barangayLabels);

// Close connection
$conn->close();
?>