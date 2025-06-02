<?php 
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Database connection settings
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, output the error message and exit
    die('Connection failed: ' . $e->getMessage());
}

// Update last active time for the user
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE usertable SET last_active = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Set the default timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Fetch all users from the usertable excluding admin users
$query = "SELECT id, first_name, last_name, email, last_active FROM usertable WHERE role != 'admin'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the current date
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

    <title>ImmuniTrack - Clients Management</title>
    <style>
.client-list {
    margin-top: 20px;
    max-height: 400px; /* You can adjust this value as needed */
    padding: 10px;
    border-radius: 5px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.client-box {
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 220px;
    padding: 20px;
    box-sizing: border-box;
    text-align: center;
    transition: transform 0.3s ease;
}

.client-box:hover {
    transform: translateY(-5px);
}

.client-box .name {
    font-size: 17px;
    font-weight: bold;
    margin-top: 15px;
}

.client-box .info {
    font-size: 11px;
    color: #6c757d;
    text-align: center;
    line-height: 1.5;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100%;
}

.client-box .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #f0f0f0;
    margin: 0 auto 15px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #007bff;
}

.client-box .status {
    font-size: 14px;
    margin-top: 10px;
}

.client-box .status span {
    font-weight: bold;
}

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
            <li>
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
            <li class="active"> <!-- Client Section -->
                <a href="admin-clients.php">
                    <i class='bx bxs-user-account'></i> <!-- You can change the icon as needed -->
                    <span class="text">Clients</span>
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
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="client-list">
                <?php foreach ($users as $user): ?>
                    <div class="client-box">
                        <div class="avatar">
                            <i class='bx bxs-user'></i> <!-- Circular user icon -->
                        </div>
                        <div class="name"><?= htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']) ?></div>
                        <div class="info"><?= htmlspecialchars($user['email']) ?></div>
                        <div class="status">
                            <?php 
                            if (!empty($user['last_active'])) {
                                $lastActiveTime = new DateTime($user['last_active']);
                                $currentTime = new DateTime();
                                $interval = $lastActiveTime->diff($currentTime);

                                if ($interval->i < 5 && $interval->h == 0 && $interval->d == 0) {
                                    echo '<span style="color: green;">Active</span>';
                                } else {
                                    echo '<span style="color: red;">Offline</span>';
                                }
                            } else {
                                echo '<span style="color: red;">Offline</span>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script>
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
