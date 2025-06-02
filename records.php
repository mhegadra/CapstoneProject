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

// Get the user ID from the URL parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the health worker's name
$sql = "SELECT first_name, last_name FROM usertable WHERE id = $userId";
$result = $conn->query($sql);
$healthWorker = $result->fetch_assoc();
$fullName = $healthWorker ? htmlspecialchars($healthWorker['first_name'] . ' ' . $healthWorker['last_name']) : 'Unknown';

// Fetch the number of children administered by the health worker
$sqlChildren = "SELECT COUNT(*) AS children_count FROM vaccination_records WHERE administered_by = '$fullName'";
$resultChildren = $conn->query($sqlChildren);
$childrenData = $resultChildren->fetch_assoc();
$childrenCount = $childrenData ? $childrenData['children_count'] : 0;

// Close connection
$conn->close();
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
/* Container for charts and table */
#chart-and-table-container { 
    display: flex;
    justify-content: center;
    align-items: flex-start;
    margin: 40px auto 10px;
    width: 90%;
}

/* Styling for the boxes containing recent children section */
.empty-white-box,
.recent-children-section {
    background-color: white;
    padding: 20px;
    width: 30%; /* Width of the section */
    min-height: 100px;
    margin: 0 15px; /* Adjusted margin for equal spacing */
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); /* Optional shadow for better appearance */
    border-radius: 5px; /* Optional rounded corners */
}

/* Scrollable content inside recent children section */
.recent-children-section {
    max-height: 250px; /* Adjusted max height for six rows with scrollbar */
    overflow-y: auto; /* Enable vertical scrolling within the box */
}

/* Styling for the table of recently registered children */
.recent-children-table {
    width: 100%; 
    border-collapse: collapse; 
    font-size: 0.9em;
}

.recent-children-table th,
.recent-children-table td {
    text-align: left;
    padding: 8px; 
    border-bottom: 1px solid #ddd;
}

/* Container for the charts (bar and line) */
#charts-container {
    display: flex;
    justify-content: center; /* Center the graphs horizontally */
    align-items: flex-start; /* Align items at the start */
    width: 90%;
    margin: 5px auto; /* Reduced top margin to 10px */
}

.chart-box {
    width: 48%; /* Each chart takes up 48% of the total width */
    height: 350px; /* Set a fixed height */
    padding: 10px;
    margin: 15px 15px; /* Reduced top margin to 15px, left and right unchanged */
    display: flex;
    justify-content: center;
    align-items: center; /* Center the content within each chart box */
}

/* Ensure canvas charts are responsive */
.chart-container canvas {
    width: 100% !important;
    height: 100% !important;
}
/* Active Dashboard Item */
#sidebar .side-menu li.active a {
    background-color: #4A628A;  /* Custom blue background for active Dashboard item */
    color: black;  /* Black text for active link */
}

#sidebar .side-menu li.active a i {
    color: white;  /* White color for the icon when active */
}

#sidebar .side-menu li.active a .text {
    color: black;  /* Black text for the Dashboard label */
}

/* Hover state for active Dashboard link */
#sidebar .side-menu li.active a:hover {
    background-color: #3a4e6c;  /* Darker blue on hover */
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


.main-content {
    display: flex;
    justify-content: center; /* Centers the boxes horizontally */
    gap: 10px; /* Space between the boxes */
    margin-top: 100px; /* Adds space above the main content */
}

.box {
    background: linear-gradient(145deg, #e6e6e6, #ffffff);
    box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.1), -4px -4px 10px #ffffff;
    padding: 10px;
    max-width: 400px;
    min-width: 250px;
    text-align: center;
    word-wrap: break-word;
}

.box h2 {
    font-size: 1.3em;
    color: #333;
    margin-bottom: 12px;
}

.box p {
    font-size: 1em;
    color: #007bff;
    margin-bottom: 12px;
}

/* Responsive Design for Small Screens */
@media (max-width: 768px) {
    .main-content {
        flex-direction: column;
        align-items: center;
    }

    .box {
        width: 100%;
        min-width: 250px;
    }
}

.btn-download button {
    background-color: #007bff; /* Blue background */
    color: white;
    font-size: 1em;
    padding: 8px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-top: 30px; /* Adds top margin */
}

.btn-download button:hover {
    background-color: #0056b3; /* Darker blue on hover */
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
        <main>
    <div class="main-content">
        <!-- Box 1 showing the number of children administered -->
        <div class="box" id="box1">
            <h2>Number of Children Administered</h2>
            <p><?php echo $childrenCount; ?> children administered</p>
        </div>
        
        <!-- Only the download button -->
        <div class="btn-download">
            <a href="download_children.php?administered_by=<?php echo $userId; ?>" class="btn-download">
                <button>Download List</button>
            </a>
        </div>
    </div>
</main>









    </section>

</body>
</html>