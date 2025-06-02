<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Database connection settings
$host = 'localhost';        // Database host, usually 'localhost'
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

    // Use the temporary password for display purposes
    $temporaryPassword = 'ImmuniTrack2024'; // Temporary password

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ImmuniTrack - User Information</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
    <style>
        #sidebar .brand {
    text-align: center;
}

#sidebar .brand .text-box {
    background-color: #ffffff; /* Set the background color of the box */
    padding: 5px 10px; /* Add padding inside the box */
    border-radius: 5px; /* Rounded corners for the box */
    display: inline-block; /* Make the box wrap the text */
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Optional: shadow for depth */
}

#sidebar .brand .text {
    font-size: 20px;
    color: #4CAF50; /* Text color */
    letter-spacing: 1px;
    line-height: 1;
    text-transform: uppercase;
    margin-left: 5px;
}

/* Pulse effect on the text */
.pulse-text {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
/* Apply Poppins font to the entire page */
body {
    font-family: 'Poppins', sans-serif;
}

/* User Info Container */
.user-info-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    border: none;
    display: flex;
    flex-direction: column;
}

/* Two-column layout for user info row */
.user-info-row {
    display: flex;
    justify-content: space-between;
}

/* Left and right column styling */
.left-column, .right-column {
    width: 48%; /* Ensure columns take equal space */
}

/* Align everything to the left */
.user-info-container h2,
.user-info-container label {
    text-align: left;
}

.user-info-container .info-group {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.user-info-container .info-group label {
    display: block;
    font-weight: normal;
    margin-right: 10px;
    width: 120px;
}

/* White background for email and password containers */
.email-container,
.password-container {
    background-color: #fff; /* White background */
    border-radius: 5px; /* Rounded corners */
    padding: 10px; /* Padding for content */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for better visibility */
}

/* Adjustments to align email and password field */
.email-container {
    display: flex;
    justify-content: space-between; /* Aligns text to the left and icon to the right */
    align-items: center;
}

/* Text and button for email */
.email-container p {
    margin: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.clipboard-button {
    cursor: pointer;
    font-size: 1.2em;
    color: black;
    margin-left: 10px;
}

/* Adjustments to align password field and icons */
.password-container {
    display: flex;
    justify-content: space-between; /* Aligns text input to the left and icons to the right */
    align-items: center;
    margin-bottom: 10px; /* Added margin to the bottom of the password section */
}

/* Input field adjustments */
.password-container input {
    border: none;
    padding: 8px;
    width: 100%;
    outline: none;
    box-sizing: border-box;
    background: transparent;
}


#error-message, #success-message {
    font-size: 0.9em; /* Same font size for both messages */
    margin-top: 5px;
    margin-bottom: 10px;
}

.message {
    font-size: 0.9em; /* Same font size for both messages */
    margin-top: 5px;
    margin-bottom: 10px;
}


/* Password icons */
.password-container i {
    cursor: pointer;
    font-size: 1.2em;
    margin-left: 10px;
}

/* Initially show the 'show' icon, hide after click */
.bx-hide {
    display: none;
}

/* Button container spans both columns */
.button-container {
    text-align: center; /* Center align the button */
    margin-top: 20px;
}

/* Change Password Button */
.btn-primary {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em; /* Smaller text size */
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-primary:hover {
    background-color: #0056b3;
}

/* Icon inside Change Password Button */
.btn-primary i {
    font-size: 0.9em; /* Smaller icon size */
    margin-right: 4px; /* Minimal spacing between icon and text */
}

</style>

</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
    <a href="#" class="brand">
    <i class='bx bxs-injection'></i> <!-- Static icon -->
    <span class="text-box" style="padding: 5px 5px; border-radius: 5px; display: inline-block; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
        <span class="text pulse-text" style="font-size: 20px; color: #0D92F4; letter-spacing: 1px; line-height: 1; text-transform: uppercase; margin-left: 5px;">ImmuniTrack</span> <!-- Pulsing text with adjustments -->
    </span>
</a>
        <ul class="side-menu top">
            <li>
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="">
                <a href="calendar.php">
                    <i class='bx bxs-calendar-event'></i>
                    <span class="text">Calendar</span>
                </a>
            </li>
            <li>
                <a href="analytics.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class='bx bxs-package'></i>
                    <span class="text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="children.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Children Profile</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout-user.php" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <span id="date_now" class="d-none d-sm-block"><?php echo $currentDate; ?></span>
            <span id="current-time" class="clock ps-2 text-muted"></span>
            <form action="#"></form>
            <a href="user-info.php" class="profile">
                <img id="profile-image" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['email'][0]) ?>&background=random&color=fff" alt="Profile">
            </a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
    <!-- User Info Container -->
    <div class="user-info-container">
    <div class="user-info-row">
        <!-- Left Column: User Information -->
        <div class="left-column">
            <!-- User Name -->
            <div class="info-group">
                <label for="userName">Name:</label>
                <div class="email-container">
                    <p id="userName"><?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?></p>
                </div>
            </div>

            <!-- User Email -->
            <div class="info-group">
                <label for="userEmail">Email:</label>
                <div class="email-container">
                    <p id="userEmail"><?php echo htmlspecialchars($userEmail); ?></p>
                    <span class="clipboard-button" data-clipboard-target="#userEmail">
                        <i class="bx bxs-copy"></i>
                    </span>
                </div>
            </div>

            <!-- Temporary Password Display -->
            <div class="info-group">
                <label for="userPassword">Password:</label>
                <div class="password-container">
                    <input type="password" id="userPassword" value="<?php echo htmlspecialchars($temporaryPassword); ?>" readonly>
                    <i class='bx bx-show' id="showPassword"></i>
                    <i class='bx bx-hide' id="hidePassword" style="display:none;"></i>
                </div>
            </div>
        </div>

        <!-- Right Column: Password Change Form -->
        <div class="right-column">
            <form id="changePasswordForm" action="change-password.php" method="POST">
                <!-- Success/Error Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="success-message" style="color: green;">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']); // Clear the message after displaying
                        ?>
                    </div>
                <?php elseif (isset($_SESSION['error_message'])): ?>
                    <div class="error-message" style="color: red;">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']); // Clear the message after displaying
                        ?>
                    </div>
                <?php endif; ?>

<!-- Error message above Current Password -->
<small id="error-message" class="message" style="color: red; display: none;">Passwords do not match.</small>

<!-- Current Password -->
<div class="info-group">
    <label for="currentPassword">Current Password:</label>
    <div class="password-container">
        <input type="password" id="currentPassword" name="currentPassword" placeholder="Enter current password" required>
        <i class='bx bx-show' id="showCurrentPassword"></i>
        <i class='bx bx-hide' id="hideCurrentPassword" style="display:none;"></i>
    </div>
</div>

<!-- New Password -->
<div class="info-group">
    <label for="newPassword">New Password:</label>
    <div class="password-container">
        <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" required>
        <i class='bx bx-show' id="showNewPassword"></i>
        <i class='bx bx-hide' id="hideNewPassword" style="display:none;"></i>
    </div>
</div>

<!-- Re-enter Password -->
<div class="info-group">
    <label for="reenterPassword">Re-enter Password:</label>
    <div class="password-container">
        <input type="password" id="reenterPassword" name="reenterPassword" placeholder="Re-enter new password" required>
        <i class='bx bx-show' id="showReenterPassword"></i>
        <i class='bx bx-hide' id="hideReenterPassword" style="display:none;"></i>
    </div>

    <!-- Success message above Re-enter Password -->
    <small id="success-message" class="message" style="color: green; display: none;">Password changed successfully!</small>
</div>

<!-- Submit Button -->
<div class="button-container">
    <button type="submit" class="btn-primary">
        <i class='bx bx-lock-alt'></i> Change Password
    </button>
</div>


    </form>
</div>
</main>
    </section>
    <!-- CONTENT -->

    <script>
    // Clipboard.js setup
    new ClipboardJS('.clipboard-button');

    // Password toggle functionality
    const showPassword = document.getElementById('showPassword');
    const hidePassword = document.getElementById('hidePassword');
    const passwordInput = document.getElementById('userPassword');

    showPassword.addEventListener('click', () => {
        passwordInput.type = 'text';
        showPassword.style.display = 'none';
        hidePassword.style.display = 'inline';
    });

    hidePassword.addEventListener('click', () => {
        passwordInput.type = 'password';
        showPassword.style.display = 'inline';
        hidePassword.style.display = 'none';
    });

    // Password toggle functionality for each password field
    function togglePasswordVisibility(inputId, showId, hideId) {
        const input = document.getElementById(inputId);
        const showIcon = document.getElementById(showId);
        const hideIcon = document.getElementById(hideId);

        if (input.type === "password") {
            input.type = "text";
            showIcon.style.display = "none";
            hideIcon.style.display = "inline";
        } else {
            input.type = "password";
            showIcon.style.display = "inline";
            hideIcon.style.display = "none";
        }
    }

    // Add event listeners for toggling password visibility
    document.getElementById('showCurrentPassword').addEventListener('click', () => togglePasswordVisibility('currentPassword', 'showCurrentPassword', 'hideCurrentPassword'));
    document.getElementById('hideCurrentPassword').addEventListener('click', () => togglePasswordVisibility('currentPassword', 'showCurrentPassword', 'hideCurrentPassword'));

    document.getElementById('showNewPassword').addEventListener('click', () => togglePasswordVisibility('newPassword', 'showNewPassword', 'hideNewPassword'));
    document.getElementById('hideNewPassword').addEventListener('click', () => togglePasswordVisibility('newPassword', 'showNewPassword', 'hideNewPassword'));

    document.getElementById('showReenterPassword').addEventListener('click', () => togglePasswordVisibility('reenterPassword', 'showReenterPassword', 'hideReenterPassword'));
    document.getElementById('hideReenterPassword').addEventListener('click', () => togglePasswordVisibility('reenterPassword', 'showReenterPassword', 'hideReenterPassword'));

// Error message toggle on form submission
document.getElementById('changePasswordForm').addEventListener('submit', function (e) {
    const newPassword = document.getElementById('newPassword').value;
    const reenterPassword = document.getElementById('reenterPassword').value;
    const errorMessage = document.getElementById('error-message');

    if (newPassword !== reenterPassword) {
        e.preventDefault(); // Prevent form submission
        errorMessage.style.display = 'block'; // Show error message
    } else {
        errorMessage.style.display = 'none'; // Hide error message
    }
});

    // Function to update the time
    function updateTime() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila' };
        const timeString = now.toLocaleTimeString('en-US', options);
        document.getElementById('current-time').textContent = timeString;
    }

    setInterval(updateTime, 1000); // Update time every second
    updateTime(); // Initial call
</script>

</body>
</html>
