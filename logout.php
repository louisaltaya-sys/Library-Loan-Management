<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isValidCsrfToken(postString('csrf_token'))) {
    http_response_code(400);
    exit('Invalid logout request.');
}

endAuthSession();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => 'Lax',
    ]);
}

session_destroy();
header('Location: login.php');
exit;
