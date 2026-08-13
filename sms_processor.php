<?php
// Prevent direct browser execution of this component if necessary
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 1. STANDARD COMPLIANT PHONE NUMBER FORMATTER
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

    echo "📱 Initiating sms-gate gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    // Read cleanly from your secure Railway environment settings
    $smsgate_api_key = getenv('SMSGATE_API_KEY') ?: 'icqsrlspg85th2';
    $smsgate_device_id = getenv('SMSGATE_DEVICE_ID') ?: '3onqHv7QcvR69kVifBQrZ'; 
    
    // Grab your real database column name
    $packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';

    // 2. SMART AUTOMATION: Calculate duration automatically based on the price paid
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
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";
    
    // 📦 SMS-GATE PAYLOAD FRAMEWORK (Array layout matching their native gateway payload rules)
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    
    // 💎 FIX 1: Corrected single path string map containing the mandatory forward slash dividers
    $api_url = "https://sms-gate.app" . $smsgate_device_id . "/send-sms";
    $ch = curl_init($api_url);
    
    // Bypass local environment SSL restrictions safely
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);        
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // 🔒 FIX 2: Standard Authorization Header to clear out the 401 block instantly
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . $smsgate_api_key
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
?>
