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

// 2. CORRECT MATCH: Search the database for the most recent PENDING checkout row
echo "Searching table rows for the most recent 'PENDING' checkout transaction...\n";

$selectQuery = "SELECT id, voucher_code, transaction_id FROM wifi_vouchers WHERE status = 'PENDING' ORDER BY id DESC LIMIT 1";
$result = $conn->query($selectQuery);

if (!$result || $result->num_rows == 0) {
    echo "\n❌ NO PENDING VOUCHERS FOUND!\n";
    echo "Reason: All rows inside your 'wifi_vouchers' table are currently set to AVAILABLE or USED.\n";
    echo "Fix: Go to your website storefront, select a package, input a 10-digit number, and hit PAY so a row becomes PENDING first!\n";
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$allocatedId = $row['id'];
$voucherCode = $row['voucher_code'];
$txId        = $row['transaction_id'];

echo "Found pending transaction reference link ID: " . $txId . "\n";
echo "Voucher PIN locked inside this row: " . $voucherCode . "\n\n";

// 3. FAKE THE APPROVAL PING: Flip the status cells straight to SUCCESS!
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";

// This updates the status and records the exact current hour, minute, and second into your fixed timestamp column!
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = $allocatedId";
// 3. FAKE THE APPROVAL PING: Flip the status cells straight to SUCCESS!
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";

// This updates the status and records the exact current hour, minute, and second into your fixed timestamp column!
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = $allocatedId";

// FIX: Run the instruction across your MySQL database connector stream!
$conn->query($updateQuery);

if ($conn->query($updateQuery) === TRUE) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n";
    echo "The voucher state has flipped from PENDING to SUCCESS.\n";
    echo "The exact execution time has been logged in your 'purchased_at' column.\n\n";
    echo "Go look at your active checkout storefront tab—your scrolling marquee will now clear away and reveal your voucher PIN code instantly!";
} else {
    echo "❌ Error updating database state: " . $conn->error;
}

$conn->close();
?>
