<?php
// ====================================================================
// TANCONNECT PRO-SPEC AUTOMATED CALLBACK ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Active protection: keeps credentials safe in production

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT WEBHOOK ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}

// 2. CAPTURE THE LIVE WEBHOOK DISPATCHED BY AZAMPAY
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Saves incoming payload for structural confirmation
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. ⚡ NESTED PROPERTIES DECODER LAYER (MATCHES YOUR IMAGE DATA EXACTLY)
$transactionstatus = '';
if (isset($paymentData['properties']['transactionstatus'])) {
    $transactionstatus = $paymentData['properties']['transactionstatus'];
} elseif (isset($paymentData['transactionstatus'])) {
    $transactionstatus = $paymentData['transactionstatus'];
}

$externalreference = '';
if (isset($paymentData['properties']['externalreference'])) {
    $externalreference = trim($paymentData['properties']['externalreference']);
} elseif (isset($paymentData['externalreference'])) {
    $externalreference = trim($paymentData['externalreference']);
}

$utilityref = '';
if (isset($paymentData['properties']['utilityref'])) {
    $utilityref = $paymentData['properties']['utilityref'];
} elseif (isset($paymentData['utilityref'])) {
    $utilityref = $paymentData['utilityref'];
}

// Clean trailing line breaks (\n) or hidden spaces safely
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref));
$cleanReference  = trim(preg_replace('/\s+/', '', $externalreference));

// Verify if incoming payload status is an explicit success indicator string
$isPaymentSuccessful = false;
if (strtolower(trim((string)$transactionstatus)) === 'success' || strtolower(trim((string)$transactionstatus)) === 'completed') {
    $isPaymentSuccessful = true;
}

// 4. DUAL-LAYER SECURITY AND DATABASE UPDATE GATEWAY
if ($isPaymentSuccessful && (!empty($cleanUtilityRef) || !empty($cleanReference))) {
    
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    $safeReference  = mysqli_real_escape_string($conn, $safeReference);
    
    // Look up the matching reserved transaction record row
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone, reference, handshake_success FROM wifi_vouchers WHERE utilityref = '$safeUtilityRef' OR reference = '$safeReference' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $voucherId = $row['id'];
        $handshakeCheck = strtoupper(trim((string)$row['handshake_success']));
        
        // 🛑 SECURITY GATE A: Enforce the three-state isolation lock. 
        // If handshake_success isn't explicitly 'TRUE', block the voucher release!
        if ($handshakeCheck !== 'TRUE') {
            error_log("TANCONNECT SECURITY BLOCK: Refused webhook unlock because handshake was not TRUE.");
            http_response_code(200);
            echo json_encode(["status" => "blocked", "message" => "Handshake quarantine constraint active"]);
            exit();
        }
        
        // Preserve initial handshake tracking token cleanly
        $finalReference = !empty($cleanReference) ? $cleanReference : $row['reference'];
        
        mysqli_begin_transaction($conn);
        try {
            // ⚡ SECURITY GATE B: Changes status to SUCCESS securely
            $updateSql = "UPDATE wifi_vouchers 
                          SET transactionstatus = 'SUCCESS', 
                              reference = '" . mysqli_real_escape_string($conn, $finalReference) . "',
                              purchased_at = NOW() 
                          WHERE id = '$voucherId'";
            
            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
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
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Voucher released successfully"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
