<?php
header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

// 1. Establish database connection contexts using your dynamic cloud variables
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT')     ?: '3306';

// 2. Safely harvest the active transaction tracking ID parameter coming from your script loops
$txnId = isset($_GET['txn_id']) ? trim($_GET['txn_id']) : (isset($_GET['tx_id']) ? trim($_GET['tx_id']) : '');

if (empty($txnId)) {
    echo json_encode(["status" => "PENDING", "message" => "Missing transaction id parameter"]);
    exit();
}

try {
    // 3. Initialize the live MySQL connector bridge instance
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($conn->connect_error) {
        echo json_encode(["status" => "PENDING", "message" => "Database node connection failed"]);
        exit();
    }

    // 4. Securely extract data matching your strict alphanumeric parameters
    $safeTxnId = $conn->real_escape_string($txnId);
    $query = "SELECT status, voucher_code FROM wifi_vouchers WHERE transaction_id = '$safeTxnId' LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Convert status string values to strict uppercase to eliminate layout spelling mismatches
        $currentStatus = strtoupper(trim($row['status']));

        // 5. If the fake callback webhook successfully verified the transaction loop, unlock the parameters!
        if ($currentStatus === 'SUCCESS') {
            echo json_encode([
                "status"       => "SUCCESS",
                "voucher_code" => $row['voucher_code'] // Delivers the PIN cleanly to your JavaScript container
            ]);
        } else {
            // Keep the loader active if the status field still reads ASSIGNED or AVAILABLE
            echo json_encode(["status" => "PENDING"]);
        }
    } else {
        echo json_encode(["status" => "PENDING", "message" => "Record not matched yet"]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["status" => "PENDING", "message" => "Runtime exception processed"]);
}
exit();
?>
