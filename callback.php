

<?php
// ====================================================================
// CADDY PROXY COMPATIBILITY LAYER
// ====================================================================
// Forces the FrankenPHP runtime to recognize external cloud gateway headers
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Fallback: If Caddy blocks the raw php://input stream, attempt to read the request buffer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty(file_get_contents('php://input'))) {
    if (isset($HTTP_RAW_POST_DATA)) {
        $incomingRawJson = $HTTP_RAW_POST_DATA;
    }
}
// ====================================================================

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

// 📝 AUDIT LOG TRAIL: Saves incoming data packet variables to your log file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. EXTRACT METRICS HANDLING BOTH FLAT AND NESTED KEY-VALUE MATRIX SCHEMES
// FIXED: Added check for 'transactionStatus' (camelCase matching AzamPay specification)
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
if (isset($paymentData['utilityref'])) {
    $utilityref = $paymentData['utilityref'];
} elseif (isset($paymentData['properties']['utilityref'])) {
    $utilityref = $paymentData['properties']['utilityref'];
}

// FIXED: Extracted message field directly from AzamPay payload safely
$payloadMessage = $paymentData['message'] ?? $paymentData['properties']['message'] ?? 'No message provided';

// Clean out hidden trailing line breaks (\n) or carriage returns completely
$cleanReference  = strtolower(trim(preg_replace('/\s+/', '', (string)$reference)));
$cleanUtilityRef = strtolower(trim(preg_replace('/\s+/', '', (string)$utilityref)));
$statusLower     = strtolower(trim((string)$transactionstatus));

// Flexible status validation check
$isPaymentSuccessful = ($statusLower === 'success' || $statusLower === 'completed' || $statusLower === 'true' || $transactionstatus === true);

// 4. TRANSACTION LOOKUP & TRANSACTIONAL STEP EXECUTION GATEWAY
if (!empty($cleanReference) || !empty($cleanUtilityRef)) {
    
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    
    // ⚡ DUAL-ROW SWEEP LOOKUP MATRIX:
    // Searches both incoming parameters against both database columns simultaneously!
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone 
                                        FROM wifi_vouchers 
                                        WHERE LOWER(reference) = '$safeReference' 
                                           OR LOWER(utilityref) = '$safeReference' 
                                           OR LOWER(reference) = '$safeUtilityRef' 
                                           OR LOWER(utilityref) = '$safeUtilityRef' 
                                        LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $voucherId = $row['id'];
        
        mysqli_begin_transaction($conn);
        try {
            if ($isPaymentSuccessful) {
                // ====================================================================
                // 🟢 CASE A: TRANSACTION SUCCEEDED (Release Voucher PIN)
                // ====================================================================
                // FIXED: Changed $message to $payloadMessage variable reference
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'SUCCESS', 
                                  callback_status = '" . mysqli_real_escape_string($conn, (string)$transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $payloadMessage) . "',
                                  callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                  purchased_at = NOW() 
                              WHERE id = '$voucherId'";
                
                mysqli_query($conn, $updateSql);
                mysqli_commit($conn);
                
                // Integrated Hardware Samsung Phone MODEM SMS Gateway Bridge
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
                
            } else {
                // ====================================================================
                // 🔴 CASE B: AUTOMATED STOCK RECOVERY LOOP (Transaction Failed / Canceled)
                // ====================================================================
                // FIXED: Changed $message to $payloadMessage variable reference
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'AVAILABLE',
                                  assigned_phone = NULL,
                                  reference = NULL,
                                  utilityref = NULL,
                                  access_token = NULL,
                                  token_status = 'PENDING',
                                  callback_status = '" . mysqli_real_escape_string($conn, (string)$transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $payloadMessage) . "',
                                  callback_raw = '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                  purchased_at = NULL 
                              WHERE id = '$voucherId'";
                
                mysqli_query($conn, $updateSql);
                mysqli_commit($conn);
                
                http_response_code(200);
                echo json_encode(["status" => "recovered", "message" => "Voucher inventory pool recycled successfully"]);
                exit();
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("TANCONNECT TRANSACTION ERROR: " . $e->getMessage());
            http_response_code(500);
            exit();
        }
    }
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy API requirements
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
