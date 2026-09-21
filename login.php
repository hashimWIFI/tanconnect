<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize active browser session context tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================================
// 1. DATA HARVESTING, PHONE & INTERACTION MAPPING
// ==========================================================
$phone  = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : ''; 
$amount = str_replace(',', '', $amount);

// Intercept incoming hidden form parameter fields sent from index.php
$capturedMac = isset($_POST['mac_address']) ? trim($_POST['mac_address']) : '0';
if ($capturedMac !== '0' && !empty($capturedMac)) {
    $_SESSION['customer_mac'] = preg_replace('/[^a-zA-Z0-9]/', '', $capturedMac);
}

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

// ==========================================================
// 2. CONNECT TO AUTOMATED RAILWAY MYSQL DB
// ==========================================================
$db_host = getenv('MYSQLHOST') ?: ' ';
$db_port = getenv('MYSQLPORT') ?: ' ';
$db_user = getenv('MYSQLUSER') ?: ' ';
$db_pass = getenv('MYSQLPASSWORD') ?: ' ';
$db_name = getenv('MYSQLDATABASE') ?: ' ';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
// =========================================================================
// LOT 2 OF 4: VOUCHER TRANSACTION SELECTION AND AZAMPAY TOKEN HANDSHAKES
// =========================================================================

$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND status = 'AVAILABLE' LIMIT 1");
$stmt->bind_param("i", $amount);
$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbResult) {
    date_default_timezone_set('Africa/Dar_es_Salaam');
    
    // ---- FIREWALL-SAFE NTFY ALERT SYSTEM ----
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
    
    $httpStatusCode = 503;
 
} else {
    $allocatedVoucherId   = $dbResult['id'];
    $allocatedVoucherCode = $dbResult['voucher_code'];
    
    $appName       = "Tanconnect";
    $clientId      = "678beae1-7761-47fb-8111-858fb60d7ad3";
    $secretKey     = "VsZ0sQJpaxcWpkm5WtfmQNfjqwq0WqeQ/4qiFI044jmdSvq5ksVo3GWtT6yjQYVr4uqgn4X9hUdnrBaf3opZI/HdK2PzbxzBLlBf5xBhTY8WeyjPgnTWbEBkkIA+8Z3MBCItvm83FBLdv/hOBAwtRbnOSNfPSKxs3TgtTGo1xMBc/NqGWAsMRKgEH5m5v0mO9jxgRQzRezzSE4ibKDrRg1bswh7GWN6u7SfKvzyZN1ZnSJPC6iTcgDz4gzeoygb9nyOprJCfwe0fEJd9ohfVMhOG/FGyXsEcG2UKjoeH12p1+/LqjzCOUyR1aYWv4R8GdizIzghOTtZCmnOb35XuyRbQkwdEq6lbC5naP322gvE+pQ/MAhS1q5ZeS3FzIYmaZ1yrcT10mIUNasaCsa+1oMmF8E/zrRnNnVPymU9S5pzjzCK44uRQHqoSnn3E44agwMq9y1A6JnCVeRAYsoI64xzjThf9DFgafop8ToYcisKqIaxYclEgJMtYX/hrIaWKGBNV+WUX0kRFh/KTLYtpOvLUpui1KMIQNEYwQDBG8gcV+uieN1VxwA780QRj1zdZI8K9HWeqzPwxgmYyi2CGeYzuLdAzC4X84NanxCMOoHCO/IFwuYhPTMqSnjMEaRoPKcymxHk0KwHN9rnzC6UKaXleNuTOG/szi2qYAr2XImY=";
    $apiKey        = "63bdee95-eba0-4eec-a5f0-0a8a12a715df";
    $transactionId = 'WIFI-' . time();

    // =======================================================
    // 3. STAGE 1: AUTOMATED TOKEN GENERATION BLOCK
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
        $httpStatusCode = 401; 
    } else {
        // =======================================================
        // 4. STAGE 2: EXECUTE LIVE CHECKOUT DISPATCH
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

// Handle database tracking assignment flags if checkout hit successfully
if ($httpStatusCode === 200 && isset($allocatedVoucherId)) {
    $updateStmt = $conn->prepare("UPDATE wifi_vouchers SET status = 'ASSIGNED', assigned_phone = ?, transaction_id = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $phone, $transactionId, $allocatedVoucherId);
    $updateStmt->execute();
    $updateStmt->close();
}

$conn->close();

// Capture current MAC address fallback variables for interface injection
$macAddress = isset($_SESSION['customer_mac']) ? $_SESSION['customer_mac'] : '0';
?>
// =========================================================================
// LOT 3 OF 4: INTERACTIVE INTERFACE STYLES & REAL-TIME VERIFICATION LOOPS
// =========================================================================
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
        .btn-portal { background: #3498db; color: white; border: 2px solid darkgreen; padding: 12px; font-size: 14px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; }
        .btn-blue { background: #1e3c72 !important; border-color: #152b52 !important; margin-top: 15px; }
        .btn-outline { background: transparent !important; border: 2px solid #bdc3c7 !important; color: #7f8c8d !important; margin-top: 10px; }
        .close-btn { position: absolute; top: 12px; right: 16px; font-weight: bold; font-size: 30px; cursor: pointer; color: #64748b; }
        .btn-portal:hover { filter: brightness(0.95); }
        .voucher-success-box { font-size: 26px !important; font-weight: bold !important; color: #ff6600 !important; letter-spacing: 2px; border: 2px dashed #ff6600 !important; background-color: #fff5eb !important; text-align: center; justify-content: center; width: 100%; padding: 15px; border-radius: 8px; box-sizing: border-box; }
        .copy-btn-link { font-size: 13px; color: #ff6600; text-decoration: underline; cursor: pointer; display: inline-block; margin-top: 8px; font-weight: bold; }
    </style>
    <script type="text/javascript">
        var fixedDeviceId = "8600081897";
        var clientMac = "<?php echo htmlspecialchars($macAddress); ?>";
        var activeTxId = "<?php echo isset($transactionId) ? htmlspecialchars($transactionId) : ''; ?>";

        function executeGuanriNmsLogin(mac, voucherCode) {
            var nmsUrl = "http://solnms.net";
            var targetLink = nmsUrl + "?device_id=" + fixedDeviceId + "&mac_address=" + mac + "&language=en&billType=0&roamingFlag=0&billing_mode=0";
            window.top.location.href = targetLink;
        }

        function startPaymentVerificationLoop() {
            if (!activeTxId) return;
            
            var checkInterval = setInterval(function() {
                fetch('check_status.php?tx_id=' + encodeURIComponent(activeTxId))
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'USED' || data.status === 'SUCCESS' || data.status === 'COMPLETED') {
                            clearInterval(checkInterval);

                            var planAmount = "<?php echo htmlspecialchars($amount); ?>";
                            var planDuration = (planAmount === "500") ? "Masaa 6" : 
                                               (planAmount === "1000") ? "Siku 1" : 
                                               (planAmount === "3000") ? "Siku 4" : 
                                               (planAmount === "5000") ? "Siku 7" : 
                                               (planAmount === "10000") ? "Siku 15" : 
                                               (planAmount === "20000") ? "Siku 30" : "Siku 30";

                            // STEP 1: Transform panel titles immediately upon successful authorization
                            var headlineElement = document.getElementById('payment-headline');
                            if (headlineElement) {
                                headlineElement.className = "success-color";
                                headlineElement.innerHTML = "✓ Malipo Yamekamilika!";
                            }

                            var subtextElement = document.getElementById('payment-subtext');
                            if (subtextElement) {
                                subtextElement.innerHTML = "Umenunua kifurushi cha <b>Tsh " + parseInt(planAmount).toLocaleString() + "</b> kitatumika kwa <b>" + planDuration + "</b>.<br>Vocha yako imetengenezwa kikamilifu.";
                            }

                            // Inject the dynamic voucher string code directly inside the production display card boxes
                            var containerBox = document.getElementById('status-loading-container');
                            if (containerBox) {
                                containerBox.className = "voucher-success-box";
                                containerBox.innerHTML = '<span id="raw-pin-string">' + data.voucher_code + '</span>';
                            }

                            // Keep the tracking references loaded for copy operations down the line
                            document.getElementById('action-button-layer').setAttribute('data-voucher', data.voucher_code);
                            
                            // STEP 1 CHOICE GATEWAY: Bring up customer choice button blocks smoothly
                            document.getElementById('waiting-marquee-container').style.display = "none";
                            document.getElementById('action-button-layer').style.display = "block";
                        }
                    })
                    .catch(err => console.log("Waiting for PIN validation..."));
            }, 3000);
        }

// =========================================================================
// LOT 4 OF 4: CLIPBOARD ENGINES, CHOICE HTML LAYOUTS & SCRIPT TERMINATIONS
// =========================================================================
        function copyVoucherToClipboard() {
            var voucherText = document.getElementById('action-button-layer').getAttribute('data-voucher');
            if (voucherText) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(voucherText).then(function() {
                        alert("Voucher imenakiliwa kwa ufanisi! " + voucherText);
                    }).catch(function() {
                        fallbackCopyText(voucherText);
                    });
                } else {
                    fallbackCopyText(voucherText);
                }
            }
        }

        function fallbackCopyText(text) {
            var tempInput = document.createElement("input");
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand("copy");
                alert("Voucher imenakiliwa kwa ufanisi! " + text);
            } catch (err) {
                alert("Tafadhali andika namba hii ya vocha: " + text);
            }
            document.body.removeChild(tempInput);
        }

        function executeManualPhoneLogin() {
            var voucherText = document.getElementById('action-button-layer').getAttribute('data-voucher');
            if (voucherText) {
                executeGuanriNmsLogin(clientMac, voucherText);
            } else {
                executeGuanriNmsLogin(clientMac, "0");
            }
        }

        function closeThisWindow() {
            window.close();
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
        <img src="logo.png" alt="TANConnect Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>

        <h2 id="payment-headline" class="transit-color" style="margin-bottom: 15px; font-size: 16px; font-weight: bold; transition: color 0.4s ease;">Ombi la Malipo Umetumiwa!</h2>
        
        <p id="payment-subtext" style="font-size: 14px; color: black; line-height: 1.5; margin-top: 5px;">
            Tafadhali weka (PIN) kwenye simu yako kuruhusu malipo ya <b>Tsh <?php echo htmlspecialchars(number_format(intval($amount))); ?></b> kwenda TANConnect Wi-Fi.
        </p>
        
        <div id="voucher-display-box" style="margin: 10px 0; width: 100%; box-sizing: border-box;">
            <!-- Status box changes dynamically once the transaction loop shifts to green -->
            <div id="status-loading-container" style="background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 12px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                <div id="waiting-marquee-container" style="display: flex; align-items: center; justify-content: center; color: #3498db; font-weight: bold; font-size: 12px; width: 100%;">
                    <marquee behavior="scroll" direction="left" scrollamount="4" style="font-size: 13px; font-weight: bold; width: 100%;">
                        Malipo yanafanyika kupitia mtandao wa AzamPay. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Voucher yako itajitokeza hapa utapoweka PIN kwenye simu yako. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Vilevile utapokea SMS yenye Voucher yako kutoka namba 0753 476 850.
                    </marquee>
                </div>
            </div>
            
            <!-- STEP 1 CHOICE BUTTONS: Hidden by default, unrolls smoothly dynamically upon successful verification -->
            <div id="action-button-layer" data-voucher="" style="display: none; margin-top: 15px; width: 100%;">
                <div class="copy-btn-link" onclick="copyVoucherToClipboard()">📋 Nakili Vocha (Copy)</div>
                
                <button onclick="executeManualPhoneLogin()" class="btn-portal btn-blue">🚀 INGIA MTANDAONI (HAPA HAPA)</button>
                
                <button onclick="alert('Salama! Vocha yako haijatumika kwenye simu hii. Unaweza kuandika au kunakili namba hii na kuiweka kwenye Laptop yako kupitia ukurasa wa HODI ili uingie mtandaoni.');" class="btn-portal btn-outline">💻 NITATUMIA KWENYE LAPTOP</button>
            </div>
        </div> 

        <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa; margin-top: 15px;">
            <p><b>© 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></b> is a registered trademark of <br><a href="https://railway.app" style="color: #0066cc; font-weight: 500;"> NIT Africa Solutions Limited</a></p>
        </footer>
    </div>
<?php else: ?>
    <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        <h2 class="error-color">Hitilafu Ya Mtandao Imejitokeza!</h2>
        <p style="font-size: 13px; color: black; line-height: 1.5; margin-top: 15px;">
            Tumeshindwa kuwasiliana na <strong><?php echo htmlspecialchars($provider); ?></strong>. Tutaomba ujaribu tena baada ya muda mfupi.
        </p>
        <a href="javascript:history.back()" class="btn-portal" style="background: #e74c3c; border-color: darkred; color: white;">RUDI NYUMA (BACK HOME)</a>
    </div>
<?php endif; ?>
</body>
</html>
<?php exit(); ?>


