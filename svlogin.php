<?php
// ====================================================================
// TANCONNECT VODACOM MPESA SANDBOX ENGINE ('svlogin.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
// 🛡️ Lock and reserve the voucher instantly inside MySQL by tagging the phone and tracking reference number
$updateSql = "UPDATE wifi_vouchers 
              SET status = 'ASSIGNED', 
                  assigned_phone = '$customerPhone', 
                  transaction_id = '$transactionRef' 
              WHERE id = '$voucherId'";
              
mysqli_query($conn, $updateSql);


// ====================================================================
// STEP 2: RUN VODACOM SANDBOX AUTHENTICATION HANDSHAKE
// ====================================================================
$apiKey = "GHGUyAXopWOZO7QvDzHGIWpQtHRglvfe"; 
$publicKeyBase64 = "MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEArv9yxA69XQKBo24BaF/D+fvlqmGdYjqLQ5WtNBb5tquqGvAvG3WMFETVUSow/LizQalxj2ElMVrUmzu5mGGkxK08bWEXF7a1DEvtVJs6nppIlFJc2SnrU14AOrIrB28ogm58JjAl5BOQawOXD5dfSk7MaAA82pVHoIqEu0FxA8BOKU+RGTihRU+ptw1j4bsAJYiPbSX6i71gfPvwHPYamM0bfI4CmlsUUR3KvCG24rB6FNPcRBhM3jDuv8ae2kC33w9hEq8qNB55uw51vK7hyXoAa+U7IqP1y6nBdlN25gkxEA8yrsl1678cspeXr+3ciRyqoRgj9RD/ONbJhhxFvt1cLBh+qwK2eqISfBb06eRnNeC71oBokDm3zyCnkOtMDGl7IvnMfZfEPFCfg5QgJVk1msPpRvQxmEsrX9MQRyFVzgy2CWNIb7c+jPapyrNwoUbANlN8adU1m6yOuoX7F49x+OjiG2se0EJ6nafeKUXw/+hiJZvELUYgzKUtMAZVTNZfT8jjb58j8GVtuS+6TM2AutbejaCV84ZK58E2CRJqhmjQibEUO6KPdD7oTlEkFy52Y1uOOBXgYpqMzufNPmfdqqqSM4dU70PO8ogyKGiLAIxCetMjjm6FCMEA3Kc8K0Ig7/XtFm9By6VxTJK1Mg36TlHaZKP6VzVLXMtesJECAwEAAQ=="; // The long Public Key string from Vodacom


$sessionUrl = "https://openapi.m-pesa.com/sandbox/ipg/v2/vodacomTZN/getSession/";
$c2bUrl     = "https://openapi.m-pesa.com/sandbox/ipg/v2/vodacomTZN/c2bPayment/singleStage/";

// Format the key to clean PEM structure context rules
$pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKeyBase64, 64, "\n") . "-----END PUBLIC KEY-----";
$publicKeyResource = openssl_pkey_get_public($pemKey);

if (!$publicKeyResource) {
    die("Authentication Engine Failure: Invalid Public Key Configuration.");
}

// Encrypt the plain text API key safely
$encrypted = "";
openssl_public_encrypt($apiKey, $encrypted, $publicKeyResource, OPENSSL_PKCS1_PADDING);
$sessionContextToken = base64_encode($encrypted);

// ⚡ EXECUTE HANDSHAKE WITH VERIFIED AUTHENTICATION HEADERS
$ch = curl_init($sessionUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $sessionContextToken, // Fixed syntax parameter formatting
    "Content-Type: application/json",
    "Origin: *"
]);
$sessionResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$sessionData = json_decode($sessionResponse, true);
$sessionID = isset($sessionData['output_SessionID']) ? $sessionData['output_SessionID'] : false;

if (!$sessionID) {
    echo "<h3>=== STEP 1 SUCCESSFUL: VOUCHER RESERVED IN DATABASE ===</h3>";
    echo "Reference created: <b>" . $transactionRef . "</b><br><br>";
    echo "<h3>=== STEP 2 API DEBUG: AUTHENTICATION FAILED ===</h3>";
    echo "HTTP Status Code: <b>" . $httpCode . "</b><br>";
    echo "Raw Response: <pre>" . htmlspecialchars($sessionResponse) . "</pre><br>";
    die("Execution halted due to API credential error.");
}

// ====================================================================
// STEP 3: EXECUTE STK PUSH USING GENERATED TOKEN
// ====================================================================
$payload = [
    "input_Amount" => (string)$packageAmount,
    "input_Country" => "TZN",
    "input_Currency" => "TZS",
    "input_CustomerMSISDN" => (string)$customerPhone,
    "input_ServiceProviderCode" => "000000", 
    "input_ThirdPartyConversationID" => $transactionRef,
    "input_TransactionReference" => $transactionRef,
    "input_PurchasedItemsDesc" => "WiFi Voucher Package"
];

$ch = curl_init($c2bUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypasses SSL blocks for sandbox responses
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . base64_encode($sessionID), 
    "Content-Type: application/json",
    "Origin: *"
]);
$paymentResponse = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Mapped correctly with $ sign
curl_close($ch);

$paymentResult = json_decode($paymentResponse, true);
$responseCode = isset($paymentResult['output_ResponseCode']) ? $paymentResult['output_ResponseCode'] : 'FAIL';
$responseDesc = isset($paymentResult['output_ResponseDesc']) ? $paymentResult['output_ResponseDesc'] : 'Connection failed';

// Clean execution logic structure
if ($httpStatusCode === 200 && $responseCode === 'INS-0') {
    ?>
    <div style="text-align:center; margin-top:50px; font-family:Arial;">
        <h2>Weka PIN Yako / Enter PIN</h2>
        <p>Tumetuma ombi la malipo kwenye simu yako ya Vodacom. Tafadhali weka namba ya siri kukamilisha.</p>
        <p><b>Reference:</b> <?php echo htmlspecialchars($transactionRef); ?></p>
    </div>
    <?php
} else {
    ?>
    <div style="text-align:center; margin-top:50px; font-family:Arial; color:#e74c3c;">
        <h2>Muamala Umeshindwa / Transaction Failed</h2>
        <p>M-Pesa Gateway Error: <?php echo htmlspecialchars($responseDesc); ?></p>
        <p><a href="javascript:history.back()">Bonyeza hapa kurudi nyuma na kujaribu tena</a></p>
    </div>
    <?php
}
?>
