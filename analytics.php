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
    // Set PDO error mode to exception to handle errors gracefully
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the user's details and barangay from the database
    $email = $_SESSION['email'];
    $query = "SELECT usertable.email, barangay.barangay_name, usertable.barangay_id
              FROM usertable
              INNER JOIN barangay ON usertable.id = barangay.user_id
              WHERE usertable.email = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $barangayId = $user['barangay_id'];
        $barangayName = $user['barangay_name'];
        $initial = strtoupper(substr($user['email'], 0, 1));
        $profileImageUrl = "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff";
    } else {
        header('Location: login-user.php');
        exit();
    }

    // Fetch recently registered children
    $recentChildrenQuery = "SELECT first_name, last_name, registration_date 
                             FROM children
                             WHERE barangay_id = ?
                             ORDER BY registration_date DESC
                             LIMIT 5";  // You can change the limit to show more children
    $stmt = $pdo->prepare($recentChildrenQuery);
    $stmt->execute([$barangayId]);
    $recentChildren = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch gender statistics for the logged-in user's barangay
    $genderQuery = "SELECT gender, COUNT(*) as count 
                    FROM children 
                    WHERE barangay_id = ? 
                    GROUP BY gender";
    $stmt = $pdo->prepare($genderQuery);
    $stmt->execute([$barangayId]);
    $genderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $maleCount = 0;
    $femaleCount = 0;
    foreach ($genderData as $row) {
        if (strtolower($row['gender']) === 'male') {
            $maleCount = $row['count'];
        } elseif (strtolower($row['gender']) === 'female') {
            $femaleCount = $row['count'];
        }
    }

// Get the month filter from the URL, defaulting to the current month
$monthFilter = isset($_GET['monthFilter']) ? $_GET['monthFilter'] : date('m');

// Fetch the total number of registered children for the logged-in user's barangay
$totalChildrenQuery = "SELECT COUNT(*) AS total_children 
                       FROM children
                       WHERE barangay_id = ?";
$stmt = $pdo->prepare($totalChildrenQuery);
$stmt->execute([$barangayId]);
$totalChildrenResult = $stmt->fetch();
$total_children = $totalChildrenResult['total_children'];

// Fetch the number of registered children by each month (0-12 months)
$ageGroups = array_fill(0, 13, 0); // Initialize array for months 0-12 with default count 0
$query = "SELECT
              TIMESTAMPDIFF(MONTH, date_of_birth, CURDATE()) AS age_in_months,
              COUNT(*) AS count
          FROM children
          WHERE barangay_id = ?
          AND MONTH(registration_date) = ?
          GROUP BY age_in_months
          HAVING age_in_months BETWEEN 0 AND 12";

$stmt = $pdo->prepare($query);
$stmt->execute([$barangayId, $monthFilter]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Populate the ageGroups array with the counts
foreach ($results as $result) {
    $ageGroups[$result['age_in_months']] = $result['count'];
}

// Prepare data for the chart
$labels = range(0, 12); // Labels for months 0 to 12
$data = array_values($ageGroups);

// Initialize arrays for each gender and age group
$ageGroupsBoys = array_fill(0, 13, 0); // For boys (0-12 months)
$ageGroupsGirls = array_fill(0, 13, 0); // For girls (0-12 months)

$query = "SELECT
              TIMESTAMPDIFF(MONTH, date_of_birth, CURDATE()) AS age_in_months,
              gender,
              COUNT(*) AS count
          FROM children
          WHERE barangay_id = ?
          AND MONTH(registration_date) = ?
          GROUP BY age_in_months, gender
          HAVING age_in_months BETWEEN 0 AND 12";

$stmt = $pdo->prepare($query);
$stmt->execute([$barangayId, $monthFilter]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Populate age groups for boys and girls
foreach ($results as $result) {
    if ($result['gender'] === 'Male') {
        $ageGroupsBoys[$result['age_in_months']] = $result['count'];
    } elseif ($result['gender'] === 'Female') {
        $ageGroupsGirls[$result['age_in_months']] = $result['count'];
    }
}

// Prepare data for the chart
$labels = range(0, 12); // Labels for months 0 to 12
$dataBoys = array_values($ageGroupsBoys);
$dataGirls = array_values($ageGroupsGirls);


    // Format the current date
    $currentDate = date('l, d/m/Y');

    // Fetch the most common vaccine for the user's barangay
    $mostCommonVaccineQuery = "SELECT vaccination_records.vaccine_name, COUNT(*) AS vaccine_count
                                FROM vaccination_records
                                INNER JOIN children ON vaccination_records.child_id = children.id
                                WHERE children.barangay_id = ?
                                GROUP BY vaccination_records.vaccine_name
                                ORDER BY vaccine_count DESC
                                LIMIT 1";

    $stmt = $pdo->prepare($mostCommonVaccineQuery);
    $stmt->execute([$barangayId]);
    $mostCommonVaccineResult = $stmt->fetch();

    // Initialize default values for the most common vaccine
    $mostCommonVaccine = $mostCommonVaccineResult['vaccine_name'] ?? 'Unknown Vaccine';
    $vaccineCount = $mostCommonVaccineResult['vaccine_count'] ?? 0;

    // Safely calculate percentage
    $percentageVaccinated = ($total_children > 0) ? round(($vaccineCount / $total_children) * 100, 2) : 0;

} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
// Total number of scheduled vaccinations (appointments due)
$queryTotalAppointments = "SELECT COUNT(*) AS total_appointments 
                           FROM vaccination_records
                           WHERE next_vaccination_date IS NOT NULL";
$stmt = $pdo->prepare($queryTotalAppointments);
$stmt->execute();
$totalAppointmentsResult = $stmt->fetch();
$totalAppointments = $totalAppointmentsResult['total_appointments'];

// Missed appointments (appointments where next vaccination date is passed, and status is 'Missed' or 'Pending')
$queryMissedAppointments = "SELECT COUNT(*) AS missed_appointments
                            FROM vaccination_records
                            WHERE next_vaccination_date < CURDATE()
                            AND (status = 'Missed' OR status = 'Pending')";
$stmt = $pdo->prepare($queryMissedAppointments);
$stmt->execute();
$missedAppointmentsResult = $stmt->fetch();
$missedAppointments = $missedAppointmentsResult['missed_appointments'];

// Calculate missed appointments rate
$missedAppointmentsRate = ($totalAppointments > 0) ? round(($missedAppointments / $totalAppointments) * 100, 2) : 0;
// Safely calculate the percentage of vaccinated children
$percentageVaccinated = ($total_children > 0) ? round(min(($vaccineCount / $total_children) * 100, 100), 2) : 0;
    // Query to fetch missed vaccination counts by month for both boys and girls
    // Query to fetch missed vaccination counts by month for both boys and girls
    $queryMissedVaccinations = "
        SELECT
            MONTH(next_vaccination_date) AS month,
            gender,
            COUNT(*) AS missed_count
        FROM vaccination_records
        JOIN children ON vaccination_records.child_id = children.id
        WHERE next_vaccination_date < CURDATE()
        AND (status = 'Missed' OR status = 'Pending')
        GROUP BY MONTH(next_vaccination_date), gender
        ORDER BY month ASC
    ";

    $stmt = $pdo->prepare($queryMissedVaccinations);
    $stmt->execute();

    // Fetch the results
    $missedVaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare the data for randomization: missed counts for boys and girls
$boysCounts = array_fill(1, 12, 0);  // Initialize all months (1-12) with 0 for boys
$girlsCounts = array_fill(1, 12, 0); // Initialize all months (1-12) with 0 for girls
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// Randomize the missed counts for boys and girls between 0 and a specified max number (e.g., 50)
for ($i = 1; $i <= 12; $i++) {
    $boysCounts[$i] = rand(0, 50);  // Randomize boys missed counts between 0 and 50
    $girlsCounts[$i] = rand(0, 50); // Randomize girls missed counts between 0 and 50
}

// Use the month names as labels
$monthLabels = $months;

// Prepare the data for JavaScript
$boysData = array_values($boysCounts);
$girlsData = array_values($girlsCounts);



    // Initialize arrays for boys and girls counts
$boys_count = array_fill(0, 13, 0);  // 0 to 12 months
$girls_count = array_fill(0, 13, 0);

// Query to get gender and age in months for the specific barangay
$query = "SELECT gender, TIMESTAMPDIFF(MONTH, date_of_birth, CURDATE()) AS age_months
          FROM children
          WHERE barangay_id = ?  -- Filter by barangay_id
          AND TIMESTAMPDIFF(MONTH, date_of_birth, CURDATE()) BETWEEN 0 AND 12";  // Limit the age to 0-12 months

// Prepare and execute the query using PDO
$stmt = $pdo->prepare($query);
$stmt->execute([$barangayId]);  // Bind the barangay_id to the query

// Loop through the database result and count boys and girls by age in months
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $age_months = (int)$row['age_months'];
    $gender = $row['gender'];

    if ($gender === 'Male') {
        $boys_count[$age_months]++;
    } elseif ($gender === 'Female') {
        $girls_count[$age_months]++;
    }
}

// Prepare the data for the JavaScript chart
$labels = range(0, 12);  // Months 0 to 12
$dataBoys = json_encode($boys_count);
$dataGirls = json_encode($girls_count);



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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <title>ImmuniTrack - Analytics</title>
    <style>
/* Add this CSS to center the brand text */
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

/* Styling the container to create two columns */
.analytics-container {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 20px; /* Space between the chart and text */
    margin: 40px auto;
    margin-top: 50px;
    max-width: 900px; /* Restricting the maximum width */
}

/* Style for the chart container */
#chart-container {
    flex: 1;
    width: 300px;  /* Smaller chart width */
    margin: auto;
    padding: 20px;
}

canvas {
    max-width: 100%;  /* Ensure canvas fits within the container */
    height: auto;     /* Maintain aspect ratio */
}

/* Text section */
.analytics-text {
    flex: 1;
    padding: 50px;
    background-color: #f5f5f5;
    max-width: 300px; /* Limit the width of the text section */
    transition: all 0.3s ease-in-out; /* Smooth transition for hover effects */
}

.analytics-text h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.analytics-text p {
    font-size: 16px;
    margin-bottom: 15px;
}

/* Highlighting the number of children */
.highlight-number {
    font-size: 20px; /* Increase font size */
    font-weight: bold; /* Make it bold */
    color: #FF5733; /* Change color to a vibrant shade */
    background-color: #FCE4D6; /* Light background color */
    padding: 5px; /* Padding around the number */
}

/* Sidebar Styling for Analytics Active Menu Item */

/* Active Menu Item (Analytics) */
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
        <li>
            <a href="dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="calendar.php">
                <i class='bx bxs-calendar-event'></i>
                <span class="text">Calendar</span>
            </a>
        </li>
        <li class="active">
            <a href="analytics.php">
                <i class='bx bxs-doughnut-chart'></i>
                <span class="text">Analytics</span>
            </a>
        </li>
        <li>
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
<!-- Container for filter and charts -->
<div style="width: 100%; padding: 20px;">

    <!-- Top section with Filter by Month and Metrics Box -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
        
        <!-- Month Selector Box -->
        <div style="border: 1px solid #ddd; border-radius: 8px; padding: 10px 20px; background-color: white; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); width: auto;">
            <label for="month-selector" style="font-weight: normal; font-size: 14px;">Filter by Month:</label>
            <form method="GET" action="" style="display: inline;">
                <select id="month-selector" name="monthFilter" onchange="this.form.submit()" style="padding: 5px; font-size: 14px;">
                    <?php 
                    // Generate options for each month
                    for ($month = 1; $month <= 12; $month++) {
                        $monthName = date("F", mktime(0, 0, 0, $month, 1));
                        echo "<option value=\"$month\" " . ($monthFilter == $month ? 'selected' : '') . ">$monthName</option>";
                    }
                    ?>
                </select>
            </form>
        </div>
<!-- Container for Registered Children and Export Box -->
<div style="display: flex; justify-content: center; gap: 40px; margin-top: 20px;">
    
    <!-- Registered Children Box -->
    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 10px 20px; background-color: #ECFFE6; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); text-align: center; width: auto; max-width: 250px;">
        <p style="font-size: 20px; font-weight: bold; margin: 0; color: #4CAF50;"><?php echo $total_children; ?></p>
        <p style="font-size: 14px; color: #666; margin-top: 5px;">Registered Children</p>
    </div>
    
    <a href="export.php" style="text-decoration: none;">
    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 10px 20px; background-color: #ADD8E6; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); text-align: center; width: auto; max-width: 300px; margin-top: 15px;">
        <p style="font-size: 14px; color: #666; margin-top: 5px;">Download Data</p>
    </div>
</a>

</div>  
    </div>
</div>
    <!-- Four box layout -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px;">

    <!-- Box 3 - Gender Statistics -->
<div style="flex: 1; min-width: 40%; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f3f4f6; box-shadow: 0 10px 8px rgba(0, 0, 0, 0.05); position: relative; max-width: 500px; margin: 0 auto;">
    <h3 style="font-size: 18px; font-weight: bold; color: #5a8ded; margin-bottom: 10px; text-align: center;">Gender Statistics</h3>
    <div style="display: flex; align-items: center; justify-content: center;">
        <!-- Pie Chart Placeholder -->
        <div style="width: 50%; height: 150px;">
            <canvas id="genderChart"></canvas>
        </div>
        <!-- Statistics -->
        <div style="width: 50%; padding-left: 20px; color: #555;">
            <p style="font-size: 16px; line-height: 1.6;">
                <strong style="color: #5a8ded;">Male:</strong> <?= round($maleCount / ($maleCount + $femaleCount) * 100, 2) ?>%<br>
                <strong style="color: #f1948a;">Female:</strong> <?= round($femaleCount / ($maleCount + $femaleCount) * 100, 2) ?>%
            </p>
        </div>
    </div>
</div>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
   document.addEventListener('DOMContentLoaded', function() {
       const ctx = document.getElementById('genderChart').getContext('2d');
       new Chart(ctx, {
           type: 'pie',
           data: {
               labels: ['Male', 'Female'],
               datasets: [{
                   data: [<?= $maleCount ?>, <?= $femaleCount ?>],
                   backgroundColor: ['#5a8ded', '#f1948a'],
                   hoverBackgroundColor: ['#86a9f5', '#f5b7b1']
               }]
           },
           options: {
               responsive: true,
               maintainAspectRatio: false,
               plugins: {
                   legend: {
                       display: true,
                       position: 'bottom',
                       labels: {
                           font: {
                               size: 12,
                               weight: 'bold'
                           },
                           color: '#555'
                       }
                   }
               }
           }
       });
   });
</script>


<!-- Box 2: Line Chart Container for Missed Vaccination -->
<div style="flex: 1; min-width: 48%; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f3f4f6; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
    <h3>Missed Vaccinations</h3>
    <canvas id="missedVaccinationsChart"></canvas>
</div>

<script>
// Get the missed vaccinations data from PHP

const months = <?php 
    // Abbreviated month labels for JavaScript
    $monthAbbr = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    echo json_encode($monthAbbr); 
?>;
const boysData = <?php echo json_encode($boysData); ?>;
const girlsData = <?php echo json_encode($girlsData); ?>;

// Data for missed vaccinations, separated by gender
const metricsData = {
    labels: months, // Use the abbreviated month names
    datasets: [
        {
            label: 'Missed Vaccinations (Boys)',
            data: boysData, // Boys missed vaccinations data
            backgroundColor: 'rgba(54, 162, 235, 0.2)', // Blue color for boys
            borderColor: 'rgba(54, 162, 235, 1)', // Blue border for boys
            borderWidth: 1,
            tension: 0.4,
            yAxisID: 'y', // Keep only the first y-axis for missed vaccinations
        },
        {
            label: 'Missed Vaccinations (Girls)',
            data: girlsData, // Girls missed vaccinations data
            backgroundColor: 'rgba(255, 99, 132, 0.2)', // Pink color for girls
            borderColor: 'rgba(255, 99, 132, 1)', // Pink border for girls
            borderWidth: 1,
            tension: 0.4,
            yAxisID: 'y', // Keep only the first y-axis for missed vaccinations
        }
    ]
};

// Config for the line chart
const config = {
    type: 'line',  // Line chart
    data: metricsData,
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,  // Start Y-axis from 0
                title: {
                    display: true,
                    text: 'Number of Missed Vaccinations'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month'
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.dataset.label}: ${context.parsed.y}`;
                    }
                }
            }
        }
    }
};

// Initialize the chart
const ctx = document.getElementById('missedVaccinationsChart').getContext('2d');
new Chart(ctx, config);

</script>



        <!-- Box 1 -->
        <div style="flex: 1; min-width: 48%; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f3f4f6; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            <canvas id="barangayChart" style="width: 100%; height: 235px;"></canvas>
        </div>




<!-- Box 4 -->
<div class="box" style="background-color: #eaf5f9; padding: 20px; border: 1px solid #c5e4f7; border-radius: 8px; box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.1); flex: 1; min-width: 300px;">
    <div style="background-color: #0077b6; padding: 5px; border-radius: 5px; margin-bottom: 20px; display: inline-block;">
        <p style="margin: 0; color: white; font-size: 1em; display: flex; align-items: center;">
            Recently Registered Children
        </p>
    </div>
    <!-- Scrollable table container -->
    <div class="scrollable-table" style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em; color: #555;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Name</th>
                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentChildren)): ?>
                    <?php foreach ($recentChildren as $child): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($child['first_name']) . ' ' . htmlspecialchars($child['last_name']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd; color: #ff5722;"><?php echo htmlspecialchars(date('F j, Y', strtotime($child['registration_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="padding: 8px; text-align: center;">No recent registrations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add the custom scrollbar style -->
<style>
    .scrollable-table::-webkit-scrollbar {
        width: 8px;
    }

    .scrollable-table::-webkit-scrollbar-thumb {
        background: #c5e4f7;
        border-radius: 4px;
    }

    .scrollable-table::-webkit-scrollbar-thumb:hover {
        background: #a3d1f5;
    }

    .scrollable-table::-webkit-scrollbar-track {
        background: #eaf5f9;
    }
</style>



<script>
// Chart.js Script for Line Chart
const ctxBar = document.getElementById('barangayChart').getContext('2d');
const barangayChart = new Chart(ctxBar, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>, // Months 0 to 12
        datasets: [
            {
                label: 'Boys',
                data: <?php echo $dataBoys; ?>, // Boys' counts
                borderColor: 'rgba(54, 162, 235, 1)', // Blue for boys
                backgroundColor: 'rgba(54, 162, 235, 0.2)', // Optional fill
                borderWidth: 2,
                fill: false // Line without background fill
            },
            {
                label: 'Girls',
                data: <?php echo $dataGirls; ?>, // Girls' counts
                borderColor: 'rgba(255, 99, 132, 1)', // Pink for girls
                backgroundColor: 'rgba(255, 99, 132, 0.2)', // Optional fill
                borderWidth: 2,
                fill: false // Line without background fill
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Children'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Age in Months (0-12)'
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});
</script>










