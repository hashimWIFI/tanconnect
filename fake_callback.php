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

// DYNAMIC EXTRACTION: Safely grab the customer's 10-digit Tigo phone number out of the AzamPay transaction string
preg_match('/(0\d{9})/', $txId, $matches);
$customer_phone = isset($matches[1]) ? $matches[1] : null;

if (!$customer_phone) {
    // If the transaction ID format changed, default back to checking the standard global HTTP form inputs
    $customer_phone = $_POST['phone'] ?? $_GET['phone'] ?? null;
}

// 3. FAKE THE APPROVAL PING: Flip the status cells straight to SUCCESS!
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";

// This updates the status and records the exact current hour, minute, and second into your fixed timestamp column!
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = $allocatedId";

if ($conn->query($updateQuery) === TRUE) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n";
    echo "The voucher state has flipped from PENDING to SUCCESS.\n";
    echo "The exact execution time has been logged in your 'purchased_at' column.\n\n";
    echo "Go look at your active checkout storefront tab—your scrolling marquee will now clear away and reveal your voucher PIN code instantly!\n\n";
    
    // 4. TEXTBEE AUTOMATION FOR VODACOM SIM CARD BLAST
    if ($customer_phone) {
        echo "📱 Initiating TextBee gateway delivery protocol...\n";
        echo "Target Customer Recipient: " . $customer_phone . "\n";
        
        // Your active production keys from your dashboard graphics
        $textbee_api_key = "txb_Pen4O2nCIdT6D42VpZndfM11wK6gfeK0S9P3V9H1"; 
        $textbee_device_id = "6a70f7cf-fa75-4700-aaec-3efdb3672957"; 
        
        // Customize the message content containing your live fetched database voucher
        $sms_message = "Your secure transaction voucher PIN code is: " . $voucherCode;
        
        $payload = json_encode([
            "recipients" => [$customer_phone],
            "message" => $sms_message
        ]);
        
        // Execute the direct secure REST call to TextBee's Cloud Gateway
        $ch = curl_init("https://textbee.dev{$textbee_device_id}/sendSync-sms");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "x-api-key: {$textbee_api_key}"
        ]);
        
        $textbee_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 || $http_code == 201) {
            echo "🚀 TextBee API success! Outbound SMS command successfully dispatched to your Vodacom Samsung device.\n";
        } else {
            echo "⚠️ TextBee API returned unexpected status code: " . $http_code . "\n";
            echo "Response payload details: " . $textbee_response . "\n";
        }
    } else {
        echo "⚠️ TextBee Skip: Could not automatically detect a valid 10-digit mobile phone number inside transaction target text.\n";
    }
    
} else {
    echo "❌ Error updating database state: " . $conn->error;
}

$conn->close();
?>

