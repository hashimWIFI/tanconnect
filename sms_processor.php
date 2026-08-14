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
        case '500': $timeDuration = 'Masaa 12'; break;
        case '1000': $timeDuration = 'Siku 1'; break;
        case '5000': $timeDuration = 'Wiki 1'; break;
        default: $timeDuration = 'Siku 1'; break;
    }
    
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";

    // Track delivery state
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
    // 🔀 STEP 2: IF TEXTBEE FAILED, TRIGGER SMSGATE AS FALLBACK
    // ===================================================================
    if ($sms_sent === false) {
        echo "🚨 TextBee did not respond or failed. Running Smsgate fallback...\n";

        $smsgate_api_key = getenv('SMSGATE_API_KEY');
        
        if (!empty($smsgate_api_key)) {
            $smsgate_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";

            $smsgate_payload = json_encode([
                "recipients" => [$clean_phone],
                "message"    => $sms_message
            ]);

            $ch2 = curl_init($smsgate_url);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $smsgate_payload);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: ' . $smsgate_api_key
            ]);

            $smsgate_response = curl_exec($ch2);
            $smsgate_code     = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            echo "Smsgate Fallback Response Code: " . $smsgate_code . "\n";

            if ($smsgate_code == 200 || $smsgate_code == 202) {
                echo "🚀 Smsgate Fallback Success! Message dispatched.\n";
                $sms_sent = true;
            } else {
                echo "❌ Both TextBee and Smsgate failed to process the SMS.\n";
            }
        } else {
            echo "❌ Smsgate credentials missing in environment variables.\n";
        }
    }

} else {
    echo "⚠️ SMS Skip: Empty phone number parameter.\n";
}
?>
