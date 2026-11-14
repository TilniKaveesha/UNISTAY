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

/**
 * Resume a selected boarding after login (Google or email/password) without
 * accepting an arbitrary external redirect URL. One-time and 30-minute limit.
 */
function unistayPostLoginDestination(string $role): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $pending = $_SESSION['unistay_pending_inquiry'] ?? null;
    unset($_SESSION['unistay_pending_inquiry']);

    if ($role === 'student' && is_array($pending)) {
        $id = filter_var($pending['listing_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $created = filter_var($pending['created_at'] ?? null, FILTER_VALIDATE_INT);
        if ($id !== false && $id !== null && $created !== false && $created !== null
            && $created <= time() && time() - $created <= 1800) {
            return '/public/inquiry.php?listing_id=' . (int) $id;
        }
    }

    return unistayDashboardForRole($role);
}

/** Prevent login/registration pages being accessed after authentication. */
function unistayRequireGuest(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (!empty($_SESSION['user_id'])) {
        $destination = unistayPostLoginDestination((string) ($_SESSION['role'] ?? ''));
        if ($destination === null) {
            http_response_code(403);
            exit('Your UniStay account role is not supported.');
        }
        header('Location: ' . $destination, true, 303);
        exit;
    }
}
