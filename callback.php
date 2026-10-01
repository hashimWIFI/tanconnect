<?php
// ====================================================================
// TANCONNECT AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep screen logs safe in production

// 1. ESTABLISH YOUR DIRECT MYSQL CONNECTION VIA RAILWAY ENV VARIABLES
\$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
\(db_port     = getenv('MYSQLPORT') ?: '3306';\)db_user     = getenv('MYSQLUSER') ?: 'root';
\(db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';\)db_name     = getenv('MYSQLDATABASE') ?: 'railway';

\(conn = mysqli_connect(\)db_host, \(db_user,\)db_password, \(db_name,\)db_port);

if (!\$conn) {
    http_response_code(500);
    file_put_contents('azampay_error_log.txt', date('[Y-m-d H:i:s] ') . "DB Conn Fail: " . mysqli_connect_error() . PHP_EOL, FILE_APPEND);
    die("Database Connection Failure");
}

// 2. CAPTURE THE HIDDEN WEBHOOK PAYLOAD DISPATCHED BY AZAMPAY
\$incomingRawJson = file_get_contents('php://input');

// 📝 AUDIT LOG TRAIL: Saves the exact data AzamPay sends right into a text file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . \$incomingRawJson . PHP_EOL, FILE_APPEND);

\(paymentData = json_decode(\)incomingRawJson, true);

if (!\$paymentData) {
    http_response_code(400);
    die("Invalid JSON Request Payload Structure");
}

// 3. BULLETPROOF VARIABLE MATCHING (Checks both upper and lower case to be 100% safe)
\$transactionStatus = isset(\(paymentData['transactionstatus']) ?\)paymentData['transactionstatus'] : (isset(\(paymentData['transactionStatus']) ?\)paymentData['transactionStatus'] : '');
\(azamPayTxId       = isset(\)paymentData['transactionid']) ? \(paymentData['transactionid'] : (isset(\)paymentData['transactionId']) ? \(paymentData['transactionId'] : '');\)customReference   = isset(\(paymentData['utilityref']) ?\)paymentData['utilityref'] : (isset(\(paymentData['utilityReference']) ?\)paymentData['utilityReference'] : '');

// 4. VERIFY STATUS AND UPDATE DATABASE
if (strtolower(\(transactionStatus) === 'success' \vert{}\vert{} strtolower(\)transactionStatus) === 'completed') {
    
    // Look up the voucher record that holds this matching transaction reference string
    \(searchQuery = mysqli_query(\)conn, "SELECT id FROM wifi_vouchers WHERE azampay_transaction_id = '\(azamPayTxId' OR transaction_id = '\)customReference' LIMIT 1");
    
    if (mysqli_num_rows(\$searchQuery) > 0) {
        \(voucherRow = mysqli_fetch_assoc(\)searchQuery);
        \(voucherId  =\)voucherRow['id'];
        
        // START SECURE TRANSACTION PROCESSING CHAIN
        mysqli_begin_transaction(\$conn);
        try {
            // Move voucher status to SUCCESS and lock down completion timestamp
            \$updateSql = "UPDATE wifi_vouchers SET status = 'SUCCESS', `Muda wa Malipo (EAT Time)` = NOW() WHERE id = '\$voucherId'";
            mysqli_query(\(conn,\)updateSql);
            mysqli_commit(\$conn);
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Voucher unlocked successfully"]);
            exit();
            
        } catch (Exception \$e) {
            mysqli_rollback(\$conn);
            file_put_contents('azampay_error_log.txt', date('[Y-m-d H:i:s] ') . "SQL Exception: " . \$e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            exit();
        }
    } else {
        file_put_contents('azampay_error_log.txt', date('[Y-m-d H:i:s] ') . "Mismatch Error: No record found in database for AzamPay ID: \(azamPayTxId or Ref:\)customReference" . PHP_EOL, FILE_APPEND);
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
