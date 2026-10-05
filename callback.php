<?php
// ====================================================================
// TANCONNECT DUO-SPEC DEDICATED TRANSACTION LOGGING ENGINE
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production shield: blocks credential exposure

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT CALLBACK SYSTEM: Database Connection Failed");
    http_response_code(500);
    exit();
}

// 2. CAPTURE THE RAW INBOUND JSON PAYLOAD STREAM FROM AZAMPAY
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Prints out exactly what AzamPay sends after PIN entry directly into your Railway console
error_log("TANCONNECT RAW INBOUND WEBHOOK PAYLOAD: " . $incomingRawJson);

// 3. EXTRACT INCOMING METRICS FROM THE WEBHOOK PAYLOAD
$transactionstatus = $paymentData['transactionStatus'] ?? $paymentData['transactionstatus'] ?? $paymentData['status'] ?? 'UNKNOWN';
$utilityref        = $paymentData['utilityRef'] ?? $paymentData['utilityref'] ?? $paymentData['externalId'] ?? '';
$azampay_reference = $paymentData['reference'] ?? $paymentData['transactionId'] ?? '';

// --- ADVANCED REGEX STRING FALLBACK CRAWLERS ---
// If keys are nested differently inside the JSON, parse the raw text string directly for safety
if (empty($utilityref) && preg_match('/(AZM01[a-zA-Z0-9\-_]+)/i', $incomingRawJson, $matches)) {
    $utilityref = $matches[1];
}
if (empty($azampay_reference) && preg_match('/(AZM08[a-zA-Z0-9\-_]+)/i', $incomingRawJson, $matches)) {
    $azampay_reference = $matches[1];
}

$cleanUtilityRef = trim((string)$utilityref);
$cleanReference  = trim((string)$azampay_reference);
$cleanStatus     = trim((string)$transactionstatus);

// 4. INSTANT PASSIVE INGESTION STEP (No lookup required)
$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);
$safeStatus     = mysqli_real_escape_string($conn, $cleanStatus);
$safeRawPayload = mysqli_real_escape_string($conn, $incomingRawJson);

$insertSql = "INSERT INTO azampay_callbacks (utilityref, reference, transaction_status, raw_payload, received_at) 
              VALUES ('$safeUtilityRef', '$safeReference', '$safeStatus', '$safeRawPayload', NOW())";

if (mysqli_query($conn, $insertSql)) {
    error_log("TANCONNECT SUCCESS: Webhook metrics saved successfully to azampay_callbacks table.");
    
    // ====================================================================
    // ⚡ OPTIONAL BRIDGE: UPDATE VOUCHER STATUS VIA DEDICATED INTERNAL HOOK
    // ====================================================================
    // Since we know exactly what tracking number arrived, we can update the core table cleanly now
    if (!empty($safeUtilityRef) && (strtolower($safeStatus) === 'success' || strtolower($safeStatus) === 'completed')) {
        
        $updateVoucherSql = "UPDATE wifi_vouchers 
                             SET transactionstatus = 'SUCCESS',
                                 callback_utilityref = '$safeUtilityRef',
                                 callback_reference = '$safeReference',
                                 purchased_at = NOW() 
                             WHERE utilityref = '$safeUtilityRef' 
                                OR reference = '$safeReference'";
                                
        if (mysqli_query($conn, $updateVoucherSql)) {
            error_log("TANCONNECT CORE BRIDGE: Linked wifi_vouchers records updated to SUCCESS.");
            
            // Trigger your SMS script if the voucher matching lookup passes locally
            $searchQuery = mysqli_query($conn, "SELECT assigned_phone, voucher_code FROM wifi_vouchers WHERE utilityref = '$safeUtilityRef' LIMIT 1");
            if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
                $row = mysqli_fetch_assoc($searchQuery);
                
                define('TANCONNECT_SECURE_PASS', true);
                $customer_phone = $row['assigned_phone'] ?? '';
                $voucherCode    = $row['voucher_code'] ?? '';
                
                if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                    ob_start();
                    include('sms_processor.php');
                    ob_end_clean();
                }
            }
        }
    }
    
    header("Content-Type: application/json");
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Data ingested successfully into dedicated logging layer."]);
    exit();
} else {
    error_log("TANCONNECT ERROR: Failed writing to logging layer table: " . mysqli_error($conn));
    http_response_code(500);
    exit();
}
?>
