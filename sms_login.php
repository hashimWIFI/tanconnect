<?php
// =========================================================================
// 🚀 TANCONNECT CAPTIVE PORTAL GATEWAY ENGINE (PART 1 OF 3) - LIVE FIXED
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

$httpStatusCode = 0;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. DATA HARVESTING & PHONE STANDARDIZATION ENGINE
// =========================================================================
$transactionId  = isset($_POST['transactionId'])   ? trim($_POST['transactionId']) : '';
$phone          = isset($_POST['customer_phone'])  ? trim($_POST['customer_phone']) : '';
$amount         = isset($_POST['amount'])          ? trim($_POST['amount']) : ''; 
$amount         = str_replace(',', '', $amount);

if (substr($phone, 0, 1) === '0') {
    $phone = '255' . substr($phone, 1);
}

$routingPrefix = substr($phone, 3, 2); 

// =========================================================================
// 2. CONNECT TO DYNAMIC RAILWAY MYSQL INSTANCE
// =========================================================================
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database connectivity node failed to respond: " . $conn->connect_error);
}

// =========================================================================
// 3. VOUCHER SELECTION, STOCK MANAGEMENT, AND AUTO-ALLOCATION LOCK
// =========================================================================
$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND transactionstatus = 'AVAILABLE' LIMIT 1");

$cleanAmount = intval($amount);
$stmt->bind_param("i", $cleanAmount);
$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbResult) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    
    $alertText  = "⚠️ TANConnect WiFi Alert ⚠️\n";
    $alertText .= "Voucher Tier OUT OF STOCK!\n";
    $alertText .= "• Price Tier: " . number_format((int)$amount) . " TZS\n";
    $alertText .= "• Time: " . date("Y-m-d H:i:s");

    $streamOptions = [
        "http" => [
            "method"  => "POST",
            "header"  => "Title: WiFi System Alert\r\nPriority: high\r\nTags: warning,wifi\r\n",
            "content" => $alertText,
            "timeout" => 5
        ]
    ];

    $context = stream_context_create($streamOptions);
    @file_get_contents("https://ntfy.sh/tanconnect_vouchers_stock_alert_2026", false, $context);
    
    $httpStatusCode = 503; 
 
} else {
    $allocatedVoucherId   = $dbResult['id'];
    $allocatedVoucherCode = $dbResult['voucher_code'];
 
    // ⚡ 4. FIXED UPDATE ENGINE MAPPED PERFECTLY TO YOUR SCHEMAS
    // Exactly 3 placeholders matching 3 tracking arguments
    $updateQuery = "UPDATE wifi_vouchers 
                    SET transactionstatus = 'SUCCESS', 
                        assigned_phone = ?, 
                        utilityref = ? 
                    WHERE id = ?";   

    $updateStmt = $conn->prepare($updateQuery);
    
    if ($updateStmt) {
        // s = assigned_phone (string)
        // s = utilityref (string -> Stores your incoming feeding transactionId)
        // i = id (integer -> Target row index)
        $updateStmt->bind_param("ssi", $phone, $transactionId, $allocatedVoucherId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $httpStatusCode = 200; // Registration process clears successfully!
    } else {
        $httpStatusCode = 500;
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
