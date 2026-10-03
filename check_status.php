<?php
// ====================================================================
// TANCONNECT PRO-SPEC FRONTEND STATUS CHECKER ('check_status.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json");

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
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

// ⚡ THE ALIGNED DISCOVERY ENGINE: Captures both 'transaction_id' and 'utilityref' URL parameter string variations safely
$txId = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : (isset($_GET['utilityref']) ? trim($_GET['utilityref']) : '');

if (!empty($txId)) {
    // Escapes the data clean string safely to guard the query transaction limits
    $safeTxId = mysqli_real_escape_string($conn, $txId);
    
    // Look up the unique row matching your literal database table configuration setup columns
    $query = mysqli_query($conn, "SELECT transactionstatus, voucher_code FROM wifi_vouchers WHERE utilityref = '$safeTxId' LIMIT 1");
    
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $currentStatus = strtoupper(trim($row['transactionstatus']));
        
        // ⚡ MULTI-KEY PAYLOAD RETURN: Sends both variations so the frontend JavaScript decodes it instantly!
        echo json_encode([
            "status"            => $currentStatus,
            "transactionstatus" => $currentStatus,
            "voucher_code"      => ($currentStatus === 'SUCCESS') ? $row['voucher_code'] : ''
        ]);
        exit();
    }
}

// Default fallback state if the verification match remains pending inside the table rows
echo json_encode([
    "status"            => "PENDING", 
    "transactionstatus" => "PENDING", 
    "voucher_code"      => ""
]);
exit();
?>
