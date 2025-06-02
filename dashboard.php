<?php
session_start();

// Database connection settings
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

// Create database connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login-user.php");
    exit();
}

// Get the user's barangay ID, initials, and name
$email = $_SESSION['email'];
$sql = "SELECT barangay_id, id as user_id, initials, first_name, last_name FROM usertable WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $barangayId = $row['barangay_id'];
    $userId = $row['user_id'];
    $userInitials = $row['initials'];
    $userName = $row['first_name'] . ' ' . $row['last_name'];
    
    // Use only the first letter of the initials for the profile image
    $profileInitial = substr($userInitials, 0, 1);
} else {
    echo "User not found.";
    exit();
}

$stmt->close();

// Fetch barangay details
$sql = "SELECT barangay_name FROM barangay WHERE barangay_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $barangayId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $barangayName = $row['barangay_name'];
} else {
    $barangayName = "Unknown Barangay"; // Set a default value if not found
}

$stmt->close();

// Define the current date and time
$currentDateTime = date('l, d/m/Y H:i:s'); // Current date and time
$currentDate = date('l, d/m/Y'); // Date only

// Generate profile image URL from the first letter of the initials
$profileImageUrl = "https://ui-avatars.com/api/?name=" . urlencode($profileInitial) . "&background=random&color=fff";

// Vaccination Metrics

// Total registered children in the barangay
$totalChildrenQuery = "SELECT COUNT(*) AS total_children FROM children WHERE barangay_id = ?";
$stmt = $conn->prepare($totalChildrenQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$totalChildrenResult = $stmt->get_result()->fetch_assoc();
$totalChildren = $totalChildrenResult['total_children'] ?? 0;

// Most common vaccine
$mostCommonVaccineQuery = "
    SELECT vaccination_records.vaccine_name, COUNT(*) AS vaccine_count
    FROM vaccination_records
    JOIN children ON vaccination_records.child_id = children.id
    WHERE children.barangay_id = ?
    GROUP BY vaccination_records.vaccine_name
    ORDER BY vaccine_count DESC
    LIMIT 1
";
$stmt = $conn->prepare($mostCommonVaccineQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$mostCommonVaccineResult = $stmt->get_result()->fetch_assoc();
$mostCommonVaccine = $mostCommonVaccineResult['vaccine_name'] ?? 'Unknown Vaccine';
$vaccineCount = $mostCommonVaccineResult['vaccine_count'] ?? 0;

// Ensure the vaccination percentage does not exceed 100%
$percentageVaccinated = ($totalChildren > 0) ? round(($vaccineCount / $totalChildren) * 100, 2) : 0;
$percentageVaccinated = min($percentageVaccinated, 100); // Ensure it doesn't exceed 100%


// Missed Appointments
$queryMissedAppointments = "
    SELECT COUNT(*) AS missed_appointments
    FROM vaccination_records
    WHERE next_vaccination_date < CURDATE()
    AND (status = 'Missed' OR status = 'Pending')
";
$stmt = $conn->prepare($queryMissedAppointments);
$stmt->execute();
$missedAppointmentsResult = $stmt->get_result()->fetch_assoc();
$missedAppointments = $missedAppointmentsResult['missed_appointments'] ?? 0;

// Total Appointments
$queryTotalAppointments = "
    SELECT COUNT(*) AS total_appointments
    FROM vaccination_records
    WHERE next_vaccination_date IS NOT NULL
";
$stmt = $conn->prepare($queryTotalAppointments);
$stmt->execute();
$totalAppointmentsResult = $stmt->get_result()->fetch_assoc();
$totalAppointments = $totalAppointmentsResult['total_appointments'] ?? 0;

// Missed Appointments Rate
$missedAppointmentsRate = ($totalAppointments > 0) ? round(($missedAppointments / $totalAppointments) * 100, 2) : 0;

// Handle form submission to add a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_activity') {
    // Sanitize and capture input data
    $activityName = $_POST['activity_name'];
    $activityDate = $_POST['activity_date'];
    $activityDescription = $_POST['activity_description'];
    $activityTime = $_POST['activity_time'];
    $activityLocation = $_POST['activity_location'];
    $targetAudience = $_POST['target_audience'];

    // Prepare SQL query to insert new activity
    $sql = "INSERT INTO activities (activity_name, activity_date, activity_description, barangay_id, user_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $activityName, $activityDate, $activityDescription, $barangayId, $userId);

    // Execute query and return success message
    if ($stmt->execute()) {
        echo "success"; // Return success message to the frontend
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    exit();
}

// Fetch recent activities for the user's barangay
$activitiesQuery = "SELECT activity_name, activity_date, activity_description 
                    FROM activities 
                    WHERE barangay_id = ? 
                    ORDER BY activity_date DESC 
                    LIMIT 10";
$stmt = $conn->prepare($activitiesQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch recently registered children
$recentChildrenQuery = "SELECT first_name, last_name, registration_date 
                        FROM children
                        WHERE barangay_id = ? 
                        ORDER BY registration_date DESC";
$stmt = $conn->prepare($recentChildrenQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$recentChildren = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Query to fetch vaccines with low stock (stock less than 10)
$lowStockVaccinesQuery = "SELECT vaccine_name, stock FROM inventory WHERE stock < 10";
$lowStockVaccinesResult = $conn->query($lowStockVaccinesQuery);

// Store low-stock vaccines in an array
$lowStockVaccines = [];
if ($lowStockVaccinesResult->num_rows > 0) {
    while ($row = $lowStockVaccinesResult->fetch_assoc()) {
        $lowStockVaccines[] = $row;
    }
}

// Births data query for boys and girls by month
$birthsQuery = "
    SELECT 
        MONTH(date_of_birth) AS birth_month, 
        gender, 
        COUNT(*) AS birth_count
    FROM children
    WHERE YEAR(date_of_birth) = YEAR(CURRENT_DATE) 
        AND barangay_id = ?
    GROUP BY birth_month, gender
    ORDER BY birth_month ASC
";

$stmt = $conn->prepare($birthsQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$birthCountsResult = $stmt->get_result();

// Initialize arrays to hold monthly data for boys and girls
$boysBirthCounts = array_fill(0, 12, 0); // Default all months to 0 for boys
$girlsBirthCounts = array_fill(0, 12, 0); // Default all months to 0 for girls

// Populate the arrays with the actual counts
while ($row = $birthCountsResult->fetch_assoc()) {
    if (strtolower($row['gender']) === 'male') {
        $boysBirthCounts[$row['birth_month'] - 1] = $row['birth_count'];
    } elseif (strtolower($row['gender']) === 'female') {
        $girlsBirthCounts[$row['birth_month'] - 1] = $row['birth_count'];
    }
}
// Vaccination Metrics

// Total registered children in the barangay
$totalChildrenQuery = "SELECT COUNT(*) AS total_children FROM children WHERE barangay_id = ?";
$stmt = $conn->prepare($totalChildrenQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$totalChildrenResult = $stmt->get_result()->fetch_assoc();
$totalChildren = $totalChildrenResult['total_children'] ?? 0;

// Most common vaccine
$mostCommonVaccineQuery = "
    SELECT vaccination_records.vaccine_name, COUNT(*) AS vaccine_count
    FROM vaccination_records
    JOIN children ON vaccination_records.child_id = children.id
    WHERE children.barangay_id = ?
    GROUP BY vaccination_records.vaccine_name
    ORDER BY vaccine_count DESC
    LIMIT 1
";
$stmt = $conn->prepare($mostCommonVaccineQuery);
$stmt->bind_param("i", $barangayId);
$stmt->execute();
$mostCommonVaccineResult = $stmt->get_result()->fetch_assoc();
$mostCommonVaccine = $mostCommonVaccineResult['vaccine_name'] ?? 'Unknown Vaccine';
$vaccineCount = $mostCommonVaccineResult['vaccine_count'] ?? 0;

// Ensure the vaccination percentage does not exceed 100%
$percentageVaccinated = ($totalChildren > 0) ? round(($vaccineCount / $totalChildren) * 100, 2) : 0;
$percentageVaccinated = min($percentageVaccinated, 100); // Ensure it doesn't exceed 100%

// Missed Appointments
$queryMissedAppointments = "
    SELECT COUNT(*) AS missed_appointments
    FROM vaccination_records
    WHERE next_vaccination_date < CURDATE()
    AND (status = 'Missed' OR status = 'Pending')
";
$stmt = $conn->prepare($queryMissedAppointments);
$stmt->execute();
$missedAppointmentsResult = $stmt->get_result()->fetch_assoc();
$missedAppointments = $missedAppointmentsResult['missed_appointments'] ?? 0;

// Total Appointments
$queryTotalAppointments = "
    SELECT COUNT(*) AS total_appointments
    FROM vaccination_records
    WHERE next_vaccination_date IS NOT NULL
";
$stmt = $conn->prepare($queryTotalAppointments);
$stmt->execute();
$totalAppointmentsResult = $stmt->get_result()->fetch_assoc();
$totalAppointments = $totalAppointmentsResult['total_appointments'] ?? 0;

// Missed Appointments Rate
$missedAppointmentsRate = ($totalAppointments > 0) ? round(($missedAppointments / $totalAppointments) *400, 2) : 0;

// Low Stock Vaccines Integration
$lowStockMessage = 'No vaccines with low stock'; // Default message
$lowStockPercentage = 0; // Default percentage

// Query to fetch low stock vaccines (stock less than 30)
$lowStockVaccinesQuery = "SELECT vaccine_name, stock FROM inventory WHERE stock < 15";
$lowStockVaccinesResult = $conn->query($lowStockVaccinesQuery);

// Store low-stock vaccines in an array
$lowStockVaccines = [];
if ($lowStockVaccinesResult->num_rows > 0) {
    $lowStockMessage = 'Low Stock Vaccines:';
    $totalStock = 0;
    $totalLowStock = 0;

    while ($row = $lowStockVaccinesResult->fetch_assoc()) {
        $lowStockVaccines[] = $row;
        $totalStock += $row['stock'];
        $totalLowStock += $row['stock'];
    }

    // Calculate the percentage of low stock vaccines
    $lowStockPercentage = ($totalStock > 0) ? round(($totalLowStock / $totalStock) * 100, 2) : 0;
}




$stmt->close();
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
    <title>ImmuniTrack - <?php echo htmlspecialchars($barangayName); ?> Dashboard</title>
    <style>
/* Style the brand container */
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
    

    /* Style adjustments */
    .head-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .head-title .left h1 {
        margin: 0;
    }
    .head-title .left p {
        margin: 10px 0;
        color: #666;
    }
    .breadcrumb {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        gap: 5px;
    }
    .breadcrumb li {
        display: inline;
    }
    .breadcrumb li a {
        text-decoration: none;
        color: #007bff;
    }
    .breadcrumb li i {
        color: #666;
    }
    .long-box {
        width: 100%;
        height: 120px;
        border-radius: 5px;
        margin: 0 0 30px 0;
        display: flex;
        align-items: center;
        justify-content: left;
        text-align: left;
        padding: 10px;
    }
    .text-black {
        color: black;
    }
    .text-blue {
        color: blue;
    }
    .new-box {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 20px;
        padding: 10px;
        display: inline-block;
        max-width: 300px;
        width: fit-content;
        transition: all 0.3s ease;
    }
    .box-container {
        display: flex;
        gap: 70px;
        margin-top: 20px;
    }
    .new-box, .side-box, .third-box {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 20px;
        margin-top: 20px;
        flex: 1;
        flex-basis: 200px;
        max-width: 300px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: left;
        transition: all 0.3s ease;
    }
    .side-box h3 i {
        font-size: 25px;
        margin-right: 10px;
        vertical-align: middle;
    }
    
    /* Hover Effect for All Boxes */
    .side-box:hover, .new-box:hover, .third-box:hover {
        background-color: #f5f5f5;
        border-color: #007bff;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
        transform: translateY(-3px);
        cursor: pointer;
    }

    /* Pulse effect on icon during hover */
    .side-box:hover .pulse-icon, .new-box:hover .pulse-icon, .third-box:hover .pulse-icon {
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .side-box h3 {
        font-size: 20px;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .side-box p {
        margin: 10px 0;
        color: #555;
        font-size: 16px;
    }

    .pulse-icon {
        margin-right: 10px;
        transition: transform 0.3s ease;
    }

    /* Feedback Button Styling */
    #send-feedback-btn {
        margin-top: 10px;
        padding: 15px 15px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s;
    }

    #send-feedback-btn:hover {
        background-color: white;
        color: #007bff;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        text-align: center;
    }

    .close-btn {
        float: right;
        font-size: 24px;
        cursor: pointer;
    }

    .rating-stars i {
        font-size: 24px;
        color: #ccc;
        cursor: pointer;
    }

    .rating-stars i.active {
        color: #FF6600;
    }

    /* Feedback Form Styles */
    .feedback-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .feedback-form select,
    .feedback-form textarea,
    .feedback-form button {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }

    .feedback-form button {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .feedback-form button:hover {
        background-color: #0056b3;
    }

    /* Custom Scrollbar for .scrollable-activities */
    .scrollable-activities::-webkit-scrollbar {
        width: 8px;
    }

    .scrollable-activities::-webkit-scrollbar-track {
        background: #eaf5f9;
        border-radius: 8px;
    }

    .scrollable-activities::-webkit-scrollbar-thumb {
        background-color: #c1e4fb;
        border-radius: 8px;
    }

    .scrollable-activities::-webkit-scrollbar-thumb:hover {
        background-color: #a0d3f2;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        text-align: center;
    }

    .close-btn {
        float: right;
        font-size: 24px;
        cursor: pointer;
    }

    #activityModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    #activityModal > div {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        max-width: 500px;
        margin: auto;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
    margin-top: 10px; /* Adds extra space at the top of the main content */
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
        <li class="active">
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
        <li>
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

       <!-- MAIN CONTENT -->
<main>


<!-- Container for Side-by-Side Layout -->
<div class="left" style="display: flex; gap: 20px; align-items: flex-start; margin-top: 0px; width: 100%;">
<!-- Main Welcome Box -->
<div class="long-box" style="background-color: #D8EFD3; padding: 20px;  border-radius: 8px; box-shadow: 0px 50px 10px rgba(0, 0, 0, 0.15); flex: 1; min-width: 300px; width: auto; height: auto;">
    <p class="greeting-text" style="margin: 0; font-size: 1em; line-height: 1.5; color: #333;">
        Welcome, <strong class="text-black" style="color: #007bff;"><?php echo htmlspecialchars($barangayName); ?></strong> Dashboard!<br><br>
        <span style="font-size: 0.95em; color: #555;">
            Stay updated on events, access health tips, and collaborate with health workers to make a positive impact in your community. Your efforts in promoting health and wellness are invaluable, and we're here to support you in every step.
        </span>
    </p>
</div>

<!-- Second Box: Line Graph for Newborn Birth Count -->
<div class="box" style="flex: 1; padding: 20px; display: flex; flex-direction: column; align-items: flex-start;">
    <!-- Section for Icon and Text -->
    <div style="margin-bottom: 10px;">
        <p style="margin: 0; color: #333; font-size: 1em; font-weight: bold;">
            Newborn Birth Count
        </p>
    </div>



    <!-- Canvas for the Line Graph -->
    <canvas id="childrenRegistrationChart" style="width: 130%; height: 310px;"></canvas>
</div>





</div>

<div style="display: flex; justify-content: space-between; gap: 20px;">
    <!-- Vaccination Metrics Box -->
    <div style="flex: 1; min-width: 48%; padding: 20px;  border-radius: 8px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2), 0 0 10px rgba(255, 255, 255, 0.8); background-color: #eaf5f9;">
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">Vaccination Metrics</h3>
        
<!-- Vaccination Completion Rate -->
<p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
    Vaccination Completion Rate: <strong><?php echo $percentageVaccinated; ?>%</strong>
</p>
<div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
    <div style="width: <?php echo $percentageVaccinated; ?>%; height: 20px; background-color: #4caf50; text-align: center; color: white; font-weight: bold; line-height: 20px;">
        <?php echo $percentageVaccinated; ?>%
    </div>
</div>
        <p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
            Missed Appointments Rate: <strong><?php echo $missedAppointmentsRate; ?>%</strong>
        </p>
        <div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 5px;">
            <div style="width: <?php echo $missedAppointmentsRate; ?>%; height: 20px; background-color: #f44336; text-align: center; color: white; font-weight: bold; line-height: 20px;">
                <?php echo $missedAppointmentsRate; ?>%
            </div>
        </div>
        
        <!-- Most Common Vaccine -->
        <p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
            Most Common Vaccine: <strong><?php echo $mostCommonVaccine; ?> (<?php echo $vaccineCount; ?> doses)</strong>
        </p>
        <div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
            <div style="width: <?php echo $percentageVaccinated; ?>%; height: 20px; background-color: #2196f3; text-align: center; color: white; font-weight: bold; line-height: 20px;">
                <?php echo $percentageVaccinated; ?>%
            </div>
        </div>
        
        <p style="font-size: 14px; line-height: 1.6; margin-bottom: 5px;">
    <?php 
    if (count($lowStockVaccines) > 0) {
        $minStockVaccine = $lowStockVaccines[0];
        foreach ($lowStockVaccines as $vaccine) {
            if ($vaccine['stock'] < $minStockVaccine['stock']) {
                $minStockVaccine = $vaccine;
            }
        }
        echo $lowStockMessage . " " . htmlspecialchars($minStockVaccine['vaccine_name']) . " ";
    } else {
        echo "No vaccines with low stock.";
    }
    ?>
</p>







<!-- Progress bar to show vaccine low stock level -->
<div style="width: 100%; background-color: #f3f3f3; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
    <?php
    // Calculate the total stock of all low stock vaccines
    $totalLowStock = 0;
    foreach ($lowStockVaccines as $vaccine) {
        $totalLowStock += $vaccine['stock'];
    }

    // Calculate the percentage based on the stock of the vaccine with the lowest stock
    $lowStockPercentage = ($totalLowStock > 0) ? round(($minStockVaccine['stock'] / $totalLowStock) * 100, 2) : 0;
    ?>
    <div style="width: <?php echo $lowStockPercentage; ?>%; height: 20px; background-color: #ff9800; text-align: center; color: white; font-weight: bold; line-height: 20px;">
        <?php echo $lowStockPercentage; ?>%
    </div>
</div>
    </div>

    <div class="activities-box" style="background-color: #eaf5f9; padding: 20px;  border-radius: 8px; box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.1); flex: 1; min-width: 300px;">
    <div style="background-color: #0077b6; padding: 5px; border-radius: 5px; margin-bottom: 20px; display: inline-block;">
        <p style="margin: 0; color: white; font-size: 1em; font-weight: bold; display: flex; align-items: center;">
            Upcoming Activities in <?php echo htmlspecialchars($barangayName); ?>
        </p>
    </div>


        <!-- Scrollable activities container -->
        <div class="scrollable-activities" style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
            <ul style="list-style-type: disc; padding-left: 20px; font-size: 0.95em; color: #555;">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $activity): ?>
                        <li style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                            <strong><?php echo htmlspecialchars($activity['activity_name']); ?></strong><br>
                            <span style="font-size: 0.9em; color: #0B60B0;"><?php echo htmlspecialchars(date('F j, Y', strtotime($activity['activity_date']))); ?></span><br>
                            <span style="font-size: 0.85em; color: #021526;"><?php echo htmlspecialchars($activity['activity_description']); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="padding: 10px 0; text-align: center;">No recent activities.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>




</main>
        <!-- MAIN CONTENT -->
    </section>
    <!-- CONTENT -->
<script>
    // Function to open the modal and display activity details
    function openModal(activityType, activityDescription) {
        // Set the modal content dynamically
        document.getElementById("modalTitle").innerHTML = activityType;  // Activity type as title
        document.getElementById("modalDescription").innerHTML = activityDescription; // Activity description
        // Show the modal
        document.getElementById("activityModal").style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
        document.getElementById("activityModal").style.display = "none";
    }
// JavaScript to handle modal display and star rating

// Elements for feedback modal
const feedbackBtn = document.getElementById('send-feedback-btn');
const feedbackModal = document.getElementById('feedback-modal');
const closeFeedbackModal = document.getElementById('close-modal');
const stars = document.querySelectorAll('#rating-stars i');

// Open the feedback modal on button click
feedbackBtn.addEventListener('click', () => {
    feedbackModal.style.display = 'flex';
});

// Close the feedback modal when clicking the "x" button
closeFeedbackModal.addEventListener('click', () => {
    feedbackModal.style.display = 'none';
});

// Close the feedback modal when clicking outside the modal content
window.addEventListener('click', (event) => {
    if (event.target == feedbackModal) {
        feedbackModal.style.display = 'none';
    }
});

// Star rating logic
stars.forEach((star, index) => {
    star.addEventListener('click', () => {
        stars.forEach((s, i) => {
            s.classList.toggle('active', i <= index);
        });
    });
});

// Function to update the time
function updateTime() {
    const now = new Date();
    const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila' };
    const timeString = now.toLocaleTimeString('en-US', options);
    document.getElementById('current-time').textContent = timeString;
}



// Function to show activity details (via AJAX or other method)
function showActivityDetails(activityId) {
    // Fetch activity details via AJAX or show a modal with data
    fetch('get-activity-details.php?id=' + activityId)
        .then(response => response.json())
        .then(data => {
            // Insert activity data into modal
            const modalContent = document.getElementById('activity-details-content');
            modalContent.innerHTML = `
                <h2>${data.title}</h2>
                <p>${data.description}</p>
                <p>Date: ${data.date}</p>
                <p>Location: ${data.location}</p>
            `;
            // Show modal
            document.getElementById('activity-modal').style.display = 'block';
        })
        .catch(error => console.log('Error fetching activity details:', error));
}

// Function to open the modal and populate it with activity details
function openModal(activityType, activityDescription) {
    // Update the modal title and description
    const title = document.getElementById('modalTitle');
    const description = document.getElementById('modalDescription');

    // Set the title based on the activity type
    switch (activityType) {
        case 'update':
            title.textContent = 'Important Updates';
            break;
        case 'event':
            title.textContent = 'Upcoming Events';
            break;
        case 'health_tip':
            title.textContent = 'Health Tips';
            break;
        default:
            title.textContent = 'General Activity';
    }

    // Set the activity description
    description.textContent = activityDescription;

    // Show the modal
    document.getElementById('activityModal').style.display = 'flex';
}

// Function to close the modal (activity modal)
function closeActivityModal() {
    document.getElementById('activityModal').style.display = 'none';
}

// Close the activity modal when clicking outside of the modal content
window.addEventListener('click', function(event) {
    if (event.target === document.getElementById('activityModal')) {
        closeActivityModal();
    }
});

// Close the modal when clicking the "x" button for activity modal
const closeActivityBtn = document.getElementById('close-activity-btn');
closeActivityBtn.addEventListener('click', closeActivityModal);
</script>
<script>
    function showActivityDetails(title, description, date) {
        // Set the content for the modal
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalDescription').innerText = description;
        document.getElementById('modalDate').innerText = date;
        
        // Show the modal
        document.getElementById('activityDetailsModal').style.display = 'flex';
    }
    
    function closeModal() {
        // Hide the modal
        document.getElementById('activityDetailsModal').style.display = 'none';
    }
    // Function to show activity details in the modal
function showActivityDetails(title, description, date) {
    // Set modal content
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDescription').innerText = description;
    document.getElementById('modalDate').innerText = date;

    // Show the modal
    document.getElementById('activityModal').style.display = 'flex';
}

// Function to close the modal
function closeModal() {
    document.getElementById('activityModal').style.display = 'none';
}

</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Sample data for the activity chart (replace this with your dynamic data)
    var activityData = {
        labels: ['Event 1', 'Event 2', 'Event 3', 'Event 4'], // Activity Titles
        datasets: [{
            label: 'Activity Frequency',
            data: [5, 10, 8, 12], // Number of occurrences for each event
            backgroundColor: 'rgba(99, 132, 255, 0.2)',
            borderColor: 'rgba(99, 132, 255, 1)',
            borderWidth: 1
        }]
    };

    // Chart.js configuration for the activity chart
    var ctxActivity = document.getElementById('activityChart').getContext('2d');
    var activityChart = new Chart(ctxActivity, {
        type: 'bar', // Bar chart
        data: activityData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<script>
// PHP data injected into JavaScript
var boysData = <?php echo json_encode($boysBirthCounts); ?>; // Boys data
var girlsData = <?php echo json_encode($girlsBirthCounts); ?>; // Girls data

// Chart.js code
var ctxChildren = document.getElementById('childrenRegistrationChart').getContext('2d');
var childrenRegistrationChart = new Chart(ctxChildren, {
    type: 'line', // Line chart
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], // Months
        datasets: [
            {
                label: 'Male Infants', // Boys line
                data: boysData,
                borderColor: 'rgba(54, 162, 235, 1)', // Blue border for boys
                fill: false, // No background color
                tension: 0, // Straight lines
                borderWidth: 2
            },
            {
                label: 'Female Infants', // Girls line
                data: girlsData,
                borderColor: 'rgba(255, 99, 132, 1)', // Pink border for girls
                fill: false, // No background color
                tension: 0, // Straight lines
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true, // Show the legend
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true, // Start y-axis at 0
                ticks: {
                    stepSize: 5 // Adjust step size
                },
                title: {
                    display: true,
                    text: 'New Births'
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
});



setInterval(updateTime, 1000); // Update time every second
updateTime(); // Initial call to update time
</script>
</body>
</html>
