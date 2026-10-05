<?php
// ====================================================================
// TANCONNECT DUO-SPEC AUTOMATED CALLBACK ENGINE ('callback.php')
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
    error_log("TANCONNECT WEBHOOK ERROR: Database Connection Failed");
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
error_log("TANCONNECT POST-PIN INBOUND PAYLOAD: " . $incomingRawJson);

// 3. EXTRACT INCOMING METRICS FROM THE WEBHOOK PAYLOAD
$transactionstatus = $paymentData['transactionStatus'] ?? $paymentData['transactionstatus'] ?? '';
$statusLower       = strtolower(trim((string)$transactionstatus));

$utilityref = $paymentData['utilityRef'] ?? $paymentData['utilityref'] ?? $paymentData['externalId'] ?? '';
$azampay_reference = $paymentData['reference'] ?? $paymentData['transactionId'] ?? '';

// --- FALLBACK REGEX PARSER ---
// If the payload structure shifts nested keys, scrape the raw JSON string directly for safety
if (empty($utilityref) && preg_match('/(AZM01[a-zA-Z0-9\-_]+)/i', $incomingRawJson, $matches)) {
    $utilityref = $matches[1];
}
if (empty($azampay_reference) && preg_match('/(AZM08[a-zA-Z0-9\-_]+)/i', $incomingRawJson, $matches)) {
    $azampay_reference = $matches[1];
}

$cleanUtilityRef = trim((string)$utilityref);
$cleanReference  = trim((string)$azampay_reference);

// Check if status evaluates to success
$isPaymentSuccessful = ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true');

// 4. TRANSACTION LOOKUP MATRIX USING PRE-SAVED ENTRIES
// We find the record using either the pre-saved baseline utilityref or checkout reference codes
if (!empty($cleanUtilityRef) || !empty($cleanReference)) {
    
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    
    error_log("TANCONNECT RUNNING DB SEARCH: Handshake search criteria -> Utility: '$safeUtilityRef' | Ref: '$safeReference'");
    
    $searchQuery = mysqli_query($conn, "SELECT id, assigned_phone, voucher_code 
                                        FROM wifi_vouchers 
                                        WHERE utilityref = '$safeUtilityRef' 
                                           OR reference = '$safeReference'
                                           OR utilityref = '$safeReference'");
    
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $matchedCount = mysqli_num_rows($searchQuery);
        error_log("TANCONNECT ROW MATCH SUCCESS: Found $matchedCount reserved voucher records.");
        
        mysqli_begin_transaction($conn);
        try {
            // 🔄 Loop through all associated vouchers assigned to this customer checkout push session
            while ($row = mysqli_fetch_assoc($searchQuery)) {
                $voucherId = $row['id'];
                
                if ($isPaymentSuccessful) {
                    // ====================================================================
                    // 🟢 CASE A: PAYMENT VERIFIED SUCCESSFUL
                    // ====================================================================
                    // Updates ONLY transactionstatus to SUCCESS, and directs input values to callback specific columns
                    $updateSql = "UPDATE wifi_vouchers 
                                  SET transactionstatus = 'SUCCESS', 
                                      callback_utilityref = '$safeUtilityRef',
                                      callback_reference = '$safeReference'
                                  WHERE id = '$voucherId'";
                    
                    mysqli_query($conn, $updateSql);
                    
                    // Integrated Hardware Phone MODEM SMS Gateway Bridge execution loop
                    define('TANCONNECT_SECURE_PASS', true);
                    $customer_phone = $row['assigned_phone'] ?? '';
                    $voucherCode    = $row['voucher_code'] ?? '';
                    
                    if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                        ob_start();
                        include('sms_processor.php');
                        ob_end_clean();
                    }
                    
                } else {
                    // ====================================================================
                    // 🔴 CASE B: PAYMENT TRIPPED / FAILED / CANCELED
                    // ====================================================================
                    // Releases voucher seat back to pool if transaction dropped
                    $updateSql = "UPDATE wifi_vouchers 
                                  SET transactionstatus = 'FAIL',
                                      callback_utilityref = '$safeUtilityRef',
                                      callback_reference = '$safeReference'
                                  WHERE id = '$voucherId'";
                    
                    mysqli_query($conn, $updateSql);
                }
            }
            
            mysqli_commit($conn);
            error_log("TANCONNECT CALL-PROCESSING RECORD COMPLETE: Successfully saved callback entries.");
            
            header("Content-Type: application/json");
            http_response_code(200);
            echo json_encode(["status" => "processed", "message" => "Voucher transaction status evaluated successfully."]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT EXCEPTION THROWN: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    } else {
        error_log("TANCONNECT LOOKUP MISMATCH: No 'ASSIGNED' records matching criteria found.");
    }
} else {
    error_log("TANCONNECT FAULT: Webhook fired but both extracted parameters returned completely empty.");
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy API requirements
header("Content-Type: application/json");
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
