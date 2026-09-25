<?php
// =========================================================================
// 📥 TANCONNECT - AUTOMATED SUPPORT INGESTION WEBHOOK RECEIVER
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to receive clean JSON network streams
header('Content-Type: application/json');

// Capture the raw background data stream sent from the email forwarder server node
$rawPayload = file_get_contents('php://input');
$emailData = json_decode($rawPayload, true);

// Fallback protection: If the payload is empty, exit gracefully
if (!$emailData) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Empty message container received"]);
    exit();
}

// 1. Harvest email parameters dynamically from the standard forwarder webhook schema
// Adjust fields mapping keys if your specific provider wraps attributes differently
$senderEmail   = isset($emailData['from'])    ? trim($emailData['from'])    : (isset($emailData['sender']) ? trim($emailData['sender']) : '');
$emailSubject  = isset($emailData['subject']) ? trim($emailData['subject']) : 'Wi-Fi Support Request';
$emailBodyText = isset($emailData['text'])    ? trim($emailData['text'])    : (isset($emailData['body'])   ? trim($emailData['body'])   : '');

// Strip out nested bracket formatting blocks from email clients if present (e.g. "John <juma@gmail.com>")
if (preg_match('/<(.*?)>/', $senderEmail, $matches)) {
    $senderEmail = trim($matches[1]);
}

// Validate that we have at least a sender and a message body before writing to database
if (empty($senderEmail) || empty($emailBodyText)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Incomplete request parameters: Missing sender or body text"]);
    exit();
}

// 2. Connect to your live cloud database instance
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database node connection failed"]);
    exit();
}

// Synchronize timezone context parameters to Tanzanian hours (EAT)
date_default_timezone_set('Africa/Dar_es_Salaam');

// 3. Generate a clean, branded, unique Ticket ID string using current time signatures
$customTicketId = 'NIT-TKT-' . rand(100, 999) . substr(time(), -4);
$initialStatus  = 'OPEN';

// 4. Update the SQL engine to insert the incoming request row securely into Option A column formats
$insertQuery = "INSERT INTO support_tickets (ticket_id, customer_email, ticket_subject, ticket_message, ticket_status) 
                VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insertQuery);
if ($stmt) {
    // Bind the 5 string variables chronologically
    $stmt->bind_param("sssss", $customTicketId, $senderEmail, $emailSubject, $emailBodyText, $initialStatus);
    $stmt->execute();
    $stmt->close();
    
    // HTTP 200 Handshake: Acknowledge successful processing to the email provider node
    http_response_code(200);
    echo json_encode([
        "status" => "success", 
        "message" => "Ticket logged successfully", 
        "ticket_id" => $customTicketId
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to compile SQL parameters: " . $conn->error]);
}

$conn->close();
exit();
?>
