<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔐 Start session tracking safely at the absolute beginning 
session_start();

// 🚀 CACHE-BUSTING BLOCK: Forces the browser to sync live database records on every single loop
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // A historical date to guarantee immediate expiration

// 🔑 Define your two entry gate passwords here
$ADMIN_PASSWORD = "nit202a";  
$GUEST_PASSWORD = "nit202g";  


// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: dashboard.php");
    exit();
}

// Check if a password was submitted at the main gate
if (isset($_POST['dashboard_access_password'])) {
    $entered_password = $_POST['dashboard_access_password'];

    if ($entered_password === $ADMIN_PASSWORD) {
        $_SESSION['dashboard_role'] = 'admin';
    } elseif ($entered_password === $GUEST_PASSWORD) {
        $_SESSION['dashboard_role'] = 'guest';
    } else {
        $login_error = "Incorrect password!";
    }
}

// 🛡️ MAIN GATE KEEPER: Show login form if not authenticated
if (!isset($_SESSION['dashboard_role'])) {
    ?>
    <!DOCTYPE html>
    <html lang="sw">
    <head>
        <meta charset="UTF-8">
        <title>Uthibitisho wa Nywila (Dashboard Access)</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="background-color: #f1f5f9; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0;">
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 360px; text-align: center;">
            <h2 style="color: #1e3c72; margin-top: 0; font-size: 20px;">Enter Password</h2>
            <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Tafadhali ingiza password ili kufikia mfumo.</p>
            
            <?php if (isset($login_error)): ?>
                <div style="color: #e74c3c; font-size: 13px; font-weight: bold; margin-bottom: 15px;"><?php echo $login_error; ?></div>
            <?php endif; ?>

            <form action="dashboard.php" method="POST">
                <input type="password" name="dashboard_access_password" placeholder="Nywila..." style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; margin-bottom: 15px; text-align: center;" required autocomplete="off">
                <button type="submit" style="background-color: #1e3c72; color: white; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 14px;">
                    Unlock
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit(); // Stops dashboard loading if password is not entered yet
}

// 🛡️ BACKEND SECURITY: Strict block if a guest bypasses frontend fields to attempt an upload
if (isset($_POST['submit_upload']) && $_SESSION['dashboard_role'] !== 'admin') {
    http_response_code(403);
    die("Kosa: Huna ruhusa ya kupakia vocha. (Access Denied: Read-only guest mode active.)");
}

// 1. Establish database connection using your dynamic Railway variables
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 🌍 TIMEZONE SYNCHRONIZATION
date_default_timezone_set('Africa/Dar_es_Salaam');
$conn->query("SET time_zone = '+03:00'");
// =========================================================================
// 2. DYNAMIC BULK UPLOADER ENGINE PARSER
// =========================================================================
$upload_message = "";
$upload_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_upload']) && isset($_FILES['voucher_file'])) {
    $price_tier = intval($_POST['upload_price_tier']);
    $file_info = $_FILES['voucher_file'];
    
    if ($file_info['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($file_info['tmp_name']);
        $raw_lines = preg_split('/\r\n|\r|\n/', $file_content);
        
        $inserted_count = 0;
        $duplicate_count = 0;
        
        $check_stmt = $conn->prepare("SELECT COUNT(*) AS exists_count FROM wifi_vouchers WHERE voucher_code = ?");
        $insert_stmt = $conn->prepare("INSERT INTO wifi_vouchers (voucher_code, price_tier, status) VALUES (?, ?, 'AVAILABLE')");
        
        foreach ($raw_lines as $line) {
            $clean_code = preg_replace('/[^a-zA-Z0-9]/', '', trim($line));
            if (!empty($clean_code)) {
                $check_stmt->bind_param("s", $clean_code);
                $check_stmt->execute();
                $exists_res = $check_stmt->get_result()->fetch_assoc();
                
                if ($exists_res['exists_count'] == 0) {
                    $insert_stmt->bind_param("si", $clean_code, $price_tier);
                    $insert_stmt->execute();
                    $inserted_count++;
                } else {
                    $duplicate_count++;
                }
            }
        }
        $check_stmt->close();
        $insert_stmt->close();
        
        $upload_success = true;
        $upload_message = "✓ Imekamilika! Vocha mpya <b>$inserted_count</b> zimepakiwa kwa bei ya Tsh " . number_format($price_tier) . ".";
        if ($duplicate_count > 0) {
            $upload_message .= " (Vocha $duplicate_count zilikataliwa kwa sababu tayari zipo kwenye mfumo).";
        }
    } else {
        $upload_message = "✕ Hitilafu: Imeshindwa kusoma faili lililopakiwa. Tafadhali jaribu tena.";
    }
}

// Fetch remaining stock broken down per specific price tier batch
$tier_stock_query = "SELECT price_tier, COUNT(*) AS tier_count FROM wifi_vouchers WHERE status = 'AVAILABLE' GROUP BY price_tier ORDER BY price_tier ASC";
$tier_stock_result = $conn->query($tier_stock_query);

$tier_stock_data = [];
if ($tier_stock_result) {
    while ($tier_row = $tier_stock_result->fetch_assoc()) {
        $tier_stock_data[] = $tier_row;
    }
}

// =========================================================================
// 3. CUSTOM DATE RANGE REVENUE CALCULATOR
// =========================================================================

// Fallback to the 1st of the current month if from_date isn't specified yet
$from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date   = isset($_GET['to_date'])   && !empty($_GET['to_date'])   ? $_GET['to_date']   : date('Y-m-d');

// Escape values safely to avoid SQL Injection issues
$safe_from = $conn->real_escape_string($from_date);
$safe_to   = $conn->real_escape_string($to_date);

// Build strict database date-range boundaries (inclusive of selected days)
$period_condition = "AND DATE(purchased_at) BETWEEN '$safe_from' AND '$safe_to'";

// 🗓️ TODAY'S METRICS (Stays static for instant comparisons)
$today_earnings_res = $conn->query("SELECT SUM(price_tier) AS total FROM wifi_vouchers WHERE status = 'SUCCESS' AND DATE(purchased_at) = CURDATE()");
$today_earnings = $today_earnings_res ? ($today_earnings_res->fetch_assoc()['total'] ?: 0) : 0;

$today_count_res = $conn->query("SELECT COUNT(*) AS total FROM wifi_vouchers WHERE status = 'SUCCESS' AND DATE(purchased_at) = CURDATE()");
$today_vouchers_sold = $today_count_res ? ($today_count_res->fetch_assoc()['total'] ?: 0) : 0;

// 📊 DYNAMIC REVENUE METRICS CALCULATOR BASED ON THE CHOSEN "FROM / TO" RANGE
$earnings_query = "SELECT SUM(price_tier) AS total FROM wifi_vouchers WHERE status = 'SUCCESS' $period_condition";
$earnings_result = $conn->query($earnings_query);
$total_earnings = $earnings_result ? ($earnings_result->fetch_assoc()['total'] ?: 0) : 0;

$count_query = "SELECT COUNT(*) AS total FROM wifi_vouchers WHERE status = 'SUCCESS' $period_condition";
$count_result = $conn->query($count_query);
$vouchers_sold = $count_result ? ($count_result->fetch_assoc()['total'] ?: 0) : 0;

// 📦 STOCK AVAILABLE (Keeps current live warehouse total balance)
$stock_result = $conn->query("SELECT COUNT(*) AS total FROM wifi_vouchers WHERE status = 'AVAILABLE'");
$remaining_stock = $stock_result ? ($stock_result->fetch_assoc()['total'] ?: 0) : 0;

// 📋 LOG ENTRIES FETCH FOR LATEST 50 TRANSACTIONS
$log_query = "SELECT id, voucher_code, price_tier, status, assigned_phone, mac_address, transaction_id, azampay_transaction_id, purchased_at FROM wifi_vouchers WHERE status IN ('SUCCESS', 'ASSIGNED') ORDER BY purchased_at DESC LIMIT 50";
$log_result = $conn->query($log_query);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 🔄 LIVE OVER-THE-AIR REFRESH SYNC CONTROL (Runs every 10 seconds) -->
    <meta http-equiv="refresh" content="10;url=dashboard.php<?php 
        // Preserves your calendar selection inputs automatically across refresh cycles
        $url_params = [];
        if (isset($_GET['from_date'])) $url_params['from_date'] = $_GET['from_date'];
        if (isset($_GET['to_date'])) $url_params['to_date'] = $_GET['to_date'];
        if (!empty($url_params)) echo '?' . http_build_query($url_params);
    ?>">
    
    <!-- Explicit HTML Level Cache Invalidation Matrix -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title><a href="https://www.tanconnect.co.tz/fake_callback.php" class="btn-portal btn-buy">RESET </a>TANConnect - Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .wrapper { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #1e3c72; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top: 20px; }
        
        /* 🚀 BULLETPROOF LAYOUT FIX RULES FOR STOCK HOVER OVERLAY OVERRIDE */
        .hover-stock-card { overflow: visible !important; position: relative; }
        .hover-menu-panel { display: none; }
        .hover-stock-card:hover .hover-menu-panel { display: block !important; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Header Block with Dynamic Status Messaging Alert Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="logo.png" style="max-width: 160px; height: auto; object-fit: contain; margin-bottom: 1px;">
            <h2 style="margin: 0; border: none; padding: 0;">Admin Sales Dashboard</h2>
        </div>
        <!-- 🚪 QUICK LOGOUT INTERFACE GATE BUTTON -->
        <a href="dashboard.php?action=logout" style="background-color: #f1f5f9; color: #e74c3c; border: 1px solid #cbd5e1; text-decoration: none; padding: 6px 14px; font-weight: bold; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#ffebee'" onmouseout="this.style.backgroundColor='#f1f5f9'">
            🚪 Logout
        </a>
    </div>

    <?php if (!empty($upload_message)): ?>
        <div style="background-color: <?php echo $upload_success ? '#e8f8f0' : '#fde8e8'; ?>; border: 1px solid <?php echo $upload_success ? '#27ae60' : '#e53e3e'; ?>; color: <?php echo $upload_success ? '#27ae60' : '#e53e3e'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: bold;">
            <?php echo $upload_message; ?>
        </div>
    <?php endif; ?>

    <!-- 📅 DYNAMIC DATE RANGE FILTER CONTROL PANEL -->
    <div style="background: white; padding: 15px 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; box-sizing: border-box;">
        <div style="color: #1e3c72; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
            🔍 Observation Period.
        </div>
        
        <form action="dashboard.php" method="GET" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12px; font-weight: bold; color: #64748b;">From:</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #334155; outline: none; background-color: #f8fafc;">
            </div>
            
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12px; font-weight: bold; color: #64748b;">To:</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #334155; outline: none; background-color: #f8fafc;">
            </div>
            
            <button type="submit" style="background-color: #1e3c72; color: white; border: none; padding: 7px 16px; font-weight: bold; font-size: 13px; border-radius: 6px; cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#0d234d'" onmouseout="this.style.backgroundColor='#1e3c72'">
                Apply Filter
            </button>
            
            <?php if (isset($_GET['from_date']) || isset($_GET['to_date'])): ?>
                <a href="dashboard.php" style="font-size: 12px; color: #e74c3c; font-weight: bold; text-decoration: none; padding-left: 5px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <!-- 📊 THE METRICS GRID DISPLAYER -->
    <div class="metrics-grid">
        
        <!-- Card 1: Today's Collection -->
        <div style="background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 5px solid #2e7d32; box-sizing: border-box;">
            <span style="font-size: 11px; font-weight: bold; color: #2e7d32; text-transform: uppercase; display: block; margin-bottom: 5px;">TODAY's COLLECTION</span>
            <h3 style="margin: 0; font-size: 24px; color: #1b5e20;">Tsh <?php echo number_format($today_earnings); ?></h3>
            <small style="color: #4caf50; font-size: 11px; display: block; margin-top: 5px;">Vocha zilizouzwa: <?php echo number_format($today_vouchers_sold); ?></small>
        </div>

        <!-- Card 2: Filtered Observation Period Total -->
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 5px solid #1565c0; box-sizing: border-box;">
            <span style="font-size: 11px; font-weight: bold; color: #1565c0; text-transform: uppercase; display: block; margin-bottom: 5px;">TOTAL COLLECTION (FILTERED)</span>
            <h3 style="margin: 0; font-size: 24px; color: #0d47a1;">Tsh <?php echo number_format($total_earnings); ?></h3>
            <small style="color: #1976d2; font-size: 11px; display: block; margin-top: 5px;">
                Kipindi: <b><?php echo date('d M Y', strtotime($from_date)); ?></b> hadi <b><?php echo date('d M Y', strtotime($to_date)); ?></b> (Vocha: <?php echo number_format($vouchers_sold); ?>)
            </small>
        </div>

        <!-- Card 3: Remaining Stock with Layout-Safe CSS Hover Breakdown Panel Overlay -->
        <div class="hover-stock-card" style="background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 5px solid #ef6c00; box-sizing: border-box; display: flex; flex-direction: column; justify-content: flex-start;">
            <div>
                <span style="font-size: 11px; font-weight: bold; color: #ef6c00; text-transform: uppercase; display: block; margin-bottom: 5px;">VOUCHER STOCK</span>
                <h3 style="margin: 0; font-size: 24px; color: #e65100; font-weight: 700;"><?php echo number_format($remaining_stock); ?></h3>
                <small style="color: #f57c00; font-size: 11px; display: block; margin-top: 5px;">Tayari kutumika na wateja</small>
            </div>
            
            <!-- Toggle/Hover Activation Row -->
            <div style="margin-top: 15px;">
                <div style="background-color: #ef6c00; color: white; border: none; padding: 6px 12px; font-size: 11px; font-weight: bold; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; cursor: help; transition: background 0.2s;">
                    ⚙️ Hover for Breakdown
                </div>
            </div>

            <!-- CSS Hover Menu Box Popover: Overlaps elements below safely without pushdowns -->
            <div class="hover-menu-panel" style="position: absolute; top: 100%; left: 0; width: 100%; background: white; border: 1px solid #ffd180; box-shadow: 0 4px 15px rgba(0,0,0,0.12); border-radius: 8px; padding: 15px; margin-top: 8px; z-index: 99999; box-sizing: border-box;">
                <h4 style="margin: 0 0 10px 0; font-size: 12px; color: #ef6c00; border-bottom: 1px dashed #ffd180; padding-bottom: 5px;">Mchanganuo wa Vifurushi (Stock per Tier)</h4>
                <?php if (!empty($tier_stock_data)): ?>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <?php foreach ($tier_stock_data as $tier): ?>
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #475569;">
                                <span style="font-weight: 600;">Tsh <?php echo number_format($tier['price_tier']); ?>:</span>
                                <span style="color: #e65100; font-weight: 700;"><?php echo number_format($tier['tier_count']); ?> pcs</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <span style="color: #64748b; font-size: 11px; font-style: italic;">Hakuna vocha zilizobaki</span>
                <?php endif; ?>
            </div>
        </div>

    </div> <!-- Close metrics-grid -->
    <!-- 🛡️ SECURITY LAYER ROLE CHECK: ONLY SHOW UPLOADER MODULE FOR FULL WRITE-ACCESS ADMIN -->
    <?php if (isset($_SESSION['dashboard_role']) && $_SESSION['dashboard_role'] === 'admin'): ?>

        <!-- BULK VOUCHER STOCK IMPORT ENGINE WITH INTEGRATED EXCEL EXPORTER -->
        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-sizing: border-box; margin-top: 25px;">
            <h3 style="margin-top: 0; color: #1e3c72; font-size: 15px; text-align: center;">📥 Ongeza Vocha kwa Mkupuo (Bulk Voucher Upload/ Download)</h3>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 15px; margin-top: 0;">Faili la maandishi (.txt au .csv) ambalo kila mstari una namba moja ya vocha.</p>
            
            <form action="dashboard.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; justify-content: space-between; width: 100%;">
                
                <!-- Left Side: Input Form Controls Cluster -->
                <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; flex: 1;">
                    <div style="display: flex; flex-direction: column;">
                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569;">Kifurushi (Price Tier):</label>
                        <select name="upload_price_tier" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                            <option value="500">500</option>
                            <option value="1000">1,000</option>
                            <option value="2000">2,000</option>
                            <option value="4000">4,000</option>
                            <option value="5000">5,000</option>
                            <option value="7000">7,000</option>
                            <option value="9000">9,000</option>
                            <option value="10000">10,000</option>
                            <option value="20000">20,000</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; flex-direction: column;">
                        <label style="font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569;">Chagua Faili (.txt / .csv):</label>
                        <input type="file" name="voucher_file" accept=".txt,.csv" style="font-size: 13px;" required>
                    </div>

                    <!-- 🚀 UPLOAD BUTTON RIGHT NEXT TO INPUTS -->
                    <button type="submit" name="submit_upload" style="background-color: #1e3c72; color: white; border: none; padding: 11px 20px; font-weight: bold; font-size: 13px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                        🚀 Upload
                    </button>
                </div>

                <!-- Right Side: Integrated Download Action Block -->
                <div style="display: flex; align-items: center; justify-content: flex-end; white-space: nowrap;">
                    <!-- 🟢 DOWNLOAD EXCEL REPORT ACTION LINK BUTTON -->
                    <a href="export_sales.php" style="background-color: #27ae60; color: white; text-decoration: none; padding: 11px 20px; border-radius: 6px; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1e7e34'" onmouseout="this.style.backgroundColor='#27ae60'">
                        📥 Download
                    </a>
                </div>
                
            </form>
        </div>

    <?php else: ?>
        <!-- 🟢 GUEST ONLY ACCESS LINK DISPLAY: Render download row independently since uploader container is hidden -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 25px; margin-top: 15px;">
            <a href="export_sales.php" style="background-color: #27ae60; color: white; text-decoration: none; padding: 11px 20px; border-radius: 6px; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.backgroundColor='#1e7e34'" onmouseout="this.style.backgroundColor='#27ae60'">
                📥 Pakua Ripoti (Excel CSV)                                                            
            </a>
        </div>
    <?php endif; ?>

    <!-- 📊 LIVE TRANSACTION FILTER SEARCH MATRIX HEADER BLOCK -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-top: 35px; margin-bottom: 10px; gap: 15px;">
        <h3 style="margin: 0; color: #1e3c72; font-size: 16px;">📑 Live Transaction Audit Logs (Latest 50 Entries)</h3>
        
        <!-- Positioned Real-time Search Input Box -->
        <div style="position: relative; max-width: 320px; width: 100%;">
            <input type="text" id="dashboardSearchBox" onkeyup="filterAdminTransactionTable()" placeholder="Tafuta kwa namba ya simu au PIN..." style="width: 100%; padding: 10px 12px 10px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#1e3c72'" onblur="this.style.borderColor='#cbd5e1'">
            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;">🔍</span>
        </div>
    </div>

    <!-- 📊 THE MAIN TRANSACTIONAL AUDIT DATA DISPLAY TABLE -->
    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; background: white; margin-bottom: 30px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                    <th style="padding: 12px 15px; text-align: center; font-weight: bold;">S/N</th>
                    <th style="padding: 12px 15px; font-weight: bold;">Voucher Code</th>
                    <th style="padding: 12px 15px; font-weight: bold;">Price Tier (Tsh)</th>
                    <th style="padding: 12px 15px; font-weight: bold;">Status</th>
                    <th style="padding: 12px 15px; font-weight: bold;">Assigned Phone</th>
                    <th style="padding: 12px 15px; font-weight: bold;">MAC Address</th>
                    <th style="padding: 12px 15px; font-weight: bold;">Muda wa Malipo (EAT Time)</th>
                    <th style="padding: 12px 15px; font-weight: bold;">NIT Transaction ID</th>
                    <th style="padding: 12px 15px; font-weight: bold;">AzamPay Transaction ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($log_result && $log_result->num_rows > 0): ?>
                    <?php 
                    $sn_counter = $log_result->num_rows; 
                    while ($row = $log_result->fetch_assoc()): 
                        $rawMac = preg_replace('/[^a-zA-Z0-9]/', '', $row['mac_address']);
                        $displayMac = strlen($rawMac) === 12 ? implode(':', str_split($rawMac, 2)) : $row['mac_address'];
                    ?>
                        <tr style="border-bottom: 1px solid #e2e8f0;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="font-weight: bold; color: #475569; font-family: monospace; text-align: center; padding: 12px 15px;">
                                <?php echo $sn_counter--; ?>
                            </td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 14px; padding: 12px 15px; color: #1e293b;">
                                <?php echo htmlspecialchars($row['voucher_code']); ?>
                            </td>
                            <td style="padding: 12px 15px; font-weight: 500;">
                                Tsh <?php echo number_format($row['price_tier']); ?>
                            </td>
                            <td style="padding: 12px 15px;">
                                <?php if ($row['status'] === 'SUCCESS'): ?>
                                    <span style="background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">SUCCESS</span>
                                <?php else: ?>
                                    <span style="background-color: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">ASSIGNED</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 15px; font-family: monospace; color: #334155;">
                                <?php echo !empty($row['assigned_phone']) ? htmlspecialchars($row['assigned_phone']) : '-'; ?>
                            </td>
                            <td style="padding: 12px 15px; font-family: monospace; color: #64748b;">
                                <?php echo !empty($rawMac) ? htmlspecialchars(strtoupper($displayMac)) : '-'; ?>
                            </td>
                            <td style="font-family: monospace; color: #2c3e50; font-weight: 500; padding: 12px 15px;">
                                <?php echo !empty($row['purchased_at']) ? date("d-m-Y H:i:s", strtotime($row['purchased_at'])) : '-'; ?>
                            </td>
                            <td style="color: #7f8c8d; font-size: 12px; font-family: monospace; padding: 12px 15px;">
                                <?php echo htmlspecialchars($row['transaction_id']); ?>
                            </td>
                            <td style="color: #27ae60; font-weight: bold; font-family: monospace; font-size: 13px; padding: 12px 15px;">
                                <?php echo !empty($row['azampay_transaction_id']) ? htmlspecialchars($row['azampay_transaction_id']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #7f8c8d; padding: 30px; font-style: italic;">
                            Hakuna kumbukumbu za malipo bado. (No transaction history logs generated yet.)
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div> <!-- Close wrapper canvas container box -->

<script type="text/javascript">
    // 🚀 PERSISTENT SEARCH MEMORY FILTER ENGINE
    function filterAdminTransactionTable() {
        var input = document.getElementById("dashboardSearchBox");
        if (!input) return;
        
        var rawInput = input.value.trim();
        var filter = rawInput.toUpperCase();
        
        // Save current entry to session memory storage to prevent wipeouts during meta refreshes
        sessionStorage.setItem("adminSearchKeyword", rawInput);
        
        var normalizedFilter = filter;
        if (rawInput.startsWith('0')) {
            normalizedFilter = '255' + filter.substring(1);
        }

        var table = document.querySelector("table:not(.stock-table)");
        if (!table) return;
        
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var tdCells = tr[i].getElementsByTagName("td");
            if (tdCells.length > 4) {
                var voucherText = (tdCells[1].textContent || tdCells[1].innerText).trim();
                var phoneText   = (tdCells[4].textContent || tdCells[4].innerText).trim();
                
                var upperVoucher = voucherText.toUpperCase();
                var upperPhone   = phoneText.toUpperCase();
                
                if (upperVoucher.indexOf(filter) > -1 || 
                    upperPhone.indexOf(filter) > -1 || 
                    upperPhone.indexOf(normalizedFilter) > -1) {
                    tr[i].style.display = ""; 
                } else {
                    tr[i].style.display = "none"; 
                }
            }       
        }
    }

    // 🔄 RE-APPLY FILTERS ON LOAD & CALL BACKGROUND OPTIMIZER
    window.addEventListener('DOMContentLoaded', function() {
        var savedKeyword = sessionStorage.getItem("adminSearchKeyword");
        if (savedKeyword) {
            var searchBox = document.getElementById("dashboardSearchBox");
            if (searchBox) {
                searchBox.value = savedKeyword;
                filterAdminTransactionTable(); 
            }
        }

        // Silent backend engine self-healing script call
        fetch('cron_cleanup.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.vouchers_recovered > 0) {
                    console.log("TANConnect Optimizer: Cleaned database and recovered " + data.vouchers_recovered + " abandoned vouchers!");
                }
            })
            .catch(err => console.log("System optimizer running..."));
    });
</script>

</body>
</html>
<?php 
// 🔒 Close live channel streams safely
if (isset($conn) && $conn instanceof mysqli) { 
    $conn->close(); 
}
exit();
?>
