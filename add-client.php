<?php
session_start();

// Check if the user is logged in (Ensure that admin is logged in)
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Set the default timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Database connection settings
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Handle form submission to add a new client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['full_name'], $_POST['barangay_name'])) {
    // Sanitize and validate inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email address.';
        header('Location: admin-barangay.php');
        exit();
    }

    $password = password_hash("ImmuniTrack2024", PASSWORD_DEFAULT); // Default password

    // Split the full name into first and last names
    $full_name = trim($_POST['full_name']);
    $name_parts = explode(' ', $full_name, 2);
    $first_name = filter_var($name_parts[0], FILTER_SANITIZE_STRING);
    $last_name = isset($name_parts[1]) ? filter_var($name_parts[1], FILTER_SANITIZE_STRING) : '';

    $barangay_name = filter_var(trim($_POST['barangay_name']), FILTER_SANITIZE_STRING);

    // Create initials from first and last names
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $role = 'user'; // Set role as 'user'

    try {
        // Check if the email already exists
        $emailCheckStmt = $pdo->prepare("SELECT * FROM usertable WHERE email = ?");
        $emailCheckStmt->execute([$email]);
        if ($emailCheckStmt->fetch()) {
            $_SESSION['message'] = 'Email already exists. Please use a different email.';
            header('Location: admin-barangay.php');
            exit();
        }

        // Retrieve barangay_id or insert if not found
        $barangay_stmt = $pdo->prepare("SELECT * FROM barangay WHERE barangay_name = ?");
        $barangay_stmt->execute([$barangay_name]);
        $barangay = $barangay_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$barangay) {
            // Insert barangay if it doesn't exist
            $insertBarangayStmt = $pdo->prepare("INSERT INTO barangay (barangay_name, completed_vaccinations, total_vaccinations, percentage_completed) VALUES (?, 0, 0, 0)");
            $insertBarangayStmt->execute([$barangay_name]);
            $barangay_id = $pdo->lastInsertId();
        } else {
            $barangay_id = $barangay['barangay_id'];
        }

        // Insert into usertable
        $insertUserQuery = "INSERT INTO usertable (first_name, last_name, email, barangay_id, password, status, role, initials, last_active) 
                            VALUES (:first_name, :last_name, :email, :barangay_id, :password, 'active', :role, :initials, NOW())";

        $userStmt = $pdo->prepare($insertUserQuery);
        $userStmt->bindParam(':first_name', $first_name);
        $userStmt->bindParam(':last_name', $last_name);
        $userStmt->bindParam(':email', $email);
        $userStmt->bindParam(':barangay_id', $barangay_id);
        $userStmt->bindParam(':password', $password);
        $userStmt->bindParam(':role', $role);
        $userStmt->bindParam(':initials', $initials);

        if ($userStmt->execute()) {
            $user_id = $pdo->lastInsertId(); // Get the ID of the newly inserted user

            // Update barangay with user_id
            $updateBarangayStmt = $pdo->prepare("UPDATE barangay SET user_id = ? WHERE barangay_id = ?");
            $updateBarangayStmt->execute([$user_id, $barangay_id]);

            // Update vaccination statistics
            $updateBarangayStatsQuery = "UPDATE barangay 
                                         SET total_vaccinations = total_vaccinations + 1, 
                                             percentage_completed = IF(total_vaccinations > 0, 
                                             (completed_vaccinations / total_vaccinations) * 100, 0) 
                                         WHERE barangay_id = ?";
            $updateStatsStmt = $pdo->prepare($updateBarangayStatsQuery);
            $updateStatsStmt->execute([$barangay_id]);

            // Send welcome email
            $subject = "Welcome to ImmuniTrack!";
            $message = "Hello $first_name,\n\nThank you for joining ImmuniTrack!\n\nBest regards,\nImmuniTrack Team";
            $headers = "From: no-reply@immunitrack.com";
            if (!mail($email, $subject, $message, $headers)) {
                error_log("Failed to send email to $email");
            }

            $_SESSION['message'] = "Client added successfully!";
            header('Location: admin-barangay.php');
            exit();
        } else {
            error_log('Error executing user insert statement: ' . implode(", ", $userStmt->errorInfo()));
            $_SESSION['message'] = 'Error adding client. Please try again later.';
        }
    } catch (PDOException $e) {
        error_log('Error adding client: ' . $e->getMessage());
        $_SESSION['message'] = 'Error adding client. Please try again later.';
    }
}

$currentDate = date('l, d/m/Y');
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">
    <title>Add Client - ImmuniTrack</title>
    <style>
        .form-container {
            max-width: 400px;
            margin: auto;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: normal;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .form-container button {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .password-container {
            position: relative; /* Position for absolute elements */
        }
        .toggle-password {
            position: absolute;
            right: 20px; /* Positioning the icon */
            top: 37%; /* Centering vertically */
            transform: translateY(-50%); /* Adjust vertical alignment */
            cursor: pointer; /* Pointer cursor for clickable icon */
            color: #007bff; /* Color for the icon */
        }
        /* Active Dashboard Item */
#sidebar .side-menu li.active a {
    background-color: #4A628A;  /* Custom blue background for active item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: white;  /* White text for the label */
}

/* Hover state for active Dashboard link */
#sidebar .side-menu li.active a:hover {
    background-color: #3a4e6c;  /* Darker blue on hover */
}

    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
    <a href="#" class="brand">
    <i class='bx bxs-injection'></i> <!-- Static icon -->
    <span class="text-box" style="padding: 5px 5px; border-radius: 5px; display: inline-block; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);">
    <span class="text pulse-text" style="font-size: 20px; color: white; letter-spacing: 1px; line-height: 1; text-transform: uppercase; margin-left: 5px;">ImmuniTrack</span> <!-- Pulsing text with adjustments -->
    </span>
</a>
        <ul class="side-menu top">
        <li class="">
        <a href="admin-dashboard.php">
            <i class='bx bxs-dashboard'></i>
            <span class="text">Dashboard</span>
        </a>
            <li>
                <a href="admin-analytics.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <li class="active">
                <a href="admin-barangay.php">
                    <i class='bx bxs-home'></i>
                    <span class="text">Barangay</span>
                </a>
            </li>
            <li>
                <a href="admin-inventory.php">
                    <i class='bx bxs-package'></i>
                    <span class="text">Inventory</span>
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
        <!-- MAIN -->
        <main>
            <div class="form-container">
            <form method="POST" action="">
    <label for="full_name">Full Name:</label>
    <input type="text" name="full_name" required>

    <label for="barangay_name">Barangay Name:</label>
    <input type="text" name="barangay_name" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>
    
    <label for="password">Password:</label>
    <div class="password-container">
        <input type="password" id="password" name="password" value="ImmuniTrack2024" required readonly>
        <i class='bx bx-show toggle-password' id="togglePassword"></i>
    </div>
    <button type="submit">Add Client</button>
</form>



            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // Toggle the icon class
            this.classList.toggle('bx-show');
            this.classList.toggle('bx-hide');
        });

        // Display current time
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
