<?php
// 1. ABSOLUTE TOP: Catch any inbound connection before database or logic runs
$rawPayload = file_get_contents('php://input');
$logMessage = "[" . date('Y-m-d H:i:s') . "] INBOUND WEBHOOK HIT! Raw Data: " . $rawPayload . PHP_EOL;
file_put_contents('azampay_network.log', $logMessage, FILE_APPEND);

header("Content-Type: application/json");

// 2. Establish Database Connection (Railway parameters)
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
    file_put_contents('azampay_network.log', "[ERROR] DB Connection Failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database disconnect"]);
    exit;
}

$data = json_decode($rawPayload, true);
if (!$data) {
    http_response_code(200);
    echo json_encode(["status" => "ready", "message" => "Waiting for POST data"]);
    exit;
}

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
            
            $rowsAffected = $stmt->rowCount();
            file_put_contents('azampay_network.log', "[SQL SUCCESS] Reference: $reference | Rows Updated: $rowsAffected" . PHP_EOL, FILE_APPEND);

            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit;
        } catch (\PDOException $e) {
            file_put_contents('azampay_network.log', "[SQL ERROR] Success Block: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            exit;
        }
    } else {
        try {
            $sql = "UPDATE wifi_vouchers 
                    SET status = 'AVAILABLE', assigned_phone = NULL, reference = NULL, utilityref = NULL 
                    WHERE reference = :reference";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);
            
            file_put_contents('azampay_network.log', "[SQL REVERSED] Reference: $reference" . PHP_EOL, FILE_APPEND);
            http_response_code(200);
            exit;
        } catch (\PDOException $e) {
            file_put_contents('azampay_network.log', "[SQL ERROR] Failure Block: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            exit;
        }
    }
}

http_response_code(200);
echo json_encode(["status" => "ignored"]);
