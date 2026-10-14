<?php
// =========================================================================
// 🚀 TANCONNECT SMS GATEWAY - OPTIMIZED TIMELY RESPONSE ENGINE
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering right away for the fast termination routine
ob_start();

// Increase execution time limit just in case the background database process is slow
set_time_limit(60);

// 1. Grab raw incoming JSON payload from GateSMS cloud server
$raw_payload = file_get_contents('php://input');
$data = json_decode($raw_payload, true);

// 2. Validate the incoming event structure instantly
if ($data && isset($data['event']) && $data['event'] === 'sms:received') {
    
    $payload       = $data['payload'];
    $sender        = $payload['sender'] ?? '';
    $message       = $payload['contentPreview'] ?? '';
    $transactionId = $payload['id'] ?? ''; // Fixed typo variable spelling to match query
    $createdAt     = $payload['createdAt'] ?? '';
    
    // =========================================================================
    // ⚡ FAST TERMINATION ROUTINE (Responds within milliseconds)
    // =========================================================================
    
    header("Connection: close");
    header("Content-Type: application/json");
    
    // Tell the client we received the payload safely so it stops retrying
    echo json_encode(["status" => "success", "message" => "Payload acknowledged instantly"]);
    
    // Get the size of the output buffer data safely
    $size = ob_get_length();
    header("Content-Length: $size");
    
    // Flush all output to SMSGate immediately
    ob_end_flush();
    flush();
    
    // If running under FastCGI (Railway PHP engines usually support this)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // =========================================================================
    // 🗄️ BACKGROUND PROCESS (Executes safely after connection is closed)
    // =========================================================================
    
    // Aligned to both common variants of Railway environment variables
    $db_host = getenv('MYSQLHOST') ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT') ?: '3306';
    $db_user = getenv('MYSQLUSER') ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) {
        error_log("Database connection failed inside background SMS process: " . $conn->connect_error);
        exit;
    }

    // Double check that 'tansactionId' is exactly how it is spelled in your actual SQL schema
    $stmt = $conn->prepare("INSERT INTO sms_incoming (customer_phone, messages, purchased_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $sender, $message);
    
    if (!$stmt->execute()) {
        error_log("Failed to execute database statement: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} else {
    // Clean buffer before sending error code
    ob_end_clean();
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Invalid payload or unsupported event"]);
}
?>
