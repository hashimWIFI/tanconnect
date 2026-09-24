<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Kuchagua Kifurushi</title>
    <style>
        /* ========================================================================= */
        /* 🎨 THE CLEANED & RESPONSIVE COMPONENT CORE STYLESHEET                      */
        /* ========================================================================= */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 10px;
            color: #333;
            box-sizing: border-box;
        }
        
        .container {
            max-width: 450px;
            margin: 0 auto;
            background: #ffffff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            box-sizing: border-box;
        }
        
        .logo-area {
            text-align: center;
            padding-bottom: 10px;
        }
        
        .subtitle {
            margin-bottom: 15px;
        }
        
        .section-title { 
            color: black; 
            font-size: 14px; 
            margin-bottom: 15px; 
            text-align: left; 
            font-weight: bold;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .card {
            border: 2px solid #0056b3;
            border-radius: 8px;
            padding: 12px 4px;
            text-align: center;
            cursor: pointer;
            background: #fff;
            transition: transform 0.1s ease;
            border-bottom: 4px solid #ff9900;
            box-sizing: border-box;
        }
        
        .card:active {
            transform: scale(0.95);
        }
        
        .price {
            display: block;
            font-size: 16px;
            font-weight: bold;
            color: #003366;
        }
        
        .duration {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin: 4px 0;
            color: #ff6600;
        }
        
        .data {
            display: block;
            font-size: 10px;
            color: #718096;
        }
        
        #topup_button {
            display: block;
            background-color: #FF6600;
            width: 100%;
            color: #ffffff;
            text-align: center;
            border: 2px solid grey;
            border-radius: 8px;
            font-weight: bold;
            padding: 12px 14px;
            text-decoration: none;
            font-size: 14px;
            box-sizing: border-box;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.1s ease, filter 0.2s ease;
        }
        
        #topup_button:hover {
            filter: brightness(0.9);
        }
        
        #topup_button:active {
            transform: scale(0.98);
        }

        /* ========================================================================= */
        /* 🚀 FIXED POPUP SIDE-BY-SIDE FLEXBOX LAYOUT DESIGN ENGINE                   */
        /* ========================================================================= */
        .modal {
            display: none; 
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 10px;
            box-sizing: border-box;
        }
        
        .modal-content {
            background-color: #fefefe;
            padding: 25px 20px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            position: relative;
            text-align: center;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .close-btn {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 28px;
            cursor: pointer;
            color: #64748b;
            line-height: 1;
        }

        /* Forces fields to run side-by-side cleanly without breaking rows */
        .popup-flex-form {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            width: 100%;
            margin-top: 15px;
            box-sizing: border-box;
        }
        
        .popup-input-wrapper {
            flex: 1; 
            display: flex;
            flex-direction: column;
        }
        
        .popup-input-wrapper label {
            display: block;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #475569;
        }
        
        .popup-input-wrapper input {
            width: 100%;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            height: 48px;
            outline: none;
            font-weight: bold;
            color: #0056b3;
            background-color: #fff;
        }
        
        .popup-input-wrapper input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-pay {
            background: #28a745;
            color: white;
            border: 2px solid grey;
            padding: 0 20px;
            height: 40px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 6px;
	    min-width: 55%;
            cursor: pointer;
            white-space: nowrap;
            box-sizing: border-box;
            transition: all 0.15s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-pay:active {
            transform: scale(0.96);
        }

        /* 🎨 THE OPERATOR CARRIER BRANDING PROFILES STYLE CONFIGURATIONS */
        .mno-mpesa { background-color: #E60000 !important; color: white !important; }
        .mno-tigo { background-color: #0033A0 !important; color: white !important; }
        .mno-airtel { background-color: #FF0000 !important; color: white !important; }
        .mno-halopesa { background-color: #ffcc00 !important; color: black !important; }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #718096;
            margin-top: 25px;
            line-height: 1.5;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
 input[type="tel"] { color: #0056b3; min-width: 80%; height: 40%; padding: 10px; border: 2px solid grey; border-radius: 6px; box-sizing: border-box; font-size: 16px; font-weight: bold; text-align:center;}

    </style>
    
    <script type="text/javascript">
        // Core vendor redirect script containing your fixed router parameters
        function recharge(device_id, mac) {
            var rawDigits = mac ? mac.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().trim() : '0';
            var formattedMac = rawDigits;
            
            if (rawDigits.length === 12) {
                var groups = [];
                for (var i = 0; i < 12; i += 2) {
                    groups.push(rawDigits.substr(i, 2));
                }
                formattedMac = groups.join(':'); 
            }
            
            var nmsUrl = "http://na.solnms.net/SOL/rechargeMobileManage.do";
            var targetLink = nmsUrl + "?device_id=" + device_id + 
                            "&mac_address=" + encodeURIComponent(formattedMac) + 
                            "&language=en&billType=0&roamingFlag=0&billing_mode=0";
            
            window.top.location.href = targetLink;
            return true;
        }

        // Dynamic Modal Controller Logic
        function openPaymentModal(priceText, durationText) {
            document.getElementById('selectedTierText').innerHTML = priceText + " || " + durationText + " || Unlimited DATA";
            
            var pureNumberAmount = priceText.replace(/[^0-9]/g, '');
            document.getElementById('hiddenPriceTier').value = pureNumberAmount;
            document.getElementById('hiddenDuration').value = durationText;
            
            document.getElementById('checkoutModal').style.display = "flex";
        }

        function closePaymentModal() {
            document.getElementById('checkoutModal').style.display = "none";
        }
    </script>
</head>
<body>
    <div class="container" id="content">

        <!-- Logo Area & Introduction Header -->
        <div class="logo-area">
            <img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
       

        <div class="subtitle">
            <marquee behavior="scroll" direction="left" scrollamount="4" style="color: black; border-bottom: 2px solid #e2e8f0; font-weight: bold; font-size: 13px; margin-bottom: 10px; font-family: 'Segoe UI', Arial, sans-serif;"> 
                Ndugu mteja, karibu kwenye mtandao wa Wi-Fi wa TANConnect || Tunakuletea internet isiyo na ukomo wa kasi kuperuzi mtandaoni || Fuata maelekezo hapa chini kununua Voucher kupitia simu yako ya mkononi || Kwa maoni au malalamiko, wasiliana nasi kwa nambari zilizo chini ya ukurasa huu
            </marquee>
        </div>
        
        <section id="main_content">
            <article>
                
                <!-- STEP 1: CHOOSE A PACKAGE TO PURCHASE -->
                <div class="section-title">Chagua Kifurushi hapa;</div>
                <div class="pricing-grid">
                    
                    <div class="card" onclick="openPaymentModal('500 TZS', 'Masaa 6')">
                        <span class="price">500 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Masaa 6</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('1,000 TZS', 'Siku 1')">
                        <span class="price">1,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 1</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

 			<div class="card" onclick="openPaymentModal('2,000 TZS', 'Siku 2')">
                        <span class="price">2,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 2</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('4,000 TZS', 'Siku 5')">
                        <span class="price">4,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 5</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('5,000 TZS', 'Siku 7')">
                        <span class="price">5,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 7</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('7,000 TZS', 'Siku 9')">
                        <span class="price">7,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 9</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('9,000 TZS', 'Siku 13')">
                        <span class="price">9,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 13</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('10,000 TZS', 'Siku 15')">
                        <span class="price">10,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 15</span>
                        <span class="data">Unlimited DATA</span>
                    </div>

                    <div class="card" onclick="openPaymentModal('20,000 TZS', 'Siku 30')">
                        <span class="price">20,000 <span style="font-size: 9px; font-weight: bold; color: #34495e;"> TZS</span></span>
                        <span class="duration">Siku 30</span>
                        <span class="data">Unlimited DATA</span>
                    </div>
                </div>

              
            </article>
        </section>
        <!-- DYNAMIC POPUP MODAL FOR MOBILE NUMBER & PRICING CAPTURE -->
        <div id="checkoutModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closePaymentModal()">&times;</span>
                <h3 style="color: #003366; margin-top: 0; text-align: left; font-size: 18px;">Checkout & Pay</h3>
 
<div id="modal-plan-summary" class="plan-summary">
<p style="font-size: 14px; text-align: left; margin: 5px 0 15px 0;">Umechagua kifurushi:<br> <strong id="selectedTierText" style="color: #ff6600;"></strong></p>

                    </div>

                
                <!-- SIDE-BY-SIDE FIXED CONTAINER: Submits cleanly to your live cloud layout server on Railway -->
                <form id="payment-form" action="login.php" method="POST" class="popup-flex-form">
                    <input type="hidden" name="amount" id="hiddenPriceTier">
                    <input type="hidden" name="duration" id="hiddenDuration"> 
                    <input type="hidden" name="device_id" value="8600081897">
                    <input type="hidden" name="mac_address" id="hidden_mac_field" value="$mac">
             
                    <div class="popup-input-wrapper">
                        <label for="phone-number">Ingiza nambari ya simu:</label>
                        <!-- FIXED ATTRIBUTE: Added the missing name="customer_phone" rule to feed variables to database table loops -->
                        <input name="customer_phone" id="phone-number" type="tel" placeholder="0713123974" autocomplete="off" oninput="detectMobileProvider()" required />
                    </div>

                    <button type="button" id="submit-payment-btn" class="btn-pay" onclick="dispatchToRailway(event)">Pay</button>
                </form>

                <!-- SILENT LOADING LAYER MARQUEE ANIMATION LOOP -->
                <div id="active-spinner-layer" style="display: none; align-items: center; justify-content: center; margin-top: 15px; font-size: 13px; font-weight: bold; color: #3498db;">
                    <span style="display: inline-block; margin-right: 6px;">🔄</span> Tafadhali subiri, tunapakia malipo...
                </div>
            </div>
        </div>

        <footer class="footer">
            <p style="margin: 0 0 10px 0; font-weight: bold;"> 
                © 2026 NIT Africa Solutions Limited. All Rights Reserved. <br>
                TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">®</sup> is a registered trademark of 
                <a href="https://nitafricasolutions-production-2f54.up.railway.app" style="color: #0066cc; text-decoration: none; font-weight: bold;"> NIT Africa Solutions Limited</a>
            </p>
            <p style="margin: 0 0 10px 0;">
                <a href="https://www.tanconnect.co.tz/privacy.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Privacy Policy</a> | 
                <a href="https://www.tanconnect.co.tz/terms.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Terms & Conditions</a> | 
                <a href="https://www.tanconnect.co.tz/refund.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Refund Policy</a>
            </p>
            <p style="margin: 0; font-size: 9px; color: #777777; line-height: 1.6;">
                <strong>Customer Support Desk:</strong><br>
                ✉ Email: <a href="mailto:support@tanconnect.co.tz" style="color: #0066cc; text-decoration: none;">support@tanconnect.co.tz</a> | 📞 Phone: <a href="tel:+255713123974" style="color: #0066cc; text-decoration: none;">+255 713 123 974</a>
            </p>
        </footer>

    </div> <!-- Closing container box cleanly -->

    <!-- ========================================================================= -->
    <!-- JAVASCRIPT CARRIER BRAND DETECTOR LOGIC & SECURE DISPATCH FILTER GATES    -->
    <!-- ========================================================================= -->
    <script type="text/javascript">
    // Live Operator Detection Engine
    function detectMobileProvider() {
        var phoneInput = document.getElementById("phone-number").value.trim();
        var cleanDigits = phoneInput.replace(/[^0-9]/g, '');
        var payBtn = document.getElementById("submit-payment-btn");
        if (!payBtn) return;
        
        // Always reset base styling elements to eliminate sticky properties
        payBtn.className = "btn-pay";
        payBtn.innerHTML = "Pay";
        
        // Standardize leading digit formatting layers (071... -> 25571...)
        var standardizedDigits = cleanDigits;
        if (standardizedDigits.startsWith('0')) {
            standardizedDigits = '255' + standardizedDigits.substring(1);
        }
        
        if (standardizedDigits.length < 5) return;
        
        // Extract the strict carrier identification index suffix keys (e.g., 74, 75, 71)
        var prefix = standardizedDigits.substring(3, 5);
        
        // Apply coloring rule layers automatically based on active prefix trace definitions
        if (['74', '75', '76', '14'].indexOf(prefix) !== -1) {
            payBtn.classList.add('mno-mpesa');
            payBtn.innerHTML = "PAY (M-Pesa)";
        } else if (['70', '71', '77', '65', '07', '67', '72'].indexOf(prefix) !== -1) {
            payBtn.classList.add('mno-tigo');
            payBtn.innerHTML = "PAY (TigoPesa)";
        } else if (['78', '79', '68', '69'].indexOf(prefix) !== -1) {
            payBtn.classList.add('mno-airtel');
            payBtn.innerHTML = "PAY (Airtel)";
        } else if (['62', '61'].indexOf(prefix) !== -1) {
            payBtn.classList.add('mno-halopesa');
            payBtn.innerHTML = "PAY (Halopesa)";
        } else {
            payBtn.innerHTML = "Pay";
        }
    }

    // Comprehensive submission length constraints and safety dictionary array filters
    function dispatchToRailway(event) {
        if (event) event.preventDefault(); 
        
        var phoneInput = document.getElementById("phone-number").value.trim();
        var cleanDigitsOnly = phoneInput.replace(/[^0-9]/g, ''); 

        // Validate text entry lengths strictly
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

        // Standardize telephone prefix structures
        var standardizedDigits = cleanDigitsOnly;
        if (standardizedDigits.startsWith('0')) {
            standardizedDigits = '255' + standardizedDigits.substring(1);
        }
        
        var carrierPrefix = standardizedDigits.substring(3, 5);
        
        // Define active mobile operator mapping networks configuration arrays
        var validVodacom  = ['74', '75', '76', '14'];
        var validTigo     = ['70', '71', '77', '65', '07', '67', '72'];
        var validAirtel   = ['78', '79', '68', '69'];
        var validHalotel  = ['62', '61'];
        
        var allValidPrefixes = validVodacom.concat(validTigo, validAirtel, validHalotel);

        // If the number prefix doesn't match any network bucket row, freeze execution layer instantly
        if (allValidPrefixes.indexOf(carrierPrefix) === -1) {
            alert("Mtandao hautambuliki! Tafadhali ingiza nambari ya Vodacom, Tigo, Airtel, au Halotel.");
            return;
        }

        // Display animated loading layer spinner and submit the container node cleanly
        var spinner = document.getElementById("active-spinner-layer");
        if (spinner) spinner.style.display = "flex";
        
        document.getElementById("payment-form").submit();
    }
    </script>
</body>
</html>
