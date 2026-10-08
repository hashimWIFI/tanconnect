<?php
header("Content-Type: application/json");

// 1. Capture the background raw POST data stream from AzamPay
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

// 2. Save the background payload immediately to the system pool
if ($data) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] AZAMPAY DATA: " . $rawPayload . PHP_EOL;
    file_put_contents('/tmp/azampay_gateway.log', $logMessage, FILE_APPEND);
    
    // Reply success back to AzamPay's servers
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Payload captured successfully by server",
        "received_data" => $data
    ]);
    exit;
}

// 3. Fallback: If YOU visit via a normal browser tab (GET Request)
http_response_code(200);
echo json_encode([
    "status" => "online",
    "message" => "Endpoint is live! Ready and waiting for AzamPay's live POST webhook payload.",
    "method_detected" => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'
]);
