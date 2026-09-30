<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Rest of your fake_trigger.php code starts below...

// ====================================================================
// DYNAMIC M-PESA SIMULATION BACKEND CALLBACK ('fake_trigger.php')
// ====================================================================

// 1. ESTABLISH YOUR MYSQL DATABASE CONNECTION

\$db_host      = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
\$db_port     = getenv('MYSQLPORT') ?: '3306';
\$db_user     = getenv('MYSQLUSER') ?: 'root';
\$db_password = getenv('MYSQLPASSWORD') ?: 'TxGqIUapIhgwhpKbqywjJXkiOWGmQVLJ';
\$db_name = getenv('MYSQLDATABASE') ?: 'railway';

\$conn = mysqli_connect(\(db_host,\)db_user, \(db_password,\)db_name);
if (!\$conn) {
    die("Database Connection Failure: " . mysqli_connect_error());
}

// 2. CATCH THE TARGET PHONE NUMBER DYNAMICALLY FROM THE URL
// Example: fake_trigger.php?phone=255753476850
\$targetPhone = isset(\(_GET['phone']) ? trim(\)_GET['phone']) : '';

if (empty(\$targetPhone)) {
    die("<h3>Simulation Error:</h3> Tafadhali weka namba ya simu kwenye URL.<br>
         Mfano: <code>https://tanconnect.co.tz</code>");
}

// 3. LOOKUP THE LATEST 'ASSIGNED' VOUCHER FOR THIS SPECIFIC NUMBER
\(searchQuery = mysqli_query(\)conn, "SELECT id, voucher_code FROM wifi_vouchers WHERE assigned_phone = '\$targetPhone' AND status = 'ASSIGNED' ORDER BY id DESC LIMIT 1");

if (mysqli_num_rows(\$searchQuery) === 0) {
    die("<h3>Simulation Info:</h3> Hakuna vocha iliyopo kwenye hali ya 'ASSIGNED' kwa namba <b>\$targetPhone</b> right now.<br>
         Hakikisha umeanzisha muamala kwenye tovuti kwanza kabla ya kufungua ukurasa huu.");
}

\(voucherRow = mysqli_fetch_assoc(\)searchQuery);
voucherId = voucherRow['id'];
voucherCode = voucherRow['voucher_code'];

// 4. EXECUTE THE SIMULATED PAYMENT SUCCESS TRANSITION
mysqli_begin_transaction(\$conn);

try {
    // Update voucher state dynamically into SUCCESS
    \$updateQuery = "UPDATE wifi_vouchers 
                    SET status = 'SUCCESS', 
                        `Muda wa Malipo (EAT Time)` = NOW() 
                    WHERE id = '\$voucherId'";
                    
    mysqli_query(conn, updateQuery);
    mysqli_commit(\$conn);
    
    echo "<h3>=== MOCK M-PESA PAYMENT SUCCESSFUL ===</h3>";
    echo "Target Number: <b>\$targetPhone</b><br>";
    echo "Voucher Found: <b>voucherCode</b> (ID: voucherId)<br>";
    echo "Status changed from <b>ASSIGNED</b> to <b>SUCCESS</b> inside your table successfully!<br><br>";
    echo "🚀 <b>Next Steps:</b> Your dashboard log screen will now show this voucher as completed.";

} catch (Exception \$e) {
    mysqli_rollback(\$conn);
    echo "Critical State Machine Failure: " . \$e->getMessage();
}
?>
