<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - Uhaba wa Vifurushi</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 50px 20px; color: #2c3e50; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 90vh; }
        .receipt-card { background: white; max-width: 450px; width: 100%; margin: 0 auto; padding: 40px 30px 30px 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; position: relative; }
        .sub-title { color: #e74c3c; font-size: 20px; font-weight: bold; margin-top: 15px; text-align: left; }
         .btn-home { background: #3498db; color: white; border: none; padding: 14px; font-size: 16px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 15px; width: 100%; box-sizing: border-box; font-weight: bold; }

    </style>
</head>
<body>

<div class="receipt-card">
 
  <span class="close-btn" onclick="closeThisWindow()" style="position: absolute; top: 12px; right: 18px; font-size: 26px; cursor: pointer; color: #7f8c8d; font-weight: bold; z-index: 110;">&times;</span>

  <img src="logo.png" style="max-width: 250px; height: auto; object-fit: contain; margin-bottom: 1px;">

  <div class="sub-title">  Privacy Policy; </div> <p style="font-size: 13px; text-align: left;">
NIT Africa Solutions Limited operates the TANConnect<sup style="font-family: Arial, Helvetica, sans-serif; font-size: 8px; font-weight: normal; vertical-align: super; line-height: 0;">&reg;</sup> portal. We collect and store customer mobile phone digits exclusively to initialize your cellular mobile wallet checkout sequence through AzamPay and dispatch your corresponding internet connectivity token string via automated local SMS network routes. We protect this data using strict encryption layers and never distribute your credentials to third-party databases.</p>

<a href="/" class="btn-home" style="background: darkblue; width: 100%; box-sizing: border-box; text-decoration: none;">← RUDI NYUMA (BACK HOME)</a>
  
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
