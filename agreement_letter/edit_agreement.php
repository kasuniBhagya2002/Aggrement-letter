<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Get agreement ID from URL
$agreement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($agreement_id <= 0) {
    header("Location: view_agreements.php");
    exit;
}

// Initialize variables
$errors = [];
$success = false;
$agreement = null;

// Fetch agreement data
$stmt = $conn->prepare("SELECT * FROM agreements WHERE id = ?");
$stmt->bind_param("i", $agreement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_agreements.php");
    exit;
}

$agreement = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
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

    // Validation
    if (empty($name)) $errors[] = "නම අවශ්‍යයි";
    if (empty($nic)) $errors[] = "ජාතික හැඳුනුම්පත් අංකය අවශ්‍යයි";
    if (empty($address)) $errors[] = "ලිපිනය අවශ්‍යයි";
    if ($payment_day < 1 || $payment_day > 31) $errors[] = "ගෙවීම් දිනය 1 සිට 31 දක්වා විය යුතුය";
    if ($period_months < 1) $errors[] = "කාලය අවම වශයෙන් 1 මාසයක් විය යුතුය";
    if ($amount_number <= 0) $errors[] = "මුදල 0 ට වැඩි විය යුතුය";
    if (empty($amount_words)) $errors[] = "මුදල (අකුරෙන්) අවශ්‍යයි";
    
    // NIC validation
    if (!empty($nic) && !preg_match('/^[0-9]{9}[vVxX]?$|^[0-9]{12}$/', $nic)) {
        $errors[] = "ජාතික හැඳුනුම්පත් අංකය වලංගු නොවේ";
    }

    // If no errors, update the agreement
    if (empty($errors)) {
        // Calculate new end date
        $startDate = new DateTime($agreement_date);
        $startDate->modify("+{$period_months} months");
        $end_date = $startDate->format('Y-m-d');

        $update_stmt = $conn->prepare("
            UPDATE agreements SET
                name = ?,
                address = ?,
                nic = ?,
                agreement_date = ?,
                end_date = ?,
                payment_day = ?,
                period_months = ?,
                amount_number = ?,
                amount_words = ?,
                interest_rate = ?,
                product_sinhala = ?,
                vehicle_number = ?,
                chassis_number = ?,
                engine_number = ?,
                make = ?,
                model = ?,
                vehicle_class_sinhala = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $update_stmt->bind_param(
            "sssssiidsssssssssi",
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
            $vehicle_class_sinhala,
            $agreement_id
        );

        if ($update_stmt->execute()) {
            $success = true;
            
            // Update local agreement data for display
            $agreement = array_merge($agreement, [
                'name' => $name,
                'address' => $address,
                'nic' => $nic,
                'agreement_date' => $agreement_date,
                'end_date' => $end_date,
                'payment_day' => $payment_day,
                'period_months' => $period_months,
                'amount_number' => $amount_number,
                'amount_words' => $amount_words,
                'interest_rate' => $interest_rate,
                'product_sinhala' => $product_sinhala,
                'vehicle_number' => $vehicle_number,
                'chassis_number' => $chassis_number,
                'engine_number' => $engine_number,
                'make' => $make,
                'model' => $model,
                'vehicle_class_sinhala' => $vehicle_class_sinhala
            ]);
            
            $_SESSION['edit_success'] = "ගිවිසුම සාර්ථකව යාවත්කාලීන කරන ලදී!";
        } else {
            $errors[] = "යාවත්කාලීන කිරීමේ දෝෂය: " . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
}

// Get current page for navigation
$current_page = 'edit_agreement.php';
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ගිවිසුම සංස්කරණය - <?php echo htmlspecialchars($agreement['name'] ?? ''); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --sidebar-width: 250px;
    --header-height: 60px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Iskoola Pota', sans-serif;
    background: #f8fafc;
}

/* Top Header */
.top-header {
    background: white;
    height: var(--header-height);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    padding: 0 20px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    color: var(--primary-dark);
    font-size: 18px;
}

.logo i {
    color: var(--primary);
    font-size: 22px;
}

.user-info {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-info span {
    color: #374151;
    font-weight: 500;
}

.logout-btn {
    background: #fee;
    color: #dc2626;
    border: 1px solid #fca5a5;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.3s;
}

.logout-btn:hover {
    background: #fecaca;
}

/* Sidebar Navigation */
.sidebar {
    position: fixed;
    top: var(--header-height);
    left: 0;
    width: var(--sidebar-width);
    height: calc(100vh - var(--header-height));
    background: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    overflow-y: auto;
    z-index: 900;
}

.nav-menu {
    list-style: none;
    padding: 20px 0;
}

.nav-item {
    margin: 5px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #374151;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.nav-link i {
    width: 20px;
    text-align: center;
    color: #6b7280;
}

.nav-link:hover {
    background: #f0f9ff;
    color: var(--primary);
    border-left-color: var(--primary);
}

.nav-link:hover i {
    color: var(--primary);
}

.nav-link.active {
    background: #dbeafe;
    color: var(--primary-dark);
    border-left-color: var(--primary);
    font-weight: 600;
}

.nav-link.active i {
    color: var(--primary);
}

/* Main Content Area */
.main-content {
    margin-left: var(--sidebar-width);
    margin-top: var(--header-height);
    padding: 20px;
    min-height: calc(100vh - var(--header-height));
}

/* Page Header */
.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 5px solid var(--primary);
}

.page-header h1 {
    color: var(--primary-dark);
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.page-header p {
    color: #6b7280;
    font-size: 14px;
}

.page-header .agreement-info {
    display: flex;
    gap: 20px;
    margin-top: 10px;
    font-size: 13px;
}

.page-header .agreement-info span {
    background: #f3f4f6;
    padding: 5px 10px;
    border-radius: 5px;
    color: #374151;
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #059669;
}

.alert-error {
    background: #fee;
    border: 1px solid #ef4444;
    color: #dc2626;
}

/* Form Container */
.form-container {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Form Sections */
.form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    color: var(--primary-dark);
    font-size: 16px;
    font-weight: 600;
}

.section-title i {
    color: var(--primary);
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

/* Form Groups */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.form-group label.required::after {
    content: " *";
    color: #ef4444;
}

.form-group input,
.form-group textarea,
.form-group select 
.form-group text {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus 
.form-group text{
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group textarea 
.form-group text {
    min-height: 80px;
    resize: vertical;
}

/* Helper text */
.helper {
    font-size: 12px;
    color: #6b7280;
    margin-top: 3px;
    display: block;
}

/* Character counter */
.char-counter {
    text-align: right;
    font-size: 11px;
    color: #6b7280;
    margin-top: 3px;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), #1e3a8a);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.btn-danger {
    background: #fee;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

.btn-danger:hover {
    background: #fecaca;
    transform: translateY(-2px);
}

/* Vehicle Grid */
.vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 10px;
}

/* Financial Grid */
.financial-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.financial-item {
    background: #f0f9ff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #dbeafe;
}

.financial-item label {
    color: #1e40af;
}

.financial-item input {
    background: transparent;
    border: none;
    padding: 5px 0;
    font-size: 16px;
    font-weight: 500;
}

.financial-item input:focus {
    background: white;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        overflow: hidden;
    }
    
    .nav-link span {
        display: none;
    }
    
    .nav-link {
        justify-content: center;
        padding: 15px 0;
    }
    
    .nav-link i {
        font-size: 18px;
    }
    
    .main-content {
        margin-left: 70px;
        padding: 15px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .vehicle-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .logo span {
        display: none;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 60px;
    }
    
    .main-content {
        margin-left: 60px;
        padding: 10px;
    }
    
    .user-info span {
        display: none;
    }
    
    .form-container {
        padding: 15px;
    }
}
</style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="logo">
            <i class="fas fa-file-contract"></i>
            <span>ගිවිසුම් පද්ධතිය</span>
        </div>
        
        <div class="user-info">
            <span>පරිශීලක: <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> ඉවත්වන්න
            </a>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>මුල් පිටුව</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>නව ගිවිසුම</span>
                </a>
            </li>
            
            
            
            <li class="nav-item">
                <a href="view_agreements.php" class="nav-link">
                    <i class="fas fa-search"></i>
                    <span>ගිවිසුම් සොයන්න</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>වාර්තා</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>සැකසුම්</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> ගිවිසුම සංස්කරණය</h1>
            <p>ගිවිසුම් අංකය: <?php echo $agreement_id; ?> | නම: <?php echo htmlspecialchars($agreement['name']); ?></p>
            <div class="agreement-info">
                <span>සාදන ලදි: <?php echo date('Y-m-d', strtotime($agreement['created_at'])); ?></span>
                <?php if ($agreement['updated_at']): ?>
                    <span>යාවත්කාලීන කළ දිනය: <?php echo date('Y-m-d', strtotime($agreement['updated_at'])); ?></span>
                <?php endif; ?>
                <span>තත්වය: 
                    <?php
                    $today = new DateTime();
                    $end_date = new DateTime($agreement['end_date']);
                    if ($end_date < $today) {
                        echo '<span style="color: #dc2626;">කල් ඉකුත් වූ</span>';
                    } else {
                        echo '<span style="color: #059669;">ක්‍රියාකාරී</span>';
                    }
                    ?>
                </span>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <strong>සාර්ථකයි!</strong> ගිවිසුම සාර්ථකව යාවත්කාලීන කරන ලදී.
            </div>
        <?php endif; ?>
        
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

        <!-- Edit Form -->
        <form method="POST" action="" class="form-container">
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
                               value="<?php echo htmlspecialchars($agreement['name']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nic" class="required">ජාතික හැඳුනුම්පත් අංකය</label>
                        <input type="text" id="nic" name="nic" 
                               value="<?php echo htmlspecialchars($agreement['nic']); ?>" 
                               required>
                        <span class="helper">123456789V හෝ 123456789012</span>
                    </div>
                    
                   <div class="form-group" style="grid-column: 1 / -1;">
    <label for="address" class="required">ලිපිනය</label>
    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($agreement['address'] ?? ''); ?>" required>
    <div class="char-counter">
        <span id="address_counter"><?php echo strlen($agreement['address'] ?? ''); ?></span> අකුරු
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
                               value="<?php echo htmlspecialchars($agreement['agreement_date']); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="period_months" class="required">කාලය (මාස)</label>
                        <input type="number" id="period_months" name="period_months" 
                               value="<?php echo htmlspecialchars($agreement['period_months']); ?>" 
                               min="1" max="360" required>
                        <span class="helper">මාස ගණන</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_day" class="required">මාසිකව වාරිකය දිනය</label>
                        <input type="number" id="payment_day" name="payment_day" 
                               value="<?php echo htmlspecialchars($agreement['payment_day']); ?>" 
                               min="1" max="31" required>
                        <span class="helper">මසක දිනය (1-31)</span>
                    </div>
                </div>
            </div>

            <!-- Financial Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>මුදල් විස්තර</span>
                </div>
                
                <div class="financial-grid">
                    <div class="financial-item">
                        <label for="amount_number" class="required">මුදල (රු.)</label>
                        <input type="number" id="amount_number" name="amount_number" 
                               value="<?php echo htmlspecialchars($agreement['amount_number']); ?>" 
                               step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="financial-item">
                        <label for="interest_rate" class="required">පොලී අනුපාතය (%)</label>
                        <input type="number" id="interest_rate" name="interest_rate" 
                               value="<?php echo htmlspecialchars($agreement['interest_rate']); ?>" 
                               step="0.01" min="0" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label for="amount_words" class="required">මුදල (අකුරෙන්)</label>
                    <textarea id="amount_words" name="amount_words" rows="2" required><?php echo htmlspecialchars($agreement['amount_words']); ?></textarea>
                    <div class="char-counter">
                        <span id="amount_words_counter"><?php echo strlen($agreement['amount_words']); ?></span> අකුරු
                    </div>
                </div>
            </div>

            <!-- Vehicle/Property Details Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-car"></i>
                    <span>වාහන / දේපල විස්තර</span>
                </div>
                
                <div class="vehicle-grid">
                    <div class="form-group">
                        <label for="product_sinhala">දේපල වර්ගය</label>
                        <input type="text" id="product_sinhala" name="product_sinhala" 
                               value="<?php echo htmlspecialchars($agreement['product_sinhala'] ?? ''); ?>"
                               placeholder="මෝටර් රථය, ඉඩම, ආදිය">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_number">වාහන අංකය</label>
                        <input type="text" id="vehicle_number" name="vehicle_number" 
                               value="<?php echo htmlspecialchars($agreement['vehicle_number'] ?? ''); ?>"
                               placeholder="CAB-1234">
                    </div>
                    
                    <div class="form-group">
                        <label for="chassis_number">චැසි අංකය</label>
                        <input type="text" id="chassis_number" name="chassis_number" 
                               value="<?php echo htmlspecialchars($agreement['chassis_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="engine_number">එන්ජින් අංකය</label>
                        <input type="text" id="engine_number" name="engine_number" 
                               value="<?php echo htmlspecialchars($agreement['engine_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="make">Make</label>
                        <input type="text" id="make" name="make" 
                               value="<?php echo htmlspecialchars($agreement['make'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" 
                               value="<?php echo htmlspecialchars($agreement['model'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_class_sinhala">වාහන වර්ගය</label>
                        <input type="text" id="vehicle_class_sinhala" name="vehicle_class_sinhala" 
                               value="<?php echo htmlspecialchars($agreement['vehicle_class_sinhala'] ?? ''); ?>"
                               placeholder="සෙඩෑන්, වෑන්, බස්">
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-eye"></i>
                    <span>පෙරදසුන</span>
                </div>
                
                <div style="background: #f8fafc; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px;">
                        <div>
                            <strong>ආරම්භක දිනය:</strong> 
                            <span id="preview_start_date"><?php echo $agreement['agreement_date']; ?></span>
                        </div>
                        <div>
                            <strong>අවසන් දිනය:</strong> 
                            <span id="preview_end_date"><?php echo $agreement['end_date']; ?></span>
                        </div>
                        <div>
                            <strong>මුළු මාස:</strong> 
                            <span id="preview_months"><?php echo $agreement['period_months']; ?></span>
                        </div>
                        <div>
                            <strong>මුළු මුදල:</strong> 
                            <span id="preview_amount" style="color: #059669; font-weight: 600;">
                                රු. <?php echo number_format($agreement['amount_number'], 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="view_agreements.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> අත්හරින්න
                </a>
                <a href="view_agreements.php?id=<?php echo $agreement_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> බලන්න
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> ගිවිසුම යාවත්කාලීන කරන්න
                </button>
                <a href="preview.php?id=<?php echo $agreement_id; ?>" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-print"></i> මුද්‍රණය කරන්න
                </a>
            </div>
        </form>
    </div>

    <script>
    // Character counters
    function setupCounters() {
        const textareas = ['address', 'amount_words'];
        
        textareas.forEach(id => {
            const textarea = document.getElementById(id);
            const counter = document.getElementById(`${id}_counter`);
            
            if (textarea && counter) {
                textarea.addEventListener('input', function() {
                    counter.textContent = this.value.length;
                });
            }
        });
    }

    // Auto-calculate end date
    function updateEndDate() {
        const agreementDate = document.getElementById('agreement_date').value;
        const periodMonths = parseInt(document.getElementById('period_months').value) || 0;
        const startPreview = document.getElementById('preview_start_date');
        const endPreview = document.getElementById('preview_end_date');
        const monthsPreview = document.getElementById('preview_months');
        
        if (agreementDate && periodMonths > 0) {
            const startDate = new Date(agreementDate);
            const endDate = new Date(startDate);
            endDate.setMonth(endDate.getMonth() + periodMonths);
            
            // Update preview
            startPreview.textContent = startDate.toLocaleDateString('si-LK');
            endPreview.textContent = endDate.toLocaleDateString('si-LK');
            monthsPreview.textContent = periodMonths;
        }
    }

    // Update amount preview
    function updateAmountPreview() {
        const amount = parseFloat(document.getElementById('amount_number').value) || 0;
        const amountPreview = document.getElementById('preview_amount');
        
        if (amount > 0) {
            amountPreview.textContent = 'රු. ' + amount.toLocaleString('si-LK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        setupCounters();
        
        // Set up real-time preview updates
        const dateInput = document.getElementById('agreement_date');
        const monthsInput = document.getElementById('period_months');
        const amountInput = document.getElementById('amount_number');
        
        if (dateInput) {
            dateInput.addEventListener('change', updateEndDate);
            dateInput.addEventListener('input', updateEndDate);
        }
        
        if (monthsInput) {
            monthsInput.addEventListener('input', updateEndDate);
        }
        
        if (amountInput) {
            amountInput.addEventListener('input', updateAmountPreview);
        }
        
        // Initialize previews
        updateEndDate();
        updateAmountPreview();
        
        // Format NIC input
        const nicField = document.getElementById('nic');
        if (nicField) {
            nicField.addEventListener('input', function() {
                let value = this.value.toUpperCase();
                value = value.replace(/[^0-9VX]/g, '');
                this.value = value;
            });
        }
        
        // Confirm before leaving if form has changes
        const form = document.querySelector('form');
        let formChanged = false;
        
        const formInputs = form.querySelectorAll('input, textarea, select');
        formInputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });

    // Format currency on blur
    document.getElementById('amount_number')?.addEventListener('blur', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value) && value > 0) {
            this.value = value.toFixed(2);
            updateAmountPreview();
        }
    });

    // Format interest rate on blur
    document.getElementById('interest_rate')?.addEventListener('blur', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value) && value >= 0) {
            this.value = value.toFixed(2);
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>