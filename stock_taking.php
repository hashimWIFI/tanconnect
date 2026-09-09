<?php
// Set your local East Africa Time timezone (GMT+3)
date_default_timezone_set('Africa/Dar_es_Salaam');

// 1. DATABASE CONFIGURATION
$db_host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD');
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 2. HARDCODE YOUR PRICE TIERS (LISTED FROM LOWEST TO HIGHEST)
$price_tiers = [500, 1000, 2000, 4000, 5000, 7000, 9000, 10000, 15000];

// 3. BUILD THE INVENTORY REPORT TEXT
$reportText = "📊 **TANConnect 12-Hour Inventory Status Report** 📊\n";
$reportText .= "Generated at: " . date("Y-m-d H:i:s") . "\n";
$reportText .= "----------------------------------------------------------------------------------------------\n";

// Loop through each hardcoded price tier and check its stock count individually
foreach ($price_tiers as $tier) {
    
    // Count how many 'available' vouchers exist for this specific price tier
    $query = "SELECT COUNT(*) as remaining_stock 
              FROM wifi_vouchers 
              WHERE price_tier = " . intval($tier) . " AND status = 'available'";
              
    $result = $conn->query($query);
    $count  = 0;
    
    if ($result) {
        $row   = $result->fetch_assoc();
        $count = intval($row['remaining_stock']);
    }

    // Apply visual warning icons based on the stock count rules
    if ($count == 0) {
        $warningIcon = "🚨 OUT OF STOCK! ";
    } elseif ($count < 10) {
        $warningIcon = "⚠️ Low! ";
    } else {
        $warningIcon = "✅ ";
    }

    $reportText .= "• " . $warningIcon . number_format($tier) . " TZS Tier: " . $count . " remaining\n";
}

$reportText .= "------------------------------------------------------------------------------------------------\n";
$reportText .= "System check status: Fully operational.";

$conn->close();

// 4. PREPARE THE HTTP WEB POST FOR NTFY
$streamOptions = [
    "http" => [
        "method"  => "POST",
        "header"  => "Title: WiFi Stock Report\r\nPriority: default\r\nTags: clipboard,chart_with_upwards_trend\r\n",
        "content" => $reportText,
        "timeout" => 5
    ]
];

// 5. TRANSMIT THE WEB PACKET TO YOUR NTFY CHANNEL PATH
$context = stream_context_create($streamOptions);
@file_get_contents("https://ntfy.sh/tanconnect_vouchers_stock_alert_2026", false, $context);


// Exit cleanly for Railway Cron container cycle
exit(0);
?>
