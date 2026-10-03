<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json");

$db_host     = getenv('MYSQLHOST')     ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT')     ?: '3306';
$db_user     = getenv('MYSQLUSER')     ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);
if (!$conn) {
    echo json_encode(["status" => "ERROR"]);
    exit();
}

// ⚡ ALIGNED INPUT INTERCEPTION: Captures both 'transaction_id' and 'utilityref' safely
$txId = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : (isset($_GET['utilityref']) ? trim($_GET['utilityref']) : '');

if (!empty($txId)) {
    $query = mysqli_query($conn, "SELECT transactionstatus, voucher_code FROM wifi_vouchers WHERE utilityref = '" . mysqli_real_escape_string($conn, $txId) . "' LIMIT 1");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        
        // ⚡ ALIGNED OUTPUT KEYS: Sends both formats so the frontend JavaScript parses it instantly
        echo json_encode([
            "status" => $row['transactionstatus'],
            "transactionstatus" => $row['transactionstatus'],
            "voucher_code" => ($row['transactionstatus'] === 'SUCCESS') ? $row['voucher_code'] : ''
        ]);
        exit();
    }
}

echo json_encode(["status" => "PENDING", "transactionstatus" => "PENDING"]);
?>
