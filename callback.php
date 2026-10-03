<?php
// ====================================================================
// 🔍 TANCONNECT LIVE PAYLOAD CATCHER ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// 1. Capture the absolute raw network input stream
$rawInputStream = file_get_contents('php://input');

// 2. Build a complete diagnostic data package
$diagnosticReport = [
    "TIMESTAMP"        => date('Y-m-d H:i:s'),
    "HTTP_METHOD"      => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    "CONTENT_TYPE"     => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET',
    "RAW_BODY_STREAM"  => $rawInputStream,
    "PARSED_POST_ARR"  => $_POST,
    "PARSED_GET_ARR"   => $_GET,
    "PARSED_REQUEST"   => $_REQUEST,
    "ALL_HTTP_HEADERS" => getallheaders()
];

// 3. Write it cleanly as raw text directly into your log file
file_put_contents(
    'azampay_webhook_log.txt', 
    "==================== AZAMPAY LIVE HIT ====================\n" .
    json_encode($diagnosticReport, JSON_PRETTY_PRINT) . "\n" .
    "==========================================================\n", 
    FILE_APPEND
);

// 4. Respond with a solid 200 OK to tell AzamPay we received it
http_response_code(200);
echo json_encode(["status" => "diagnostic_captured", "success" => true]);
exit();
?>
