<?php 
session_start(); // Start the session

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php'; // Adjust the path as necessary

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

// Check if the user is already logged in
if (isset($_SESSION['email'])) {
    if ($_SESSION['email'] === 'admin@gmail.com') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// Function to send verification code via email
function sendVerificationCode($email, $verificationCode) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP(); 
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true; 
        $mail->Username = 'immunitrack2024@gmail.com'; // SMTP username
        $mail->Password = 'ebxt abwy pwni gwyd'; // Gmail App Password
        $mail->SMTPSecure = 'tls'; 
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('immunitrack2024@gmail.com', 'ImmuniTrack');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true); 
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "Your verification code is: <strong>$verificationCode</strong>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

// Inside the login form submission logic
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $errors = []; 

    if (empty($email) || empty($password)) {
        $errors[] = "Email and Password are required.";
    } else {
        // Hardcoded admin credentials for simplicity
        $adminEmail = 'admin@gmail.com';
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);

        if ($email === $adminEmail && password_verify($password, $adminPassword)) {
            $_SESSION['email'] = $email;
            header('Location: admin-dashboard.php');
            exit();
        } else {
            // Use parameterized queries to prevent SQL injection
            $query = "SELECT * FROM usertable WHERE email = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['email'] = $email;

                // Generate and store verification code
                $verificationCode = rand(100000, 999999); 
                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                // Store the verification code in the database
                $query = "REPLACE INTO verification_codes (email, code, created_at, expires_at) VALUES (?, ?, NOW(), ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$email, $verificationCode, $expiresAt]);

                // Send the verification code via email
                if (sendVerificationCode($email, $verificationCode)) {
                    echo "<div class='alert alert-success text-center'>Verification code sent to your email.</div>";
                } else {
                    echo "<div class='alert alert-danger text-center'>Failed to send verification code.</div>";
                }

                // Redirect to the verification page
                header('Location: verification-user.php');
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
        max-width: 160px; /* Limit maximum width */
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

    h2 {
        margin-bottom: 20px;
        font-weight: 600;
    }

    p {
        margin-bottom: 20px;
    }

    .alert {
        margin-bottom: 20px;
    }

    .form {
        background: #fff; /* Keep the form background white */
        padding: 30px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .form .form-control {
        border-radius: 4px;
        border: 2px solid #ddd;
        box-shadow: none;
        margin-bottom: 15px;
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
        padding: 10px 20px;
    }

    .form .button:hover {
        background-color: #0056b3;
    }

    .form .link {
        margin-top: 15px;
    }

    .form .link a {
        color: #007bff;
    }

    .form .link a:hover {
        text-decoration: underline;
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
            margin: 10px;
        }
    }
</style>


</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-4 offset-md-4 form login-form">
            <div class="icon-container">
                <img src="images/immunitrack.png" alt="Icon">
            </div>
            <form action="login-user.php" method="POST" autocomplete="off">
                <p class="text-center">Login with your email and password.</p>
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
                <div class="form-group">
                    <input class="form-control" type="email" name="email" placeholder="Email Address" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
                <div class="form-group">
                    <input class="form-control" type="password" id="password" name="password" placeholder="Password" required>
                    <input type="checkbox" id="showPassword" onclick="togglePassword()">
                    <label for="showPassword">Show Password</label>
                </div>
                <div class="link forget-pass text-left"><a href="forgot-password.php">Forgot password?</a></div>
                <div class="form-group"><br>
                    <input class="form-control button" type="submit" name="login" value="Login">
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var showPasswordCheckbox = document.getElementById('showPassword');
            passwordField.type = showPasswordCheckbox.checked ? 'text' : 'password';
        }
    </script>
</body>
</html>