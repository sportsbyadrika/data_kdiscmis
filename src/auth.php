<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/env.php';

const ROLE_SUPER_ADMIN = 'super_admin';
const ROLE_STATE_USER = 'state_user';
const ROLE_DISTRICT_USER = 'district_user';
const ROLE_LOCALBODY_USER = 'localbody_user';

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

function has_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

function is_admin(): bool
{
    return has_role(ROLE_SUPER_ADMIN);
}

function require_auth(array $roles = []): void
{
    $user = current_user();
    $isAuthenticated = $user !== null && (empty($roles) || in_array($user['role'], $roles, true));

    if (!$isAuthenticated) {
        header('Location: /login.php');
        exit();
    }
}

function login(string $mobile, string $password): bool
{
    $conn = db_connect();
    $stmt = $conn->prepare(
        'SELECT id, name, email, mobile, password_hash, role, team_id, team_role, district_id, status FROM users WHERE mobile = ? LIMIT 1'
    );
    $stmt->bind_param('s', $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
        start_session_once();
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'role' => $user['role'],
            'team_id' => $user['team_id'] !== null ? (int) $user['team_id'] : null,
            'team_role' => $user['team_role'],
            'district_id' => $user['district_id'] !== null ? (int) $user['district_id'] : null,
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
