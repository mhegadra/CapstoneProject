<?php
require_once('tcpdf/tcpdf.php');

// Database connection settings
$host = 'localhost';
$db = 'immuni_track';
$user = 'root';
$pass = '12345';

try {
    // Database connection
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the total number of boys and girls in the Barangay
    $query = "SELECT 
                COUNT(CASE WHEN gender = 'male' THEN 1 END) AS total_boys,
                COUNT(CASE WHEN gender = 'female' THEN 1 END) AS total_girls
              FROM children";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalBoys = $result['total_boys'];   // Get the total count of boys
    $totalGirls = $result['total_girls']; // Get the total count of girls

    // Initialize PDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ImmuniTrack');
    $pdf->SetTitle('Children Data Report');
    $pdf->SetHeaderData('', 0, 'Barangay Children Data Report', "Generated on: " . date('d M Y'));

    // Set Times New Roman font for the header and footer
    $pdf->setHeaderFont(['times', '', 10]);  // Times New Roman for header
    $pdf->setFooterFont(['times', '', 8]);   // Times New Roman for footer
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('times', 'B', 16);  // Times New Roman Bold for the title
    $pdf->Cell(0, 10, 'Children Data Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Set font for the label and value
    $pdf->SetFont('times', '', 12);  // Times New Roman Regular for the label (no bold)

    // Label and value for total boys
    $labelBoys = 'Total Number of Boys in ' . date('Y');
    $valueBoys = $totalBoys;
    $labelWidthBoys = $pdf->GetStringWidth($labelBoys) + 6;  // Add padding
    $valueWidthBoys = $pdf->GetStringWidth($valueBoys) + 6;  // Add padding

    // Label and value for total girls
    $labelGirls = 'Total Number of Girls in ' . date('Y');
    $valueGirls = $totalGirls;
    $labelWidthGirls = $pdf->GetStringWidth($labelGirls) + 6;  // Add padding
    $valueWidthGirls = $pdf->GetStringWidth($valueGirls) + 6;  // Add padding

    // Set starting Y position
    $yPos = 40;

    // Label and value for total boys (positioned at the left)
    $pdf->SetXY(15, $yPos);  // Position at 15mm from left and 40mm from top
    $pdf->Cell($labelWidthBoys, 10, $labelBoys, 0, 0, 'L');  // Label (no bold)
    $pdf->SetFont('times', '', 12);  // Times New Roman Regular for the value
    $pdf->SetXY(15 + $labelWidthBoys, $yPos);  // Position for the total count
    // Set blue color for boys box
    $pdf->SetFillColor(173, 216, 230);  // Light blue color
    $pdf->Rect(15 + $labelWidthBoys, $yPos, $valueWidthBoys, 10, 'DF'); // Blue box around the value
    $pdf->Cell($valueWidthBoys, 10, $totalBoys, 0, 0, 'L', 1);  // Total number for boys with blue background

    // Now position the total girls next to boys with a margin space in between
    $pdf->SetXY(15 + $labelWidthBoys + $valueWidthBoys + 10, $yPos);  // Position for total girls (10mm space)
    $pdf->Cell($labelWidthGirls, 10, $labelGirls, 0, 0, 'L');  // Label for girls
    $pdf->SetFont('times', '', 12);  // Times New Roman Regular for the value
    $pdf->SetXY(15 + $labelWidthBoys + $valueWidthBoys + 10 + $labelWidthGirls, $yPos);  // Position for the total count
    // Set pink color for girls box
    $pdf->SetFillColor(255, 182, 193);  // Light pink color
    $pdf->Rect(15 + $labelWidthBoys + $valueWidthBoys + 10 + $labelWidthGirls, $yPos, $valueWidthGirls, 10, 'DF'); // Pink box around the value
    $pdf->Cell($valueWidthGirls, 10, $totalGirls, 0, 1, 'L', 1);  // Total number for girls with pink background

    // Add footer
    $pdf->SetY(-15); // Position at 15mm from bottom
    $pdf->SetFont('times', 'I', 8);  // Times New Roman Italic for footer
    $pdf->Cell(0, 10, 'End of Report', 0, 0, 'C');

    // Output PDF
    $pdf->Output('Children_Data_Report.pdf', 'D');
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
?>
