<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Karibu kwenye Wi-Fi </title>
    <style>
      body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; text-align: center; color: #2c3e50; }
        .container { max-width: 800px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #3498db; margin-bottom: 5px; }
        p { color: #7f8c8d; margin-bottom: 25px; }
        .package-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .package-card { border: 2px solid #e2e8f0; border-left: 4px solid #3498db; border-bottom: 4px solid #3498db; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.2s; background: #f8fafc; }
        .package-card:hover { border-color: #3498db; background-color: #f7fafc; }
        .package-card.selected { border-color: #3498db; background-color: #ebf8ff; }
        .card-price { font-size: 14px; font-weight: bold; color: #0056b3; margin-bottom: 4px;text-align: left; font-family: toledo heavy; }
        .card-data { font-size: 11px;  color: #334155; text-align: left; }
        .card-time { font-size: 12px; color: #64748b; margin-top: 2px;font-weight: bold; text-align: left; }

        
        /* Modal Popup Styles */
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 61%; background: rgba(15, 23, 42, 0.6); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-card { background: white; max-width: 380 px; height: 55%; margin: 15% auto; padding: 20px; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); text-align: left; position: relative; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 32px; cursor: pointer; color: #7f8c8d; font-weight: bold; }
        .close-btn:hover { color: #34495e; }
        .form-group { margin-bottom: 20px; font-size: 14px}
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="tel"] { color: #0056b3; width: 45%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 16px; font-weight: bold;}
      
        /* Dynamic Provider Button Base Style */
        .btn-submit { background:#0056b3; color:#0056b3; border: none; width: 53%; padding: 14px; font-color: white; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; }
        .btn-submit:hover { filter: brightness(0.9); }
        .title { color: #64748b; font-size: 16px; margin-bottom: 10px; font-family: arial black; font-weight: bold;}
        .footer-text { font-size: 12px; color: #64748b; margin-top: 20px; line-height: 1.5; font-weight: bold}
        .logo { font-size: 24px; font-weight: bold; color: #1e3c72; letter-spacing: 1px; margin-bottom: 5px;font-family:Broadway, Helvetica, sans-serif; }
        .logo2 { font-family: 'Segoe UI', Arial, sans-serif; text-align: center; font-size: 10px; font-weight: bold; color: #1e3c72;}
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; text-align: left; font-weight: bold;}
        .plan-summary { background: #ebf3fc; border: 1px solid #d0e2fa; border-left: 5px solid #3498db; padding: 14px; border-radius: 4px; margin-bottom: 22px; color: #002e6e; font-size: 13px; }

/* 1. MASTER CONTAINER (PUSHED DOWN BY TWO LINES) */
.tight-spinner-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    box-sizing: border-box;
    
    /* FIX A: Adds roughly 40px of top margin padding to shift the entire loader down by two lines! */
    margin-top: 40px; 
}

/* 2. THE ROTATING ORBIT ENGINE (WITH A VISIBLE OVERLAY RING) */
.single-dot-orbit {
    position: relative;
    width: 96px;   
    height: 96px;  
    margin: 20px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    
    /* FIX B: Draws a thin, beautiful light gray circular line path outline */
    border: 1px solid rgba(0, 0, 0, 0.08); 
    border-radius: 50%; /* Forces the border to wrap into a perfect geometric circle */
    
    /* Triggers the fluid, continuous 360-degree rotation engine loop */
    animation: smoothOrbitRotate 1s linear infinite;
}

@keyframes smoothOrbitRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* 3. THE ONE TRUE REVOLVING DOT: Locked onto the absolute top edge of the spinning wheel */
.the-revolving-dot {
    position: absolute;
    top: 0;         /* Places it dead-center at the absolute top point of the orbit path line */
    left: 50%;
    transform: translateX(-50%);
    width: 8px;    /* The size of your single tracking dot */
    height: 8px;
    background-color: #f15a24; /* Your vibrant orange layout theme color */
    border-radius: 50%;
    box-shadow: 0 0 6px #f15a24; /* Subtle glow effect to make the single dot pop out clearly */
}

/* 4. STATIC CENTRAL WORD (WITH COUNTER-ROTATION AND FADING PULSE) */
.spinner-text-center {
    position: absolute;
    font-size: 16px;
    font-weight: 900;
    color: lightblue;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 10;
    
    /* FIX: Runs the flat rotation counter-animation AND the breathing fade animation together! */
    animation: counterTextRotate 1s linear infinite, subiriTextFade 1.6s ease-in-out infinite;
}

@keyframes counterTextRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(-360deg); }
}

/* New Fading Animation: Transitions opacity smoothly to create a breathing text pulse */
@keyframes subiriTextFade {
    0%, 100% { 
        opacity: 0.2; /* Softly faded out state */
    }
    50% { 
        opacity: 1;   /* Fully crisp and visible state */
    }
}

 
 </style>
</head>

<body>
<div class="container">
<div class="portal-card">

 <div class="logo" style="font-size: 24px; font-family:Broadway, Helvetica, sans-serif;color:#1e3c72;">TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: normal; vertical-align: super; line-height: 0;"><sup>®</sup></sup></div><div class="logo2">"We bring the world at your finger tips"</div>


    <div class="subtitle">
 

<marquee hspace="-45" vspace="" behavior="" height="20" text-align="bottom" style="font-size: 18px><font color="white"><div><b>Ndugu mteja, karibu kwenye mtandao wa Wi-Fi wa TANConnect &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tunakuletea internet isiyo na ukomo wa kasi ya kuperuzi mtandaoni &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Fuata maelekezo hapa chini kununua Voucher kupitia simu yako ya mkononi&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Wasiliana nasi kwa nambari 0713 123 974, kwa ufafanuzi, malalamiko au maelekezo zaidi.</b></div></font></marquee></button>


<hr width="100%" align="center"></div>

    <div class="subtitle">Bonyeza kifurushi unachohitaji kununua;</div>
    <div class="package-grid">

        <div class="package-card" onclick="document.getElementById('selected-amount').value='500'; document.getElementById('summary-bold-text').innerHTML='500 TZS || Masaa 6 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">500 TZS</div>
            <div class="card-time">Masaa 6</div>
            <div class="card-data">Unlimited DATA</div>

        </div>

        <div class="package-card" onclick="document.getElementById('selected-amount').value='1000'; document.getElementById('summary-bold-text').innerHTML='1,000 TZS || Siku 1 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">1,000 TZS</div>
            <div class="card-time">Siku 1</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='2000'; document.getElementById('summary-bold-text').innerHTML='2,000 TZS || Siku 3 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">2,000 TZS</div>
            <div class="card-time">Siku 3</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='5000'; document.getElementById('summary-bold-text').innerHTML='5,000 TZS || Siku 7 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">5,000 TZS</div>
            <div class="card-time">Siku 7</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='20000'; document.getElementById('summary-bold-text').innerHTML='20,000 TZS || Mwezi 1 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">20,000 TZS</div>
            <div class="card-time">Mwezi 1</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='35000'; document.getElementById('summary-bold-text').innerHTML='35,000 TZS || Miezi 2 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">35,000 TZS</div>
            <div class="card-time">Miezi 2</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
 <div class="package-card" onclick="document.getElementById('selected-amount').value='50000'; document.getElementById('summary-bold-text').innerHTML='50,000 TZS || Miezi 3 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">50,000 TZS</div>
            <div class="card-time">Miezi 3</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='100000'; document.getElementById('summary-bold-text').innerHTML='100,000 TZS || Miezi 6 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">100,000 TZS</div>
            <div class="card-time">Miezi 6</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='200000'; document.getElementById('summary-bold-text').innerHTML='200,000 TZS || Mwaka 1 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState(); trackPopupOpeningSession();">

            <div class="card-price">200,000 TZS</div>
            <div class="card-time">Mwaka 1</div>
            <div class="card-data">Unlimited DATA</div>

        </div>

    </div>
<!-- HIDDEN INPUT CARRIER: Passes the final computed session steps safely to login.php! -->
<input type="hidden" id="pop-history-counter-field" name="history_steps_total" value="2" />


<!-- Floating Form Modal Overlay Sheet Container -->
<div id="payment-modal-overlay" class="modal-overlay">
    <div class="modal-card">
        <span class="close-btn" onclick="document.getElementById('payment-modal-overlay').style.display='none';">&times;</span>
        
           <h3 style="margin-top: 0; font-size: 16px; font-weight: bold; color: #34495e;">Checkout & Pay</h3>
          
        
        <div id="modal-plan-summary" class="plan-summary">
            Umechagua kifurushi:<br> <strong id="summary-bold-text" style="color: #0033a0;">1,000 TZS || Masaa 24 kuperuzi || Unlimited DATA</strong>
        </div>
   
<form id="payment-form" action="login.php" method="post">
    <!-- Inside your <form id="payment-form"> container block -->
<input type="hidden" id="selected-amount" name="amount" value="1000" />

<!-- FIX A: This hidden input field dynamically captures and carries the customer's MAC address forward to login.php! -->
<input type="hidden" id="router-mac-carrier" name="mac_address" value="" />

            <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                <label for="phone-number">Ingiza nambari ya simu, kisha bonyeza PAY:</label>
<div style="display: flex; gap: 10px;">

<input class="button" name="customer_phone" id="phone-number" pattern="[0]{1}[6-7]{1}[0-9]{8}" type="tel" placeholder="0713123974" autocomplete="off" oninput="detectMobileProvider()" style="flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; color: black; text-align: center;" required/>

<button type="button" id="submit-payment-btn" class="btn-popup-pay" style="margin: 0; padding: 0 30px; background: #3498db; border-radius: 6px; font-size: 13px; color: white; font-weight: bold; min-width: 180px;" onclick="dispatchToRailway(event)">Pay</button>                </div></div>




        </form>

<!-- Centered Processing Overlay Mask (Fully transparent background layout) -->
<div id="active-spinner-layer" style="display: none !important; position: absolute; top: 0; left: 0; width: 90%; height: 90%; background: transparent; z-index: 100; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; padding: 20px;">
    
    <div class="tight-spinner-wrapper">
        
        <!-- THE SINGLE DOT ORBIT: The outer ring spins, carrying 1 dot while 'SUBIRI' stays firm -->
        <div class="single-dot-orbit">
            <span class="spinner-text-center"><b>SUBIRI</b></span>
            <div class="the-revolving-dot"></div> <!-- Only 1 single dot item row remains! -->
        </div>
        
        
        
    </div>
</div>

</div>
</div></div>
<!-- ========================================== -->
<!-- 🎭 THE TANCONNECT MASTER POPUP OVERLAY LAYOUT -->
<!-- ========================================== -->
<div id="payment-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(44, 62, 80, 0.6); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;">
    
    <!-- 📄 Dynamic Receipt Content Target Card -->
    <div id="modal-card-content" style="background: #ffffff; width: 100%; max-width: 460px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) both; padding: 24px; position: relative; box-sizing: border-box;">
        
        <!-- ✕ Manual Close Button Option Anchor -->
        <button onclick="closePaymentModal()" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 24px; color: #95a5a6; cursor: pointer; line-height: 1; padding: 0;">&times;</button>
        
        <!-- Dynamic Layout Target Injection Node -->
        <div id="modal-body-injector"></div>
        
    </div>
</div>

<!-- Simple Keyframe Scale Physics Simulation Animation Code -->
<style>
@keyframes popIn {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

</body>
<div class="footer-text"><hr width="100%" align="center">Unaweza kuwasiliana nasi kwa nambari 0713 123 974 au kwa kutembelea tovuti yetu "www.tanconnect.co.tz".</div> </div>
    
<script>
// Replace your old form element submission redirect loop code block with this clean script:
function triggerPaymentModal(amount, phone, trackingTxId) {
    // 1. Reveal our master blurred modal framework canvas on top of the page screen view
    var overlay = document.getElementById('payment-modal-overlay');
    var injector = document.getElementById('modal-body-injector');
    overlay.style.display = "flex";
    
    // 2. Initialize the default "Weka PIN" tracking marquee layout template view inside the modal card slot
    injector.innerHTML = `
        <div style="text-align: center; font-family: sans-serif;">
            <h2 style="color: #2c3e50; font-size: 22px; margin-bottom: 5px; font-weight: bold; font-family: 'Century Gothic', sans-serif;">TANConnect <span style="font-size:12px; vertical-align:super;">®</span></h2>
            <h3 id="modal-headline" style="color: #3498db; font-size: 16px; margin: 15px 0; font-weight: bold;">⏳ Tafadhali Weka PIN...</h3>
            <p id="modal-subtext" style="color: #7f8c8d; font-size: 13px; line-height: 1.6; margin-bottom: 20px;">
                Tafadhali angalia simu yako sasa hivi na uweke namba yako ya siri (PIN) ya siri kuruhusu muamala wa <b>Tsh ${parseInt(amount).toLocaleString()}</b> kukamilika kupitia AzamPay. ASANTE.
            </p>
            
            <!-- Flex Row Box Holds Loader Canvas Info Panel Elements Line -->
            <div style="display: flex; width: 100%; gap: 12px; margin-top: 15px;">
                <div id="modal-loading-box" style="flex: 7; background: #e8f4fd; border: 2px dashed #3498db; border-radius: 8px; padding: 12px; min-height: 55px; display: flex; align-items: center; justify-content: center; box-sizing: border-box;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; color: #3498db; font-weight: bold; font-size: 12px; width: 100%;">
                        <div style="width: 14px; height: 14px; border: 2px solid rgba(52,152,219,0.2); border-radius: 50%; border-top-color: #3498db; animation: spin 1s linear infinite; flex-shrink:0;"></div>
                        <marquee behavior="scroll" direction="left" scrollamount="4" style="width: 100%;">Inasubiri uthibitisho wa malipo ya PIN kutoka mtandao wa AzamPay...</marquee>
                    </div>
                </div>
                <div id="modal-copy-box" style="flex: 3; display: none; box-sizing: border-box;">
                    <button onclick="copyModalVoucher()" style="width: 100%; background: #3498db; color: #fff; border: none; border-radius: 8px; font-weight: bold; font-size: 12px; height: 55px; cursor: pointer; text-transform: uppercase;">Nakili</button>
                </div>
            </div>
            <p style="color: #7f8c8d; font-size: 11px; margin-top: 20px; font-style: italic;">"We bring the world at your finger tips"</p>
        </div>
    `;

    // 3. Immediately launch our automated background loop targeting check_status.php right from this modal!
    startModalVerificationLoop(trackingTxId, amount);
}

// Quick Global Close Method Component Action Pointer Rule
function closePaymentModal() {
    document.getElementById('payment-modal-overlay').style.color = "none";
    document.getElementById('payment-modal-overlay').style.display = "none";
}

// Live Mobile Network Operator Color Detection Engine
function detectMobileProvider() {
    var phoneInput = document.getElementById("phone-number").value.trim();
    var cleanDigits = phoneInput.replace(/[^0-9]/g, '');
    var payBtn = document.getElementById("submit-payment-btn");
    
    payBtn.style.backgroundColor = "#f15a24"; // Default customized theme orange
    payBtn.innerHTML = "Pay";
    
    var standardizedDigits = cleanDigits;
    if (standardizedDigits.startsWith('0')) {
        standardizedDigits = '255' + standardizedDigits.substring(1);
    }
    
    if (cleanDigits.length < 2) return; 
    
    var prefix = standardizedDigits.substring(3, 5);
    
    if (['74', '75', '76', '14'].includes(prefix)) {
        payBtn.style.backgroundColor = "#E60000"; // Vodacom Red
        payBtn.innerHTML = "M-Pesa";
    } else if (['71', '77', '65', '07', '67', '72'].includes(prefix)) {
        payBtn.style.backgroundColor = "#0033A0"; // Tigo Blue
        payBtn.innerHTML = "Tigo Pesa";
    } else if (['78', '79', '68', '69'].includes(prefix)) {
        payBtn.style.backgroundColor = "#FF0000"; // Airtel Red
        payBtn.innerHTML = "Airtel Money";
    } else if (['62', '61'].includes(prefix)) {
        payBtn.style.backgroundColor = "#2ECC71"; // Halopesa Green
        payBtn.innerHTML = "Halopesa";
    } else {
        if (cleanDigits.length >= 10) {
            payBtn.style.backgroundColor = "#555555"; 
            payBtn.innerHTML = "Unknown";
        }
    }
}

function resetButtonState() {
    var payBtn = document.getElementById("submit-payment-btn");
    if (payBtn) {
        payBtn.style.backgroundColor = "#f15a24";
        payBtn.innerHTML = "Pay";
    }
}

// Full 10-Digit & Carrier Prefix Validation with Router Parameter Forwarding
function dispatchToRailway(event) {
    if (event) event.preventDefault(); 
    
    var phoneInput = document.getElementById("phone-number").value.trim();
    var cleanDigitsOnly = phoneInput.replace(/[^0-9]/g, ''); 

    // 1. Core Length Validation Checks
    if (phoneInput === "") {
        alert("Tafadhali ingiza namba ya simu kwanza.");
        return;
    } else if (cleanDigitsOnly.length < 10) {
        alert("Namba uliyoingiza imepungua! Tafadhali ingiza namba kamili yenye tarakimu 10.");
        return;
    } else if (cleanDigitsOnly.length > 10) {
        alert("Namba uliyoingiza imezidi! Tafadhali hakikisha namba yako ina tarakimu 10 pekee.");
        return;
    }

    // 2. Standardize Prefix Extraction Configurations
    var standardizedDigits = cleanDigitsOnly;
    if (standardizedDigits.startsWith('0')) {
        standardizedDigits = '255' + standardizedDigits.substring(1);
    }
    
    var carrierPrefix = standardizedDigits.substring(3, 5);
    
    var validVodacom  = ['74', '75', '76', '14'];
    var validTigo     = ['71', '77', '65', '07', '67', '72'];
    var validAirtel   = ['78', '79', '68', '69'];
    var validHalotel  = ['62', '61'];
    
    var allValidPrefixes = validVodacom.concat(validTigo, validAirtel, validHalotel);

    // 3. Prefix Gate Validation Check
    if (!allValidPrefixes.includes(carrierPrefix)) {
        alert("Mtandao hautambuliki! Tafadhali ingiza nambari ya Vodacom, Tigo, Airtel, au Halotel.");
        return;
    }

    // SUCCESS GATE: Unrolls the full-screen transparent loading spinner layer instantly
    document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
    
    // FIX: Grabs the hidden router query strings (?device_id=...&mac=...) and sticks them right onto the form route!
    var formElement = document.getElementById("payment-form");
    if (formElement) {
        formElement.action = "login.php" + window.location.search;
        formElement.submit();
    }
}

// Initialize the default history baseline steps needed to step past index.php
var totalPopSessionSteps = 2; 

function trackPopupOpeningSession() {
    // Every single time the popup box opens without leaving index.php, 
    // we update the tracking variable state to keep history.go completely accurate! [^2]
    var hiddenCounterCarrier = document.getElementById('pop-history-counter-field');
    if (hiddenCounterCarrier) {
        hiddenCounterCarrier.value = totalPopSessionSteps.toString();
    }
    
    console.log("Current calculated browser rewind step map: " + totalPopSessionSteps);
    
    // Increment the counter step map for any subsequent double-clicks or re-opens
    totalPopSessionSteps++; 
}
  
</script>
