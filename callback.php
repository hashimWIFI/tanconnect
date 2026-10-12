<?php
// ====================================================================
// TANCONNECT PRODUCTION LIVE UTILITY ENFORCEMENT ENGINE ('callback.php')
// ====================================================================

// 1. Capture incoming secure raw data stream from the gateway
$rawPayloadData = file_get_contents('php://input');
$jsonArray = json_decode($rawPayloadData, true);

if (empty($jsonArray) || !is_array($jsonArray)) {
    $jsonArray = array_merge($_GET, $_POST, $_REQUEST);
}

// 📝 EMERGENCY BUFFER BACKUP Ledger
$logBlock = "========================================\n";
$logBlock .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logBlock .= "RAW_STREAM: " . ($rawPayloadData ?: 'EMPTY_STREAM_BUFFER') . "\n";
$logBlock .= "========================================\n";
file_put_contents('azampay_raw_sniffer_log.txt', $logBlock, FILE_APPEND);

// 2. Immediate handshake back to AzamPay gateway router to keep connections clear
header("Content-Type: application/json");
http_response_code(200);
echo json_encode(["success" => true, "message" => "Packet logged securely"]);

// ====================================================================
// 3. BACKGROUND DATABASE EXECUTION CONTEXT RUNNER
// =========================================================================
$conn = mysqli_connect(
    getenv('MYSQLHOST')     ?: 'mysql.railway.internal',
    getenv('MYSQLUSER')     ?: 'root',
    getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ',
    getenv('MYSQLDATABASE') ?: 'railway',
    getenv('MYSQLPORT')     ?: '3306'
);

if (!$conn) { exit(); }

// ⚡ LEAN ATUMIC PARAMETER HARVESTING (Matches your live sniffer log format)
$transactionStatus  = $jsonArray['transactionstatus'] ?? 'UNKNOWN';
$checkout_reference = $jsonArray['reference']         ?? $jsonArray['transid'] ?? ''; 
$carrier_receipt_id = $jsonArray['mnoreference']      ?? ''; 

$cleanStatus = strtolower(trim((string)$transactionStatus));

// Run the core update only if AzamPay reports an absolute success state
if ($cleanStatus === 'success' || $cleanStatus === 'completed' || $cleanStatus === 'true') {
    
    // Sanitize values cleanly to neutralize malicious string formatting vectors
    $safeCheckoutRef   = mysqli_real_escape_string($conn, trim((string)$checkout_reference));
    $safeCarrierReceipt = mysqli_real_escape_string($conn, trim((string)$carrier_receipt_id));

    // ⚡ STEP 1: Look up your active row where your internal tracking ID sits inside utilityref
    $searchQuery = mysqli_query($conn, "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                                         WHERE utilityref = '$safeCheckoutRef' 
                                           AND transactionstatus = 'PENDING' 
                                         LIMIT 1");
                                         
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $vId = $row['id'];
        
        // ⚡ STEP 2: Overwrite the tracking columns with your lean parameter mapping
        // We write the mobile money carrier ID to utilityref to link it directly with the client
        $finalUpdate = "UPDATE wifi_vouchers 
                        SET transactionstatus = 'SUCCESS', 
                            utilityref = '$safeCarrierReceipt'
                        WHERE id = '$vId'";
                        
        if (mysqli_query($conn, $finalUpdate)) {
            define('TANCONNECT_SECURE_PASS', true);
            $customer_phone = $row['assigned_phone'] ?? '';
            $voucherCode    = $row['voucher_code'] ?? '';
            
            // Instantly route to your SMS wrapper file to text them the access code
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
