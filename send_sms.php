<?php
// Database connection details
$host = 'localhost';  // Your database host
$db = 'immuni_track'; // Your database name
$user = 'root';       // Your database username
$pass = '12345';      // Your database password

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $db);

// Check for a successful connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch phone numbers from the 'parents' table
$sql = "SELECT phone_number FROM parents";
$result = $conn->query($sql);

// Infobip API Key and URL
$api_key = 'd22ebfa85a991b5facafe4166570de5a-5951565d-b0cc-4f36-a488-e16d5ca72eb0';
$url = 'https://e51xeq.api.infobip.com/sms/2/text/advanced';

// Get today's date and day of the week
$today = new DateTime();
$dayOfWeek = $today->format('l'); // Get the day of the week (e.g., "Tuesday", "Wednesday")

// Determine if it's the day before Wednesday (Tuesday) or Wednesday itself
if ($dayOfWeek === 'Tuesday' || $dayOfWeek === 'Wednesday') {
    // Set the vaccination day (Wednesday)
    $vaccinationDate = new DateTime('next Wednesday');
    $formattedDate = $vaccinationDate->format('Y-m-d');

    if (is_object($result) && $result->num_rows > 0) {
        // Loop through each phone number
        while ($row = $result->fetch_assoc()) {
            $phone_number = $row['phone_number'];

            // Ensure phone number is valid (e.g., not empty, correct format)
            if (empty($phone_number)) {
                echo "Phone number is missing, skipping.\n";
                continue;
            }

            // Prepare the SMS content
            $message = [
                "messages" => [
                    [
                        "from" => "ImmuniTrack", // Alphanumeric sender name
                        "destinations" => [
                            ["to" => $phone_number]
                        ],
                    "text" => "Reminder: Your child's vaccination is scheduled for $formattedDate. Please make sure to visit on this day to keep their immunization on track. Let's protect their health together!"
                    ]
                ]
            ];

            // Set up the request options
            $options = [
                'http' => [
                    'header' => "Authorization: App $api_key\r\n" .
                                "Content-Type: application/json\r\n",
                    'method' => 'POST',
                    'content' => json_encode($message),
                    'ignore_errors' => true  // Capture errors in the response
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ]
            ];

            // Send the SMS using file_get_contents
            $context = stream_context_create($options);
            $result_sms = file_get_contents($url, false, $context);

            // Handle the result
            if ($result_sms === FALSE) {
                echo "Error sending SMS to $phone_number: " . print_r($http_response_header, true) . "\n";
            } else {
                echo "SMS sent to $phone_number: $result_sms\n";
            }
        }
    } else {
        echo "No phone numbers found or query failed.";
    }
} else {
    echo "Today is not a reminder day (Tuesday or Wednesday). No SMS sent.";
}

// Close the database connection
$conn->close();
?>
