<?php
// =========================================================================
// 🚀 TANCONNECT AUTOMATED OFFICE EXPORT ENGINE
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enforce Excel spreadsheet download stream content headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=TANConnect_Sales_Report_' . date('Y-m-d_H-i-s') . '.csv');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1. Establish database connection using dynamic Railway variables
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("Database connection failed for download pipeline.");
}

// 2. Safely capture and harvest dashboard calendar selection constraints
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date   = isset($_GET['to_date'])   ? trim($_GET['to_date'])   : '';

// Build dynamic WHERE condition block matching dashboard calendar parameters
$where_clauses = ["status IN ('SUCCESS', 'ASSIGNED')"];

if (!empty($from_date)) {
    $safe_from = $conn->real_escape_string($from_date);
    $where_clauses[] = "DATE(purchased_at) >= '$safe_from'";
}
if (!empty($to_date)) {
    $safe_to = $conn->real_escape_string($to_date);
    $where_clauses[] = "DATE(purchased_at) <= '$safe_to'";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// 3. Assemble and execute dynamic sales audit query matching the dashboard data grid layout
$query = "SELECT voucher_code, price_tier, status, assigned_phone, mac_address, purchased_at, transaction_id, azampay_transaction_id 
          FROM wifi_vouchers 
          $where_sql 
          ORDER BY purchased_at DESC";

$result = $conn->query($query);

// 4. Initialize layout headers matching your dashboard grid column schema exactly
$output = fopen('php://output', 'w');

// Add UTF-8 BOM byte sequence to guarantee correct Swahili character encoding in Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [
    'S/N', 
    'Voucher Code', 
    'Price Tier (Tsh)', 
    'Status', 
    'Assigned Phone', 
    'MAC Address', 
    'Muda wa Malipo (EAT Time)', 
    'NIT Transaction ID', 
    'AzamPay Transaction ID'
]);

// 5. Populate file text layers dynamically matching rows perfectly
if ($result && $result->num_rows > 0) {
    $counter = 1;
    while ($row = $result->fetch_assoc()) {
        // Enforce upper casing formatting standards side-by-side
        $cleanStatus = strtoupper(trim($row['status']));
        
        // Normalize dynamic MAC address format parameters
        $rawMac = preg_replace('/[^a-zA-Z0-9]/', '', $row['mac_address']);
        $displayMac = (strlen($rawMac) === 12) ? implode(':', str_split($rawMac, 2)) : $row['mac_address'];
        $displayMac = !empty($rawMac) ? strtoupper($displayMac) : '-';

        // Enforce localized East African time standard formatting styles
        $formattedTime = !empty($row['purchased_at']) ? date("d-m-Y H:i:s", strtotime($row['purchased_at'])) : '-';

        fputcsv($output, [
            $counter++,
            $row['voucher_code'],
            $row['price_tier'],
            $cleanStatus,
            !empty($row['assigned_phone']) ? $row['assigned_phone'] : '-',
            $displayMac,
            $formattedTime,
            $row['transaction_id'],
            !empty($row['azampay_transaction_id']) ? $row['azampay_transaction_id'] : '-'
        ]);
    }
}

fclose($output);
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit();
?>
