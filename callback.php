<?php
header('Content-Type: application/json; charset=utf-8');

// Only allow incoming POST requests from AzamPay
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

// 1. DATABASE CONNECTIVITY (Railway Environment)
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

// 2. Parse the Incoming Webhook Payload from AzamPay
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

// Extract variables sent by the AzamPay checkout completion callback
$utilityref        = $data['utilityref'] ?? null;
$reference         = $data['reference'] ?? null; // This matches the reference you created during checkout
$webhookStatus     = isset($data['transactionstatus']) ? strtolower(trim($data['transactionstatus'])) : '';

// 3. Map the customer's actual payment outcome
if ($webhookStatus === 'success' || $webhookStatus === 'completed') {
    $dbStatus = 'SUCCESS';
} else {
    $dbStatus = 'FAILED';
}

// 4. Update the wifi_vouchers Table based on the payment outcome
if (!empty($reference)) {
    try {
        // Find the voucher row that was previously 'ASSIGNED' matching this reference
        // and update its transactionstatus to SUCCESS or FAILED.
        $sql = "UPDATE wifi_vouchers 
                SET transactionstatus = :transactionstatus,
                    purchased_at = NOW() 
                WHERE reference = :reference";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':transactionstatus' => $dbStatus, // Changes 'ASSIGNED' to 'SUCCESS' or 'FAILED'
            ':reference'         => $reference
        ]);
        
        $rowCount = $stmt->rowCount();
        
        if ($rowCount > 0) {
            error_log("✅ Voucher Reference $reference updated successfully from ASSIGNED to $dbStatus");
            
            // OPTIONAL: If the status is SUCCESS, you can trigger your SMS gateway here 
            // to send the voucher_code to the customer's assigned_phone number.
            if ($dbStatus === 'SUCCESS') {
                // Fetch the row data to get the voucher code and phone if needed for SMS
                $fetchStmt = $pdo->prepare("SELECT voucher_code, assigned_phone FROM wifi_vouchers WHERE reference = :reference");
                $fetchStmt->execute([':reference' => $reference]);
                $voucher = $fetchStmt->fetch();
                
                if ($voucher) {
                    error_log("📱 Ready to send Voucher Code: " . $voucher['voucher_code'] . " to Phone: " . $voucher['assigned_phone']);
                }
            }
        } else {
            error_log("⚠️ Webhook received for Reference $reference, but no matching row was modified. (It may already be updated).");
        }

    } catch (PDOException $e) {
        error_log("❌ MySQL Database Exception Error: " . $e->getMessage());
    }
} else {
    error_log("❌ Webhook ignored: No reference key found in the payload data.");
}

// 5. Respond back to AzamPay with a 200 OK so they know you processed it
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Webhook processed successfully"]);
