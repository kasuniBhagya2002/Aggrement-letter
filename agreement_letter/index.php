<?php
ob_start();

require_once 'header.php';


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Initialize variables and error messages
$errors = [];
$formData = [];
$success = false;

// Define field labels for error messages
$fieldLabels = [
    'name' => 'නම',
    'nic' => 'ජාතික හැඳුනුම්පත් අංකය',
    'address' => 'ලිපිනය',
    'agreement_date' => 'ගිවිසුම් දිනය',
    'payment_day' => 'ගෙවීම් දිනය',
    'period_months' => 'කාලය (මාස)',
    'amount_number' => 'මුදල (ඉලක්කමෙන්)',
    'amount_words' => 'මුදල (අකුරෙන්)',
    'interest_rate' => 'පොලී අනුපාතය (%)',
    'product_sinhala' => 'දේපල වර්ගය',
    'vehicle_number' => 'වාහන අංකය'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate all inputs
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $agreement_date = $_POST['agreement_date'] ?? '';
    $payment_day = (int)($_POST['payment_day'] ?? 0);
    $period_months = (int)($_POST['period_months'] ?? 0);
    $amount_number = (float)($_POST['amount_number'] ?? 0);
    $amount_words = trim($_POST['amount_words'] ?? '');
    $interest_rate = (float)($_POST['interest_rate'] ?? 0);
    $product_sinhala = trim($_POST['product_sinhala'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $chassis_number = trim($_POST['chassis_number'] ?? '');
    $engine_number = trim($_POST['engine_number'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $vehicle_class_sinhala = trim($_POST['vehicle_class_sinhala'] ?? '');

    // Store form data for repopulation
    $formData = $_POST;

    // Validation rules
    if (empty($name)) $errors[] = "නම අවශ්‍යයි";
    if (empty($nic)) $errors[] = "ජාතික හැඳුනුම්පත් අංකය අවශ්‍යයි";
    if (empty($address)) $errors[] = "ලිපිනය අවශ්‍යයි";
    if (empty($agreement_date)) $errors[] = "ගිවිසුම් දිනය අවශ්‍යයි";
    if ($payment_day < 1 || $payment_day > 31) $errors[] = "ගෙවීම් දිනය 1 සිට 31 දක්වා විය යුතුය";
    if ($period_months < 1) $errors[] = "කාලය අවම වශයෙන් 1 මාසයක් විය යුතුය";
    if ($amount_number <= 0) $errors[] = "මුදල 0 ට වැඩි විය යුතුය";
    if (empty($amount_words)) $errors[] = "මුදල (අකුරෙන්) අවශ්‍යයි";
    if ($interest_rate < 0) $errors[] = "පොලී අනුපාතය සෘණ අගයක් විය නොහැක";
    
    // NIC validation (Sri Lankan format)
    if (!empty($nic) && !preg_match('/^[0-9]{9}[vVxX]?$|^[0-9]{12}$/', $nic)) {
        $errors[] = "ජාතික හැඳුනුම්පත් අංකය වලංගු නොවේ";
    }
    
    // Date validation
    // Date validation - allow today and past dates only
if (!empty($agreement_date)) {
    $agreementDateTime = DateTime::createFromFormat('Y-m-d', $agreement_date);
    $today = new DateTime();
    $today->modify('+1 day'); // Add one day to allow today's date
    $today->setTime(0, 0, 0);
    
    if (!$agreementDateTime) {
        $errors[] = "ගිවිසුම් දිනය වලංගු නොවේ";
    } elseif ($agreementDateTime >= $today) {
        $errors[] = "ගිවිසුම් දිනය අනාගත දිනයක් විය නොහැක";
    }
}

    // If no errors, process the form
    if (empty($errors)) {
        // Calculate end date
        $startDate = new DateTime($agreement_date);
        $startDate->modify("+{$period_months} months");
        $end_date = $startDate->format('Y-m-d');

        // Prepare and execute SQL statement
        $stmt = $conn->prepare("
            INSERT INTO agreements
            (name, address, nic, agreement_date, end_date, payment_day, period_months,
             amount_number, amount_words, interest_rate,
             product_sinhala, vehicle_number, chassis_number,
             engine_number, make, model, vehicle_class_sinhala, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
        ");

        if (!$stmt) {
            $errors[] = "දත්ත සුරැකීමේ දෝෂයක්: " . $conn->error;
        } else {
            $stmt->bind_param(
                "sssssiidsssssssss",
                $name,
                $address,
                $nic,
                $agreement_date,
                $end_date,
                $payment_day,
                $period_months,
                $amount_number,
                $amount_words,
                $interest_rate,
                $product_sinhala,
                $vehicle_number,
                $chassis_number,
                $engine_number,
                $make,
                $model,
                $vehicle_class_sinhala
            );

            if ($stmt->execute()) {
                $_SESSION['last_agreement_id'] = $stmt->insert_id;
                $_SESSION['form_success'] = "ගිවිසුම සාර්ථකව සුරකින ලදී!";
                
                // Clear form data after successful submission
                $formData = [];
                
                // Redirect to preview
                header("Location: preview.php");
                exit;
            } else {
                $errors[] = "දත්ත සුරැකීමේ දෝෂය: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ගිවිසුම් පත්‍රය – දත්ත ඇතුළත් කිරීම</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Compact Form Styles */
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --bg: #f8fafc;
    --text: #1f2937;
    --border: #d1d5db;
    --error: #dc2626;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Iskoola Pota', sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    line-height: 1.4;
    padding: 10px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Compact Header */
.header {
    background: white;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 4px solid var(--primary);
}

.header h1 {
    font-size: 18px;
    color: var(--primary-dark);
    margin-bottom: 4px;
}

.header p {
    font-size: 12px;
    color: #6b7280;
}

/* Compact Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

/* Section Styling */
.section {
    grid-column: 1 / -1;
    margin: 8px 0 4px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
}

.section-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--primary-dark);
    display: flex;
    align-items: center;
    gap: 6px;
}

.section-title::before {
    content: "•";
    color: var(--primary);
    font-size: 18px;
}

/* Compact Form Groups */
.form-group {
    margin-bottom: 8px;
}

.form-group.compact {
    margin-bottom: 6px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 3px;
    color: #374151;
}

.form-group label.required::after {
    content: " *";
    color: var(--error);
    font-size: 10px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--border);
    border-radius: 5px;
    font-size: 13px;
    font-family: inherit;
    background: white;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.form-group textarea {
    min-height: 60px;
    resize: vertical;
}

/* Special compact textareas */
.form-group textarea.compact {
    min-height: 40px;
    font-size: 12px;
}

/* Helper text */
.helper {
    font-size: 11px;
    color: #6b7280;
    margin-top: 2px;
    display: block;
}

/* Error Messages */
.error-container {
    grid-column: 1 / -1;
    background: #fee;
    border: 1px solid var(--error);
    border-radius: 5px;
    padding: 8px 10px;
    margin-bottom: 10px;
    font-size: 12px;
}

.error-container ul {
    margin-left: 15px;
}

/* Financial Section - Compact */
.financial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-top: 8px;
}

.financial-item {
    background: #f0f9ff;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #dbeafe;
}

.financial-item label {
    font-size: 11px;
    color: #1e40af;
}

.financial-item input {
    font-size: 14px;
    font-weight: 500;
    background: transparent;
    border: none;
    padding: 4px 0;
}

.financial-item input:focus {
    background: white;
}

/* Vehicle Grid - Ultra Compact */
.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
    margin-top: 8px;
}

.vehicle-item {
    margin-bottom: 0;
}

.vehicle-item label {
    font-size: 11px;
    margin-bottom: 2px;
}

.vehicle-item input {
    padding: 5px 6px;
    font-size: 12px;
}

/* Form Actions */
.form-actions {
    grid-column: 1 / -1;
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
}

.btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(to right, var(--primary), var(--primary-dark));
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(to right, var(--primary-dark), #1e3a8a);
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(37, 99, 235, 0.2);
}

.btn-secondary {
    background: #f3f4f6;
    color: var(--text);
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

/* Inline help icons */
.help-icon {
    display: inline-block;
    width: 14px;
    height: 14px;
    background: #6b7280;
    color: white;
    border-radius: 50%;
    text-align: center;
    font-size: 10px;
    line-height: 14px;
    margin-left: 4px;
    cursor: help;
}

/* Quick calculation preview */
.calc-preview {
    font-size: 12px;
    color: #059669;
    background: #d1fae5;
    padding: 4px 6px;
    border-radius: 3px;
    margin-top: 3px;
    display: inline-block;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .vehicle-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .financial-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    body {
        padding: 5px;
        font-size: 13px;
    }
    
    .header {
        padding: 8px 10px;
    }
    
    .header h1 {
        font-size: 16px;
    }
    
    .form-grid {
        padding: 10px;
    }
    
    .vehicle-grid {
        grid-template-columns: 1fr;
    }
}

/* Character counter */
.char-counter {
    text-align: right;
    font-size: 10px;
    color: #6b7280;
    margin-top: 2px;
}

/* Auto-calculate end date */
.auto-calc {
    font-size: 11px;
    color: #7c3aed;
    font-weight: 500;
}

/* Compact tables for data display */
.data-table {
    width: 100%;
    font-size: 12px;
    border-collapse: collapse;
    margin: 5px 0;
}

.data-table td {
    padding: 3px 5px;
    border: 1px solid #e5e7eb;
}

.data-table td:first-child {
    background: #f9fafb;
    width: 35%;
}
</style>
</head>

<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-file-contract"></i> නව ගිවිසුමක් එක් කිරීම</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>දෝෂයක් සිදුවිය!</strong>
                <ul style="margin-top: 8px; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="post" action="" class="form" id="agreementForm">
            <!-- Personal Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-user"></i>
                    <span>පුද්ගලික විස්තර</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="required">නම</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>" 
                               required 
                               placeholder="පුද්ගලයාගේ සම්පූර්ණ නම">
                    </div>
                    
                    <div class="form-group">
                        <label for="nic" class="required">ජාතික හැඳුනුම්පත් අංකය</label>
                        <input type="text" id="nic" name="nic" 
                               value="<?php echo htmlspecialchars($formData['nic'] ?? ''); ?>" 
                               required 
                               placeholder="උදා: 123456789V හෝ 123456789012">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="address" class="required">ලිපිනය</label>
                        <textarea id="address" name="address" rows="3" required 
                                  placeholder="සම්පූර්ණ ලිපිනය"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Agreement Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-file-alt"></i>
                    <span>ගිවිසුම් විස්තර</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="agreement_date" class="required">ගිවිසුම් දිනය</label>
                        <input type="date" id="agreement_date" name="agreement_date" 
                               value="<?php echo htmlspecialchars($formData['agreement_date'] ?? date('Y-m-d')); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="period_months" class="required">කාලය (මාස)</label>
                        <input type="number" id="period_months" name="period_months" 
                               value="<?php echo htmlspecialchars($formData['period_months'] ?? ''); ?>" 
                               min="1" max="360" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_day" class="required">මාසිකව වාරිකය ගෙවන දිනය</label>
                        <input type="number" id="payment_day" name="payment_day" 
                               value="<?php echo htmlspecialchars($formData['payment_day'] ?? ''); ?>" 
                               min="1" max="31" required 
                               placeholder="1-31">
                    </div>
                </div>
            </div>

            <!-- Financial Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>මුදල් විස්තර</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="amount_number" class="required">මුදල (ඉලක්කමෙන්)</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280;">රු.</span>
                            <input type="number" id="amount_number" name="amount_number" 
                                   value="<?php echo htmlspecialchars($formData['amount_number'] ?? ''); ?>" 
                                   step="0.01" min="0.01" required 
                                   style="padding-left: 40px;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="interest_rate" class="required">පොලී අනුපාතය (%)</label>
                        <input type="number" id="interest_rate" name="interest_rate" 
                               value="<?php echo htmlspecialchars($formData['interest_rate'] ?? ''); ?>" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="amount_words" class="required">මුදල (අකුරෙන්)</label>
                        <textarea id="amount_words" name="amount_words" rows="2" required 
                                  placeholder="උදා: එක් ලක්ෂ පනස් දහසයි"><?php echo htmlspecialchars($formData['amount_words'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="amount_words_counter">0</span> අකුරු
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle/Property Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-car"></i>
                    <span>දේපල / වාහන විස්තර</span>
                </div>
                
                <div class="vehicle-grid">
                    <div class="form-group">
                        <label for="product_sinhala">දේපල වර්ගය</label>
                        <input type="text" id="product_sinhala" name="product_sinhala" 
                               value="<?php echo htmlspecialchars($formData['product_sinhala'] ?? ''); ?>" 
                               placeholder="උදා: මෝටර් රථය, ඉඩම, ගොඩනැගිල්ල">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_number">වාහන අංකය</label>
                        <input type="text" id="vehicle_number" name="vehicle_number" 
                               value="<?php echo htmlspecialchars($formData['vehicle_number'] ?? ''); ?>" 
                               placeholder="උදා: CAB-1234">
                    </div>
                    
                    <div class="form-group">
                        <label for="chassis_number">චැසි අංකය</label>
                        <input type="text" id="chassis_number" name="chassis_number" 
                               value="<?php echo htmlspecialchars($formData['chassis_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="engine_number">එන්ජින් අංකය</label>
                        <input type="text" id="engine_number" name="engine_number" 
                               value="<?php echo htmlspecialchars($formData['engine_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="make">Make</label>
                        <input type="text" id="make" name="make" 
                               value="<?php echo htmlspecialchars($formData['make'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" 
                               value="<?php echo htmlspecialchars($formData['model'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_class_sinhala">වාහන වර්ගය</label>
                        <input type="text" id="vehicle_class_sinhala" name="vehicle_class_sinhala" 
                               value="<?php echo htmlspecialchars($formData['vehicle_class_sinhala'] ?? ''); ?>" 
                               placeholder="උදා: සෙඩෑන්, වෑන්, බස්">
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="clearForm()">
                    <i class="fas fa-eraser"></i> ඉවත් කරන්න
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-eye"></i> Submit & Preview
                </button>
            </div>
        </form>
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: var(--text-light); font-size: 13px;">
        <p>ගිවිසුම් පද්ධතිය v1.0 | සියලුම හිමිකම් ඇවිරිණි</p>
    </div>
</div>

<script>
// Character counter for amount_words textarea
const amountWordsTextarea = document.getElementById('amount_words');
const amountWordsCounter = document.getElementById('amount_words_counter');

if (amountWordsTextarea && amountWordsCounter) {
    amountWordsTextarea.addEventListener('input', function() {
        amountWordsCounter.textContent = this.value.length;
    });
    
    // Initialize counter
    amountWordsCounter.textContent = amountWordsTextarea.value.length;
}

// Form validation
const agreementForm = document.getElementById('agreementForm');
if (agreementForm) {
    agreementForm.addEventListener('submit', function(event) {
        let isValid = true;
        const requiredFields = agreementForm.querySelectorAll('[required]');
        
        // Clear previous error indicators
        agreementForm.querySelectorAll('.has-error').forEach(el => {
            el.classList.remove('has-error');
        });
        
        agreementForm.querySelectorAll('.error-message').forEach(el => {
            el.remove();
        });
        
        // Validate required fields
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.parentElement.classList.add('has-error');
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = `<i class="fas fa-exclamation-circle"></i> මෙම ක්ෂේත්‍රය අවශ්‍යයි`;
                field.parentElement.appendChild(errorMsg);
            }
        });
        
        // Validate payment day
        const paymentDay = document.getElementById('payment_day');
        if (paymentDay && (paymentDay.value < 1 || paymentDay.value > 31)) {
            isValid = false;
            paymentDay.parentElement.classList.add('has-error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.innerHTML = `<i class="fas fa-exclamation-circle"></i> මාසිකව වාරිකය ගෙවන දිනය 1 සිට 31 දක්වා විය යුතුය`;
            paymentDay.parentElement.appendChild(errorMsg);
        }
        
        // Validate amount
        const amount = document.getElementById('amount_number');
        if (amount && parseFloat(amount.value) <= 0) {
            isValid = false;
            amount.parentElement.parentElement.classList.add('has-error');
            
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.innerHTML = `<i class="fas fa-exclamation-circle"></i> මුදල 0 ට වැඩි විය යුතුය`;
            amount.parentElement.appendChild(errorMsg);
        }
        
        if (!isValid) {
            event.preventDefault();
            
            // Scroll to first error
            const firstError = agreementForm.querySelector('.has-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

// Clear form function
function clearForm() {
    if (confirm('ඔබට මෙම පෝරමයේ ඇති සියලුම දත්ත ඉවත් කිරීමට අවශ්‍යද?')) {
        document.getElementById('agreementForm').reset();
        
        // Reset character counter
        if (amountWordsCounter) {
            amountWordsCounter.textContent = 0;
        }
        
        // Focus on first field
        document.getElementById('name').focus();
    }
}

// Set agreement date to today by default if empty
window.addEventListener('DOMContentLoaded', function() {
    const agreementDateField = document.getElementById('agreement_date');
    if (agreementDateField && !agreementDateField.value) {
        const today = new Date();
        agreementDateField.value = today.toISOString().split('T')[0];
    }
    
    // Set payment day to today's date if empty
    const paymentDayField = document.getElementById('payment_day');
    if (paymentDayField && !paymentDayField.value) {
        const today = new Date();
        paymentDayField.value = today.getDate();
    }
});

// Auto-format NIC input
const nicField = document.getElementById('nic');
if (nicField) {
    nicField.addEventListener('input', function() {
        let value = this.value.toUpperCase();
        value = value.replace(/[^0-9VX]/g, '');
        this.value = value;
    });
}
</script>
</body>
</html>

<?php
ob_end_flush();
?>