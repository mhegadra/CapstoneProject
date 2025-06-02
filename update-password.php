<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Database connection settings
$host = 'localhost';        // Database host
$db = 'immuni_track';       // Database name
$user = 'root';             // Database username
$pass = '12345';            // Database password

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
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
    $userFirstName = $user['first_name'];
    $userLastName = $user['last_name'];

    // Profile image URL
    $initial = strtoupper(substr($userEmail, 0, 1));
    $profileImageUrl = "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff";
} else {
    header('Location: login-user.php');
    exit();
}

// Handle password update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'];
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateQuery = "UPDATE usertable SET password = ? WHERE email = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        if ($updateStmt->execute([$hashedPassword, $email])) {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Password cannot be empty.']);
    }
    exit();
}

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
        body { font-family: 'Poppins', sans-serif; }
        .user-info-container { max-width: 400px; margin: 20px auto; padding: 20px; border: none; }
        .info-group { margin-bottom: 15px; display: flex; align-items: center; }
        .info-group label { display: block; font-weight: bold; margin-right: 10px; width: 120px; }
        .email-container, .password-container { background-color: #fff; border-radius: 5px; padding: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .email-container { display: flex; justify-content: space-between; align-items: center; }
        .clipboard-button { cursor: pointer; font-size: 1.2em; color: #ffc107; margin-left: 10px; }
        .password-container { display: flex; justify-content: space-between; align-items: center; }
        .password-container input { border: none; padding: 8px; width: 100%; outline: none; background: transparent; }
        .password-container i { cursor: pointer; font-size: 1.2em; margin-left: 10px; }
        .bx-hide { display: none; }
        .warning-message { display: none; color: #d9534f; font-weight: bold; margin-top: 10px; }
        .btn-primary { background-color: #007bff; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 1em; transition: background-color 0.3s ease; }
        .btn-primary:hover { background-color: #0056b3; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); padding-top: 60px; }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 400px; border-radius: 5px; }
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close-modal:hover, .close-modal:focus { color: black; text-decoration: none; cursor: pointer; }
        main { margin-top: 30px; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-injection'></i>
            <span class="text">ImmuniTrack</span>
        </a>
        <ul class="side-menu top">
            <li><a href="dashboard.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
            <li><a href="calendar.php"><i class='bx bxs-calendar-event'></i><span class="text">Calendar</span></a></li>
            <li><a href="analytics.php"><i class='bx bxs-doughnut-chart'></i><span class="text">Analytics</span></a></li>
            <li><a href="inventory.php"><i class='bx bxs-package'></i><span class="text">Inventory</span></a></li>
            <li><a href="children.php"><i class='bx bxs-group'></i><span class="text">Children Profile</span></a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="logout-user.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <a href="user-info.php" class="profile">
                <img id="profile-image" src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile">
            </a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="user-info-container">
                <div class="info-group">
                    <label for="userName">Name:</label>
                    <p id="userName"><?php echo htmlspecialchars($userFirstName . ' ' . $userLastName); ?></p>
                </div>
                <div class="info-group">
                    <label for="userEmail">Email:</label>
                    <div class="email-container">
                        <p id="userEmail"><?php echo htmlspecialchars($userEmail); ?></p>
                        <span class="clipboard-button" data-clipboard-target="#userEmail">
                            <i class="bx bxs-copy"></i>
                        </span>
                    </div>
                </div>
                <div class="info-group">
                    <label for="userPassword">Password:</label>
                    <div class="password-container">
                        <input type="password" id="userPassword" value="********" readonly>
                        <i class='bx bx-show' id="showPassword"></i>
                        <i class='bx bx-hide' id="hidePassword"></i>
                    </div>
                </div>
                <button class="btn-primary" id="changePasswordBtn">Change Password</button>
                <div id="passwordMessage" class="warning-message"></div>
            </div>

            <!-- Change Password Modal -->
            <div id="changePasswordModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Change Password</h2>
                    <div class="info-group">
                        <label for="newPassword">New Password:</label>
                        <input type="password" id="newPassword">
                    </div>
                    <div class="info-group">
                        <label for="confirmPassword">Confirm Password:</label>
                        <input type="password" id="confirmPassword">
                    </div>
                    <button class="btn-primary" id="submitNewPassword">Submit</button>
                    <div id="modalMessage" class="warning-message"></div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="script.js"></script>
    <script>
        // Clipboard functionality
        new ClipboardJS('.clipboard-button');

        // Show/hide password
        const showPassword = document.getElementById('showPassword');
        const hidePassword = document.getElementById('hidePassword');
        const passwordInput = document.getElementById('userPassword');

        showPassword.addEventListener('click', () => {
            passwordInput.type = 'text';
            showPassword.style.display = 'none';
            hidePassword.style.display = 'block';
        });

        hidePassword.addEventListener('click', () => {
            passwordInput.type = 'password';
            hidePassword.style.display = 'none';
            showPassword.style.display = 'block';
        });

        // Change password modal
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const closeModal = document.querySelector('.close-modal');
        const submitNewPassword = document.getElementById('submitNewPassword');
        const modalMessage = document.getElementById('modalMessage');
        const passwordMessage = document.getElementById('passwordMessage');

        changePasswordBtn.addEventListener('click', () => {
            changePasswordModal.style.display = 'block';
            modalMessage.textContent = ''; // Reset modal message
        });

        closeModal.addEventListener('click', () => {
            changePasswordModal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target == changePasswordModal) {
                changePasswordModal.style.display = 'none';
            }
        });

        // Submit new password
        submitNewPassword.addEventListener('click', () => {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (newPassword === '' || confirmPassword === '') {
                modalMessage.textContent = 'Both fields are required.';
                return;
            }

            if (newPassword !== confirmPassword) {
                modalMessage.textContent = 'Passwords do not match.';
                return;
            }

            // AJAX request to change password
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update-password.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                const response = JSON.parse(this.responseText);
                modalMessage.textContent = response.message;
                if (response.success) {
                    passwordMessage.textContent = 'Password updated successfully.';
                    passwordMessage.style.color = 'green';
                    passwordInput.value = '********'; // Reset password display
                    changePasswordModal.style.display = 'none'; // Close modal
                } else {
                    passwordMessage.textContent = response.message;
                }
            };
            xhr.send(`password=${encodeURIComponent(newPassword)}`);
        });
    </script>
</body>
</html>
