<?php
// Include your database connection file
include 'db_connection.php';  

// Allow Cross-Origin requests from Android devices (optional but often necessary)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Check if parent_id is sent via POST or GET request (since Android will send it that way)
$parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : (isset($_GET['parent_id']) ? $_GET['parent_id'] : null);

// Check if parent_id is available
if (!$parent_id) {
    echo json_encode(["error" => "Error: Parent ID is missing."]);
    exit;
}

// Initialize an array to hold all the results
$response = array();

// Fetch combined child details
$child_details_sql = "
    SELECT 
        c.id AS child_id,
        c.first_name,
        c.last_name,
        c.date_of_birth,
        c.gender,
        c.parent_id,
        p.parent_name,
        p.address,
        p.phone_number,
        c.registration_date,
        c.age_of_registration,
        vr.vaccine_name,
        vr.vaccination_date,
        vr.administered_by,
        vr.age_in_months
    FROM 
        children c
    JOIN 
        parents p ON c.parent_id = p.id
    LEFT JOIN 
        vaccination_records vr ON c.id = vr.child_id
    WHERE 
        p.id = ?";

$stmt = $conn->prepare($child_details_sql);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$child_details_result = $stmt->get_result();

$child_details = array();
while ($row = $child_details_result->fetch_assoc()) {
    $child_details[] = $row;  // Store each child's details in the array
}

$response['child_details'] = $child_details;  // Store child details in the response

// Send the JSON response back to the client
echo json_encode($response);

// Close the database connection
$conn->close();
?>
