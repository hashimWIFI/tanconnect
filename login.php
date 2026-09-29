<?php
// =========================================================================
// 🚀 TANCONNECT CAPTIVE PORTAL GATEWAY ENGINE (PART 1 OF 2)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 1. HARVEST FORM METRICS FROM USER INPUTS
$amount         = isset($_POST['amount']) ? trim($_POST['amount']) : ''; 
$duration       = isset($_POST['duration']) ? trim($_POST['duration']) : '';
$device_id      = isset($_POST['device_id']) ? trim($_POST['device_id']) : '';
$customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$capturedMac    = isset($_POST['mac_address']) ? trim($_POST['mac_address']) : '0';

// Fallback protection shield for running local browser tests on a PC
if ($capturedMac === '$mac' || empty($capturedMac) || $capturedMac === '0') {
    $_SESSION['customer_mac'] = "24EE9A7A9112"; 
} else {
    $_SESSION['customer_mac'] = preg_replace('/[^a-zA-Z0-9]/', '', $capturedMac);
}

// Format telephone input into uniform country prefix code
$phone = preg_replace('/[^0-9]/', '', $customer_phone);
if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
    $phone = '255' . substr($phone, 1);
}

// 2. ESTABLISH DATABASE GATEWAY
$db_host = getenv('MYSQLHOST')     ?: '127.0.0.1';
$db_port = getenv('MYSQLPORT')     ?: '3306';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 3. RUN LOW-STOCK SANITATION SCANNER
$stmt = $conn->prepare("SELECT id, voucher_code FROM wifi_vouchers WHERE price_tier = ? AND status = 'AVAILABLE' LIMIT 1");
$stmt->bind_param("i", $amount);
$stmt->execute();
$dbResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dbResult) {
    // Fire stock drop alert webhook notification straight to your Ntfy app channel
   @file_get_contents("https://ntfy.sh/tanconnect_vouchers_stock_alert_2026", false, $context);
    $payloadText = "🚨 STOCK ZERO WARNING: Kifurushi cha Tsh " . number_format(intval($amount)) . " kimeisha kabisa! Wateja hawawezi kununua kwa sasa.";
    
    $chNtfy = curl_init($ntfyUrl);
    curl_setopt($chNtfy, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chNtfy, CURLOPT_POST, true);
    curl_setopt($chNtfy, CURLOPT_POSTFIELDS, $payloadText);
    curl_setopt($chNtfy, CURLOPT_HTTPHEADER, ["Title: Voucher Stock Alert", "Priority: urgent", "Tags: warning,no_entry"]);
    curl_setopt($chNtfy, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chNtfy, CURLOPT_TIMEOUT, 5);
    curl_exec($chNtfy);
    curl_close($chNtfy);

    $httpStatusCode = 503; 
} else {
    $allocatedVoucherId   = $dbResult['id'];
    $allocatedVoucherCode = $dbResult['voucher_code'];

    // 🚀 REBRANDING UPGRADE: Generate your custom branded tracking index number string
    $transactionId = 'NITW-' . time();

    // Dynamically sort carrier codes by phone number prefix strings
    $prefix = substr($phone, 0, 6);
    $airtelPrefixes = ['25568', '25578', '25569'];
    $tigoPrefixes   = ['25565', '25567', '25571'];
    $halotelPrefixe = ['25562', '25561'];

    $provider = 'Mpesa'; 
    foreach ($airtelPrefixes as $ap) { if (strpos($prefix, $ap) === 0) { $provider = 'Airtel'; break; } }
    foreach ($tigoPrefixes as $tp)   { if (strpos($prefix, $tp) === 0) { $provider = 'Tigo';   break; } }
    foreach ($halotelPrefixe as $hp) { if (strpos($prefix, $hp) === 0) { $provider = 'Halopesa'; break; } }

    // 4. FETCH AZAMPAY ACCESS TOKEN BLOCK
    $tokenUrl = "https://authenticator.azampay.co.tz/AppRegistration/GenerateToken";
    
    $tokenPayload = json_encode([
        'appName'      => 'Tanconnect';
        'clientId'     => "01a0ec31-f4b8-7380-8abd-61eb895de07a";
        'clientSecret' => "VzBpaTRYTjBRMWE4QVJxa3FGRDhVZlNwc2U2UXdwS1VzdW1XczhzckQ3SEdzSVpDOUMrZGsxcHoyYzlnaDg4N1NEaWdRalhiNWJaWUtYTEI0ZzdTem1JT20wc0U5TlE2WXZYb3NDN2VGUHpZZGlVMHdOQmh0bUFxVkxuQ20raU9aZy84NFZTSWwzMlF6RDJpMTJ0MVZ1NWR3OEFWQ044RThNTDNmSHpoT1RHZ004dGJOQUJuNE53dWw0S3BuZC9kcGs2R3g4cnE3SjE4aHhKYnN5dkJheWJJNHRWZVc1c1VMVlgzaDUvSnBOa2g3OXZ3ZjRBdHJVU3NzM01EdUtqbEZnVy9qcXU2OVg2cHltSnVqZFRpcVVrdWdLOU5FSy82d1dTc3B6SWZhUDZoOHNPbkhRQzJpU2kvRWp0Y3JBOW5vcUx1eWZuaUpxWXpnTUE0Y2lHSjVlQW90NXI3UmdiZS9wOU9zVW93NUoySzVTK29KeDd4TlExalFVK2haNDZjdFZyS25ZTVpqc0tkaW1WZGVOcUo5b3FtTVhwNmRIcUM5eUhRN01pcVRJdFErU3FINkRFSCtiNzZleSt2RXd2UU9XSTZJbnVld0FSbmo1aTNJTFAwRVM5TTF5L1RpWTVGNWFSSDJhQXNmSFJma0JSdUtnVE9qUlJDQmh6d0YvbG9JSzhEczJ3SzdOSkVWMGdFTzJ0d2IyMTd2QTZPWVlnNXR1dHh4Y3JYTjlBTzZoRWF6Tlh1eTNxM0ozSGc3ZXdpZXAza0hhclZJWnBVWGViWWRuMTVZZUZERTFpOGYxQVU2aE94OEs4Qm9hRGwrRUoraWdMNm90eE9nS2FKRTR5L3A5cDZ0V0UvOWZlSG9jQ1Y4L0RxWWluKzZGUXJ6d1R3VjRtY1FtOGExT009";
   

    $chAuth = curl_init($tokenUrl);
    curl_setopt($chAuth, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chAuth, CURLOPT_POST, true);
    curl_setopt($chAuth, CURLOPT_POSTFIELDS, $tokenPayload);
    curl_setopt($chAuth, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: application/json"]);
    curl_setopt($chAuth, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chAuth, CURLOPT_TIMEOUT, 15);
    $authResponse = curl_exec($chAuth);
    $authJson = json_decode($authResponse, true);
    curl_close($chAuth);

    $token = isset($authJson['data']['accessToken']) ? $authJson['data']['accessToken'] : '';

    if (empty($token)) {
        $httpStatusCode = 401;
    } else {
        // 5. STAGE 2: DISPATCH LIVE MOBILE MOBILE CHECKOUT QUERY
        $checkoutUrl =  "https://checkout.azampay.co.tz/azampay/mno/checkout";

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
            "Authorization: Bearer $token"
        ]);
        curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chCheck, CURLOPT_TIMEOUT, 30);
        $checkoutResponse = curl_exec($chCheck);
        $httpStatusCode = curl_getinfo($chCheck, CURLINFO_HTTP_CODE);
        curl_close($chCheck);

        $apiResult = json_decode($checkoutResponse, true);

        // 🚀 LIVE API EXTRACTION LAYER: Pulls the true transaction ID right out of the text response
        $azamPayTransactionId = isset($apiResult['transactionId']) ? trim($apiResult['transactionId']) : (isset($apiResult['id']) ? trim($apiResult['id']) : NULL);
    }
}
// Update database status flags to 'ASSIGNED' if cURL checkout request hit 200 OK successfully
if ($httpStatusCode === 200 && isset($allocatedVoucherId)) {
    $sessionMac = isset($_SESSION['customer_mac']) ? $_SESSION['customer_mac'] : '0';
    
    // Set local East African Time parameters upon successful insertion
    date_default_timezone_set('Africa/Dar_es_Salaam');
    $currentDateTime = date("Y-m-d H:i:s");
    
    // PRODUCTION QUERY: Stores your custom internal tracking key AND the AzamPay API response ID side-by-side
    $updateStmt = $conn->prepare("UPDATE wifi_vouchers SET status = 'ASSIGNED', assigned_phone = ?, mac_address = ?, transaction_id = ?, azampay_transaction_id = ?, purchased_at = ? WHERE id = ?");
    
    // Bind parameters safely: 5 string values followed by 1 integer ("sssssi")
    $updateStmt->bind_param("sssssi", $phone, $sessionMac, $transactionId, $azamPayTransactionId, $currentDateTime, $allocatedVoucherId);
    $updateStmt->execute();
    $updateStmt->close();
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Hali ya Malipo</title>
    <link rel="stylesheet" href="style2026.css">
    <script type="text/javascript">
        // Keep tracking context indicators active in global script memory
        var activeTxId = "<?php echo isset($transactionId) ? $transactionId : ''; ?>";
        var clientMac  = "<?php echo isset($_SESSION['customer_mac']) ? $_SESSION['customer_mac'] : '0'; ?>";
        var intervalId = null;

        window.addEventListener('DOMContentLoaded', function() {
            if (activeTxId !== '') {
                // Initialize background status tracking loops every 2.5 seconds
                intervalId = setInterval(startPaymentVerificationLoop, 2500);
            }
        });

        function startPaymentVerificationLoop() {
            fetch('check_status.php?txn_id=' + encodeURIComponent(activeTxId))
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'SUCCESS') {
                        clearInterval(intervalId);
                        
                        // Switch headline typography styles smoothly
                        var headline = document.getElementById('payment-headline');
                        headline.innerHTML = "✓ Malipo Yamekamilika!";
                        headline.className = "success-color";
                        
                        document.getElementById('payment-subtext').innerHTML = 
                            "Bonyeza sanduku la kijani hapa chini kupata internet sasa hivi.";
                        
                        // Generate the interactive modern success card button view layer
                        var successCardHtml = 
                            '<div onclick="copyVoucherToClipboardAndGoHome(\'' + data.voucher_code + '\', \'' + clientMac + '\')" class="voucher-success-box" style="cursor:pointer; background:#e8f8f0; border:2px dashed #27ae60; padding:15px; border-radius:8px; text-align:center; box-sizing:border-box;">' +
                            '  <span style="display:block; font-size:11px; color:#27ae60; font-weight:bold; text-transform:uppercase; margin-bottom:5px;">✓ VOCHA YAKO TAYARI (CLICK TO ACTIVATE)</span>' +
                            '  <span style="display:block; font-size:26px; font-weight:bold; letter-spacing:1px; color:#2c3e50; font-family:monospace; margin-bottom:5px;">' + data.voucher_code + '</span>' +
                            '  <span style="display:block; font-size:12px; color:#7f8c8d;">Kuingia mtandaoni moja kwa moja gusa hapa.</span>' +
                            '</div>';
                        
                        document.getElementById('voucher-display-box').innerHTML = successCardHtml;
                    }
                })
                .catch(err => console.log("Checking status..."));
        }
        function copyVoucherToClipboardAndGoHome(voucherCode, fallbackMac) {
            var tempInput = document.createElement("input");
            tempInput.value = voucherCode;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // Mobile browser protection compliance shield
            
            try {
                document.execCommand("copy");
                alert("Vocha yako (" + voucherCode + ") imenakiliwa!\n\nGusa HODI kweye ukurasa unaofuata kisha fuata maelekezo kuingia mtandaoni.");
            } catch (err) {
                alert("Tafadhali Nakili namba ya vocha yako: " + voucherCode);
            }
            document.body.removeChild(tempInput);
            
            var fixedDeviceId = "8600081897"; 
            var currentMacString = fallbackMac ? fallbackMac.trim() : '0';
            var rawDigits = currentMacString.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            var finalFormattedMac = currentMacString;
            
            if (rawDigits.length === 12) {
                var groups = [];
                for (var i = 0; i < 12; i += 2) { groups.push(rawDigits.substr(i, 2)); }
                finalFormattedMac = groups.join(':'); // Enforces target "24:EE:9A:7A:91:12" structure
            }
            
            var nmsUrl = "http://na.solnms.net/SOL/rechargeMobileManage.do";
            var queryParams = [
                'device_id=' + encodeURIComponent(fixedDeviceId),
                'mac_address=' + encodeURIComponent(finalFormattedMac),
                'language=en',
                'billType=0',
                'roamingFlag=0',
                'billing_mode=0'
            ].join('&');
            
            window.top.location.href = nmsUrl + "?" + queryParams;
        }

        function closeThisWindow() {
            window.history.back();
        }
    </script>
</head>
<body>
<?php if (isset($httpStatusCode) && intval($httpStatusCode) === 200): ?>

    <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        <div style="font-size: 24px; font-family: Broadway, Helvetica, sans-serif; color: #1e3c72; font-weight: bold; margin-bottom: 2px;">TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></div>
        <div style="font-size: 11px; font-style: italic; color: #555; margin-bottom: 20px;">"We bring the world at your finger tips"</div>
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>

        <h2 id="payment-headline" class="transit-color" style="margin-bottom: 15px; font-size: 16px; font-weight: bold;">Ombi la Malipo Umetumiwa!</h2>
        
        <p id="payment-subtext" style="font-size: 14px; color: black; line-height: 1.5; margin-top: 5px;">
            Tafadhali weka (PIN) kwenye simu yako kuruhusu malipo ya <b>Tsh <?php echo htmlspecialchars(number_format(intval($amount))); ?></b> kwenda TANConnect Wi-Fi.
        </p>
        
        <div id="voucher-display-box" style="margin: 10px 0; width: 100%; box-sizing: border-box;">
            <div id="status-loading-container" style="background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 12px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                <div id="waiting-marquee-container" style="display: flex; align-items: center; justify-content: center; color: #3498db; font-weight: bold; font-size: 12px; width: 100%;">
                    <marquee behavior="scroll" direction="left" scrollamount="4" style="font-size: 13px; font-weight: bold; width: 100%;">
                        Malipo yanafanyika kupitia mtandao wa AzamPay. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Voucher yako itajitokeza hapa utapoweka PIN kwenye simu yako. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Vilevile utapokea SMS yenye Voucher yako kutoka nambari 0753 476 850.
                    </marquee>
                </div>
            </div>
        </div> 

        <footer style="padding: 6px 6px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa; margin-top: 15px;">
            <p><b>© 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<br><b>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></b> is a registered trademark.</p>
        </footer>
    </div>

<?php else: ?>

    <div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
        <div style="font-size: 24px; font-family: Broadway, Helvetica, sans-serif; color: #1e3c72; font-weight: bold; margin-bottom: 2px;">TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 10px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup></div>
        <span class="close-btn" onclick="closeThisWindow()">&times;</span>
        
        <h2 class="error-color" style="color: #e74c3c; margin-top: 15px;">✕ Hitilafu Imejitokeza!</h2>
        
        <p style="font-size: 14px; line-height: 1.6; color: #34495e; text-align: left; margin-top: 15px;">
            <?php 
            if (isset($httpStatusCode) && intval($httpStatusCode) === 503) {
                echo "<b>Samahani ndugu mteja, mtambo umeshindwa kuchakata vifurushi vya bei hii.</b>";
            } else {
                echo "<b>Tumeshindwa kuanzisha malipo.</b><br><br>Tafadhali hakikisha namba yako ya simu iko hewani na ina salio la kutosha.";
            }
            ?>
        </p>
        <a href="javascript:history.back()" class="btn-portal" style="background: #e74c3c; border-color: darkred; color: white; padding: 12px; display: block; text-decoration: none; font-weight: bold; border-radius: 6px; text-align: center; margin-top: 20px;">RUDI NYUMA</a>
    </div>

<?php endif; ?>
</body>
</html>
<?php 
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
exit();
?>
