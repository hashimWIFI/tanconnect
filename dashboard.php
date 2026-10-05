<?php
// ====================================================================
// TANCONNECT LIVE AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production protection: shields credentials from outside viewing

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

// 2. CAPTURE THE RAW INCOMING FLAT JSON PAYLOAD STREAM FROM AZAMPAY
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

// Fallback to request superglobals if data transmits via standard web forms
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Saves the flat raw metrics payload to your text log history file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. EXTRACT FLAT PARAMETERS CORE MATCHING OFFICIAL AZAMPAY DATA SPECIFICATION
$transactionstatus = isset($paymentData['transactionstatus']) ? strtolower(trim((string)$paymentData['transactionstatus'])) : '';
$reference         = isset($paymentData['reference'])         ? trim((string)$paymentData['reference'])         : '';
$utilityref        = isset($paymentData['utilityref'])        ? trim((string)$paymentData['utilityref'])        : '';
$message           = isset($paymentData['message'])           ? trim((string)$paymentData['message'])           : 'No message provided';

// Clean out hidden trailing line breaks (\n) or carriage returns completely
$cleanReference  = trim(preg_replace('/\s+/', '', $reference));  // AzamPay's Tracking ID ("01a0d...") [image_EJLeLi.png]
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref)); // Your system ID ("NITW-...") [image_EJLeLi.png]

$isPaymentSuccessful = ($transactionstatus === 'success' || $transactionstatus === 'completed' || $transactionstatus === 'true');

// 4. TRANSACTION LOOKUP & EXECUTION ENGINE
if (!empty($cleanReference) || !empty($cleanUtilityRef)) {
    
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    
    // ⚡ THE COMPLIANT TARGET MATCH LOCK:
    // Looks up the row by matching your system tracking tags to your database columns exactly!
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone 
                                        FROM wifi_vouchers 
                                        WHERE reference = '$safeUtilityRef' 
                                           OR utilityref = '$safeReference' 
                                        LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $voucherId = $row['id'];
        
        mysqli_begin_transaction($conn);
        try {
            if ($isPaymentSuccessful) {
                // ====================================================================
                // 🟢 CASE A: TRANSACTION SUCCEEDED (Instantly Release Voucher)
                // ====================================================================
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'SUCCESS', 
                                  callback_status = '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $message) . "',
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
                echo json_encode(["status" => "success", "message" => "Voucher unlocked cleanly"]);
                exit();
                
            } else {
                // ====================================================================
                // 🔴 CASE B: AUTOMATED STOCK RECOVERY LOOP (Transaction Failed / Canceled)
                // ====================================================================
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'AVAILABLE', // Recycles voucher back to pool instantly
                                  assigned_phone = NULL,           // Wipes user mobile numbers
                                  reference = NULL,                // Wipes internal tracking code
                                  utilityref = NULL,               // Wipes AzamPay's identifier
                                  token_status = 'PENDING',        // Restores default handshake state
                                  callback_status = '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $message) . "',
                                  purchased_at = NULL 
                              WHERE id = '$voucherId'";
                
                mysqli_query($conn, $updateSql);
                mysqli_commit($conn);
                
                http_response_code(200);
                echo json_encode(["status" => "recovered", "message" => "Payment failed flag recorded. Inventory pool recycled successfully."]);
                exit();
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

// Always acknowledge webhook receipt with a clean 200 OK block to satisfy integration constraints
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
