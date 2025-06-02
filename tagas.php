<?php 
session_start();

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
 
// Retrieve barangay names and total registered children
$query = "
    SELECT b.barangay_name, b.barangay_id, COUNT(c.id) AS total_children
    FROM barangay b
    LEFT JOIN children c ON b.barangay_id = c.barangay_id
    GROUP BY b.barangay_name, b.barangay_id
";
$stmt = $pdo->query($query);
$barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);

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


    <title>ImmuniTrack - Health Workers</title>
    <style>
.barangay-box {
    background-color: #f5f9ff; /* Light blue background similar to the design */
    border: 1px solid #e0e0e0; /* Light gray border */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Softer shadow for depth */
    width: 220px; /* Fixed width similar to user card */
    padding: 10px;
    box-sizing: border-box;
    text-align: center; /* Center-align the text */
    transition: transform 0.3s ease;
    margin: 10px; /* Added margin around each box */
}


.barangay-box:hover {
    transform: translateY(-5px); /* Slight lift effect on hover */
}

.barangay-box .name {
    font-size: 18px; /* Larger font size for barangay name */
    font-weight: bold;
    margin-top: 15px; /* Space between avatar and name */
    color: #333;
}

.barangay-box .info {
    font-size: 14px; /* Slightly smaller text for manager info */
    color: #6c757d; /* Muted color for the info */
}

.barangay-box .icon {
    width: 60px;
    height: 60px;
    border-radius: 30px; /* Slightly rounded square */
    background-color: #e8f0ff; /* Light blue background for icon */
    margin: 0 auto 15px auto; /* Center the avatar with some space below */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #007bff; /* Example icon or text color for avatar */
}

.barangay-list {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); /* Flexible grid layout */
    gap: 5px; /* Increased space between boxes */
    justify-content: center;
    padding: 5px;
}



        .barangay-box {
    background-color: #ffffff; /* White background */
    border: 1px solid #e0e0e0; /* Light gray border similar to user cards */
    border-radius: 10px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Softer shadow for depth */
    width: 220px; /* Fixed width similar to user card */
    padding: 20px;
    box-sizing: border-box;
    text-align: center; /* Center-align the text */
    transition: transform 0.3s ease;
}

.barangay-box:hover {
    transform: translateY(-5px); /* Slight lift effect on hover */
}

.barangay-box .name {
    font-size: 18px; /* Larger font size for barangay name */
    font-weight: bold;
    margin-top: 15px; /* Space between avatar and name */
}

.barangay-box .info {
    font-size: 14px; /* Slightly smaller text for manager info */
    color: #6c757d; /* Muted color for the info */
}

.barangay-box .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%; /* Circular avatar */
    background-color: #f0f0f0; /* Placeholder background color */
    margin: 0 auto 15px auto; /* Center the avatar with some space below */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #007bff; /* Example icon or text color for avatar */
}
.barangay-box .status {
    font-size: 14px; /* Slightly larger than info text */
    margin-top: 10px; /* Margin above status */
}

.barangay-box .status span {
    font-weight: bold; /* Bold for the status text */
}



        .add-barangay {
            margin-top: 20px;
            background-color: #ffffff; /* Set to white for consistency */
            padding: 15px; /* Added padding for consistency */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 300px; /* Same max-width as barangay boxes */
            margin-left: auto; /* Center the box horizontally */
            margin-right: auto; /* Center the box horizontally */
            display: flex; /* Flexbox for centering */
            flex-direction: column; /* Stack elements vertically */
            align-items: center; /* Center items horizontally */
        }
        .add-barangay h2 {
            font-size: 16px;
            margin-bottom: 10px; /* Space between heading and button */
            cursor: pointer; /* Indicate that it's clickable */
        }
        .head-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .head-title .left {
            flex: 1;
        }
        .head-title .right {
            display: flex;
            align-items: center;
        }
        .search-bar {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .search-bar input[type="text"] {
            border: none;
            padding: 5px;
            background: none;
            outline: none;
            flex: 1;
        }
        .search-bar button {
            background-color: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
        }
        .search-bar button i {
            font-size: 18px;
            color: #007bff;
        }
        .date-time {
            margin-left: auto; /* Push to the right */
            padding: 0 15px; /* Optional padding */
            font-size: 14px; /* Adjust font size as needed */
            color: #333; /* Change text color if desired */
        }

        /* New styles for the button at the bottom right */
        .add-client-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
        }
        .add-client-button:hover {
            background-color: #0056b3;
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
.matrix-section {
    display: flex;
    justify-content: center; /* Centers horizontally */
    align-items: flex-start; /* Aligns the table closer to the top */
    height: auto;            /* Adjust height dynamically based on content */
    margin: 30px 0;          /* Add vertical spacing around the section */
    padding: 10px;           /* Add some padding for a better layout */
}

.matrix-table {
    width: auto;             /* Allows the table to resize based on content */
    max-width: 1200px;       /* Optional: limit maximum width */
    border-collapse: collapse;
    margin: 0 auto;          /* Center the table horizontally */
    table-layout: auto;      /* Allow table to adjust based on content */
}

.matrix-table th, .matrix-table td {
    border: 1px solid #ddd;
    padding: 8px 15px;
    text-align: left;
    min-width: 300px;        /* Optional: minimum width to prevent too small of a cell */
}

.matrix-table th {
    background-color: #1679AB; /* Apply color to table header */
    color: white;              /* Make text in the table header white */
}

.matrix-table tr:hover {
    background-color: #40A2E3; /* Apply color to table rows when hovered */
    cursor: pointer;
}

.head-title {
    margin-top: 50px; /* Adjust the value as needed */
    text-align: left; /* Align the heading to the left */
    padding-left: 20px; /* Optional: Add padding for spacing from the edge */
}

.head-title h2 {
    color: #40A2E3; /* Customize the text color */
    font-size: 1.1rem; /* Adjust font size */
    margin: 0; /* Reset default margins */
}

.hw-table-container {
    display: grid;
    grid-template-columns: 1fr; /* Single column for centering */
    justify-items: center; /* Center content horizontally */
    align-items: start; /* Align content at the top */
    margin-top: 5px; /* Add margin at the top */
}

.hw-table {
    background: #fff;
    box-shadow: 0 20px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    width: 80%; /* Set table container width */
}

.hw-table table {
    width: 100%;
    border-collapse: collapse;
}

.hw-table th, .hw-table td {
    text-align: center;
    padding: 15px;
    border-bottom: 1px solid #ddd;
}

.hw-table th {
    background-color: #007bff;
    color: white;
    text-transform: uppercase;
}

.hw-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.hw-table tr:hover {
    background-color: #f1f1f1;
}

.hw-table a {
    color: #000000; /* Change link color to black */
    text-decoration: none;
    font-weight: bold;
}

.hw-table a:hover {
    text-decoration: underline;
    color: #007bff; /* Optional: Add hover effect to revert to blue */
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
            <li><a href="admin-dashboard.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
            <li><a href="admin-analytics.php"><i class='bx bxs-doughnut-chart'></i><span class="text">Analytics</span></a></li>
            <li class="active"><a href="admin-barangay.php"><i class='bx bxs-home'></i><span class="text">Barangay</span></a></li>
            <li><a href="admin-inventory.php"><i class='bx bxs-package'></i><span class="text">Inventory</span></a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="logout-user.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
    <div class="head-title">
        <div class="left">
            <h2>Barangay Health Workers - Tagas</h2>
        </div>
    </div>

    <div class="main-content">
        <div class="hw-table-container">
            <div class="hw-table">
                <?php
                // Database connection
                $host = "localhost";
                $db = "immuni_track";
                $user = "root";
                $pass = "12345";

                // Create connection
                $conn = new mysqli($host, $user, $pass, $db);

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Query to fetch health workers' names and ids
                $sql = "SELECT id, first_name, last_name FROM usertable WHERE barangay_name ='Tagas'";
                $result = $conn->query($sql);

                // Check if rows exist
                if ($result->num_rows > 0) {
                    echo '<table>';
                    echo '<thead>';
                    echo '</thead>';
                    echo '<tbody>';

                    while ($row = $result->fetch_assoc()) {
                        $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                        $userId = $row['id'];

                        echo '<tr>';
                        echo '<td>' . $fullName . '</td>';
                        echo '<td><a href="records.php?id=' . $userId . '">View Records</a></td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo "<p>No health workers found.</p>";
                }

                // Close connection
                $conn->close();
                ?>
            </div>
        </div>
    </div>
</main>

    </section>


</body>
</html>