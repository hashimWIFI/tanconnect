<?php
// Prevent direct browser execution of this component
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 1. CLEAN AND FORMAT THE PHONE NUMBER
$base_phone = str_replace([' ', '-', '(', ')', '+'], '', $customer_phone);

if (!empty($base_phone)) {
    if (substr($base_phone, 0, 3) === '255') {
        $clean_phone = '+' . $base_phone;
    } elseif (substr($base_phone, 0, 1) === '0') {
        $clean_phone = '+255' . substr($base_phone, 1);
    } else {
        $clean_phone = '+' . $base_phone;
    }

    // 2. BUILD YOUR SWAHILI SMS MESSAGE TEXT
    $packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';
    switch ($packagePrice) {
        case '500': $timeDuration = 'masaa 12'; break;
        case '1000': $timeDuration = 'siku 1'; break;
        case '2000': $timeDuration = 'siku 2'; break;
        case '4000': $timeDuration = 'siku 5'; break;
        case '5000': $timeDuration = 'siku 7'; break;
        case '10000': $timeDuration = 'siku 15'; break;
        case '20000': $timeDuration = 'siku 30'; break;
        default: $timeDuration = 'masaa 24'; break;
    }
    
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";

    $sms_sent = false;

    // ===================================================================
    // 🚀 STEP 1: RUN TEXTBEE AS PRIMARY ROUTE
    // ===================================================================
    $tb_api_key   = getenv('TEXTBEE_API_KEY') ?: 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g';
    $tb_device_id = getenv('TEXTBEE_DEVICE_ID') ?: '6a70f731f83fbea6290c1fff';

    if (!empty($tb_api_key) && !empty($tb_device_id)) {
        echo "📱 Attempting Primary Route (TextBee) for: " . $clean_phone . "\n";
        
        $tb_url = "https://api.textbee.dev/api/v1/gateway/devices/" . $textbee_device_id . "/send-sms";
        $tb_payload = json_encode([
            "recipients" => [$clean_phone],
            "message"    => $sms_message
        ]);

        $ch1 = curl_init($tb_url);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 12);
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

        if ($tb_code == 200 || $tb_code == 201) {
            echo "✅ TextBee Success! Message sent via local device.\n";
            $sms_sent = true;
        }
    }

    // ===================================================================
    // 🔀 STEP 2: FALLBACK TO SMSGATE (USING YOUR DOCUMENTED 3RDPARTY PATH)
    // ===================================================================
    if ($sms_sent === false) {
        echo "🚨 TextBee did not respond or failed. Running Smsgate fallback...\n";

        // Hardcoded matching your verified working parameters layout
        $smsgate_username  = 'PKHHG1';
        $smsgate_password  = 'icqsrlspg85th2'; 
        $smsgate_device_id = '3onqHv7QcvR69kVifBQrZ'; 

        // 📦 NESTED OBJECT PAYLOAD MATCHING YOUR CURL DOCUMENTATION FORMAT EXHAUSTIVELY
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

        // Aligned perfectly with your active query string parameters
            $smsgate_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";

        $ch2 = curl_init($smsgate_url);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 12);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 18);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $smsgate_payload);
        
        // Injecting the Base64 Basic Authentication header to clear the 401 block
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($smsgate_username . ':' . $smsgate_password)
        ]);

        $smsgate_response = curl_exec($ch2);
        $smsgate_code     = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        echo "Smsgate Fallback Response Code: " . $smsgate_code . "\n";

        // Accepting your verified 202 Accepted status header
        if ($smsgate_code == 200 || $smsgate_code == 201 || $smsgate_code == 202) {
            echo "🚀 Smsgate Fallback Success! Message dispatched.\n";
            $sms_sent = true;
        } else {
            echo "❌ Both TextBee and Smsgate failed to process the SMS. Trace: " . $smsgate_response . "\n";
        }
    }

} else {
    echo "⚠️ SMS Skip: Empty phone number parameter.\n";
}
?>
