<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic HTTP Authentication Layer for Administrator Access Protection
$admin_user = "admin";
$admin_pass = "nit2026!"; // <-- CHANGE THIS PASSWORD TO SECURE YOUR DASHBOARD!

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $admin_user || $_SERVER['PHP_AUTH_PW'] !== $admin_pass) {
    header('WWW-Authenticate: Basic realm="TANConnect Wi-Fi Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Utambuzi unahitajika kufungua ukurasa huu.';
    exit;
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

// 🌍 TIMEZONE SYNCHRONIZATION: Enforce explicit East African Time schema context baseline
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

// 2. Fetch Aggregated Sales Summary Metrics
$earnings_result = $conn->query("SELECT SUM(price_tier) AS total FROM wifi_vouchers WHERE status = 'SUCCESS'");
$total_earnings = $earnings_result ? ($earnings_result->fetch_assoc()['total'] ?: 0) : 0;

$count_result = $conn->query("SELECT COUNT(*) AS total FROM wifi_vouchers WHERE status = 'SUCCESS'");
$vouchers_sold = $count_result ? $count_result->fetch_assoc()['total'] : 0;


$stock_result = $conn->query("SELECT COUNT(*) AS total FROM wifi_vouchers WHERE status = 'AVAILABLE'");
$remaining_stock = $stock_result ? $stock_result->fetch_assoc()['total'] : 0;

// 3. PRODUCTION UPGRADE: Selecting your branded NITW internal IDs alongside your renamed azampesa_transaction_id column cells!
$log_query = "SELECT id, voucher_code, price_tier, status, assigned_phone, mac_address, transaction_id, azampay_transaction_id, purchased_at FROM wifi_vouchers WHERE status IN ('SUCCESS', 'ASSIGNED') ORDER BY purchased_at DESC LIMIT 50";
$log_result = $conn->query($log_query);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>TANConnect - Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .wrapper { max-width: 1200px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #1e3c72; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top: 20px; }
        .metric-card { padding: 20px; border-radius: 8px; color: white; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.1s ease; }
        .card-green { background: #27ae60; }
        .card-blue { background: #2980b9; }
        .card-orange { background: #e67e22; }
        .metric-val { font-size: 28px; display: block; margin-top: 5px; font-family: monospace; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 12px 10px; text-align: left; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #e8f8f0; color: #27ae60; border: 1px solid #27ae60; }
        .badge-assigned { background: #eaf2f8; color: #2980b9; border: 1px solid #2980b9; }
        .mac-text { font-family: monospace; letter-spacing: 0.5px; color: #555; }

        /* Solid White Opaque Stock Detail Dropdown Rules Window */
        .stock-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5) !important;
            justify-content: center;
            align-items: center;
            z-index: 9999 !important;
        }
        .stock-modal-content {
            background-color: #ffffff !important;
            background: #ffffff !important;
            padding: 25px;
            border: 1px solid #cbd5e1;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            position: relative;
            box-sizing: border-box;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15), 0 10px 10px -5px rgba(0,0,0,0.1) !important;
            color: #333 !important;
            text-align: center;
        }
        .stock-close { position: absolute; top: 12px; right: 16px; font-size: 24px; font-weight: bold; cursor: pointer; color: #94a3b8; line-height: 1; }
        .stock-close:hover { color: #334155; }
        .stock-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .stock-table th, .stock-table td { padding: 10px 12px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 14px; text-align: left; }
        .stock-table th { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; font-size: 12px; color: #475569; font-weight: bold; }
    </style>

    <script type="text/javascript">
        function openStockSummaryPopup() { document.getElementById('stockSummaryModal').style.display = 'flex'; }
        function closeStockSummaryPopup() { document.getElementById('stockSummaryModal').style.display = 'none'; }
        window.onclick = function(event) {
            var modal = document.getElementById('stockSummaryModal');
            if (event.target == modal) { modal.style.display = 'none'; }
        }
    </script>
</head>
<body>

<div class="wrapper">
    <img src="logo.png" style="max-width: 160px; height: auto; object-fit: contain; margin-bottom: 1px;"><h2>Admin Sales Dashboard</h2>
    <?php if (!empty($upload_message)): ?>
        <div style="background-color: <?php echo $upload_success ? '#e8f8f0' : '#fde8e8'; ?>; border: 1px solid <?php echo $upload_success ? '#27ae60' : '#27ae60'; ?>; color: <?php echo $upload_success ? '#27ae60' : '#e53e3e'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: bold;">
            <?php echo $upload_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="metrics-grid">
        <div class="metric-card card-green">
            Jumla ya Mapato (Total Earnings)
            <span class="metric-val">Tsh <?php echo number_format($total_earnings); ?></span>
        </div>
        <div class="metric-card card-blue">
            Vocha Zilizouzwa (Vouchers Sold)
            <span class="metric-val"><?php echo number_format($vouchers_sold); ?> pcs</span>
        </div>
        <div class="metric-card card-orange" onclick="openStockSummaryPopup()" style="cursor: pointer;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1.0)'">
            Vocha Zilizobaki (Voucher Stock)
            <span class="metric-val"><?php echo number_format($remaining_stock); ?> pcs</span>
            <span style="font-size: 9px; display: block; margin-top: 5px; color: #ffe0b2; letter-spacing: 0.5px;">📋 GUSA HAPA KUONA BATCH DETAILS</span>
        </div>
    </div>

    <!-- BULK VOUCHER STOCK IMPORT ENGINE -->
    <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 8px; margin-bottom: 25px; box-sizing: border-box;">
        <h3 style="margin-top: 0; color: #1e3c72; font-size: 15px;">📥 Ongeza Vocha kwa Mkupuo (Bulk Voucher Uploader)</h3>
        <p style="font-size: 12px; color: #64748b; margin-bottom: 15px; margin-top: 0;">Faili la maandishi (.txt au .csv) ambalo kila mstari una namba moja ya vocha.</p>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569;">Kifurushi (Price Tier):</label>
                <select name="upload_price_tier" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;" required>
                    <option value="500">Tsh 500</option>
                    <option value="1000">Tsh 1,000</option>
                    <option value="2000">Tsh 2,000</option>
                    <option value="2000">Tsh 4,000</option>
                    <option value="5000">Tsh 5,000</option>
                    <option value="2000">Tsh 7,000</option>
                    <option value="2000">Tsh 9,000</option>
                    <option value="10000">Tsh 10,000</option>
                    <option value="20000">Tsh 20,000</option>
                </select>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 11px; font-weight: bold; margin-bottom: 4px; color: #475569;">Chagua Faili (.txt / .csv):</label>
                <input type="file" name="voucher_file" accept=".txt,.csv" style="font-size: 13px;" required>
            </div>
            <button type="submit" name="submit_upload" style="background-color: #1e3c72; color: white; border: none; padding: 11px 20px; font-weight: bold; font-size: 13px; border-radius: 6px; cursor: pointer; margin-top: 18px;">🚀 Pakia Vocha (Upload)</button>
  </form>
</div> <!-- Closes the background bulk uploader container card box cleanly -->

<!-- 📊 LIVE TRANSACTION FILTER SEARCH MATRIX WITH INTEGRATED EXCEL EXPORTER -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-top: 35px; margin-bottom: 15px; gap: 15px; width: 100%; box-sizing: border-box;">

    
    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <!-- 📥 INTEGRATED ONE-CLICK EXCEL SPREADSHEET DOWNLOAD ENGINE -->
        <a href="export_sales.php" style="background-color: #27ae60; color: white; text-decoration: none; padding: 10px 16px; border-radius: 6px; font-size: 13px; font-weight: bold; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: background-color 0.2s; border: none;" onmouseover="this.style.backgroundColor='#1e7e34'" onmouseout="this.style.backgroundColor='#27ae60'">
            📥 Pakua Ripoti (Excel CSV)
        </a>

        <!-- The Properly Positioned Search Input Box -->
    
    </div>
</div>

<!-- DYNAMIC BATCH STOCK SUMMARY POPUP MODAL CONTAINER -->

    <div id="stockSummaryModal" class="stock-modal" onclick="closeStockSummaryPopup()">
        <div class="stock-modal-content" onclick="event.stopPropagation()">
            <span class="stock-close" onclick="closeStockSummaryPopup()">&times;</span>
            <h3 style="margin-top: 0; color: #e67e22; font-size: 16px; border-bottom: 2px solid #eee; padding-bottom: 8px;">📊 Muhtasari wa Vocha (Stock Summary)</h3>
            <table class="stock-table">
                <thead><tr><th>Price Tier</th><th>Zilizobaki (Stock)</th></tr></thead>
                <tbody>
                    <?php if (!empty($tier_stock_data)): ?>
                        <?php foreach ($tier_stock_data as $tier): 
                            $isLowStock = ($tier['tier_count'] < 50);
                            $textStyle = $isLowStock ? 'color: #d9534f; font-weight: 800;' : 'color: #1e3c72; font-weight: bold;';
                            $badge = $isLowStock ? ' <span style="font-size: 8px; background-color: #fde8e8; color: #e53e3e; padding: 2px 6px; border-radius: 4px; border: 1px solid #fed7d7;">⚠️ LOW</span>' : '';
                        ?>
                            <tr style="<?php echo $isLowStock ? 'background-color: #fffaf0;' : ''; ?>">
                                <td style="<?php echo $textStyle; ?>">Tsh <?php echo number_format($tier['price_tier']); ?><?php echo $badge; ?></td>
                                <td style="text-align: right; font-weight: bold; color: <?php echo $isLowStock ? '#d9534f' : '#333'; ?>;"><?php echo number_format($tier['tier_count']); ?> pcs</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" style="text-align: center; color: #7f8c8d;">Hakuna vocha zilizobaki.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

       <!-- 🔍 LIVE TRANSACTION FILTER SEARCH MATRIX -->
    <!-- 📊 LIVE TRANSACTION FILTER SEARCH MATRIX WITH INTEGRATED EXCEL EXPORTER -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-top: 35px; margin-bottom: 10px; gap: 15px;">
    <h3 style="margin: 0; color: #1e3c72; font-size: 16px;">📑 Live Transaction Audit Logs (Latest 50 Entries)</h3>

        <!-- The Properly Positioned Search Input Box -->
        <div style="position: relative; max-width: 320px; width: 100%;">
            <input type="text" id="dashboardSearchBox" onkeyup="filterAdminTransactionTable()" placeholder="Tafuta kwa namba ya simu au PIN..." style="width: 100%; padding: 10px 12px 10px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#1e3c72'" onblur="this.style.borderColor='#cbd5e1'">
            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;">🔍</span>
        </div>
    </div>
</div>

   
</div>


    <!-- LIGHTWEIGHT CLIENT-SIDE FILTER SEARCH ENGINE -->
    <script type="text/javascript">
      function filterAdminTransactionTable() {
        var input = document.getElementById("dashboardSearchBox");
        var rawInput = input.value.trim();
        var filter = rawInput.toUpperCase();
        
        // 🚀 SMART PREFIX NORMALIZATION: If user types a standard local number starting with 0, 
        // create a normalized fallback string that replaces the '0' with '255' for matching cells
        var normalizedFilter = filter;
        if (rawInput.startsWith('0')) {
            normalizedFilter = '255' + filter.substring(1);
        }

        var table = document.querySelector("table:not(.stock-table)");
        var tr = table.getElementsByTagName("tr");

        // Loop through all data rows skipping your table header columns element row
        for (var i = 1; i < tr.length; i++) {
            // Target text cells: Voucher PIN (Column 1) and Assigned Phone (Column 4)
            var tdVoucher = tr[i].getElementsByTagName("td")[1];
            var tdPhone   = tr[i].getElementsByTagName("td")[4];
            
            if (tdVoucher || tdPhone) {
                var voucherText = tdVoucher ? (tdVoucher.textContent || tdVoucher.innerText).trim() : "";
                var phoneText   = tdPhone ? (tdPhone.textContent || tdPhone.innerText).trim() : "";
                
                var upperVoucher = voucherText.toUpperCase();
                var upperPhone   = phoneText.toUpperCase();
                
                // 🔍 MULTI-MATCH EVALUATION MATRIX: 
                // Checks raw input against PIN, raw input against Phone, AND normalized 255 string against Phone!
                if (upperVoucher.indexOf(filter) > -1 || 
                    upperPhone.indexOf(filter) > -1 || 
                    upperPhone.indexOf(normalizedFilter) > -1) {
                    
                    tr[i].style.display = ""; // Keyword matches, reveal row layout element
                } else {
                    tr[i].style.display = "none"; // No match found, hide row dynamically
                }
            }       
        }
    }

    </script>

    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th style="text-align: center;">S/N</th>
                    <th>Voucher Code</th>
                    <th>Price Tier</th>
                    <th>Status</th>
                    <th>Assigned Phone</th>
                    <th>MAC Address</th>
                    <th>Muda wa Malipo (EAT Time)</th>
                    <th>NIT Transacion ID</th>
                    <th>AzamPay Transaction ID</th> <!-- 🚀 UPDATED HEADER KEY NAME -->
                </tr>
            </thead>
            <tbody>
                <?php if ($log_result && $log_result->num_rows > 0): ?>
                    <?php 
                    // Descending Serial Number loop engine initial tracking setting
                    $sn_counter = $log_result->num_rows; 
                    
                    while ($row = $log_result->fetch_assoc()): 
                        $rawMac = preg_replace('/[^a-zA-Z0-9]/', '', $row['mac_address']);
                        $displayMac = strlen($rawMac) === 12 ? implode(':', str_split($rawMac, 2)) : $row['mac_address'];
                    ?>
                        <tr>
                            <td style="font-weight: bold; color: #475569; font-family: monospace; text-align: center;">
                                <?php echo $sn_counter--; ?>
                            </td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px;"><?php echo $row['voucher_code']; ?></td>
                            <td>Tsh <?php echo number_format($row['price_tier']); ?></td>
                            <td><span class="badge <?php echo ($row['status'] === 'SUCCESS') ? 'badge-success' : 'badge-assigned'; ?>"><?php echo $row['status']; ?></span></td>
                            <td><?php echo !empty($row['assigned_phone']) ? htmlspecialchars($row['assigned_phone']) : '-'; ?></td>
                            <td class="mac-text"><?php echo !empty($rawMac) ? htmlspecialchars(strtoupper($displayMac)) : '-'; ?></td>
                            <td style="font-family: monospace; color: #2c3e50; font-weight: 500;">
                                <?php echo !empty($row['purchased_at']) ? date("d-m-Y H:i:s", strtotime($row['purchased_at'])) : '-'; ?>
                            </td>
                            <td style="color: #7f8c8d; font-size: 12px; font-family: monospace;"><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            
                            <!-- 🚀 DYNAMIC AUDIT CELL: Safely displays the custom renamed column values visually live -->
                            <td style="color: #27ae60; font-weight: bold; font-family: monospace; font-size: 13px;">
                                <?php echo !empty($row['azampay_transaction_id']) ? htmlspecialchars($row['azampay_transaction_id']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align: center; color: #7f8c8d; padding: 20px;">Hakuna kumbukumbu za malipo bado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    <!-- SILENT BACKGROUND DATABASE CLEANUP TRIGGER -->
  <script type="text/javascript">
    // 🚀 PERSISTENT SEARCH MEMORY FILTER ENGINE
    function filterAdminTransactionTable() {
        var input = document.getElementById("dashboardSearchBox");
        if (!input) return;
        
        var rawInput = input.value.trim();
        var filter = rawInput.toUpperCase();
        
        // Save the current input keyword into the browser's temporary session storage memory
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
                // Column Index 1: Voucher PIN | Column Index 4: Assigned Phone
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

    // 🔄 AUTOMATED RESTORATION LAYER: Executes seamlessly immediately upon page reload/sync
    window.addEventListener('DOMContentLoaded', function() {
        // 1. Pull any stored keyword out of session storage and re-apply the filters
        var savedKeyword = sessionStorage.getItem("adminSearchKeyword");
        if (savedKeyword) {
            var searchBox = document.getElementById("dashboardSearchBox");
            if (searchBox) {
                searchBox.value = savedKeyword;
                filterAdminTransactionTable(); 
            }
        }

        // 2. Your background self-healing database cleaner script runs perfectly here
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
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
exit();
?>
