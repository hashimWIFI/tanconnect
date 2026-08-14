<?php
// Prevent direct browser execution of this component
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

    // 2. READ METADATA SAFELY FROM DATABASE ROWS
    $packagePrice = isset($row['price_tier']) ? $row['price_tier'] : '1000';

    // 3. COMPLETE DYNAMIC BILLING ENGINE: Maps every package price to its exact duration
    switch ($packagePrice) {
        case '500':
            $timeDuration = 'Masaa 12';
            break;
        case '1000':
            $timeDuration = 'Siku 1';
            break;
        case '2000':
            $timeDuration = 'Siku 2';
            break;
        case '4000':
            $timeDuration = 'Siku 5';
            break;
        case '5000':
            $timeDuration = 'Wiki 1';
            break;
        case '7000':
            $timeDuration = 'Siku 10';
            break;
        case '9000':
            $timeDuration = 'Siku 13';
            break;
        case '10000':
            $timeDuration = 'Siku 15';
            break;
        case '20000':
            $timeDuration = 'Siku 30';
            break;
        default:
            $timeDuration = 'Masaa 24'; // Protective system fallback
            break;
    }

    // 🌍 YOUR EXACT PRODUCTION SWAHILI TEMPLATE
    $sms_message = "Hongera, umefanikiwa kununua kifurushi cha Wifi cha " . $packagePrice . " TZS kutoka TANConnect kitakachotumika kwa " . $timeDuration . ". Voucher yako ni " . $voucherCode . ". ASANTE.";

    // 4. HARDCODED PRIMARY ROUTE: TEXTBEE CONFIGURATION
    $textbee_api_key   = 'txb_nr4AZvvZoncnKwhsgTKJufStKToas52g';
    $textbee_device_id = '6a70f731f83fbea6290c1fff';
    $sms_sent          = false;

    if (!empty($textbee_api_key) && !empty($textbee_device_id)) {
        echo "📱 Attempting Primary Route (TextBee) for: " . $customer_phone . "\n";

        
        $tb_url     = ""https://api.textbee.dev/api/v1/gateway/devices/" . $textbee_device_id . "/send-sms";
        $tb_payload = json_encode([
            "recipients" => [$customer_phone],
            "message"    => $sms_message
        ]);

        $ch1 = curl_init($tb_url);
        // 🛠️ ADD THESE TWO LINES HERE TO FIX TEXTBEE HTTP CODE 0:
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $tb_payload);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $textbee_api_key
        ]);

        $tb_response = curl_exec($ch1);
        $tb_http_code = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        curl_close($ch1);

        if ($tb_http_code == 200 || $tb_http_code == 201) {
            echo "🚀 TextBee API success! SMS sent via primary line.\n";
            $sms_sent = true;
        } else {
            echo "⚠️ TextBee API failed (HTTP Code: $tb_http_code). Engaging Failover...\n";
        }
    } else {
        echo "Primary Route Skipped: Hardcoded TextBee tokens are empty. Engaging Failover...\n";
    }

    // 5. FAILOVER ROUTE: SMS-GATE.APP CONFIGURATION
    if (!$sms_sent) {
        echo "📱 Initiating Backup Route (sms-gate.app) for: " . $customer_phone . "\n";

        $smsgate_username  = 'PKHHG1';
        $smsgate_password  = 'icqsrlspg85th2';
        $smsgate_device_id = '3onqHv7QcvR69kVifBQrZ';

        $smsgate_payload = json_encode([
            "textMessage"  => ["text" => $sms_message],
            "deviceId"     => $smsgate_device_id,
            "phoneNumbers" => [$customer_phone],
            "simNumber"    => 1,
            "ttl"          => 3600,
            "priority"     => 100
        ]);
       
        $api_url = "https://api.sms-gate.app/3rdparty/v1/messages?skipPhoneValidation=true&deviceActiveWithin=12";
        $ch2     = curl_init($api_url);

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
        $http_code        = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);

        echo "Base Delivery Status Header Received: " . $http_code . "\n";
        echo "Raw Engine Trace Details: " . $smsgate_response . "\n\n";

        if ($http_code == 200 || $http_code == 201 || $http_code == 202) {
            echo "🚀 sms-gate backup API success! Outbound SMS command successfully queued.\n";
        } else {
            echo "⚠️ sms-gate API rejected the packet structure.\n";
        }
    }
} else {
    echo "⚠️ SMS Skip: Customer phone number is missing or empty.\n";
}
?>
