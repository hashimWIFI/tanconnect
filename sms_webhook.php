<?php
// =========================================================================
// 🚀 TANCONNECT SMS GATEWAY - STAGE 2: LIVE PHONE CAPTURE
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// 1. Grab raw incoming JSON payload from GateSMS cloud server
$raw_payload = file_get_contents('php://input');
$data = json_decode($raw_payload, true);

// 2. Validate the incoming event structure
if ($data && isset($data['event']) && $data['event'] === 'sms:received') {
    
    $payload = $data['payload'];
    
    // Extract raw details from the phone payload
    $sender  = $payload['sender'] ?? 'Unknown';
    $message = $payload['message'] ?? '';
    
    // Hardcoded receiver number as per your request
    $receiver = '255753476850';

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

    // 4. Secure Prepared Statement matching your exact database columns
    $stmt = $conn->prepare("INSERT INTO sms_incoming (sender, receiver, msg, received_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $sender, $receiver, $message);
    
    if ($stmt->execute()) {
        http_response_code(200); 
        echo json_encode(["status" => "success", "message" => "Live SMS stored safely"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save data record"]);
    }

    $stmt->close();
    $conn->close();

} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload or unsupported event"]);
}
?>
