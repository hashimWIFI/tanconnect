
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =========================================
// 1. DATA HARVESTING & PHONE STANDARDIZATION
// ==========================================
$phone  = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : ''; 
$amount = str_replace(',', '', $amount);

if (substr($phone, 0, 1) === '0') {
    $phone = '255' . substr($phone, 1);
}

$routingPrefix = substr($phone, 3, 2); 

if (in_array($routingPrefix, ['74', '75', '76', '14'])) {
    $provider = "Mpesa";
} elseif (in_array($routingPrefix, ['70', '71', '77', '65', '07', '67', '72'])) {
    $provider = "Tigo";
} elseif (in_array($routingPrefix, ['78', '79', '68', '69'])) {
    $provider = "Airtel";
} elseif (in_array($routingPrefix, ['62', '61'])) {
    $provider = "Halopesa";
} else {
    $provider = "Mpesa"; 
}
// ==========================================
// 2. CONNECT TO AUTOMATED RAILWAY MYSQL DB
// ==========================================
$db_host = getenv('MYSQLHOST') ?: ' ';
$db_port = getenv('MYSQLPORT') ?: ' ';
$db_user = getenv('MYSQLUSER') ?: ' ';
$db_pass = getenv('MYSQLPASSWORD') ?: ' ';
$db_name = getenv('MYSQLDATABASE') ?: ' ';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND status = 'AVAILABLE' LIMIT 1");
$stmt->bind_param("i", $amount);
$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbResult) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    
    // 1. Build your alert text content bundle
    $alertText  = "⚠️ TANConnect WiFi Alert ⚠️\n";
    $alertText .= "Voucher Tier OUT OF STOCK!\n";
    $alertText .= "• Price Tier: " . number_format($amount) . " TZS\n";
    $alertText .= "• Time: " . date("Y-m-d H:i:s");

    $streamOptions = [
        "http" => [
            "method"  => "POST",
            "header"  => "Title: WiFi System Alert\r\nPriority: high\r\nTags: warning,wifi\r\n",
            "content" => $alertText,
            "timeout" => 5
        ]
    ];

    $context = stream_context_create($streamOptions);
    @file_get_contents("https://ntfy.sh/tanconnect_vouchers_stock_alert_2026", false, $context);
    // ----------------------------------------------------------------------------------
   
    ?>
<!DOCTYPE html>
<html lang="sw">
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Uhaba wa Vifurushi</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 90vh; }
        .receipt-card { background: white; max-width: 450px; width: 100%; margin: 0 auto; padding: 40px 30px 30px 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; position: relative; }
        .error-color { color: #e74c3c; font-size: 16px; font-weight: bold; margin-top: 15px; }
        .footer { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; font-size: 11px; font-weight: bold; color: #1e3c72;}
        .btn-portal:active { transform: scale(0.98); }
        .btn-portal:hover { filter: brightness(0.95); }
        .close-btn { position: absolute; top: 12px; right: 16px; font-weight: bold; font-size: 30px; cursor: pointer; color: #64748b;}
        .btn-portal { background: #e74c3c; color: white; border: 2px solid grey; padding: 10px; font-size: 14px; border-radius: 6px; cursor: pointer;  
         display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; transition: background 0.2s; text-decoration: none}
    </style>
</head>
<body>

<div class="receipt-card">
    <!-- Top-corner Exit Close Button -->
    <span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>
   <b> <img src="logo.png" alt="TANConnect&reg;" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;"></b>

    <!-- FIX 1: Aligned the opening and closing tag matching properties character-for-character -->
    <div class="error-color">Uhaba wa Vifurushi Umejitokeza!</div>
<p style="font-size: 14px; color: black; line-height: 1.5; margin-top: 15px;">Mtambo umeshindwa kuchakata kifurushi cha Tsh. <?php echo htmlspecialchars($amount); ?> kwa sasa. Tafadhali chagua kingine au jaribu tena baadae.</p>
     <a href="/" style=" background: red; color: white;" class="btn-portal">← RUDI NYUMA (BACK HOME)</a>
    <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa;">
  <p><b> © 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">&reg;</sup></b> is a registered trademark of <br> <a href= https://nitafricasolutions-production-2f54.up.railway.app style="color: #0066cc; font-weight: 500;"> NIT Africa Solutions Limited</a></p></div>
<script>
function closeThisWindow() {
    window.close();
    var hiddenExitLink = document.createElement('a');
    hiddenExitLink.href = "about:blank"; 
    hiddenExitLink.target = "_self";
    document.body.appendChild(hiddenExitLink);
    hiddenExitLink.click();
    if (!window.closed) {
        window.open('', '_self', '');
        window.close();
    }
}
</script>
</body>
</html>
    <?php
    $conn->close();
    exit();
}

$allocatedVoucherId   = $dbResult['id'];
$allocatedVoucherCode = $dbResult['voucher_code'];
$appName   = "Tanconnect";
$clientId  = "678beae1-7761-47fb-8111-858fb60d7ad3";
$secretKey = "VsZ0sQJpaxcWpkm5WtfmQNfjqwq0WqeQ/4qiFI044jmdSvq5ksVo3GWtT6yjQYVr4uqgn4X9hUdnrBaf3opZI/HdK2PzbxzBLlBf5xBhTY8WeyjPgnTWbEBkkIA+8Z3MBCItvm83FBLdv/hOBAwtRbnOSNfPSKxs3TgtTGo1xMBc/NqGWAsMRKgEH5m5v0mO9jxgRQzRezzSE4ibKDrRg1bswh7GWN6u7SfKvzyZN1ZnSJPC6iTcgDz4gzeoygb9nyOprJCfwe0fEJd9ohfVMhOG/FGyXsEcG2UKjoeH12p1+/LqjzCOUyR1aYWv4R8GdizIzghOTtZCmnOb35XuyRbQkwdEq6lbC5naP322gvE+pQ/MAhS1q5ZeS3FzIYmaZ1yrcT10mIUNasaCsa+1oMmF8E/zrRnNnVPymU9S5pzjzCK44uRQHqoSnn3E44agwMq9y1A6JnCVeRAYsoI64xzjThf9DFgafop8ToYcisKqIaxYclEgJMtYX/hrIaWKGBNV+WUX0kRFh/KTLYtpOvLUpui1KMIQNEYwQDBG8gcV+uieN1VxwA780QRj1zdZI8K9HWeqzPwxgmYyi2CGeYzuLdAzC4X84NanxCMOoHCO/IFwuYhPTMqSnjMEaRoPKcymxHk0KwHN9rnzC6UKaXleNuTOG/szi2qYAr2XImY=";
$apiKey    = "63bdee95-eba0-4eec-a5f0-0a8a12a715df";
$transactionId = 'WIFI-' . time();

// ==========================================
// 3. STAGE 1: AUTOMATED TOKEN GENERATION BLOCK
// ==========================================
$authUrl = "https://authenticator-sandbox.azampay.co.tz/AppRegistration/GenerateToken";
$authPayload = json_encode([
    'appname'      => $appName,
    'clientid'     => $clientId,
    'clientsecret' => $secretKey
]);

$chAuth = curl_init($authUrl);
curl_setopt($chAuth, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chAuth, CURLOPT_POST, true);
curl_setopt($chAuth, CURLOPT_POSTFIELDS, $authPayload);
curl_setopt($chAuth, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json"]);
curl_setopt($chAuth, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($chAuth, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($chAuth, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($chAuth, CURLOPT_TIMEOUT, 30);
curl_setopt($chAuth, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$authResponse = curl_exec($chAuth);

if (curl_errno($chAuth)) {
    $checkoutResponse = "Stage 1 Connection Timeout: " . curl_error($chAuth);
    $httpStatusCode = 0;
    curl_close($chAuth);
} else {
    curl_close($chAuth);
    $authResult = json_decode($authResponse, true);
    $token = isset($authResult['data']['accessToken']) ? $authResult['data']['accessToken'] : null;

    if (!$token) {
        $checkoutResponse = "Stage 1 Rejection: " . $authResponse;
        $httpStatusCode = 0;
    } else {
        // ==========================================
        // 4. STAGE 2: EXECUTE LIVE CHECKOUT DISPATCH
        // ==========================================
$checkoutUrl = "https://sandbox.azampay.co.tz/azampay/mno/checkout";
        $checkoutPayload = '{"accountNumber":"255750000001","amount":"' . $amount . '","currency":"TZS","externalId":"' . $transactionId . '","provider":"' . $provider . '","additionalProperties":{}}';

        $chCheck = curl_init($checkoutUrl);
        curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chCheck, CURLOPT_POST, true);
        curl_setopt($chCheck, CURLOPT_POSTFIELDS, $checkoutPayload);
        curl_setopt($chCheck, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json",
            "X-API-KEY: $apiKey",
            "Authorization: Bearer $token"
        ]);
        curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chCheck, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_setopt($chCheck, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($chCheck, CURLOPT_TIMEOUT, 30);
        curl_setopt($chCheck, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $checkoutResponse = curl_exec($chCheck);
        $httpStatusCode   = curl_getinfo($chCheck, CURLINFO_HTTP_CODE);

        if (curl_errno($chCheck)) {
            $checkoutResponse = "Stage 2 Connection Timeout: " . curl_error($chCheck);
            $httpStatusCode = 0;
        }
        curl_close($chCheck);
    }
}

if ($httpStatusCode === 200) {
    $updateStmt = $conn->prepare("UPDATE wifi_vouchers SET status = 'ASSIGNED', assigned_phone = ?, transaction_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $phone, $transactionId, $allocatedVoucherId);
    $updateStmt->execute();
    $updateStmt->close();
}

$conn->close();

// ==========================================
// 5. RENDER SYSTEM RECEIPT CARD
// ==========================================
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>TANConnect - SUCCESS REPORT </title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; }
        .receipt-card { background: white; max-width: 450px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; }
        .success-color { color: forestgreen !important; margin-bottom: 10px; font-size: 16px; font-weight: bold; }
        .transit-color { color: #3498db; margin-bottom: 10px; font-size: 16px; font-weight: bold; }
        .voucher-box { background: #e8f4fd; border: 2px dashed #3498db; padding: 10px; font-size: 14px; color: #7f8c8d; margin: 10px 0; border-radius: 6px; word-break: break-all; }
        .btn-portal { background: #3498db; color: white;  border: 2px solid darkgreen; padding: 10px; font-size: 14px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; }
        .close-btn { position: absolute; top: 12px; right: 16px; font-weight: bold; font-size: 30px; cursor: pointer; color: #64748b;}
        .btn-portal:active { transform: scale(0.98); }
        .btn-portal:hover { filter: brightness(0.95); }
        .voucher-success-box { font-size: 22px !important; font-weight: bold !important; color: #2c3e50 !important; letter-spacing: 2px; border: 2px solid green !important; background-color: #f4fbf7 !important; text-align: center; justify-content: center; width: 100%; display: flex; align-items: center; }
   
    </style>
        <script type="text/javascript">
        // 1. The Core Guanri Redirection Function mapping your fixed router ID profile
        function recharge(device_id, mac) {
            window.top.location.href = 'http://na.solnms.net/SOL/rechargeMobileManage.do?device_id=8600081897&mac_address=0&language=en&billType=0&roamingFlag=0&billing_mode=0';
        }

        // Capture parameters from the PHP workflow context safely
        var fixedDeviceId = "8600081897";
        var clientMac = "<?php echo htmlspecialchars($_POST['mac_address'] ?? $_GET['mac'] ?? ''); ?>";
        var referenceId = "<?php echo htmlspecialchars($referenceNumber ?? ''); ?>"; // Your unique tracking ID for this transaction

        // 2. Background Polling Engine
        var statusChecker = setInterval(function() {
            if (!referenceId) return; // Guard clause if missing reference mapping targets
            
            // Checks your local DB status file silently behind the scenes
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "check_payment_status.php?ref=" + encodeURIComponent(referenceId), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    
                    // Trigger automation workflows instantly when payment status switches to complete
                    if (response.status === "COMPLETED" || response.voucher_code) {
                        clearInterval(statusChecker); // Stop the polling loops immediately
                        handleSuccessfulPayment(response.voucher_code);
                    }
                }
            };
            xhr.send();
        }, 3000); // Polls database every 3 seconds

        // 3. UI and Network Bridge Execution Routine
        function handleSuccessfulPayment(voucherCode) {
            // Update Title Text Styling to Green Success State
            var headline = document.getElementById('payment-headline');
            headline.className = "success-color";
            headline.innerText = "✓ Malipo Yamekamilika!";
            
            // Adjust Subtext Instructions
            document.getElementById('payment-subtext').innerHTML = "Kifurushi chako kimeamilishwa kikamilifu. <b>Tunakuunganisha kwenye Internet sasa hivi...</b>";
            
            // Swap out Marquee Scroller area for the functional clear Voucher view
            var textContainer = document.getElementById('status-loading-container');
            textContainer.className = "voucher-success-box";
            textContainer.innerHTML = "<span>" + voucherCode + "</span>";
            
            // Reveal the Backup NAKILI manual button framework
            document.getElementById('copy-button-container').style.display = "block";
            document.getElementById('copy-btn-trigger').setAttribute('data-voucher', voucherCode);

            // AUTO-LOGIN TRIGGER: Redirects browser window immediately to open access
            setTimeout(function() {
                recharge(fixedDeviceId, clientMac);
            }, 2500); // 2.5 seconds window buffer to allow visibility of generated code
        }

        // Manual backup fallback mechanism
        function copyVoucherToClipboard() {
            var btn = document.getElementById('copy-btn-trigger');
            var voucherText = btn.getAttribute('data-voucher');
            if (voucherText) {
                navigator.clipboard.writeText(voucherText);
                alert("Voucher imenakiliwa kwa ufanisi: " + voucherText);
                recharge(fixedDeviceId, clientMac);
            }
        }

        function closeThisWindow() {
            window.close();
        }
    </script>
</head>
<body>


 <?php if ($httpStatusCode === 200): ?>

        <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        <img src="logo.png" alt="TANConnect Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>

        <!-- Starts with structural transit-color blue text theme style -->
        <h2 id="payment-headline" class="transit-color" style="margin-bottom: 15px; font-size: 16px; font-weight: bold; transition: color 0.4s ease;">Ombi la Malipo Umetumiwa!</h2>
        <p id="payment-subtext" style="font-size: 14px; color: black; line-height: 1.5; margin-top: 5px;">
            Tafadhali weka (PIN) kwenye simu yako kuruhusu malipo ya <b>Tsh <?php echo htmlspecialchars($amount); ?></b> kwenda TANConnect Wi-Fi.
        </p>
        <!-- CONTAINER FOR DYNAMIC DATA POPPING INLINE -->
        <div id="voucher-display-box" style="gap: 10px; display: flex; align-items: center; justify-content: space-between; margin: 10px 0; width: 100%; box-sizing: border-box;">
            
            <!-- LEFT BOX (Dynamic Sizing Contexts) -->
            <div id="status-loading-container" style="flex: 7; background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 12px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                <div style="display: flex; align-items: center; justify-content: center; color: #3498db; font-weight: bold; font-size: 12px; width: 100%;">
                    <marquee behavior="scroll" direction="left" scrollamount="4" style="font-size: 13px; font-weight: bold; width: 100%;">
                        Malipo yanafanyika kupitia mtandao wa AzamPay. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Voucher yako itajitokeza hapa utapoweka PIN kwenye simu yako. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Vilevile utapokea SMS yenye Voucher yako kutoka NIT Africa Solutions.
                    </marquee>
                </div>
            </div>
            
            <!-- RIGHT BOX (Holds the copy link trigger button - hidden initially) -->
            <div id="copy-button-container" style="flex: 3; display: none; min-height: 55px; box-sizing: border-box;">
                <button onclick="copyVoucherToClipboard()" id="copy-btn-trigger" data-voucher="" style="width: 100%; height: 55px; background: green; color: white;" class="btn-portal">NAKILI</button>
            </div>
        </div> 

        <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa; margin-top: 15px;">
            <p><b>© 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></b> is a registered trademark of <br><a href="https://nitafricasolutions-production-2f54.up.railway.app" target="_blank" style="color: #0066cc; font-weight: 500;">NIT Africa Solutions Limited</a></p>
        </footer>
    </div>
<?php endif; ?>

</body>
</html>       
       
 
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Hitilafu Ya Mtandao</title>
<div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
<img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
<span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>
 <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; }
        .receipt-card { background: white; max-width: 450px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; }
        .success-color { color: #006400; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
        .error-color { color: #e74c3c; font-size: 14px; font-weight: bold; }
        .transit-color { color: #3498db; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
        .footer { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; font-size: 11px; font-weight: bold; color: #1e3c72;}
        .voucher-box { background: #e8f4fd; border: 2px dashed #3498db; padding: 10px; font-size: 14px; color: #7f8c8d; margin: 10px 0; border-radius: 6px; word-break: break-all; }
        .btn-portal:active { transform: scale(0.98); }
        .btn-portal:hover { filter: brightness(0.95); }
        .btn-portal { border: 2px solid grey; cursor: pointer;  display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; transition: background 0.2s; font-size: 14px; text-decoration: none; border-radius: 6px; padding: 9px;}
        .close-btn { position: absolute; top: 12px; right: 16px; font-weight: bold; font-size: 30px; cursor: pointer; color: #64748b;}

 </style>
</head>
<body>
    <div class="error-color">✕ Hitilafu Ya Mtandao Imejitokeza!</div>
    <p style="font-size: 13px; color: black; line-height: 1.5; margin-top: 15px;">Tumeshindwa kuwasiliana na <strong><?php echo ($provider === 'Mpesa') ? 'M-Pesa' : (($provider === 'Tigo') ? 'Tigopesa' : (($provider === 'Airtel') ? 'Airtel Money' : (($provider === 'Halopesa') ? 'Halopesa' : 'simu yako'))); ?></strong> kuanzisha malipo, tafadhali jaribu tena au chagua kifurushi kingine.</p>
    <a href="/" style=" background: red; color: white;" class="btn-portal">← RUDI NYUMA (BACK HOME)</a>
    <br>  <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa;">
  <p><b> © 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">&reg;</sup></b> is a registered trademark of<br><a href= https://nitafricasolutions-production-2f54.up.railway.app style="color: #0066cc; font-weight: 500;"> NIT Africa Solutions Limited</a></p></div> 
</body>
</html>
<?php endif; ?> 

<script>
    // =======================================================
    // GLOBAL CONSTANTS & VARIABLE HOOKS FROM PHP
    // =======================================================
    var activeTxId = "<?php echo htmlspecialchars($transactionId); ?>";
    var fixedDeviceId = "8600081897";
    
    // Dynamically safely capture the user's incoming hardware MAC signature
    var clientMac = "<?php echo htmlspecialchars($macAddress); ?>"; 

    // 1. Core Guanri cloud platform verification redirect function
    function executeGuanriNmsLogin(deviceId, mac) {
        window.top.location.href = 'http://solnms.net' + deviceId + '&mac_address=' + mac + '&language=en&billType=0&roamingFlag=0&billing_mode=0';
    }

    // 2. Automated Polling Engine Workflow
    function startPaymentVerificationLoop() {
        var checkInterval = setInterval(function() {
            // Using the precise endpoint file your code targets
            fetch('check_status.php?tx_id=' + encodeURIComponent(activeTxId))
                .then(response => response.json())
                .then(data => {
                    // Detect when payment has cleared successfully
                    if (data.status === 'USED' || data.status === 'SUCCESS' || data.status === 'COMPLETED') {
                        clearInterval(checkInterval); // Kill background thread

                        var planAmount = "<?php echo htmlspecialchars($amount); ?>";
                        var planDuration = (planAmount === "500") ? "Masaa 6" : 
                                           (planAmount === "1000") ? "Siku 1" : 
                                           (planAmount === "2000") ? "Siku 2" : 
                                           (planAmount === "5000") ? "Siku 7" : "Siku 30";

                        // 3. Dynamic UI Transformation to Success state
                        var headlineElement = document.getElementById('payment-headline');
                        if (headlineElement) {
                            headlineElement.style.color = "#2ecc71";
                            headlineElement.innerHTML = "✓ Malipo Yamekamilika!";
                        }

                        var subtextElement = document.getElementById('payment-subtext');
                        if (subtextElement) {
                            subtextElement.innerHTML = "Umenunua kifurushi cha <b>Tsh " + parseInt(planAmount).toLocaleString() + "</b> kitatumika kwa <b>" + planDuration + "</b>.<br><br><span style='color:#3498db; font-weight:bold;'>Tunakuunganisha kwenye Internet sasa hivi, tafadhali subiri...</span>";
                        }

                        // Pull dynamic voucher code generated from backend payload response data row
                        var realPinCode = data.voucher_code; 

                        // Update voucher display box values safely
                        var leftContainerBox = document.getElementById('status-loading-container');
                        var rightContainerBox = document.getElementById('copy-button-container');

                        if (leftContainerBox && rightContainerBox) {
                            leftContainerBox.innerHTML = '<span id="raw-pin-string" style="font-size: 20px; font-weight: bold; color: #2c3e50; letter-spacing: 1px;">' + realPinCode + '</span>';
                            leftContainerBox.style.border = "3px solid #2ecc71";
                            leftContainerBox.style.backgroundColor = "#ebf8f1";
                            
                            // Load voucher into button data property and display the copy button card layout row frame
                            document.getElementById('copy-btn-trigger').setAttribute('data-voucher', realPinCode);
                            rightContainerBox.style.display = "block";
                        }

                        // =======================================================
                        // FULLY AUTOMATED REDIRECT WORKFLOW
                        // Wait 3.5 seconds to let the user visually read the pin code box, then login instantly
                        // =======================================================
                        setTimeout(function() {
                            executeGuanriNmsLogin(fixedDeviceId, clientMac);
                        }, 3500);
                    }
                })
                .catch(err => console.log("Waiting for PIN validation..."));
        }, 3000); // Polling rhythm frequency sequence executed every 3 seconds
    }

    // 4. Manual Fallback Action Function (Copy button click backup layout check)
    function copyVoucherToClipboard() {
        var btn = document.getElementById('copy-btn-trigger');
        var voucherText = btn.getAttribute('data-voucher');
        
        if (voucherText) {
            navigator.clipboard.writeText(voucherText).then(function() {
                alert("Voucher imenakiliwa kwa ufanisi! Kujaribu kuingia mtandaoni sasa hivi...");
                executeGuanriNmsLogin(fixedDeviceId, clientMac);
            });
        }
    }

    function closeThisWindow() {
        window.close();
        if (!window.closed) {
            window.open('', '_self', '');
            window.close();
        }
    }

    // 5. Global Initialization Trigger Engine Execution Point
    window.onload = function() {
        if (activeTxId !== "") {
            startPaymentVerificationLoop();
        }
    };
</script>
