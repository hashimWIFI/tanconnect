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

// 3. Fetch the Latest 50 Live Hotspot Transactions (Includes purchased_at)
$log_query = "SELECT id, voucher_code, price_tier, status, assigned_phone, mac_address, transaction_id, purchased_at FROM wifi_vouchers WHERE status IN ('SUCCESS', 'ASSIGNED') ORDER BY id DESC LIMIT 50";
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
        .wrapper { max-width: 1000px; margin: 0 auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #1e3c72; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top: 20px; }
        .metric-card { padding: 20px; border-radius: 8px; color: white; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card-green { background: #27ae60; }
        .card-blue { background: #2980b9; }
        .card-orange { background: #e67e22; }
        .metric-val { font-size: 28px; display: block; margin-top: 5px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #e2e8f0; padding: 12px 10px; text-align: left; }
        th { background-color: #f8fafc; color: #475569; font-weight: bold; }
        tr:hover { background-color: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #e8f8f0; color: #27ae60; border: 1px solid #27ae60; }
        .badge-assigned { background: #eaf2f8; color: #2980b9; border: 1px solid #2980b9; }
        .mac-text { font-family: monospace; letter-spacing: 0.5px; color: #555; }
    /* Stock Modal Popup Layout Rules */
    .stock-modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }
    .stock-modal-content {
    background-color: #ffffff !important; /* FORCES SOLID OPAQUE VISUAL BACKFILL LAYER */
    background: #ffffff !important;       /* BULLETPROOF BACKFILL OVERRIDE SEGMENT */
    margin: 5% auto;
    padding: 25px;
    border: 1px solid #cbd5e1;
    width: 90%;
    max-width: 450px;
    border-radius: 12px;
    position: relative;
    box-sizing: border-box;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); /* Drops deep shadow separation layer */
}
    .stock-close {
        position: absolute;
        top: 8px; right: 12px;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }
    .stock-close:hover { color: #333; }
    .stock-table {
        width: 100%;
        margin-top: 12px;
        border-collapse: collapse;
    }
    .stock-table th, .stock-table td {
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        font-family: monospace;
        font-size: 14px;
    }
    .stock-table th { background-color: #f8fafc; font-family: sans-serif; font-size: 12px; }
</style>

<script type="text/javascript">
    function openStockSummaryPopup() {
        document.getElementById('stockSummaryModal').style.display = 'flex';
    }
    function closeStockSummaryPopup() {
        document.getElementById('stockSummaryModal').style.display = 'none';
    }
    // Close modal if user clicks outside the white content box area
    window.onclick = function(event) {
        var modal = document.getElementById('stockSummaryModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

    </style>
</head>
<body>

<body>

<div class="wrapper">
    <h2><img src="logo.png" alt="Water Point Logo" style="max-width: 120px; height: auto; object-fit: contain; margin-bottom: 1px;"> Wi-Fi Admin Sales Dashboard</h2>
    
    <!-- Summary Cards Layer -->
    <div class="metrics-grid">
        <div class="metric-card card-green">
            Jumla ya Mapato (Total Earnings)
            <span class="metric-val">Tsh <?php echo number_format($total_earnings); ?></span>
        </div>
        <div class="metric-card card-blue">
            Vocha Zilizouzwa (Vouchers Sold)
            <span class="metric-val"><?php echo number_format($vouchers_sold); ?> pcs</span>
        </div>
        
        <!-- DYNAMIC REMAINING STOCK CARD -->
        <div class="metric-card card-orange" onclick="openStockSummaryPopup()" style="cursor: pointer; transform: scale(1);" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1.0)'">
            Vocha Zilizobaki (Voucher Stock)
            <span class="metric-val"><?php echo number_format($remaining_stock); ?> pcs</span>
            <span style="font-size: 9px; display: block; margin-top: 5px; color: #ffe0b2; letter-spacing: 0.5px;">📋 GUSA HAPA KUONA BATCH DETAILS</span>
        </div>
    </div> 

    <!-- DYNAMIC BATCH STOCK SUMMARY POPUP MODAL CONTAINER -->
    <div id="stockSummaryModal" class="stock-modal" onclick="closeStockSummaryPopup()">
        <div class="stock-modal-content" onclick="event.stopPropagation()">
            <span class="stock-close" onclick="closeStockSummaryPopup()">&times;</span>
            <h3 style="margin-top: 0; color: #e67e22; font-size: 16px; border-bottom: 2px solid #eee; padding-bottom: 8px;">📊 Muhtasari wa Vocha (Stock Summary)</h3>
            
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Price Tier</th>
                        <th>Zilizobaki (Stock)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tier_stock_data)): ?>
                        <?php foreach ($tier_stock_data as $tier): 
                            // Inventory warning threshold configuration matrix
                            $lowStockThreshold = 50; 
                            $isLowStock = ($tier['tier_count'] < $lowStockThreshold);
                            
                            $textStyle = $isLowStock ? 'color: #d9534f; font-weight: 800;' : 'color: #1e3c72; font-weight: bold;';
                            $badgeMarkup = $isLowStock ? ' <span style="font-size: 8px; background-color: #fde8e8; color: #e53e3e; padding: 2px 6px; border-radius: 4px; margin-left: 5px; border: 1px solid #fed7d7; font-family: sans-serif;">⚠️ LOW STOCK</span>' : '';
                            $countStyle = $isLowStock ? 'color: #d9534f; font-weight: 800;' : 'color: #e67e22; font-weight: bold;';
                        ?>
                            <tr style="<?php echo $isLowStock ? 'background-color: #fffaf0;' : ''; ?>">
                                <td style="<?php echo $textStyle; ?>">
                                    Tsh <?php echo number_format($tier['price_tier']); ?>
                                    <?php echo $badgeMarkup; ?>
                                </td>
                                <td style="text-align: right; <?php echo $countStyle; ?>">
                                    <?php echo number_format($tier['tier_count']); ?> pcs
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #7f8c8d;">Hakuna vocha zilizobaki.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h3>📝 Live Transaction Audit Logs (Latest 50 Entries)</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Voucher PIN</th>
                    <th>Price Tier</th>
                    <th>Status</th>
                    <th>Assigned Phone</th>
                    <th>Customer MAC Address</th>
                    <th>Time Purchased</th>
                    <th>Transaction ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($log_result && $log_result->num_rows > 0): ?>
                    <?php while ($row = $log_result->fetch_assoc()): ?>
                        <?php 
                        $rawMac = preg_replace('/[^a-zA-Z0-9]/', '', $row['mac_address']);
                        $displayMac = $row['mac_address'];
                        if (strlen($rawMac) === 12) {
                            $displayMac = implode(':', str_split($rawMac, 2));
                        }
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td style="font-weight: bold; font-family: monospace; font-size: 15px;"><?php echo $row['voucher_code']; ?></td>
                            <td>Tsh <?php echo number_format($row['price_tier']); ?></td>
                            <td>
                                <span class="badge <?php echo ($row['status'] === 'SUCCESS') ? 'badge-success' : 'badge-assigned'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo !empty($row['assigned_phone']) ? htmlspecialchars($row['assigned_phone']) : '-'; ?></td>
                            <td class="mac-text"><?php echo !empty($rawMac) ? htmlspecialchars(strtoupper($displayMac)) : '-'; ?></td>
                            <td style="font-family: monospace; color: #2c3e50; font-weight: 500;">
                                <?php echo !empty($row['purchased_at']) ? date("d-m-Y H:i:s", strtotime($row['purchased_at'])) : '-'; ?>
                            </td>
                            <td style="color: #7f8c8d; font-size: 12px;"><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #7f8c8d; padding: 20px;">Hakuna kumbukumbu za malipo bado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    <!-- SILENT BACKGROUND DATABASE CLEANUP TRIGGER -->
    <script type="text/javascript">
    window.addEventListener('DOMContentLoaded', function() {
        fetch('cron_cleanup.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.vouchers_recovered > 0) {
                    console.log("TANConnect Optimizer: Cleaned database and recovered " + data.vouchers_recovered + " abandoned vouchers back to active stock!");
                }
            })
            .catch(err => console.log("System optimizer loop active..."));
    });
    </script>

</body>
</html>
<?php 
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close(); 
}
exit();
?>
