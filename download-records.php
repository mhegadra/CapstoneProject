<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login-user.php');
    exit();
}

// Include TCPDF library
require_once 'tcpdf/tcpdf.php'; // Adjust the path if necessary

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

// Get the child ID from the URL
$child_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($child_id <= 0) {
    die('Invalid child ID');
}

// Fetch the child's details from the database
$childQuery = "
    SELECT 
        c.first_name, 
        c.last_name, 
        c.date_of_birth, 
        c.gender, 
        c.birth_weight,          -- Added birth weight
        c.birth_head_circumference, -- Added birth head circumference
        c.birth_length,          -- Added birth length
        p.parent_name, 
        p.address, 
        p.phone_number
    FROM 
        children c
    JOIN 
        parents p ON c.parent_id = p.id
    WHERE 
        c.id = ?
";
$childStmt = $pdo->prepare($childQuery);
$childStmt->execute([$child_id]);
$childDetails = $childStmt->fetch(PDO::FETCH_ASSOC);

if (!$childDetails) {
    die('No records found for this child.');
}

// Fetch vaccination records
$recordQuery = "
    SELECT 
        v.vaccine_name, 
        v.vaccination_date, 
        v.administered_by, 
        v.age_in_months
    FROM 
        vaccination_records v
    WHERE 
        v.child_id = ?
";
$recordStmt = $pdo->prepare($recordQuery);
$recordStmt->execute([$child_id]);
$records = $recordStmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Vaccination Records');
$pdf->SetSubject('Vaccination Records');
$pdf->SetKeywords('TCPDF, PDF, vaccination, records');

// Remove header content
$pdf->SetHeaderData('', 0, '', ''); // No logo, no text
$pdf->setHeaderFont(Array('', '', 0)); // Set header font size to 0 (optional)
$pdf->setHeaderMargin(0); // Remove header margin

// Set font for child information section
$pdf->SetFont('helvetica', '', 12);

// Define column widths for two columns
$leftColumnX = 10;     // X position for the left column
$rightColumnX = 110;   // X position for the right column
$columnWidth = 90;     // Width for each column

// Left Column - Child's Basic Information
$pdf->SetXY($leftColumnX, $pdf->GetY()); // Set position to left column
$pdf->Cell($columnWidth, 10, 'Name: ' . $childDetails['first_name'] . ' ' . $childDetails['last_name'], 0, 1, 'L');

$pdf->SetX($leftColumnX); // Reset X position to left column
$pdf->Cell($columnWidth, 10, 'Date of Birth: ' . $childDetails['date_of_birth'], 0, 1, 'L');

$pdf->SetX($leftColumnX);
$pdf->Cell($columnWidth, 10, 'Gender: ' . $childDetails['gender'], 0, 1, 'L');

$pdf->SetX($leftColumnX);
$pdf->Cell($columnWidth, 10, 'Parent Name: ' . $childDetails['parent_name'], 0, 1, 'L');

// Right Column - Additional Details
$pdf->SetXY($rightColumnX, $pdf->GetY() - 40); // Move Y back to top of this section for the right column
$pdf->Cell($columnWidth, 10, 'Address: ' . $childDetails['address'], 0, 1, 'L');

$pdf->SetX($rightColumnX);
$pdf->Cell($columnWidth, 10, 'Phone Number: ' . $childDetails['phone_number'], 0, 1, 'L');

$pdf->SetX($rightColumnX);
$pdf->Cell($columnWidth, 10, 'Birth Weight (kg): ' . $childDetails['birth_weight'], 0, 1, 'L');

$pdf->SetX($rightColumnX);
$pdf->Cell($columnWidth, 10, 'Birth Head Circumference (cm): ' . $childDetails['birth_head_circumference'], 0, 1, 'L');

$pdf->SetX($rightColumnX);
$pdf->Cell($columnWidth, 10, 'Birth Length (cm): ' . $childDetails['birth_length'], 0, 1, 'L');

$pdf->Ln(10); // Add spacing after the columns


// Add a title for vaccination records
$pdf->SetFont('helvetica', 'B', 14); // Bold font for the title
$pdf->Cell(0, 10, 'Vaccination Records', 0, 1, 'C');
$pdf->Ln(5);

// Set font for table
$pdf->SetFont('helvetica', '', 10); // Normal font

// Define column widths
$colWidths = [
    'vaccine_name' => 60, // Reduced width
    'vaccination_date' => 40, // Reduced width
    'administered_by' => 40, // Reduced width
    'age_in_months' => 50 // Reduced width
];

// Add table header with borders and centered text
$pdf->Cell($colWidths['vaccine_name'], 10, 'Vaccine Name', 1, 0, 'C');
$pdf->Cell($colWidths['vaccination_date'], 10, 'Vaccination Date', 1, 0, 'C');
$pdf->Cell($colWidths['administered_by'], 10, 'Administered By', 1, 0, 'C');
$pdf->Cell($colWidths['age_in_months'], 10, 'Age in Months', 1, 1, 'C');

// Add table data using MultiCell for wrapping text
foreach ($records as $record) {
    // Vaccine Name
    $pdf->MultiCell($colWidths['vaccine_name'], 10, $record['vaccine_name'], 1, 'C', 0, 0);
    // Vaccination Date
    $pdf->MultiCell($colWidths['vaccination_date'], 10, $record['vaccination_date'], 1, 'C', 0, 0);
    // Administered By
    $pdf->MultiCell($colWidths['administered_by'], 10, $record['administered_by'], 1, 'C', 0, 0);
    // Age in Months
    $pdf->MultiCell($colWidths['age_in_months'], 10, $record['age_in_months'], 1, 'C', 0, 1);
}

// Use the child's last name for the file name
$filename = $childDetails['last_name'] . '.pdf';

// Close and output PDF document
$pdf->Output($filename, 'D');

exit();
?>
