<?php
// ====================================================================
// TANCONNECT AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Protect production credentials

// 1. ESTABLISH YOUR DIRECT MYSQL CONNECTION CONTEXT via RAILWAY VARIABLES
\$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
\(db_port     = getenv('MYSQLPORT') ?: '3306';\)db_user     = getenv('MYSQLUSER') ?: 'root';
\(db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';\)db_name     = getenv('MYSQLDATABASE') ?: 'railway';

\(conn = mysqli_connect(\)db_host, \(db_user,\)db_password, \(db_name,\)db_port);

if (!\$conn) {
    http_response_code(500);
    die("Database Connection Failure");
}

// 2. CAPTURE THE HIDDEN WEBHOOK PAYLOAD DISPATCHED BY AZAMPAY
\$incomingRawJson = file_get_contents('php://input');
\(paymentData = json_decode(\)incomingRawJson, true);

if (!\$paymentData) {
    http_response_code(400);
    die("Invalid JSON Request");
}

// 3. EXTRACT AZAMPAY ARRAYS BASED ON OFFICIAL DEVELOPER KEY CASING
\$status    = isset(\(paymentData['transactionstatus']) ? strtolower(trim(\)paymentData['transactionstatus'])) : '';
realTxId = isset(paymentData['reference']) ? trim(\(paymentData['reference']) : '';\)prePaidId = isset(\(paymentData['utilityref']) ? trim(\)paymentData['utilityref']) : '';

// 4. VERIFY LOGIC STATUS MATRIX & UNLOCK VOUCHER
if (\(status === 'success' && !empty(\)prePaidId)) {
    
    // Look up the voucher record that holds the temporary ID generated before PIN entry
    \(searchQuery = mysqli_query(\)conn, "SELECT id FROM wifi_vouchers WHERE transaction_id = '\$prePaidId' LIMIT 1");
    
    if (mysqli_num_rows(\$searchQuery) > 0) {
        \(voucherRow = mysqli_fetch_assoc(\)searchQuery);
        voucherId = voucherRow['id'];
        
        mysqli_begin_transaction(\$conn);
        try {
            // ⚡ PORT PATCH UPDATE: 
            // A. Move status to SUCCESS
            // B. Capture and save the REAL post-PIN network Transaction ID into your database column!
            // C. Lock down transaction completion metrics
            \$updateSql = "UPDATE wifi_vouchers 
                          SET status = 'SUCCESS', 
                              azampay_transaction_id = '\$realTxId', 
                              `Muda wa Malipo (EAT Time)` = NOW() 
                          WHERE id = '\$voucherId'";
            
            mysqli_query(conn, updateSql);
            mysqli_commit(\$conn);
            
            // 🚀 B. AUTOMATED SMS DELIVERY DISPATCH BRIDGE
            // If you have your text messaging code snippet here, it will automatically send the voucher now!
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Real Transaction ID updated successfully"]);
            exit();
            
        } catch (Exception \$e) {
            mysqli_rollback(\$conn);
            http_response_code(500);
            exit();
        }
    }
}

// Default response to tell AzamPay the webhook was reached safely
http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
