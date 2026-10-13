<?php

session_start();


require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get ID from URL parameter instead of session
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_SESSION['last_agreement_id'])) {
    // Fallback to session for newly created agreements
    $id = (int)$_SESSION['last_agreement_id'];
} else {
    die("No agreement ID provided");
}

// Clear the session variable after use (optional)

$stmt = $conn->prepare("SELECT * FROM agreements WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Agreement data not found for ID: " . $id);
}

$templatePath = __DIR__ . "/templates/agreement_template.html";
if (!file_exists($templatePath)) {
    die("Template not found at: " . $templatePath);
}

$template = file_get_contents($templatePath);
$template = mb_convert_encoding($template, 'UTF-8', 'UTF-8');

$end_date = '';
if (!empty($data['agreement_date']) && !empty($data['period_months'])) {
    try {
        $dt = new DateTime($data['agreement_date']);
        $dt->modify('+' . (int)$data['period_months'] . ' months');
        $end_date = $dt->format('Y-m-d');
    } catch (Exception $e) {
        $end_date = 'Invalid date';
    }
}

$replace = [
    '{{NAME}}' => $data['name'] ?? '',
    '{{ADDRESS}}' => nl2br($data['address'] ?? ''),
    '{{NIC}}' => $data['nic'] ?? '',
    '{{AGREEMENT_DATE}}' => $data['agreement_date'] ?? '',
    '{{END_DATE}}' => $end_date,
    '{{AMOUNT_IN_NUMBER}}' => isset($data['amount_number']) ? number_format($data['amount_number'], 2) : '',
    '{{AMOUNT_IN_WORDS}}' => $data['amount_words'] ?? '',
    '{{INTEREST_RATE}}' => rtrim(rtrim($data['interest_rate'], '0'), '.'),
    '{{PRODUCT_SINHALA}}' => $data['product_sinhala'] ?? '',
    '{{VEHICLE_NUMBER}}' => $data['vehicle_number'] ?? '',
    '{{CHASSIS_NUMBER}}' => $data['chassis_number'] ?? '',
    '{{ENGINE_NUMBER}}' => $data['engine_number'] ?? '',
    '{{MAKE}}' => $data['make'] ?? '',
    '{{MODEL}}' => $data['model'] ?? '',
    '{{VEHICLE_CLASS_SINHALA}}' => $data['vehicle_class_sinhala'] ?? '',
    '{{PERIOD}}' => $data['period_months'] ?? '',
    '{{PAYMENT_DAY}}' => $data['payment_day'] ?? '',
];

$output = str_replace(array_keys($replace), array_values($replace), $template);
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<title>Agreement Preview</title>

<style>
body{
    font-family:"Iskoola Pota","Noto Serif Sinhala",serif;
    background:#f4f6f9;
    padding:30px;
}
.paper{
    background:#fff;
    padding:40px;
    max-width:900px;
    margin:auto;
    box-shadow:0 0 15px rgba(0,0,0,.15);
}
.paper[contenteditable="true"]{
    outline:2px dashed #2563eb;
}
.print-btn{
    text-align:right;
    margin-bottom:20px;
}
.action-btn{
    padding:10px 18px;
    font-size:15px;
    background:#2563eb;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
    text-decoration:none;
    margin-left:6px;
}
.action-btn.gray{
    background:#6b7280;
}
.action-btn:hover{opacity:.9}
@media print{
    .print-btn{display:none;}
    body{background:#fff;padding:0;}
}
/* ===== PRINT BORDER REMOVE FIX ===== */
@media print {

    body {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: none !important;
    }

    .WordSection1 {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: none !important;
    }

    * {
        box-shadow: none !important;
    }

    @page {
        size: A4;
        margin: 0.5in;
        border: none;
    }
}
</style>
</head>

<body>

<div class="print-btn">
    <a href="index.php" class="action-btn gray">Home</a>
    <button class="action-btn gray" onclick="enableEdit()">✏️ Edit</button>
    <button class="action-btn gray" onclick="disableEdit()">🔒 Lock</button>
    <a href="logout.php" class="action-btn gray">Logout</a>
    <button onclick="window.print()" class="action-btn">🖨️ Print</button>
</div>

<div class="paper" id="paper">
    <?= $output ?>
</div>

<script>
function enableEdit(){
    document.getElementById("paper").setAttribute("contenteditable","true");
}
function disableEdit(){
    document.getElementById("paper").setAttribute("contenteditable","false");
}
</script>

</body>
</html>