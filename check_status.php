<?php
header("Content-Type: application/json");

// 1. Establish data stream targets natively using your dynamic cloud variables
$db_host = getenv('MYSQLHOST') ?: '127.0.0.1';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: '3306';

// 2. Safely capture the active transaction ID variable coming from your JavaScript frontend loop
$txnId = isset($_GET['txn_id']) ? trim($_GET['txn_id']) : (isset($_GET['tx_id']) ? trim($_GET['tx_id']) : '');

if (empty($txnId)) {
    echo json_encode(["status" => "Pending", "message" => "Missing transaction id parameter"]);
    exit();
}

try {
    // 3. Connect to your actual MySQL instance
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($conn->connect_error) {
        echo json_encode(["status" => "Pending", "message" => "Connection failed"]);
        exit();
    }

    // 4. Securely look up your custom table "wifi_vouchers" using your exact "transaction_id" column name
    $safeTxnId = $conn->real_escape_string($txnId);
    $query = "SELECT status, voucher_code FROM wifi_vouchers WHERE transaction_id = '$safeTxnId' LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 5. If the webhook or fake callback has updated the status to SUCCESS or USED, reveal everything!
        if ($row['status'] === 'SUCCESS' || $row['status'] === 'USED') {
            echo json_encode([
                "status" => "SUCCESS",
                "voucher" => $row['voucher_code'] // Feeds the code perfectly to your JavaScript data.voucher reader!
            ]);
        } else {
            echo json_encode(["status" => "Pending"]);
        }
    } else {
        echo json_encode(["status" => "Pending", "message" => "Record not matched yet"]);
    }

    $conn->close();

} catch (Exception $e) {
    echo json_encode(["status" => "Pending"]);
}
?>
