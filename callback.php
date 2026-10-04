<?php
header("Content-Type: application/json");

// 1. Establish Database Connection (Railway MySQL parameters)
$host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db       = getenv('MYSQLDATABASE') ?: 'railway';
$user     = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    error_log("AzamPay Callback DB Connection Failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database disconnect"]);
    exit;
}

// 2. Extract Raw JSON from AzamPay
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

// Recommended: This saves the raw webhook data in a text file for troubleshooting
file_put_contents('azampay_debug.log', $rawPayload . PHP_EOL, FILE_APPEND);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Empty payload"]);
    exit;
}

// 3. Match Parameters
$transactionStatus = $data['transactionStatus'] ?? null; 
$reference         = $data['reference'] ?? null; // e.g., NITW12345678

if ($reference) {
    if ($transactionStatus === 'success') {
        try {
            // SUCCESS BLOCK: Updates table status to SUCCESS for the matched reference
            $sql = "UPDATE wifi_vouchers 
                    SET transactionstatus = 'SUCCESS' 
                    WHERE reference = :reference AND transactionstatus = 'ASSIGNED'";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);

            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Voucher released"]);
            exit;
            
        } catch (\PDOException $e) {
            error_log("SQL execution error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "SQL execution fault"]);
            exit;
        }
    } else {
        try {
            // FAILURE BLOCK: Cleans up data and returns voucher to AVAILABLE pool
            $sql = "UPDATE wifi_vouchers 
                    SET transactionstatus = 'AVAILABLE', assigned_phone = NULL, reference = NULL, utilityref = NULL 
                    WHERE reference = :reference";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);
            
            http_response_code(200);
            echo json_encode(["status" => "reversed", "message" => "Transaction failed or cancelled"]);
            exit;
        } catch (\PDOException $e) {
            error_log("SQL execution error in failure block: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "SQL execution fault on failure"]);
            exit;
        }
    }
}

// Always fallback response to tell AzamPay we received the packet
http_response_code(200);
echo json_encode(["status" => "ignored"]);
