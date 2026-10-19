<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Session.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isAuthenticated(): bool
{
    return isset($_SESSION['user']['user_id']);
}

function requireAuthentication(): void
{
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }

    if (!recordSessionActivity()) {
        unset($_SESSION['user']);
        http_response_code(503);
        exit('Unable to verify your session. Please try again.');
    }
}

function createCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken(mixed $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function authenticateUser(array $user): bool
{
    session_regenerate_id(true);

    $appSession = new AppSession();
    if (!$appSession->startForUser((int) $user['user_id'], session_id())) {
        return false;
    }

    $_SESSION['user'] = [
        'user_id' => (int) $user['user_id'],
        'username' => (string) $user['username'],
        'full_name' => (string) $user['full_name'],
        'role' => (string) $user['role'],
    ];
    unset($_SESSION['csrf_token']);
    createCsrfToken();
    return true;
}

function recordSessionActivity(): bool
{
    $appSession = new AppSession();
    return $appSession->touch((int) $_SESSION['user']['user_id'], session_id());
}

function endAuthSession(): bool
{
    if (!isAuthenticated()) {
        return true;
    }

    $appSession = new AppSession();
    return $appSession->endForUser((int) $_SESSION['user']['user_id'], session_id());
}
