<?php
session_start();
require 'db_connection.php'; // Ensure you have this file to connect to your database

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $reenterPassword = $_POST['reenterPassword'];

    // Check if new passwords match
    if ($newPassword !== $reenterPassword) {
        $_SESSION['error_message'] = "New password and re-entered password do not match.";
        header('Location: user-info.php'); // Redirect back to the user info page
        exit();
    }

    // Assuming user's email is stored in the session for authentication
    $email = $_SESSION['email'];

    // Retrieve current password hash from database
    $query = "SELECT password FROM usertable WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verify the current password
        if (password_verify($currentPassword, $row['password'])) {
            // Hash the new password
            $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password in the database
            $updateQuery = "UPDATE usertable SET password = ? WHERE email = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $hashedNewPassword, $email);

            if ($updateStmt->execute()) {
                $_SESSION['success_message'] = "Password updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating password.";
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect.";
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
    }

    $stmt->close();
    $conn->close();

    header('Location: user-info.php'); // Redirect back to the user info page
    exit();
}
?>
