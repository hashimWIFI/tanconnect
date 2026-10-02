<?php
// ====================================================================
// TANCONNECT PRO-SPEC ALIGNED WEBHOOK LISTENER ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); 

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

// 2. CAPTURE THE LIVE WEBHOOK DISPATCHED BY AZAMPAY
$incomingRawJson = file_get_contents('php://input');
$paymentData = json_decode($incomingRawJson, true);

// Fallback to request superglobals if transmitted via alternative headers
if (empty($paymentData) || !is_array($paymentData)) {
    $paymentData = !empty($_POST) ? $_POST : $_REQUEST;
}

// 📝 AUDIT LOG TRAIL: Saves the actual parsed dataset to verify parameter arrival
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . json_encode($paymentData) . PHP_EOL, FILE_APPEND);

if (empty($paymentData)) {
    http_response_code(400);
    exit();
}

// 3. EXTRACT METRICS CORE MATCHING OFFICIAL AZAMPAY SIGNATURE SCHEME
// Safely maps both root levels and nested wrapper object scenarios
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

// Clean out hidden trailing line breaks (\n) or spaces from the string keys safely
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref));
$cleanReference  = trim(preg_replace('/\s+/', '', $externalreference));

// 4. VERIFY LOGIC AND UPDATE RENAMED MYSQL COLUMNS
if (($transactionstatus === 'success' || $transactionstatus === 'completed') && (!empty($cleanUtilityRef) || !empty($cleanReference))) {
    
    // Look up matching records using your newly renamed database table columns!
    $searchQuery = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE utilityref = '" . mysqli_real_escape_string($conn, $cleanUtilityRef) . "' OR reference = '" . mysqli_real_escape_string($conn, $cleanReference) . "' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($searchQuery);
        $voucherId  = $voucherRow['id'];
        
       mysqli_begin_transaction($conn);
try {
    // ⚡ FIXED: Targets the update query explicitly to match the exact active reference keys
    $updateSql = "UPDATE wifi_vouchers 
                  SET transactionstatus = 'SUCCESS', 
                      purchased_at = NOW() 
                  WHERE utilityref = '" . mysqli_real_escape_string($conn, $cleanUtilityRef) . "' 
                     OR reference = '" . mysqli_real_escape_string($conn, $cleanReference) . "'";
    
    mysqli_query($conn, $updateSql);
    mysqli_commit($conn);
    
    error_log("TANCONNECT WEBHOOK SUCCESS: Transaction successfully updated to SUCCESS!");
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Voucher unlocked cleanly"]);
    exit();
    
} catch (Exception $e) {

            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

http_response_code(200); 
echo json_encode(["status" => "ignored"]);
?>
