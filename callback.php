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

// Extract payment details safely from the incoming payload
$utilityref        = $data['utilityref'] ?? null; 
$reference         = $data['reference'] ?? null;  
$webhookStatus     = isset($data['transactionstatus']) ? strtolower(trim($data['transactionstatus'])) : '';

// Map the webhook payload string strictly to uppercase SUCCESS or FAILED
if ($webhookStatus === 'success' || $webhookStatus === 'completed') {
    $dbStatus = 'SUCCESS';
} else {
    $dbStatus = 'FAILED';
}

// 3. Process and Update the Table using the existing callback_response column
if (!empty($reference)) {
    try {
        // Step A: Attempt strict matching via BOTH reference and utilityref
        $sql = "UPDATE wifi_vouchers 
                SET transactionstatus = :transactionstatus,
                    callback_response = :callback_response,
                    purchased_at = NOW()
                WHERE reference = :reference 
                  AND utilityref = :utilityref";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transactionstatus' => $dbStatus,
            ':callback_response' => $rawPayload, // Writes the entire raw AzamPay payload text string
            ':reference'         => $reference,
            ':utilityref'        => $utilityref
        ]);
        
        // Step B: Fallback if the strict combination matched 0 rows (e.g., due to whitespace or case issues)
        if ($stmt->rowCount() === 0) {
            error_log("⚠️ Strict dual-match found 0 rows. Attempting fallback matching via reference string alone...");
            
            $fallbackSql = "UPDATE wifi_vouchers 
                            SET transactionstatus = :transactionstatus,
                                callback_response = :callback_response,
                                purchased_at = NOW()
                            WHERE reference = :reference";
            
            $fallbackStmt = $pdo->prepare($fallbackSql);
            $fallbackStmt->execute([
                ':transactionstatus' => $dbStatus,
                ':callback_response' => $rawPayload,
                ':reference'         => $reference
            ]);
            
            if ($fallbackStmt->rowCount() > 0) {
                error_log("✅ Fallback Update Successful! Row updated using reference key match.");
            } else {
                error_log("❌ Failure: Both update attempts returned 0 modified rows. Reference target: $reference");
            }
        } else {
            error_log("✅ Primary Update Successful! Matched both reference and utilityref codes.");
        }

    } catch (PDOException $e) {
        // Captures strict SQL constraint breaks (e.g. if transactionstatus is blocked by ENUM keys)
        error_log("❌ MySQL Callback Writer Exception: " . $e->getMessage());
    }
} else {
    error_log("❌ Webhook ignored: Incoming data stream missing structural 'reference' tracker.");
}

// 4. Always respond 200 OK to AzamPay so they clear the request out of their queue
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
