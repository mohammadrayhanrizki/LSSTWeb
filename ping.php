<?php
// Endpoint ringan untuk AJAX ping
require 'init.php';

if (isset($_SESSION['user_id'])) {
    echo "OK";
} else {
    echo "GUEST";
}
?>
