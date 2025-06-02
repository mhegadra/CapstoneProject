<?php
// Include database connection (adjust path if necessary)
include('db_connection.php');

// Include TCPDF library
require_once 'tcpdf/tcpdf.php'; // Adjust the path if necessary

// Create a new instance of TCPDF
$pdf = new TCPDF();

// Set document properties
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ImmuniTrack');
$pdf->SetTitle('Children Administered Vaccines');
$pdf->SetSubject('List of Children Administered Vaccines');
$pdf->SetKeywords('TCPDF, PDF, children, vaccines, administered');

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add a page
$pdf->AddPage();

// Set header
$pdf->Cell(0, 10, 'List of Children Administered ', 0, 1, 'C');
$pdf->Ln(5);

// Query to fetch children data
$query = "SELECT c.first_name, c.last_name, c.date_of_birth, c.gender FROM children c";
$result = mysqli_query($conn, $query);

// Check if records are found
if (mysqli_num_rows($result) > 0) {
    // Add table header without grid
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(80, 10, 'Name', 0, 0, 'C'); // Combined Name column (no border)
    $pdf->Cell(40, 10, 'Date of Birth', 0, 0, 'C'); // Centered header (no border)
    $pdf->Cell(30, 10, 'Gender', 0, 1, 'C'); // Centered header (no border)

    // Add table rows without grid
    $pdf->SetFont('helvetica', '', 12);
    while ($row = mysqli_fetch_assoc($result)) {
        $fullName = $row['first_name'] . ' ' . $row['last_name']; // Combine first and last name
        $pdf->Cell(80, 10, $fullName, 0, 0, 'C'); // Centered content for Name (no border)
        $pdf->Cell(40, 10, $row['date_of_birth'], 0, 0, 'C'); // Centered content for Date of Birth (no border)
        $pdf->Cell(30, 10, $row['gender'], 0, 1, 'C'); // Centered content for Gender (no border)
    }
} else {
    // If no records found, add message
    $pdf->SetFont('helvetica', 'I', 12);
    $pdf->Cell(0, 10, 'No records found', 0, 1, 'C');
}

// Output the PDF as a download
$pdf->Output('children_data_report.pdf', 'D');
?>
