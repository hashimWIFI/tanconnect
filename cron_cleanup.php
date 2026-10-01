<?php
// ====================================================================
// TANCONNECT AUTOMATED VOUCHER POOL RECOVERY ENGINE ('cron_cleanup.php')
// ====================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// 1. ESTABLISH CONNECTIVITY USING RAILWAY ENV VARIABLES
// 1. Establish database connection using your dynamic Railway variables
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database node connection failed: " . $conn->connect_error]);
    exit();
}

try {
    // 2. CONSOLIDATED RECOVERY ENGINE QUERY
    // Reverts records stuck in 'ASSIGNED' state for longer than 2 hours back to 'AVAILABLE'
    // safely clearing out temporary transaction strings so new customers can buy them.
    $cleanupQuery = "UPDATE wifi_vouchers 
                     SET status = 'AVAILABLE', 
                         assigned_phone = NULL, 
                         mac_address = NULL, 
                         transaction_id = NULL,
                         purchased_at = NULL 
                     WHERE status = 'ASSIGNED' 
                     AND (`Muda wa Malipo (EAT Time)` < NOW() - INTERVAL 2 HOUR OR created_at < NOW() - INTERVAL 2 HOUR)";

    if (conn->query(cleanupQuery) === TRUE) {
        recoveredRows = conn->affected_rows;
        echo json_encode([
            "status" => "success",
            "message" => "Database cleanup completed successfully",
            "vouchers_recovered" => $recoveredRows,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Query execution failed: " . $conn->error]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Runtime exception caught: " . $e->getMessage()]);
}

$conn->close();
exit();
?>
