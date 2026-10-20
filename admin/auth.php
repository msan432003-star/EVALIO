<?php
// Admin guard: include this at the top of every admin page (after autoload, before any output).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// NOTE: your admin login page must set $_SESSION['admin_id'] after a successful login.
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php'); // change to your admin login page if named differently
    exit;
}

// Small helper to safely print values inside HTML
if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}