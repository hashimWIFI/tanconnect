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

// Fallback to request superglobals if data transmits via standard web forms
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Outputs payload directly into Railway App Console Logs
error_log("TANCONNECT METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | RAW PAYLOAD: " . $incomingRawJson);

// 3. EXTRACT METRICS HANDLING BOTH CAMELCASE, LOWERCASE AND NESTED SCHEMES
$transactionstatus = '';
if (isset($paymentData['transactionStatus'])) {
    $transactionstatus = $paymentData['transactionStatus'];
} elseif (isset($paymentData['transactionstatus'])) {
    $transactionstatus = $paymentData['transactionstatus'];
} elseif (isset($paymentData['properties']['transactionStatus'])) {
    $transactionstatus = $paymentData['properties']['transactionStatus'];
}

$reference = '';
if (isset($paymentData['reference'])) {
    $reference = $paymentData['reference'];
} elseif (isset($paymentData['properties']['reference'])) {
    $reference = $paymentData['properties']['reference'];
}

$utilityref = '';
if (isset($paymentData['utilityRef'])) {
    $utilityref = $paymentData['utilityRef'];
} elseif (isset($paymentData['utilityref'])) {
    $utilityref = $paymentData['utilityref'];
} elseif (isset($paymentData['properties']['utilityRef'])) {
    $utilityref = $paymentData['properties']['utilityRef'];
}

// Extract message or message alternative safely to avoid undefined variable crashes
$callbackMessage = $paymentData['message'] ?? $paymentData['substatus'] ?? 'No message provided';

// Clean out hidden trailing line breaks (\n) or carriage returns completely
$cleanReference  = strtolower(trim(preg_replace('/\s+/', '', (string)$reference)));
$cleanUtilityRef = strtolower(trim(preg_replace('/\s+/', '', (string)$utilityref)));
$statusLower     = strtolower(trim((string)$transactionstatus));

// Flexible status validation check
$isPaymentSuccessful = ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true' || $transactionstatus === true);

// 4. TRANSACTION LOOKUP & TRANSACTIONAL STEP EXECUTION GATEWAY
if (!empty($cleanUtilityRef)) {
    
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    
    // ⚡ STEP 1: Look up rows using the pre-registered tracking number in your main utilityref column
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone 
                                        FROM wifi_vouchers 
                                        WHERE utilityref = '$safeUtilityRef'");
    
    if ($searchQuery && mysqli_num_rows($searchQuery) > 0) {
        
        mysqli_begin_transaction($conn);
        try {
            // 🔄 Loop through all vouchers linked to this customer checkout push session
            while ($row = mysqli_fetch_assoc($searchQuery)) {
                $voucherId = $row['id'];
                
                if ($isPaymentSuccessful) {
                    // ====================================================================
                    // 🟢 CASE A: TRANSACTION SUCCEEDED (Release Voucher PIN)
                    // ====================================================================
                    // 📝 UPDATED: Explicitly maps incoming webhook payloads into your callback specific matrix columns
                    $updateSql = "UPDATE wifi_vouchers 
                                  SET transactionstatus = 'SUCCESS', 
                                      callback_utilityref = '$safeUtilityRef',
                                      callback_reference = '$safeReference',
                                      callback_status = '" . mysqli_real_escape_string($conn, (string)$transactionstatus) . "',
                                      callback_message = '" . mysqli_real_escape_string($conn, $callbackMessage) . "',
                                      callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                      purchased_at = NOW() 
                                  WHERE id = '$voucherId'";
                    
                    mysqli_query($conn, $updateSql);
                    
                    // Integrated Hardware Phone MODEM SMS Gateway Bridge
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
                                      callback_status = '" . mysqli_real_escape_string($conn, (string)$transactionstatus) . "',
                                      callback_message = '" . mysqli_real_escape_string($conn, $callbackMessage) . "',
                                      callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                      purchased_at = NULL 
                                  WHERE id = '$voucherId'";
                    
                    mysqli_query($conn, $updateSql);
                }
            }
            
            mysqli_commit($conn);
            
            header("Content-Type: application/json");
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Callback matrix written successfully"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT EXCEPTION: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    } else {
        error_log("TANCONNECT LOOKUP FAIL: No records matched initial tracker utilityref: '$cleanUtilityRef'");
    }
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy API requirements
header("Content-Type: application/json");
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
