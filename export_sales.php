<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basic HTTP Authentication Layer matching your dashboard security matrix
$admin_user = "admin";
$admin_pass = "nit2026!"; 

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $admin_user || $_SERVER['PHP_AUTH_PW'] !== $admin_pass) {
    header('WWW-Authenticate: Basic realm="TANConnect Wi-Fi Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Utambuzi unahitajika.';
    exit;
}

// Establish temporary database attachment nodes
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { die("Database link dropped."); }

// Fetch the complete transaction history matching success states chronologically
$query = "SELECT voucher_code, price_tier, status, assigned_phone, mac_address, purchased_at, transaction_id, azampay_transaction_id FROM wifi_vouchers WHERE status = 'SUCCESS' ORDER BY purchased_at DESC";
$result = $conn->query($query);

// Force the browser header tracking properties to download a spreadsheet document layout file stream
$filename = "TANConnect_Sales_Report_" . date('Y-m-d_H-i-s') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output buffer data stream
$output = fopen('php://output', 'w');

// Set Excel column headers matching your live operational views row layout fields
fputcsv($output, ['Voucher PIN', 'Price Tier (TZS)', 'Status', 'Customer Phone', 'MAC Address', 'Time Purchased (EAT)', 'NITW Internal TxID', 'AzamPay Transaction ID']);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['voucher_code'],
            $row['price_tier'],
            $row['status'],
            $row['assigned_phone'],
            $row['mac_address'],
            $row['purchased_at'],
            $row['transaction_id'],
            $row['azampay_transaction_id']
        ]);
    }
}

fclose($output);
$conn->close();
exit();
?>
