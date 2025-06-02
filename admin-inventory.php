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
$query = "
SELECT b.barangay_name, b.barangay_id, i.vaccine_name, i.stock
FROM barangay b
LEFT JOIN inventory i ON b.barangay_id = i.barangay_id
";



$stmt = $pdo->prepare($query);
$stmt->execute();
$barangayVaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>ImmuniTrack - Inventory Management</title>
    <style>
        /* Existing styles */
        .main-content {
            max-width: 800px;
            margin: auto;
            padding: 5px;
        }

/* Table Styles */
.inventory-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.inventory-table th,
.inventory-table td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

.inventory-table th {
    background-color: #f1f1f1;
    font-weight: bold;
}

.inventory-table tr:hover {
    background-color: #f9f9f9;
    cursor: pointer;
}

.inventory-table .view-button {
    padding: 10px 12px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.inventory-table .view-button:hover {
    background-color: #0056b3;
}

/* Active and hover effects for rows */
.inventory-item:hover {
    background-color: #e9f5ff;
}

.inventory-item td {
    vertical-align: middle;
}
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
        <!-- MAIN -->
        <main>
        <div class="main-content">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>Barangay</th>
                <th>Total Stock</th>
                <th>Low Stock Vaccines</th>
                <th>Manage Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php
                    // Initialize an array to group barangays and their vaccines
        // Initialize an array to group barangays and their vaccines
        $barangayVaccinesGrouped = [];

        // Group vaccines by barangay and calculate total stock
        foreach ($barangayVaccines as $barangay) {
            $barangayName = $barangay['barangay_name'];
            $vaccineStock = $barangay['stock']; // Vaccine stock
            $vaccineName = $barangay['vaccine_name']; // Vaccine name

            // If this barangay doesn't exist in the grouped array, initialize it
            if (!isset($barangayVaccinesGrouped[$barangayName])) {
                $barangayVaccinesGrouped[$barangayName] = [
                    'barangay_name' => $barangayName,
                    'barangay_id' => $barangay['barangay_id'],
                    'total_stock' => 0,
                    'low_stock_vaccines' => []
                ];
            }

            // Add vaccine stock to total stock
            $barangayVaccinesGrouped[$barangayName]['total_stock'] += $vaccineStock;

            // Check if the vaccine is low stock
            if ($vaccineStock < 30) { // Threshold for low stock (can be adjusted)
                // Avoid duplicates in the low_stock_vaccines array
                $isDuplicate = false;
                foreach ($barangayVaccinesGrouped[$barangayName]['low_stock_vaccines'] as $existingVaccine) {
                    if ($existingVaccine['vaccine_name'] === $vaccineName) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $barangayVaccinesGrouped[$barangayName]['low_stock_vaccines'][] = [
                        'vaccine_name' => $vaccineName,
                        'stock' => $vaccineStock
                    ];
                }
            }
        }

            ?>

            <?php if (count($barangayVaccinesGrouped) > 0): ?>
                <?php foreach ($barangayVaccinesGrouped as $barangay): ?>
                    <tr class="inventory-item" onclick="location.href='update-inventory.php?barangay_id=<?php echo htmlspecialchars($barangay['barangay_id']); ?>'">
                        <td>
                            <span class="barangay-name"><?php echo htmlspecialchars($barangay['barangay_name']); ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $barangay['total_stock'] > 50 ? 'approved' : 'pending'; ?>">
                                <?php echo htmlspecialchars($barangay['total_stock']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (count($barangay['low_stock_vaccines']) > 0): ?>
                                <ul>
                                    <?php foreach ($barangay['low_stock_vaccines'] as $lowStockVaccine): ?>
                                        <li>
                                            <?php echo htmlspecialchars($lowStockVaccine['vaccine_name']); ?> 
                                            <span class="highlight-stock"><?php echo htmlspecialchars($lowStockVaccine['stock']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="status-badge status-approved">No Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="view-button">View Details</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No barangays have been registered yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<style> 
    /* Import Poppins font from Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

    /* Main content container */
    .main-content {
        max-width: 1000px;
        margin: 40px auto;
        font-family: 'Poppins', sans-serif;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .inventory-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: white; /* Set the table background color to white */
    }

    .inventory-table th, .inventory-table td {
        text-align: left;
        border-bottom: 1px solid #ddd;
        padding: 10px;
    }

    /* Background color for header cells (keeps original light blue color) */
    .inventory-table th {
        background-color: #78B3CE; /* Light blue background for headers */
        color: white; /* White text color */
        font-weight: bold;
    }

    /* Make the table rows' background color white */
    .inventory-table td {
        background-color: white;
    }

    .barangay-name {
        font-weight: bold;
        color: #1679AB;
    }

    .status-badge {
        display: inline-block;
        text-align: center;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 12px;
    }

    .status-approved {
        background-color: #d4edda;
        color: #155724;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .highlight-stock {
        color: #ff4d4d; /* Bright red for low stock */
        font-weight: bold;
    }

    .view-button {
        display: block;
        width: 100%;
        text-align: center;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 10px;
        font-size: 14px;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }

    .view-button:hover {
        background-color: #0056b3;
    }

    .inventory-item:hover {
        background-color: #f1f1f1;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    /* Make the number in the Total Stock column larger */
    .inventory-table td:nth-child(2) {
        text-align: center; /* Centers the content of the 2nd column (Total Stock) */
        font-size: 24px; /* Makes the font size bigger */
        font-weight: bold; /* Optional: Makes the font bold */
    }
</style>








        </main>
    </section>
    <!-- CONTENT -->

    <!-- Script to update current time -->
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
