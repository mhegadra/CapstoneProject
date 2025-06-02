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

// Check if barangay_id is set in the query string
if (!isset($_GET['barangay_id'])) {
    header('Location: admin-inventory.php');
    exit();
}

$barangay_id = intval($_GET['barangay_id']);

// Fetch vaccine data for the specific barangay
$query = "
    SELECT vaccine_name, stock
    FROM inventory
    WHERE barangay_id = :barangay_id
";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
$stmt->execute();
$vaccineData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission for updating inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update vaccine quantities
        foreach ($_POST['stocks'] as $vaccine_name => $added_stock) {
            // Fetch the current stock for this vaccine
            $currentStockQuery = "
                SELECT stock FROM inventory
                WHERE barangay_id = :barangay_id AND vaccine_name = :vaccine_name
            ";
            $currentStockStmt = $pdo->prepare($currentStockQuery);
            $currentStockStmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $currentStockStmt->bindParam(':vaccine_name', $vaccine_name, PDO::PARAM_STR);
            $currentStockStmt->execute();
            $currentStock = $currentStockStmt->fetchColumn();

            // Update the stock
            $updateQuery = "
                UPDATE inventory
                SET stock = stock + :added_stock
                WHERE barangay_id = :barangay_id AND vaccine_name = :vaccine_name
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->bindParam(':added_stock', $added_stock, PDO::PARAM_INT);
            $updateStmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $updateStmt->bindParam(':vaccine_name', $vaccine_name, PDO::PARAM_STR);
            $updateStmt->execute();

            // Log the change
            $historyInsertQuery = "
                INSERT INTO vaccine_history (barangay_id, vaccine_name, previous_stock, added_stock, change_date)
                VALUES (:barangay_id, :vaccine_name, :previous_stock, :added_stock, NOW())
            ";
            $historyInsertStmt = $pdo->prepare($historyInsertQuery);
            $historyInsertStmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
            $historyInsertStmt->bindParam(':vaccine_name', $vaccine_name, PDO::PARAM_STR);
            $historyInsertStmt->bindParam(':previous_stock', $currentStock, PDO::PARAM_INT);
            $historyInsertStmt->bindParam(':added_stock', $added_stock, PDO::PARAM_INT);
            $historyInsertStmt->execute();
        }
        $_SESSION['message'] = "Inventory updated successfully!";
        header('Location: admin-inventory.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update inventory: " . $e->getMessage();
    }
}
    // Fetch vaccine history for the selected barangay
    $vaccineHistoryData = [];
    if (isset($barangay_id)) {
        $historyQuery = "
            SELECT vaccine_name, previous_stock, added_stock, change_date
            FROM vaccine_history
            WHERE barangay_id = :barangay_id
            ORDER BY change_date DESC
        ";
        $historyStmt = $pdo->prepare($historyQuery);
        $historyStmt->bindParam(':barangay_id', $barangay_id, PDO::PARAM_INT);
        $historyStmt->execute();
        $vaccineHistoryData = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    }

// Format the current date
$currentDate = date('l, d/m/Y');
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
    <title>ImmuniTrack - Update Inventory</title>
    <style>
.update-form {
    max-width: 100%; /* Makes the form take up the full width */
    margin: 0 auto; /* Centers the form horizontally */
    padding: 20px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Centers the items vertically */
    align-items: center; /* Centers the items horizontally */
    min-height: 100vh; /* Ensures it takes up the full height */
}

.vaccine-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center; /* Centers the boxes */
    width: 75%;
}

.vaccine-box {
    width: calc(45% - 20px); /* Adjust width to make boxes smaller and fit */
    padding: 20px;
    background-color: #ffffff; /* White background */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Subtle shadow for depth */
}

.vaccine-box label, .vaccine-box input {
    display: block;
    margin-bottom: 10px;
}

.vaccine-box input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da; /* Light border */
    border-radius: 4px;
}

.vaccine-box button {
    padding: 8px;
    background-color: #007bff; /* Button background color */
    border: none;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}

.vaccine-box button:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

.message, .error {
    font-size: 16px;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 5px;
}

.message {
    background-color: #d4edda;
    color: #155724;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
}

.date-time {
    font-weight: normal;
}

.clock {
    font-weight: normal;
}

.update-button {
    padding: 10px 40px;
    background-color: #007bff; /* Blue color */
    border: none;
    color: white;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px; /* Matches the font size */
    font-weight: normal; /* Adds a bold effect like in the image */
    position: fixed;  /* Makes the button fixed at a certain position */
    bottom: 20px;     /* Aligns the button 30px from the bottom of the page */
    right: 20px;      /* Aligns the button 30px from the right side */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Adds a shadow for depth */
}

.update-button:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

.plain-box {
    width: 100%; /* Ensure the box takes up the full width of its container */
    max-width: 100%; /* Ensures the box takes the entire width available */
    margin: 0 auto; /* Center the box */
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.plain-box table {
    width: 100%; /* Set to 100% to fit the container */
    border-collapse: collapse;
}

.plain-box th, .plain-box td {
    border: 1px solid #dee2e6; /* Light gray border */
    padding: 8px; /* Cell padding */
    text-align: left; /* Align text to the left */
    max-width: 100px; /* Set a max width for columns */
    overflow: hidden; /* Hide overflow */
    text-overflow: ellipsis; /* Add ellipsis for overflowed text */
    white-space: normal; /* Allow text to wrap */
}

.plain-box th {
    background-color: #e9ecef; /* Light gray background for header */
    font-weight: bold; /* Bold font for header */
    word-wrap: break-word; /* Break long words */
}

.scrollable-container {
    max-height: 400px; /* Set a maximum height */
    overflow-y: auto; /* Allow vertical scrolling */
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    white-space: nowrap; /* Prevents text wrapping */
}

th {
    font-weight: bold;
}

.month-selector-container {
    display: inline-block;
    width: 100%; /* Make it fit the header width */
}

#month-selector {
    width: 100%; /* Full width to match the header size */
    padding: 5px;
}

/* Optional: Style for the select dropdown */
select {
    padding: 1px; /* Padding for the dropdown */
    border-radius: 5px; /* Rounded corners */
    border: 1px solid #ccc; /* Border styling */
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
            <li>
                <a href="admin-barangay.php">
                    <i class='bx bxs-home'></i>
                    <span class="text">Barangay</span>
                </a>
            </li>
            <li class="active">
                <a href="admin-inventory.php">
                    <i class='bx bxs-package'></i>
                    <span class="text">Inventory</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu bottom">
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
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <form class="update-form" method="POST">
                <div class="vaccine-container">
                    <?php foreach ($vaccineData as $vaccine): ?>
                        <div class="vaccine-box">
                            <label><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></label>
                            <input
                                type="number"
                                name="stocks[<?php echo htmlspecialchars($vaccine['vaccine_name']); ?>]"
                                value="<?php echo htmlspecialchars($vaccine['stock']); ?>"
                                placeholder="Enter new stock quantity"
                            />
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="update-button">Update Stock</button>
                
                <div class="plain-box" style="width: 100%; background-color: #f8f9fa; margin-top: 10px; padding: 20px; border-radius: 8px;">
                <div class="scrollable-container" style="max-height: 400px; overflow-y: auto;">
            <table>
            <thead>
            <tr>
    <th>Vaccine Name</th>
    <th>Previous Stock</th>
    <th>Added Stock</th>
    <th>
        Change Date
        <!-- Add the month selector container behind the header -->
        <div class="month-selector-container">
            <select id="month-selector" onchange="filterByMonth()">
                <option value="">All Months</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>
        </div>
    </th>
</tr>
            </thead>
            <tbody id="vaccine-history-table">
                <?php if (!empty($vaccineHistoryData)): ?>
                    <?php foreach ($vaccineHistoryData as $history): ?>
                        <tr data-month="<?php echo htmlspecialchars(date('m', strtotime($history['change_date']))); ?>">
                            <td><?php echo htmlspecialchars($history['vaccine_name']); ?></td>
                            <td><?php echo htmlspecialchars($history['previous_stock']); ?></td>
                            <td><?php echo htmlspecialchars($history['added_stock']); ?></td>
                            <td><?php echo htmlspecialchars(date('F j, Y', strtotime($history['change_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 10px;">No history available for this barangay.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- JavaScript for filtering by month -->
<script>
    function filterByMonth() {
        const selectedMonth = document.getElementById('month-selector').value;
        const rows = document.querySelectorAll('#vaccine-history-table tr');

        rows.forEach(row => {
            const rowMonth = row.getAttribute('data-month');
            if (selectedMonth === "" || rowMonth === selectedMonth) {
                row.style.display = ''; // Show row
            } else {
                row.style.display = 'none'; // Hide row
            }
        });
    }
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
