<?php
session_start();

// Check if user is logged in - FIXED VERSION
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Get current page for navigation
$current_page = 'view_agreements.php';

// Pagination variables
$limit = 15; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1

$offset = ($page - 1) * $limit;

// Get total number of agreements
$total_result = $conn->query("SELECT COUNT(*) as total FROM agreements");
$total_row = $total_result->fetch_assoc();
$total_agreements = $total_row['total'];
$total_pages = ceil($total_agreements / $limit);

// Ensure page doesn't exceed total pages
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$search_params = [];
$types = '';

if (!empty($search)) {
    $where_clause = " WHERE name LIKE ? OR nic LIKE ? OR vehicle_number LIKE ? OR product_sinhala LIKE ? ";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term];
    $types = 'ssss';
}

// Get agreements with pagination
$sql = "SELECT * FROM agreements $where_clause ORDER BY agreement_date DESC LIMIT ? OFFSET ?";

if (!empty($search)) {
    $sql = "SELECT * FROM agreements $where_clause ORDER BY agreement_date DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params = array_merge($search_params, [$limit, $offset]);
} else {
    $sql = "SELECT * FROM agreements ORDER BY agreement_date DESC LIMIT ? OFFSET ?";
    $types = 'ii';
    $params = [$limit, $offset];
}

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ගිවිසුම් බලන්න - ගිවිසුම් පද්ධතිය</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Navigation Styles - FIXED VERSION */
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
body {
    font-family: 'Iskoola Pota', sans-serif;
    background: #f8fafc;
}

/* Font Awesome Fix */
.fa,
.fas,
.far,
.fab,
.fa-solid {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900;
}

/* Action button icon fix */
.action-btn i {
    font-size: 14px;
    line-height: 1;
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

/* Page Title */
.page-title {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 5px solid var(--primary);
}

.page-title h1 {
    color: var(--primary-dark);
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.page-title p {
    color: #6b7280;
    font-size: 14px;
}

/* Search and Filter Section */
.search-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.search-input {
    flex: 1;
    position: relative;
}

.search-input input {
    width: 100%;
    padding: 12px 40px 12px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}

.search-input i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.search-btn:hover {
    background: var(--primary-dark);
}

.clear-btn {
    background: #ef4444;
    color: white;
    text-decoration: none;
    padding: 12px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.clear-btn:hover {
    background: #dc2626;
}

.filter-tags {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-tag {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.3s;
}

.filter-tag.active {
    background: #dbeafe;
    color: var(--primary-dark);
    font-weight: 600;
}

.filter-tag:not(.active) {
    background: #f3f4f6;
    color: #6b7280;
}

.filter-tag:not(.active):hover {
    background: #e5e7eb;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    color: white;
    padding: 20px;
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-card.total {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.stat-card.monthly {
    background: linear-gradient(135deg, #10b981, #059669);
}

.stat-card.active {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    margin: 5px 0;
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
}

.stat-icon {
    font-size: 40px;
    opacity: 0.8;
}

/* Agreements Table */
.table-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.table-responsive {
    overflow-x: auto;
}

.agreements-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.agreements-table thead {
    background: #f3f4f6;
}

.agreements-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}

.agreements-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.3s;
}

.agreements-table tbody tr:hover {
    background: #f9fafb;
}

.agreements-table td {
    padding: 15px;
    vertical-align: middle;
}

/* Status Badge */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.status-active {
    background: #d1fae5;
    color: #059669;
}

.status-warning {
    background: #fef3c7;
    color: #d97706;
}

.status-expired {
    background: #fee;
    color: #dc2626;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 5px;
}

.action-btn {
    padding: 6px 10px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    transition: all 0.3s;
}

.view-btn {
    background: #dbeafe;
    color: #2563eb;
}

.view-btn:hover {
    background: #bfdbfe;
}

.edit-btn {
    background: #fef3c7;
    color: #d97706;
}

.edit-btn:hover {
    background: #fde68a;
}

.print-btn {
    background: #dcfce7;
    color: #16a34a;
}

.print-btn:hover {
    background: #bbf7d0;
}

.delete-btn {
    background: #fee;
    color: #dc2626;
    border: none;
    cursor: pointer;
}

.delete-btn:hover {
    background: #fecaca;
}

/* Empty State */
.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #6b7280;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #d1d5db;
    display: block;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.pagination a, .pagination span {
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination a {
    background: #f3f4f6;
    color: #374151;
    transition: all 0.3s;
}

.pagination a:hover {
    background: #e5e7eb;
}

.pagination .current {
    background: var(--primary);
    color: white;
    font-weight: 600;
}

/* Export Options */
.export-options {
    text-align: right;
    margin-top: 20px;
}

.export-container {
    display: inline-flex;
    gap: 10px;
    background: white;
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.export-label {
    font-size: 14px;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 8px;
}

.export-btn {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s;
}

.export-btn.pdf {
    background: #ef4444;
    color: white;
}

.export-btn.pdf:hover {
    background: #dc2626;
}

.export-btn.excel {
    background: #10b981;
    color: white;
}

.export-btn.excel:hover {
    background: #059669;
}

.export-btn.csv {
    background: #3b82f6;
    color: white;
}

.export-btn.csv:hover {
    background: #2563eb;
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
    
    .search-form {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .export-container {
        flex-direction: column;
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
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        margin-bottom: 5px;
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
        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-list"></i> ගිවිසුම් ලැයිස්තුව</h1>
            <p>සමස්ත ගිවිසුම්: <?php echo $total_agreements; ?></p>
        </div>

        <!-- Search and Filter -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <div class="search-input">
                    <input type="text" name="search" placeholder="නම, ජාතික හැදුනුම, වාහන අංකය, ආදියෙන් සොයන්න..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <i class="fas fa-search"></i>
                </div>
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> සොයන්න
                </button>
                
                <?php if (!empty($search)): ?>
                    <a href="view_agreements.php" class="clear-btn">
                        <i class="fas fa-times"></i> ඉවත් කරන්න
                    </a>
                <?php endif; ?>
            </form>
            
            <div class="filter-tags">
                <a href="view_agreements.php" class="filter-tag <?php echo empty($_GET['status']) ? 'active' : ''; ?>">
                    සියලුම
                </a>
                <a href="?status=active" class="filter-tag <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'active' : ''; ?>">
                    ක්‍රියාකාරී
                </a>
                <a href="?status=expired" class="filter-tag <?php echo (isset($_GET['status']) && $_GET['status'] == 'expired') ? 'active' : ''; ?>">
                    කල් ඉකුත් වූ
                </a>
                <a href="?status=upcoming" class="filter-tag <?php echo (isset($_GET['status']) && $_GET['status'] == 'upcoming') ? 'active' : ''; ?>">
                    ඉදිරියේදී අවසන් වන
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-info">
                    <div class="stat-label">සමස්ත ගිවිසුම්</div>
                    <div class="stat-value"><?php echo $total_agreements; ?></div>
                </div>
                <i class="fas fa-file-contract stat-icon"></i>
            </div>
            
            <div class="stat-card monthly">
                <div class="stat-info">
                    <div class="stat-label">මෙම මාසයේ</div>
                    <div class="stat-value">
                        <?php
                        $current_month = date('Y-m');
                        $month_result = $conn->query("SELECT COUNT(*) as count FROM agreements WHERE DATE_FORMAT(agreement_date, '%Y-%m') = '$current_month'");
                        echo $month_result->fetch_assoc()['count'];
                        ?>
                    </div>
                </div>
                <i class="fas fa-calendar-alt stat-icon"></i>
            </div>
            
            <div class="stat-card active">
                <div class="stat-info">
                    <div class="stat-label">ක්‍රියාකාරී</div>
                    <div class="stat-value">
                        <?php
                        $today = date('Y-m-d');
                        $active_result = $conn->query("SELECT COUNT(*) as count FROM agreements WHERE end_date >= '$today'");
                        echo $active_result->fetch_assoc()['count'];
                        ?>
                    </div>
                </div>
                <i class="fas fa-check-circle stat-icon"></i>
            </div>
        </div>

        <!-- Agreements Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="agreements-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>නම</th>
                            <th>ජා.හැ.අ.</th>
                            <th>වාහන අංකය</th>
                            <th>මුදල (රු.)</th>
                            <th>ආරම්භක දිනය</th>
                            <th>අවසන් දිනය</th>
                            <th>තත්වය</th>
                            <th>ක්‍රියා</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $counter = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                // Determine status
                                $today = new DateTime();
                                $end_date = new DateTime($row['end_date']);
                                $status_class = '';
                                $status_text = '';
                                
                                if ($end_date < $today) {
                                    $status_class = 'status-expired';
                                    $status_text = 'කල් ඉකුත් වූ';
                                } elseif ($end_date->diff($today)->days <= 30) {
                                    $status_class = 'status-warning';
                                    $status_text = 'ඉදිරියේදී අවසන් වන';
                                } else {
                                    $status_class = 'status-active';
                                    $status_text = 'ක්‍රියාකාරී';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td style="font-family: monospace;"><?php echo htmlspecialchars($row['nic']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_number'] ?: '-'); ?></td>
                                    <td style="font-weight: 600; color: #059669;">
                                        රු. <?php echo number_format($row['amount_number'], 2); ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($row['agreement_date'])); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($row['end_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="preview.php?id=<?php echo $row['id']; ?>" 
                                               class="action-btn view-btn" 
                                               title="බලන්න">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_agreement.php?id=<?php echo $row['id']; ?>" 
                                               class="action-btn edit-btn" 
                                               title="සංස්කරණය">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="preview.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank"
                                               class="action-btn print-btn" 
                                               title="මුද්‍රණය">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" 
                                                    class="action-btn delete-btn" 
                                                    title="මකන්න">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h3>ගිවිසුම් හමු නොවීය</h3>
                                        <?php if (!empty($search)): ?>
                                            <p>"<?php echo htmlspecialchars($search); ?>" සඳහා ප්‍රතිඵල හමු නොවීය</p>
                                        <?php else: ?>
                                            <p>තවමත් ගිවිසුම් නැත. <a href="index.php" style="color: var(--primary);">පළමු ගිවිසුම එකතු කරන්න</a></p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-angle-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-angle-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

       
    </div>

    <script>
    function confirmDelete(id) {
        if (confirm('ඔබට මෙම ගිවිසුම මකා දැමීමට අවශ්‍යද?\nමෙම ක්‍රියාව ආපසු හැරවිය නොහැක.')) {
            window.location.href = 'delete_agreement.php?id=' + id;
        }
    }

    // Search with enter key
    document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });

    // Filter status handling
    document.addEventListener('DOMContentLoaded', function() {
        // Add active class to current status filter
        const currentStatus = new URLSearchParams(window.location.search).get('status');
        const filterTags = document.querySelectorAll('.filter-tag');
        
        filterTags.forEach(tag => {
            const href = tag.getAttribute('href');
            if (href.includes('status=' + currentStatus) || (!currentStatus && href === 'view_agreements.php')) {
                tag.classList.add('active');
            }
        });
        
        // Table row click for view
        const tableRows = document.querySelectorAll('.agreements-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons
                if (!e.target.closest('.action-buttons')) {
                    const viewLink = this.querySelector('.view-btn');
                    if (viewLink) {
                        window.location.href = viewLink.href;
                    }
                }
            });
            
            // Add hover effect
            row.style.cursor = 'pointer';
        });
    });
    </script>
</body>
</html>

<?php
// Close connections
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>