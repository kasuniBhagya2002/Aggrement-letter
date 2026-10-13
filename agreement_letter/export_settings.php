<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Get current page for navigation
$current_page = 'export_settings.php';
?>

<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>අපනයන සැකසුම්</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Navigation styles (same as before) */
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

/* ... (Same navigation styles as previous pages) ... */

/* Export Settings Styles */
.export-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.export-options {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.export-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.export-section h3 {
    color: var(--primary-dark);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.format-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.format-card {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.format-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.format-card.selected {
    border-color: var(--primary);
    background: #dbeafe;
}

.format-icon {
    font-size: 36px;
    margin-bottom: 10px;
}

.format-card.pdf .format-icon {
    color: #ef4444;
}

.format-card.excel .format-icon {
    color: #10b981;
}

.format-card.csv .format-icon {
    color: #3b82f6;
}

.format-card h4 {
    color: var(--primary-dark);
    margin-bottom: 5px;
}

.format-card p {
    color: #6b7280;
    font-size: 12px;
}

.filter-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.filter-group {
    margin-bottom: 15px;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #374151;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-family: inherit;
}

.date-range {
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-range input {
    flex: 1;
}

.export-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s;
}

.export-btn:hover {
    background: linear-gradient(135deg, var(--primary-dark), #1e3a8a);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
}

.export-info {
    background: #f0f9ff;
    border: 1px solid #dbeafe;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.export-info h4 {
    color: var(--primary-dark);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.export-info ul {
    margin-left: 20px;
    color: #374151;
}

.export-info li {
    margin-bottom: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .export-container {
        padding: 15px;
    }
    
    .format-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-options {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>
    <!-- Navigation (Same as before) -->
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

    <div class="sidebar">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>මුල් පිටුව</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="create_agreement.php" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>නව ගිවිසුම</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="view_agreements.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    <span>ගිවිසුම් බලන්න</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="search.php" class="nav-link">
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
                <a href="export_settings.php" class="nav-link active">
                    <i class="fas fa-download"></i>
                    <span>අපනයනය</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-download"></i> ගිවිසුම් අපනයනය</h1>
            <p>ගිවිසුම් දත්ත ඔබේ කැමති ආකෘතියකින් අපනයනය කරන්න</p>
        </div>

        <?php if (isset($_SESSION['export_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>දෝෂයක්!</strong>
                    <p><?php echo $_SESSION['export_error']; unset($_SESSION['export_error']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="export-container">
            <form action="export.php" method="GET" class="export-options" id="exportForm">
                <!-- Format Selection -->
                <div class="export-section">
                    <h3><i class="fas fa-file"></i> අපනයන ආකෘතිය තෝරන්න</h3>
                    
                    <div class="format-grid">
                        <div class="format-card pdf selected" onclick="selectFormat('pdf')">
                            <div class="format-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h4>PDF</h4>
                            <p>පරිශීලක හුවමාරුව සඳහා</p>
                            <input type="radio" name="type" value="pdf" checked hidden>
                        </div>
                        
                        <div class="format-card excel" onclick="selectFormat('excel')">
                            <div class="format-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <h4>Excel</h4>
                            <p>දත්ත විශ්ලේෂණය සඳහා</p>
                            <input type="radio" name="type" value="excel" hidden>
                        </div>
                        
                        <div class="format-card csv" onclick="selectFormat('csv')">
                            <div class="format-icon">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            <h4>CSV</h4>
                            <p>දත්ත ආයාත කිරීම සඳහා</p>
                            <input type="radio" name="type" value="csv" hidden>
                        </div>
                    </div>
                </div>

                <!-- Filter Options -->
                <div class="export-section">
                    <h3><i class="fas fa-filter"></i> පෙරහන් යොදන්න</h3>
                    
                    <div class="filter-options">
                        <div class="filter-group">
                            <label for="status">තත්වය අනුව</label>
                            <select id="status" name="status">
                                <option value="">සියලුම</option>
                                <option value="active">ක්‍රියාකාරී</option>
                                <option value="expired">කල් ඉකුත් වූ</option>
                                <option value="upcoming">ඉදිරියේදී අවසන් වන</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">දින පරාසය</label>
                            <div class="date-range">
                                <input type="date" id="date_from" name="date_from" placeholder="සිට">
                                <span>සිට</span>
                                <input type="date" id="date_to" name="date_to" placeholder="දක්වා">
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label for="amount_from">මුදල් පරාසය</label>
                            <div class="date-range">
                                <input type="number" id="amount_from" name="amount_from" placeholder="සිට" step="0.01">
                                <span>සිට</span>
                                <input type="number" id="amount_to" name="amount_to" placeholder="දක්වා" step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Button -->
                <button type="submit" class="export-btn">
                    <i class="fas fa-download"></i>
                    අපනයනය කරන්න
                </button>
            </form>

            <div class="export-info">
                <h4><i class="fas fa-info-circle"></i> අපනයනය පිළිබඳ තොරතුරු</h4>
                <ul>
                    <li><strong>PDF:</strong> මුද්‍රණය කිරීම සහ බෙදාගැනීම සඳහා සුදුසුය</li>
                    <li><strong>Excel:</strong> දත්ත විශ්ලේෂණය සහ වගු නිර්මාණය සඳහා</li>
                    <li><strong>CSV:</strong> වෙනත් යෙදුම් වලට දත්ත ආයාත කිරීම සඳහා</li>
                    <li>අපනයනයේ දී තෝරාගත් පෙරහන් යොදනු ලැබේ</li>
                    <li>අපනයන ගොනුව "agreements_YYYY-MM-DD_HH-MM-SS" ආකෘතියෙන් නම් කරනු ලැබේ</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    // Format selection
    function selectFormat(format) {
        // Remove selected class from all cards
        document.querySelectorAll('.format-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to clicked card
        document.querySelector(`.format-card.${format}`).classList.add('selected');
        
        // Update hidden radio button
        document.querySelector(`input[value="${format}"]`).checked = true;
    }

    // Set default dates
    document.addEventListener('DOMContentLoaded', function() {
        // Set date_to to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date_to').value = today;
        
        // Set date_from to 30 days ago
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        document.getElementById('date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
        
        // Form validation
        const form = document.getElementById('exportForm');
        form.addEventListener('submit', function(e) {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (dateFrom && dateTo && dateFrom > dateTo) {
                e.preventDefault();
                alert('ආරම්භක දිනය අවසන් දිනයට පසුව විය නොහැක.');
                return false;
            }
            
            const amountFrom = parseFloat(document.getElementById('amount_from').value);
            const amountTo = parseFloat(document.getElementById('amount_to').value);
            
            if (amountFrom && amountTo && amountFrom > amountTo) {
                e.preventDefault();
                alert('අවම මුදල උපරිම මුදලට වඩා වැඩි විය නොහැක.');
                return false;
            }
            
            // Show loading indicator
            const submitBtn = form.querySelector('.export-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> අපනයනය කරමින්...';
            submitBtn.disabled = true;
        });
    });

    // Auto-calculate amount range
    function setAmountRange() {
        const minAmount = 1000;
        const maxAmount = 1000000;
        document.getElementById('amount_from').value = minAmount;
        document.getElementById('amount_to').value = maxAmount;
    }

    // Quick export buttons
    function quickExport(type) {
        window.location.href = `export.php?type=${type}`;
    }
    </script>
</body>
</html>