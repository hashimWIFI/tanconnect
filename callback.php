<?php
// =========================================================================
// 🚀 TANCONNECT OFFICIAL DIRECT AZAMPAY CALLBACK WEBHOOK RECEIVER (RAILWAY)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep production execution completely clean of raw PHP leaks

header("Content-Type: application/json");

// 1. Capture the raw direct JSON transmission incoming from AzamPay's servers
$raw_payload = file_get_contents('php://input');
$jsonArray = json_decode($raw_payload, true);

if (empty($jsonArray) || !is_array($jsonArray)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Malformed or absent webhook dataset payload"]);
    exit;
}

// 📝 IMMUTABLE EMERGENCY REPORT FILE: Saves every single hit straight to disk space
$logBlock = "========================================\n";
$logBlock .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logBlock .= "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
$logBlock .= "RAW_STREAM: " . ($raw_payload ?: 'EMPTY_STREAM_BUFFER') . "\n";
$logBlock .= "========================================\n";
file_put_contents('azampay_raw_sniffer_log.txt', $logBlock, FILE_APPEND);

// 2. Immediate handshake back to AzamPay gateway router to keep connections clear
header("Content-Type: application/json");
http_response_code(200);
echo json_encode(["success" => true, "message" => "Packet logged securely"]);

// =========================================================================
// 3. SEPARATE BACKGROUND DATABASE CONTEXT RUNNER (PAYLOAD ALIGNED)
// =========================================================================
$conn = mysqli_connect(
    getenv('MYSQLHOST')     ?: 'mysql.railway.internal',
    getenv('MYSQLUSER')     ?: 'root',
    getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ',
    getenv('MYSQLDATABASE') ?: 'railway',
    getenv('MYSQLPORT')     ?: '3306'
);

if (!$conn) { exit(); }

// ⚡ LIVE ATTRIBUTE EXTRACTION: Aligned perfectly with your flat root sniffer keys
$transactionStatus = $jsonArray['transactionstatus'] ?? $jsonArray['message'] ?? 'UNKNOWN';
$checkoutReference = $jsonArray['reference']         ?? $jsonArray['transid'] ?? ''; 
$networkReceiptId  = $jsonArray['mnoreference']      ?? $jsonArray['utilityref'] ?? ''; 
$customer_phone    = $jsonArray['msisdn']             ?? '';

$cleanStatus = strtolower(trim((string)$transactionStatus));

// 🛡️ SECURE TRANSACTION VALIDATION GATE
if ($cleanStatus === 'success' || $cleanStatus === 'completed' || $cleanStatus === 'true') {
    
    // Clean inputs safely to protect your database structure
    $safeCheckoutRef    = mysqli_real_escape_string($conn, trim((string)$checkoutReference));
    $safeNetworkReceipt = mysqli_real_escape_string($conn, trim((string)$networkReceiptId));
    $safeCustomerPhone  = mysqli_real_escape_string($conn, trim((string)$customer_phone));

    // Normalize phone layout formatting automatically to local '0' representation format
    if (substr($safeCustomerPhone, 0, 3) === '255') {
        $safeCustomerPhone = '0' . substr($safeCustomerPhone, 3);
    }

    // ⚡ CLEAN PRODUCTION LOOKUP: Finds the pending voucher using your tracking reference identifier
    // This targets your active utilityref column where checkout portals store the placeholder string
    $searchQuery = mysqli_query($conn, "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                                         WHERE utilityref = '$safeCheckoutRef' 
                                           AND status = 'PENDING' 
                                         LIMIT 1");
                                         
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $vId = $row['id'];
        
        // Updates only your clean, production database columns [image_JZCnQZ.png]
        // Overwrites placeholder tracking reference tag with verified carrier MNO reference number [image_JZCnQZ.png]
        $finalUpdate = "UPDATE wifi_vouchers 
                        SET status = 'SUCCESS', 
                            utilityref = '$safeNetworkReceipt',
                            assigned_phone = '$safeCustomerPhone'
                        WHERE id = '$vId'";
                        
        if (mysqli_query($conn, $finalUpdate)) {
            define('TANCONNECT_SECURE_PASS', true);
            $customer_phone = $row['assigned_phone'] ?? $safeCustomerPhone;
            $voucherCode    = $row['voucher_code'] ?? '';
            
            // Instantly trigger your SMS wrapper file to text the voucher code to the customer
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
