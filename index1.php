<?php
// Initialize the message variable safely
$message = "";

// If the form has been submitted, we can capture feedback from the redirect if desired
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $message = "🎉 Success! Test payload sent to sms_webhook.php.";
    } else {
        $message = "❌ Error: Failed to process test webhook.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Store - Fake SMS Trigger</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; display: flex; justify-content: center; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { color: #333; text-align: center; margin-bottom: 5px; }
        p.subtitle { text-align: center; color: #777; font-size: 14px; margin-top: 0; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-top: 15px; color: #555; }
        input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        button { width: 100%; background-color: #28a745; color: white; padding: 12px; border: none; border-radius: 4px; font-size: 16px; margin-top: 25px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #218838; }
        .alert { padding: 12px; margin-top: 15px; border-radius: 4px; text-align: center; font-weight: bold; background-color: #e2f0d9; color: #385723; border: 1px solid #c5e1b1; }
    </style>
</head>
<body>

<div class="container">
    <h2>FAKE SMS TRIGGER</h2>
    <p class="subtitle">Stage 1: Internal Script Testing</p>
    
    <?php if (!empty($message)): ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Submits data directly to your newly fixed webhook -->
    <form action="sms_webhook.php" method="post">
        <label for="sender">Sender</label>
        <input type="tel" id="sender" name="sender" placeholder="e.g. 255712345678" required>

        <label for="receiver">Receiver</label>
        <input type="tel" id="receiver" name="receiver" placeholder="e.g. 255753476850" required>

        <label for="message">Message</label>
        <input type="text" id="message" name="message" placeholder="ingiza ujumbe hapa" required>

        <button type="submit">SUBMIT FAKE SMS</button>
    </form>
</div>

</body>
</html>
