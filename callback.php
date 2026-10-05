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

// 📝 AUDIT LOG TRAIL: Streams the exact payload object into your Railway app panel view logs
error_log("TANCONNECT INBOUND PAYLOAD DATA: " . $incomingRawJson);

/**
 * Helper function to deeply find any values matching your tracker pattern (e.g., AZM01...)
 */
function findTrackerCodeDeep($array) {
    if (!is_array($array)) return null;
    foreach ($array as $key => $value) {
        if (is_string($value) && stripos($value, 'AZM01') === 0) {
            return trim($value);
        }
        if (is_array($value)) {
            $deepSearch = findTrackerCodeDeep($value);
            if ($deepSearch !== null) return $deepSearch;
        }
    }
    return null;
}

/**
 * Helper function to deeply extract AzamPay's final transaction ID (e.g., AZM08...)
 */
function findAzamPayReferenceDeep($array) {
    if (!is_array($array)) return null;
    foreach ($array as $key => $value) {
        if (is_string($value) && stripos($value, 'AZM08') === 0) {
            return trim($value);
        }
        if (is_array($value)) {
            $deepSearch = findAzamPayReferenceDeep($value);
            if ($deepSearch !== null) return $deepSearch;
        }
    }
    if (isset($array['reference'])) return trim((string)$array['reference']);
    if (isset($array['transactionId'])) return trim((string)$array['transactionId']);
    return null;
}

// 3. EXTRACT TARGET MATRICES DYNAMICALLY
$cleanUtilityRef  = findTrackerCodeDeep($paymentData);
$cleanReference   = findAzamPayReferenceDeep($paymentData);

// Fallback explicit extraction if the deep loop parser handles alternative keys
if (empty($cleanUtilityRef)) {
    $cleanUtilityRef = $paymentData['utilityRef'] ?? $paymentData['utilityref'] ?? $paymentData['externalId'] ?? '';
    $cleanUtilityRef = trim((string)$cleanUtilityRef);
}

$transactionstatus = $paymentData['transactionStatus'] ?? $paymentData['transactionstatus'] ?? '';
$statusLower       = strtolower(trim((string)$transactionstatus));
$callbackMessage   = $paymentData['message'] ?? $paymentData['substatus'] ?? 'No message provided';

$isPaymentSuccessful = ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true' || $transactionstatus === true);

// 4. TRANSACTION LOOKUP & TRANSACTIONAL STEP EXECUTION GATEWAY
if (!empty($cleanUtilityRef)) {
    
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    
    error_log("TANCONNECT LOOKUP DB EXECUTION: Querying where utilityref = '$safeUtilityRef'");
    
    // ⚡ STEP 1: Find row(s) using the handshake tracking token residing in your main 'utilityref' column
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone 
                                        FROM wifi_vouchers 
                                        WHERE utilityref = '$safeUtilityRef'");
    
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        $matchedRowsCount = mysqli_num_rows($searchQuery);
        error_log("TANCONNECT DATABASE MATCH SUCCESS: Found $matchedRowsCount row(s) mapped to this session.");
        
        mysqli_begin_transaction($conn);
        try {
            // 🔄 Loop through all associated vouchers assigned to this single customer checkout action
            while ($row = mysqli_fetch_assoc($searchQuery)) {
                $voucherId = $row['id'];
                
                if ($isPaymentSuccessful) {
                    // ====================================================================
                    // 🟢 CASE A: TRANSACTION SUCCEEDED (Release Voucher PIN)
                    // ====================================================================
                    // Updates transactionstatus to SUCCESS, and populates callback structural log columns
                    $updateSql = "UPDATE wifi_vouchers 
                                  SET transactionstatus = 'SUCCESS', 
                                      callback_utilityref = '$safeUtilityRef',
                                      callback_reference = '$safeReference',
                                      token_status = 'COMPLETED',
                                      purchased_at = NOW() 
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
                    // 🔴 CASE B: AUTOMATED STOCK RECOVERY LOOP (Transaction Failed / Canceled)
                    // ====================================================================
                    $updateSql = "UPDATE wifi_vouchers 
                                  SET transactionstatus = 'AVAILABLE',
                                      assigned_phone = NULL,
                                      reference = NULL,
                                      utilityref = NULL,
                                      access_token = NULL,
                                      token_status = 'PENDING',
                                      callback_utilityref = NULL,
                                      callback_reference = NULL,
                                      purchased_at = NULL 
                                  WHERE id = '$voucherId'";
                    
                    mysqli_query($conn, $updateSql);
                }
            }
            
            mysqli_commit($conn);
            error_log("TANCONNECT STATUS EXECUTION COMPLETE: Successfully updated and committed updates to the database table.");
            
            header("Content-Type: application/json");
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Vouchers columns modified successfully"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT RUNTIME EXCEPTION: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    } else {
        error_log("TANCONNECT LOOKUP FAIL: The value '$cleanUtilityRef' does not exist inside your primary utilityref table column.");
    }
} else {
    error_log("TANCONNECT CRITICAL FAILURE: Both utilityRef and externalId patterns extracted blank from inbound webhook payload JSON packet.");
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy API requirements
header("Content-Type: application/json");
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
