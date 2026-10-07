<?php
// ====================================================================
// TANCONNECT LIVE AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

header("Content-Type: application/json");

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

// 2. CAPTURE THE RAW INCOMING WEBHOOK STREAM (AS SENT TO JOSEPH'S DESK)
$rawInput = file_get_contents('php://input');
$payloadData = json_decode($rawInput, true);

if (empty($payloadData) || !is_array($payloadData)) {
    $payloadData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 3. EXTRACT METRICS HANDLING DUAL CASE SCENARIOS
$transactionStatus = $payloadData['transactionStatus'] ?? $payloadData['transactionstatus'] ?? 'Not Provided';
$reference         = $payloadData['reference']         ?? $payloadData['transactionId']       ?? 'Not Provided';
$utilityRef        = $payloadData['utilityRef']        ?? $payloadData['utilityref']          ?? 'Not Provided';
$message           = $payloadData['message']           ?? 'No message';

// 📝 AUDIT LOG TRAIL: Creates the clean human-readable text block you shared with Joseph
$logEntry = "========================================\n";
$logEntry .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logEntry .= "STATUS EXTRACTED: " . $transactionStatus . "\n";
$logEntry .= "REFERENCE MATCHED: " . $reference . "\n";
$logEntry .= "UTILITY REF: " . $utilityRef . "\n";
$logEntry .= "PROVIDER MESSAGE: " . $message . "\n";
$logEntry .= "RAW JSON PAYLOAD: " . $rawInput . "\n";
$logEntry .= "========================================\n";

file_put_contents('azampay_delivery_report.txt', $logEntry, FILE_APPEND);

// Normalize text parameters to lowercase strings to survive case shifts safely
$cleanUtilityRef = strtolower(trim((string)$utilityRef));
$cleanReference  = strtolower(trim((string)$reference));
$statusLower     = strtolower(trim((string)$transactionStatus));

$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);

// ====================================================================
// ⚡ THE FOUR-KEY CASE-INSENSITIVE CROSS-OVER BRIDGE VOUCHER RELEASE
// ====================================================================
if (!empty($cleanUtilityRef) || !empty($cleanReference)) {
    if ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true') {
        
        // Sweeps both arriving payload parameters across both database columns to guarantee a match
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
            
            // Updates voucher row to SUCCESS instantly
            $updateVoucherSql = "UPDATE wifi_vouchers 
                                 SET transactionstatus = 'SUCCESS',
                                     purchased_at = NOW() 
                                 WHERE id = '$voucherId'";
                                    
            if (mysqli_query($conn, $updateVoucherSql)) {
                error_log("TANCONNECT CORE BRIDGE: Linked wifi_vouchers record ID $voucherId updated to SUCCESS.");
                
                // Integrated Hardware Phone MODEM SMS Bridge
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

// 4. RETURN THE CONFIRMATION RESPONSE TO SATISFY AZAMPAY'S INTERFACE RULES
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Webhook payload successfully extracted and logged by TanConnect"
]);
exit();
?>
