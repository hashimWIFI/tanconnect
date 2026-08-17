<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =========================================
// 1. DATA HARVESTING & PHONE STANDARDIZATION
// ==========================================
$phone  = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
$amount = isset($_POST['amount']) ? trim($_POST['amount']) : '1000'; 

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
$db_host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

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
        .btn-done { background: #3498db; color: white; border: none; padding: 14px; font-size: 14px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; text-transform: uppercase; transition: background 0.2s; }
        .btn-done:hover { filter: brightness(0.9); }
         .footer { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; font-size: 11px; font-weight: bold; color: #1e3c72;}
     
    </style>
</head>
<body>

<div class="receipt-card">
    <!-- Top-corner Exit Close Button -->
    <span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>

    <img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">

    <!-- FIX 1: Aligned the opening and closing tag matching properties character-for-character -->
    <div class="error-color">Uhaba wa Vifurushi Umejitokeza!</div>

    <p style="font-size: 14px; color: black; line-height: 1.5; margin-top: 15px;">Mtambo umeshindwa kuchakata vifurushi vya bei hii. Tafadhali jaribu vifurushi vingine.</p>
    
    <a href="index.php" class="btn-done" style="background: #e74c3c;">Jaribu</a>
    <br><br><div class="footer">"We bring the world at your finger tips" </div></div>

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
    $updateStmt = $conn->prepare("UPDATE wifi_vouchers SET status = 'PENDING', assigned_phone = ?, transaction_id = ? WHERE id = ?");
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
    <title>TANConnect - Hali ya Malipo</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; }
        .receipt-card { background: white; max-width: 450px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; }
        .success-color { color: #2ecc71; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
        .error-color { color: #e74c3c; font-size: 14px; font-weight: bold; }
         .transit-color { color: #3498db; margin-bottom: 10px; font-size: 14px; font-weight: bold; }
         .footer { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; font-size: 11px; font-weight: bold; color: #1e3c72;}
        .voucher-box { background: #e8f4fd; border: 2px dashed #3498db; padding: 10px; font-size: 14px; color: #7f8c8d; margin: 10px 0; border-radius: 6px; word-break: break-all; }
        .btn-done { background: #3498db; color: white; border: none; padding: 14px; font-size: 14px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; }
   /* Animated Status Spinner Logic */
    </style>

 <?php if ($httpStatusCode === 200): ?>
<div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
<img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
        <span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>

        <!-- FIX 1: Starts out with a professional transit-color blue text theme style! -->
        <h2 id="payment-headline" style="color: #3498db; margin-bottom: 15px; font-size: 16px; font-weight: bold; transition: color 0.4s ease;">Ombi la Malipo Umetumiwa!</h2>
       <p id="payment-subtext" style="font-size: 14px; color: black; line-height: 1.5; margin-top: 5px;"> Tafadhali weka (PIN) kwenye simu yako kuruhusu malipo ya <b>Tsh <?php echo htmlspecialchars($amount); ?></b> kwenda TANConnect Wi-Fi.</p>

               <!-- UPDATED TWIN-BOX AREA: The box container acts as an invisible horizontal row holding two small inline boxes -->
        <div id="voucher-display-box" data-real-pin="<?php echo htmlspecialchars($allocatedVoucherCode); ?>" style="display: flex; gap: 10px; align-items: center; justify-content: space-between; margin: 25px 0; width: 100%; box-sizing: border-box;">
            
            <!-- LEFT BOX (70%): Holds the spinning placeholder text or your final real voucher PIN text string -->
            <div id="status-loading-container" style="flex: 7; background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 14px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; color: #3498db; font-weight: bold; font-size: 13px;">
               <marquee hspace="-45" vspace="" behavior="" height="20" text-align="bottom" style="font-size: 14px><font color="white">
                <div><b>Malipo yanafanyika kupitia mtandao wa AzamPay. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Voucher yako itajitokeza hapa utapoweka PIN kwenye simu yako. &nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp; Vilevile utapokea SMS yenye Voucher yako kutoka 0753 476 850.</b></div>
                </marquee>
                
            </div></div>
            
            <!-- RIGHT BOX (30%): Holds the copy link trigger button completely hidden until payment clears successfully -->
            <div id="copy-button-container" style="flex: 3; display: none; min-height: 55px; box-sizing: border-box;">
                <!-- Buttons styles adjusted with relative positioning parameters to frame tightly inside the small box -->
                <button onclick="copyVoucherToClipboard()" id="copy-btn-trigger" style="width: 100%; height: 55px; background: #3498db; color: white; border: none; font-size: 13px; font-weight: bold; border-radius: 8px; cursor: pointer; text-transform: uppercase; transition: background 0.2s; box-shadow: 0 4px 10px rgba(52,152,219,0.15);">Nakili</button>
            </div></div>
<div class="footer">"We bring the world at your finger tips" </div>
</body>
 </html>       

        
    <?php else: ?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Uhaba wa Vifurushi</title>
<div class="receipt-card" style="position: relative; overflow: hidden; padding-top: 40px;">
<img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
<span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>
</head>
<body>
    <div class="error-color">✕ Hitilafu ya Mtandao Imejitokeza!</div>
        <p style="font-size: 14px; color: black; line-height: 1.5; margin-top: 15px;">Tumeshindwa kuwasiliana na <strong><?php echo ($provider === 'Mpesa') ? 'M-Pesa' : (($provider === 'Tigo') ? 'Tigopesa' : (($provider === 'Airtel') ? 'Airtel Money' : (($provider === 'Halopesa') ? 'Halopesa' : 'simu yako'))); ?></strong> kuanzisha malipo, tafadhali jaribu tena.</p>
       <a href="index.php" class="btn-done" style="background: #e74c3c; width: 100%; box-sizing: border-box; text-decoration: none;">JARIBU</a><br>
      <br> <div class="footer">"We bring the world at your finger tips" </div></div> 
</body>
</html>
<?php endif; ?> 
<script>
// 1. Grab the unique transaction tracking ID generated for this session
var activeTxId = "<?php echo $transactionId; ?>"; 

function startPaymentVerificationLoop() {
    // Check status in the background every 3000 milliseconds (3 seconds)
    var checkInterval = setInterval(function() {
        fetch('check_status.php?tx_id=' + activeTxId)
            .then(response => response.json())
            .then(data => {
                // If fake_callback.php updates your database row status to USED or SUCCESS
                if (data.status === 'USED' || data.status === 'SUCCESS') {
                    clearInterval(checkInterval); // Kill background intervals completely
                    
                    // Pull package tier details directly from your active PHP settings
                    var planAmount = "<?php echo htmlspecialchars($amount); ?>";
                    var planDuration = (planAmount === "500") ? "Masaa 6" : (planAmount === "1000") ? "Siku 1" : (planAmount === "2000") ? "Siku 2" : (planAmount === "4000") ? "Siku 5" : (planAmount === "5000") ? "Siku 7" : (planAmount === "7000") ? "Siku 10" : (planAmount === "9000") ? "Siku 13" : (planAmount === "10000") ? "Siku 15" : (planAmount === "20000") ? "Siku 30" : "Siku 1";
                    
                    // DYNAMIC STATE UPGRADE: Morph headline typography elements from Transit Blue straight to Success Green
                    var headlineElement = document.getElementById('payment-headline');
                    if (headlineElement) {
                        headlineElement.style.color = "#2ecc71"; 
                        headlineElement.innerHTML = "✓ Malipo Yamekamilika!";
                    }

                    // Dynamically map amount and period confirmation notification text strings
                    var subtextElement = document.getElementById('payment-subtext');
                    if (subtextElement) {
                        subtextElement.innerHTML = "Hongera! Umefanikiwa kununua kifurushi cha <b><br>Tsh " + parseInt(planAmount).toLocaleString() + "</b> kitatumika kwa muda wa <b> " + planDuration + ".</b> kuingia mtandaoni"; 
                    }
                    
                    // INLINE REVEAL LOGIC: Capture our twin structural layer elements safely
                    var masterDisplayFrame = document.getElementById('voucher-display-box');
                    var leftContainerBox = document.getElementById('status-loading-container');
                    var rightContainerBox = document.getElementById('copy-button-container');
                    
                    if (masterDisplayFrame && leftContainerBox && rightContainerBox) {
                        var realPinCode = masterDisplayFrame.getAttribute('data-real-pin');
                        
                        // TARGET LEFT BOX: Turn its border emerald green, refresh background, and insert raw PIN strings cleanly
                        leftContainerBox.innerHTML = `<span id="raw-pin-string" style="font-size: 20px; font-weight: bold; color: #2c3e50; letter-spacing: 1px;">${realPinCode}</span>`;
                        leftContainerBox.style.border = "3px solid #2ecc71";
                        leftContainerBox.style.backgroundColor = "#ebf8ff";
                        
                        // TARGET RIGHT BOX: Reveal your small right button box inline right next to it!
                        rightContainerBox.style.display = "block";
                    }
                }
            })
            .catch(err => console.log("Waiting for PIN validation..."));
    }, 3000);
}

function copyVoucherToClipboard() {
    // 1. Core Extraction: Snaps your raw code number string from the text element card layers
    var pinText = document.getElementById("raw-pin-string").innerText.trim();

    // 2. CLIPBOARD BACKUP: Saves the code to the customer's phone memory track as a backup
    navigator.clipboard.writeText(pinText).then(function() {
        var copyBtn = document.getElementById("copy-btn-trigger");
        if (copyBtn) {
            // Give instant visual feedback to the user
            copyBtn.innerHTML = "✓ INAINGIZA...";
            copyBtn.style.background = "#2ecc71"; // Flashes success green
        }

        // ==========================================
        // BYPASS ROUTER PORTAL & LOGIN DIRECTLY
        // ==========================================
        setTimeout(function() {
            // A. Create a hidden form completely programmatically
            var hiddenForm = document.createElement('form');
            hiddenForm.method = 'POST'; // Most routers process credentials via POST methods
            
            // B. TARGET ENDPOINT: Point directly to the router's core login processor
            hiddenForm.action = 'http://192.168.88.1'; 

            // C. VOUCHER SEED: Generate the dynamic inputs to inject the key data fields
            var voucherInput = document.createElement('input');
            voucherInput.type = 'hidden';
            
            // D. ELEMENT NAME: Standard Mikrotik/captive routers expect the value parameter named 'username'
            voucherInput.name = 'username'; 
            voucherInput.value = pinText;

            // E. Optional Fallback: Some portals require an identical password field matching the voucher
            var passwordInput = document.createElement('input');
            passwordInput.type = 'hidden';
            passwordInput.name = 'password';
            passwordInput.value = ""; // Leave blank or match pinText depending on router configuration

            // F. Assemble the elements, append them to the current DOM window canvas, and execute!
            hiddenForm.appendChild(voucherInput);
            hiddenForm.appendChild(passwordInput);
            document.body.appendChild(hiddenForm);
            
            hiddenForm.submit(); // This actions the "HODI" click automatically
        }, 800); // 800ms delay lets user see the button change state smoothly

    }).catch(function(err) {
        console.error("Clipboard failure, falling back to direct form post: ", err);
        
        // Immediate fallback submit if browser security blocks clipboard operations
        var hiddenForm = document.createElement('form');
        hiddenForm.method = 'POST';
        hiddenForm.action = 'http://192.168.88.1'; 
        var voucherInput = document.createElement('input');
        voucherInput.type = 'hidden';
        voucherInput.name = 'username'; 
        voucherInput.value = pinText;
        hiddenForm.appendChild(voucherInput);
        document.body.appendChild(hiddenForm);
        hiddenForm.submit();
    });
}

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

// Global execution trigger point on canvas window frame launch
window.onload = function() {
    if (activeTxId !== "") {
        startPaymentVerificationLoop();
    }
};
</script>
