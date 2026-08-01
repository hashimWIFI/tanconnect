<?php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // Serve the requested resource as-is.
} else {
    include_once __DIR__ . '/index.php';
}
?>
