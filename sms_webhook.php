<?php
// sms_webhook.php

// 1. Database Credentials (Fill these out with your actual Tanzanian/Cloud DB settings)
 $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';

// 2. Connect to MySQL
$test_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($test_conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Database connection failed"]));
}

// 3. Read incoming data stream from SMSGate Cloud
$raw_json = file_get_contents('php://input');
$data = json_decode($raw_json, true);

// 4. Validate Event Signature
if (isset($data['event']) && $data['event'] === 'sms:received') {
    $payload = $data['payload'];
    
    // Extract using the precise keys sent by SMSGate Cloud mode
    $phone_number = $test_conn->real_escape_string($payload['phone']); 
    $sms_text = $test_conn->real_escape_string($payload['message']);
    $time_received = $test_conn->real_escape_string($payload['received_at']);

    // 5. Insert directly into your clean test table
    $query = "INSERT INTO sms_incoming (sender, message, received_time) 
              VALUES ('$phone_number', '$sms_text', '$time_received')";
    
    if ($test_conn->query($query) === TRUE) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Logged to MySQL successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $test_conn->error]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid signature or missing event data"]);
}

$test_conn->close();
?>
