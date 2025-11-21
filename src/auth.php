<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/env.php';

function start_session_once(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user(): ?array
{
    start_session_once();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function login(string $username, string $password): bool
{
    $conn = db_connect();
    $stmt = $conn->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        start_session_once();
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];
        return true;
    }

    return false;
}

function logout(): void
{
    start_session_once();
    $_SESSION = [];
    session_destroy();
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /login.php');
        exit();
    }
}

function csrf_token(): string
{
    start_session_once();
    if (empty($_SESSION['csrf_token'])) {
        $secret = env('APP_SECRET', bin2hex(random_bytes(16)));
        $_SESSION['csrf_token'] = hash('sha256', $secret . session_id());
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    start_session_once();
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
