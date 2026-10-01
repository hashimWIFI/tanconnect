<?php
// ====================================================================
// TANCONNECT AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep logs safe in production

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

// 📝 AUDIT LOG TRAIL: Saves incoming payloads to verify incoming json keys
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . \$incomingRawJson . PHP_EOL, FILE_APPEND);

\(paymentData = json_decode(\)incomingRawJson, true);

if (!\$paymentData) {
    http_response_code(400);
    die("Invalid JSON Request Payload");
}

// 3. EXTRACT AZAMPAY DATA KEYS USING VERIFIED EXTENSION PATHS
\$status          = isset(\(paymentData['transactionstatus']) ? strtolower(trim(\)paymentData['transactionstatus'])) : '';
realNetworkTxId = isset(paymentData['reference']) ? trim(\(paymentData['reference']) : '';\)prePaymentId    = isset(\(paymentData['utilityref']) ? trim(\)paymentData['utilityref']) : '';

// 4. RUN SUCCESSFUL PAYMENT TRANSITION LOGIC
if (\(status === 'success' && !empty(\)prePaymentId)) {
    
    // ⚡ FIX: Search table by matching the pre-payment ID saved before PIN entry
    \(searchQuery = mysqli_query(\)conn, "SELECT id FROM wifi_vouchers WHERE transaction_id = '\$prePaymentId' LIMIT 1");
    
    if (mysqli_num_rows(\$searchQuery) > 0) {
        \(voucherRow = mysqli_fetch_assoc(\)searchQuery);
        voucherId = voucherRow['id'];
        
        // START SECURE TRANSACTION PROCESSING CHAIN
        mysqli_begin_transaction(\$conn);
        try {
            // A. Move voucher status to SUCCESS
            // B. Capture and save the REAL post-PIN network Transaction ID into your database column!
            // C. Lock down transaction completion metrics
            \$updateSql = "UPDATE wifi_vouchers 
                          SET status = 'SUCCESS', 
                              azampay_transaction_id = '\$realNetworkTxId', 
                              `Muda wa Malipo (EAT Time)` = NOW() 
                          WHERE id = '\$voucherId'";
            
            mysqli_query(conn, updateSql);
            mysqli_commit(\$conn);
            
            // 🚀 B. AUTOMATED SMS DELIVERY DISPATCH BRIDGE
            // If you have your text messaging code snippet here, it will send the voucher automatically!
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Unlocked and real network ID recorded"]);
            exit();
            
        } catch (Exception \$e) {
            mysqli_rollback(\$conn);
            file_put_contents('azampay_error_log.txt', date('[Y-m-d H:i:s] ') . "SQL Exception: " . \$e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            exit();
        }
    } else {
        file_put_contents('azampay_error_log.txt', date('[Y-m-d H:i:s] ') . "Mismatch Error: No row matches pre-payment reference ID: \$prePaymentId" . PHP_EOL, FILE_APPEND);
    }
}

// Default response if transaction data criteria mismatches
http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
