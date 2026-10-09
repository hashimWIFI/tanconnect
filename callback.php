<?php
// ====================================================================
// TANCONNECT RAW NETWORK PAYLOAD SNIFFER ENGINE ('callback.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);

// 1. ISOLATE AND STREAM RAW INPUT IMMEDIATELY BEFORE ANY OTHER OPERATION
$rawInputData = file_get_contents('php://input');
$parsedPayload = json_decode($rawInputData, true);

if (empty($parsedPayload) || !is_array($parsedPayload)) {
    $parsedPayload = array_merge($_GET, $_POST, $_REQUEST);
}

// 📝 THE UNBREAKABLE SYSTEM AUDIT VAULT: 
// Writes the exact string to a flat file inside your Railway directory root space!
$logOutput = "========================================\n";
$logOutput .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
$logOutput .= "HTTP_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
$logOutput .= "RAW_PAYLOAD: " . ($rawInputData ?: 'EMPTY_STREAM') . "\n";
$logOutput .= "PARSED_JSON: " . json_encode($parsedPayload) . "\n";
$logOutput .= "========================================\n";

file_put_contents('azampay_raw_sniffer_log.txt', $logOutput, FILE_APPEND);

// 2. DISCONNECT EXTERNALS IMMEDIATELY TO SATISFY AZAMPAY OUTBOUND FIREWALLS
header("Content-Type: application/json");
http_response_code(200);
echo json_encode([
    "success" => true,
    "message" => "Raw network packet captured successfully by TanConnect sniffer layer"
]);
exit();
?>
