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

    // 2. META DATA EXTRACTOR
    $packagePrice = '1000'; 
    $timeDuration = 'siku 1';
    
    if (isset($row) && is_array($row)) {
        if (isset($row['price_tier'])) {
            $packagePrice = $row['price_tier'];
        } elseif (isset($row['price'])) {
            $packagePrice = $row['price'];
        }
    }
    
    switch ($packagePrice) {
        case '500': $timeDuration = 'masaa 12'; break;
        case '1000': $timeDuration = 'siku 1'; break;
        case '2000': $timeDuration = 'siku 2'; break;
        case '5000': $timeDuration = 'siku 7'; break;
        default: $timeDuration = 'siku 1'; break;
    }
    
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";

    $sms_sent = false;

    // ===================================================================
    // 🚀 STEP 1: TEXTBEE PRIMARY ROUTE (URL-ENCODED FORMAT FIX)
    // ===================================================================
    $tb_api_key   = 'txb_u0liKgZdszGYc7NyXsOannnd4c6vqnlk';
    $tb_device_id = '6a742479f83fbea62920b02f';

    echo "📱 Running Primary Route (TextBee) for: " . $clean_phone . "\n";
    
    $tb_url = "https://api.textbee.dev/api/v1/gateway/devices/" . $tb_device_id . "/send-sms";
 
    // 💡 FIX 1: Format fields as a flat string payload map, matching standard curl -d behaviors
    $tb_payload = json_encode([
        "recipients" => [$clean_phone],
        "message"    => $sms_message
    ]);

    $ch_textbee = curl_init();
    curl_setopt($ch_textbee, CURLOPT_URL, $tb_url);
    curl_setopt($ch_textbee, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_textbee, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch_textbee, CURLOPT_CONNECTTIMEOUT, 12);
    curl_setopt($ch_textbee, CURLOPT_TIMEOUT, 18);
    curl_setopt($ch_textbee, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_textbee, CURLOPT_POST, true);
    curl_setopt($ch_textbee, CURLOPT_POSTFIELDS, $tb_payload);
    curl_setopt($ch_textbee, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $tb_api_key
    ]);

    $tb_response = curl_exec($ch_textbee);
    $tb_code     = curl_getinfo($ch_textbee, CURLINFO_HTTP_CODE);
    curl_close($ch_textbee);

    echo "TextBee Response Code: " . $tb_code . "\n";
    echo "TextBee Raw Trace Feedback: " . $tb_response . "\n\n";

    if ($tb_code == 200 || $tb_code == 201) {
        echo "✅ TextBee Success! Message sent via local device.\n";
        $sms_sent = true;
    }

    // ===================================================================
    // 🔀 STEP 2: FALLBACK TO SMSGATE (VERIFIED WORKING FORMAT RESTORATION)
    // ===================================================================
    if ($sms_sent === false) {
        echo "🚨 TextBee Route Failed. Triggering Smsgate backup...\n";

        $smsgate_username  = 'PKHHG1';
        $smsgate_password  = 'icqsrlspg85th2'; 
        $smsgate_device_id = '3onqHv7QcvR69kVifBQrZ'; 

        // 💡 FIX 2: Restored your exact nested payload schema that generated your 202 code
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

        // 💡 FIX 3: Restored your exact 3rdparty endpoint layout string to clear the 405 error
        $smsgate_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";
 

        $ch_smsgate = curl_init();
        curl_setopt($ch_smsgate, CURLOPT_URL, $smsgate_url);
        curl_setopt($ch_smsgate, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_smsgate, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch_smsgate, CURLOPT_CONNECTTIMEOUT, 12);
        curl_setopt($ch_smsgate, CURLOPT_TIMEOUT, 18);
        curl_setopt($ch_smsgate, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_smsgate, CURLOPT_POST, true);
        curl_setopt($ch_smsgate, CURLOPT_POSTFIELDS, $smsgate_payload);
        curl_setopt($ch_smsgate, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($smsgate_username . ':' . $smsgate_password)
        ]);

        $smsgate_response = curl_exec($ch_smsgate);
        $smsgate_code     = curl_getinfo($ch_smsgate, CURLINFO_HTTP_CODE);
        curl_close($ch_smsgate);

        echo "Smsgate Fallback Response Code: " . $smsgate_code . "\n";
        echo "Smsgate Trace Details: " . $smsgate_response . "\n\n";
    }

} else {
    echo "⚠️ SMS Skip: Empty phone number parameter.\n";
}
?>
