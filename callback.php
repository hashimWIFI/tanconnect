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

// 2. Parse Incoming Webhook Payload from AzamPay
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

// Extract variables safely from incoming payload
$utilityref        = $data['utilityref'] ?? null; // e.g., "AZM01a106a59fae..."
$reference         = $data['reference'] ?? null;  // e.g., "NITW-1791112877"
$webhookStatus     = isset($data['transactionstatus']) ? strtolower(trim($data['transactionstatus'])) : '';

// 3. Map the payment outcome to your strict database state
if ($webhookStatus === 'success' || $webhookStatus === 'completed') {
    $dbStatus = 'SUCCESS';
} else {
    $dbStatus = 'FAILED';
}

// 4. Update the Table matching BOTH critical keys to eliminate collisions
if (!empty($reference) && !empty($utilityref)) {
    try {
        // Strict double-matching WHERE clause guarantees no two overlapping transactions collide
        $sql = "UPDATE wifi_vouchers 
                SET transactionstatus = :transactionstatus,
                    purchased_at = NOW()
                WHERE reference = :reference 
                  AND utilityref = :utilityref";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transactionstatus' => $dbStatus, // Flips 'ASSIGNED' strictly to 'SUCCESS' or 'FAILED'
            ':reference'         => $reference,
            ':utilityref'        => $utilityref
        ]);
        
        // Debug confirmation line
        error_log("AzamPay Callback Handler -> Dual-matched Reference: $reference and UtilityRef: $utilityref. Status updated to: $dbStatus");

    } catch (PDOException $e) {
        error_log("❌ MySQL Webhook Update Error: " . $e->getMessage());
    }
} else {
    error_log("❌ Webhook ignored: Missing tracking parameters (reference or utilityref empty).");
}

// 5. Always respond 200 OK to AzamPay to clear the queue callback
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
