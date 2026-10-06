<?php
// ====================================================================
// TANCONNECT DIRECT M-PESA EXTRACTION ENGINE ('mpesa_listener.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Active production safety protection

// 1. DATABASE CONNECTIVITY
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("M-PESA EXTRACTOR ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}

// 2. INTERCEPT THE INBOUND RAW JSON DISPATCH STREAM FROM THE APP ENGINE
$incomingRawJson = file_get_contents('php://input');
$smsGateData = json_decode($incomingRawJson, true);

// Extract parameters from standard request streams if JSON fails
if (empty($smsGateData) || !is_array($smsGateData)) {
    $smsGateData = array_merge($_GET, $_POST, $_REQUEST);
}

// 📝 AUDIT TRAIL LOG: Saves the raw inbound text stream to check pattern alignment strings
file_put_contents('mpesa_sms_audit_log.txt', date('[Y-m-d H:i:s] ') . "RAW_STREAM: " . json_encode($smsGateData) . PHP_EOL, FILE_APPEND);

// 3. SECURE PARAMETER EXTRACTION
$sender = '';
$messageText = '';

// Capture variables handles either the flat root schema or nested payload wrappers
if (isset($smsGateData['payload'])) {
    $sender      = $smsGateData['payload']['sender'] ?? '';
    $messageText = $smsGateData['payload']['message'] ?? $smsGateData['payload']['text'] ?? '';
} else {
    $sender      = $smsGateData['sender'] ?? $smsGateData['from'] ?? '';
    $messageText = $smsGateData['message'] ?? $smsGateData['text'] ?? '';
}

$cleanSender   = strtoupper(trim((string)$sender));
$cleanSmsText  = trim((string)$messageText);

// 🛑 FIREWALL GUARD: Only process actual confirmation receipts containing cash deposit markers
if (strpos($cleanSender, 'M-PESA') === false && strpos(strtoupper($cleanSmsText), 'UMEPOKEA TSHS') === false) {
    header("Content-Type: application/json");
    http_response_code(200);
    echo json_encode(["status" => "ignored", "message" => "Text does not contain valid M-Pesa transaction identifiers"]);
    exit();
}

// ====================================================================
// 🔍 4. THE EXTRACTION MATRIX (REGULAR EXPRESSION ENGINE)
// ====================================================================
$mpesaTxId       = '';
$extractedAmount = 0;
$customerPhone   = '';

// A. Extract Transaction ID (Grabs 'DEU9614W7C' - the first word before 'imethibitishwa')
if (preg_match('/^([A-Z0-9]+)\s+imethibitishwa/i', $cleanSmsText, $matches)) {
    $mpesaTxId = trim($matches[1]);
}

// B. Extract Cash Amount Value (Grabs '1500' cleanly out of 'Tshs 1,500.00')
if (preg_match('/Umepokea\s+Tshs\s+([\d,]+\.\d{2})/i', $cleanSmsText, $matches)) {
    $extractedAmount = intval(str_replace(',', '', $matches[1]));
}

// C. Extract Sender Mobile Number (Grabs '255778343646' sitting inside the text brackets ': 255778343646 -')
if (preg_match('/:\s+(\d+)\s+-/i', $cleanSmsText, $matches)) {
    $customerPhone = trim($matches[1]);
}

// 5. DATABASE INTEGRATION LAYER
if (!empty($mpesaTxId) && $extractedAmount > 0) {
    
    $safeTxId   = mysqli_real_escape_string($conn, $mpesaTxId);
    $safePhone  = mysqli_real_escape_string($conn, $customerPhone);
    $priceTier  = (string)$extractedAmount;
    
    // Check if this specific network ID has already been fulfilled to prevent duplicate text reuse
    $dupCheck = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE mpesa_trans_id = '$safeTxId' LIMIT 1");
    if (mysqli_num_rows($dupCheck) > 0) {
        http_response_code(200);
        echo json_encode(["status" => "duplicate", "message" => "Voucher already released for this tracking token"]);
        exit();
    }
    
    // Scan pool inventory for the first available card matching this category amount
    $voucherQuery = mysqli_query($conn, "SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = '$priceTier' AND transactionstatus = 'AVAILABLE' LIMIT 1");
    
    if (mysqli_num_rows($voucherQuery) > 0) {
        $voucherRow = mysqli_fetch_assoc($voucherQuery);
        $voucherId  = $voucherRow['id'];
        $voucherCodeString = $voucherRow['voucher_code'];
        
        mysqli_begin_transaction($conn);
        try {
            // ⚡ EXECUTION BRIDGE: Shifts the tracking record status cleanly to SUCCESS!
            $updateSql = "UPDATE wifi_vouchers 
                          SET transactionstatus = 'SUCCESS',
                              assigned_phone = '$safePhone',
                              mpesa_trans_id = '$safeTxId',
                              purchased_at = NOW() 
                          WHERE id = '$voucherId'";
            
            mysqli_query($conn, $updateSql);
            mysqli_commit($conn);
            
            // Integrated Hardware Samsung Phone MODEM SMS Gateway Receipt Dispatch
            define('TANCONNECT_SECURE_PASS', true);
            $customer_phone = $customerPhone;
            $voucherCode    = $voucherCodeString;
            
            if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                ob_start();
                include('sms_processor.php');
                ob_end_clean();
            }
            
            header("Content-Type: application/json");
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "M-Pesa data extracted. Voucher state updated to SUCCESS."]);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            http_response_code(500);
            exit();
        }
    }
}

header("Content-Type: application/json");
http_response_code(200);
echo json_encode(["status" => "failed", "message" => "Extraction fields could not be matched completely"]);
exit();
?>
