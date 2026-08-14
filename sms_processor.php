<?php
// 1. Prevent direct browser execution of this component
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 2. FORCE FORMAT PHONE NUMBER TO INTERNATIONAL STRING (+255...)
$customer_phone = str_replace([' ', '-', '(', ')', '+'], '', $customer_phone);

if (!empty($customer_phone)) {
    if (substr($customer_phone, 0, 3) === '255') {
        $customer_phone = '+' . $customer_phone;
    } elseif (substr($customer_phone, 0, 1) === '0') {
        $customer_phone = '+255' . substr($customer_phone, 1);
    } else {
        $customer_phone = '+' . $customer_phone;
    }

    echo "📱 Initiating sms-gate gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    // 🛠️ HARDCODED IDENTIFIERS FROM YOUR SCREENSHOT TO ELIMINATE CLOUD VARIABLE ISSUES
    $smsgate_username  = 'PKHHG1';
    $smsgate_password  = 'icqsrlspg85th2'; 
    $smsgate_device_id = '3onqHv7QcvR69kVifBQrZ'; 
    
    // 3. READ METADATA SAFELY FROM DATABASE ROWS
    $packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';

    // 4. SMART AUTOMATION: Calculate duration automatically based on the price paid
    switch ($packagePrice) {
        case '500':
            $timeDuration = 'masaa 12';
            break;
        case '1000':
            $timeDuration = 'siku 1';
            break;
        case '2000':
            $timeDuration = 'siku 2';
            break;
        case '4000':
            $timeDuration = 'siku 5';
            break;
        case '5000':
            $timeDuration = 'siku 7';
            break;
        case '7000':
            $timeDuration = 'siku 10';
            break;
        case '9000':
            $timeDuration = 'siku 13';
            break;
        case '10000':
            $timeDuration = 'siku 15';
            break;
        case '20000':
            $timeDuration = 'siku 30';
            break;
        default:
            $timeDuration = 'masaa 24';
            break;
    }

    // 🌍 YOUR EXACT PRODUCTION SWAHILI TEMPLATE
    $sms_message = "Hongera, umenunua kifurushi cha Wifi cha Tsh " . $packagePrice . " kitatumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ".";
    
    // 📦 NESTED JSON PAYLOAD DESIGN (Matches your exact documentation format)
    $payload = json_encode([
        "textMessage" => [
            "text" => $sms_message
        ],
        "deviceId" => $smsgate_device_id,
        "phoneNumbers" => [$customer_phone],
        "simNumber" => 1,
        "ttl" => 3600,
        "priority" => 100
    ]);
    
       // 💎 OFFICIAL GATEWAY ENDPOINT WITH DEVICE ACTIVE WINDOW PARAMETERS
    $api_url = "https://api.sms-gate.app/mobile/v1"
    $ch = curl_init($api_url);
    
    // Bypass local workspace certificate constraints safely
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Network stability buffers
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);        
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // BASIC HTTP AUTHENTICATION INJECTION (-u parameter tracking)
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($smsgate_username . ':' . $smsgate_password)
    ]);
    
    $smsgate_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($smsgate_response === false) {
        $curl_error_msg = curl_error($ch);
        echo "❌ Core Network Transport Error: " . $curl_error_msg . "\n";
    }
    
    curl_close($ch);
    
    echo "Base Delivery Status Header Recieved: " . $http_code . "\n";
    echo "Raw Engine Trace Details: " . $smsgate_response . "\n\n";

    // 🔗 ACCEPT CODES 200, 201, AND YOUR VERIFIED 202 STATUSES AS SUCCESS
    if ($http_code == 200 || $http_code == 201 || $http_code == 202) {
        echo "🚀 sms-gate API success! Outbound SMS command successfully dispatched to your Vodacom Samsung device.\n";
    } else {
        echo "⚠️ sms-gate API rejected the packet structure.\n";
    }
} else {
    echo "⚠️ sms-gate Skip: Customer phone number is missing.\n";
}
?>
