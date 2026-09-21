<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize active browser session context tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================================
// 1. DATA HARVESTING & PHONE STANDARDIZATION ENGINE
// ========================================================
$phone  = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : ''; 
$amount = str_replace(',', '', $amount);

// Intercept incoming hidden form parameter fields sent from index.php
$capturedMac = isset($_POST['mac_address']) ? trim($_POST['mac_address']) : '0';
if ($capturedMac !== '0' && !empty($capturedMac)) {
    $_SESSION['customer_mac'] = preg_replace('/[^a-zA-Z0-9]/', '', $capturedMac);
}

// Enforce international dialing schema standard formatting rules
if (substr($phone, 0, 1) === '0') {
    $phone = '255' . substr($phone, 1);
}

$routingPrefix = substr($phone, 3, 2); 

// Map network carrier designations by standard Tanzanian operator configurations
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

// ========================================================
// 2. CONNECT TO DYNAMIC RAILWAY MYSQL INSTANCE
// ========================================================
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database connectivity node failed to respond: " . $conn->connect_error);
}
// =========================================================================
// 3. VOUCHER SELECTION, STOCK MANAGEMENT, AND AZAMPAY CHECKOUT DISPATCH
// =========================================================================

// Query the database to find one available voucher matching the selected price tier
$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND status = 'AVAILABLE' LIMIT 1");
$stmt->bind_param("i", $amount);
$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbResult) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    
    // ---- FIREWALL-SAFE OUT OF STOCK SYSTEM ALERTS VIA NTFY ----
    $alertText  = "⚠️ TANConnect WiFi Alert ⚠️\n";
    $alertText .= "Voucher Tier OUT OF STOCK!\n";
    $alertText .= "• Price Tier: " . number_format((int)$amount) . " TZS\n";
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

    
    $httpStatusCode = 503; // Sets failure flag to load the out-of-stock template later
 
} else {
    // Voucher is available, isolate variables for tracking
    $allocatedVoucherId   = $dbResult['id'];
    $allocatedVoucherCode = $dbResult['voucher_code'];
    
    // Set AzamPay Secure Sandbox credentials
    $appName   = "Tanconnect";
    $clientId  = "678beae1-7761-47fb-8111-858fb60d7ad3";
    $secretKey = "VsZ0sQJpaxcWpkm5WtfmQNfjqwq0WqeQ/4qiFI044jmdSvq5ksVo3GWtT6yjQYVr4uqgn4X9hUdnrBaf3opZI/HdK2PzbxzBLlBf5xBhTY8WeyjPgnTWbEBkkIA+8Z3MBCItvm83FBLdv/hOBAwtRbnOSNfPSKxs3TgtTGo1xMBc/NqGWAsMRKgEH5m5v0mO9jxgRQzRezzSE4ibKDrRg1bswh7GWN6u7SfKvzyZN1ZnSJPC6iTcgDz4gzeoygb9nyOprJCfwe0fEJd9ohfVMhOG/FGyXsEcG2UKjoeH12p1+/LqjzCOUyR1aYWv4R8GdizIzghOTtZCmnOb35XuyRbQkwdEq6lbC5naP322gvE+pQ/MAhS1q5ZeS3FzIYmaZ1yrcT10mIUNasaCsa+1oMmF8E/zrRnNnVPymU9S5pzjzCK44uRQHqoSnn3E44agwMq9y1A6JnCVeRAYsoI64xzjThf9DFgafop8ToYcisKqIaxYclEgJMtYX/hrIaWKGBNV+WUX0kRFh/KTLYtpOvLUpui1KMIQNEYwQDBG8gcV+uieN1VxwA780QRj1zdZI8K9HWeqzPwxgmYyi2CGeYzuLdAzC4X84NanxCMOoHCO/IFwuYhPTMqSnjMEaRoPKcymxHk0KwHN9rnzC6UKaXleNuTOG/szi2qYAr2XImY=";
    $apiKey    = "63bdee95-eba0-4eec-a5f0-0a8a12a715df";
    $transactionId = 'WIFI-' . time();

    // =======================================================
    // 4. STAGE 1: AUTOMATED ACCESS TOKEN GENERATION
    // =======================================================
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
    curl_close($chAuth);

    $authResult = json_decode($authResponse, true);
    $token = isset($authResult['data']['accessToken']) ? $authResult['data']['accessToken'] : null;

    if (!$token) {
        $httpStatusCode = 401; // Authentication token dispatch failure
    } else {
        // =======================================================
        // 5. STAGE 2: EXECUTE LIVE MOBILE CHECKOUT DISPATCH
        // =======================================================
        $checkoutUrl = "https://sandbox.azampay.co.tz/azampay/mno/checkout";
        $checkoutPayload = json_encode([
            'accountNumber' => $phone,
            'amount'        => $amount,
            'currency'      => 'TZS',
            'externalId'    => $transactionId,
            'provider'      => $provider,
            'additionalProperties' => new stdClass()
        ]);

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
        $httpStatusCode = curl_getinfo($chCheck, CURLINFO_HTTP_CODE);
        curl_close($chCheck);
    }
}

// Update database status flags to 'ASSIGNED' if cURL checkout request hit 200 OK successfully
if ($httpStatusCode === 200 && isset($allocatedVoucherId)) {
    $updateStmt = $conn->prepare("UPDATE wifi_vouchers SET status = 'ASSIGNED', assigned_phone = ?, transaction_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $phone, $transactionId, $allocatedVoucherId);
    $updateStmt->execute();
    $updateStmt->close();
}

$conn->close();

// Capture the active session MAC address variable for target template parsing
$macAddress = isset($_SESSION['customer_mac']) ? $_SESSION['customer_mac'] : '0';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - SUCCESS REPORT</title>
       <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 40px 15px; color: #2c3e50; margin: 0; }
        .receipt-card { background: white; max-width: 450px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; position: relative; }
        
        .success-color { color: forestgreen !important; margin-bottom: 10px; font-size: 20px; font-weight: bold; }
        .error-color { color: #e74c3c; font-size: 14px; font-weight: bold; }
        .transit-color { color: #3498db; margin-bottom: 10px; font-size: 16px; font-weight: bold; }
        
        /* 🚀 THE FIXED CRITICAL ACTION BUTTON CLASSES 🚀 */
        .btn-portal { display: block !important; width: 100% !important; box-sizing: border-box !important; background: #3498db; color: white !important; border: 2px solid darkgreen !important; padding: 14px 20px !important; font-size: 15px !important; border-radius: 8px !important; cursor: pointer !important; text-decoration: none !important; margin-top: 15px !important; font-weight: bold !important; text-align: center !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important; }
        .btn-blue { background: #1e3c72 !important; border-color: #152b52 !important; }
        .btn-outline { background: transparent !important; border: 2px solid #bdc3c7 !important; color: #7f8c8d !important; margin-top: 10px !important; }
        .btn-portal:hover { filter: brightness(0.95); }
        
        .close-btn { position: absolute; top: 12px; right: 16px; font-weight: bold; font-size: 30px; cursor: pointer; color: #64748b; line-height: 1; }
        .copy-btn-link { font-size: 13px !important; color: #ff6600 !important; text-decoration: underline !important; cursor: pointer !important; display: block !important; margin: 12px auto !important; font-weight: bold !important; text-align: center !important; }
    </style>

    
    <script type="text/javascript">
        var fixedDeviceId = "8600081897";
        var clientMac = "<?php echo htmlspecialchars($macAddress); ?>";
        var activeTxId = "<?php echo isset($transactionId) ? htmlspecialchars($transactionId) : ''; ?>";

        function executeGuanriNmsLoginInline(mac, voucherCode) {
            var nmsUrl = "http://solnms.net";
            var targetLink = nmsUrl + "?device_id=" + fixedDeviceId + "&mac_address=" + mac + "&password=" + voucherCode + "&language=en&billType=0&roamingFlag=0&billing_mode=0";
            window.top.location.href = targetLink;
        }

        function copyInlineText(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    alert("Voucher " + text + " imenakiliwa kwa ufanisi!");
                }).catch(function() {
                    fallbackCopyInline(text);
                });
            } else {
                fallbackCopyInline(text);
            }
        }

        function fallbackCopyInline(text) {
            var tempInput = document.createElement("input");
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            alert("Voucher " + text + " imenakiliwa kwa ufanisi!");
        }

        function closeThisWindow() {
            window.close();
        }

        function startPaymentVerificationLoop() {
            if (!activeTxId) return;
            
            var checkInterval = setInterval(function() {
                fetch('check_status.php?txn_id=' + encodeURIComponent(activeTxId))
                    .then(response => response.json())
                    .then(data => {
                        var upperStatus = data.status ? data.status.toUpperCase() : '';
                        
                        if (upperStatus === 'USED' || upperStatus === 'SUCCESS' || upperStatus === 'COMPLETED') {
                            clearInterval(checkInterval);

                           // Replace your old planDuration lines with this corrected timeline allocation map:
                          // Convert the value into a raw math integer immediately to strip out any string formatting characters
                        var numericAmount = parseInt("<?php echo htmlspecialchars($amount); ?>", 10);

                       var planDuration = (numericAmount === 500)   ? "Masaa 6"  : 
                          (numericAmount === 1000)  ? "Siku 1"   : 
                          (numericAmount === 3000)  ? "Siku 4"   : 
                          (numericAmount === 5000)  ? "Siku 7"   : 
                          (numericAmount === 10000) ? "Siku 15"  : 
                          (numericAmount === 20000) ? "Siku 30"  : "Siku 30";



                            var headlineElement = document.getElementById('payment-headline');
                            if (headlineElement) {
                                headlineElement.className = "success-color";
                                headlineElement.innerHTML = "✓ Malipo Yamekamilika!";
                            }

                            var subtextElement = document.getElementById('payment-subtext');
                            if (subtextElement) {
                                subtextElement.innerHTML = "Umenunua kifurushi cha <b>Tsh " + parseInt(planAmount).toLocaleString() + "</b> kitatumika kwa <b>" + planDuration + "</b>.<br>Vocha yako imetengenezwa kikamilifu.";
                            }

                            var trueVoucherCode = data.voucher_code || data.code || data.voucher || "KODI-SAHIHI";

                            // INLINE INJECTION ENGINE: Renders box and buttons securely in one step
                            var containerBox = document.getElementById('status-loading-container');
                            if (containerBox) {
                                containerBox.style.border = "none";
                                containerBox.style.background = "transparent";
                                containerBox.style.display = "block";
                                containerBox.style.padding = "0";

                                containerBox.innerHTML = 
                                    '<div style="font-size: 28px; font-weight: bold; color: #ff6600; letter-spacing: 2px; border: 2px dashed #ff6600; background-color: #fff5eb; text-align: center; width: 100%; padding: 12px; border-radius: 8px; box-sizing: border-box; margin-bottom: 15px;">' + 
                                        '<span id="raw-pin-string">' + trueVoucherCode + '</span>' +
                                    '</div>' +
                                    '<div style="width: 100%; box-sizing: border-box;">' +
                                        '<button type="button" onclick="executeManualPhoneLoginInline(\'' + clientMac + '\', \'' + trueVoucherCode + '\')" class="btn-portal btn-blue">' +
                                            '🚀 INGIA MTANDAONI (HAPA HAPA)' +
                                        '</button>' +
                                        '<div onclick="copyInlineText(\'' + trueVoucherCode + '\')" class="copy-btn-link">' +
                                            '📋 Nakili Vocha (Copy)' +
                                        '</div>' +
                                        '<button type="button" onclick="alert(\'Salama! Vocha yako haijatumika kwenye simu hii. Unaweza kuandika au kunakili namba hii na kuiweka kwenye Laptop yako kupitia ukurasa wa HODI ili uingie mtandaoni.\');" class="btn-portal btn-outline">' +
                                            '💻 NITATUMIA KWENYE LAPTOP' +
                                        '</button>' +
                                    '</div>';
                            }
                        }
                    })
                    .catch(err => console.log("Waiting for PIN validation..."));
            }, 3000);
        }

        window.onload = function() {
            if (activeTxId !== "") {
                startPaymentVerificationLoop();
            }
        };
    </script>
</head>
<body>
<?php if ($httpStatusCode === 200): ?>

    <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        
        <div style="font-size: 24px; font-family: Broadway, Helvetica, sans-serif; color: #1e3c72; font-weight: bold; margin-bottom: 2px;">TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></div>
        <div style="font-size: 11px; font-style: italic; color: #555; margin-bottom: 20px;">"We bring the world at your finger tips"</div>
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>

        <h2 id="payment-headline" class="transit-color" style="margin-bottom: 15px; font-size: 16px; font-weight: bold; transition: color 0.4s ease;">Ombi la Malipo Umetumiwa!</h2>
        
        <p id="payment-subtext" style="font-size: 14px; color: black; line-height: 1.5; margin-top: 5px;">
            Tafadhali weka (PIN) kwenye simu yako kuruhusu malipo ya <b>Tsh <?php echo htmlspecialchars(number_format(intval($amount))); ?></b> kwenda TANConnect Wi-Fi.
        </p>
        
        <div id="voucher-display-box" style="margin: 10px 0; width: 100%; box-sizing: border-box;">
            <!-- DYNAMIC REGION: Verified loops rewrite this container entirely on clearance -->
            <div id="status-loading-container" style="background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 12px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                <div id="waiting-marquee-container" style="display: flex; align-items: center; justify-content: center; color: #3498db; font-weight: bold; font-size: 12px; width: 100%;">
                    <marquee behavior="scroll" direction="left" scrollamount="4" style="font-size: 13px; font-weight: bold; width: 100%;">
                        Malipo yanafanyika kupitia mtandao wa AzamPay. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Voucher yako itajitokeza hapa utapoweka PIN kwenye simu yako. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Vilevile utapokea SMS yenye Voucher yako kutoka namba 0753 476 850.
                    </marquee>
                </div>
            </div>
        </div> 

        <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa; margin-top: 15px;">
            <p><b>© 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></b> is a registered trademark of <br><a href="https://railway.app" style="color: #0066cc; font-weight: 500; text-decoration: none;"> NIT Africa Solutions Limited</a></p>
        </footer>
    </div>

<?php else: ?>

    <!-- FAIL-SAFE SYSTEM PANELS: FIRES FOR OUT OF STOCK OR 401 GATEWAY DISCONNECTS -->
    <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        <div style="font-size: 24px; font-family: Broadway, Helvetica, sans-serif; color: #1e3c72; font-weight: bold; margin-bottom: 2px;">TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></div>
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>
        
        <h2 class="error-color" style="color: #e74c3c; margin-top: 15px;">✕ Hitilafu Imepatikana!</h2>
        
        <p style="font-size: 14px; line-height: 1.6; color: #34495e; text-align: left; margin-top: 15px;">
            <?php 
            if (isset($httpStatusCode) && $httpStatusCode === 503) {
                echo "<b>Samahani ndugu mteja, mtambo umeshindwa kutoa vocha kwa sasa kwa sababu vocha za kiwango hiki zimeisha (Out of Stock).</b><br><br>Uongozi wetu umearifiwa kupitia Ntfy Alert na tunaongeza vocha nyingine sasa hivi. Tafadhali jaribu tena baada ya muda mfupi au wasiliana nasi.";
            } else {
                echo "<b>Imeshindwa kuanzisha mawasiliano na mtandao wa malipo wa AzamPay.</b><br><br>Tafadhali hakikisha kuwa namba yako ya simu iko hewani, salio linatosha na ujaribu tena. Kama umekatwa pesa hewani bila kuona vocha, piga simu: <b>0713 123 974</b>.";
            }
            ?>
        </p>
        
        <a href="javascript:history.back()" class="btn-portal" style="background: #e74c3c; border-color: darkred; color: white; padding: 12px; display: block; text-decoration: none; font-weight: bold; border-radius: 6px; text-align: center; margin-top: 20px;">
            RUDI NYUMA (BACK HOME)
        </a>
    </div>

<?php endif; ?>

</body>
</html>

