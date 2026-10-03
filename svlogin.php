<?php
// ====================================================================
// TANCONNECT VODACOM MPESA SANDBOX ENGINE ('svlogin.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ⏳ TIMEOUT EXTENSION PATCH: Prevents the 30-second crash when sleep(30) executes
ini_set('max_execution_time', 300);
set_time_limit(300);

// ====================================================================
// STEP 1: ESTABLISH DATABASE CONNECTION & CHECK AVAILABILITY
// ====================================================================


// ==========================================
// STEP1. CONNECT TO AUTOMATED RAILWAY MYSQL DB
// ==========================================
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';

// Establish the connection matrix
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check if connection was successful
if (!$conn) {
    die("Database Connection Failure: " . mysqli_connect_error());
}

// Inherited variables from gate.php
$customerPhone = isset($phone) ? $phone : '255753476850';
$packageAmount = isset($cleanAmount) ? $cleanAmount : 500;
$transactionRef = "9" . time() . rand(10, 99);

// 🔍 Check voucher availability BEFORE calling the external API
$voucherQuery = mysqli_query($conn, "SELECT id, voucher_code FROM wifi_vouchers WHERE transactionstatus = 'AVAILABLE' LIMIT 1");

if (mysqli_num_rows($voucherQuery) === 0) {
    die("Huduma Imesimama: Hakuna vocha za WiFi zilizobaki kwenye mfumo wetu. (No Vouchers Available)");
}

$voucherRow = mysqli_fetch_assoc($voucherQuery);
$voucherId   = $voucherRow['id'];
$voucherCode = $voucherRow['voucher_code'];

// 🛡️ Lock, reserve the voucher, and save the transaction_id inside MySQL
$updateSql = "UPDATE wifi_vouchers 
              SET transactionstatus = 'ASSIGNED', 
                  assigned_phone = '$customerPhone', 
                  utilityref = '$transactionRef' 
              WHERE id = '$voucherId'";       
mysqli_query($conn, $updateSql);

// ====================================================================
// STEP 2: RUN VODACOM SANDBOX AUTHENTICATION HANDSHAKE
// ====================================================================
$apiKey = "GHGUyAXopWOZO7QvDzHGIWpQtHRglvfe"; 
$publicKeyBase64 = "MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEArv9yxA69XQKBo24BaF/D+fvlqmGdYjqLQ5WtNBb5tquqGvAvG3WMFETVUSow/LizQalxj2ElMVrUmzu5mGGkxK08bWEXF7a1DEvtVJs6nppIlFJc2SnrU14AOrIrB28ogm58JjAl5BOQawOXD5dfSk7MaAA82pVHoIqEu0FxA8BOKU+RGTihRU+ptw1j4bsAJYiPbSX6i71gfPvwHPYamM0bfI4CmlsUUR3KvCG24rB6FNPcRBhM3jDuv8ae2kC33w9hEq8qNB55uw51vK7hyXoAa+U7IqP1y6nBdlN25gkxEA8yrsl1678cspeXr+3ciRyqoRgj9RD/ONbJhhxFvt1cLBh+qwK2eqISfBb06eRnNeC71oBokDm3zyCnkOtMDGl7IvnMfZfEPFCfg5QgJVk1msPpRvQxmEsrX9MQRyFVzgy2CWNIb7c+jPapyrNwoUbANlN8adU1m6yOuoX7F49x+OjiG2se0EJ6nafeKUXw/+hiJZvELUYgzKUtMAZVTNZfT8jjb58j8GVtuS+6TM2AutbejaCV84ZK58E2CRJqhmjQibEUO6KPdD7oTlEkFy52Y1uOOBXgYpqMzufNPmfdqqqSM4dU70PO8ogyKGiLAIxCetMjjm6FCMEA3Kc8K0Ig7/XtFm9By6VxTJK1Mg36TlHaZKP6VzVLXMtesJECAwEAAQ=="; // The long Public Key string from Vodacom

$sessionUrl = "https://openapi.m-pesa.com/sandbox/ipg/v2/vodacomTZN/getSession/";
$c2bUrl     = "https://openapi.m-pesa.com/sandbox/ipg/v2/vodacomTZN/c2bPayment/singleStage/";


$pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKeyBase64, 64, "\n") . "-----END PUBLIC KEY-----";
$publicKeyResource = openssl_pkey_get_public($pemKey);

if (!$publicKeyResource) {
    die("Authentication Engine Failure: OpenSSL cannot parse the Public Key string.");
}

$encrypted = "";
openssl_public_encrypt($apiKey, $encrypted, $publicKeyResource, OPENSSL_PKCS1_PADDING);
$sessionContextToken = base64_encode($encrypted);

// 1. Fetch Session token via cURL handshake
$ch = curl_init($sessionUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// ⚡ NETWORK ARCHITECTURE PATCH: Force IPv4 to prevent 30-second hang drops
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); 
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8); // Abort if server takes more than 8 seconds to reply
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $sessionContextToken,
    "Content-Type: application/json",
    "Origin: *"
]);
$sessionResponse = curl_exec($ch);
$httpHandshakeCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $handshakeError = curl_error($ch);
    curl_close($ch);
    die("<h3>=== VODACOM SANDBOX CONNECTION TIMEOUT ===</h3>
         <p>Network Error: <b>" . htmlspecialchars($handshakeError) . "</b></p>
         <p>The sandbox server refused to respond over the active network interface layout. This confirms an API gateway drop.</p>");
}
curl_close($ch);


$sessionData = json_decode($sessionResponse, true);
$sessionID = isset($sessionData['output_SessionID']) ? $sessionData['output_SessionID'] : false;

// If the token handshake fails, stop here and show the explicit debug window
if (!$sessionID) {
    ?>
    <div style="text-align:center; margin-top:50px; font-family:Arial; color:#e74c3c; padding: 20px;">
        <h2>=== STEP 1 SUCCESSFUL: VOUCHER RESERVED IN DATABASE ===</h2>
        <p>Reference: <b><?php echo htmlspecialchars($transactionRef); ?></b></p>
        <hr style="max-width:500px; border:1px solid #f5c6cb;">
        <h2>=== STEP 2 API DEBUG: HANDSHAKE AUTHENTICATION FAILED ===</h2>
        <p>HTTP Code: <b><?php echo $httpHandshakeCode; ?></b></p>
        <p style="font-size:13px; color:#7f8c8d; background:#f8d7da; padding:10px; display:inline-block; border-radius:4px; font-family:monospace;">
            Raw Handshake Response: <?php echo htmlspecialchars($sessionResponse); ?>
        </p>
    </div>
    <?php
    die();
}

// ====================================================================
// STEP 3: EXECUTE STK PUSH USING GENERATED TOKEN
// ====================================================================

// ⏳ SDK SPECIFICATION COMPLIANCE: Wait for the session token to propagate live across gateways
sleep(30); 

// 🔒 RE-ENCRYPTION LAYER: The Vodacom API gateway expects the Session ID to be RSA encrypted too!
$encryptedSession = "";
openssl_public_encrypt($sessionID, $encryptedSession, $publicKeyResource, OPENSSL_PKCS1_PADDING);
$finalAuthToken = base64_encode($encryptedSession);

$payload = [
    "input_Amount" => (string)$packageAmount,
    "input_Country" => "TZN",
    "input_Currency" => "TZS",
    "input_CustomerMSISDN" => (string)$customerPhone,
    "input_ServiceProviderCode" => "000000", 
    "input_ThirdPartyConversationID" => (string)$transactionRef,
    "input_TransactionReference" => (string)$transactionRef,
    "input_PurchasedItemsDesc" => "WiFi Voucher Package"
];

$ch = curl_init($c2bUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); 
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $finalAuthToken, // ⚡ FIXED: Passed the newly encrypted session context string
    "Content-Type: application/json",
    "Origin: *"
]);

$paymentResponse = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $curlErrorText = curl_error($ch);
    curl_close($ch);
    die("<h3>=== CRITICAL SERVER NETWORK FAILURE ===</h3>cURL Transport Error: <b>" . htmlspecialchars($curlErrorText) . "</b>");
}
curl_close($ch);

$paymentResult = json_decode($paymentResponse, true);
$responseCode = isset($paymentResult['output_ResponseCode']) ? $paymentResult['output_ResponseCode'] : 'FAIL';
$responseDesc = isset($paymentResult['output_ResponseDesc']) ? $paymentResult['output_ResponseDesc'] : 'No description returned';

// User presentation layer layouts
if ($httpStatusCode === 200 && $responseCode === 'INS-0') {
    ?>
    <div style="text-align:center; margin-top:50px; font-family:Arial;">
        <h2>Weka PIN Yako / Enter PIN</h2>
        <p>Tumetuma ombi la malipo kwenye simu yako ya Vodacom. Tafadhali weka namba ya siri kuthibitisha muamala.</p>
        <p><b>Reference ID:</b> <?php echo htmlspecialchars($transactionRef); ?></p>
    </div>
    <?php
} else {
    ?>
    <div style="text-align:center; margin-top:50px; font-family:Arial; color:#e74c3c; padding: 20px;">
        <h2>Muamala Umeshindwa / Transaction Failed</h2>
        <p>M-Pesa Gateway Error: <b><?php echo htmlspecialchars($responseDesc); ?></b></p>
        <hr style="max-width:400px; border:1px solid #f5c6cb;">
        <p>HTTP Code: <b><?php echo $httpStatusCode; ?></b> | Response Code: <b><?php echo htmlspecialchars($responseCode); ?></b></p>
        <p style="font-size:12px; color:#7f8c8d; background:#f8d7da; padding:10px; display:inline-block; border-radius:4px;">
            Raw JSON Response: <?php echo htmlspecialchars($paymentResponse); ?>
        </p>
        <p><a href="javascript:history.back()">Bonyeza hapa kurudi nyuma na kujaribu tena</a></p>
    </div>
    <?php
}
?>
