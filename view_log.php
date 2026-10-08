<?php
header("Content-Type: text/plain");
$logPath = '/tmp/azampay_gateway.log';

if (file_exists($logPath)) {
    echo file_get_contents($logPath);
} else {
    echo "Log file does not exist yet. No inbound traffic has been recorded.";
}
