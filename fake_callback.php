<?php
header('Content-Type: text/plain');

echo "=== STAGE 4: AZAMPAY BACKDOOR WEBHOOK SIMULATOR ===\n\n";

// 1. Establish database connection metrics automatically through cloud variables
$db_host = getenv('MYSQLHOST') ?: '127.0.0.1';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("❌ DATABASE CONNECTION FAILED: " . $conn->connect_error);
}

echo "✓ Connected to MySQL Database successfully.\n";

// 2. Search database for the most recent PENDING checkout row
echo "Searching table rows for the most recent 'PENDING' checkout transaction...\n";
// Pulling price_tier directly to match your custom billing matrix columns
$selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone, price_tier FROM wifi_vouchers WHERE status = 'PENDING' ORDER BY id DESC LIMIT 1";
$result = $conn->query($selectQuery);

if (!$result || $result->num_rows == 0) {
    echo "\n❌ NO PENDING VOUCHERS FOUND!\n";
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$allocatedId   = $row['id'];
$voucherCode   = $row['voucher_code'];
$txId          = $row['transaction_id'];
$customer_phone = $row['assigned_phone']; 

echo "Found pending transaction reference link ID: " . $txId . "\n";
echo "Voucher PIN locked inside this row: " . $voucherCode . "\n";
echo "Target customer phone extracted directly: " . $customer_phone . "\n\n";

// 3. Update database status from PENDING to SUCCESS
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = $allocatedId";
$db_update_success = $conn->query($updateQuery);

if ($db_update_success) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n\n";
    
    // 🔗 THE CRITICAL LAUNCH HAND-OFF: Forces the server to load your SMS processor script!
    define('TANCONNECT_SECURE_PASS', true);
    include('sms_processor.php'); 
    
} else {
    echo "❌ Error updating database state: " . $conn->error . "\n\n";
}

$conn->close();
?>
