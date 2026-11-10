<?php
declare(strict_types=1);

// Shared helpers for Tasks 3 and 4. Uses the existing UniStay auth.php and db() function.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

function module_require(string $role): int
{
    requireRole($role);
    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        http_response_code(403);
        exit('Invalid session. Please sign in again.');
    }
    return (int)$id;
}

function module_escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function module_csrf_token(): string
{
    if (empty($_SESSION['module_csrf'])) {
        $_SESSION['module_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['module_csrf'];
}

function module_check_csrf(): void
{
    if (!hash_equals(module_csrf_token(), (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(419);
        exit('Invalid or expired request. Reload the page and try again.');
    }
}

function module_redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function module_flash(string $message, bool $error = false): void
{
    $_SESSION['module_flash'] = ['message' => $message, 'error' => $error];
}

function module_show_flash(): void
{
    $flash = $_SESSION['module_flash'] ?? null;
    unset($_SESSION['module_flash']);
    if (!is_array($flash)) return;
    $class = !empty($flash['error']) ? 'error' : 'success';
    echo '<div class="notice ' . $class . '">' . module_escape((string)$flash['message']) . '</div>';
}

function module_fail(Throwable $e, string $redirectTo): never
{
    error_log('UniStay module failure: ' . get_class($e) . ': ' . $e->getMessage());
    module_flash('The operation could not be completed. Check the server log if it continues.', true);
    module_redirect($redirectTo);
}

function module_header(string $title, string $role): void
{
    $nav = $role === 'owner'
        ? ['Dashboard' => '/owner/dashboard.php', 'My Listings' => '/owner/listings.php', 'Inquiries' => '/owner/inquiries.php', 'Tenants' => '/owner/tenants.php', 'Rent Ledger' => '/owner/rent.php', 'My Profile' => '/profile.php']
        : ['Dashboard' => '/student/dashboard.php', 'Browse' => '/student/listings.php', 'My Inquiries' => '/student/inquiries.php', 'My Tenancy' => '/student/tenancy.php', 'Payments' => '/student/payments.php', 'My Profile' => '/profile.php'];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . module_escape($title) . ' | UniStay</title>';
    echo '<link rel="stylesheet" href="/assets/css/unistay-modules.css">';
    echo '<link rel="stylesheet" href="/assets/css/unistay-rent.css">';
    echo '<link rel="stylesheet" href="/assets/css/unistay-dashboard.css">';
    echo '<link rel="stylesheet" href="/assets/css/unistay-profile.css">';
    echo '<link rel="stylesheet" href="/assets/css/unistay-logout.css"></head><body>';
    echo '<header class="topbar"><div class="brand">UniStay <span>' . module_escape(ucfirst($role)) . '</span></div><nav>';
    foreach ($nav as $label => $url) {
         $current = (string)($_SERVER['SCRIPT_NAME'] ?? '') === $url;
        echo '<a href="' . module_escape($url) . '"' . ($current ? ' aria-current="page"' : '') . '>' . module_escape($label) . '</a>';
    }
    // One shared sign-out control for Owner and Student navigation.
    echo '<form class="unistay-logout-form" action="/logout.php" method="post">';
    echo '<input type="hidden" name="csrf_token" value="' . module_escape(module_csrf_token()) . '">';
    echo '<button type="submit" class="unistay-logout-button">Log out</button></form>';
    echo '</nav></header><main class="container"><h1>' . module_escape($title) . '</h1>';
    module_show_flash();
}

function module_footer(): void
{
    echo '</main><footer class="footer">UniStay · Shared TiDB development database</footer></body></html>';
}

/** Use the application's local calendar for rent and tenancy dates, regardless of server timezone. */
function module_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
}

function module_current_month(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m');
}

function module_positive_int(mixed $value, string $name): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($number === false) throw new InvalidArgumentException("Invalid {$name}.");
    return (int)$number;
}

function module_money(mixed $value): string
{
    $input = trim((string)$value);
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $input)) {
        throw new InvalidArgumentException('Enter a valid rent amount (up to 2 decimal places).');
    }
    if ((float)$input <= 0) throw new InvalidArgumentException('Rent must be greater than zero.');
    return number_format((float)$input, 2, '.', '');
}
