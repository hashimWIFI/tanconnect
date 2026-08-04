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
    
    // Read cleanly from Railway environment settings
    $textbee_api_key = getenv('TEXTBEE_API_KEY') ?: 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g'; 
    $textbee_device_id = getenv('TEXTBEE_DEVICE_ID') ?: '6a70f731f83fbea6290c1fff'; 
    
    // Fallbacks to default values if package metadata isn't logged in row
    $packagePrice = isset($packagePrice) ? $packagePrice : '1000';
    $timeDuration = isset($timeDuration) ? $timeDuration : 'masaa 24';

    // 🌍 YOUR EXACT SWAHILI TEMPLATE
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";
    
    $payload = json_encode([
        "recipients" => [$customer_phone],
        "message" => $sms_message
    ]);
    
    // Dynamic string fragments to destroy cached memory references
    $domain_string = "https://textbee.dev";
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
