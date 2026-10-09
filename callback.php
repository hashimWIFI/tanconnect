<?php
// ====================================================================
// TANCONNECT Live STREAM-OPTIMIZED AUTOMATED CALLBACK LISTENER
// ====================================================================

// 🟢 STEP 1: INTERCEPT RAW STREAM IMMEDIATELY BEFORE BUFFER FLUSH CONSUMPTION
// This isolates the raw data text safely in memory as the very first operation!
$rawInput = file_get_contents('php://input');
$payloadData = json_decode($rawInput, true);

if (empty($payloadData) || !is_array($payloadData)) {
    $payloadData = array_merge($_GET, $_POST, $_REQUEST);
}

// 🟢 STEP 2: RESPONSE BUFFER FLUSH TERMINATION LOOP
// Instantly tells AzamPay we received the packet, keeping their firewalls 100% happy!
ob_start();
header("Content-Type: application/json");
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Webhook payload successfully extracted and logged by TanConnect"
]);

$size = ob_get_length();
header("Content-Length: $size");
header("Connection: close");
ob_end_flush();
ob_flush();
flush();

// ⚡ NET CHANNELS DISCONNECTED. The server now processes the database loop in the background!
if (session_status() === PHP_SESSION_NONE) {
    session_write_close();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 3. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT WEBHOOK ERROR: Database Connection Failed");
    exit();
}

// 📝 AUDIT TRAIL LOG: Writes the populated data packet directly to your text audit history
$logEntry = "========================================\n";
$logEntry .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logEntry .= "STATUS EXTRACTED: " . ($payloadData['transactionStatus'] ?? $payloadData['transactionstatus'] ?? 'Not Provided') . "\n";
$logEntry .= "REFERENCE MATCHED: " . ($payloadData['reference'] ?? $payloadData['transactionId'] ?? 'Not Provided') . "\n";
$logEntry .= "UTILITY REF: " . ($payloadData['utilityRef'] ?? $payloadData['utilityref'] ?? 'Not Provided') . "\n";
$logEntry .= "RAW JSON PAYLOAD: " . $rawInput . "\n";
$logEntry .= "========================================\n";

file_put_contents('azampay_delivery_report.txt', $logEntry, FILE_APPEND);

// 4. INGEST DATA INTO YOUR HISTORICAL LOG TABLE VAULT
$transactionStatus = $payloadData['transactionStatus'] ?? $payloadData['transactionstatus'] ?? 'UNKNOWN';
$reference         = $payloadData['reference']         ?? $payloadData['transactionId']       ?? '';
$utilityRef        = $payloadData['utilityRef']        ?? $payloadData['utilityref']          ?? '';

$cleanUtilityRef = strtolower(trim((string)$utilityRef));
$cleanReference  = strtolower(trim((string)$reference));
$statusLower     = strtolower(trim((string)$transactionStatus));

$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);
$safeStatus     = mysqli_real_escape_string($conn, $statusLower);
$safeRawPayload = mysqli_real_escape_string($conn, $rawInput);

try {
    $insertSql = "INSERT INTO azampay_callbacks (utilityref, reference, transaction_status, raw_payload, received_at) 
                  VALUES ('$safeUtilityRef', '$safeReference', '$safeStatus', '$safeRawPayload', NOW())";
    mysqli_query($conn, $insertSql);
} catch (Exception $logEx) {
    error_log("TANCONNECT AUDIT EXCEPTION: Bypassed due to local column structure difference: " . $logEx->getMessage());
}

// ====================================================================
// ⚡ THE FOUR-KEY CASE-INSENSITIVE CROSS-OVER BRIDGE VOUCHER RELEASE
// ====================================================================
if (!empty($cleanUtilityRef) || !empty($cleanReference)) {
    if ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true') {
        
        $searchSql = "SELECT id, assigned_phone, voucher_code FROM wifi_vouchers 
                      WHERE LOWER(reference) = '$safeUtilityRef' 
                         OR LOWER(utilityref) = '$safeReference'
                         OR LOWER(reference) = '$safeReference'
                         OR LOWER(utilityref) = '$safeUtilityRef' 
                      LIMIT 1";
                      
        $searchQuery = mysqli_query($conn, $searchSql);
        
        if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
            $row = mysqli_fetch_assoc($searchQuery);
            $voucherId = $row['id'];
            
            $updateVoucherSql = "UPDATE wifi_vouchers 
                                 SET transactionstatus = 'SUCCESS',
                                     purchased_at = NOW() 
                                 WHERE id = '$voucherId'";
                                    
            if (mysqli_query($conn, $updateVoucherSql)) {
                error_log("TANCONNECT CORE BRIDGE: Linked wifi_vouchers record ID $voucherId updated to SUCCESS.");
                
                // Integrated Hardware Phone MODEM SMS Bridge Execution
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
}
exit();
?>
