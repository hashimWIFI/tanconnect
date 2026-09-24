<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database node connection failed"]);
    exit();
}

try {
    // Target checkout attempts that were abandoned for longer than 2 hours
    $expirationThreshold = time() - 7200; 
    
    // Recovery Query: Recycles vouchers back to stock and resets variables
    $cleanupQuery = "UPDATE wifi_vouchers 
                     SET status = 'AVAILABLE', 
                         assigned_phone = NULL, 
                         mac_address = NULL, 
                         transaction_id = NULL,
                         purchased_at = NULL,
                         azampay_transaction_id = NULL
                     WHERE status = 'ASSIGNED' 
                     AND purchased_at < NOW() - INTERVAL 2 HOUR";

    if ($conn->query($cleanupQuery) === TRUE) {
        echo json_encode([
            "status" => "success",
            "vouchers_recovered" => $conn->affected_rows,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
exit();
?>
