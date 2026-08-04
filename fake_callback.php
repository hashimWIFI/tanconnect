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

// ===================================================================
// SECTION 2: FETCHING ROW DATA (Updated to grab price and duration)
// ===================================================================
// NOTE: Add your exact column names for price and duration to this SELECT query if they are in the table!
$selectQuery = "SELECT id, voucher_code, transaction_id, assigned_phone, package_price, duration FROM wifi_vouchers WHERE status = 'PENDING' ORDER BY id DESC LIMIT 1";
$result = $conn->query($selectQuery);

// ... (keep your standard row validation checks here) ...

$row = $result->fetch_assoc();
$allocatedId   = $row['id'];
$voucherCode   = $row['voucher_code'];
$txId          = $row['transaction_id'];
$customer_phone = $row['assigned_phone']; 

// Dynamic Fallbacks: If your database stores them raw, format them nicely here
$packagePrice  = isset($row['package_price']) ? $row['package_price'] : '1000';
$timeDuration  = isset($row['duration']) ? $row['duration'] : 'masaa 24';



// 3. Update database status from PENDING to SUCCESS
echo "Simulating mock successful wallet PIN validation approval ping from AzamPay network...\n";
$updateQuery = "UPDATE wifi_vouchers SET status = 'SUCCESS', purchased_at = NOW() WHERE id = $allocatedId";
$db_update_success = $conn->query($updateQuery);

if ($db_update_success) {
    echo "✓ SUCCESS! Database record updated flawlessly.\n\n";
} else {
    echo "❌ Error updating database state: " . $conn->error . "\n\n";
}

// ===================================================================
// SECTION 4: TEXTBEE AUTOMATION (With your exact dynamic Swahili template)
// ===================================================================
if (!empty($customer_phone)) {
    // Standardizing phone number formatting to +255...
    if (substr($customer_phone, 0, 3) === '255') {
        $customer_phone = '+' . $customer_phone;
    } elseif (substr($customer_phone, 0, 1) === '0') {
        $customer_phone = '+255' . substr($customer_phone, 1);
    } else {
        $customer_phone = '+' . $customer_phone;
    }

    echo "📱 Initiating TextBee gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    $textbee_api_key = getenv('TEXTBEE_API_KEY') ?: 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g'; 
    $textbee_device_id = getenv('TEXTBEE_DEVICE_ID') ?: '6a70f731f83fbea6290c1fff'; 
    
    // 🌍 YOUR EXACT SWAHILI TEMPLATE WIRED DYNAMICALLY
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";
    
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    

    // 💎 PRODUCTION ROADMAP FIX: Forced correction of subdomain routing maps
    $domain_string = "https://api.textbee.dev";
    $folder_string = "/api/v1/gateway/devices/";
    $endpoint_string = "/send-sms";
    
    $api_url = $domain_string . $folder_string . $textbee_device_id . $endpoint_string;
    $ch = curl_init($api_url);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);        
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); 
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $textbee_api_key
    ]);
    
    $textbee_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($textbee_response === false) {
        $curl_error_msg = curl_error($ch);
        echo "❌ Internal Network Error Details: " . $curl_error_msg . "\n";
    }
    
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
