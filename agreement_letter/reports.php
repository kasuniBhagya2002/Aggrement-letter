<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$current_page = 'reports.php';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$product_type = isset($_GET['product_type']) ? $_GET['product_type'] : '';

// Initialize statistics arrays
$stats = [
    'total_agreements' => 0,
    'total_amount' => 0,
    'active_agreements' => 0,
    'expired_agreements' => 0,
    'monthly_agreements' => 0,
    'monthly_amount' => 0
];

// Calculate statistics
$today = date('Y-m-d');
$current_month = date('Y-m');

// Total agreements
$query = "SELECT COUNT(*) as count, SUM(amount_number) as total FROM agreements";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
    $stats['total_agreements'] = $row['count'] ?: 0;
    $stats['total_amount'] = $row['total'] ?: 0;
}

// Active agreements
$query = "SELECT COUNT(*) as count FROM agreements WHERE end_date >= '$today'";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
    $stats['active_agreements'] = $row['count'] ?: 0;
}

// Expired agreements
$query = "SELECT COUNT(*) as count FROM agreements WHERE end_date < '$today'";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
    $stats['expired_agreements'] = $row['count'] ?: 0;
}

// Monthly statistics
$query = "SELECT COUNT(*) as count, SUM(amount_number) as total FROM agreements WHERE DATE_FORMAT(agreement_date, '%Y-%m') = '$current_month'";
$result = $conn->query($query);
if ($row = $result->fetch_assoc()) {
    $stats['monthly_agreements'] = $row['count'] ?: 0;
    $stats['monthly_amount'] = $row['total'] ?: 0;
}

// Get monthly trend data (last 6 months)
$monthly_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $query = "SELECT 
                COUNT(*) as count,
                SUM(amount_number) as total_amount,
                SUM(CASE WHEN end_date >= '$today' THEN 1 ELSE 0 END) as active_count
              FROM agreements 
              WHERE DATE_FORMAT(agreement_date, '%Y-%m') = '$month'";
    
    $result = $conn->query($query);
    $monthly_trend[$month] = $result->fetch_assoc() ?: ['count' => 0, 'total_amount' => 0, 'active_count' => 0];
}

// Get product type distribution
$product_distribution = [];
$query = "SELECT 
            product_sinhala,
            COUNT(*) as count,
            SUM(amount_number) as total_amount
          FROM agreements 
          WHERE product_sinhala IS NOT NULL AND product_sinhala != ''
          GROUP BY product_sinhala
          ORDER BY count DESC";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $product_distribution[] = $row;
}

// Get upcoming expirations (next 30 days)
$thirty_days_later = date('Y-m-d', strtotime('+30 days'));
$query = "SELECT * FROM agreements 
          WHERE end_date BETWEEN '$today' AND '$thirty_days_later'
          ORDER BY end_date ASC
          LIMIT 10";
$upcoming_expirations = $conn->query($query);

// Get recent large agreements (top 10 by amount)
$query = "SELECT * FROM agreements 
          ORDER BY amount_number DESC 
          LIMIT 10";
$large_agreements = $conn->query($query);

// Get annual summary
$annual_summary = [];
$current_year = date('Y');
for ($i = 0; $i < 5; $i++) {
    $year = $current_year - $i;
    $query = "SELECT 
                COUNT(*) as count,
                SUM(amount_number) as total_amount,
                AVG(amount_number) as avg_amount
              FROM agreements 
              WHERE YEAR(agreement_date) = $year";
    
    $result = $conn->query($query);
    $annual_summary[$year] = $result->fetch_assoc() ?: ['count' => 0, 'total_amount' => 0, 'avg_amount' => 0];
}

// Get payment status summary
$payment_status = [];
$query = "SELECT 
            MONTH(agreement_date) as month,
            COUNT(*) as total_agreements,
            SUM(amount_number) as total_amount
          FROM agreements 
          WHERE YEAR(agreement_date) = $current_year
          GROUP BY MONTH(agreement_date)
          ORDER BY month";
$result = $conn->query($query);
$monthly_data = [];
$months = ['', 'ජනවාරි', 'පෙබරවාරි', 'මාර්තු', 'අප්‍රේල්', 'මැයි', 'ජූනි', 
           'ජූලි', 'අගෝස්තු', 'සැප්තැම්බර්', 'ඔක්තෝබර්', 'නොවැම්බර්', 'දෙසැම්බර්'];

while ($row = $result->fetch_assoc()) {
    $month_num = $row['month'];
    $monthly_data[] = [
        'month' => $months[$month_num] ?? $month_num,
        'count' => $row['total_agreements'],
        'amount' => $row['total_amount']
    ];
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>වාර්තා - ගිවිසුම් පද්ධතිය</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
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
    color: #1f2937;
}

/* Navigation Styles (same as before) */
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
    text-decoration: none;
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

/* Main Content */
.main-content {
    margin-left: var(--sidebar-width);
    margin-top: var(--header-height);
    padding: 20px;
    min-height: calc(100vh - var(--header-height));
}

/* Page Header */
.page-header {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-left: 6px solid var(--primary);
}

.page-header h1 {
    color: var(--primary-dark);
    font-size: 26px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.page-header p {
    color: #6b7280;
    font-size: 15px;
    line-height: 1.6;
}

/* Filter Section */
.filter-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.filter-section h3 {
    color: var(--primary-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.filter-group {
    margin-bottom: 15px;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    background: white;
    transition: all 0.3s;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.filter-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.filter-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-btn.primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.filter-btn.primary:hover {
    background: linear-gradient(135deg, var(--primary-dark), #1e3a8a);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
}

.filter-btn.secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.filter-btn.secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.stat-icon.total { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
.stat-icon.amount { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.stat-icon.active { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.stat-icon.expired { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.stat-icon.monthly { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
.stat-icon.revenue { background: linear-gradient(135deg, #ec4899, #db2777); color: white; }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #1f2937;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-change {
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-change.positive {
    color: #059669;
}

.stat-change.negative {
    color: #dc2626;
}

/* Charts Section */
.charts-section {
    margin-bottom: 25px;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 25px;
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

.chart-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h3 {
    color: var(--primary-dark);
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-container {
    height: 300px;
    position: relative;
}

/* Data Tables */
.data-section {
    margin-bottom: 25px;
}

.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
}

@media (max-width: 768px) {
    .data-grid {
        grid-template-columns: 1fr;
    }
}

.data-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.data-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.data-header h3 {
    color: var(--primary-dark);
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f9fafb;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    font-size: 13px;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.amount-cell {
    font-weight: 600;
    color: #059669;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.status-active {
    background: #d1fae5;
    color: #059669;
}

.status-expired {
    background: #fee;
    color: #dc2626;
}

.status-upcoming {
    background: #fef3c7;
    color: #d97706;
}

/* Export Section */
.export-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    text-align: center;
}

.export-section h3 {
    color: var(--primary-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 18px;
}

.export-options {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.export-btn {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.export-btn.pdf {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.export-btn.pdf:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.export-btn.excel {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.export-btn.excel:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
}

.export-btn.print {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.export-btn.print:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
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
    
    .logo span {
        display: none;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .export-options {
        flex-direction: column;
    }
    
    .export-btn {
        width: 100%;
        justify-content: center;
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
    
    .page-header,
    .filter-section,
    .stat-card,
    .chart-card,
    .data-card,
    .export-section {
        padding: 15px;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        margin-bottom: 15px;
    }
}
</style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-file-contract"></i>
            <span>ගිවිසුම් පද්ධතිය</span>
        </a>
        
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
                <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>මුල් පිටුව</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $current_page == 'create_agreement.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>නව ගිවිසුම</span>
                </a>
            </li>
            
          
            
            <li class="nav-item">
                <a href="view_agreements.php" class="nav-link <?php echo $current_page == 'search.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>ගිවිසුම් සොයන්න</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>වාර්තා</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> වාර්තා සහ විශ්ලේෂණය</h1>
            <p>ගිවිසුම් පද්ධතියේ සමස්ත ක්‍රියාකාරිත්වය පිළිබඳ විස්තරාත්මක වාර්තා සහ සංඛ්‍යාලේඛන</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> වාර්තා පෙරහන්</h3>
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="start_date">ආරම්භක දිනය</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">අවසන් දිනය</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">ගිවිසුම් තත්වය</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>සියලුම</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>ක්‍රියාකාරී</option>
                            <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>කල් ඉකුත් වූ</option>
                            <option value="upcoming" <?php echo $status == 'upcoming' ? 'selected' : ''; ?>>ඉදිරියේදී අවසන් වන</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="product_type">දේපල වර්ගය</label>
                        <select id="product_type" name="product_type">
                            <option value="">සියලුම</option>
                            <?php
                            $product_types = $conn->query("SELECT DISTINCT product_sinhala FROM agreements WHERE product_sinhala IS NOT NULL AND product_sinhala != ''");
                            while ($type = $product_types->fetch_assoc()) {
                                $selected = $product_type == $type['product_sinhala'] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($type['product_sinhala']) . '" ' . $selected . '>' 
                                     . htmlspecialchars($type['product_sinhala']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="filter-btn primary">
                        <i class="fas fa-filter"></i> වාර්තා උත්පාදනය
                    </button>
                    <button type="reset" class="filter-btn secondary" onclick="window.location.href='reports.php'">
                        <i class="fas fa-redo"></i> පෙරහන් ඉවත් කරන්න
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_agreements']); ?></div>
                    <div class="stat-label">සමස්ත ගිවිසුම්</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php 
                        $prev_month = date('Y-m', strtotime('-1 month'));
                        $query = "SELECT COUNT(*) as count FROM agreements WHERE DATE_FORMAT(agreement_date, '%Y-%m') = '$prev_month'";
                        $prev_count = $conn->query($query)->fetch_assoc()['count'] ?: 0;
                        $change = $prev_count > 0 ? (($stats['monthly_agreements'] - $prev_count) / $prev_count * 100) : 100;
                        echo number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'වැඩිවීම' : 'අඩුවීම');
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon amount">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">රු. <?php echo number_format($stats['total_amount'], 2); ?></div>
                    <div class="stat-label">සමස්ත මුදල</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php 
                        $query = "SELECT SUM(amount_number) as total FROM agreements WHERE DATE_FORMAT(agreement_date, '%Y-%m') = '$prev_month'";
                        $prev_total = $conn->query($query)->fetch_assoc()['total'] ?: 0;
                        $change = $prev_total > 0 ? (($stats['monthly_amount'] - $prev_total) / $prev_total * 100) : 100;
                        echo number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'වැඩිවීම' : 'අඩුවීම');
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['active_agreements']); ?></div>
                    <div class="stat-label">ක්‍රියාකාරී ගිවිසුම්</div>
                    <div class="stat-change positive">
                        <i class="fas fa-chart-line"></i>
                        <?php echo number_format($stats['active_agreements'] > 0 ? ($stats['active_agreements'] / $stats['total_agreements'] * 100) : 0, 1); ?>% ක්‍රියාකාරී
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon expired">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['expired_agreements']); ?></div>
                    <div class="stat-label">කල් ඉකුත් වූ ගිවිසුම්</div>
                    <div class="stat-change">
                        <i class="fas fa-chart-line"></i>
                        <?php echo number_format($stats['expired_agreements'] > 0 ? ($stats['expired_agreements'] / $stats['total_agreements'] * 100) : 0, 1); ?>% කල් ඉකුත්
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon monthly">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['monthly_agreements']); ?></div>
                    <div class="stat-label">මෙම මාසයේ ගිවිසුම්</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        රු. <?php echo number_format($stats['monthly_amount'], 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">රු. <?php echo number_format($stats['monthly_amount'], 2); ?></div>
                    <div class="stat-label">මෙම මාසයේ මුදල</div>
                    <div class="stat-change positive">
                        <i class="fas fa-percentage"></i>
                        සාමාන්‍ය: රු. <?php echo number_format($stats['monthly_agreements'] > 0 ? $stats['monthly_amount'] / $stats['monthly_agreements'] : 0, 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="charts-grid">
                <!-- Monthly Trend Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> මාසික ගිවිසුම් ප්‍රවණතාව</h3>
                    </div>
                    <div id="monthlyTrendChart" class="chart-container"></div>
                </div>
                
                <!-- Product Distribution Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> දේපල වර්ග අනුව ව්‍යාප්තිය</h3>
                    </div>
                    <div id="productDistributionChart" class="chart-container"></div>
                </div>
                
                <!-- Annual Summary Chart -->
                <div class="chart-card" style="grid-column: 1 / -1;">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> වාර්ෂික සාරාංශය (වසර 5)</h3>
                    </div>
                    <div id="annualSummaryChart" class="chart-container" style="height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Data Tables Section -->
        <div class="data-section">
            <div class="data-grid">
                <!-- Upcoming Expirations -->
                <div class="data-card">
                    <div class="data-header">
                        <h3><i class="fas fa-clock"></i> ඉදිරියේදී අවසන් වන ගිවිසුම් (ඉදිරි දින 30)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>නම</th>
                                    <th>වාහන අංකය</th>
                                    <th>අවසන් දිනය</th>
                                    <th>තත්වය</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($upcoming_expirations->num_rows > 0): ?>
                                    <?php while ($row = $upcoming_expirations->fetch_assoc()): ?>
                                        <?php
                                        $end_date = new DateTime($row['end_date']);
                                        $days_remaining = $today->diff($end_date)->days;
                                        $status_class = $days_remaining <= 7 ? 'status-upcoming' : 'status-active';
                                        $status_text = $days_remaining <= 7 ? 'ඉක්මනින් අවසන්' : ($days_remaining . ' දින');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['vehicle_number'] ?: '-'); ?></td>
                                            <td><?php echo $row['end_date']; ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">
                                            ඉදිරි දින 30 තුල අවසන් වන ගිවිසුම් නැත
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Large Agreements -->
                <div class="data-card">
                    <div class="data-header">
                        <h3><i class="fas fa-money-check-alt"></i> ඉහළ මුදල් වල ගිවිසුම් (පළමු 10)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>නම</th>
                                    <th>මුදල (රු.)</th>
                                    <th>දිනය</th>
                                    <th>තත්වය</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($large_agreements->num_rows > 0): ?>
                                    <?php while ($row = $large_agreements->fetch_assoc()): ?>
                                        <?php
                                        $end_date = new DateTime($row['end_date']);
                                        $status_class = $end_date < $today ? 'status-expired' : 'status-active';
                                        $status_text = $end_date < $today ? 'කල් ඉකුත්' : 'ක්‍රියාකාරී';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="amount-cell">රු. <?php echo number_format($row['amount_number'], 2); ?></td>
                                            <td><?php echo $row['agreement_date']; ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">
                                            ගිවිසුම් නැත
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Monthly Summary Table -->
                <div class="data-card" style="grid-column: 1 / -1;">
                    <div class="data-header">
                        <h3><i class="fas fa-calendar-check"></i> මාසික සාරාංශය (<?php echo $current_year; ?> වර්ෂය)</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>මාසය</th>
                                    <th>ගිවිසුම් ගණන</th>
                                    <th>මුළු මුදල (රු.)</th>
                                    <th>සාමාන්‍ය මුදල (රු.)</th>
                                    <th>සම්භාවිතාව</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_data as $data): ?>
                                    <tr>
                                        <td><?php echo $data['month']; ?></td>
                                        <td><?php echo number_format($data['count']); ?></td>
                                        <td class="amount-cell">රු. <?php echo number_format($data['amount'], 2); ?></td>
                                        <td><?php echo number_format($data['count'] > 0 ? $data['amount'] / $data['count'] : 0, 2); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = $stats['total_agreements'] > 0 ? ($data['count'] / $stats['total_agreements'] * 100) : 0;
                                            ?>
                                            <div style="background: #e5e7eb; border-radius: 10px; height: 8px; margin: 5px 0;">
                                                <div style="background: var(--primary); width: <?php echo $percentage; ?>%; height: 100%; border-radius: 10px;"></div>
                                            </div>
                                            <span style="font-size: 12px; color: #6b7280;"><?php echo number_format($percentage, 1); ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Trend Chart
        const monthlyMonths = Object.keys(<?php echo json_encode($monthly_trend); ?>);
        const monthlyCounts = Object.values(<?php echo json_encode($monthly_trend); ?>).map(item => item.count);
        const monthlyAmounts = Object.values(<?php echo json_encode($monthly_trend); ?>).map(item => item.total_amount || 0);
        
        const monthlyTrendOptions = {
            series: [{
                name: 'ගිවිසුම් ගණන',
                data: monthlyCounts,
                type: 'column'
            }, {
                name: 'මුළු මුදල (රු. ලක්ෂ)',
                data: monthlyAmounts.map(amount => (amount / 100000).toFixed(2)),
                type: 'line'
            }],
            chart: {
                height: '100%',
                type: 'line',
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: [0, 3]
            },
            colors: ['#3b82f6', '#10b981'],
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: monthlyMonths,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [{
                title: {
                    text: 'ගිවිසුම් ගණන'
                }
            }, {
                opposite: true,
                title: {
                    text: 'මුදල (රු. ලක්ෂ)'
                }
            }],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (value, { seriesIndex }) {
                        return seriesIndex === 0 ? value + ' ගිවිසුම්' : 'රු. ' + (value * 100000).toLocaleString('si-LK');
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        };
        
        const monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), monthlyTrendOptions);
        monthlyTrendChart.render();
        
        // Product Distribution Chart
        const productLabels = <?php echo json_encode(array_column($product_distribution, 'product_sinhala')); ?>;
        const productCounts = <?php echo json_encode(array_column($product_distribution, 'count')); ?>;
        
        const productDistributionOptions = {
            series: productCounts,
            chart: {
                type: 'donut',
                height: '100%'
            },
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
            labels: productLabels,
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)';
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'සමස්ත ගිවිසුම්',
                                color: '#6b7280',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' ගිවිසුම්';
                    }
                }
            },
            legend: {
                position: 'right',
                horizontalAlign: 'center'
            }
        };
        
        const productDistributionChart = new ApexCharts(document.querySelector("#productDistributionChart"), productDistributionOptions);
        productDistributionChart.render();
        
        // Annual Summary Chart
        const annualYears = Object.keys(<?php echo json_encode($annual_summary); ?>).reverse();
        const annualCounts = Object.values(<?php echo json_encode($annual_summary); ?>).reverse().map(item => item.count);
        const annualAmounts = Object.values(<?php echo json_encode($annual_summary); ?>).reverse().map(item => item.total_amount || 0);
        
        const annualSummaryOptions = {
            series: [{
                name: 'ගිවිසුම් ගණන',
                data: annualCounts
            }],
            chart: {
                type: 'bar',
                height: '100%',
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: false,
                    columnWidth: '60%',
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val;
                }
            },
            colors: ['#3b82f6'],
            xaxis: {
                categories: annualYears,
                title: {
                    text: 'වර්ෂය'
                }
            },
            yaxis: {
                title: {
                    text: 'ගිවිසුම් ගණන'
                }
            },
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex, dataPointIndex }) {
                        const amount = annualAmounts[dataPointIndex];
                        return val + ' ගිවිසුම්<hr>මුළු මුදල: රු. ' + amount.toLocaleString('si-LK') + 
                               '<br>සාමාන්‍ය: රු. ' + (val > 0 ? Math.round(amount / val).toLocaleString('si-LK') : '0');
                    }
                }
            }
        };
        
        const annualSummaryChart = new ApexCharts(document.querySelector("#annualSummaryChart"), annualSummaryOptions);
        annualSummaryChart.render();
        
        // Auto-update date fields
        const today = new Date().toISOString().split('T')[0];
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
        const lastDay = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split('T')[0];
        
        if (!document.getElementById('start_date').value) {
            document.getElementById('start_date').value = firstDay;
        }
        if (!document.getElementById('end_date').value) {
            document.getElementById('end_date').value = lastDay;
        }
        
        // Validate date range
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && startDate > endDate) {
                e.preventDefault();
                alert('ආරම්භක දිනය අවසන් දිනයට පසුව විය නොහැක.');
                return false;
            }
        });
        
        // Refresh data every 5 minutes
        setInterval(() => {
            // You can implement auto-refresh here if needed
        }, 300000);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+F for filter focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.getElementById('start_date').focus();
        }
        
        // Ctrl+P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
    </script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>