<?php
// Set your local East Africa Time timezone (GMT+3)
date_default_timezone_set('Africa/Dar_es_Salaam');

// 1. DATABASE CONFIGURATION

$db_host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// 2. QUERY STOCK COUNT PER TIER
// Adjust table names ('vouchers') and column names ('price_tier', 'status') to match your schema
$query = "SELECT price_tier, COUNT(*) as remaining_stock 
          FROM vouchers 
          WHERE status = 'available' 
          GROUP BY price_tier";

$result = $conn->query($query);

// 3. BUILD THE INVENTORY REPORT TEXT
$reportText = "📊 **TANConnect 12-Hour Inventory Status Report** 📊\n";
$reportText .= "Generated at: " . date("Y-m-d H:i:s") . "\n";
$reportText .= "------------------------------------------\n";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tier  = number_format($row['price_tier']);
        $count = $row['remaining_stock'];
        
        // Add visual warnings if a stock tier drops too low
        $warningIcon = ($count < 10) ? "⚠️ Low! " : "✅ ";
        $reportText .= "• " . $warningIcon . $tier . " TZS Tier: " . $count . " remaining\n";
    }
} else {
    $reportText .= "❌ Warning: No unused vouchers found in the database!\n";
}

$reportText .= "------------------------------------------\n";
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

// CRITICAL FOR RAILWAY CRON: Exit cleanly when the task concludes
exit(0);
?>
