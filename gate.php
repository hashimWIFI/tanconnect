<?php
// =========================================================================
// 🎛️ TANCONNECT INTELLIGENT TRAFFIC GATEWAY ROUTER (`gate.php`)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone  = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
    $amount = str_replace(',', '', $amount);

    // Normalize phone formatting rules safely
    if (substr($phone, 0, 1) === '0') {
        $phone = '255' . substr($phone, 1);
    }
    
    // Extract the Tanzanian network routing prefix code
    $routingPrefix = substr($phone, 3, 2); 
    
    // 🛡️ TRAFFIC MATRIX: Isolate Vodacom prefixes (74, 75, 76, 14)
    if (in_array($routingPrefix, ['74', '75', '76', '14'])) {
        // Chagua Vlogin.php kwa namba za Vodacom
        include('vlogin.php');
    } else {
        // Chagua Alogin.php kwa namba za Tigo, Airtel, na Halotel
        include('public/alogin.php');
    }
    exit();
}
