<?php
session_start(); // Start the session

// Check if the user is logged in
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
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, output the error message
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Fetch the barangay ID from the URL
$barangay_id = isset($_GET['barangay_id']) ? intval($_GET['barangay_id']) : 0;

// Fetch the barangay name
$barangay_query = "SELECT barangay_name FROM barangay WHERE barangay_id = ?";
$barangay_stmt = $pdo->prepare($barangay_query);
$barangay_stmt->execute([$barangay_id]);
$barangay = $barangay_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all children associated with the selected barangay
$query = "SELECT c.id, c.first_name, c.last_name, c.date_of_birth, p.parent_name
          FROM children c
          JOIN parents p ON c.parent_id = p.id
          WHERE c.barangay_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$barangay_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the current date
$currentDate = date('l, d/m/Y');

// Count the total number of children
$totalChildren = count($children);
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

    <title>Registered Children - Barangay</title>
    <style>
        .children-list {
            margin-top: 20px;
            padding: 0;
            border-radius: 5px;
            display: grid; /* Use CSS Grid */
            grid-template-columns: repeat(5, minmax(150px, 1fr)); /* 5 columns, each item takes min 150px, but can grow */
            gap: 15px; /* Add gap between items */
            list-style: none; /* Remove bullet points */
        }

        .child-item {
            border: 1px solid #ced4da;
            padding: 10px 15px;
            background-color: #ffffff;
            border-radius: 8px;
            transition: background-color 0.3s, box-shadow 0.3s;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .child-item:hover {
            background-color: #f1f1f1;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .child-item .name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .child-item .info {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .header-text {
            font-size: 24px; /* Header font size */
            font-weight: normal; /* Header font weight */
            color: #333; /* Header font color */
            margin: 20px 0; /* Space around header */
            text-align: left; /* Center the header */
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
            <li> <!-- New Client Section -->
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
            <span class="date-time" id="date_now"><?php echo $currentDate; ?></span>
            <span id="current-time" class="clock ps-2 text-muted"></span>
        </nav>
        <!-- NAVBAR -->

        <main>
            <!-- Header Text -->
            <div class="header-text">Number of Children Registered in Barangay <?= htmlspecialchars($barangay['barangay_name']); ?>: <?= $totalChildren; ?></div>
            
            <ul class="children-list">
                <?php if ($totalChildren > 0): ?>
                    <?php foreach ($children as $child): ?>
                        <li class="child-item">
                            <div class="name"><?= htmlspecialchars($child['first_name']) . ' ' . htmlspecialchars($child['last_name']) ?></div>
                            <div class="info">Date of Birth: <?= htmlspecialchars($child['date_of_birth']) ?></div>
                            <div class="info">Parent: <?= htmlspecialchars($child['parent_name']) ?></div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No registered children found for this barangay.</p>
                <?php endif; ?>
            </ul>
        </main>
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

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call to display time immediately
    </script>
</body>
</html>
