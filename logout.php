<?php
require_once __DIR__ . '/auth.php';

// Logout only via POST with a valid CSRF token (prevents forced logouts via links/images).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}
verify_csrf();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

header('Location: login.php?loggedout=1');
exit;
