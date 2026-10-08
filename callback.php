<?php
header("Content-Type: application/json");

// 1. Establish Database Connection
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
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database disconnect"]);
    exit;
}

// 2. Read the JSON sent by AzamPay
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

// Save a copy of what arrived to check formatting later
file_put_contents('azampay_debug.log', $rawPayload . PHP_EOL, FILE_APPEND);

// 3. IF A BROWSER VISITS (Empty Data), alert the tester
if (!$data) {
    http_response_code(200); 
    echo json_encode([
        "success" => true, 
        "message" => "Endpoint is live! Ready and waiting for AzamPay's POST data."
    ]);
    exit;
}

// 4. IF AZAMPAY SENDS DATA, process the database update
$transactionStatus = $data['transactionStatus'] ?? null; 
$reference         = $data['reference'] ?? null; 

if ($reference) {
    if ($transactionStatus === 'success') {
        try {
            $sql = "UPDATE wifi_vouchers 
                    SET status = 'SUCCESS' 
                    WHERE reference = :reference AND status = 'ASSIGNED'";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);

            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Voucher updated to SUCCESS"]);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database update failed"]);
            exit;
        }
    } else {
        // Payment failed or timed out -> Revert back to AVAILABLE
        $sql = "UPDATE wifi_vouchers 
                SET status = 'AVAILABLE', assigned_phone = NULL, reference = NULL, utilityref = NULL 
                WHERE reference = :reference";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':reference' => $reference]);
        
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Transaction failed, voucher reset"]);
        exit;
    }
}

// Fallback safety response
http_response_code(200);
echo json_encode(["success" => false, "message" => "No valid transaction reference found in payload"]);
