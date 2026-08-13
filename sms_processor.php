<?php
// Prevent direct browser execution of this component if necessary
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 1. TEXTBEE AUTOMATION FOR VODACOM SIM CARD BLAST
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

    echo "📱 Initiating smsgate gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    // Read cleanly from Railway environment variables
    $smsgate_api_key = getenv('SMSGATE_API_KEY') ?: 'icqsrlspg85th2';
    $smsgate_device_id = getenv('SMSGATE_DEVICE_ID') ?: '3onqHv7QcvR69kVifBQrZ'; 
    
    // Fallbacks to default values if package metadata isn't logged in row
    // 1. Grab your real database column name
$packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';

// 2. SMART AUTOMATION: Calculate duration automatically based on the price paid
switch ($packagePrice) {
    case '500':
        $timeDuration = 'masaa 12'; // 500 TZS package duration
        break;
    case '1000':
        $timeDuration = 'siku 1'; // 1000 TZS package duration
        break;
    case '2000':
        $timeDuration = 'siku 2';    // Example: 5000 TZS for 7 days
        break;
    case '4000':
        $timeDuration = 'siku 5';    // Example: 5000 TZS for 7 days
        break;
    case '5000':
        $timeDuration = 'siku 7';    // Example: 5000 TZS for 7 days
        break;
    case '7000':
        $timeDuration = 'siku 10';    // Example: 5000 TZS for 7 days
        break;
    case '9000':
        $timeDuration = 'siku 13';    // Example: 5000 TZS for 7 days
        break;
    case '10000':
        $timeDuration = 'siku 15';    // Example: 5000 TZS for 7 days
        break;
    case '20000':
        $timeDuration = 'siku 30';    // Example: 5000 TZS for 7 days
        break;
    default:
        $timeDuration = 'masaa 24'; // Standard fallback if price doesn't match
        break;
}


    // 🌍 YOUR EXACT SWAHILI TEMPLATE
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";
    
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    
    // 💎 FIXED STABLE URL: Unified single string as per official smsgate quickstart docs
    $api_url = "https://api.sms-gate.app/mobile/v1" . $smsgate_device_id . "/send-sms";
    $ch = curl_init($api_url);
    
    // Bypass local environment SSL restrictions safely
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // Timeout limits to avoid infinite hanging loops
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);        
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // Standard system API headers without custom user-agent blocks
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $smsgate_api_key
    ]);
    
    $smsgate_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Base Delivery Status Header Recieved: " . $http_code . "\n";
    echo "Raw Engine Trace Details: " . $smsgate_response . "\n\n";

    if ($http_code == 200 || $http_code == 201) {
        echo "🚀 smsgate API success! Outbound SMS command successfully dispatched to your Vodacom Samsung device.\n";
    } else {
        echo "⚠️ smsgate API rejected the packet structure.\n";
    }
} else {
    echo "⚠️ smsgate Skip: Customer phone number is missing.\n";
}


