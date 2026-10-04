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

// 2. CAPTURE THE RAW INCOMING FLAT JSON PAYLOAD STREAM
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

// Fallback to request superglobals if data transmits via standard web forms
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Saves the flat raw metrics payload to your text log history file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | FLAT_PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. EXTRACT FLAT PARAMETERS CORE MATCHING OFFICIAL AZAMPAY SPECIFICATION
$transactionstatus = isset($paymentData['transactionstatus']) ? trim((string)$paymentData['transactionstatus']) : '';
$reference         = isset($paymentData['reference'])         ? trim((string)$paymentData['reference'])         : '';
$utilityref        = isset($paymentData['utilityref'])        ? trim((string)$paymentData['utilityref'])        : '';
$message           = isset($paymentData['message'])           ? trim((string)$paymentData['message'])           : 'No message provided';

// Wipes out hidden trailing line breaks (\n) or carriage returns completely
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref));
$cleanReference  = trim(preg_replace('/\s+/', '', $reference));

$statusLower = strtolower($transactionstatus);
$isPaymentSuccessful = ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true');

if (!empty($cleanUtilityRef) || !empty($cleanReference)) {
    
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    
    // Look up rows using your true database column headings character-for-character
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone, reference, token_status FROM wifi_vouchers WHERE utilityref = '$safeUtilityRef' OR reference = '$safeReference' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $voucherId = $row['id'];
        $tokenCheck = strtolower(trim((string)$row['token_status']));
        
        // 🛑 SECURITY GATE A: Enforce the isolated Stage 1 token check constraint
        if ($tokenCheck !== 'true') {
            error_log("TANCONNECT SECURITY BLOCK: Refused callback update because token_status was not true.");
            http_response_code(200);
            echo json_encode(["status" => "blocked", "message" => "Stage 1 authentication constraint active"]);
            exit();
        }
        
        // Preserve initial handshake tracking token safely if incoming reference is blank
        $finalReference = !empty($cleanReference) ? $cleanReference : $row['reference'];
        
        mysqli_begin_transaction($conn);
        try {
            if ($isPaymentSuccessful) {
                // ⚡ STAGE 3 SUCCESS MATRIX: Wallet deduction verified! 
                // Updates standard status to SUCCESS and explicitly logs all arriving parameters for cross-audit reference!
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'SUCCESS', 
                                  reference = '" . mysqli_real_escape_string($conn, $finalReference) . "',
                                  callback_status = '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $message) . "',
                                  callback_utilityref = '$safeUtilityRef',
                                  callback_reference = '$safeReference',
                                  callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
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
                echo json_encode(["status" => "success", "message" => "Voucher unlocked cleanly and Stage 3 parameters logged"]);
                exit();
            } else {
                // ⚡ STAGE 3 FAILURE MATRIX: Wallet deduction failed (Canceled / Wrong PIN / Insufficient Balance).
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'FAILED', 
                                  callback_status = '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $message) . "',
                                  callback_utilityref = '$safeUtilityRef',
                                  callback_reference = '$safeReference',
                                  callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "' 
                              WHERE id = '$voucherId'";
                
                mysqli_query($conn, $updateSql);
                mysqli_commit($conn);
                
                http_response_code(200);
                echo json_encode(["status" => "failed", "message" => "Payment failed state captured inside database"]);
                exit();
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

// Always acknowledge receipt of the packet with a 200 OK json block to satisfy integration constraints
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback received successfully"]);
?>
