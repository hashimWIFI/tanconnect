<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Karibu kwenye Wi-Fi</title>
    <style>
      body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 15px; text-align: center; color: #2c3e50; }
        .container { max-width: 450px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #3498db; margin-bottom: 5px; }
        p { color: #7f8c8d; margin-bottom: 25px; }
        .package-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 3px; }
        .package-card { border: 2px solid #e2e8f0; border-left: 4px solid #3498db; border-bottom: 8px solid #3498db; border-radius: 12px; padding: 8px; cursor: pointer; transition: all 0.2s; background: #f8fafc; width: 65px; height: 50px;}
        .package-card:hover { border-color: #3498db; background-color: #f7fafc; }
        .package-card.selected { border-color: #3498db; background-color: #ebf8ff; }
        .card-price { font-size: 12px; font-weight: bold; color: #0056b3; margin-bottom: 2px;text-align: left; font-family: toledo heavy; }
        .card-data { font-size: 9px;  color: #334155; text-align: left; }
        .card-time { font-size: 10px; color: #64748b; margin-top: 2px;font-weight: bold; text-align: left; }

        

        
        /* Modal Popup Styles */
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-card { background: white; max-width: 340px; height: 38%; margin: 15% auto; padding: 20px; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); text-align: left; position: relative; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 32px; cursor: pointer; color: #7f8c8d; font-weight: bold; }
        .close-btn:hover { color: #34495e; }
        .form-group { margin-bottom: 20px; font-size: 14px}
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="tel"] { color: #0056b3; width: 45%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 16px; font-weight: bold;}
      
        /* Dynamic Provider Button Base Style */
        .btn-submit { background:#0056b3; color:#0056b3; border: none; width: 53%; padding: 14px; font-color: white; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; }
        .btn-submit:hover { filter: brightness(0.9); }
        .title { color: #64748b; font-size: 16px; margin-bottom: 25px; font-family: arial black; font-weight: bold;}
        .footer { font-size: 10px; color: #64748b; margin-top: 10px; line-height: 1.5; font-weight: bold}
        .logo { font-size: 24px; font-weight: bold; color: #1e3c72; letter-spacing: 1px; margin-bottom: 5px;font-family:Broadway, Helvetica, sans-serif; }
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 15px; text-align: left; font-weight: bold;}
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
<img src="logo.png" alt="Water Point Logo" style="max-width: 280px; height: auto; object-fit: contain;">
</div>



    <div class="subtitle">
 

<marquee hspace="-45" vspace="" behavior="" height="20" text-align="bottom" style="font-size: 16px><font color="white"><div><b>Ndugu mteja, karibu kwenye mtandao wa Wi-Fi wa TANConnect &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tunakuletea internet isiyo na ukomo wa kasi ya kuperuzi mtandaoni &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Fuata maelekezo hapa chini kununua Voucher kupitia simu yako ya mkononi&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;||&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Wasiliana nasi kwa nambari 0713 123 974, kwa ufafanuzi, malalamiko au maelekezo zaidi.</b></div></font></marquee></button>


<hr width="100%" align="center"></div>

    <div class="subtitle">Bonyeza kifurushi unachohitaji kununua;</div>
    <div class="package-grid">

        <div class="package-card" onclick="document.getElementById('selected-amount').value='500'; document.getElementById('summary-bold-text').innerHTML='500 TZS || Masaa 6 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">500 TZS</div>
            <div class="card-time">Masaa 6</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='1000'; document.getElementById('summary-bold-text').innerHTML='1,000 TZS || Siku 1 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">1,000 TZS</div>
            <div class="card-time">Siku 1</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='2000'; document.getElementById('summary-bold-text').innerHTML='2,000 TZS || Siku 3 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">2,000 TZS</div>
            <div class="card-time">Siku 2</div>
            <div class="card-data">Unlimited DATA</div>
       </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='4000'; document.getElementById('summary-bold-text').innerHTML='4,000 TZS || Siku 5 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">4,000 TZS</div>
            <div class="card-time">Siku 5</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='5000'; document.getElementById('summary-bold-text').innerHTML='5,000 TZS || Siku 7 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">5,000 TZS</div>
            <div class="card-time">Siku 7</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='7000'; document.getElementById('summary-bold-text').innerHTML='7,000 TZS || Siku 10 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">7,000 TZS</div>
            <div class="card-time">Siku 10</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
        <div class="package-card" onclick="document.getElementById('selected-amount').value='9000'; document.getElementById('summary-bold-text').innerHTML='9,000 TZS || Siku 13 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">9,000 TZS</div>
            <div class="card-time">Siku 13</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
 <div class="package-card" onclick="document.getElementById('selected-amount').value='10000'; document.getElementById('summary-bold-text').innerHTML='10,000 TZS || Siku 15 kuperuzi || Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">10,000 TZS</div>
            <div class="card-time">Siku 15</div>
            <div class="card-data">Unlimited DATA</div>

        </div>
<div class="package-card" onclick="document.getElementById('selected-amount').value='20000'; document.getElementById('summary-bold-text').innerHTML='20,000 TZS || Mwezi 1 kuperuzi|| Unlimited DATA'; document.getElementById('active-spinner-layer').style.setProperty('display', 'none', 'important'); document.getElementById('payment-modal-overlay').style.display='block'; resetButtonState();">
            <div class="card-price">20,000 TZS</div>
            <div class="card-time">Mwezi 1</div>
            <div class="card-data">Unlimited DATA</div>

        </div>

    </div>


<!-- Floating Form Modal Overlay Sheet Container -->
<div id="payment-modal-overlay" class="modal-overlay">
    <div class="modal-card">
        <span class="close-btn" onclick="document.getElementById('payment-modal-overlay').style.display='none';">&times;</span>
        
           <h3 style="margin-top: 0; font-size: 16px; font-weight: bold; color: #34495e;">Checkout & Pay</h3>
          
        
        <div id="modal-plan-summary" class="plan-summary">
            Umechagua kifurushi:<br> <strong id="summary-bold-text" style="color: #0033a0;">1,000 TZS || Masaa 24 kuperuzi || Unlimited DATA</strong>
        </div>
   
<form id="payment-form" action="login.php" method="post">
          <input type="hidden" id="selected-amount" name="amount" value="1000" /> 
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
        
        
</div></div>

</div>
</div>
    





<script>


// Live Operator Detection Engine
function detectMobileProvider() {
    var phoneInput = document.getElementById("phone-number").value.trim();
    var cleanDigits = phoneInput.replace(/[^0-9]/g, '');
    var payBtn = document.getElementById("submit-payment-btn");
    
    // 1. ALWAYS RESET BACK TO GREEN FIRST (Clears any previous sticky states)
    payBtn.style.backgroundColor = "#f15a24";
    payBtn.innerHTML = "PAY";
    
    // 2. Standardize leading 0 to country code format maps
    var standardizedDigits = cleanDigits;
    if (standardizedDigits.startsWith('0')) {
        standardizedDigits = '255' + standardizedDigits.substring(1);
    }
    
    // 3. Extract the operator routing prefix block digits (e.g. from position 3 to 5)
    var prefix = standardizedDigits.substring(3, 5);
    
    // Brand hex colors setup maps
    var mpesaColor   = "#E60000"; // Vodacom Red
    var tigoColor    = "#0033A0"; // Tigo Corporate Blue
    var airtelColor  = "#FF0000"; // Airtel Red-Orange
    var haloColor    = "#ffcc00"; // Halopesa Golden Yellow
    var unknownColor = "#555555"; // Charcoal Gray for Unknown Networks
    
    // 4. Run your exact requested filter gates smoothly
    if (['74', '75', '76', '14'].includes(prefix)) {
        payBtn.style.backgroundColor = mpesaColor;
        payBtn.innerHTML = "PAY(M-pesa)";
    } else if (['71', '77', '65', '07', '67', '72', '70'].includes(prefix)) {
        payBtn.style.backgroundColor = tigoColor;
        payBtn.innerHTML = "PAY(Tigopesa)";
    } else if (['78', '79', '68', '69'].includes(prefix)) {
        payBtn.style.backgroundColor = airtelColor;
        payBtn.innerHTML = "PAY(Airtel Money)";
    } else if (['62', '61'].includes(prefix)) {
        payBtn.style.backgroundColor = haloColor;
        payBtn.innerHTML = "PAY(Halopesa)";
     } else {
        // The last gate: If it doesn't match any provider above, it is instantly unknown!
        payBtn.style.backgroundColor = unknownColor;
        payBtn.innerHTML = "Mtandao Hautambuliki ?";
    }
}




function resetButtonState() {
    var payBtn = document.getElementById("submit-payment-btn");
    payBtn.style.backgroundColor = "#f15a24"; // Default emerald green
    payBtn.innerHTML = "PAY";
}


function dispatchToRailway(event) {
    if (event) event.preventDefault(); 
    
    var phoneInput = document.getElementById("phone-number").value.trim();
    var cleanDigitsOnly = phoneInput.replace(/[^0-9]/g, ''); 

    // 1. Basic Length Controls
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

    // 2. Standardize prefix extraction layers
    var standardizedDigits = cleanDigitsOnly;
    if (standardizedDigits.startsWith('0')) {
        standardizedDigits = '255' + standardizedDigits.substring(1);
    }
    
    // Extract the strict carrier routing suffix index code (e.g. 74, 75, 71, etc.)
    var carrierPrefix = standardizedDigits.substring(3, 5);
    
    // 3. Define valid Tanzanian MNO network code buckets
    var validVodacom  = ['74', '75', '76', '14'];
    var validTigo     = ['71', '77', '65', '07', '67', '72'];
    var validAirtel   = ['78', '79', '68', '69'];
    var validHalotel  = ['62', '61'];
    
    // Combine all recognized buckets into a single master validation dictionary array
    var allValidPrefixes = validVodacom.concat(validTigo, validAirtel, validHalotel);

    // 4. THE PREFIX GATE: If the extracted prefix is completely unknown, freeze submission instantly!
    if (!allValidPrefixes.includes(carrierPrefix)) {
        alert("Mtandao hautambuliki! Tafadhali ingiza nambari ya Vodacom, Tigo, Airtel, au Halotel.");
        return;
    }

    // SUCCESS GATE: Only unrolls the spinner and dispatches form if the prefix matches our dictionary!
    document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
    document.getElementById("payment-form").submit();
}

</script>

<div style="margin: 0; margin: 8px auto; max-width: 450px; max-height: 50px; border-left: 10px solid #3498db; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; border-radius: 0 10px 10px 0; padding: 10px 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-family: sans-serif;" background-image: url('background.png');>
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">💧</span>
        <div>
            <h5 style="margin: 0; color: #1e293b; font-size: 12px; font-weight: bold;">TANConnect Water Point</h5>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 9px; text-align: left;">Karibu ujipatie maji safi na salama yaliyochujwa kwa kutumia technolojia ya kisasa ya RO na UV.<br><br><b>Tunapatikana TANDIKA, Mtaa MALUMBA</p></b></div></div></div>
<hr width="100%" align="center">

 <div class="footer">Unaweza kuwasiliana nasi kwa nambari 0713 123 974 au kwa kutembelea tovuti yetu "www.tanconnect.co.tz".</div> </div>

</body>
</html>
