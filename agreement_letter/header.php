<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ගිවිසුම් කළමනාකරණ පද්ධතිය</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Navigation Styles */
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

    /* Page Title */
    .page-title {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .page-title h1 {
        color: var(--primary-dark);
        font-size: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-title p {
        color: #6b7280;
        margin-top: 5px;
        font-size: 14px;
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
            padding: 15px 10px;
        }
        
        .user-info span {
            display: none;
        }
    }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="logo">
            <i class="fas fa-file-contract"></i>
            <span>ගිවිසුම් පද්ධතිය - දයානන්ද මෝටර්ස්</span>
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

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-title">