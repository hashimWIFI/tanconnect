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

    echo "📱 Initiating TextBee gateway delivery protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n";
    
    // Read cleanly from Railway environment variables
    $textbee_api_key = getenv('TEXTBEE_API_KEY') ?: 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g'; 
    $textbee_device_id = getenv('TEXTBEE_DEVICE_ID') ?: '6a70f731f83fbea6290c1fff'; 
    
    // Fallbacks to default values if package metadata isn't logged in row
    $packagePrice = isset($packagePrice) ? $packagePrice : '1000';
    $timeDuration = isset($timeDuration) ? $timeDuration : 'masaa 24';

    // 🌍 YOUR EXACT SWAHILI TEMPLATE
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $price_tier . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";
    
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    
    // 💎 FIXED STABLE URL: Unified single string as per official textbee quickstart docs
    $api_url = "https://api.textbee.dev/api/v1/gateway/devices/" . $textbee_device_id . "/send-sms";
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
        'x-api-key: ' . $textbee_api_key
    ]);
    
    $textbee_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Base Delivery Status Header Recieved: " . $http_code . "\n";
    echo "Raw Engine Trace Details: " . $textbee_response . "\n\n";

    if ($http_code == 200 || $http_code == 201) {
        echo "🚀 TextBee API success! Outbound SMS command successfully dispatched to your Vodacom Samsung device.\n";
    } else {
        echo "⚠️ TextBee API rejected the packet structure.\n";
    }
} else {
    echo "⚠️ TextBee Skip: Customer phone number is missing.\n";
}

