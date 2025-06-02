<?php
include 'db_connection.php';

$barangay_id = $_SESSION['barangay_id']; // Use the barangay_id of the logged-in user
$sql = "SELECT activity_name, activity_date, activity_description FROM activities WHERE barangay_id = ? ORDER BY activity_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $barangay_id);
$stmt->execute();
$result = $stmt->get_result();

$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

echo json_encode(['activities' => $activities]);

$stmt->close();
$conn->close();
?>
