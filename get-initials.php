<?php
// Assuming you have a connection to your database
include 'db_connection.php'; // Your database connection file

header('Content-Type: application/json');

$userId = $_SESSION['user_id']; // Or however you identify the current user

$sql = "SELECT initials FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode(['initials' => $user['initials']]);
} else {
    echo json_encode(['initials' => '']);
}
?>
