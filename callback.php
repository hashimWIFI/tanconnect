<?php
// ====================================================================
// TANCONNECT PRODUCTION DUAL PAYLOAD VALIDATOR ENGINE ('callback.php')
// ====================================================================

// 1. ISOLATE RAW INTAKE STREAM IMMEDIATELY ON LINE 1
$rawPayloadData = file_get_contents('php://input');
$jsonArray = json_decode($rawPayloadData, true);

if (empty($jsonArray) || !is_array($jsonArray)) {
    $jsonArray = array_merge($_GET, $_POST, $_REQUEST);
}

// 📝 IMMUTABLE EMERGENCY REPORT FILE: Saves every single hit straight to your disk space!
$logBlock = "========================================\n";
$logBlock .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logBlock .= "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
$logBlock .= "RAW_STREAM: " . ($rawPayloadData ?: 'EMPTY_STREAM_BUFFER') . "\n";
$logBlock .= "PARSED_FIELDS: " . json_encode($jsonArray) . "\n";
$logBlock .= "========================================\n";

file_put_contents('azampay_raw_sniffer_log.txt', $logBlock, FILE_APPEND);

// 2. DISCONNECT FROM OUTSIDE PORTS IMMEDIATELY TO BYPASS OUTBOUND BLOCKS
header("Content-Type: application/json");
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "TanConnect sniffer layer successfully captured and logged this network packet"
]);

// 3. SEPARATE BACKGROUND DATABASE CONTEXT RUNNER
$conn = mysqli_connect(
    getenv('MYSQLHOST') ?: 'mysql.railway.internal',
    getenv('MYSQLUSER') ?: 'root',
    getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ',
    getenv('MYSQLDATABASE') ?: 'railway',
    getenv('MYSQLPORT') ?: '3306'
);

if (!$conn) { exit(); }

$transactionStatus = $jsonArray['transactionStatus'] ?? $jsonArray['transactionstatus'] ?? 'UNKNOWN';
$reference         = $jsonArray['reference']         ?? $jsonArray['transactionId']       ?? '';
$utilityRef        = $jsonArray['utilityRef']        ?? $jsonArray['utilityref']          ?? '';

$cleanUtilityRef = strtolower(trim((string)$utilityRef));
$cleanReference  = strtolower(trim((string)$reference));
$statusLower     = strtolower(trim((string)$transactionStatus));

$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);

if ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true') {
    // Four-key safety matrix sweeps both incoming reference variables across both target table columns!
    $searchQuery = mysqli_query($conn, "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                                         WHERE LOWER(reference) = '$safeUtilityRef' OR LOWER(utilityref) = '$safeReference'
                                            OR LOWER(reference) = '$safeReference' OR LOWER(utilityref) = '$safeUtilityRef' 
                                         LIMIT 1");
                                         
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $vId = $row['id'];
        
        if (mysqli_query($conn, "UPDATE wifi_vouchers SET transactionstatus = 'SUCCESS', purchased_at = NOW() WHERE id = '$vId'")) {
            define('TANCONNECT_SECURE_PASS', true);
            $customer_phone = $row['assigned_phone'] ?? '';
            $voucherCode    = $row['voucher_code'] ?? '';
            if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                ob_start(); include('sms_processor.php'); ob_end_clean();
            }
        }
    }
}
mysqli_close($conn);
exit();
?>
