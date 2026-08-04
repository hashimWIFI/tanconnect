<?php
header('Content-Type: text/plain');

echo "=== STAGE 4: PERFECTED AZAMPAY WEBHOOK SIMULATOR ===\n\n";

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

// 2. CAPTURE DYNAMIC INCOMING WEBHOOK PAYLOAD (Matches real world AzamPay data)
// In production, AzamPay sends these metrics to your script via POST/GET data
$target_txId   = $_GET['txId'] ?? $_POST['txId'] ?? null;
$target_phone  = $_GET['phone'] ?? $_POST['phone'] ?? null;
$target_price  = $_GET['price'] ?? $_POST['price'] ?? null;

// Clean formatting of incoming phone numbers for accurate matching
if ($target_phone) {
    $target_phone = str_replace([' ', '-', '(', ')', '+'], '', $target_phone);
    // If it starts with 255, convert it back to standard local 0 format to match database input style
    if (substr($target_phone, 0, 3) === '255') {
        $target_phone = '0' . substr($target_phone, 3);
    }
}

// Validation Step: Ensure the incoming request provides the mandatory billing identifiers
if (!$target_txId || !$target_phone || !$target_price) {
    echo "⚠️ Simulator Alert: Awaiting explicit dynamic transaction parameters.\n";
    echo "Usage Example: fake_callback.php?txId=WIFI-1785860841&phone=0713123974&price=1000\n\n";
    echo "Falling back to safest verification query style (broad scan)...\n";
    
    // Fallback: If parameters aren't explicitly passed in URL, look for the newest pending item
    $selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone, package_price, duration FROM wifi_vouchers WHERE status = 'PENDING' ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($selectQuery);
} else {
    echo "🎯 TARGET CRITERIA RECEIVED:\n";
    echo " - TxID: $target_txId\n";
    echo " - Phone: $target_phone\n";
    echo " - Price: $target_price TZS\n\n";

    // 🔒 PERFECTED PRODUCTION SECURITY MATCH
    // We look for a row where ALL 4 metrics match simultaneously: status, txId, phone, AND price!
    $selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone, package_price, duration FROM wifi_vouchers WHERE status = 'PENDING' AND transaction_id = ? AND (assigned_phone = ? OR assigned_phone = ?) AND package_price = ? LIMIT 1";
    
    $stmt = $conn->prepare($selectQuery);
    // Accommodate variants with leading zeros or raw formats
    $phone_variant = (substr($target_phone, 0, 1) === '0') ? substr($target_phone, 1) : '0' . $target_phone; 
    $stmt->bind_param("ssss", $target_txId, $target_phone, $phone_variant, $target_price);
}

// Execute Targeted Scan
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    echo "\n❌ TRANSACTION VERIFICATION FAILED!\n";
    echo "Reason: No row found matching that combined TxID, Phone, and Price Tier with a PENDING status.\n";
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$allocatedId   = $row['id'];
$voucherCode   = $row['voucher_code'];
$txId          = $row['transaction_id'];
$customer_phone = $row['assigned_phone']; 
$packagePrice  = $row['package_price'] ?? '1000';
$timeDuration  = $row['duration'] ?? 'masaa 24';

echo "✓ TRANSACTION VERIFIED VALID!\n";
echo "Matching Voucher Found: " . $voucherCode . " locked inside Reference Link ID: " . $txId . "\n\n";

// 3. FAKE THE APPROVAL PING: Flip the status cells straight to SUCCESS!
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("i", $allocatedId);

if ($updateStmt->execute() === TRUE) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n\n";
    
    // 🔗 THE MODULAR SPLIT HAND-OFF
    define('TANCONNECT_SECURE_PASS', true);
    include('sms_processor.php'); // Launch your clean Swahili SMS workflow safely!
    
} else {
    echo "❌ Error updating database state: " . $conn->error;
}

$conn->close();
?>
