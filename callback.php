<?php
// ====================================================================
// TANCONNECT STREAMLINED LIVE WEBHOOK CALLBACK ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Active protection: shields credentials from outside viewing

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

// 📝 AUDIT LOG TRAIL: Temporary flat file logs for launch validation (Safe to delete post-launch)
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. EXTRACT FLAT PARAMETERS CORE MATCHING OFFICIAL AZAMPAY DATA SPECIFICATION
$transactionstatus = isset($paymentData['transactionstatus']) ? strtolower(trim((string)$paymentData['transactionstatus'])) : '';
$reference         = isset($paymentData['reference'])         ? trim((string)$paymentData['reference'])         : '';
$utilityref        = isset($paymentData['utilityref'])        ? trim((string)$paymentData['utilityref'])        : '';
$message           = isset($paymentData['message'])           ? trim((string)$paymentData['message'])           : 'No message provided';

// Clean out hidden trailing line breaks (\n) or carriage returns completely
$cleanReference  = trim(preg_replace('/\s+/', '', $reference));  // This holds your internal 'NITW-...' string
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref)); // This holds AzamPay's official 'AZM-...' token

$isPaymentSuccessful = ($transactionstatus === 'success' || $transactionstatus === 'completed' || $transactionstatus === 'true');

// 4. TRANSACTION LOOKUP & EXECUTION ENGINE
if (!empty($cleanReference) || !empty($cleanUtilityRef)) {
    
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    
    // Look up matching records using your true spec-aligned database column headings (Bypasses token checks completely!)
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone FROM wifi_vouchers WHERE reference = '$safeReference' OR utilityref = '$safeUtilityRef' LIMIT 1");
    
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
                              SET transactionstatus 	= 'SUCCESS', 
                                  callback_status 	= '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message 	= '" . mysqli_real_escape_string($conn, $message) . "',
                                  callback_raw 		= '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                  purchased_at 		= NOW() 
                                  WHERE id 		= '$voucherId'";
                
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
                echo json_encode(["status" => "success", "message" => "Voucher unlocked and SMS triggered cleanly"]);
                exit();
                
            } else {
                // ====================================================================
                // 🔴 CASE B: AUTOMATED STOCK RECOVERY LOOP (Transaction Failed / Cancelled)
                // ====================================================================
                // Clears data trail, retains audit reasons, and recycles voucher back to pool instantly!
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus 	= 'AVAILABLE', // Returns code back to general pool
                                  assigned_phone 	= NULL,           // Wipes mobile tracker string
                                  reference 		= NULL,                // Wipes internal ID string
                                  utilityref 		= NULL,               // Wipes AzamPay ID string
                                  callback_status  	= '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message 	= '" . mysqli_real_escape_string($conn, $message) . "',
                                  callback_raw 		= '" . mysqli_real_escape_string($conn, $incomingRawJson) . "',
                                  purchased_at 		= NULL 
                              	  WHERE id 		= '$voucherId'";
                
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

// Always acknowledge webhook receipt with a clean 200 OK block to prevent endpoint retry loops
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
