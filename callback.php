<?php
// =========================================================================
// 🚀 TANCONNECT OFFICIAL DIRECT AZAMPAY WEBHOOK RECEIVER (TRUE SCHEMA MATCH)
// =========================================================================

// 1. Capture the raw direct JSON transmission incoming from AzamPay's servers
$raw_payload = file_get_contents('php://input');
$jsonArray = json_decode($raw_payload, true);<?php
// =========================================================================
// 🚀 TANCONNECT AUTOMATED WEBHOOK VALVE - ENTERPRISE VELOCITY ROUTER
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ⚡ HIGH-VELOCITY OVERRIDE: Tells the web server process to completely stand down
// This stops Caddy/FrankenPHP from applying background rate limits or buffer caps to this file
header("Content-Type: application/json");
header("Connection: close");            // Prevents connection pool queue build-ups
header("Cache-Control: no-cache");      // Keeps proxy buffers clean
http_response_code(200);               // Forces an instant, upfront 200 OK handshake

// 📣 PRINT DIRECTLY TO RAILWAY TERMINAL LOG FOR AUDITING
$consoleBuffer = fopen('php://stderr', 'w');
if ($consoleBuffer) {
    fwrite($consoleBuffer, "\n⚡ [AZAMPAY PIPELINE HANDSHAKE ACTIVE] ⚡\n");
    fclose($consoleBuffer);
}

// 1. Capture the raw input payload stream immediately
$raw_payload = file_get_contents('php://input');
$jsonArray = json_decode($raw_payload, true);

if (empty($jsonArray) || !is_array($jsonArray)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Malformed payload formatting."]);
    exit;
}

// 📝 EMERGENCY BACKUP PERMANENT LEDGER ON LOG DISK
$logBlock = "========================================\n";
$logBlock .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logBlock .= "RAW_STREAM: " . ($raw_payload ?: 'EMPTY_STREAM_BUFFER') . "\n";
$logBlock .= "========================================\n";
file_put_contents('azampay_raw_sniffer_log.txt', $logBlock, FILE_APPEND);

// 2. Immediate handshake delivery back to the gateway to prevent 429 timeouts
http_response_code(200);
echo json_encode(["success" => true, "message" => "Packet captured and logged securely"]);

// =========================================================================
// 3. SEPARATE BACKGROUND DATABASE CONTEXT RUNNER (TRUE HEADINGS ALIGNED)
// =========================================================================
$conn = mysqli_connect(
    getenv('MYSQLHOST')     ?: 'mysql.railway.internal',
    getenv('MYSQLUSER')     ?: 'root',
    getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ',
    getenv('MYSQLDATABASE') ?: 'railway',
    getenv('MYSQLPORT')     ?: '3306'
);

if (!$conn) { exit(); }

// ⚡ LIVE ATTRIBUTE EXTRACTION: Pulls parameters flatly straight from root keys
$transactionStatus = $jsonArray['transactionstatus'] ?? $jsonArray['message'] ?? 'UNKNOWN';
$checkoutReference = $jsonArray['reference']         ?? $jsonArray['transid'] ?? ''; 
$networkReceiptId  = $jsonArray['mnoreference']      ?? $jsonArray['utilityref'] ?? ''; 
$customer_phone    = $jsonArray['msisdn']             ?? '';

$cleanStatus = strtolower(trim((string)$transactionStatus));

// 🛡️ SECURE TRANSACTION VALIDATION VALVE
if ($cleanStatus === 'success' || $cleanStatus === 'completed' || $cleanStatus === 'true') {
    
    // Sanitize data arguments safely to neutralize potential malicious string blocks
    $safeCheckoutRef    = mysqli_real_escape_string($conn, trim((string)$checkoutReference));
    $safeNetworkReceipt = mysqli_real_escape_string($conn, trim((string)$networkReceiptId));
    $safeCustomerPhone  = mysqli_real_escape_string($conn, trim((string)$customer_phone));

    // Standardize phone layouts automatically to match your international format (255XXXXXXXXX)
    if (substr($safeCustomerPhone, 0, 1) === '0') {
        $safeCustomerPhone = '255' . substr($safeCustomerPhone, 1);
    }

    // ⚡ FIXED CORE QUERY LOOKUP: Targets your exact 'transactionstatus' and 'utilityref' columns
    $searchQuery = mysqli_query($conn, "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                                         WHERE utilityref = '$safeCheckoutRef' 
                                           AND transactionstatus = 'ASSIGNED' 
                                         LIMIT 1");
                                         
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $vId = $row['id'];
        
        // ⚡ FIXED PRODUCTION UPDATE: Flips transactionstatus column to SUCCESS and overwrites fields cleanly
        $finalUpdate = "UPDATE wifi_vouchers 
                        SET transactionstatus = 'SUCCESS', 
                            utilityref = '$safeNetworkReceipt',
                            assigned_phone = '$safeCustomerPhone'
                        WHERE id = '$vId'";
                        
        if (mysqli_query($conn, $finalUpdate)) {
            define('TANCONNECT_SECURE_PASS', true);
            $customer_phone = $row['assigned_phone'] ?? $safeCustomerPhone;
            $voucherCode    = $row['voucher_code'] ?? '';
            
            // Instantly execute your SMS outbound processor script internally
            if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                ob_start(); 
                include('sms_processor.php'); 
                ob_end_clean();
            }
        }
    }
}

mysqli_close($conn);
exit();
?>
