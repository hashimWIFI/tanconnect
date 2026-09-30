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
        
        <p style="font-size: 14px; color: #34495e;">
            Umelipia kifurushi cha <b>Tsh <?php echo number_format(cleanAmount); ?></b> kwa namba ya M-Pesa: <b><?php echo htmlspecialchars(phone); ?></b>.
        </p>

        <!-- 📑 CHOOSE YOUR DIRECTION BELOW: Modify these lines to match your business rules -->
        <div class="instructions-box">
            <b>Tafadhali fuata hatua hizi kwenye simu yako:</b><br>
            1. Piga <b>*150*00#</b> kufungua menyu ya M-Pesa<br>
            2. Chagua 4 - <b>Lipa kwa M-Pesa</b><br>
            3. Chagua 4 - <b>Weka namba ya Kampuni</b><br>
            4. Namba ya kampuni ya TANConnect: <b>XXXXXX</b><br>
            5. Kiasi cha kuweka: <b>Tsh <?php echo number_format(\$cleanAmount); ?></b><br>
            6. Weka namba yako ya siri ya M-Pesa kuthibitisha.
        </div>

        <p style="font-size: 12px; color: #7f8c8d; font-style: italic;">
            Ukishatuma malipo, mfumo utatuma Vocha yako ya WiFi kwa njia ya SMS kwenye namba yako ndani ya dakika 1.
        </p>

        <a href="javascript:history.back()" class="btn-portal">
            RUDI NYUMA (BACK HOME)
        </a>
    </div>
</body>
</html>
