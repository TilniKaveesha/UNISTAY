<?php
declare(strict_types=1);

/** Single source of truth for routing signed-in UniStay users. */
function unistayDashboardForRole(string $role): ?string
{
    return match ($role) {
        'student' => '/student/dashboard.php',
        'owner'   => '/owner/dashboard.php',
        'admin'   => '/public/admin/dashboard.php',
        default   => null,
    };
}

/** Prevent login/registration pages being accessed after authentication. */
function unistayRequireGuest(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Prevent browsers storing authenticated/guest auth pages in the back cache.
    header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (!empty($_SESSION['user_id'])) {
        $destination = unistayDashboardForRole((string) ($_SESSION['role'] ?? ''));
        if ($destination === null) {
            http_response_code(403);
            exit('Your UniStay account role is not supported.');
        }
        header('Location: ' . $destination, true, 303);
        exit;
    }
}
