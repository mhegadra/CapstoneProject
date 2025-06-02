<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Database connection settings
$host = 'localhost';        // Database host
$db = 'immuni_track';       // The database name you created
$user = 'root';             // Your database username
$pass = '12345';            // Your database password

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Set PDO error mode to exception to handle errors gracefully
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, output the error message
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Get the user's email from the session
$email = $_SESSION['email'];

// Fetch the user's information from the database
$query = "SELECT email, password, first_name, last_name FROM usertable WHERE email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Extract user data
    $userEmail = $user['email'];
    $userFirstName = $user['first_name']; // Get first name
    $userLastName = $user['last_name'];   // Get last name

    // Extract the first letter of the email for profile image
    $initial = strtoupper(substr($userEmail, 0, 1));

    // Generate the profile image URL with the initial
    $profileImageUrl = "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff";
} else {
    // If the user is not found, redirect to the login page
    header('Location: login-user.php');
    exit();
}

// Format the current date
$currentDate = date('l, d/m/Y');

// Handle password update if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the new password
    $newPassword = $_POST['newPassword'];

    // Update the password in the database
    $query = "UPDATE usertable SET password = ? WHERE email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $email]);

    // Return success response
    echo json_encode(['success' => true]);
    exit();
}

// Output the current user's information (for form display, etc.)
?>

<!-- Include HTML for the change password modal -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <!-- Include your styles and scripts -->
</head>
<body>
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h2>Change Password</h2>
            <form id="changePasswordForm">
                <label for="newPassword">New Password:</label>
                <input type="password" id="newPassword" required>
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" required>
                <div id="passwordMessage" style="display: none; color: red;"></div>
                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('changePasswordModal');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const closeModal = document.getElementById('closeModal');

        changePasswordBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Form validation and submission
        const changePasswordForm = document.getElementById('changePasswordForm');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMessage = document.getElementById('passwordMessage');

        changePasswordForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                passwordMessage.textContent = 'Passwords do not match.';
                passwordMessage.style.display = 'block';
            } else {
                passwordMessage.style.display = 'none';

                // Prepare data to send to server
                const formData = new FormData();
                formData.append('newPassword', newPassword);

                // Send data to server using AJAX
                fetch('change-password.php', { // Adjust the URL if needed
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Password updated successfully!');
                        modal.style.display = 'none'; // Close the modal
                    } else {
                        passwordMessage.textContent = 'Error updating password.';
                        passwordMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    </script>
</body>
</html>
