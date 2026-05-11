<?php

declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function login_user(array $user): void
{
    start_app_session();
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['user_id'],
        'username' => (string) $user['username'],
        'role' => (string) $user['role'],
    ];
}

function logout_user(): void
{
    start_app_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function current_user(): ?array
{
    start_app_session();

    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string|array $roles): bool
{
    $user = current_user();

    if ($user === null) {
        return false;
    }

    $allowedRoles = is_array($roles) ? $roles : [$roles];

    return in_array($user['role'], $allowedRoles, true);
}

function require_login(string $loginPath = 'login.php'): void
{
    if (is_logged_in()) {
        return;
    }

    header('Location: ' . $loginPath);
    exit;
}

function require_role(string|array $roles, string $loginPath = 'login.php', string $fallbackPath = 'index.php'): void
{
    require_login($loginPath);

    if (has_role($roles)) {
        return;
    }

    header('Location: ' . $fallbackPath);
    exit;
}

function redirect_after_login(string $role): void
{
    if ($role === 'admin') {
        header('Location: admin/index.php');
        exit;
    }

    if ($role === 'creator') {
        header('Location: creator/index.php');
        exit;
    }

    header('Location: index.php');
    exit;
}
