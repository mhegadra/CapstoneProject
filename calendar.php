<?php 
session_start(); // Start the session

// Database connection settings
$host = 'localhost';        // Database host
$db = 'immuni_track';       // Database name
$user = 'root';             // Database username
$pass = '12345';            // Database password

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

// Get the user's email and fetch the user's barangay ID and initials from the database
$email = $_SESSION['email'];
$sql = "SELECT barangay_id, initials FROM usertable WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $barangayId = $row['barangay_id'];
    $userInitials = $row['initials'];
    
    // Use only the first letter of the initials for the profile image
    $profileInitial = substr($userInitials, 0, 1);
} else {
    echo "User not found.";
    exit();
}

// Fetch barangay details
$sql = "SELECT barangay_name FROM barangay WHERE barangay_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $barangayId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $barangayName = $row['barangay_name'];
} else {
    $barangayName = "Unknown Barangay";
}

$stmt->close();

// Generate profile image URL from the first letter of the initials
$profileImageUrl = "https://ui-avatars.com/api/?name=" . urlencode($profileInitial) . "&background=random&color=fff";

// Fetch vaccination schedules (Assuming you have a query to fetch vaccination schedules here)

// Format the current date
$currentDate = date('l, d/m/Y');

if (isset($_POST['action']) && $_POST['action'] == 'add_activity') {
    // Sanitize input data
    $activityName = $_POST['activity_name'];
    $activityDate = $_POST['activity_date'];
    $activityDescription = $_POST['activity_description'];
    $activityTime = $_POST['activity_time'];
    $activityLocation = $_POST['activity_location'];
    $targetAudience = $_POST['patients'];

    // Insert activity into the database
    $query = "INSERT INTO activities (activity_name, activity_date, activity_description, barangay_id, activity_time, activity_location, patients) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssisss', $activityName, $activityDate, $activityDescription, $barangayId, $activityTime, $activityLocation, $targetAudience);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "failure";
    }
    $stmt->close();
}

// Fetch all unique activities for the specific barangay
$sql = "SELECT DISTINCT activity_name, activity_date, activity_description, activity_time, activity_location, patients 
        FROM activities 
        WHERE barangay_id = ? 
        ORDER BY activity_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $barangayId);
$stmt->execute();
$result = $stmt->get_result();

$allActivities = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allActivities[] = $row;
    }
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();

// Close the connection after all operations
$conn->close();

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.css' rel='stylesheet' />
    <title>ImmuniTrack - Calendar</title>
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

        .calendar-container {
            display: flex; /* Enable flexbox for centering */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically if height allows */
            max-width: 90%; /* Ensure it takes the full width of the parent */
            margin: 0px auto; /* Center the calendar container */
        }

        #calendar {
            width: 100%;
            max-width: 900px;
            min-width: 900px;
            background-color: #ffffff;
            border-radius: 5px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 550px;
            overflow: hidden;
        }

        /* Day grid styling */
        .fc-daygrid-day {
            border: 1px solid #ddd;
            background-color: #fafafa;
        }

        .fc-day-today {
            background-color: #B7B7B7 !important;
        }

        .vaccination-container {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    margin-bottom: 70px;
}

/* Style for Upcoming Vaccinations and Missed Vaccinations boxes */
.upcoming-vaccinations, .missed-vaccinations {
    width: auto; /* Allows box width to adjust based on content */
    min-width: 250px; /* Set a minimum width for the boxes */
    max-width: 450px; /* Optionally you can limit the maximum width */
    padding: 20px;
    background-color: #eaf5f9;
    border: 1px solid #c5e4f7;
    border-radius: 8px;
    box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.3);
    max-height: 250px;
    overflow-y: auto;
}

/* Scrollbar styling */
.upcoming-vaccinations::-webkit-scrollbar, .missed-vaccinations::-webkit-scrollbar {
    width: 8px;
}

.upcoming-vaccinations::-webkit-scrollbar-thumb, .missed-vaccinations::-webkit-scrollbar-thumb {
    background-color: lightblue;
    border-radius: 10px;
}

.upcoming-vaccinations::-webkit-scrollbar-track, .missed-vaccinations::-webkit-scrollbar-track {
    background-color: #f1f1f1;
}

/* Header style */
.upcoming-vaccinations h2, .missed-vaccinations h2 {
    background-color: #c1e4fb;
    padding: 5px;
    border-radius: 5px;
    font-weight: normal;
    font-size: 1em;
    margin-bottom: 20px;
    white-space: nowrap; /* Prevents line break in header */
}

/* List style for vaccines */
.upcoming-vaccinations ul, .missed-vaccinations ul {
    list-style-type: none;
    padding-left: 0;
    font-size: 0.95em;
    color: #555;
}

/* Vaccine item styling */
.upcoming-vaccinations ul li, .missed-vaccinations ul li {
    display: flex; /* Aligns name and date in a single row */
    justify-content: space-between; /* Ensures space between name and date */
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #ddd;
    width: 100%; /* Ensures the entire width is used */
}

/* Vaccine name and date styles */
.vaccine-name {
    font-weight: bold;
    white-space: nowrap; /* Prevents line break */
    overflow: hidden;
    text-overflow: ellipsis; /* Adds ellipsis if name is too long */
    flex-grow: 1; /* Makes sure vaccine name uses all available space */
    margin-right: 10px; /* Adds a little space before the date */
}

.vaccine-date {
    font-size: 0.85em;
    color: #777;
    white-space: nowrap; /* Prevents line break for date */
    text-align: right; /* Ensures the date is aligned to the right */
}





h2 {
    font-size: 18px;
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
    .modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #ffffff;
    margin: 10% auto;
    padding: 15px;
    border: 1px solid #888;
    width: 80%;  /* Adjust the default width */
    max-width: 600px; /* Increase the max width for larger screens */
    min-width: 300px; /* Ensure a minimum width on small screens */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.close-btn {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 10px;
}

.close-btn:hover,
.close-btn:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

h2 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #4CAF50;
}

/* 2-Column Form Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr; /* Two columns */
    gap: 15px; /* Space between columns */
}

.form-group {
    display: flex;
    flex-direction: column;
}

label {
    font-size: 14px;
    color: #555;
}

input[type="text"], input[type="date"], input[type="time"], textarea {
    padding: 8px;
    font-size: 13px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin: 5px 0;
    width: 100%;
}

textarea {
    resize: vertical;
    min-height: 80px;
}

button {
    background-color: #4CAF50;
    color: white;
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-top: 10px;
    grid-column: span 2; /* Makes button span both columns */
}

button:hover {
    background-color: #388E3C;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modal-content {
        width: 90%; /* Adjust width on smaller screens */
        margin: 15% auto;
    }

    .form-grid {
        grid-template-columns: 1fr; /* Single column on small screens */
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
        <!-- Active Dashboard item with styling -->
        <li class="">
            <a href="dashboard.php">
                <i class='bx bxs-dashboard'></i>
                <span class="text">Dashboard</span>
            </a>
        </li>
        <!-- Active Calendar item with styling -->
        <li class="active">
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
        <main>
            
        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.js'></script>

<div class="calendar-container">
    <div id="calendar"></div>
</div>

<!-- Modal for showing activity details -->
<div id="activityModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="activityTitle">Activity Details</h2>
        <div class="modal-body">
            <div class="modal-item">
                <p class="modal-label">Date:</p>
                <p id="activityDate"></p>
            </div>
            <div class="modal-item">
                <p class="modal-label">Description:</p>
                <p id="activityDescription"></p>
            </div>
            <div class="modal-item">
                <p class="modal-label">Location:</p>
                <p id="activityLocation"></p>
            </div>
            <div class="modal-item">
                <p class="modal-label">Patients:</p>
                <p id="patients"></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal background */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

/* Modal content styling */
.modal-content {
    background-color: #ffffff;
    padding: 15px;
    width: 80%; /* Smaller width */
    max-width: 400px; /* Reduced max width */
    border-radius: 10px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    position: relative;
    animation: fadeIn 0.3s ease;
    font-size: 14px; /* Smaller font size */
    line-height: 1.4; /* Adjusted line height for readability */
}

/* Modal header styling */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
    margin-bottom: 10px;
}

.modal-header h2 {
    font-size: 16px; /* Reduced header font size */
    margin: 0;
}

/* Modal close button styling */
.close-btn {
    color: #333;
    font-size: 16px; /* Smaller close button */
    cursor: pointer;
    transition: color 0.3s;
}
.close-btn:hover {
    color: #e74c3c;
}

/* Modal body detail item styling */
.modal-body .detail-item {
    margin-bottom: 8px;
}
.modal-body .label {
    font-weight: bold;
    color: #555;
    display: inline-block;
    width: 100px; /* Align labels */
    margin-right: 5px;
    font-size: 13px; /* Uniform label size */
}
.modal-body span {
    font-size: 13px; /* Smaller detail text */
}

/* Animation for modal appearance */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}



</style>



    
<div id="activityModal" style="display: none;">
    <div class="modal-content">
        <span id="closeActivityModal" class="close">&times;</span>
        <h3>Activity Details</h3>
        <form id="activityForm">
            <label for="activity_date">Date:</label>
            <input type="date" id="activity_date" name="activity_date" required>

            <label for="activity_name">Activity Name:</label>
            <input type="text" id="activity_name" name="activity_name" required>

            <label for="activity_description">Description:</label>
            <textarea id="activity_description" name="activity_description" rows="3" required></textarea>

            <label for="activity_time">Time:</label>
            <input type="time" id="activity_time" name="activity_time" required>

            <label for="activity_location">Location:</label>
            <input type="text" id="activity_location" name="activity_location" required>

            <label for="patients">Patients:</label>
            <textarea id="patients" name="patients" rows="2"></textarea>

            <button type="submit">Save Activity</button>
        </form>
    </div>
</div>




<!-- Modal for viewing activity details -->
<div id="viewActivityModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Activity Details</h2>
            <span class="close-btn" onclick="closeViewModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="detail-item">
                <span class="label">Activity:</span>
                <span id="view_activity_name"></span>
            </div>
            <div class="detail-item">
                <span class="label">Date:</span>
                <span id="view_activity_date"></span>
            </div>
            <div class="detail-item">
                <span class="label">Description:</span>
                <span id="view_activity_description"></span>
            </div>
            <div class="detail-item">
                <span class="label">Time:</span>
                <span id="view_activity_time"></span>
            </div>
            <div class="detail-item">
                <span class="label">Location:</span>
                <span id="view_activity_location"></span>
            </div>
            <div class="detail-item">
                <span class="label">Patients:</span>
                <span id="view_patients"></span>
            </div>
        </div>
    </div>
</div>


<!-- Success message modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeSuccessModal()">&times;</span>
        <h2>Success</h2>
        <p>Activity added successfully!</p>
    </div>
</div>

<script>
// Function to close the add activity modal
function closeModal() {
    document.getElementById('activityModal').style.display = 'none';
}

// Function to close the view activity modal
function closeViewModal() {
    document.getElementById('viewActivityModal').style.display = 'none';
}

// Function to close the success modal
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
}

// JavaScript to handle viewing activity details
document.addEventListener('DOMContentLoaded', function () {
    const viewActivityModal = document.getElementById('viewActivityModal');
    const activityModal = document.getElementById('activityModal'); // Reference to the Add Activity Modal
    const activityForm = document.getElementById('activityForm');
    const successModal = document.getElementById('successModal');
    const calendarElement = document.getElementById('calendar');
    
    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarElement, {
        initialView: 'dayGridMonth',
        events: [
            <?php foreach ($allActivities as $activity) : ?>
                {
                    title: '<?php echo htmlspecialchars($activity['activity_name']); ?>',
                    start: '<?php echo htmlspecialchars($activity['activity_date']); ?>',
                    allDay: true,
                    extendedProps: {
                        description: '<?php echo htmlspecialchars($activity['activity_description']); ?>',
                        time: '<?php echo htmlspecialchars($activity['activity_time']); ?>',
                        location: '<?php echo htmlspecialchars($activity['activity_location']); ?>',
                        audience: '<?php echo htmlspecialchars($activity['patients']); ?>'
                    }
                },
            <?php endforeach; ?>
        ],
        eventClick: function (info) {
            // Populate the view activity modal with event details
            document.getElementById('view_activity_name').textContent = info.event.title;
            document.getElementById('view_activity_date').textContent = info.event.start.toISOString().split('T')[0];
            document.getElementById('view_activity_description').textContent = info.event.extendedProps.description;
            document.getElementById('view_activity_time').textContent = info.event.extendedProps.time;
            document.getElementById('view_activity_location').textContent = info.event.extendedProps.location;
            document.getElementById('view_patients').textContent = info.event.extendedProps.audience;
            viewActivityModal.style.display = 'block'; // Show the view activity modal
        },
        // Open Add Activity modal when a date is clicked
        dateClick: function (info) {
            document.getElementById('activity_date').value = info.dateStr;  // Set the date field in the form
            activityModal.style.display = 'block';  // Show the Add Activity modal
        }
    });

    calendar.render();

    // Handle form submission for adding activity
    activityForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent normal form submission
        const formData = new FormData(activityForm); // Collect form data

        // AJAX request to add the activity
        fetch('calendar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                // Extract values to dynamically add the event to the calendar
                const activityName = document.getElementById('activity_name').value;
                const activityDate = document.getElementById('activity_date').value;
                const activityDescription = document.getElementById('activity_description').value;
                const activityTime = document.getElementById('activity_time').value;
                const activityLocation = document.getElementById('activity_location').value;
                const patients = document.getElementById('patients').value;

                // Add the new event to the calendar dynamically
                calendar.addEvent({
                    title: activityName,
                    start: activityDate,
                    allDay: true,
                    extendedProps: {
                        description: activityDescription,
                        time: activityTime,
                        location: activityLocation,
                        audience: patients
                    }
                });

                // Close the modal after adding the activity
                closeModal();

                // Show success modal
                successModal.style.display = 'block';
                setTimeout(() => {
                    successModal.style.display = 'none'; // Hide the success modal after 3 seconds
                }, 3000);
            } else {
                alert('Failed to add activity.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the activity.');
        });
    });

    // Close modals when clicking the close button
    window.onclick = function (event) {
        if (event.target == activityModal) {
            closeModal();
        }
        if (event.target == viewActivityModal) {
            closeViewModal();
        }
    };

    // JavaScript to update the time every second
    function updateTime() {
        const now = new Date();
        const options = { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true, 
            timeZone: 'Asia/Manila' 
        };
        const timeString = now.toLocaleTimeString('en-US', options);
        document.getElementById('current-time').textContent = timeString;
    }
    setInterval(updateTime, 1000); // Update time every second
    updateTime(); // Initial call to display time immediately
});
</script>




</body>
</html>
