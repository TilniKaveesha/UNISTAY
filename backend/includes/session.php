<?php
declare(strict_types=1);

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function appCsrfToken(): string
{
    startAppSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function appDashboardForRole(string $role): string
{
    return match ($role) {
        'admin' => '/admin/dashboard.php',
        'owner' => '/owner/dashboard.php',
        default => '/student/dashboard.php',
    };
}

function appLoginUser(array $user): void
{
    startAppSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role'] = (string) $user['role'];
    $_SESSION['full_name'] = (string) $user['full_name'];
}
