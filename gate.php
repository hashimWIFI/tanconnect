<?php
// =========================================================================
// 🎛️ TANCONNECT INTELLIGENT TRAFFIC GATEWAY ROUTER (`gate.php`)
// =========================================================================
if (\(_SERVER['REQUEST_METHOD'] === 'POST') {\)phone = isset(\(_POST['customer_phone']) ? trim(\)_POST['customer_phone']) : '';
    
    // Normalize phone formatting rules safely
    if (substr(\$phone, 0, 1) === '0') {
        phone = '255' . substr(phone, 1);
    }
    
    // Extract the Tanzanian network routing prefix code
    routingPrefix = substr(phone, 3, 2); 
    
    // 🛡️ TRAFFIC MATRIX: Isolate Vodacom prefixes (74, 75, 76, 14)
    if (in_array(\$routingPrefix, ['74', '75', '76', '14'])) {
        // Route Vodacom numbers directly to the upcoming dedicated wallet framework
        include('Vlogin.php');
    } else {
        // Route Tigo, Airtel, and Halotel cleanly to your working AzamPay engine
        include('Alogin.php');
    }
    exit();
}
