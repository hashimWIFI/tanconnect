<?php
header('Content-Type: text/plain');

echo "=== STAGE 4: AZAMPAY BACKDOOR WEBHOOK SIMULATOR WITH TEXTBEE ===\n\n";

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
$selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone FROM wifi_vouchers WHERE status = 'PENDING' ORDER BY id DESC LIMIT 1";
$result = $conn->query($selectQuery);

if (!$result || $result->num_rows == 0) {
    echo "\n❌ NO PENDING VOUCHERS FOUND!\n";
    $conn->close();
    exit();
}

$row = $result->fetch_assoc();
$allocatedId = $row['id'];
$voucherCode = $row['voucher_code'];
$txId        = $row['transaction_id'];
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
} else {
    echo "❌ Error updating database state: " . $conn->error . "\n\n";
}

// 4. TEXTBEE AUTOMATION FOR VODACOM SIM CARD BLAST
$customer_phone = str_replace([' ', '-', '(', ')', '+'], '', $customer_phone);

if (!empty($customer_phone)) {
    // Force format phone number to international string (+255...)
    if (substr($customer_phone, 0, 3) === '255') {
        $customer_phone = '+' . $customer_phone;
    } elseif (substr($customer_phone, 0, 1) === '0') {
        $customer_phone = '+255' . substr($customer_phone, 1);
    } else {
        $customer_phone = '+' . $customer_phone;
    }

    echo "📱 Initiating TextBee gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    // Credentials matching your visual active dashboards
    $textbee_api_key = "txb_nr4AZvvZoncnKwhsgTKJufStKToas52g"; 
    $textbee_device_id = "6a70f731f83fbea6290c1fff"; 
    $sms_message = "Your secure transaction voucher PIN code is: " . $voucherCode;
    
    // Payload architecture matching the exact developer document layout
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    
    // Correct URL Concatenation from the document screenshot
    $api_url = "https://textbee.dev" . $textbee_device_id . "/send-sms";
    $ch = curl_init($api_url);
    
    // Explicit bypass configs for server environments
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $textbee_api_key
    ]);
    
    $textbee_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📡 TextBee Server HTTP Code: " . $http_code . "\n";
    echo "📝 Response Details: " . $textbee_response . "\n\n";
    
    if ($http_code == 200 || $http_code == 201) {
        echo "🚀 TextBee API success! Outbound SMS command successfully dispatched to your Vodacom Samsung device.\n";
    } else {
        echo "⚠️ TextBee API rejected the packet structure.\n";
    }
} else {
    echo "⚠️ TextBee Skip: Customer phone number is missing.\n";
}

$conn->close();
?>
