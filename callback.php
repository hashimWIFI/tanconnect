<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT WEBHOOK ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}

$incomingRawJson = file_get_contents('php://input');

error_log("=== AZAMPAY RAW WEBHOOK ARRIVED ===");
error_log($incomingRawJson);
error_log("====================================");

$paymentData = json_decode($incomingRawJson, true);

if (!$paymentData) {
    error_log("TANCONNECT WEBHOOK ERROR: Incoming data is not valid JSON");
    http_response_code(400);
    exit();
}

$status    = isset($paymentData['transactionstatus']) ? strtolower(trim($paymentData['transactionstatus'])) : (isset($paymentData['transactionStatus']) ? strtolower(trim($paymentData['transactionStatus'])) : '');
$realTxId  = isset($paymentData['reference']) ? trim($paymentData['reference']) : (isset($paymentData['transactionId']) ? trim($paymentData['transactionId']) : (isset($paymentData['transactionid']) ? trim($paymentData['transactionid']) : ''));
$prePaidId = isset($paymentData['utilityref']) ? trim($paymentData['utilityref']) : (isset($paymentData['utilityReference']) ? trim($paymentData['utilityReference']) : '');

if (($status === 'success' || $status === 'completed') && !empty($prePaidId)) {
    
    $searchQuery = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE transaction_id = '$prePaidId' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($searchQuery);
        $voucherId  = $voucherRow['id'];
        
        mysqli_begin_transaction($conn);
        try {
            $updateSql = "UPDATE wifi_vouchers SET status = 'SUCCESS', azampay_transaction_id = '$realTxId', `Muda wa Malipo (EAT Time)` = NOW() WHERE id = '$voucherId'";
            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
            error_log("TANCONNECT WEBHOOK SUCCESS: Row ID $voucherId successfully shifted to SUCCESS!");
            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT WEBHOOK EXCEPTION: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    } else {
        error_log("TANCONNECT WEBHOOK MISMATCH: Received payment for $prePaidId but no matching row found in database.");
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
