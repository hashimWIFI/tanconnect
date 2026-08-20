<?php
// ===================================================================
// 🏆 TANCONNECT WATER POINT & WIFI HOTSPOT CONTROL SYSTEM (PRODUCTION)
// ===================================================================
error_reporting(0);
ini_set('display_errors', 0);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Lipia Kifurushi</title>
    <style>
        :root {
            --primary: #0284c7;
            --primary-hover: #0369a1;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --border-clr: #334155;
        }

        body {
            background-color:white;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            color: #ffffff;
        }

        .container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }

        .portal-card {
            background-color:white; 
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            text-align: center;
            margin-bottom: 20px;
        }

        .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 2px;
            margin-bottom: 2px;
        }

      
.package-grid {
    display: grid;
    /* 💡 CHANGED: Automatically creates exactly 3 equal columns per row frame */
    grid-template-columns: repeat(3, 1fr); 
    gap: 10px;
    margin-bottom: 20px;
    width: 100%;
}


        .package-card {
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            border-left: 4px solid #3498db;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .package-card:hover {
            border-color: var(--primary);
            background: lightblue;
            transform: translateY(-1px);
        }

        .card-price {
            font-size: 18px;
            font-weight: bold;
            color: darkblue;
        }

        .card-time {
            font-size: 12px;
            font-weight: bold;
            color: black;
        }

        .card-data {
            font-size: 10px;
            color: #94a3b8;
            font-weight: bold;
        }

        /* 🌫️ PREMIUM FULL-SCREEN GLASS LOADING OVERLAY OVERRIDE */
        .loader-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: rgba(15, 23, 42, 0.75) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            display: none;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            z-index: 99999 !important;
        }

        /* 🌀 THE CHASING 10-DOT CIRCLE RADIUS ENGINE */
        .chasing-spinner {
            position: relative !important;
            width: 100px !important;
            height: 100px !important;
            margin-bottom: 25px !important;
        }

        .chasing-spinner div {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            width: 12px !important;
            height: 12px !important;
            background-color: var(--primary) !important;
            border-radius: 50% !important;
            animation: sequentialLightUp 1s linear infinite !important;
            transform-origin: center center !important;
        }

        /* Spaced fanned circle transformation loops */
        .chasing-spinner div:nth-child(1)  { transform: translate(-50%, -50%) rotate(0deg) translate(0, -45px) !important; animation-delay: 0s !important; }
        .chasing-spinner div:nth-child(2)  { transform: translate(-50%, -50%) rotate(36deg) translate(0, -45px) !important; animation-delay: -0.1s !important; }
        .chasing-spinner div:nth-child(3)  { transform: translate(-50%, -50%) rotate(72deg) translate(0, -45px) !important; animation-delay: -0.2s !important; }
        .chasing-spinner div:nth-child(4)  { transform: translate(-50%, -50%) rotate(108deg) translate(0, -45px) !important; animation-delay: -0.3s !important; }
        .chasing-spinner div:nth-child(5)  { transform: translate(-50%, -50%) rotate(144deg) translate(0, -45px) !important; animation-delay: -0.4s !important; }
        .chasing-spinner div:nth-child(6)  { transform: translate(-50%, -50%) rotate(180deg) translate(0, -45px) !important; animation-delay: -0.5s !important; }
        .chasing-spinner div:nth-child(7)  { transform: translate(-50%, -50%) rotate(216deg) translate(0, -45px) !important; animation-delay: -0.6s !important; }
        .chasing-spinner div:nth-child(8)  { transform: translate(-50%, -50%) rotate(252deg) translate(0, -45px) !important; animation-delay: -0.7s !important; }
        .chasing-spinner div:nth-child(9)  { transform: translate(-50%, -50%) rotate(288deg) translate(0, -45px) !important; animation-delay: -0.8s !important; }
        .chasing-spinner div:nth-child(10) { transform: translate(-50%, -50%) rotate(324deg) translate(0, -45px) !important; animation-delay: -0.9s !important; }

        @keyframes sequentialLightUp {
            0% { opacity: 1; background-color: #38bdf8; box-shadow: 0 0 15px #0284c7; scale: 1.3; }
            100% { opacity: 0.15; background-color: var(--primary); box-shadow: none; scale: 1; }
        }

        .loading-text {
            font-size: 14px !important;
            font-weight: bold !important;
            letter-spacing: 0.5px !important;
            color: #94a3b8 !important;
            text-transform: uppercase !important;
            animation: textFlickerPulse 1.5s ease-in-out infinite !important;
        }

        @keyframes textFlickerPulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; color: #ffffff; }
        }

        /* 📋 MODAL OVERLAY STYLES */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal-card {
            background: #ffffff;
            color: #1e293b;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 24px;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
          
        }

        .close-btn {
            position: absolute;
            top: 12px; right: 16px;
            font-size: 24px; cursor: pointer; color: #64748b;
        }

        .form-group {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-class {
            padding: 12px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            font-size: 16px;
            text-align: center;
            font-weight: bold;
            color: #1e293b;
            max-width: 40%;
            box-sizing: border-box;
            border: 1px solid #ccc;

        }

        .btn-popup-pay {
            background-color: #f15a24;
            color: white;
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
            min-width: 50%;
            text-transform: uppercase;
        }


        .badge-footer {
            margin-top: 25px;
            border-top: 1px solid #334155;
            padding-top: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11px;
            color: #94a3b8;
        }

.btn-submit { background:#0056b3; color:#0056b3;  border: 1px solid #334155; width: 45%; padding: 14px; font-color: white; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer; transition: all 0.3s ease; }

    </style>
</head>
<body>

    <div class="container">
        <!-- SECTIONS 1 & 2: PORTAL HEADER & SCROLLING ANNOUNCEMENT -->
        <div class="portal-card">
            <img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
            <div class="subtitle">
                <marquee behavior="scroll" direction="left" scrollamount="4" style="color: black; font-weight: bold; margin-bottom: 10px;font-family: 'Segoe UI', Arial, sans-serif;">
                    Ndugu mteja, karibu kwenye mtandao wa Wi-Fi wa TANConnect || Tunakuletea internet isiyo na ukomo wa kasi kuperuzi mtandaoni || Fuata maelekezo hapa chini kununua Voucher kupitia simu yako ya mkononi || Kwa ufafanuzi, malamiko au maelekezo zaidi, wasiliana nasi kwa nambari 0713 123 974 
                </marquee>
            </div>
      <hr width="100%" align="center"></hr>

            <div style="font-weight: bold; color: black; text-align: left;">Bonyeza kifurushi unachohitaji kununua:<br><br>
        

        <!-- SECTION 3: THE PACKAGE SELECTION GRID LOOP -->
        <div class="package-grid">
            <div class="package-card" onclick="selectPackage('500', '500 TZS || Masaa 12 kuperuzi || Unlimited DATA')">
                <div class="card-price">500<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>

                <div class="card-time">Masaa 12</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('1000', '1,000 TZS || Siku 1 kuperuzi || Unlimited DATA')">
                <div class="card-price">1,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 1</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('2000', '2,000 TZS || Siku 2 kuperuzi || Unlimited DATA')">
                <div class="card-price">2,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 2</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('4000', '4,000 TZS || Siku 5 kuperuzi || Unlimited DATA')">
                <div class="card-price">4,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 5</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('5000', '5,000 TZS || Siku 7 kuperuzi || Unlimited DATA')">
                <div class="card-price">5,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 7</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
            
            <div class="package-card" onclick="selectPackage('7000', '7,000 TZS || Siku 10 kuperuzi || Unlimited DATA')">
                <div class="card-price">7,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 10</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
 <div class="package-card" onclick="selectPackage('9000', '9,000 TZS || Siku 13 kuperuzi || Unlimited DATA')">
                <div class="card-price">9,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 13</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('10000', '10,000 TZS || Siku 15 kuperuzi || Unlimited DATA')">
                <div class="card-price">10,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 15</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
            
            <div class="package-card" onclick="selectPackage('20000', '20,000 TZS || Siku 30 kuperuzi || Unlimited DATA')">
                <div class="card-price">20,000<span style= "font-size: 12px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 30</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

        </div>

<!-- Floating Form Modal Overlay Sheet Container -->
<div id="payment-modal-overlay" class="modal-overlay">
    <div class="modal-card">
        <span class="close-btn" onclick="document.getElementById('payment-modal-overlay').style.display='none';">&times;</span>
        
           <h3 style="margin-top: 0; font-size: 16px; font-weight: 500px; color: #34495e;">Checkout & Pay</h3>
          
        
        <div id="modal-plan-summary" class="plan-summary">
            Umechagua kifurushi:<br> <strong id="summary-bold-text" style="color: #0033a0;">1,000 TZS || Masaa 24 kuperuzi || Unlimited DATA</strong>
        </div>
   
       <form id="payment-form" action="login.php" method="post">
          <input type="hidden" id="selected-amount" name="amount" value="1000" /> 
            <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                <label for="phone-number">Ingiza nambari ya simu, kisha bonyeza PAY:</label>
<div style="display: flex; gap: 10px;">

<input class="button-submit" name="customer_phone" id="phone-number" pattern="[0]{1}[6-7]{1}[0-9]{8}" type="tel" placeholder="0713123974" autocomplete="off" oninput="detectMobileProvider()" style="flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; color: black; text-align: center;" required/>

<button type="button" id="submit-payment-btn" class="btn-popup-pay" style="margin: 0; padding: 0 30px; background: #3498db; border-radius: 6px; font-size: 13px; color: white; font-weight: bold; min-width: 180px;" onclick="dispatchToRailway(event)">Pay</button>                </div></div>

        </form> </div></div>

        <!-- 🌫️ SECTION 5: NEW PREMIUM FULL-SCREEN GLASS LOADING OVERLAY -->
        <div id="active-spinner-layer" class="loader-overlay">
            <div class="chasing-spinner">
                <div></div><div></div><div></div><div></div><div></div>
                <div></div><div></div><div></div><div></div><div></div>
            </div>
            <div class="loading-text">Tafadhali subiri, tunatayarisha malipo...</div>
        </div>

        <!-- SECTION 6: PHYSICAL LOCATION WATER POINT FOOOTER -->
        <div class="badge-footer" style="border-left: 10px solid #349dbb; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; border-radius: 0 10px 10px 0; padding: 10px; display: flex; justify-content: space-between; gap: 10px; background-image: url('background.png');">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">💧</span>
                <div>
                    <h5 style="margin: 0; color: #1e293b; font-size: 12px; font-weight: bold;">Water Point</h5>
                    <p style="margin: 2px 0 0 0; color: black; font-size: 9px; text-align: left;">
                        Karibu ujipatie maji safi na salama yaliyochujwa kwa <br>kutumia teknolojia ya RO na UV.<br><br>
                        <b>Tupo TANDIKA, Mtaa MALUMBA <b>
                    </p>
                </div>
            </div>
        </div></b>
      
        <div style="text-align: center; margin-top: 5px; padding-top: 5px; border-top: 1px solid #334155; font-size: 8px; color: #64748b;">
            <p>© 2026 TANConnect. All Rights Reserved.<br>
            TANConnect is a registered trademark of <b>NIT Africa Solutions Limited</b>.</p>
        </div>
    </div></div></div>


    <script>
        function selectPackage(amount, summaryText) {
            document.getElementById('selected-amount').value = amount;
            document.getElementById('summary-bold-text').innerHTML = summaryText;
            document.getElementById('payment-modal-overlay').style.display = 'flex';
            resetButtonState();
        }

        function closeModal() {
            document.getElementById('payment-modal-overlay').style.display = 'none';
        }

        function resetButtonState() {
            var payBtn = document.getElementById("submit-payment-btn");
            payBtn.style.backgroundColor = "#f15a24";
            payBtn.innerHTML = "PAY";
        }

        function detectMobileProvider() {
            var phoneInput = document.getElementById("phone-number").value.trim();
            var cleanDigits = phoneInput.replace(/[^0-9]/g, '');
            var payBtn = document.getElementById("submit-payment-btn");

            var standardizedDigits = cleanDigits;
            if (standardizedDigits.startsWith('0')) {
                standardizedDigits = '255' + standardizedDigits.substring(1);
            }
            
            var prefix = standardizedDigits.substring(3, 5);

            // Brand Hex Color Codes
            var mpesaColor    = "#E60000"; // Vodacom Red
            var tigoColor     = "#0033A0"; // Tigo Corporate Blue
            var airtelColor   = "#FF0000"; // Airtel Orange
            var haloloColor   = "#ffcc00"; // Halotel Yellow
            var unknownColor  = "#555555"; // Default

            if (['74', '75', '76', '14'].includes(prefix)) {
                payBtn.style.backgroundColor = mpesaColor;
                payBtn.innerHTML = "PAY (M-Pesa)";
            } else if (['71', '77', '65', '07', '67', '72', '70'].includes(prefix)) {
                payBtn.style.backgroundColor = tigoColor;
                payBtn.innerHTML = "PAY (TigoPesa)";
            } else if (['78', '79', '68', '69'].includes(prefix)) {
                payBtn.style.backgroundColor = airtelColor;
                payBtn.innerHTML = "PAY (Airtel Money)";
            } else if (['62', '61'].includes(prefix)) {
                payBtn.style.backgroundColor = haloloColor;
                payBtn.innerHTML = "PAY (Halopesa)";
            } else {
                payBtn.style.backgroundColor = unknownColor;
                payBtn.innerHTML = "PAY";
            }
        }

        function dispatchToRailway(event) {
            event.preventDefault();
            
            var phoneInput = document.getElementById("phone-number").value.trim();
            var cleanDigitsOnly = phoneInput.replace(/[^0-9]/g, '');

            if (phoneInput === "") {
                alert("Tafadhali ingiza namba ya simu kwanza.");
                return;
            }
            if (cleanDigitsOnly.length < 10) {
                alert("Namba uliyoingiza imepungua! Tafadhali ingiza namba kamili yenye tarakimu 10.");
                return;
            }
            if (cleanDigitsOnly.length > 10) {
                alert("Namba uliyoingiza imezidi! Tafadhali hakikisha namba yako ina tarakimu 10 pekee.");
                return;
            }

            var standardizedDigits = cleanDigitsOnly;
            if (standardizedDigits.startsWith('0')) {
                standardizedDigits = '255' + standardizedDigits.substring(1);
            }
            var carrierPrefix = standardizedDigits.substring(3, 5);

            var validVodacom = ['74', '75', '76', '14'];
            var validTigo    = ['71', '77', '65', '07', '67', '72', '70'];
            var validAirtel  = ['78', '79', '68', '69'];
            var validHalotel = ['62', '61'];
            var allValidPrefixes = validVodacom.concat(validTigo, validAirtel, validHalotel);

            if (!allValidPrefixes.includes(carrierPrefix)) {
                alert("Mtandao hautambuliki! Tafadhali ingiza nambari ya Vodacom, Tigo, Airtel, au Halotel.");
                return;
            }

            // 🌫️ ACTIVATE THE 10-DOT GLASS LOADING OVERLAY
            document.getElementById("active-spinner-layer").style.setProperty("display", "flex", "important");
            
            // Forward form transaction straight to login processing engine
            document.getElementById("payment-form").submit();
        }
    </script>
</body>
</html>
