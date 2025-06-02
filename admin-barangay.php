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

// Retrieve barangay names and the total number of users with role = 'user' grouped by barangay
$query = "
    SELECT 
        b.barangay_name, 
        COUNT(u.id) AS total_health_workers
    FROM barangay b
    LEFT JOIN usertable u 
        ON b.barangay_id = u.barangay_id AND u.role = 'user'
    GROUP BY b.barangay_name
    ORDER BY b.barangay_name ASC
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

    <title>ImmuniTrack - Barangay Management</title>
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

    <section id="content">
    <!-- MAIN -->
    <main>
    <div class="matrix-section">
        <!-- Box Container with Flexbox Layout -->
        <div class="matrix-box">
    <table class="matrix-table">
        <thead>
            <tr>
                <th>Barangay</th>
                <th>Number of Health Workers</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (isset($barangays) && count($barangays) > 0): 
                foreach ($barangays as $barangay): ?>
                    <tr onclick="redirectToBarangay('<?php echo htmlspecialchars(strtolower(str_replace(' ', '', $barangay['barangay_name']))) ?>.php')">
                        <td><?php echo htmlspecialchars($barangay['barangay_name']); ?></td> <!-- Display barangay name -->
                        <td><?php echo (int)$barangay['total_health_workers']; ?></td> <!-- Display total health workers -->
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2">No barangays have been registered yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


            <!-- Right Side: Add New Client Button -->
            <div class="right-side">
                <button class="add-client-button" onclick="location.href='add-client.php'">
                    &#43; Add New Client
                </button>
            </div>
        </div> <!-- End of Box Container -->
    </div> <!-- End of matrix-section -->
</main>

</section>

<!-- Styling -->
<style>
/* Styling for the search form */
.search-form {
    display: flex;
    align-items: center;
    justify-content: center; /* Centers the form items */
    position: absolute; /* For precise positioning */
    top: 30px; /* Adjusted distance from the top to bring it down */
    right: 20px; /* Distance from the right */
    z-index: 1000; /* Ensure it stays on top */
    margin: 5px 0 10px 0; /* Adjust top, right, bottom, and left margins */
}

/* Styling for the search bar container */
.search-bar {
    display: flex;
    align-items: center;
    width: 210px; /* Width of the search bar */
    background-color: #f9f9f9;
    padding: 3px; /* Padding for the container */
}

/* Styling for the search input field */
.search-input {
    width: 75%; /* Adjust input field width */
    padding: 10px; /* Input padding for better touch targets */
    border: 1px solid #ccc; /* Consistent border */
    border-radius: 5px;
    margin-right: 1px; /* Space between input and button */
    font-size: 12px; /* Input text size */
}

/* Styling for the search button */
.search-btn {
    padding: 8px; /* Button padding */
    color: white; /* Text color */
    background-color: #4CAF50; /* Initial background color (green) */
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px; /* Button text size */
    transition: transform 0.2s; /* Smooth transition effect for transform */
}

/* Remove the hover background color change */
.search-btn:hover {
    transform: scale(1.05); /* Slight hover enlargement */
}

/* Optional styling for the search container */
.search-container {
    margin-bottom: 20px; /* Add space below the search bar */
}
/* Styling for the search icon */
.search-icon {
    padding: 8px; /* Add padding inside the box */
    color: white; /* Set the icon color to white */
    background-color: #4CAF50; /* Green background color */
    border: 2px solid #4CAF50; /* Border to create the box effect */
    border-radius: 50%; /* Rounded edges for the box */
    font-size: 18px; /* Adjust icon size */
    transition: transform 0.2s, background-color 0.2s; /* Smooth transition effect */
}

/* Styling for the search icon on hover */
.search-icon:hover {
    background-color: #388E3C; /* Darker green on hover */
    transform: scale(1.1); /* Slight enlargement of the icon */
}





/* Styling for the matrix box container */
.matrix-box {
    display: flex;
    justify-content: space-between; /* Space between the table and button */
    align-items: flex-start;
    padding: 20px;
    box-shadow: 0 30px 20px rgba(0, 0, 0, 0.2); /* Adding box shadow */
    margin-left: 200px; /* Add left margin */
    position: relative; /* Ensure it works within the normal document flow */
}


/* Styling for the left side container (Barangay Table) */
.left-side {
    width: 80%; /* Adjust width as needed */
}

/* Styling for the right side container (Add New Client button) */
.right-side {
    display: flex;
    justify-content: flex-end; /* Align button to the right */
    align-items: center;
    width: 20%; /* Adjust width as needed */
}

/* Styling for the "Add New Client" button */
.add-client-button {
    padding: 10px 20px;
    background-color: #007BFF;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.add-client-button:hover {
    background-color: #0056b3;
}

/* Styling for the matrix table */
.matrix-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.matrix-table th, 
.matrix-table td {
    border: 1px solid #ccc;
    padding: 10px 15px;
    text-align: left;
}

.matrix-table th {
    background-color: #007BFF;
    color: #fff;
    text-transform: uppercase;
    font-size: 14px;
}

.matrix-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

/* Optional hover effect for rows */
.matrix-table tr:hover {
    background-color: #f1f1f1;
}

</style>




<script>
    function redirectToBarangay(fileName) {
        // Redirect to the specific barangay's page
        window.location.href = fileName;
    }
</script>

<script>
    function redirectToBarangay(fileName) {
        // Redirect to the specific barangay's page
        window.location.href = fileName;
    }
</script>

<!-- Styling for the new section -->
<style>
.new-box-section {
    margin-top: 10px;         /* Reduce space above the box */
    padding: 10px;            /* Reduce internal padding */
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f9f9f9;
    max-width: 600px;         /* Set a maximum width for the box */
    margin-left: 0;           /* Align box to the left */
    margin-right: 0;          /* No right margin */
}

.matrix-section {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;                /* Adds space between the table and button */
}

.matrix-table {
    width: 100%;              /* Full width for the table */
    border-collapse: collapse;
    margin: 0;                /* No margin */
    table-layout: auto;       /* Adjust table layout based on content */
}

.matrix-table th, .matrix-table td {
    border: 1px solid #ddd;
    padding: 8px 25px;
    text-align: left;
    min-width: 300px;         /* Optional: minimum width to prevent too small of a cell */
}

.matrix-table th {
    background-color: #1679AB; /* Apply color to table header */
    color: white;              /* Make text in the table header white */
}

.matrix-table tr:hover {
    background-color: #40A2E3; /* Apply color to table rows when hovered */
    cursor: pointer;
}

/* Styling for the Add New Client button */
.add-client-button {
    background-color: #1679AB;  /* Blue background */
    color: white;               /* White text */
    padding: 10px 20px;         /* Padding for better appearance */
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-align: center;
    display: inline-block;
}

.add-client-button:hover {
    background-color: #40A2E3;  /* Lighter blue on hover */
}
</style>


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
