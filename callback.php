<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}


// 1. DATABASE CONNECTIVITY VIA NATIVE RAILWAY ENV VARIABLES
$db_host     = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port     = getenv('MYSQLPORT') ?: '3306';
$db_user     = getenv('MYSQLUSER') ?: 'root';
$db_pass     = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name     = getenv('MYSQLDATABASE') ?: 'railway';



try {
    // Establish PDO MySQL Connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// 2. Parse Incoming Webhook Payload
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

// Extract variables safely
$utilityref        = $data['utilityref'] ?? null;
$reference         = $data['reference'] ?? null; 
$transactionstatus = $data['transactionstatus'] ?? null; // e.g., "SUCCESS"

// 3. Process and Update Table
if (strtoupper($transactionstatus) === 'SUCCESS' && !empty($reference)) {
    try {
        // SQL Statement to update columns for the matching reference row
        $sql = "UPDATE wifi_vouchers 
                SET utilityref = :utilityref, 
                    transactionstatus = :transactionstatus 
                WHERE reference = :reference";
        
        $stmt = $pdo->prepare($sql);
        
        // Execute with parameterized bindings to fully protect against SQL injection
        $stmt->execute([
            ':utilityref'        => $utilityref,
            ':transactionstatus' => $transactionstatus,
            ':reference'         => $reference
        ]);
        
        error_log("Successfully updated wifi_vouchers for reference: $reference");

    } catch (PDOException $e) {
        error_log("SQL Execution Error: " . $e->getMessage());
        // Acknowledge AzamPay even if DB fails internally so they stop retrying a broken script
    }
} else {
    // Handle failures or log non-success statuses (e.g., FAILED)
    if (!empty($reference)) {
        try {
            $sql = "UPDATE wifi_vouchers SET transactionstatus = :transactionstatus WHERE reference = :reference";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':transactionstatus' => $transactionstatus,
                ':reference'         => $reference
            ]);
        } catch (PDOException $e) {
            error_log("SQL Error on failure logging: " . $e->getMessage());
        }
    }
}

// 4. Respond back to AzamPay with a 200 OK
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
