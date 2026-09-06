<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Refund and Cancellation Policy</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 90vh; }
        .receipt-card { background: white; max-width: 450px; width: 100%; margin: 0 auto; padding: 40px 30px 30px 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; position: relative; }
        .sub-title { color: #e74c3c; font-size: 17px; font-weight: bold; margin-top: 10px; text-align: left; }
        .btn-home { background: #3498db; color: white; border: none; padding: 13px; font-size: 13px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 2px; width: 100%; box-sizing: border-box; font-weight: bold; }
    </style>
</head>
<body>

<div class="receipt-card">
  <span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>
  <img src="logo.png" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">
  <div class="sub-title"> Refund & Cancellation Policy; </div> <p style="font-size: 13px; text-align: justify;">
 All micro-payments done to purchase local Wi-Fi digital voucher tokens are completed following receipt of a valid "SUCCESS" webhook payload validation check from AzamPay servers. If an operational infrastructure breakdown happens where customer money is withdrawn but an SMS with voucher token is delayed, you can contact support team to intervene. Support team will cross-reference your validation identifier and issue your voucher token manually.</p>

  <a href="/" class="btn-home" style="background: darkblue; width: 100%; box-sizing: border-box; text-decoration: none;">← RUDI NYUMA (BACK HOME)</a>
  <footer style="margin-top: 2px; padding: 10px 10px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; border-radius: 8px; font-size: 10px; color: #555555; background-color: #fafafa;">
  <p><b> © 2026 NIT Africa Solutions Ltd.</b> All Rights Reserved.<b><br>TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 6px; font-weight: normal; vertical-align: super; line-height: 0;">&reg;</sup></b> is a registered trademark of<br><a href= https://nitafricasolutions-production-2f54.up.railway.app style="color: #0066cc; text-decoration: none; font-weight: 500;"> NIT Africa Solutions Ltd</a></p>
</div>
 
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
