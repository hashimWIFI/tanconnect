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
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>

    <div class="container">
        <!-- SECTIONS 1 & 2: PORTAL HEADER & SCROLLING ANNOUNCEMENT -->
        <div class="portal-card">
            <img src="logo.png" alt="Water Point Logo" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
            <div class="subtitle">
                <marquee behavior="scroll" direction="left" scrollamount="4" style="color: black; border-bottom: 2px solid #e2e8f0; font-weight: bold; font-size: 13px; margin-bottom: 10px;font-family: 'Segoe UI', Arial, sans-serif;">
                    Ndugu mteja, karibu kwenye mtandao wa Wi-Fi wa TANConnect || Tunakuletea internet isiyo na ukomo wa kasi kuperuzi mtandaoni || Fuata maelekezo hapa chini kununua Voucher kupitia simu yako ya mkononi || Kwa ufafanuzi, malamiko au maelekezo zaidi, wasiliana nasi kwa nambari 0713 123 974 <br>
                </marquee> <br>
            </div>
            <div style="font-weight: bold; font-size: 13px; color: black; text-align: left;">Bonyeza kifurushi unachohitaji kununua:<br><br>
        <!-- SECTION 3: THE PACKAGE SELECTION GRID LOOP -->
        <div class="package-grid">
            <div class="package-card" onclick="selectPackage('500', '500 TZS || Masaa 12 kuperuzi || Unlimited DATA')">
                <div class="card-price">500<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>

                <div class="card-time">Saa 12</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('1000', '1,000 TZS || Siku 1 kuperuzi || Unlimited DATA')">
                <div class="card-price">1,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 1</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('2000', '2,000 TZS || Siku 2 kuperuzi || Unlimited DATA')">
                <div class="card-price">2,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 2</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('4000', '4,000 TZS || Siku 5 kuperuzi || Unlimited DATA')">
                <div class="card-price">4,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 5</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('5000', '5,000 TZS || Siku 7 kuperuzi || Unlimited DATA')">
                <div class="card-price">5,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 7</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
            
            <div class="package-card" onclick="selectPackage('7000', '7,000 TZS || Siku 10 kuperuzi || Unlimited DATA')">
                <div class="card-price">7,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 10</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
 <div class="package-card" onclick="selectPackage('9000', '9,000 TZS || Siku 13 kuperuzi || Unlimited DATA')">
                <div class="card-price">9,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 13</div>
                <div class="card-data">Unlimited DATA</div>
            </div>

            <div class="package-card" onclick="selectPackage('10000', '10,000 TZS || Siku 15 kuperuzi || Unlimited DATA')">
                <div class="card-price1">10,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
                <div class="card-time">Siku 15</div>
                <div class="card-data">Unlimited DATA</div>
            </div>
            
            <div class="package-card" onclick="selectPackage('20000', '20,000 TZS || Siku 30 kuperuzi || Unlimited DATA')">
                <div class="card-price1">20,000<span style= "font-size: 8px; font-weight: bold; color: #34495e;"> TZS</span></div>
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

<input class="btn-submit" name="customer_phone" id="phone-number" pattern="[0]{1}[6-7]{1}[0-9]{8}" type="tel" placeholder="0713123974" autocomplete="off" oninput="detectMobileProvider()" style="flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; color: black; text-align: center;" required/>

<button type="button" id="submit-payment-btn" class="btn-popup-pay" style="margin: 0; padding: 0 30px; background: #3498db; border-radius: 6px; font-size: 13px; color: white; font-weight: bold;" onclick="dispatchToRailway(event)">Pay</button>                </div></div>

        </form> </div></div>

        <!-- 🌫️ SECTION 5: NEW PREMIUM FULL-SCREEN GLASS LOADING OVERLAY -->
        <div id="active-spinner-layer" class="loader-overlay">
            <div class="chasing-spinner">
                <div></div><div></div><div></div><div></div><div></div>
                <div></div><div></div><div></div><div></div><div></div>
            </div>
            <div class="loading-text">TUNAWASILIANA NA MTANDAO WAKO <br> TAFADHALI SUBIRI ...</div>
        </div>

        <!-- START OF AZAMPAY MANDATORY COMPLIANCE FOOTER -->
<footer style="margin-top: 15px; padding: 15px 15px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 9px; color: #555555; background-color: #fafafa;">
    <p style="margin: 0 0 10px 0; font-weight: bold;"> © 2026 NIT Africa Solutions Limited. All Rights Reserved. <br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">&reg;</sup> is a registered trademark of<a href= https://nitafricasolutions-production-2f54.up.railway.app style="color: #0066cc; text-decoration: none; font-weight: bold;"> NIT Africa Solutions Limited</a></p>
    <p style="margin: 0 0 15px 0;">
        <a href="/privacy.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Privacy Policy</a> | 
        <a href="/terms.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Terms & Conditions</a> | 
        <a href="/refund.php" style="color: #0066cc; text-decoration: none; margin: 0 12px; font-weight: 500;">Refund Policy</a>
    </p>

    <p style="margin: 0; font-size: 9px; color: #777777; line-height: 1.6;">
        <strong>Customer Support Desk:</strong><br>
        📧<b> Email: <a href="mailto:support@tanconnect.co.tz" style="color: #0066cc; text-decoration: none;">support@tanconnect.co.tz</a> &nbsp;&nbsp;|&nbsp;&nbsp; 📞 Phone: <a href="tel:+255713123974" style="color: #0066cc; text-decoration: none;">+255 713 123 974</a>


    </p></b>
</footer>
<!-- END OF AZAMPAY MANDATORY COMPLIANCE FOOTER -->
    </div></div></div>


  

</body>
</html>