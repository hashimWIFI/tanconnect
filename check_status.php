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

$txId = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';

if (!empty($txId)) {
    $query = mysqli_query($conn, "SELECT status, voucher_code FROM wifi_vouchers WHERE transaction_id = '" . mysqli_real_escape_string($conn, $txId) . "' LIMIT 1");
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        echo json_encode([
            "status" => $row['status'],
            "voucher_code" => ($row['status'] === 'SUCCESS') ? $row['voucher_code'] : ''
        ]);
        exit();
    }
}

echo json_encode(["status" => "PENDING"]);
?>
