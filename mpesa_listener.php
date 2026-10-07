<?php
// ====================================================================
// TANCONNECT ACTIVE INBOX SYNCHRONIZATION POLLING ENGINE ('mpesa_listener.php')
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
    error_log("TANCONNECT SYSTEM ERROR: Database Connection Failed");
    http_response_code(500);
    exit();
}
// ====================================================================
// 🎛️ CONFIGURATION LAYER: ENTER YOUR ACCOUNT CREDENTIALS
// ====================================================================
// Enter the exact Username and Password you use to access your dashboard portal
$smsGatewayUsername  = "PKHHG1";
$smsGatewayPassword = "icqsrlspg85th2";

// The endpoint address linking directly to the gateway engine messages directory
$gatewayBaseUrl = "https://sms-gate.app"; 

// 2. CONNECT TO THE SMS GATEWAY APPLICATION PLATFORM VIA BASIC AUTH HEADERS
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $gatewayBaseUrl . "?type=received&limit=20");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $smsGatewayUsername . ":" . $smsGatewayPassword); // ⚡ Binds username natively!
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);


$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($apiResponse)) {
    error_log("TANCONNECT SMS EXTRACTION EXCEPTION: Connection to Gateway Engine failed with status: " . $httpCode);
    header("Content-Type: application/json");
    http_response_code(200);
    echo json_encode(["status" => "error", "message" => "Connection to gateway failed. Check your login details."]);
    exit();
}

$smsData = json_decode($apiResponse, true);
$receivedMessagesList = $smsData['data'] ?? [];

// 3. PARSE EACH INBOUND INBOX MESSAGE THROUGH THE EXTRACTION MATRIX
foreach ($receivedMessagesList as $sms) {
    $sender      = strtoupper(trim((string)($sms['sender'] ?? '')));
    $messageText = trim((string)($sms['message'] ?? ''));
    
    // 🔍 FILTER STAGE: Focus exclusively on messages carrying Vodacom M-Pesa receipt traits
    if (strpos($sender, 'M-PESA') !== false || strpos(strtoupper($messageText), 'UMEPOKEA TSHS') !== false) {
        
        $mpesaTxId       = '';
        $extractedAmount = 0;
        $customerPhone   = '';

        // Pattern A: Extract Transaction ID (Grabs 'DEU9614W7C' - the first word before 'imethibitishwa')
        if (preg_match('/^([A-Z0-9]+)\s+imethibitishwa/i', $messageText, $matches)) {
            $mpesaTxId = trim($matches[1]);
        }

        // Pattern B: Extract Numerical Cash Amount (Strips commas to parse text '1,500.00' to numerical 1500)
        if (preg_match('/Umepokea\s+Tshs\s+([\d,]+\.\d{2})/i', $messageText, $matches)) {
            $extractedAmount = intval(str_replace(',', '', $matches[1]));
        }

        // Pattern C: Extract Customer Contact Phone (Grabs numbers inside ': 255778343646 -')
        if (preg_match('/:\s+(\d+)\s+-/i', $messageText, $matches)) {
            $customerPhone = trim($matches[1]);
        }
        
        // 4. DATABASE SYNC CHECK AND CONDITIONAL RELEASE LOOP
        if (!empty($mpesaTxId) && $extractedAmount > 0) {
            $safeTxId = mysqli_real_escape_string($conn, $mpesaTxId);
            
            // Confirm this specific network message reference was never used before
            $dupCheck = mysqli_query($conn, "SELECT id FROM wifi_vouchers WHERE mpesa_trans_id = '$safeTxId' LIMIT 1");
            
            if (mysqli_num_rows($dupCheck) === 0) {
                $priceTier = (string)$extractedAmount;
                
                // Identify the oldest available inventory card item matching this pricing group category
                $voucherQuery = mysqli_query($conn, "SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = '$priceTier' AND transactionstatus = 'AVAILABLE' LIMIT 1");
                
                if (mysqli_num_rows($voucherQuery) > 0) {
                    $voucherRow = mysqli_fetch_assoc($voucherQuery);
                    $voucherId  = $voucherRow['id'];
                    $voucherCodeString = $voucherRow['voucher_code'];
                    
                    $safePhone = mysqli_real_escape_string($conn, $customerPhone);
                    
                    mysqli_begin_transaction($conn);
                    try {
                        // ⚡ EXECUTION SUCCESS GATE: Assigns customer, logs transaction token, sets state to SUCCESS!
                        $updateSql = "UPDATE wifi_vouchers 
                                      SET transactionstatus = 'SUCCESS',
                                          assigned_phone = '$safePhone',
                                          mpesa_trans_id = '$safeTxId',
                                          purchased_at = NOW() 
                                      WHERE id = '$voucherId'";
                        
                        mysqli_query($conn, $updateSql);
                        mysqli_commit($conn);
                        
                        // Integrated Hardware Phone MODEM SMS Receipt Dispatch Bridge
                        define('TANCONNECT_SECURE_PASS', true);
                        $customer_phone = $customerPhone;
                        $voucherCode    = $voucherCodeString;
                        
                        if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
                            ob_start();
                            include('sms_processor.php');
                            ob_end_clean();
                        }
                        
                        error_log("TANCONNECT COMPLETE: Automated Inbox Sync unlocked Voucher ID: $voucherId via M-Pesa Token: $mpesaTxId");
                        
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                    }
                }
            }
        }
    }
}

header("Content-Type: application/json");
http_response_code(200);
echo json_encode(["status" => "complete", "message" => "Inbox synchronization routine processed successfully."]);
exit();
?>
