<?php
session_start(); // Start the session

// Redirect if the user is not logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Database connection details
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Count the total number of distinct barangay names
$barangaysCount = $pdo->query("SELECT COUNT(DISTINCT barangay_name) FROM barangay")->fetchColumn();


    // Count the total number of registered children
    $childrenCount = $pdo->query("SELECT COUNT(*) FROM children")->fetchColumn();

// Count the total number of health workers, excluding admins
$healthWorkersCount = $pdo->query("SELECT COUNT(*) FROM usertable WHERE role = 'user'")->fetchColumn();


    // Query to get gender distribution by month
    $query = "
        SELECT MONTHNAME(registration_date) AS month, 
               gender, 
               COUNT(*) AS total_children
        FROM children
        GROUP BY MONTH(registration_date), MONTHNAME(registration_date), gender
        ORDER BY MONTH(registration_date)
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for Chart.js
    $months = [];
    $maleData = [];
    $femaleData = [];

    // Initialize gender counts per month
    $allMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthlyGenderCounts = array_fill_keys($allMonths, ['Male' => 0, 'Female' => 0]);

    // Populate counts based on query results
    foreach ($monthlyData as $row) {
        $month = $row['month'];
        $gender = $row['gender'];
        $count = $row['total_children'];

        $monthlyGenderCounts[$month][$gender] = $count;
    }

    // Separate data for Chart.js
    foreach ($monthlyGenderCounts as $month => $counts) {
        $months[] = $month;
        $maleData[] = $counts['Male'];
        $femaleData[] = $counts['Female'];
    }

    // Convert PHP arrays to JSON for Chart.js
    $labels = json_encode($months);
    $datasets = json_encode([
        [
            'label' => 'Male',
            'data' => $maleData,
            'borderColor' => '#4A90E2',
            'backgroundColor' => '#4A90E233', // Transparent blue
            'fill' => true
        ],
        [
            'label' => 'Female',
            'data' => $femaleData,
            'borderColor' => '#E57373',
            'backgroundColor' => '#E5737333', // Transparent pink
            'fill' => true
        ]
    ]);

    // Fetch missed vaccination data
    function getMissedVaccinationsData($pdo) {
        // SQL query to fetch missed vaccination counts per month
        $sql = "SELECT MONTH(vaccination_date) AS month, COUNT(*) AS missed_count 
                FROM vaccination_records 
                WHERE status = 'missed' 
                AND vaccination_date BETWEEN '2024-01-01' AND '2024-12-31' 
                GROUP BY MONTH(vaccination_date) 
                ORDER BY month";

        // Prepare and execute the query
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Initialize missed vaccination counts
        $missedVaccinations = array_fill(1, 12, 0);  // Default all months to 0

        // Process result and populate missedVaccinations array
        foreach ($result as $row) {
            $month = (int) $row['month'];
            $missedVaccinations[$month] = (int) $row['missed_count'];
        }

        // Prepare the data for the chart
        $monthLabels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        $boysData = [];
        $girlsData = [];

        // Randomly split the count between boys and girls for charting purposes
        foreach ($missedVaccinations as $month => $count) {
            $boysCount = rand(0, $count); // Randomly assign boys
            $girlsCount = $count - $boysCount; // Assign the remainder to girls

            $boysData[] = $boysCount;
            $girlsData[] = $girlsCount;
        }

        return [
            'monthLabels' => $monthLabels,
            'boysData' => $boysData,
            'girlsData' => $girlsData
        ];
    }

    // Get missed vaccination data
    $missedVaccinationData = getMissedVaccinationsData($pdo);

    // Extract data for the chart
    $missedMonths = json_encode($missedVaccinationData['monthLabels']);
    $missedBoysData = json_encode($missedVaccinationData['boysData']);
    $missedGirlsData = json_encode($missedVaccinationData['girlsData']);

    // Convert missed vaccination data to JSON for Chart.js
    $missedVaccinationsData = json_encode([
        [
            'label' => 'Male (Missed)',
            'data' => $missedBoysData,
            'borderColor' => '#4A90E2',
            'backgroundColor' => '#4A90E233',
            'fill' => true
        ],
        [
            'label' => 'Female (Missed)',
            'data' => $missedGirlsData,
            'borderColor' => '#E57373',
            'backgroundColor' => '#E5737333',
            'fill' => true
        ]
    ]);

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Query to retrieve activities for each barangay
$query = "
    SELECT a.activity_name, a.activity_date, a.activity_description, a.activity_location, a.patients, a.barangay_id, b.barangay_name
    FROM activities a
    JOIN barangay b ON a.barangay_id = b.barangay_id
    ORDER BY a.activity_date DESC
    LIMIT 5
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);





// Replace these with actual data from your database
$percentageVaccinated = 64.55; // Example current vaccination rate
$missedAppointmentsRate = 42.31; // Example current missed appointments rate
$previousVaccinationRate = 60.00; // Last month's vaccination rate
$previousMissedAppointmentsRate = 45.00; // Last month's missed appointments rate

// Calculate changes
$percentageVaccinatedChange = $percentageVaccinated - $previousVaccinationRate;
$missedAppointmentsRateChange = $missedAppointmentsRate - $previousMissedAppointmentsRate;

?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>ImmuniTrack - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.css' rel='stylesheet' />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
/* General Styles */
body {
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden;
    height: 100vh;
}

.container {
    display: flex;
    justify-content: center;  /* Center items horizontally */
    align-items: center;      /* Center items vertically */
    height: 100vh;            /* Ensure it takes the full height */
    padding: 0 5%;            /* Add some padding if you want space from the edges */
}

.pie-chart-container {
    width: 25%;
    background: transparent;   /* Set background to transparent */
    padding: 20px;              /* Added more padding */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add box shadow */
    display: flex;
    flex-direction: column;
    align-items: center;        /* Center the contents horizontally */
    justify-content: center;    /* Center the contents vertically */
    margin-left: 100px;
}


canvas {
    max-width: 100%;          /* Ensure chart takes full width */
    height: 200px;           /* Adjust the height */
    width: 100%;             /* Ensure chart takes full width */
}

/* Box Styles */
.box {
    flex: 1;
    margin: 0 10px;
    padding: 20px;           /* Added padding for more space */
    color: white;
    text-align: center;
    margin-top: 50px;        /* Adjusted margin-top to move boxes lower */
    display: flex;
    flex-direction: column;
    justify-content: center; /* Align content vertically */
    min-height: 90px;       /* Minimum height to make the boxes larger */
    box-sizing: border-box;  /* Ensure padding is included in the width/height */
    word-wrap: break-word;   /* Ensure long words wrap properly */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Add box shadow */
}

/* Light Color Boxes */
.barangays {
    background-color: #FFEBEE; /* Light Red/Pink */
}

.children {
    background-color: #E3F2FD; /* Light Blue */
}

.health-workers {
    background-color: #C8E6C9; /* Light Green */
}

.label {
    font-size: 14px;  /* Adjusted font size for better readability */
    font-weight: bold;
    margin-bottom: 5px;
}

.count {
    font-size: 16px;  /* Adjusted font size for better readability */
    font-weight: bold;
}


/* Navbar Styles */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background-color: #fff;
    padding: 10px 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    display: flex;
    align-items: center;
}

.date-time {
    margin-left: auto;
    padding: 0 15px;
    font-size: 14px;
    color: #333;
}

.main-content {
    margin-top: 70px;
    width: 70%;
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: #f0f0f0;
    padding: 20px;
    margin: 0 auto;
}

/* Box and Sidebar Styles */
.dashboard {
    display: flex;
    gap: 10px;
    justify-content: space-around;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap; /* Allow boxes to wrap on smaller screens */
}

/* Active Sidebar Item Styles */
#sidebar .side-menu li.active a {
    background-color: #4A628A;
    color: white;
}

#sidebar .side-menu li.active a i,
#sidebar .side-menu li.active a .text {
    color: white; /* Ensure both icon and text are white */
}

#sidebar .side-menu li.active a:hover {
    background-color: #3a4e6c;
}

.box {
        padding: 20px;
        background-color: #e0e5ec;
        border-radius: 12px;
        box-shadow: 8px 8px 15px #a3b1c6, -8px -8px 15px #ffffff;
        margin: 10px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .box:hover {
        box-shadow: 4px 4px 10px #a3b1c6, -4px -4px 10px #ffffff;
        transform: translateY(-5px);
    }

    .label {
        font-size: 14px;
        color: #666;
    }

    .count {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .line-chart-container {
    width: 50%;                  /* Adjust container width */
    margin-left: 0;              /* Align the container to the left */
    padding: 10px;               /* Add padding inside the container */
    background-color: white;     /* Optional: Add a background color for contrast */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional: Add a subtle shadow for better appearance */
}


    h3 {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
    }

    #genderChart {
        width: 100%;
        height: 400px;
    }
    
    /* Main container for chart and boxes */
    .chart-and-boxes {
        display: flex;                  /* Flex layout for side-by-side */
        gap: 20px;                      /* Space between the chart and the box */
        align-items: flex-start;        /* Align items to the top */
        padding: 20px;
    }

    /* Styling for the chart box */
    .line-chart-box {
        flex: 2;                        /* Take more space than the box */
        background-color: white;
        padding: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        justify-content: center;        /* Center the chart inside */
        align-items: center;            /* Center the chart inside */
        min-height: 150px;              /* Ensure a minimum height */
    }

    /* Box container for the single box */
    .boxes-container {
        flex: 1;                        /* Adjust to take less space than the chart */
        display: flex;                  /* Flex layout for the box */
        flex-direction: column;         /* Stack the box vertically */
        gap: 15px;                      /* Space between the boxes */
        justify-content: center;        /* Center the content */
    }

    /* Styling for the single box (medium size) */
    .box {
        padding: 25px;                  /* Adjust padding for medium size */
        background-color: white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);  /* Add shadow for depth */
        border-radius: 8px;             /* Rounded corners */
        text-align: center;             /* Center align text */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 180px;              /* Set minimum height for medium box */
        width: 250px;                   /* Adjust width for medium size */
    }

    .box .label {
        font-size: 16px;
        color: #555;
        margin-bottom: 8px;
    }

    .box .count {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
 /* Updated Box Styles */
.box {
    flex: 1;
    margin: 0 10px;
    padding: 20px;           /* Added padding for more space */
    color: white;
    text-align: center;
    margin-top: 50px;        /* Adjusted margin-top to move boxes lower */
    display: flex;
    flex-direction: column;
    justify-content: center; /* Align content vertically */
    min-height: 90px;       /* Minimum height to make the boxes larger */
    box-sizing: border-box;  /* Ensure padding is included in the width/height */
    word-wrap: break-word;   /* Ensure long words wrap properly */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); /* Add box shadow */
}

/* Chart box styling */
.line-chart-box {
    flex: 1;                        /* Ensures the chart box takes up equal space as other boxes */
    background-color: white;
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: center;        /* Center the chart inside */
    align-items: center;            /* Center the chart inside */
    min-height: 100px;              /* Ensure a minimum height */
}

/* Main container for chart and boxes */
.chart-and-boxes {
    display: flex;                  /* Flex layout for side-by-side */
    gap: 20px;                      /* Space between the chart and the boxes */
    align-items: flex-start;        /* Align items to the top */
    padding: 20px;
}

/* Styling for individual boxes */
.box {
    padding: 15px;
    background-color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);  /* Add shadow for depth */
    border-radius: 8px;             /* Rounded corners */
    text-align: center;             /* Center align text */
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
        <li class="active">
            <a href="admin-dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
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

<!-- CONTENT -->
<section id="content">
    <!-- NAVBAR -->


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

<div class="dashboard">
    <!-- Flex container for side-by-side boxes -->
    <div class="side-by-side-boxes" style="display: flex; gap: 20px; margin-top: 5px;">
        
        <!-- Left Side: Recent Barangay Activities -->
        <div class="activities-box" style="padding: 15px; box-shadow: 0px 40px 10px rgba(0, 0, 0, 0.1); flex: 1; min-width: 660px; max-width: 700px; margin-left: 10px;">
            <div style="margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 1.1em; color: #0077b6; font-weight: bold;">
                    Upcoming Barangay Activities
                </h3>
            </div>

            <div class="scrollable-activities" style="max-height: 198px; overflow-y: auto; padding-right: 10px; border-radius: 8px; padding: 10px;">
                <ul style="list-style-type: none; margin: 0; padding: 0; font-size: 0.95em; color: #555;">
                    <?php if (!empty($activities)): ?>
                        <?php 
                        $barangay_names = ['Tagas', 'Binitayan']; // Array of barangay names
                        $i = 0; // Index to alternate between barangays
                        ?>
                        <?php foreach ($activities as $activity): ?>
                            <li style="background-color: #C9E6F0; padding: 10px; margin-bottom: 15px;  box-shadow: 0px 9px 5px rgba(0, 0, 0, 0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <strong style="color: #0077b6;"><?php echo htmlspecialchars($activity['activity_name']); ?></strong>
        <span style="font-size: 0.9em; color: #888;"><?php echo htmlspecialchars(date('F j, Y', strtotime($activity['activity_date']))); ?></span>
    </div>
    <!-- Activity Location and Barangay Name in One Row -->
    <div style="font-size: 0.85em; color: #0077b6; font-weight:normal; margin-top: 5px; display: flex; justify-content: space-between;">
        <span><?php echo htmlspecialchars($activity['activity_location']); ?></span>
        <span style="font-weight: bold;">Barangay <?php echo htmlspecialchars($barangay_names[$i % 2]); ?></span>
    </div>
    <div style="font-size: 0.85em; color: #777; margin-top: 5px;">
        <?php echo htmlspecialchars($activity['activity_description']); ?>
    </div>
</li>

                            <?php $i++; ?> <!-- Increment to alternate barangays -->
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li style="padding: 10px; text-align: center; color: #777;">No recent activities.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>




        <!-- Right Side: Vaccination & Missed Appointments -->
        <div style="margin-top: 70px; margin-bottom: 70px; margin-left: 1px; padding: 15px; border: 1px solid #ddd; box-shadow: 0 40px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; background-color: white; border-radius: 8px;">
        <!-- Vaccination Completion Rate -->
            <div style="margin-bottom: 15px;">
                <p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
                    Vaccination Completion Rate: 
                    <strong><?php echo number_format($percentageVaccinated, 2); ?>%</strong>
                    <?php if ($percentageVaccinatedChange > 0): ?>
                        <span style="color: #4caf50; font-size: 12px; margin-left: 5px;">
                            ↑ <?php echo number_format($percentageVaccinatedChange, 2); ?>%
                        </span>
                    <?php elseif ($percentageVaccinatedChange < 0): ?>
                        <span style="color: #f44336; font-size: 12px; margin-left: 5px;">
                            ↓ <?php echo number_format(abs($percentageVaccinatedChange), 2); ?>%
                        </span>
                    <?php else: ?>
                        <span style="color: #888; font-size: 12px; margin-left: 5px;">No change</span>
                    <?php endif; ?>
                </p>
                <div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 10px;">
                    <div style="width: <?php echo number_format($percentageVaccinated, 2); ?>%; height: 20px; background-color: #4caf50; text-align: center; color: white; font-weight: bold; line-height: 20px;">
                        <?php echo number_format($percentageVaccinated, 2); ?>%
                    </div>
                </div>
            </div>

            <!-- Missed Appointments Rate -->
            <div>
                <p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
                    Missed Appointments Rate: 
                    <strong><?php echo number_format($missedAppointmentsRate, 2); ?>%</strong>
                    <?php if ($missedAppointmentsRateChange > 0): ?>
                        <span style="color: #f44336; font-size: 12px; margin-left: 5px;">
                            ↑ <?php echo number_format($missedAppointmentsRateChange, 2); ?>%
                        </span>
                    <?php elseif ($missedAppointmentsRateChange < 0): ?>
                        <span style="color: #4caf50; font-size: 12px; margin-left: 5px;">
                            ↓ <?php echo number_format(abs($missedAppointmentsRateChange), 2); ?>%
                        </span>
                    <?php else: ?>
                        <span style="color: #888; font-size: 12px; margin-left: 5px;">No change</span>
                    <?php endif; ?>
                </p>
                <div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 10px;">
                    <div style="width: <?php echo number_format($missedAppointmentsRate, 2); ?>%; height: 20px; background-color: #f44336; text-align: center; color: white; font-weight: bold; line-height: 20px;">
                        <?php echo number_format($missedAppointmentsRate, 2); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Scrollbar Styling for Activities */
    .scrollable-activities::-webkit-scrollbar {
        width: 8px;
    }

    .scrollable-activities::-webkit-scrollbar-thumb {
        background-color: #C9E6F0;
        border-radius: 10px;
    }

    .scrollable-activities::-webkit-scrollbar-track {
        background-color: #f1f1f1;
    }

    .scrollable-activities::-webkit-scrollbar-button {
        display: none;
    }

    .side-by-side-boxes {
        display: flex;
        gap: 20px; /* Maintains spacing between the remaining boxes */
    }



    /* Media Query for Mobile Responsiveness */
    @media (max-width: 768px) {
        .side-by-side-boxes {
            flex-direction: column;
            gap: 20px;
        }


    }
</style>











<div class="chart-and-boxes">
    <!-- Line Chart Container -->
    <div class="line-chart-box" style="padding: 20px;  border-radius: 8px; box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); flex: 1; min-width: 500px; background: none;">
        <h3 style="margin: 0; font-size: 1.1em; text-align: center; font-weight: bold;">Gender Statistics</h3>
        <canvas id="genderChart"></canvas>
    </div>




<!-- Barangays Box -->
<div class="box barangays">
    <div class="icon"></div>
    <div class="label">Number of Barangay Registered</div>
    <div class="count"><?php echo number_format($barangaysCount); ?></div>
</div>

<!-- Children Box -->
<div class="box children">
    <div class="icon"></div>
    <div class="label">Number of Registered Children</div>
    <div class="count"><?php echo number_format($childrenCount); ?></div>
</div>

<!-- Health Workers Box -->
<div class="box health-workers">
    <div class="icon"></div>
    <div class="label">Number of Health Workers Registered</div>
    <div class="count"><?php echo number_format($healthWorkersCount); ?></div>
</div>



<style>
    /* Dashboard container styling */
    .dashboard {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 10px;
    }

    /* Individual box styling */
    .box {
        background: #fff;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-top: 40px; /* Add margin-top to space out boxes */
    }

    /* Label inside the box */
    .box .label {
        font-size: 14px;
        color: #888;
        margin-bottom: 6px;
    }

    /* Count value styling */
    .box .count {
        font-size: 22px;
        font-weight: bold;
        color: #333;
    }

    /* Icon styling */
    .box .icon {
        font-size: 24px;
        color: #fff;
        width: 40px;
        height: 40px;
        line-height: 40px;
        border-radius: 50%;
        margin: 0 auto 8px auto;
    }

    /* Specific color accents for each box */
    .barangays {
        border-top: 4px solid #4ca1af;
    }

    .children {
        border-top: 4px solid #f39c12;
    }

    .health-workers {
        border-top: 4px solid #9b59b6;
    }

    /* Hover effect for boxes */
    .box:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    
    /* Layout for the chart and boxes */
    .chart-and-boxes {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: space-between;
    }

    .line-chart-box {
        padding: 20px;
        border: 1px solid #c5e4f7;
        border-radius: 8px;
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1);
        flex: 1;
        min-width: 500px;
        background: none; /* Removes the background color */
    }

    /* Adjust layout for mobile responsiveness */
    @media (max-width: 768px) {
        .chart-and-boxes {
            flex-direction: column;
            gap: 20px;
        }

        .box {
            width: 100%;
        }
    }
</style>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Define the abbreviated months of the year
const allMonths = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'June',
    'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'
];

// Pass PHP data to JavaScript
const labels = <?php echo $labels; ?>; // Array of months, e.g., ["January", "February", ..., "December"]
const datasets = <?php echo $datasets; ?>; // Array of dataset objects

// Custom colors for gender datasets
const customColors = {
    Male: '#0A5EB0', // Blue for boys
    Female: '#FF77B7' // Pink for girls
};

// Configure datasets with specific colors, straight lines, and background color
const filteredDatasets = datasets.map((dataset) => ({
    ...dataset, // Copy the other dataset properties
    borderColor: customColors[dataset.label], // Use appropriate color for the dataset label
    backgroundColor: customColors[dataset.label] + '33', // Add a translucent version for the background
    tension: 0, // Straight lines
    fill: true, // Enable filling under the line
    borderWidth: 2 // Set line thickness
}));

// Chart.js Configuration
const data = {
    labels: allMonths, // Full list of abbreviated months
    datasets: filteredDatasets // Use datasets with specific colors and straight lines
};

const config = {
    type: 'line', // Line chart type
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top', // Position of the legend
            },
            tooltip: {
                callbacks: {
                    label: function (tooltipItem) {
                        const value = tooltipItem.raw;
                        return `${tooltipItem.dataset.label}: ${value} children`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true, // Start y-axis at 0
                ticks: {
                    stepSize: 10 // Define step size for y-axis ticks
                },
                title: {
                    display: true,
                    text: 'Number of Children'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month'
                }
            }
        }
    }
};

// Render the straight-line chart
const genderChart = new Chart(
    document.getElementById('genderChart'),
    config
);
</script>

















</section>

</body>
</html>
