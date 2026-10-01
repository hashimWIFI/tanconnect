<?php
// ====================================================================
// TANCONNECT AUTOMATED ALIGNED WEBHOOK LISTENER ('callback.php')
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

// 2. CAPTURE THE RAW INCOMING POST DATA DISPATCHED BY AZAMPAY
$incomingRawJson = file_get_contents('php://input');
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . $incomingRawJson . PHP_EOL, FILE_APPEND);

$paymentData = json_decode($incomingRawJson, true);

if (!$paymentData) {
    http_response_code(400);
    exit();
}

// 3. EXTRACT CORE METRICS MATCHING AZAMPAY WEBHOOK SPECIFICATIONS
$status = '';
if (isset($paymentData['transactionstatus'])) {
    $status = strtolower(trim($paymentData['transactionstatus']));
} elseif (isset($paymentData['properties']['transactionstatus'])) {
    $status = strtolower(trim($paymentData['properties']['transactionstatus']));
}

$reference = '';
if (isset($paymentData['reference'])) {
    $reference = trim($paymentData['reference']);
} elseif (isset($paymentData['properties']['reference'])) {
    $reference = trim($paymentData['properties']['reference']);
}

$utilityref = '';
if (isset($paymentData['utilityref'])) {
    $utilityref = $paymentData['utilityref'];
} elseif (isset($paymentData['properties']['utilityref'])) {
    $utilityref = $paymentData['properties']['utilityref'];
}

// Strip hidden lines (\n) and carriage returns cleanly from the incoming reference strings
$cleanUtilityRef = trim(preg_replace('/\s+/', '', $utilityref));
$cleanReference  = trim(preg_replace('/\s+/', '', $reference));

// 4. VERIFY LOGIC AND UPDATE RENAMED MYSQL COLUMNS
if (($status === 'success' || $status === 'completed') && (!empty($cleanUtilityRef) || !empty($cleanReference))) {
    
    // ⚡ NEW SCHEMA QUERY: Looks up rows using your newly renamed table columns!
    $searchQuery = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE utilityref = '$cleanUtilityRef' OR reference = '$cleanReference' LIMIT 1");
    
    if (mysqli_num_rows($searchQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($searchQuery);
        $voucherId  = $voucherRow['id'];
        
        mysqli_begin_transaction($conn);
        try {
            // ⚡ NEW SCHEMA UPDATE: Modifies your newly named database columns cleanly
            $updateSql = "UPDATE wifi_vouchers 
                          SET transactionstatus = 'SUCCESS', 
                              reference = '$cleanReference', 
                              purchased_at = NOW() 
                          WHERE id = '$voucherId'";
            
            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
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
