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
    // FIXED: Added port to the DSN string since Railway uses custom high ports externally
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
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
$rawStatus         = isset($data['transactionstatus']) ? strtolower(trim($data['transactionstatus'])) : '';

// 3. FIXED: Strict mapping logic to force "SUCCESS" or "FAILED" string outputs
if ($rawStatus === 'success' || $rawStatus === 'completed') {
    $dbStatus = 'SUCCESS';
} else {
    $dbStatus = 'FAILED';
}

// 4. Process and Update Table (Consolidated to ensure updates run cleanly every time)
if (!empty($reference)) {
    try {
        // SQL Statement to update all fields cleanly for matching reference row
        $sql = "UPDATE wifi_vouchers 
                SET utilityref = :utilityref, 
                    transactionstatus = :transactionstatus 
                WHERE reference = :reference";
        
        $stmt = $pdo->prepare($sql);
        
        // Execute with parameterized bindings
        $stmt->execute([
            ':utilityref'        => $utilityref,
            ':transactionstatus' => $dbStatus, // Saves strict "SUCCESS" or "FAILED" uppercase text
            ':reference'         => $reference
        ]);
        
        error_log("Successfully updated wifi_vouchers for reference: $reference to status: $dbStatus");

    } catch (PDOException $e) {
        error_log("SQL Execution Error: " . $e->getMessage());
    }
} else {
    error_log("Webhook payload received without a valid unique reference parameter.");
}

// 5. Respond back to AzamPay with a 200 OK
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
