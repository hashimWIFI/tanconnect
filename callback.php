<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$db_host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT WEBHOOK ERROR: Database connection failed");
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failure"]);
    exit();
}

$incomingRawJson = file_get_contents('php://input');
error_log("=== INBOUND PAYLOAD ARRIVED ===");
error_log($incomingRawJson);

file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . $incomingRawJson . PHP_EOL, FILE_APPEND);

$paymentData = json_decode($incomingRawJson, true);

if (!$paymentData) {
    error_log("TANCONNECT WEBHOOK ERROR: Malformed JSON payload received");
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload structure"]);
    exit();
}
// ⚡ SANITIZATION PATCH: Clean out line-breaks (\n) and spaces dynamically from AzamPay's payload strings
$status    = isset($paymentData['transactionstatus']) ? strtolower(trim($paymentData['transactionstatus'])) : '';
$realTxId  = isset($paymentData['reference']) ? trim($paymentData['reference']) : '';
$prePaidId = isset($paymentData['utilityref']) ? trim(preg_replace('/\s+/', '', $paymentData['utilityref'])) : '';

if ($status === 'success' || $status === 'completed') {
    
    $searchQuery = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE transaction_id = '$prePaidId' OR azampay_transaction_id = '$realTxId' OR transaction_id = '$realTxId' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($searchQuery);
        $voucherId = $voucherRow['id'];
        
        mysqli_begin_transaction($conn);
        try {
            $updateSql = "UPDATE wifi_vouchers SET status = 'SUCCESS', azampay_transaction_id = '$realTxId', `Muda wa Malipo (EAT Time)` = NOW() WHERE id = '$voucherId'";
            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
            error_log("TANCONNECT WEBHOOK SUCCESS: Voucher record updated successfully!");
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Voucher unlocked cleanly"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT WEBHOOK EXCEPTION: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "SQL execution runtime crash"]);
            exit();
        }
    } else {
        error_log("TANCONNECT WEBHOOK MISMATCH: Reference numbers not found inside the database rows.");
        http_response_code(200);
        echo json_encode(["status" => "mismatch", "message" => "Reference key not found inside database columns"]);
        exit();
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored", "message" => "Non-success transaction skipped"]);
?>
