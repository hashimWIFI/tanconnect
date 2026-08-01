<?php
header("Content-Type: application/json");
$payload = json_decode(file_get_contents("php://input"), true);
if (!$payload) { http_response_code(400); echo json_encode(["success" => false]); exit(); }
$status = isset($payload['transactionStatus']) ? $payload['transactionStatus'] : ''; 
$extId  = isset($payload['externalId']) ? $payload['externalId'] : ''; 
$phone  = isset($payload['msisdn']) ? $payload['msisdn'] : ''; 
$amount = isset($payload['amount']) ? $payload['amount'] : '';
try {
    $dsn = "pgsql:host=".getenv('DB_HOST').";port=".getenv('DB_PORT').";dbname=".getenv('DB_NAME').";sslmode=require";
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (strtoupper($status) === 'SUCCESS') {
        $tier = '6 Hours'; if ($amount == 1000) $tier = '12 Hours'; if ($amount == 5000) $tier = '7 Days'; if ($amount == 20000) $tier = '30 Days';
        $stmt = $pdo->prepare("SELECT id, voucher_code FROM vouchers WHERE duration = ? AND status = 'Available' LIMIT 1 FOR UPDATE");
        $stmt->execute([$tier]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $pdo->prepare("UPDATE vouchers SET status = 'Used' WHERE id = ?")->execute([$row['id']]);
            $pdo->prepare("UPDATE transactions SET status = 'Success', delivered_voucher = ? WHERE external_id = ?")->execute([$row['voucher_code'], $extId]);
        } else { $pdo->prepare("UPDATE transactions SET status = 'Success' WHERE external_id = ?")->execute([$extId]); }
    } else { $pdo->prepare("UPDATE transactions SET status = 'Failed' WHERE external_id = ?")->execute([$extId]); }
} catch (PDOException $e) { http_response_code(500); exit(); }
http_response_code(200); echo json_encode(["success" => true]);
?>
