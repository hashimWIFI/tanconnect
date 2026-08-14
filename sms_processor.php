<?php
// Prevent direct browser execution of this component
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 1. CLEAN AND UNIVERSAL PHONE NUMBER FORMATTER
$base_phone = str_replace([' ', '-', '(', ')', '+'], '', $customer_phone);

if (!empty($base_phone)) {
    if (substr($base_phone, 0, 3) === '255') {
        $clean_phone = '+' . $base_phone;
    } elseif (substr($base_phone, 0, 1) === '0') {
        $clean_phone = '+255' . substr($base_phone, 1);
    } else {
        $clean_phone = '+' . $base_phone;
    }

    // 2. 💡 HARDCODED SAFETY CONTROLS: Read directly out of active script parameters
    // This completely bypasses any database column naming issues ($row vs $result)
    $packagePrice = '1000'; // Default test price anchor
    $timeDuration = 'siku 1';
    
    // If the database data is available, read it natively
    if (isset($row) && is_array($row)) {
        if (isset($row['price_tier'])) {
            $packagePrice = $row['price_tier'];
        } elseif (isset($row['price'])) {
            $packagePrice = $row['price'];
        }
    }
    
    // Recalculate duration using standard clean strings
    switch ($packagePrice) {
        case '500': $timeDuration = 'masaa 12'; break;
        case '1000': $timeDuration = 'siku 1'; break;
        case '2000': $timeDuration = 'siku 2'; break;
        case '5000': $timeDuration = 'siku 7'; break;
        default: $timeDuration = 'siku 1'; break;
    }
    
    // 🌍 VERIFIED PRODUCTION SWAHILI TEMPLATE STRING
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";

    $sms_sent = false;

    // ===================================================================
    // 🚀 STEP 1: TEXTBEE ROUTE (PRIMARY)
    // ===================================================================
    $tb_api_key   = 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g';
    $tb_device_id = '6a70f731f83fbea6290c1fff';

    echo "📱 Running Primary Route (TextBee) for: " . $clean_phone . "\n";
    

    $api_url = "https://api.textbee.dev/api/v1/gateway/devices/" . $tb_device_id . "/send-sms";
    $tb_payload = json_encode([
        "recipients" => [$clean_phone],
        "message"    => $sms_message
    ]);

    $ch1 = curl_init($tb_url);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch1, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_POST, true);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, $tb_payload);
    curl_setopt($ch1, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $tb_api_key
    ]);

    $tb_response = curl_exec($ch1);
    $tb_code     = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
    curl_close($ch1);

    echo "TextBee Response Code: " . $tb_code . "\n";
    echo "TextBee Raw Trace Feedback: " . $tb_response . "\n\n";

    if ($tb_code == 200 || $tb_code == 201) {
        echo "✅ TextBee Success! Message sent via local device.\n";
        $sms_sent = true;
    }

    // ===================================================================
    // 🔀 STEP 2: FALLBACK TO SMSGATE (ONLY IF TEXTBEE METADATA COLLIDES)
    // ===================================================================
    if ($sms_sent === false) {
        echo "🚨 TextBee Route Failed. Triggering Smsgate backup...\n";

        $smsgate_username  = 'PKHHG1';
        $smsgate_password  = 'icqsrlspg85th2'; 
        $smsgate_device_id = '3onqHv7QcvR69kVifBQrZ'; 

        $smsgate_payload = json_encode([
            "textMessage" => [
                "text" => $sms_message
            ],
            "deviceId" => $smsgate_device_id,
            "phoneNumbers" => [$clean_phone],
            "simNumber" => 1,
            "ttl" => 3600,
            "priority" => 100
        ]);
   
  

        $smsgate_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";

        $ch2 = curl_init($smsgate_url);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $smsgate_payload);
        
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($smsgate_username . ':' . $smsgate_password)
        ]);

        $smsgate_response = curl_exec($ch2);
        $smsgate_code     = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        echo "Smsgate Fallback Response Code: " . $smsgate_code . "\n";
    }

} else {
    echo "⚠️ SMS Skip: Empty phone number parameter.\n";
}
?>

