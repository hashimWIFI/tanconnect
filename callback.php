<?php
// ====================================================================
// TANCONNECT PRO-SPEC AUTOMATED CALLBACK ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production protection: blocks credential exposure

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT SYSTEM ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}

// 2. CAPTURE THE RAW INCOMING FLAT JSON PAYLOAD STREAM FROM AZAMPAY
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Prints raw metrics directly to your live Railway console stream
error_log("TANCONNECT RAW INBOUND WEBHOOK PAYLOAD: " . $incomingRawJson);

// 3. EXTRACT METRICS CORE MATCHING OFFICIAL AZAMPAY DATA SPECIFICATION
$transactionstatus = $paymentData['transactionStatus'] ?? $paymentData['transactionstatus'] ?? $paymentData['status'] ?? 'UNKNOWN';
$utilityref        = $paymentData['utilityRef'] ?? $paymentData['utilityref'] ?? $paymentData['externalId'] ?? '';
$azampay_reference = $paymentData['reference'] ?? $paymentData['transactionId'] ?? '';

$cleanUtilityRef = trim((string)$utilityref);        // Holds your system order ID string ("NITW-...")
$cleanReference  = trim((string)$azampay_reference);   // Holds AzamPay's unique network string ("01a0d...")
$statusLower     = strtolower(trim((string)$transactionstatus));

$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);
$safeStatus     = mysqli_real_escape_string($conn, $statusLower);
$safeRawPayload = mysqli_real_escape_string($conn, $incomingRawJson);

// ====================================================================
// 🟢 STEP A: INGEST DATA INTO YOUR DEDICATED LOGGING VAULT TABLE
// ====================================================================
// Wrapped inside a resilient try/catch to ensure database schema drops don't block voucher code releases!
try {
    $insertSql = "INSERT INTO azampay_callbacks (utilityref, reference, transaction_status, raw_payload, received_at) 
                  VALUES ('$safeUtilityRef', '$safeReference', '$safeStatus', '$safeRawPayload', NOW())";
    mysqli_query($conn, $insertSql);
} catch (Exception $logEx) {
    error_log("TANCONNECT AUDIT WARNING: Log table write bypassed due to schema mismatch: " . $logEx->getMessage());
}

// ====================================================================
// 🔵 STEP B: THE DUAL-CROSS OVER OPERATION TO UNLOCK WIFI_VOUCHERS
// ====================================================================
if (!empty($cleanUtilityRef) || !empty($cleanReference)) {
    if ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true') {
        
        // ⚡ THE FOUR-KEY SAFETY MATRIX:
        // Sweeps both arriving payload parameters across both database columns simultaneously!
        // This guarantees a match even if columns are reversed in alogin.php.
        $searchSql = "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                      WHERE reference = '$safeUtilityRef' 
                         OR utilityref = '$safeReference'
                         OR reference = '$safeReference'
                         OR utilityref = '$safeUtilityRef' 
                      LIMIT 1";
                      
        $searchQuery = mysqli_query($conn, $searchSql);
        
        if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
            $row = mysqli_fetch_assoc($searchQuery);
            $voucherId = $row['id'];
            
            // Updates only your clean, baseline database fields to release the code safely
            $updateVoucherSql = "UPDATE wifi_vouchers 
                                 SET transactionstatus = 'SUCCESS',
                                     purchased_at = NOW() 
                                 WHERE id = '$voucherId'";
                                    
            if (mysqli_query($conn, $updateVoucherSql)) {
                error_log("TANCONNECT CORE BRIDGE: Linked wifi_vouchers record ID $voucherId updated to SUCCESS successfully.");
                
                // ====================================================================
                // 🚀 INTEGRATED HARDWARE MODEM SMS GATEWAY PROCESSOR BRIDGE
                // ====================================================================
                define('TANCONNECT_SECURE_PASS', true);
                $customer_phone = $row['assigned_phone'] ?? '';
                $voucherCode    = $row['voucher_code'] ?? '';
                
                if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                    ob_start();
                    include('sms_processor.php');
                    ob_end_clean();
                }
            }
        } else {
            error_log("TANCONNECT WARNING: Webhook tokens matched no active record rows in wifi_vouchers inventory. Release skipped.");
        }
    }
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy integration constraints
header("Content-Type: application/json");
http_response_code(200);
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
exit();
?>
