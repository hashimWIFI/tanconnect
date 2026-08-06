<?php
header('Content-Type: text/plain');

echo "=== TANCONNECT DEVELOPER UTILITY: GLOBAL VOUCHER STATUS RESET ===\n\n";

// 1. Establish database connection metrics automatically through cloud variables
$db_host = getenv('MYSQLHOST') ?: '127.0.0.1';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_port = getenv('MYSQLPORT') ?: '3306';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die("❌ DATABASE CONNECTION FAILED: " . $conn->connect_error);
}

echo "✓ Connected to MySQL Database successfully.\n";

// 2. Clear out transaction_id and reset status back to PENDING or AVAILABLE
// Change the target table name if you ever migrate from wifi_vouchers
echo "Initiating global wipe of testing data states...\n";

// This resets SUCCESS back to PENDING so you can rerun your fake_callback.php loops infinitely
$resetQuery = "UPDATE wifi_vouchers SET status = 'PENDING', purchased_at = NULL WHERE status = 'SUCCESS'";

if ($conn->query($resetQuery) === TRUE) {
    // Get the exact number of rows that were modified by this action
    $affected_rows = $conn->affected_rows;
    
    echo "========================================================================\n";
    echo "🚀 GLOBAL DATABASE RECOVERY REBOOT COMPLETE!\n";
    echo "========================================================================\n";
    echo "✓ Affected Rows Switched Back to Test Mode: " . $affected_rows . "\n";
    echo "✓ Status columns flipped cleanly from 'SUCCESS' back to 'PENDING'.\n";
    echo "✓ All temporary test purchase timestamps have been wiped out.\n\n";
    echo "Go look at your website storefront dashboard or fire up your fake_callback.php simulator tab—your entire environment has been refreshed back to step one seamlessly!";
} else {
    echo "❌ System execution error while parsing query string: " . $conn->error;
}

$conn->close();
?>
