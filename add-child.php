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
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

// Get logged-in user's email
$userEmail = $_SESSION['email'];

// Retrieve the user's associated barangay
$query = "SELECT barangay_id FROM barangay WHERE user_id = (SELECT id FROM usertable WHERE email = ?)";
$stmt = $pdo->prepare($query);
$stmt->execute([$userEmail]);
$barangay = $stmt->fetch(PDO::FETCH_ASSOC);
$barangayId = $barangay['barangay_id'] ?? null; // Use null coalescing operator for safety

// Handle form submission
$successMessage = $errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize form inputs
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $ageOfRegistration = filter_input(INPUT_POST, 'age_of_registration', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $parentName = filter_input(INPUT_POST, 'parent_name', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $dateOfBirth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);

    // New fields for birth information
    $birthWeight = filter_input(INPUT_POST, 'birth_weight', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $birthHeadCircumference = filter_input(INPUT_POST, 'birth_head_circumference', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $birthLength = filter_input(INPUT_POST, 'birth_length', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Check if form data is complete
    if (!$firstName || !$lastName || !$ageOfRegistration || !$gender || !$parentName || !$address || !$phoneNumber || !$dateOfBirth || !$birthWeight || !$birthHeadCircumference || !$birthLength) {
        $errorMessage = "Please fill out all fields.";
    } else {
        // Check if the parent already exists
        $query = "SELECT id FROM parents WHERE parent_name = ? AND address = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$parentName, $address]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        // If parent does not exist, insert into parents table with username and password
        if (!$parent) {
            $insertParentQuery = "INSERT INTO parents (parent_name, address, phone_number, username, password) VALUES (?, ?, ?, ?, ?)";
            $insertParentStmt = $pdo->prepare($insertParentQuery);
            $username = $phoneNumber; // Set username as the phone number
            $password = 'ImmuniTrack2024'; // Set password as 'ImmuniTrack2024'
            $insertParentStmt->execute([$parentName, $address, $phoneNumber, $username, $password]);
            $parentId = $pdo->lastInsertId(); // Get the last inserted parent's ID
        } else {
            $parentId = $parent['id']; // Use existing parent's ID
        }

        // Insert new child record into the database, including birth info
        $query = "
            INSERT INTO children (first_name, last_name, date_of_birth, age_of_registration, gender, parent_id, barangay_id, birth_weight, birth_head_circumference, birth_length, registration_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$firstName, $lastName, $dateOfBirth, $ageOfRegistration, $gender, $parentId, $barangayId, $birthWeight, $birthHeadCircumference, $birthLength]);

            // Check if data was inserted successfully
            if ($stmt->rowCount() > 0) {
                // Send welcome message using Infobip API
                sendWelcomeMessage($phoneNumber, $parentName, $firstName); // Updated call with parent and child name

                $successMessage = "Child added successfully and a welcome message has been sent!";
                header('Location: children.php'); // Redirect after successful insertion
                exit();
            } else {
                $errorMessage = "Failed to add child. Please try again.";
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}

// Function to send SMS using Infobip API
function sendWelcomeMessage($phoneNumber, $parentName, $childFirstName) {
    $url = 'https://e51xeq.api.infobip.com/sms/2/text/advanced'; // Infobip API endpoint
    $apiKey = 'd22ebfa85a991b5facafe4166570de5a-5951565d-b0cc-4f36-a488-e16d5ca72eb0'; // Replace with your actual API key

    $data = [
        'messages' => [
            [
                'from' => 'ImmuniTrack',
                'destinations' => [
                    ['to' => $phoneNumber]
                ],
                'text' => "Hello $parentName, welcome to ImmuniTrack! Your child $childFirstName has been successfully registered."
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
        // Handle errors if needed
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
    <title>ImmuniTrack - Add Child</title>
    <style>
    /* Main Content Styling */
    main {
        background-color: #D8EFD3; /* Light green background color */
        padding: 20px; /* Adds padding around the content */
        border-radius: 10px; /* Rounds the corners */
        box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1); /* Shadow for depth */
        margin: 20px; /* Adds spacing from surrounding elements */
    }

    /* Sidebar Brand Styling */
    #sidebar .brand {
        text-align: center;
    }

    #sidebar .brand .text-box {
        background-color: #ffffff; /* White background for brand box */
        padding: 5px 10px; /* Adds padding */
        border-radius: 5px; /* Rounded corners */
        display: inline-block; /* Makes box fit content */
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); /* Shadow for depth */
    }

    #sidebar .brand .text {
        font-size: 20px;
        color: #4CAF50; /* Green text color */
        letter-spacing: 1px;
        line-height: 1;
        text-transform: uppercase;
        margin-left: 5px;
    }

    /* Pulse Effect for Brand Text */
    .pulse-text {
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

/* Form Styling */
.small-form {
    max-width: 1000px; /* Width for form */
    margin: 30px auto;
    padding: 20px;
    border-radius: 5px;
    background-color: white; /* White background */
    box-shadow: 0 25px 20px rgba(0, 0, 0, 0.15); /* Enhanced shadow */
}


.form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* Three equal-width columns */
    gap: 20px; /* Adds space between columns */
    margin: -10px;
}

.form-column {
    display: flex;
    flex-direction: column;
    padding: 10px;
    min-width: 200px; /* Ensures form fields are evenly distributed */
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.form-group button {
    padding: 10px 20px;
    background-color: #4CAF50; /* Green button */
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

/* Fixed Action Button */
button[type="submit"], .add-btn {
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 11px 40px;
    cursor: pointer;
    font-size: 17px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    position: fixed;
    bottom: 40px;
    right: 20px;
    transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
}

button[type="submit"]:hover, .add-btn:hover {
    background-color: #45a049;
    transform: scale(1.05);
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.25);
}


    /* Active Sidebar Item */
    #sidebar .side-menu li.active a {
        background-color: #4CAF50;
        color: white;
    }

    #sidebar .side-menu li.active a i,
    #sidebar .side-menu li.active a .text {
        color: white;
    }

    #sidebar .side-menu li.active a:hover {
        background-color: #388E3C; /* Darker green on hover */
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

        <!-- MAIN -->
        <main>
        <div class="small-form">
    <form action="add-child.php" method="post">
        <div class="form-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <!-- First Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" required="">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" required="">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required="">
                </div>
                <div class="form-group">
                    <label for="age_of_registration">Age of Registration (in month/s):</label>
                    <input type="number" id="age_of_registration" name="age_of_registration" required="">
                </div>
            </div>

            <!-- Second Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="birth_weight">Birth Weight (kg):</label>
                    <input type="number" id="birth_weight" name="birth_weight" step="0.01" required="">
                </div>
                <div class="form-group">
                    <label for="birth_head_circumference">Birth Head Circumference (cm):</label>
                    <input type="number" id="birth_head_circumference" name="birth_head_circumference" step="0.01" required="">
                </div>
                <div class="form-group">
                    <label for="birth_length">Birth Length (cm):</label>
                    <input type="number" id="birth_length" name="birth_length" step="0.01" required="">
                </div>
                <!-- Align Gender with the other fields in the third column -->
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required="">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
            </div>

            <!-- Third Column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="parent_name">Parent Name:</label>
                    <input type="text" id="parent_name" name="parent_name" required="">
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required="">
                </div>
                <div class="form-group">
                    <label for="phone_number">Phone Number:</label>
                    <input type="text" id="phone_number" name="phone_number" required="">
                </div>
                <!-- Blank row to maintain equal column height -->
                <div class="form-group"></div>
            </div>
        </div>
        <div class="form-group" style="display: flex; justify-content: center; margin-top: 20px;">
            <button type="submit" class="add-btn">
                <i class="bx bxs-plus-circle" style="font-size: 15px; margin-right: 10px;"></i>
                Add
            </button>
        </div>
    </form>
</div>



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

        setInterval(updateTime, 1000); // Update time every second
        updateTime(); // Initial call
    </script>
</body>
</html>
