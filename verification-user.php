<?php 
session_start(); // Start the session

// Database connection settings
$host = 'localhost';        // Database host
$db = 'immuni_track';       // Database name
$user = 'root';             // Database username
$pass = '12345';            // Database password

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php'); // Redirect to login if not logged in
    exit();
}

// Function to send verification code via email
function sendVerificationCode($email, $code) {
    $subject = "Your Verification Code";
    $message = "Your verification code is: " . $code;
    $headers = "From: no-reply@yourdomain.com"; // Change this to your desired sender email

    return mail($email, $subject, $message, $headers);
}

// Handle verification form submission
if (isset($_POST['verify'])) {
    $verificationCode = trim($_POST['verification_code']); // Trim whitespace
    $email = $_SESSION['email'];

    $errors = [];

    // Check if the verification code is provided
    if (empty($verificationCode)) {
        $errors[] = "Verification code is required.";
    } else {
        // Verify code in the database and check expiry
// Verify code in the database and check expiry
$query = "SELECT * FROM verification_codes WHERE email = ? AND code = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email, $verificationCode]);
$verificationRecord = $stmt->fetch();

// Debugging: output all records for the email
$allCodesQuery = "SELECT code, expires_at FROM verification_codes WHERE email = ?";
$allCodesStmt = $pdo->prepare($allCodesQuery);
$allCodesStmt->execute([$email]);
$allCodes = $allCodesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Debug: All records for $email:<br>";
foreach ($allCodes as $record) {
    echo "Code: {$record['code']}, Expires At: {$record['expires_at']}<br>";
}

if (!$verificationRecord) {
    $errors[] = "Invalid or expired verification code.";
} else {
    header('Location: dashboard.php');
    exit();
}

        
    }
}

// Handle resend verification code
if (isset($_POST['resend_code'])) {
    $email = $_SESSION['email'];
    $verificationCode = rand(100000, 999999); // Generate a new 6-digit code

    // Store or update the code in the database with REPLACE INTO
    $query = "REPLACE INTO verification_codes (email, code, created_at, expires_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email, $verificationCode]);

    // Send the code
    if (sendVerificationCode($email, $verificationCode)) {
        echo "<div class='alert alert-success text-center'>Verification code sent to your email.</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Failed to send verification code.</div>";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Account</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .container {
            margin-top: 70px;
        }
        .icon-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .icon-container img {
            width: 100%; /* Full width of the parent container */
            height: auto; /* Maintain aspect ratio */
            max-width: 150px; /* Limit maximum width */
            border-radius: 50%; /* Circle shape */
            object-fit: cover; /* Cover the circle without distortion */  
            animation: pulse 1.5s infinite; /* Add pulse animation */
        }

        /* Define pulse animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }

        p.text-center {
            font-size: 0.95rem; /* Slightly smaller than the heading */
            color: #555; /* Softer color for the paragraph */
            margin-bottom: 20px; /* Adds space below the text */
            text-align: center; /* Center align the text */
        }

        h2 {
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 1.25rem; /* Reduced from 1.5rem to 1.25rem for smaller size */
        }

        .alert {
            margin-bottom: 15px;
            font-size: 0.9rem; /* Smaller alert text size */
        }

        .form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .form .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            box-shadow: none;
            margin-bottom: 15px;
            font-size: 0.9rem; /* Smaller input text size */
        }

        .form .form-control:focus {
            border-color: #007bff;
            box-shadow: none;
        }

        .form .button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 8px 12px; /* Reduced button padding */
            font-size: 0.9rem; /* Adjusted font size */
            flex-grow: 1; /* Makes button size flexible based on text */
            width: auto; /* Allow button width to adjust dynamically */
            white-space: nowrap; /* Prevents the button text from wrapping */
        }

        .form .button:hover {
            background-color: #0056b3;
        }

        .timer {
            margin-left: 15px; /* Space between button and timer */
            font-weight: bold;
            min-width: 60px; /* Ensures timer width is consistent */
            text-align: center; /* Center text inside the timer */
        }

        .form .resend-button {
            background-color: #007bff; /* Blue color for Resend button */
            color: #fff; /* White text */
            border: none; /* No border */
            cursor: pointer; /* Pointer cursor */
            font-weight: 600; /* Bold text */
            padding: 8px 12px; /* Same padding as Verify button */
            font-size: 0.9rem; /* Font size similar to Verify button */
            width: 100%; /* Full width for Resend Code button */
            text-align: center; /* Center the text */
            white-space: nowrap; /* Prevent text wrapping */
        }

        .form .resend-button:hover {
            background-color: #0056b3; /* Darker shade on hover */
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
        }

        @media (max-width: 767px) {
            .col-md-4 {
                width: 100%;
                padding: 0;
            }
            .form {
                margin: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 offset-md-4 form">
                <div class="icon-container">
                    <img src="images/immunitrack.png" alt="Icon"> <!-- Updated icon image path -->
                </div>
                <h2 class="text-center">Verify Your Account</h2>
                <p class="text-center">
                    We've sent a verification code to your email. Please enter the code below to verify your account.
                </p>
                <?php
                if (isset($errors) && count($errors) > 0) {
                    ?>
                    <div class="alert alert-danger text-center">
                        <ul>
                        <?php
                        foreach ($errors as $showerror) {
                            echo "<li>$showerror</li>";
                        }
                        ?>
                        </ul>
                    </div>
                    <?php
                }
                ?>
                <form action="verification-user.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <input class="form-control" type="text" name="verification_code" placeholder="Enter Verification Code" required>
                    </div>
                    <div class="form-group d-flex align-items-center">
                        <input class="form-control button" type="submit" name="verify" value="Verify">
                        <div class="timer" id="timer">02:00</div> <!-- Timer display -->
                    </div>
                </form>
                <form action="verification-user.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <input class="form-control resend-button" type="submit" name="resend_code" value="Resend Code">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Timer functionality (example for 2 minutes countdown)
        let time = 120; // 2 minutes in seconds
        const timerElement = document.getElementById('timer');
        
        const interval = setInterval(() => {
            if (time <= 0) {
                clearInterval(interval);
                timerElement.textContent = "Time's up!";
            } else {
                const minutes = Math.floor(time / 60);
                const seconds = time % 60;
                timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                time--;
            }
        }, 1000);
    </script>
</body>
</html>
