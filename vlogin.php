<?php
// =========================================================================
// 💳 TANCONNECT DEDICATED VODACOM (M-PESA) CHANNEL ENGINE (`Vlogin.php`)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize baseline flags safely
$httpStatusCode = 200;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $phone, $amount, and $capturedMac are already inherited perfectly from gate.php!
$cleanAmount = intval($amount);
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TANConnect - M-PESA MALIPO</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; text-align: center; padding: 40px 15px; color: #2c3e50; margin: 0; }
        .receipt-card { background: white; max-width: 450px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); box-sizing: border-box; position: relative; }
        .voda-red { color: #e60000; font-weight: bold; font-size: 18px; margin-bottom: 15px; }
        .instructions-box { background-color: #fff5f5; border: 2px dashed #e60000; padding: 15px; border-radius: 8px; text-align: left; font-size: 14px; line-height: 1.6; margin: 15px 0; }
        .btn-portal { display: block !important; width: 100% !important; box-sizing: border-box !important; background: #e60000; color: white !important; border: none !important; padding: 14px 20px !important; font-size: 15px !important; border-radius: 8px !important; cursor: pointer !important; text-decoration: none !important; margin-top: 15px !important; font-weight: bold !important; text-align: center !important; }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div style="font-size: 24px; font-family: Broadway, Helvetica, sans-serif; color: #1e3c72; font-weight: bold; margin-bottom: 2px;">TANConnect<sup style="font-size: 10px;">®</sup></div>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
        
        <div class="voda-red">🔴 Mwongozo wa Malipo ya M-Pesa</div>
        
                <p style="font-size: 15px; color: #e74c3c; font-weight: bold;">
            ⚠️ Huduma ya M-Pesa Inaboreshwa / M-Pesa Service is Under Maintenance
        </p>

        <div class="instructions-box" style="border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; text-align: left;">
            <b>Ndugu Mteja (Dear Customer):</b><br><br>
            Mifumo yetu ya malipo ya Vodacom (M-Pesa) kwa sasa inapitia matengenezo ya kiufundi ili kukuletea huduma ya haraka zaidi.<br><br>
            Tafadhali <b>rudi nyuma</b> na utumie namba ya <b>Tigo, Airtel, au Halotel</b> ili kukamilisha malipo yako na kupokea vocha yako ya WiFi papo hapo kwa njia ya SMS.<br><br>
            ---<br><br>
            Our Vodacom (M-Pesa) channels are currently undergoing technical upgrades to serve you better. <br><br>
            Please <b>go back</b> and checkout using a <b>Tigo, Airtel, or Halotel</b> number to receive your WiFi voucher instantly via SMS.
        </div>

        <div style="margin-top: 25px;">
            <a href="javascript:history.back()" class="btn-portal" style="background-color: #34495e; padding: 12px 25px; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">
                ⬅️ RUDI NYUMA (GO BACK)
            </a>
        </div>
    </div>
</body>
</html>
