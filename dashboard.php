<?php
// ====================================================================
// TANCONNECT PRO-SPEC AUTOMATED CALLBACK ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production protection: blocks credential exposure

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

// 2. UNIVERSAL METHOD INTAKE CAPTURE MATRIX
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

// ⚡ UNIVERSAL BRIDGE: If the body payload is empty, harvest parameters from the URL parameter headers!
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = array_merge($_GET, $_POST, $_REQUEST);
}

// 📝 AUDIT LOG TRAIL: Records the method and parameters to verify structure arrival
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 3. EXTRACT METRICS FROM ALL SOURCES COMPLIANT WITH THE 3-PARAM RULE
$transactionstatus = isset($paymentData['transactionstatus']) ? strtolower(trim((string)$paymentData['transactionstatus'])) : '';
$reference         = isset($paymentData['reference'])         ? trim((string)$paymentData['reference'])         : '';
$utilityref        = isset($paymentData['utilityref'])        ? trim((string)$paymentData['utilityref'])        : '';
$message           = isset($paymentData['message'])           ? trim((string)$paymentData['message'])           : 'No message provided';

// Clean out hidden trailing line breaks (\n) or carriage returns completely
$cleanReference  = trim(preg_replace('/\s+/', '', $reference));  // AzamPay's Tracking ID ("01a0d...")
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref)); // Your system ID ("NITW-...")

$isPaymentSuccessful = ($transactionstatus === 'success' || $transactionstatus === 'completed' || $transactionstatus === 'true');

// 4. DUAL-ROW SWEEP LOOKUP MATRIX
if (!empty($cleanReference) || !empty($cleanUtilityRef)) {
    
    $safeReference  = mysqli_real_escape_string($conn, $cleanReference);
    $safeUtilityRef = mysqli_real_escape_string($conn, $cleanUtilityRef);
    
    // Look up rows using your true spec-aligned database column headings
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
                // 🟢 CASE A: TRANSACTION SUCCEEDED (Release Voucher PIN)
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
                // 🔴 CASE B: AUTOMATED STOCK RECOVERY LOOP (Transaction Failed)
                // ====================================================================
                $updateSql = "UPDATE wifi_vouchers 
                              SET transactionstatus = 'AVAILABLE',
                                  assigned_phone = NULL,
                                  reference = NULL,
                                  utilityref = NULL,
                                  token_status = 'PENDING',
                                  callback_status = '" . mysqli_real_escape_string($conn, $transactionstatus) . "',
                                  callback_message = '" . mysqli_real_escape_string($conn, $message) . "',
                                  purchased_at = NULL 
                              WHERE id = '$voucherId'";
                
                mysqli_query($conn, $updateSql);
                mysqli_commit($conn);
                
                http_response_code(200);
                echo json_encode(["status" => "recovered", "message" => "Inventory recycled cleanly"]);
                exit();
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

// Always acknowledge webhook receipt with a clean 200 OK block
http_response_code(200); 
echo json_encode(["success" => true, "message" => "Callback request handled successfully"]);
?>
