<?php
require_once '../config/config.php';


// Hapus semua data session
$_SESSION = [];

// Hapus cookie session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header('Location: ' . BASE_URL . '/auth/login.php');
exit();
?>