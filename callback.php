<?php
// ====================================================================
// TANCONNECT AUTOMATED WEBHOOK LISTENER & DB DIAGNOSTIC ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); 

// 1. ESTABLISH YOUR DIRECT MYSQL CONNECTION CONTEXT via RAILWAY VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

// ⚡ PORT PATCH: Added the 5th parameter slot to allow Railway internal connection matrix routing
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

// Check if connection was successful
if (!$conn) {
    http_response_code(500);
    die("Database Connection Failure: " . mysqli_connect_error());
}


// 1. CAPTURE THE RAW HIDDEN TEXT INBOUND FROM AZAMPAY
\$incomingRawJson = file_get_contents('php://input');
\(paymentData = json_decode(\)incomingRawJson, true);

// 🔍 CRITICAL DATABASE DEBUGGER LAYER:
// If we receive ANY data from AzamPay, immediately update the latest ASSIGNED voucher 
// row status to show us the raw text package they sent!
if (!empty(\(incomingRawJson)) {\)escapedJson = mysqli_real_escape_string(conn, substr(incomingRawJson, 0, 200));
    debugStatusText = "RAW:" . escapedJson;
    
    // Save the raw callback blueprint text right into your active table status column to bypass file system limits
    mysqli_query(\$conn, "UPDATE wifi_vouchers SET status = '\$debugStatusText' WHERE status = 'ASSIGNED' ORDER BY id DESC LIMIT 1");
}

if (!\$paymentData) {
    http_response_code(400);
    die("Invalid JSON Request");
}

// 2. EXTRACT TRANSACTION KEYS
\$transactionStatus = isset(\(paymentData['transactionStatus']) ? trim(\)paymentData['transactionStatus']) : '';
azamPayTxId = isset(paymentData['transactionId']) ? trim(\(paymentData['transactionId']) : '';\)customReference    = isset(\(paymentData['utilityReference']) ? trim(\)paymentData['utilityReference']) : '';

// 3. EXECUTE TRANSITION STATE MACHINE
if (strtolower(\(transactionStatus) === 'success' && !empty(\)azamPayTxId)) {
    
    // Attempt database lookup using structural variables
    \(searchQuery = mysqli_query(\)conn, "SELECT id FROM wifi_vouchers WHERE azampay_transaction_id = '\(azamPayTxId' OR transaction_id = '\)customReference' LIMIT 1");
    
    if (mysqli_num_rows(\$searchQuery) > 0) {
        \(voucherRow = mysqli_fetch_assoc(\)searchQuery);
        voucherId = voucherRow['id'];
        
        mysqli_begin_transaction(\$conn);
        try {
            // Flip the voucher state to permanent success metrics cleanly
            mysqli_query(\$conn, "UPDATE wifi_vouchers SET status = 'SUCCESS', `Muda wa Malipo (EAT Time)` = NOW() WHERE id = '\$voucherId'");
            mysqli_commit(\$conn);
            
            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit();
            
        } catch (Exception \$e) {
            mysqli_rollback(\$conn);
            http_response_code(500);
            exit();
        }
    } else {
        // Mismatch tracking fallback: Update the status column to flag a key matching failure
        mysqli_query(\$conn, "UPDATE wifi_vouchers SET status = 'ERR_REF_MISMATCH' WHERE status = 'ASSIGNED' ORDER BY id DESC LIMIT 1");
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
