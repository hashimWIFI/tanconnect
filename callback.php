<?php
// ====================================================================
// TANCONNECT AZAMPESA WEBHOOK
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);


// ==========================================
// 1. CONNECT TO AUTOMATED RAILWAY MYSQL DB
// ==========================================
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

// Establish the connection matrix
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check if connection was successful
if (!$conn) {
    die("Database Connection Failure: " . mysqli_connect_error());
}


	$incomingRawJson = file_get_contents('php://input');
	file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . $incomingRawJson . PHP_EOL, FILE_APPEND);

// Line 31: Fixed parenthesis mismatch and added missing $ sign
$paymentData = json_decode($incomingRawJson, true);

if (!$paymentData) {
    http_response_code(400);
    die("Invalid JSON Request");
}

// Lines 38-40: Added all missing $ signs and removed stray parenthesis formatting blocks
// Lines 38-40: Keep your clean lowercase parameters mapping intact
$status    = isset($paymentData['transactionstatus']) ? strtolower(trim($paymentData['transactionstatus'])) : (isset($paymentData['transactionStatus']) ? strtolower(trim($paymentData['transactionStatus'])) : '');
$realTxId  = isset($paymentData['transactionid']) ? trim($paymentData['transactionid']) : (isset($paymentData['transactionId']) ? trim($paymentData['transactionId']) : (isset($paymentData['reference']) ? trim($paymentData['reference']) : ''));
$prePaidId = isset($paymentData['utilityref']) ? trim($paymentData['utilityref']) : (isset($paymentData['utilityReference']) ? trim($paymentData['utilityReference']) : '');

// Line 42: Aligned variable spelling completely ($prePaidId matches line 40)
if (($status === 'success' || $status === 'completed') && !empty($prePaidId)) {
    
    // Line 43: Swapped temporary parameter identifier mapping safely
    $searchQuery = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE transaction_id = '$prePaidId' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($searchQuery);
        $voucherId  = $voucherRow['id'];

        
        mysqli_begin_transaction($conn);
        try {
            // Manually clean this line to say: $updateSql = "UPDATE wifi_vouchers SET status = 'SUCCESS', azampay_transaction_id = '$realTxId', ...
            $updateSql = "UPDATE wifi_vouchers SET status = 'SUCCESS', azampay_transaction_id = '$realTxId', `Muda wa Malipo (EAT Time)` = NOW() WHERE id = '$voucherId'";

            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            exit();
        }
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
