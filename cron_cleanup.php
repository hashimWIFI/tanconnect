<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

// 1. Establish database connection using your dynamic Railway variables
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
    // 2. ISOLATE OLD EXPIRED ASSIGNED VOUCHERS (Older than 2 hours)
    // We convert the current timestamp minus 7200 seconds (2 hours) to target abandoned sessions.
    $expirationThreshold = time() - 7200; 
    
    // 3. EXECUTE RECOVERY QUERY
    // This updates the status back to AVAILABLE, clears out the phone, transaction ID, and MAC,
    // ensuring no voucher gets permanently locked by an abandoned checkout attempt.
    $cleanupQuery = "UPDATE wifi_vouchers 
                     SET status = 'AVAILABLE', 
                         assigned_phone = NULL, 
                         mac_address = NULL, 
                         transaction_id = NULL 
                     WHERE status = 'ASSIGNED' 
                     ABS(CAST(SUBSTRING(transaction_id, 6) AS UNSIGNED)) < $expirationThreshold 
                     AND transaction_id LIKE 'WIFI-%'";

    if ($conn->query($cleanupQuery) === TRUE) {
        $recoveredRows = $conn->affected_rows;
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
