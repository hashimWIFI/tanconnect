<?php
// ====================================================================
// TANCONNECT COMPLIANT STATUS CHECKER ('check_status.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);

// ⚡ CORS HEADERS: Allows the user's mobile browser to fetch data safely without security blocks
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");

// Handle preflight OPTIONS requests gracefully
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST')     ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT')     ?: '3306';
$db_user     = getenv('MYSQLUSER')     ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);
if (!$conn) {
    echo json_encode(["status" => "ERROR", "transactionstatus" => "ERROR"]);
    exit();
}

// 2. CAPTURE IDENTIFIER PARAMETERS
$txId = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : (isset($_GET['utilityref']) ? trim($_GET['utilityref']) : '');

if (!empty($txId)) {
    $safeTxId = mysqli_real_escape_string($conn, $txId);
    
    // Looks up the row using your verified database table columns
    $query = mysqli_query($conn, "SELECT transactionstatus, voucher_code FROM wifi_vouchers WHERE utilityref = '$safeTxId' LIMIT 1");
    
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $currentStatus = strtoupper(trim($row['transactionstatus']));
        
        echo json_encode([
            "status"            => $currentStatus,
            "transactionstatus" => $currentStatus,
            "voucher_code"      => ($currentStatus === 'SUCCESS') ? $row['voucher_code'] : ''
        ]);
        mysqli_close($conn);
        exit();
    }
}

echo json_encode([
    "status"            => "PENDING", 
    "transactionstatus" => "PENDING", 
    "voucher_code"      => ""
]);
mysqli_close($conn);
exit();
?>
