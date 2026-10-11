<?php

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Store</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; display: flex; justify-content: center; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #333; text-align: center; }
        label { font-weight: bold; display: block; margin-top: 15px; color: #555; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        button { width: 100%; background-color: #007bff; color: white; padding: 12px; border: none; border-radius: 4px; font-size: 16px; margin-top: 20px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .alert { padding: 12px; margin-top: 15px; border-radius: 4px; text-align: center; font-weight: bold; background-color: #e2f0d9; color: #385723; border: 1px solid #c5e1b1; }
    </style>
</head>
<body>

<div class="container">
    <h2>FAKE SMS TRIGGER</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="https://www.tanconnect.co.tz/sms_webhook.php" method="post">
        <label for="mobile_number">Sender</label>
        <input type="tel" id="mobile_number" name="sender" placeholder="e.g. 255712345678" required>
 <label for="mobile_number">Receiver</label>
        <input type="tel" id="mobile_number" name="receiver" placeholder="e.g. 255712345678" required>
 <label for="mobile_number">Message</label>
        <input type="txt" id="text" name="message" placeholder="ingiza ujumbe hapa" required>


        </select>

        <button type="submit">submit sms</button>
    </form>
</div>

</body>
</html>
?>
