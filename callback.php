<?php
// ====================================================================
// TANCONNECT HIGH-SPEED ASYNCHRONOUS WEBHOOK LISTENER ('callback.php')
// ====================================================================

// 1. TERMINATE THE OUTBOUND CONNECTION INSTANTLY TO BYPASS AZAMPAY'S 429 TIMEOUT
// This tells AzamPay's proxy we received the data immediately, unblocking their loop!
ob_start();
header("Content-Type: application/json");
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Webhook payload successfully received by TanConnect"
]);

// Get the exact length of the output string and flush the buffer to close the connection
$size = ob_get_length();
header("Content-Length: $size");
header("Connection: close");
ob_end_flush();
ob_flush();
flush();

// ⚡ CONNECTION IS NOW CLOSED WITH AZAMPAY. 
// The code below executes silently in the background on your server!

if (session_status() === PHP_SESSION_NONE) {
    session_write_close(); // Prevent session locking delays
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 2. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("TANCONNECT ASYNC ERROR: Database Connection Failed");
    exit();
}

// 3. CAPTURE THE INBOUND JSON PAYLOAD FROM THE RAW INPUT CHANNEL
$rawInput = file_get_contents('php://input');
$payloadData = json_decode($rawInput, true);

if (empty($payloadData) || !is_array($payloadData)) {
    $payloadData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 HISTORICAL REPORT LOG: Writes data to file inside your container workspace
file_put_contents('azampay_delivery_report.txt', date('[Y-m-d H:i:s] ') . "PAYLOAD: " . json_encode($payloadData) . PHP_EOL, FILE_APPEND);

// 4. EXTRACT MAPPING TARGET METRICS
$transactionStatus = $payloadData['transactionStatus'] ?? $payloadData['transactionstatus'] ?? 'UNKNOWN';
$reference         = $payloadData['reference']         ?? $payloadData['transactionId']       ?? '';
$utilityRef        = $payloadData['utilityRef']        ?? $payloadData['utilityref']          ?? '';

$cleanUtilityRef = strtolower(trim((string)$utilityRef));        // Your system order ID ("nitw-...")
$cleanReference  = strtolower(trim((string)$reference));         // AzamPay's network ID ("01a0d...")
$statusLower     = strtolower(trim((string)$transactionStatus));

$safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
$safeReference  = mysqli_real_escape_string($conn, $cleanReference);

// 5. THE FOUR-KEY CASE-INSENSITIVE CROSS-OVER CORE VOUCHER RELEASE
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
            
            // Securely logs customer details and sets tracking status to SUCCESS
            $updateVoucherSql = "UPDATE wifi_vouchers 
                                 SET transactionstatus = 'SUCCESS',
                                     purchased_at = NOW() 
                                 WHERE id = '$voucherId'";
                                    
            if (mysqli_query($conn, $updateVoucherSql)) {
                error_log("TANCONNECT ASYNC BRIDGE: Linked record ID $voucherId updated to SUCCESS.");
                
                // Trigger your hardware Samsung Phone MODEM SMS Gateway Processor
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
