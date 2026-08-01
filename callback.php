<?php
// ==========================================================
// AZAMPAY TRANSACTION COMPLETION CALLBACK HANDLER
// ==========================================================

// Set header to receive and return clean JSON objects
header('Content-Type: application/json');

// Capture the raw background data payload sent from AzamPay's server
$rawIncomingData = file_get_contents('php://input');
$paymentData = json_decode($rawIncomingData, true);

// Fallback: If the incoming data packet is completely empty, kill the script safely
if (!$paymentData) {
    http_response_code(400);
    echo json_encode(["status" => "fail", "message" => "Empty payload received"]);
    exit();
}

// Extract transaction variables sent from AzamPay as shown in your documentation image
$transactionStatus = isset($paymentData['transactionstatus']) ? $paymentData['transactionstatus'] : ''; // "success" or "fail"
$externalId        = isset($paymentData['externalId']) ? $paymentData['externalId'] : '';               // Your "WIFI-XXXXX" reference ID
$operatorReference = isset($paymentData['operator']) ? $paymentData['operator'] : '';                 // e.g. "Tigo", "Mpesa"

// Log the incoming payment raw results into a temporary file on Railway to verify transmission fields
file_put_contents('payment_logs.txt', "ID: " . $externalId . " | Status: " . $transactionStatus . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Check if AzamPay confirms the user successfully processed the wallet transaction
if (strtolower($transactionStatus) === 'success') {
    
    // ------------------------------------------------------
    // TODO: PLACE YOUR SUPABASE VOUCHER ISSUING CODE HERE
    // ------------------------------------------------------
    // Example: Mark transactionId as PAID in database and send voucher SMS via Beem / Twilio!
    
}

// Always send a clean HTTP 200 OK success response back to AzamPay so they know you received it safely
http_response_code(200);
echo json_encode(["status" => "acknowledged", "reference" => $externalId]);
