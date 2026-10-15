<?php
// =========================================================================
// 🚀 TANCONNECT CAPTIVE PORTAL GATEWAY ENGINE (PART 1 OF 3)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize baseline status flags globally to protect the bottom template layers from crashing
$httpStatusCode = 0;

// Initialize active browser session context tracking safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. DATA HARVESTING & PHONE STANDARDIZATION ENGINE
// =========================================================================
$transactionId  = isset($_POST['transactionId']) ? trim($_POST['transactionId']) : '';
$phone          = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$amount         = isset($_POST['amount']) ? trim($_POST['amount']) : ''; 
$amount         = str_replace(',', '', $amount);

// Enforce international dialing schema standard formatting rules (Tanzania 255)
if (substr($phone, 0, 1) === '0') {
    $phone = '255' . substr($phone, 1);
}

$routingPrefix = substr($phone, 3, 2); 

// Map network carrier designations by standard Tanzanian operator configurations
if (in_array($routingPrefix, ['74', '75', '76', '14'])) {
    $provider = "MPESA";
} elseif (in_array($routingPrefix, ['70', '71', '77', '65', '07', '67', '72'])) {
    $provider = "Tigo";
} elseif (in_array($routingPrefix, ['78', '79', '68', '69'])) {
    $provider = "Airtel";
} elseif (in_array($routingPrefix, ['62', '61'])) {
    $provider = "Halopesa";
} else {
    $provider = "MPESA"; 
}

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
// 3. VOUCHER SELECTION, STOCK MANAGEMENT, AND AZAMPAY CHECKOUT DISPATCH
// =========================================================================

// Query the database to find one available voucher matching the selected price tier
$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND transactionstatus = 'AVAILABLE' LIMIT 1");

// 🚀 THE CRITICAL FIX: Explicitly enforce mathematical integer validation type conversion metrics
$cleanAmount = intval($amount);
$stmt->bind_param("i", $cleanAmount);

$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$dbResult) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    
    // ---- FIREWALL-SAFE OUT OF STOCK SYSTEM ALERTS VIA NTFY ----
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
    
    $httpStatusCode = 503; // Sets failure flag to load the out-of-stock template later
 
} else {
    // Voucher is available, isolate variables for tracking
    $allocatedVoucherId   = $dbResult['id'];
    $allocatedVoucherCode = $dbResult['voucher_code'];
 
 
// CLEAN SPEC-ALIGNED QUERY (MATCHES YOUR TRIPPED BACK TABLE SCHEMA RULES DEFINITIVELY)
    $updateQuery = "UPDATE wifi_vouchers 
                    SET transactionstatus = 'SUCCESS', 
                        assigned_phone = ?, 
                        reference = ?, 
                        utilityref = ?, 
                        purchased_at = ? 
                    WHERE id = ?";   

       
    }



// Type safety wrapper for connection closure prevents uncaught execution crashes
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

?>

