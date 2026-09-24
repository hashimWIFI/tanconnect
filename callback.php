<?php
// =========================================================================
// 🚀 AZAMPAY LIVE TRANSACTION COMPLETION CALLBACK HANDLER (PRODUCTION BRIDGE)
// =========================================================================

// Set header to receive and return clean JSON objects
header('Content-Type: application/json');

// Capture the raw background data payload sent from AzamPay's live server nodes
$rawIncomingData = file_get_contents('php://input');
$paymentData = json_decode($rawIncomingData, true);

// Fallback: If the incoming data packet is completely empty, kill the script safely
if (!$paymentData) {
    http_response_code(400);
    echo json_encode(["status" => "fail", "message" => "Empty payload received"]);
    exit();
}

// Extract transaction tracking variables dynamically according to AzamPay API specifications
$transactionStatus = isset($paymentData['transactionstatus']) ? trim($paymentData['transactionstatus']) : ''; // "success" or "fail"
$externalId        = isset($paymentData['externalId']) ? trim($paymentData['externalId']) : '';               // Your unique "WIFI-1790207273" tracking key
$azamPayRef        = isset($paymentData['submerchantAcc']) ? trim($paymentData['submerchantAcc']) : (isset($paymentData['utilityref']) ? trim($paymentData['utilityref']) : ''); 
$operatorReference = isset($paymentData['operator']) ? trim($paymentData['operator']) : '';                 // e.g. "Tigo", "Mpesa"

// Fallback extraction mapping for standard AzamPay reference fields if nested differently in signature payload
if (empty($azamPayRef) && isset($paymentData['msisdn'])) {
    $azamPayRef = "AZM-" . time(); // Safe sequential fallback string format to protect matching fields from dropping empty
}

// Log the incoming payment raw results into a temporary file on Railway for permanent structural audits
file_put_contents('payment_logs.txt', "ID: " . $externalId . " | Status: " . $transactionStatus . " | AzamPayRef: " . $azamPayRef . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Execute the database check and update loops only if the payload signals true transaction validation clearance
if (strtolower($transactionStatus) === 'success' && !empty($externalId)) {
    
    // 1. Establish database connection contexts using your dynamic cloud variables
    $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if (!$conn->connect_error) {
        
        // 2. Align explicit timezone context tracking to match Tanzania local time parameters
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $currentDateTime = date("Y-m-d H:i:s");
        
        // 3. PRODUCTION RECONCILIATION MATCHING UPDATE QUERY:
        // Swaps status to SUCCESS, registers the actual purchased_at timestamp metrics, 
        // and safely overrides your transient WIFI tracking id with AzamPay's real payment transaction reference key!
        $updateQuery = "UPDATE wifi_vouchers 
                        SET status = 'SUCCESS', 
                            purchased_at = ?, 
                            transaction_id = ? 
                        WHERE transaction_id = ? 
                        LIMIT 1";
                        
        $stmt = $conn->prepare($updateQuery);
        if ($stmt) {
            // Bind the types: 3 string markers ("sss") mapped chronologically
            $stmt->bind_param("sss", $currentDateTime, $azamPayRef, $externalId);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->close();
    }
}

// Always send a clean HTTP 200 OK success response back to AzamPay so they register delivery tracking signatures safely
http_response_code(200);
echo json_encode(["status" => "acknowledged", "reference" => $externalId]);
exit();
?>
