<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Database connection settings
$host = 'localhost';        // Database host
$db = 'immuni_track';       // The database name
$user = 'root';             // Your database username
$pass = '12345';            // Your database password

try {
    // Create a PDO instance (connect to the database)
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Check for 'add_activity' action to save event data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_activity') {
    $activityName = $_POST['activity_name'];
    $activityDate = $_POST['activity_date'];
    $activityDescription = $_POST['activity_description'];
    $activityTime = $_POST['activity_time'];
    $activityLocation = $_POST['activity_location'];
    $targetAudience = $_POST['target_audience'];
    $barangayId = 1; // Replace with the appropriate barangay ID if dynamic

    // Prepare the SQL statement to insert data into the activities table
    $stmt = $pdo->prepare("INSERT INTO activities (activity_name, activity_date, activity_description, activity_time, activity_location, target_audience, barangay_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$activityName, $activityDate, $activityDescription, $activityTime, $activityLocation, $targetAudience, $barangayId])) {
        echo "success";
    } else {
        echo "error";
    }
    exit();
}

// Fetch user and child data (existing code)...
$email = $_SESSION['email'];
$query = "SELECT email FROM usertable WHERE email = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $initial = strtoupper(substr($user['email'], 0, 1));
    $profileImageUrl = "https://ui-avatars.com/api/?name={$initial}&background=random&color=fff";
} else {
    header('Location: login-user.php');
    exit();
}

// Get the child ID from the URL
$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "
    SELECT 
        c.id, 
        c.first_name, 
        c.last_name, 
        c.date_of_birth, 
        c.gender, 
        p.parent_name, 
        p.address,
        p.phone_number,
        c.birth_weight, 
        c.birth_head_circumference, 
        c.birth_length
    FROM 
        children c
    JOIN 
        parents p ON c.parent_id = p.id
    WHERE 
        c.id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$child_id]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    header('Location: children.php');
    exit();
}

// Fetch vaccination records for the child
$query = "
    SELECT 
        vaccine_name, 
        vaccination_date, 
        administered_by,
        age_in_months
    FROM 
        vaccination_records
    WHERE 
        child_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$child_id]);
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process vaccination form submission (existing code)...
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vaccine_name'])) {
    $vaccine_name = isset($_POST['vaccine_name']) ? $_POST['vaccine_name'] : '';
    $vaccination_date = isset($_POST['vaccination_date']) ? $_POST['vaccination_date'] : '';
    $administered_by = isset($_POST['administered_by']) ? $_POST['administered_by'] : '';
    $age_in_months = isset($_POST['age_in_months']) ? (int)$_POST['age_in_months'] : 0;
    $next_vaccine_name = isset($_POST['next_vaccine_name']) ? $_POST['next_vaccine_name'] : '';
    $next_vaccination_date = isset($_POST['next_vaccination_date']) ? $_POST['next_vaccination_date'] : '';

    if ($vaccine_name && $vaccination_date && $administered_by && $age_in_months >= 0) {
        // Insert vaccination record
        $query = "
            INSERT INTO vaccination_records (child_id, vaccine_name, vaccination_date, administered_by, age_in_months, next_vaccine_name, next_vaccination_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$child_id, $vaccine_name, $vaccination_date, $administered_by, $age_in_months, $next_vaccine_name, $next_vaccination_date]);

        // Update the stock in the inventory
        $query = "
            UPDATE inventory
            SET stock = stock - 1
            WHERE vaccine_name = ?
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$vaccine_name]);

        // Send notification SMS about the vaccination and the next vaccine
        sendVaccinationMessage($child['phone_number'], $vaccine_name, $next_vaccine_name, $next_vaccination_date);

        // Refresh the page to display the updated records
        header("Location: children-data.php?id=$child_id");
        exit();
    }
}

// Function to send SMS using Infobip API (existing code)
function sendVaccinationMessage($phoneNumber, $vaccineName, $nextVaccineName, $nextVaccinationDate) {
    $url = 'https://e51xeq.api.infobip.com/sms/2/text/advanced'; // Infobip API endpoint
    $apiKey = 'd22ebfa85a991b5facafe4166570de5a-5951565d-b0cc-4f36-a488-e16d5ca72eb0'; // Replace with your actual API key

    $data = [
        'messages' => [
            [
                'from' => 'ImmuniTrack',
                'destinations' => [
                    ['to' => $phoneNumber]
                ],
                'text' => "Your child has successfully received the $vaccineName. The next vaccination is scheduled for $nextVaccinationDate. Thank you for keeping them safe!"
            ]
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: App $apiKey\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        error_log("Failed to send SMS to $phoneNumber");
    }
}

// Format the current date
$currentDate = date('l, d/m/Y');
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>ImmuniTrack - Children Details</title>
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
        /* Container styling */
        .container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Form container styling */
        .form-container {
            width: 100%;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }

        /* Form styling */
        .form-container form {
            display: flex;
            flex-direction: column;
            align-items: center; /* Center the button */
        }

        .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 5px; /* Existing gap */
        margin-bottom: 10px; /* Add spacing between form rows */
        width: 100%;
        }

        .form-row input {
            flex: 1;
            min-width: 48%; /* Make sure input fields don't get too narrow */
            padding: 8px;
            box-sizing: border-box;
        }

        .form-container button {
            margin-top: 20px; /* Space above the button */
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            align-self: center; /* Center the button */
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

        /* Vaccination record table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Download button styling */
        .download-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            text-align: center;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }

        .download-btn:hover {
            background-color: #0056b3;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }

            .form-row input {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        /* Active Sidebar Item */
#sidebar .side-menu li.active a {
    background-color: #4CAF50;  /* Green background for active item */
    color: white;  /* White text for active link */
}

#sidebar .side-menu li.active a i,
#sidebar .side-menu li.active a .text {
    color: white;  /* White color for icon and text when active */
}

/* Hover state for active sidebar link */
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


    .container {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    max-width: 1000px;
    margin: 20px auto;
    /* Removed background, padding, and box shadow */
}

.child-info {
    display: flex;
    justify-content: space-between;
    width: 100%;
}

.column {
    flex: 1;
    padding: 0; /* Remove padding */
    margin: 0;  /* Remove margin */
    border: none; /* Remove any border */
    background: none; /* Remove any background */
    margin-bottom: 20px;
}



/* Ensure responsive behavior */
@media (max-width: 768px) {
    .child-info {
        flex-direction: column; /* Stack columns on small screens */
    }
    .column {
        padding: 10px;
    }
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
            <li class="">
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
            <li class="active">
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
        <div class="container">
        <div class="child-info">
    <div class="column">
        <p>Name: <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></p>
        <p>Date of Birth: <?php echo htmlspecialchars($child['date_of_birth']); ?></p>
        <p>Gender: <?php echo htmlspecialchars($child['gender']); ?></p>
        <p>Parent Name: <?php echo htmlspecialchars($child['parent_name']); ?></p>
    </div>
    <div class="column">
        <p>Address: <?php echo htmlspecialchars($child['address']); ?></p>
        <p>Phone Number: <?php echo htmlspecialchars($child['phone_number']); ?></p>
        <p>Birth Weight (kg): <?php echo htmlspecialchars($child['birth_weight']); ?></p>
        <p>Birth Head Circumference (cm): <?php echo htmlspecialchars($child['birth_head_circumference']); ?></p>
        <p>Birth Length (cm): <?php echo htmlspecialchars($child['birth_length']); ?></p>
    </div>
</div>



    <div class="form-container">
        <form action="children-data.php?id=<?php echo htmlspecialchars($child_id); ?>" method="post">
            <div class="form-row">
                <input type="text" name="vaccine_name" placeholder="Vaccine Name" required>
                <input type="date" name="vaccination_date" placeholder="Vaccination Date" required>
            </div>
            <div class="form-row">
                <input type="text" name="administered_by" placeholder="Administered By" required>
                <input type="number" name="age_in_months" placeholder="Age in Months" required>
            </div>
            <div class="form-row">
                <input type="text" name="next_vaccine_name" placeholder="Next Vaccine Name" required>
                <input type="date" name="next_vaccination_date" placeholder="Next Vaccination Date" required>
            </div>
            <button type="submit">Add Vaccination Record</button>
        </form>
    </div>

    <h3 style="font-weight: normal;">Vaccination Records</h3>
    <table>
        <thead>
            <tr>
                <th style="font-weight: normal; text-align: center;">Vaccine Name</th>
                <th style="font-weight: normal; text-align: center;">Vaccination Date</th>
                <th style="font-weight: normal; text-align: center;">Administered By</th>
                <th style="font-weight: normal; text-align: center;">Age in Months</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vaccinations as $record): ?>
                <tr>
                    <td style="text-align: center;"><?php echo htmlspecialchars($record['vaccine_name']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($record['vaccination_date']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($record['administered_by']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($record['age_in_months']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>



            <!-- Download button -->
            <a href="download-records.php?id=<?php echo htmlspecialchars($child_id); ?>" class="download-btn">Download Records</a>
        </main>
        <!-- MAIN CONTENT -->
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

