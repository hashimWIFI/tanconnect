<?php
// sms_webhook.php (Handles UPSTREAM inputs)

// 1. Database Credentials (Fill these out with your actual Tanzanian/Cloud DB settings)
 $db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
    $db_port = getenv('MYSQLPORT')     ?: '3306';
    $db_user = getenv('MYSQLUSER')     ?: 'root';
    $db_pass = getenv('MYSQLPASSWORD') ?: '';
    $db_name = getenv('MYSQLDATABASE') ?: 'railway';


// 2. Open Direct Connection Over Custom Port
$test_conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($test_conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Railway Upstream pipe broken: " . $test_conn->connect_error]));
}

// 3. Receive the raw downstream JSON packet stream directly from the phone relay
$raw_json = file_get_contents('php://input');
$data = json_decode($raw_json, true);

// 4. Validate upstream signature keys
if (isset($data['event']) && $data['event'] === 'sms:received') {
    $payload = $data['payload'];
    
    // Parse the inner nested components exactly how the app packs them
    $phone_number = $test_conn->real_escape_string($payload['sender']); 
    $sms_text     = $test_conn->real_escape_string($payload['message']);
    
    // Convert the phone's ISO time string (2024-06-22T15:46:11.000+07:00) cleanly into MySQL DATETIME
    $time_received = date('Y-m-d H:i:s', strtotime($payload['receivedAt']));

    // 5. Insert directly into your table
    $query = "INSERT INTO sms_incoming (sender, msg, received_time) 
              VALUES ('$phone_number', '$sms_text', '$time_received')";
    
    if ($test_conn->query($query) === TRUE) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Upstream data logged!"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $test_conn->error]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid upstream payload design"]);
}

$test_conn->close();
?>
