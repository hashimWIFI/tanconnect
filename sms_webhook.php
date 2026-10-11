<?php
header("Content-Type: application/json");

// 1. Grab raw input from SMSGate
$raw_payload = file_get_contents('php://input');
$data = json_decode($raw_payload, true);

// 2. Validate that it's an incoming SMS event from SMSGate
if ($data && isset($data['event']) && $data['event'] === 'sms:received') {
    
    $payload = $data['payload'];
    
    // Extracting fields needed for your table
    $sender   = $payload['sender'] ?? 'Unknown';
    $receiver = $payload['recipient'] ?? 'MyAndroidSIM';
    $message  = $payload['message'] ?? '';

    // =========================================================================
    // 3. CONNECT TO DYNAMIC RAILWAY MYSQL INSTANCE
    // =========================================================================
    $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }

    // 4. Secure Prepared Statement incorporating the received_time column
    $stmt = $conn->prepare("INSERT INTO sms_incoming (sender, receiver, msg, received_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $sender, $receiver, $message);
    
    if ($stmt->execute()) {
        http_response_code(200); // Confirms receipt to SMSGate app
        echo json_encode(["status" => "success", "message" => "SMS stored in database with timestamp"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert record"]);
    }

    $stmt->close();
    $conn->close();

} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload or unsupported event"]);
}
?>
