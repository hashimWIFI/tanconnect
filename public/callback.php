<?php
header("Content-Type: application/json");

// 1. Database Connection Configuration
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
    echo json_encode(["status" => "error", "message" => "Database disconnect"]);
    exit;
}

// 2. Safely capture the data stream without writing files
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (!$data) {
    http_response_code(200);
    echo json_encode(["status" => "ready", "message" => "Endpoint live inside public folder!"]);
    exit;
}

// 3. Extract AzamPay Variables
$transactionStatus = $data['transactionStatus'] ?? null; 
$reference         = $data['reference'] ?? null; 

if ($reference) {
    if ($transactionStatus === 'success') {
        try {
            // Update status to SUCCESS for the assigned voucher reference
            $sql = "UPDATE wifi_vouchers 
                    SET status = 'SUCCESS' 
                    WHERE reference = :reference AND status = 'ASSIGNED'";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);

            http_response_code(200);
            echo json_encode(["status" => "success"]);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            exit;
        }
    } else {
        try {
            // Revert voucher to AVAILABLE if user cancels or transaction fails
            $sql = "UPDATE wifi_vouchers 
                    SET status = 'AVAILABLE', assigned_phone = NULL, reference = NULL, utilityref = NULL 
                    WHERE reference = :reference";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);
            
            http_response_code(200);
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            exit;
        }
    }
}

http_response_code(200);
echo json_encode(["status" => "ignored"]);
