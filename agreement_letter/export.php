<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Get export type
$export_type = isset($_GET['type']) ? strtolower($_GET['type']) : 'pdf';

// Get filters
$filters = [];
$where_clause = '';
$params = [];
$types = '';

if (isset($_GET['status'])) {
    $today = date('Y-m-d');
    switch ($_GET['status']) {
        case 'active':
            $where_clause = " WHERE end_date >= ? ";
            $params[] = $today;
            $types = 's';
            break;
        case 'expired':
            $where_clause = " WHERE end_date < ? ";
            $params[] = $today;
            $types = 's';
            break;
        case 'upcoming':
            $thirty_days_later = date('Y-m-d', strtotime('+30 days'));
            $where_clause = " WHERE end_date BETWEEN ? AND ? ";
            $params[] = $today;
            $params[] = $thirty_days_later;
            $types = 'ss';
            break;
    }
}

// Get all agreements based on filters
$sql = "SELECT * FROM agreements $where_clause ORDER BY agreement_date DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Prepare data for export
$agreements = [];
while ($row = $result->fetch_assoc()) {
    // Determine status
    $today = new DateTime();
    $end_date = new DateTime($row['end_date']);
    $status = ($end_date < $today) ? 'කල් ඉකුත් වූ' : 'ක්‍රියාකාරී';
    
    $agreements[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'nic' => $row['nic'],
        'address' => $row['address'],
        'agreement_date' => $row['agreement_date'],
        'end_date' => $row['end_date'],
        'payment_day' => $row['payment_day'],
        'period_months' => $row['period_months'],
        'amount_number' => $row['amount_number'],
        'amount_words' => $row['amount_words'],
        'interest_rate' => $row['interest_rate'],
        'product_sinhala' => $row['product_sinhala'],
        'vehicle_number' => $row['vehicle_number'],
        'chassis_number' => $row['chassis_number'],
        'engine_number' => $row['engine_number'],
        'make' => $row['make'],
        'model' => $row['model'],
        'vehicle_class_sinhala' => $row['vehicle_class_sinhala'],
        'status' => $status,
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

$stmt->close();

// Set export filename
$filename = 'agreements_' . date('Y-m-d_H-i-s');

// Export based on type
switch ($export_type) {
    case 'excel':
        exportExcel($agreements, $filename);
        break;
    case 'csv':
        exportCSV($agreements, $filename);
        break;
    case 'pdf':
    default:
        exportPDF($agreements, $filename);
        break;
}

// Close database connection
$conn->close();

// Function to export to Excel
function exportExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>ගිවිසුම් වාර්තාව</h2>';
    echo '<p>උපදේශනය කළ දිනය: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p>මුළු ගිවිසුම්: ' . count($data) . '</p>';
    
    echo '<table>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>නම</th>';
    echo '<th>ජා.හැ.අ.</th>';
    echo '<th>වාහන අංකය</th>';
    echo '<th>මුදල (රු.)</th>';
    echo '<th>ආරම්භක දිනය</th>';
    echo '<th>අවසන් දිනය</th>';
    echo '<th>තත්වය</th>';
    echo '<th>පොලී අනුපාතය (%)</th>';
    echo '<th>දේපල වර්ගය</th>';
    echo '</tr>';
    
    $counter = 1;
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . $counter++ . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nic']) . '</td>';
        echo '<td>' . htmlspecialchars($row['vehicle_number'] ?: '-') . '</td>';
        echo '<td>' . number_format($row['amount_number'], 2) . '</td>';
        echo '<td>' . $row['agreement_date'] . '</td>';
        echo '<td>' . $row['end_date'] . '</td>';
        echo '<td>' . $row['status'] . '</td>';
        echo '<td>' . $row['interest_rate'] . '</td>';
        echo '<td>' . htmlspecialchars($row['product_sinhala'] ?: '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Add summary
    echo '<br><br>';
    echo '<h3>සාරාංශය</h3>';
    echo '<table>';
    echo '<tr><td>මුළු ගිවිසුම්:</td><td>' . count($data) . '</td></tr>';
    
    $total_amount = array_sum(array_column($data, 'amount_number'));
    echo '<tr><td>මුළු මුදල (රු.):</td><td>' . number_format($total_amount, 2) . '</td></tr>';
    
    $active_count = count(array_filter($data, function($item) {
        return $item['status'] === 'ක්‍රියාකාරී';
    }));
    echo '<tr><td>ක්‍රියාකාරී ගිවිසුම්:</td><td>' . $active_count . '</td></tr>';
    
    $expired_count = count(array_filter($data, function($item) {
        return $item['status'] === 'කල් ඉකුත් වූ';
    }));
    echo '<tr><td>කල් ඉකුත් වූ ගිවිසුම්:</td><td>' . $expired_count . '</td></tr>';
    echo '</table>';
    
    echo '</body>';
    echo '</html>';
    exit;
}

// Function to export to CSV
function exportCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Headers
    $headers = [
        '#',
        'නම',
        'ජාතික හැඳුනුම්පත් අංකය',
        'ලිපිනය',
        'වාහන අංකය',
        'මුදල (රු.)',
        'මුදල (අකුරෙන්)',
        'පොලී අනුපාතය (%)',
        'ආරම්භක දිනය',
        'අවසන් දිනය',
        'ගෙවීම් දිනය',
        'කාලය (මාස)',
        'දේපල වර්ගය',
        'චැසි අංකය',
        'එන්ජින් අංකය',
        'Make',
        'Model',
        'වාහන වර්ගය',
        'තත්වය',
        'සාදන ලද දිනය',
        'යාවත්කාලීන කළ දිනය'
    ];
    
    fputcsv($output, $headers);
    
    // Data
    $counter = 1;
    foreach ($data as $row) {
        $csv_row = [
            $counter++,
            $row['name'],
            $row['nic'],
            $row['address'],
            $row['vehicle_number'] ?: '',
            $row['amount_number'],
            $row['amount_words'],
            $row['interest_rate'],
            $row['agreement_date'],
            $row['end_date'],
            $row['payment_day'],
            $row['period_months'],
            $row['product_sinhala'] ?: '',
            $row['chassis_number'] ?: '',
            $row['engine_number'] ?: '',
            $row['make'] ?: '',
            $row['model'] ?: '',
            $row['vehicle_class_sinhala'] ?: '',
            $row['status'],
            $row['created_at'],
            $row['updated_at'] ?: ''
        ];
        
        fputcsv($output, $csv_row);
    }
    
    fclose($output);
    exit;
}

// Function to export to PDF (using FPDF library)
function exportPDF($data, $filename) {
    // Check if FPDF is available
    if (!class_exists('FPDF')) {
        // If not available, redirect to download page with message
        $_SESSION['export_error'] = "PDF උත්පාදනය සඳහා FPDF පුස්තකාලය අවශ්‍යයි.";
        header("Location: view_agreements.php");
        exit;
    }
    
    // Include FPDF library
    require('fpdf/fpdf.php');
    
    // Create custom class for Sinhala support (basic)
    class PDF extends FPDF {
        function Header() {
            // Logo
            $this->Image('logo.png', 10, 6, 30);
            
            // Title
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'ගිවිසුම් වාර්තාව', 0, 1, 'C');
            
            // Date
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'උපදේශනය කළ දිනය: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
            
            // Line break
            $this->Ln(10);
        }
        
        function Footer() {
            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            
            // Page number
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'පිටුව ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
        
        function ChapterTitle($num, $label) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetFillColor(200, 220, 255);
            $this->Cell(0, 6, "$num $label", 0, 1, 'L', true);
            $this->Ln(4);
        }
        
        function ChapterBody($file) {
            $txt = file_get_contents($file);
            $this->SetFont('Arial', '', 12);
            $this->MultiCell(0, 5, $txt);
            $this->Ln();
        }
    }
    
    // Create PDF
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('Arial', '', 10);
    
    // Add summary section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'සාරාංශය', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    
    $total_amount = array_sum(array_column($data, 'amount_number'));
    $active_count = count(array_filter($data, function($item) {
        return $item['status'] === 'ක්‍රියාකාරී';
    }));
    $expired_count = count(array_filter($data, function($item) {
        return $item['status'] === 'කල් ඉකුත් වූ';
    }));
    
    $pdf->Cell(0, 6, 'මුළු ගිවිසුම්: ' . count($data), 0, 1);
    $pdf->Cell(0, 6, 'මුළු මුදල (රු.): ' . number_format($total_amount, 2), 0, 1);
    $pdf->Cell(0, 6, 'ක්‍රියාකාරී ගිවිසුම්: ' . $active_count, 0, 1);
    $pdf->Cell(0, 6, 'කල් ඉකුත් වූ ගිවිසුම්: ' . $expired_count, 0, 1);
    
    $pdf->Ln(10);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(200, 200, 200);
    
    // Adjust column widths
    $w = array(10, 40, 25, 25, 25, 20, 20, 15);
    
    // Header
    $pdf->Cell($w[0], 7, '#', 1, 0, 'C', true);
    $pdf->Cell($w[1], 7, 'නම', 1, 0, 'L', true);
    $pdf->Cell($w[2], 7, 'ජා.හැ.අ.', 1, 0, 'L', true);
    $pdf->Cell($w[3], 7, 'වාහන අංකය', 1, 0, 'L', true);
    $pdf->Cell($w[4], 7, 'මුදල', 1, 0, 'R', true);
    $pdf->Cell($w[5], 7, 'ආරම්භය', 1, 0, 'C', true);
    $pdf->Cell($w[6], 7, 'අවසානය', 1, 0, 'C', true);
    $pdf->Cell($w[7], 7, 'තත්වය', 1, 1, 'C', true);
    
    // Table data
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    $counter = 1;
    $fill = false;
    
    foreach ($data as $row) {
        // Check if we need a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            // Reprint header
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell($w[0], 7, '#', 1, 0, 'C', true);
            $pdf->Cell($w[1], 7, 'නම', 1, 0, 'L', true);
            $pdf->Cell($w[2], 7, 'ජා.හැ.අ.', 1, 0, 'L', true);
            $pdf->Cell($w[3], 7, 'වාහන අංකය', 1, 0, 'L', true);
            $pdf->Cell($w[4], 7, 'මුදල', 1, 0, 'R', true);
            $pdf->Cell($w[5], 7, 'ආරම්භය', 1, 0, 'C', true);
            $pdf->Cell($w[6], 7, 'අවසානය', 1, 0, 'C', true);
            $pdf->Cell($w[7], 7, 'තත්වය', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $fill = !$fill;
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);
        
        $pdf->Cell($w[0], 6, $counter++, 1, 0, 'C', true);
        $pdf->Cell($w[1], 6, substr($row['name'], 0, 20), 1, 0, 'L', true);
        $pdf->Cell($w[2], 6, $row['nic'], 1, 0, 'L', true);
        $pdf->Cell($w[3], 6, substr($row['vehicle_number'] ?: '-', 0, 10), 1, 0, 'L', true);
        $pdf->Cell($w[4], 6, number_format($row['amount_number'], 2), 1, 0, 'R', true);
        $pdf->Cell($w[5], 6, substr($row['agreement_date'], 0, 10), 1, 0, 'C', true);
        $pdf->Cell($w[6], 6, substr($row['end_date'], 0, 10), 1, 0, 'C', true);
        
        // Status with color coding
        if ($row['status'] === 'ක්‍රියාකාරී') {
            $pdf->SetTextColor(0, 128, 0);
        } else {
            $pdf->SetTextColor(255, 0, 0);
        }
        $pdf->Cell($w[7], 6, substr($row['status'], 0, 8), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // Output PDF
    $pdf->Output('D', $filename . '.pdf');
    exit;
}
?>