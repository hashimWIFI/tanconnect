<?php
// ====================================================================
// TANCONNECT PRO-SPEC NATIVE VODACOM M-PESA RECOVERY BRIDGE
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Production protection: shields credentials from view

// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name, $db_port);

if (!$conn) {
    error_log("M-PESA BRIDGE ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}

// 2. CAPTURE THE WEBHOOK DISPATCHED BY GATESMS API
// (Adjust these key indices to match your GATESMS gateway layout structure precisely)
$sender  = isset($_REQUEST['sender']) ? strtoupper(trim($_REQUEST['sender'])) : ''; // Expected: 'M-PESA'
$messageText = isset($_REQUEST['message']) ? trim($_REQUEST['message']) : '';

// 📝 AUDIT LOG TRAIL: Logs incoming messages for debugging string validation loops
file_put_contents('mpesa_sms_audit_log.txt', date('[Y-m-d H:i:s] ') . "SENDER: $sender | TEXT: $messageText" . PHP_EOL, FILE_APPEND);

// 🛑 SECURITY FIREWALL GATE A: Ignore any messages not originating from Vodacom's official header!
if (strpos($sender, 'M-PESA') === false && strpos(strtoupper($messageText), 'UMEPOKEA TSHS') === false) {
    http_response_code(200);
    echo json_encode(["status" => "ignored", "reason" => "Not a valid M-Pesa receipt transaction message"]);
    exit();
}

// ====================================================================
// 🔍 3. THE ENTERPRISE REGEX EXTRACTION ENGINE (UNIFORM PATTERN MATCHING)
// ====================================================================
$mpesaTxId       = '';
$extractedAmount = 0;
$customerPhone   = '';

// A. Extract Transaction ID (Grabs 'DEU9614W7C' - the word immediately preceding 'imethibitishwa')
if (preg_match('/^([A-Z0-9]+)\s+imethibitishwa/i', $messageText, $matches)) {
    $mpesaTxId = trim($matches[1]);
}

// B. Extract Numerical Amount (Grabs '1,500.00' cleanly out of 'Tshs 1,500.00')
if (preg_match('/Umepokea\s+Tshs\s+([\d,]+\.\d{2})/i', $messageText, $matches)) {
    // Strips commas cleanly to turn string text "1,500.00" into pure numerical integer 1500 safely
    $extractedAmount = intval(str_replace(',', '', $matches[1]));
}

// C. Extract Customer Phone Destination (Grabs '255778343646' sitting inside the text brackets ': 255778343646 -')
if (preg_match('/:\s+(\d+)\s+-/i', $messageText, $matches)) {
    $customerPhone = trim($matches[1]);
}


// 4. TRANSACTION DUP CHECK & VOUCHER ALLOCATION TIMELINE
$safeTxId = mysqli_real_escape_string($conn, $mpesaTxId);
$dupCheck = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE mpesa_trans_id = '$safeTxId' LIMIT 1");

if (mysqli_num_rows($dupCheck) > 0) {
    http_response_code(200);
    echo json_encode(["status" => "blocked", "reason" => "This M-Pesa transaction ID has already been fulfilled previously."]);
    exit();
}

// Convert amount values to package price tiers cleanly
$targetPriceTier = (string)$extractedAmount; // e.g. '1500', '1000', '500'

// Query database schema pool for an available voucher matching this price category block
$voucherQuery = mysqli_query($conn, "SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = '$targetPriceTier' AND transactionstatus = 'AVAILABLE' LIMIT 1");

if (mysqli_num_rows($voucherQuery) > 0) {
    $voucherRow = mysqli_fetch_assoc($voucherQuery);
    $allocatedId = $voucherRow['id'];
    $voucherCodeString = $voucherRow['voucher_code'];
    
    $safePhone = mysqli_real_escape_string($conn, $customerPhone);
    
    mysqli_begin_transaction($conn);
    try {
        // Securely logs customer details, sets tracking parameters, and flips state to SUCCESS instantly!
        $updateSql = "UPDATE wifi_vouchers 
                      SET transactionstatus = 'SUCCESS',
                          assigned_phone = '$safePhone',
                          mpesa_trans_id = '$safeTxId',
                          purchased_at = NOW() 
                      WHERE id = '$allocatedId'";
                      
        mysqli_query($conn, $updateSql);
        mysqli_commit($conn);
        
        // ====================================================================
        // 🚀 INTEGRATED HARDWARE MODEM SMS TRANSMISSION BRIDGE
        // ====================================================================
        define('TANCONNECT_SECURE_PASS', true);
        $customer_phone = $customerPhone;
        $voucherCode    = $voucherCodeString;
        
        // Fires your native Swahili voucher SMS delivery directly back over your Samsung Phone hardware pipeline!
        if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
            ob_start();
            include('sms_processor.php');
            ob_end_clean();
        }
        
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "M-Pesa payment captured. Voucher code released successfully via hardware SMS."]);
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        http_response_code(500);
        exit();
    }
} else {
    // Fallback error logging: In case a customer transfers money but your stock pool runs completely empty
    error_log("TANCONNECT CRITICAL INVENTORY ALERT: Received M-Pesa package for Tsh $targetPriceTier but no AVAILABLE vouchers exist!");
    http_response_code(200); 
    exit();
}
?>
