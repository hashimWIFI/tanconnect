<?php
// Prevent direct browser execution layout bypasses
if (!defined('TANCONNECT_SECURE_PASS')) {
    die("Direct access to processor layer denied.");
}

// 1. PHONE NUMBER FORMATTING (Standardized E.164 international string)
$customer_phone = str_replace([' ', '-', '(', ')', '+'], '', $customer_phone);
if (!empty($customer_phone)) {
    if (substr($customer_phone, 0, 3) === '255') {
        $customer_phone = '+' . $customer_phone;
    } elseif (substr($customer_phone, 0, 1) === '0') {
        $customer_phone = '+255' . substr($customer_phone, 1);
    } else {
        $customer_phone = '+' . $customer_phone;
    }

    echo "📱 Initiating Cost-Optimized Failover Protocol...\n";
    echo "Target Customer Recipient: " . $customer_phone . "\n\n";
    
    // Fetch dynamic package fields from row array or set standard text fallbacks
    $packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';
    $timeDuration = 'Siku 1'; 
    if ($packagePrice == '5000') { $timeDuration = 'Wiki 1'; }

    // 🌍 DYNAMIC SWAHILI SMS TEMPLATE TEXT (Stays under 150 characters safety buffer)
    $sms_message = "Hongera! Umefanikiwa kununua kifurushi cha Wi-Fi cha Tsh " . number_format($packagePrice) . " kitakachotumika kwa muda wa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ".";

    // Initialize tracking state flag
    $sms_sent_successfully = false;

    // ===================================================================
    // 💵 STEP A: ATTEMPT ROUTE 1 - TEXTBEE.DEV (PRIMARY CHEAPEST ROUTE)
    // ===================================================================
    echo "⚡ [PRIMARY ROUTE] Attempting dispatch via TextBee App line...\n";

    $textbee_api_key   = getenv('TEXTBEE_API_KEY');
    $textbee_device_id = getenv('TEXTBEE_DEVICE_ID');

    if (!empty($textbee_api_key) && !empty($textbee_device_id)) {
        
        $textbee_url = "https://textbee.dev" . $textbee_device_id . "/send-sms";
        
        $textbee_payload = json_encode([
            "recipients" => [$customer_phone],
            "message" => $sms_message
        ]);

        $ch1 = curl_init($textbee_url);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 8); // Fast 8-second structural timeout
        curl_setopt($ch1, CURLOPT_TIMEOUT, 12);        
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $textbee_payload);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $textbee_api_key
        ]);

        $textbee_response = curl_exec($ch1);
        $textbee_code     = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        curl_close($ch1);

        echo "TextBee Response Code: " . $textbee_code . "\n";

        if ($textbee_code == 200 || $textbee_code == 201) {
            echo "✅ Primary Route Success! Outbound voucher command dispatched via TextBee.\n";
            $sms_sent_successfully = true;
        } else {
            echo "⚠️ Primary Route Failed (HTTP " . $textbee_code . "). Engaging fallback parameters...\n";
        }
    } else {
        echo "⚠️ Primary Route Skipped: Missing secure environment key values.\n";
    }


    // ===================================================================
    // 🚨 STEP B: AUTOMATED FALLBACK ROUTE - SMS-GATE.APP (BACKUP REPLACEMENT)
    // ===================================================================
    if ($sms_sent_successfully == false) {
        echo "\n🚨 [FAILOVER ENGAGED] Activating backup pipeline via sms-gate.app...\n";
        
        $smsgate_api_key   = getenv('SMSGATE_API_KEY');
        $smsgate_device_id = getenv('SMSGATE_DEVICE_ID');

        if (!empty($smsgate_api_key)) {
            
            // Reverted to your exact verified working 3rdparty URL structure layout
            $api_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";

            
            $smsgate_payload = json_encode([
                "recipients" => [$customer_phone],
                "message" => $sms_message
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

            echo "Smsgate Response Code: " . $smsgate_code . "\n";

            if ($smsgate_code == 200 || $smsgate_code == 202) {
                echo "🚀 Backup Route Success! Voucher successfully processed via sms-gate.app.\n";
                $sms_sent_successfully = true;
            } else {
                echo "❌ Backup Route Defeated: Secondary gateway also rejected transmission request (HTTP " . $smsgate_code . ").\n";
            }
        } else {
            echo "❌ Backup Route Aborted: sms-gate parameters missing inside cloud node configurations.\n";
        }
    }

} else {
    echo "❌ Error Handler Triggered: Extracted customer cell entry is missing or empty.\n";
}
?>
