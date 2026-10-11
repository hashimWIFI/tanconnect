<?php
// =========================================================================
// 🚀 TANCONNECT CAPTIVE PORTAL GATEWAY ENGINE
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Initialize active browser session context tracking safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. DATA HARVESTING (Supporting both Form Post and JSON payloads)
// =========================================================================
$sender   = '';
$receiver = '';
$message  = '';

// Check if data is coming from your HTML form/GateSMS test
if (!empty($_POST)) {
    $sender   = isset($_POST['sender']) ? trim($_POST['sender']) : '';
    $receiver = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';
    $message  = isset($_POST['message']) ? trim($_POST['message']) : '';
} else {
    // Fallback to raw JSON payload (What GateSMS and cURL send natively)
    $raw_payload = file_get_contents('php://input');
    $data = json_decode($raw_payload, true);
    
    if ($data && isset($data['event']) && $data['event'] === 'sms:received') {
        $payload  = $data['payload'];
        $sender   = $payload['sender'] ?? '';
        $receiver = $payload['recipient'] ?? '';
        $message  = $payload['message'] ?? '';
    }
}

// Validate that we actually have the required parameters before hitting the DB
if (!empty($sender) && !empty($message)) {

    // =========================================================================
    // 2. CONNECT TO DYNAMIC RAILWAY MYSQL INSTANCE
    // =========================================================================
    $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
        exit;
    }

    // =========================================================================
    // 3. EXECUTE PREPARED STATEMENT INSERTS
    // =========================================================================
    $stmt = $conn->prepare("INSERT INTO sms_incoming (sender, receiver, msg, received_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $sender, $receiver, $message);
    
    if ($stmt->execute()) {
        http_response_code(200); 
        echo json_encode(["status" => "success", "message" => "SMS stored in database with timestamp"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert record: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} else {
    // Triggered if the code structural tags or payload fields are missing
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload or unsupported event structure"]);
}
?>
