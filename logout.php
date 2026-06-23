<?php
require 'init.php';

session_unset();    // Bersihkan semua variabel session
session_destroy();  // Hancurkan session

// Hapus cookie session dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>