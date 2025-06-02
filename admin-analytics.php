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

function getMissedVaccinationsData($pdo) {
    // Query to fetch missed vaccination data
    $queryMissedVaccinations = "
        SELECT
            MONTH(vr.next_vaccination_date) AS month,
            c.gender,
            COUNT(*) AS missed_count
        FROM vaccination_records vr
        JOIN children c ON vr.child_id = c.id
        WHERE vr.next_vaccination_date < CURDATE()
        AND (vr.status = 'Missed' OR vr.status = 'Pending')
        GROUP BY MONTH(vr.next_vaccination_date), c.gender
        ORDER BY month ASC
    ";

    // Prepare and execute the query
    $stmt = $pdo->prepare($queryMissedVaccinations);
    $stmt->execute();
    $missedVaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize arrays for boys and girls
    $boysCounts = array_fill(1, 12, 0);  // Months 1 to 12 initialized with 0
    $girlsCounts = array_fill(1, 12, 0); // Months 1 to 12 initialized with 0
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Process the data
    foreach ($missedVaccinations as $record) {
        $month = (int)$record['month'];
        $missedCount = (int)$record['missed_count'];

        if ($record['gender'] == 'Male') {
            $boysCounts[$month] += $missedCount;
        } elseif ($record['gender'] == 'Female') {
            $girlsCounts[$month] += $missedCount;
        }
    }

    // Prepare data for the chart
    return [
        'monthLabels' => $months,
        'boysData' => array_values($boysCounts),
        'girlsData' => array_values($girlsCounts),
    ];
}

// Include your database connection here
require 'db_connection.php';  // Assuming you have a db_connection.php file

// Call the function to get data
$chartData = getMissedVaccinationsData($pdo);

// Extract data for the chart
$monthLabels = $chartData['monthLabels'];
$boysData = $chartData['boysData'];
$girlsData = $chartData['girlsData'];


function getVaccinationCompletionRate($pdo) {
    $query = "
        SELECT COUNT(*) AS total_children,
               SUM(CASE WHEN vr.status = 'Completed' THEN 1 ELSE 0 END) AS vaccinated_children
        FROM children c
        LEFT JOIN vaccination_records vr ON c.id = vr.child_id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalChildren = $data['total_children'];
    $vaccinatedChildren = $data['vaccinated_children'];

    // Avoid division by zero
    if ($totalChildren > 0) {
        return ($vaccinatedChildren / $totalChildren) * 100;
    } else {
        return 0; // If there are no children, return 0%
    }
}

$percentageVaccinated = getVaccinationCompletionRate($pdo);

function getMissedAppointmentsRate($pdo) {
    $query = "
        SELECT COUNT(*) AS total_appointments,
               SUM(CASE WHEN vr.status = 'Missed' THEN 1 ELSE 0 END) AS missed_appointments
        FROM vaccination_records vr
        WHERE vr.next_vaccination_date <= CURDATE()
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAppointments = $data['total_appointments'];
    $missedAppointments = $data['missed_appointments'];

    // Avoid division by zero
    if ($totalAppointments > 0) {
        return ($missedAppointments / $totalAppointments) * 100;
    } else {
        return 0; // If there are no appointments, return 0%
    }
}

$missedAppointmentsRate = getMissedAppointmentsRate($pdo);

// SQL query to get the completed and total vaccinations for each barangay
$sql = "SELECT barangay_name, completed_vaccinations, total_vaccinations 
        FROM barangay"; // Adjust table name if needed

$result = $pdo->query($sql); // Use PDO instead of $conn->query

if ($result->rowCount() > 0) {
    // Fetch the result and calculate the percentage
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $completedVaccinations = $row['completed_vaccinations'];
        $totalVaccinations = $row['total_vaccinations'];
        
        // Calculate the percentage
        if ($totalVaccinations > 0) {
            $percentageVaccinated = ($completedVaccinations / $totalVaccinations) * 100;
        } else {
            $percentageVaccinated = 0; // If total vaccinations are zero, set the percentage to 0
        }
        
    }
} else {
    echo "No data found";
}

// Fetch the data from the database
$sql = "SELECT b.barangay_name, i.vaccine_name, i.stock
        FROM inventory i
        INNER JOIN barangay b ON i.barangay_id = b.barangay_id
        WHERE i.stock <= 50";

$result = $conn->query($sql);

$barangayData = [];
$vaccineNames = [];

// Store the vaccine data for each barangay
while ($row = $result->fetch_assoc()) {
    $barangayName = $row['barangay_name'];
    $vaccineName = $row['vaccine_name'];
    $stock = $row['stock'];

    // Add vaccine names to the list if not already present
    if (!in_array($vaccineName, $vaccineNames)) {
        $vaccineNames[] = $vaccineName;
    }

    // Add stock data for each barangay
    if (!isset($barangayData[$barangayName])) {
        $barangayData[$barangayName] = array_fill_keys($vaccineNames, 0);
    }

    // Update stock for the barangay's vaccine
    $barangayData[$barangayName][$vaccineName] = $stock;
}

// JSON encode the data for use in JavaScript
$vaccineNamesJson = json_encode($vaccineNames);
$barangayDataJson = json_encode($barangayData);

// Function to calculate vaccination completion rate for all barangays
function getTotalVaccinationCompletionRate($pdo) {
    // Query to get the completed and total vaccinations for each barangay
    $sql = "SELECT barangay_name, completed_vaccinations, total_vaccinations 
            FROM barangay";  // Adjust table name if needed
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $totalCompleted = 0;
    $totalVaccinations = 0;
    
    // Loop through each barangay to calculate the total completed and total vaccinations
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalCompleted += $row['completed_vaccinations'];
        $totalVaccinations += $row['total_vaccinations'];
    }

    // Calculate the overall vaccination completion rate
    if ($totalVaccinations > 0) {
        return ($totalCompleted / $totalVaccinations) * 100;
    } else {
        return 0; // If there are no vaccinations, return 0%
    }
}

// Call the function to get the vaccination completion rate across all barangays
$percentageVaccinated = getTotalVaccinationCompletionRate($pdo);

// Replace these with actual data from your database
$percentageVaccinated = 64.55; // Example current vaccination rate
$missedAppointmentsRate = 42.31; // Example current missed appointments rate
$previousVaccinationRate = 60.00; // Last month's vaccination rate
$previousMissedAppointmentsRate = 45.00; // Last month's missed appointments rate

// Calculate changes
$percentageVaccinatedChange = $percentageVaccinated - $previousVaccinationRate;
$missedAppointmentsRateChange = $missedAppointmentsRate - $previousMissedAppointmentsRate;

// Sample PHP data for demonstration (replace with actual database results)
$query = "
    SELECT 
        b.barangay_name, 
        i.vaccine_name, 
        SUM(i.stock) AS total_stock
    FROM 
        inventory i
    JOIN 
        barangay b ON i.barangay_id = b.barangay_id
    GROUP BY 
        b.barangay_name, i.vaccine_name;
";

$stmt = $pdo->query($query);
$barangayData = [];
$vaccineNames = [];

// Define the vaccine acronyms
$vaccineAcronyms = [
    'Bacille Calmette-Guérin vaccine (BCG)' => 'BCG',
    'Hepatitis B vaccine (HBV)' => 'HBV',
    'Oral Polio Vaccine (OPV)' => 'OPV',
    'Pentavalent Vaccine' => 'Pentavalent',
    'Pneumococcal conjugate vaccine (PCV)' => 'PCV',
    'Measles-Mumps-Rubella vaccine (MMR)' => 'MMR',
    'Inactivated Polio Vaccine (IPV)' => 'IPV'
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Replace vaccine names with acronyms
    $acronym = isset($vaccineAcronyms[$row['vaccine_name']]) ? $vaccineAcronyms[$row['vaccine_name']] : $row['vaccine_name'];

    $barangayData[$row['barangay_name']][] = $row['total_stock'];
    if (!in_array($acronym, $vaccineNames)) {
        $vaccineNames[] = $acronym;
    }
}

$sql = "SELECT vr.id, vr.vaccine_name, vr.vaccination_date, br.barangay_name
        FROM vaccination_records vr
        JOIN barangay br ON vr.barangay_id = br.barangay_id
        WHERE br.barangay_name IN ('Binitayan', 'Tagas')
        ORDER BY vr.vaccination_date ASC";



// Execute the query and store the result
$result = $conn->query($sql);

// Fetch Barangay and Health Worker Data
try {
    $sql = "SELECT barangay_name, CONCAT(first_name, ' ', last_name) AS health_worker, email
    FROM usertable 
    WHERE role = 'user'"; // Adjust 'user' if the role naming differs
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $barangayHealthWorkers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching data: " . $e->getMessage();
    $barangayHealthWorkers = [];
}

// Assuming $monthLabels is an array with full month names
$monthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

// Convert to abbreviated month names
$monthAbbreviations = array_map(function($month) {
    return substr($month, 0, 3); // Get the first three characters of each month
}, $monthLabels);


$currentDate = date('l, d/m/Y'); 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>ImmuniTrack - Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <li><a href="admin-dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
        <li class="active">
    <a href="admin-analytics.php">
        <i class='bx bxs-doughnut-chart'></i> 
        <span class="text">Analytics</span>
    </a>
</li>
        <li><a href="admin-barangay.php"><i class='bx bxs-home'></i>Barangay</a></li>
        <li><a href="admin-inventory.php"><i class='bx bxs-package'></i> Inventory</a></li>
    </ul>
    <ul class="side-menu">
        <li><a href="logout-user.php" class="logout"><i class='bx bxs-log-out-circle'></i> Logout</a></li>
    </ul>
</section>
<!-- SIDEBAR -->

<!-- CONTENT -->
<section id="content">
    <!-- NAVBAR -->

    <!-- NAVBAR -->

<!-- MAIN -->
<main>
    <!-- Flex Container for Top Section -->
    <div id="top-container" style="display: flex; gap: 10px; justify-content: flex-start; align-items: flex-start;">

<!-- Vaccination Stock Level by Barangay Box (Left Side) -->
<div class="empty-white-box" style="width: 48%; background-color: transparent; padding: 15px; box-shadow: 0 30px 10px rgba(0, 0, 0, 0.1);">
    <h3 style="font-weight: bold; font-size: 1.1em;">Vaccination Stock Level</h3><br>
    <div class="container" style="width: 100%; padding: 10px 0;">
        <canvas id="lowStockChart" width="490" height="230"></canvas>
    </div>
</div>


<!-- Missed Vaccinations Box (Right Side) -->
<div style="flex: 0.6; padding: 15px; box-shadow: 0 30px 10px rgba(0, 0, 0, 0.1); margin-left: 5px;">
    <h3 style="font-size: 1.1em; font-weight: bold;">Missed Vaccinations</h3><br>
    <canvas id="missedVaccinationsChart" width="450" height="230"></canvas>
</div>

    </div>

<!-- Barangay Health Workers Table (Below the charts) -->
<div style="padding: 5px; box-shadow: 0 30px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 600px; background-color: transparent; margin-left: 50px;">
    <h3 style="margin-bottom: 10px; font-size: 1.1em; font-weight: bold; text-align: left; color: black;">Barangay Health Workers</h3>
    <div style="max-height: 200px; overflow-y: auto;">

        <table>
            <thead>
                <tr>
                    <th style="text-align: center; background-color: #7C93C3; color: white;">Barangay</th>
                    <th style="text-align: center; background-color: #7C93C3; color: white;">Health Worker</th>
                    <th style="text-align: center; background-color: #7C93C3; color: white;">Contact Info </th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($barangayHealthWorkers)) {
                    $lastBarangay = ''; // Track the last barangay name displayed
                    foreach ($barangayHealthWorkers as $worker) {
                        $barangay = $worker['barangay_name'];
                        $healthWorker = $worker['health_worker'];
                        $email = $worker['email'];  // Assuming you have an 'email' field

                        // Display barangay only if it's different from the previous one
                        if ($barangay !== $lastBarangay) {
                            echo "<tr>
                                    <td style='text-align: center;'>{$barangay}</td>
                                    <td style='text-align: center;'>{$healthWorker}</td>
                                    <td style='text-align: center;'>{$email}</td>
                                  </tr>";
                            $lastBarangay = $barangay; // Update the last displayed barangay
                        } else {
                            echo "<tr>
                                    <td></td>
                                    <td style='text-align: center;'>{$healthWorker}</td>
                                    <td style='text-align: center;'>{$email}</td>
                                  </tr>";
                        }
                    }
                } else {
                    echo "<tr>
                            <td colspan='3' style='text-align: center;'>No Barangay Health Workers found</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</main>

<style>
/* Flexbox layout for aligning the boxes on the left side */
#top-container {
    display: flex;
    gap: 10px;
    justify-content: flex-start; /* Align items to the left */
    align-items: flex-start; /* Align items to the top */
    margin-bottom: 20px; /* Add some space between the charts and the table */
}

/* Styling the canvas (chart) box */
.empty-white-box {
    width: 48%; /* Adjusted width to fit both charts side by side */
    background-color: transparent;
    padding: 15px;
}

/* Styling for the table */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
}

th {
    background-color: #e6f0ff; /* Added background color for headers */
    color: #003366; /* Set text color for headers */
}

tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}

/* Hide the scrollbar */
div[style*="overflow-y: auto"]::-webkit-scrollbar {
    width: 0px;  /* Hide scrollbar width */
    background: transparent;  /* Make the scrollbar background transparent */
}
</style>

<script>
// Script for Vaccination Stock Level by Barangay Chart
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('lowStockChart').getContext('2d');

    const vaccineNames = <?php echo json_encode($vaccineNames); ?>;
    const barangayData = <?php echo json_encode($barangayData); ?>;

    const datasets = Object.keys(barangayData).map((barangay, index) => {
        let color;
        
        // Assign specific colors for Tagas and Binitayan
        if (barangay === "Tagas") {
            color = "rgba(110, 172, 218, 0.5)"; // #6EACDA for Tagas
        } else if (barangay === "Binitayan") {
            color = "rgba(255, 217, 90, 0.5)"; // #FFD95A for Binitayan
        } else {
            // Default color for others
            color = `rgba(${index * 50}, 99, 132, 0.5)`;
        }

        return {
            label: barangay,
            data: barangayData[barangay],
            backgroundColor: color,
            borderColor: color.replace('0.5', '1'), // Make the border color slightly darker
            borderWidth: 1,
            tension: 0.5
        };
    });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: vaccineNames,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Vaccines'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Stock Level'
                    },
                    beginAtZero: true
                }
            }
        }
    });
});

// Sample data for missed vaccinations (Jan-Dec) in Tagas and Binitayan
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const tagasData = [4, 7, 6, 8, 5, 6, 10, 8, 7, 5, 9, 10];    // Missed vaccinations for Tagas
const binitayanData = [3, 6, 5, 7, 8, 4, 9, 10, 11, 9, 8, 6];  // Missed vaccinations for Binitayan

// Data for missed vaccinations, separated by barangay
const metricsData = {
    labels: months, // Use the abbreviated months
    datasets: [
        {
            label: 'Tagas',
            data: tagasData, // Tagas missed vaccinations data
            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Green color for Tagas
            borderColor: 'rgba(75, 192, 192, 1)', // Green border for Tagas
            borderWidth: 1,
            tension: 0.4,
            yAxisID: 'y', // Keep only the first y-axis for missed vaccinations
        },
        {
            label: 'Binitayan',
            data: binitayanData, // Binitayan missed vaccinations data
            backgroundColor: 'rgba(255, 159, 64, 0.2)', // Orange color for Binitayan
            borderColor: 'rgba(255, 159, 64, 1)', // Orange border for Binitayan
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












</div>

</div>








</main>
<!-- MAIN -->

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
