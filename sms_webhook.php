<?php
// =========================================================================
// 🚀 TANCONNECT CAPTIVE PORTAL GATEWAY ENGINE (PART 1 OF 3)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize baseline status flags globally to protect the bottom template layers from crashing
$httpStatusCode = 0;

// Initialize active browser session context tracking safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// 1. DATA HARVESTING 
// =========================================================================
$sender  = isset($_POST['sender']) ? trim($_POST['sender']) : '';
$receiver  = isset($_POST['receiver']) ? trim($_POST['receiver']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : ''; 


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
    die("Database connectivity node failed to respond: " . $conn->connect_error);
}
 $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }

    // 4. Secure Prepared Statement incorporating the received_time column
    $stmt = $conn->prepare("INSERT INTO sms_incoming (sender, receiver, smg, received_time) VALUES (?, ?, ?, NOW())");
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

