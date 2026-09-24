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
$transactionStatus = isset($paymentData['transactionstatus']) ? trim($paymentData['transactionstatus']) : ''; 
$externalId        = isset($paymentData['externalId']) ? trim($paymentData['externalId']) : ''; // Our NITW-XXXXX tracking ID
$azamPayRef        = isset($paymentData['submerchantAcc']) ? trim($paymentData['submerchantAcc']) : (isset($paymentData['utilityref']) ? trim($paymentData['utilityref']) : ''); 

// Log raw result text patterns into your cloud storage folder for permanent auditing audits
file_put_contents('payment_logs.txt', "ID: " . $externalId . " | Status: " . $transactionStatus . " | AzamPayID: " . $azamPayRef . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if (strtolower($transactionStatus) === 'success' && !empty($externalId)) {
    
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

http_response_code(200);
echo json_encode(["status" => "acknowledged", "reference" => $externalId]);
exit();
?>
