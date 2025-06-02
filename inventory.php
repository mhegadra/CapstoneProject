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
    
    // Set PDO error mode to exception to handle errors gracefully
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, output the error message
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Get the user's email from the session
$email = $_SESSION['email'];

// Fetch the user's details and barangay ID from the database
$query = "SELECT u.email, b.barangay_id 
          FROM usertable u
          JOIN barangay b ON u.id = b.user_id 
          WHERE u.email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Extract the user's barangay ID and email initial
    $barangay_id = $user['barangay_id'];
    $initial = strtoupper(substr($user['email'], 0, 1));

    // Generate the profile image URL with the initial
    $profileImageUrl = "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff";
} else {
    // If the user is not found, redirect to the login page
    header('Location: login-user.php');
    exit();
}

// Fetch vaccine inventory data for the user's barangay, excluding HPV
$query = "SELECT vaccine_name, stock 
          FROM inventory 
          WHERE barangay_id = ? AND vaccine_name != 'Human Papillomavirus vaccine (HPV)'";

$stmt = $pdo->prepare($query);
$stmt->execute([$barangay_id]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>ImmuniTrack - Inventory</title>
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

    .table-data {
        margin-top: 20px;
        display: flex;
        justify-content: center;
    }

    table {
        width: 100%;
        max-width: 990px; /* You can adjust this value based on your layout */
        border-collapse: collapse;
        background-color: white;
        border-radius: 8px;
        margin: auto; /* Centers the table */
        padding: 20px; /* Adds padding around the table */
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left; /* Center align the text */
        vertical-align: top;
        white-space: normal;
    }

    th {
        background-color: #3498db;
        color: white;
        font-weight: bold;
    }

    td a {
        color: #2980b9;
        text-decoration: none;
        transition: color 0.3s;
    }

    td a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        table {
            font-size: 14px; /* Smaller font size for mobile devices */
        }
    }
    /* Sidebar Styling for Active Menu Items */

/* Active Dashboard Item */
#sidebar .side-menu li.active a {
    background-color: #4CAF50;  /* Green background for active Dashboard item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: white;  /* White text for the Dashboard label */
}

/* Hover state for active Dashboard link */
#sidebar .side-menu li.active a:hover {
    background-color: #388E3C;  /* Darker green on hover */
}

/* Active Analytics Item */
#sidebar .side-menu li.active a {
    background-color: #4CAF50;  /* Green background for active Analytics item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: white;  /* White text for the Analytics label */
}

/* Hover state for active Analytics link */
#sidebar .side-menu li.active a:hover {
    background-color: #388E3C;  /* Darker green on hover */
}

/* Active Calendar Item */
#sidebar .side-menu li.active a {
    background-color: #4CAF50;  /* Green background for active Calendar item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: white;  /* White text for the Calendar label */
}

/* Hover state for active Calendar link */
#sidebar .side-menu li.active a:hover {
    background-color: #388E3C;  /* Darker green on hover */
}

/* Active Inventory Item */
#sidebar .side-menu li.active a {
    background-color: #4CAF50;  /* Green background for active Inventory item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: white;  /* White text for the Inventory label */
}

/* Hover state for active Inventory link */
#sidebar .side-menu li.active a:hover {
    background-color: #388E3C;  /* Darker green on hover */
}
main {
    background-color: #D8EFD3; /* Set the desired background color */
    padding: 20px; /* Optional: Adds padding around the content */
    border-radius: 10px; /* Optional: Rounds the corners of the main section */
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); /* Optional: Adds shadow for depth */
}
main {
        background-color: #D8EFD3; /* Light green background color */
        padding: 20px; /* Adds padding around the content */
        border-radius: 10px; /* Rounds the corners */
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); /* Shadow for depth */
        margin: 20px; /* Adds spacing from surrounding elements */
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
        <!-- Active Dashboard item with styling -->
        <li class="">
            <a href="dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <!-- Active Calendar item with styling -->
        <li class="">
            <a href="calendar.php">
                <i class='bx bxs-calendar-event'></i>
                <span class="text">Calendar</span>
            </a>
        </li>
        <!-- Active Analytics item with styling -->
        <li class="">
            <a href="analytics.php">
                <i class='bx bxs-doughnut-chart'></i>
                <span class="text">Analytics</span>
            </a>
        </li>
        <!-- Active Inventory item with styling -->
        <li class="active">
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
        <div class="head-title">
        <div class="left">
            <h2 class="normal-weight"> </h2>
            <ul class="breadcrumb">
                <!-- Removed the breadcrumb -->
            </ul>
        </div>
    </div>
    <div class="table-data">
    <table>
        <thead>
            <tr>
                <th>Vaccine Name</th>
                <th>Details</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $item): ?>
            <tr>
                <td>
                    <a href="#" class="vaccine-link" data-name="<?php echo htmlspecialchars($item['vaccine_name']); ?>">
                        <?php echo htmlspecialchars($item['vaccine_name']); ?>
                    </a>
                </td>
                <td>
                <?php
// Hardcoded vaccine details based on the vaccine name
switch ($item['vaccine_name']) {
    case 'Bacille Calmette-Guérin vaccine (BCG)':
        echo 'Given at birth to prevent tuberculosis.';
        break;
    case 'Hepatitis B vaccine (HBV)':
        echo 'Given at birth to prevent Hepatitis B.';
        break;
    case 'Oral Polio Vaccine (OPV)':
        echo 'Given at 1 month, 2 months, and 3 months to prevent polio (oral).';
        break;
    case 'Pentavalent Vaccine':
        echo 'Given at 1 month, 2 months, and 3 months to protect against diphtheria, tetanus, pertussis, Hib, and hepatitis B.';
        break;
    case 'Inactivated Polio Vaccine (IPV)':
        echo 'Given at 3 months to prevent polio (injection).';
        break;
    case 'Pneumococcal conjugate vaccine (PCV)':
        echo 'Given at 1 month, 2 months, and 3 months to prevent pneumococcal diseases.';
        break;
    case 'Measles-Mumps-Rubella vaccine (MMR)':
        echo 'Given at 9 months to protect against measles, mumps, and rubella.';
        break;
    default:
        echo 'No details available.';
}
?>

</td>

                <td><?php echo htmlspecialchars($item['stock']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
