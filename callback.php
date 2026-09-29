<?php
header('Content-Type: application/json');

$rawIncomingData = file_get_contents('php://input');
$paymentData = json_decode($rawIncomingData, true);

if (!$paymentData) {
    http_response_code(400);
    echo json_encode(["status" => "fail", "message" => "Empty payload received"]);
    exit();
}

// Harvest variables sent asynchronously from AzamPay's system notification payload
// 🚀 FIX 1: Robust fallback case parameters array mapping for status string
$transactionStatus = isset($paymentData['transactionstatus']) ? trim($paymentData['transactionstatus']) : (isset($paymentData['transactionStatus']) ? trim($paymentData['transactionStatus']) : ''); 

// 🚀 FIX 2: Check for both standard all-lowercase 'externalid' and camelCase 'externalId'
$externalId = '';
if (isset($paymentData['externalid'])) {
    $externalId = trim($paymentData['externalid']);
} elseif (isset($paymentData['externalId'])) {
    $externalId = trim($paymentData['externalId']);
}

// 🚀 FIX 3: Check AzamPay's official webhook key names for the transaction ID token
$azamPayRef = '';
if (isset($paymentData['azampayTransactionId']) && !empty($paymentData['azampayTransactionId'])) {
    $azamPayRef = trim($paymentData['azampayTransactionId']);
} elseif (isset($paymentData['transactionId']) && !empty($paymentData['transactionId'])) {
    $azamPayRef = trim($paymentData['transactionId']);
} elseif (isset($paymentData['id']) && !empty($paymentData['id'])) {
    $azamPayRef = trim($paymentData['id']);
} else {
    // Keep your historical submerchant references as a clean fallback layer
    $azamPayRef = isset($paymentData['submerchantAcc']) ? trim($paymentData['submerchantAcc']) : (isset($paymentData['utilityref']) ? trim($paymentData['utilityref']) : '');
}

// Log raw result text patterns into your cloud storage folder for permanent auditing audits
file_put_contents('payment_logs.txt', "ID: " . $externalId . " | Status: " . $transactionStatus . " | AzamPayID: " . $azamPayRef . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Evaluate code response matches safely
if (strtolower($transactionStatus) === 'success' && !empty($externalId) && !empty($azamPayRef)) {
    
    $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if (!$conn->connect_error) {
        
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $currentDateTime = date("Y-m-d H:i:s");
        
        // Match the row using your unique internal NITW tracking key
        $updateQuery = "UPDATE wifi_vouchers 
                        SET status = 'SUCCESS', 
                            purchased_at = ?, 
                            azampay_transaction_id = ? 
                        WHERE transaction_id = ? 
                        LIMIT 1";
                        
        $stmt = $conn->prepare($updateQuery);
        if ($stmt) {
            $stmt->bind_param("sss", $currentDateTime, $azamPayRef, $externalId);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
}

// Always respond with an acknowledgement so AzamPay knows your webhook received the row
http_response_code(200);
echo json_encode(["status" => "acknowledged", "reference" => $externalId]);
exit();
?>
