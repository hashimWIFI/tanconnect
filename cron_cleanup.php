<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$db_host = getenv('MYSQLHOST')     ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    echo json_encode(["transactionstatus" => "error", "message" => "Database node connection failed"]);
    exit();
}

try {
    $cleanupQuery = "UPDATE wifi_vouchers 
                     SET transactionstatus = 'AVAILABLE', 
                         assigned_phone = NULL, 
                         mac_address = NULL, 
                         utilityref = NULL 
                     WHERE transactionstatus = 'ASSIGNED' 
                     AND (`Muda wa Malipo (EAT Time)` < NOW() - INTERVAL 2 HOUR OR created_at < NOW() - INTERVAL 2 HOUR)";

    if ($conn->query($cleanupQuery) === TRUE) {
        $recoveredRows = $conn->affected_rows;
        echo json_encode([
            "transactionstatus" => "success",
            "vouchers_recovered" => $recoveredRows,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    } else {
        echo json_encode(["transactionstatus" => "error", "message" => $conn->error]);
    }

} catch (Exception $e) {
    echo json_encode(["transactionstatus" => "error", "message" => $e->getMessage()]);
}

$conn->close();
exit();
?>
