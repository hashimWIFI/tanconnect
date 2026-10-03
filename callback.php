<?php
// ====================================================================
// TANCONNECT LIVE AUTOMATED WEBHOOK CALLBACK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production protection: shields error metrics from the outside world

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    http_response_code(500);
    exit();
}

// 2. CAPTURE METHOD FOR INCOMING BODY TRAFFIC
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

// Fallback to request collections if transmitted via alternative headers
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Append the parsed transaction details into your text file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . " | PAYLOAD: " . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

// 🛑 SECURITY GATE A: If AzamPay sends a GET heartbeat verification ping, 
// respond with 200 OK to clear the handshake but skip database changes entirely!
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    http_response_code(200);
    echo json_encode(["status" => "handshake_verified", "message" => "GET request ignored for security safety"]);
    exit();
}

// 3. EXTRACT METRICS CORE MATCHING OFFICIAL AZAMPAY SIGNATURE SCHEME
$transactionstatus = '';
if (isset($paymentData['transactionstatus'])) {
    $transactionstatus = strtolower(trim($paymentData['transactionstatus']));
} elseif (isset($paymentData['properties']['transactionstatus'])) {
    $transactionstatus = strtolower(trim($paymentData['properties']['transactionstatus']));
}

$externalreference = '';
if (isset($paymentData['externalreference'])) {
    $externalreference = trim($paymentData['externalreference']);
} elseif (isset($paymentData['properties']['externalreference'])) {
    $externalreference = trim($paymentData['properties']['externalreference']);
}

$utilityref = '';
if (isset($paymentData['utilityref'])) {
    $utilityref = $paymentData['utilityref'];
} elseif (isset($paymentData['properties']['utilityref'])) {
    $utilityref = $paymentData['properties']['utilityref'];
}

$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref));
$cleanReference  = trim(preg_replace('/\s+/', '', $externalreference));

// 🛑 SECURITY GATE B: The Absolute Payment Barrier
// The query is only allowed to proceed if AzamPay explicitly sends an authentic 'success' status code string!
if ($transactionstatus === 'success' || $transactionstatus === 'completed') {
    
    // Look up matching records using your true database column headings
    $searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone FROM wifi_vouchers WHERE utilityref = '" . mysqli_real_escape_string($conn, $cleanUtilityRef) . "' OR reference = '" . mysqli_real_escape_string($conn, $cleanReference) . "' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $row = mysqli_fetch_assoc($searchQuery);
        $voucherId = $row['id'];
        
        mysqli_begin_transaction($conn);
        try {
            // Alters the status column inside the MySQL table row to SUCCESS securely
            $updateSql = "UPDATE wifi_vouchers 
                          SET transactionstatus = 'SUCCESS', 
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
            echo json_encode(["status" => "success", "message" => "MySQL table updated cleanly"]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

// Fallback response for failed, canceled, or declined payments
http_response_code(200); 
echo json_encode(["status" => "ignored", "message" => "Non-success payload skipped successfully"]);
?>
