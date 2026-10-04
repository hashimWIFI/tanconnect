<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

// 1. DATABASE CONNECTIVITY
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

// 2. Parse Incoming Webhook Payload
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

// CRITICAL DEBUG STEP: Write the exact raw JSON from AzamPay to an error log file
// This lets you see if AzamPay is sending the values in a different casing (e.g., transactionStatus vs transactionstatus)
file_put_contents('azampay_webhook_debug.log', date('[Y-m-d H:i:s] ') . $rawPayload . PHP_EOL, FILE_APPEND);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

// Extract variables safely - checking both lowercase and camelCase just in case
$utilityref        = $data['utilityref'] ?? $data['utilityRef'] ?? null;
$reference         = $data['reference'] ?? null; 
$rawStatus         = $data['transactionstatus'] ?? $data['transactionStatus'] ?? '';
$cleanStatus       = strtolower(trim($rawStatus));

// 3. Normalize Status to Strictly SUCCESS or FAILED
if ($cleanStatus === 'success' || $cleanStatus === 'completed') {
    $dbStatus = 'SUCCESS';
} else {
    $dbStatus = 'FAILED';
}

// 4. Update the Table
if (!empty($reference)) {
    try {
        // SQL Statement using standard reference
        $sql = "UPDATE wifi_vouchers 
                SET utilityref = :utilityref, 
                    transactionstatus = :transactionstatus 
                WHERE reference = :reference";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':utilityref'        => $utilityref,
            ':transactionstatus' => $dbStatus,
            ':reference'         => $reference
        ]);
        
        $rowCount = $stmt->rowCount();
        
        // If 0 rows were updated, it means the column 'reference' does not contain the value $reference
        if ($rowCount === 0) {
            error_log("⚠️ SQL executed successfully, but 0 rows matched reference: $reference. Checking table columns...");
            
            // ALTERNATE ATTEMPT: If your system uses the reference string as the voucher_code itself
            $backupSql = "UPDATE wifi_vouchers 
                          SET utilityref = :utilityref, 
                              transactionstatus = :transactionstatus 
                          WHERE voucher_code = :reference";
            $backupStmt = $pdo->prepare($backupSql);
            $backupStmt->execute([
                ':utilityref'        => $utilityref,
                ':transactionstatus' => $dbStatus,
                ':reference'         => $reference
            ]);
            
            if ($backupStmt->rowCount() > 0) {
                error_log("✅ Backup update fixed it! Matched via voucher_code column instead.");
            }
        } else {
            error_log("✅ Successfully updated $rowCount row(s) to status: $dbStatus");
        }

    } catch (PDOException $e) {
        // Captures if column names don't exist or if an ENUM value constraint blocks the update
        error_log("❌ SQL DB Exception Error: " . $e->getMessage());
    }
} else {
    error_log("❌ Webhook payload received without a valid unique reference parameter.");
}

// 5. Respond back to AzamPay with a 200 OK
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
