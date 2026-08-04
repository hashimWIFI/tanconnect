<?php
header('Content-Type: text/plain');

echo "=== STAGE 4: UNIVERSAL BILLING WEBHOOK GATEWAY ===\n\n";

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

// ===================================================================
// 2. UNIVERSAL PAYLOAD CAPTURE LAYER (Reads ANY incoming data stream)
// ===================================================================

// Try to capture raw incoming background JSON data (Real-world Webhook Style)
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

if (!empty($json_data)) {
    // Reading straight out of the universal JSON request packet
    $target_txId  = $json_data['TransactionId'] ?? $json_data['txId'] ?? null;
    $target_phone = $json_data['Msisdn'] ?? $json_data['phone'] ?? null;
    $target_price = $json_data['Amount'] ?? $json_data['price'] ?? null;
    echo "📥 Received raw background JSON data stream.\n";
} else {
    // Fallback: Read directly from standard HTTP form variables or Browser URL inputs
    $target_txId  = $_REQUEST['txId'] ?? $_REQUEST['transaction_id'] ?? null;
    $target_phone = $_REQUEST['phone'] ?? $_REQUEST['msisdn'] ?? null;
    $target_price = $_REQUEST['price'] ?? $_REQUEST['amount'] ?? null;
    echo "🌐 Received standard Web browser parameter request.\n";
}

// Clean phone formatting rules dynamically on the fly
if ($target_phone) {
    $target_phone = str_replace([' ', '-', '(', ')', '+'], '', $target_phone);
    // Standardize international country code structures to simple local digits matching DB
    if (substr($target_phone, 0, 3) === '255') {
        $target_phone = '0' . substr($target_phone, 3);
    }
}

// ===================================================================
// 3. TARGETED DATABASE BINDING & VERIFICATION PROTOCOL
// ===================================================================

if (!$target_txId || !$target_phone || !$target_price) {
    echo "\n⚠️ UNIVERSAL CONTROLLER: Awaiting explicit dynamic parameters.\n";
    echo "Browser Test Form: fake_callback.php?txId=WIFI-1785860841&phone=0713123974&price=1000\n";
    $conn->close();
    exit();
}

echo "🎯 PROCESSING METRICS:\n";
echo " - TxID:  $target_txId\n";
echo " - Phone: $target_phone\n";
echo " - Price: $target_price TZS\n\n";

// Strict validation: Ensures everything lines up perfectly under a single query command thread
$selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone, package_price, duration FROM wifi_vouchers WHERE status = 'PENDING' AND transaction_id = ? AND (assigned_phone = ? OR assigned_phone = ?) AND package_price = ? LIMIT 1";

$stmt = $conn->prepare($selectQuery);
$phone_variant = (substr($target_phone, 0, 1) === '0') ? substr($target_phone, 1) : '0' . $target_phone; 
$stmt->bind_param("ssss", $target_txId, $target_phone, $phone_variant, $target_price);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    echo "❌ SECURITY DENIED: No matching PENDING transaction found for that specific customer, price, and reference pair.\n";
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$allocatedId   = $row['id'];
$voucherCode   = $row['voucher_code'];
$txId          = $row['transaction_id'];
$customer_phone = $row['assigned_phone']; 
$packagePrice  = $row['package_price']; // Dynamic assignment matching customer's real database entry
$timeDuration  = $row['duration'] ?? 'masaa 24'; // Pulls the exact time block they bought

echo "✓ DATA MATCH CONFIRMED VALID!\n";
echo "Voucher [ $voucherCode ] successfully locked to record row ID: $allocatedId\n\n";

// ===================================================================
// 4. TRANSACTION RESOLUTION & TRANSMISSION HAND-OFF
// ===================================================================

echo "Executing production status commitment protocol to database...\n";
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("i", $allocatedId);

if ($updateStmt->execute() === TRUE) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n\n";
    
    // Launch separate SMS automation script 
    define('TANCONNECT_SECURE_PASS', true);
    include('sms_processor.php'); 
    
} else {
    echo "❌ Error updating database state: " . $conn->error;
}

$conn->close();
?>
