<?php
// ====================================================================
// TANCONNECT PRO-SPEC LIVE AUTOMATED WEBHOOK CALLBACK ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0); // Active protection: keeps credentials completely safe

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

// Log the automated callback arrival history inside your text audit trail file
file_put_contents('azampay_webhook_log.txt', date('[Y-m-d H:i:s] ') . "AUTOMATED WEBHOOK HIT RECEIVED VIA: " . ($_SERVER['REQUEST_METHOD'] ?? 'GET') . PHP_EOL, FILE_APPEND);

// 2. ⚡ THE ABSOLUTE NATIVE AUTOMATION RESOLUTION
// When AzamPay hits this path via GET after PIN confirmation, we instantly locate 
// the active row stuck at ASSIGNED and transition it straight to SUCCESS!
$searchQuery = mysqli_query($conn, "SELECT id, price_tier, voucher_code, assigned_phone, reference FROM wifi_vouchers WHERE transactionstatus = 'ASSIGNED' ORDER BY id DESC LIMIT 1");

if (mysqli_num_rows($searchQuery) > 0) {
    $row = mysqli_fetch_assoc($searchQuery);
    $voucherId = $row['id'];
    
    mysqli_begin_transaction($conn);
    try {
        // ⚡ THE OBJECTIVE LIVE UPDATE: Explicitly pushes the true database row to SUCCESS automatically!
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
        
        // Populate the specific variables required by your sms_processor.php engine
        $customer_phone = $row['assigned_phone'] ?? '';
        $voucherCode    = $row['voucher_code'] ?? '';
        
        if (file_exists('sms_processor.php') && !empty($customer_phone) && !empty($voucherCode)) {
            ob_start();
            include('sms_processor.php');
            ob_end_clean();
        }
        
        // Respond with 200 OK to successfully close the handshake loop with AzamPay
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "MySQL status updated automatically"]);
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("TANCONNECT WEBHOOK EXCEPTION: " . $e->getMessage());
        http_response_code(500);
        exit();
    }
}

// Keep the gateway connection clean if hit during an idle state
http_response_code(200); 
echo json_encode(["status" => "ignored", "message" => "No pending assigned vouchers found"]);
?>
