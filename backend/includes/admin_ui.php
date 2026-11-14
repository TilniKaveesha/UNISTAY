<?php
declare(strict_types=1);

/**
 * UniStay admin presentation + protection helpers.
 * Uses existing session authentication and TiDB; no React or Node build required.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

function ua_escape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ua_bootstrap(): PDO
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');

    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!$userId || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: /public/login.php', true, 302);
        exit;
    }

    // Verify the role/status against TiDB, not only the session. Suspend/role changes
    // should take effect without waiting for the next login.
    $pdo = db();
    $stmt = $pdo->prepare('SELECT full_name, email, role, status FROM `User` WHERE user_id = ? LIMIT 1');
    $stmt->execute([(int) $userId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || $admin['role'] !== 'admin' || $admin['status'] !== 'active') {
        http_response_code(403);
        exit('Administrator access is unavailable.');
    }

    // Preserve optional single-admin allowlist written by provision_admin.php.
    $allowlistPath = __DIR__ . '/../../config/admin.local.php';
    if (is_file($allowlistPath)) {
        $localConfig = require $allowlistPath;
        $allowedEmail = is_array($localConfig) ? strtolower(trim((string) ($localConfig['email'] ?? ''))) : '';
        if ($allowedEmail === '' || !hash_equals($allowedEmail, strtolower((string) $admin['email']))) {
            http_response_code(403);
            exit('Administrator access is unavailable on this installation.');
        }
    }

    $_SESSION['ua_admin_name'] = (string) ($admin['full_name'] ?? 'Administrator');
    return $pdo;
}

function ua_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['admin_csrf'];
}

function ua_check_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals(ua_token(), $posted)) {
        http_response_code(403);
        exit('Your session has expired. Refresh the page and try again.');
    }
}

function ua_flash(string $message, string $type = 'success'): void
{
    $_SESSION['ua_flash'] = ['message' => $message, 'type' => $type === 'error' ? 'error' : 'success'];
}

function ua_redirect(string $page): never
{
    // Fixed allowlist: never redirect to a user-supplied external URL.
    if (!in_array($page, ['dashboard.php', 'users.php', 'listings.php', 'universities.php'], true)) {
        $page = 'dashboard.php';
    }
    header('Location: /public/admin/' . $page, true, 303);
    exit;
}

function ua_flash_html(): void
{
    $flash = $_SESSION['ua_flash'] ?? null;
    unset($_SESSION['ua_flash']);
    if (is_array($flash)) {
        echo '<div class="ua-alert ua-alert-' . ua_escape($flash['type'] ?? 'success') . '" role="status">'
            . ua_icon(($flash['type'] ?? '') === 'error' ? 'alert' : 'check')
            . '<span>' . ua_escape($flash['message'] ?? '') . '</span></div>';
    }
}

function ua_icon(string $name, int $size = 20): string
{
    // Small, self-hosted SVG set (no external icon CDN).
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'house' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10Z"/><path d="M9 21v-8h6v8"/>',
        'university' => '<path d="m3 10 9-7 9 7M5 10v10M10 10v10M14 10v10M19 10v10M3 21h18M2 10h20"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'external' => '<path d="M14 3h7v7M10 14 21 3"/><path d="M21 13v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h7"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'filter' => '<path d="M4 4h16l-6 7v7l-4 2v-9L4 4Z"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'alert' => '<path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v4m0 4h.01"/>',
        'trend' => '<path d="m3 17 7-7 4 4 7-7M14 7h7v7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'moon' => '<path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"/>',
    ];
    $safeSize = max(12, min(32, $size));
    return '<svg width="' . $safeSize . '" height="' . $safeSize . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['dashboard']) . '</svg>';
}

function ua_badge(string $value): string
{
    $normalized = strtolower(trim($value));
    $tone = match ($normalized) {
        'active', 'available', 'approved', 'confirmed' => 'good',
        'pending', 'soon' => 'warn',
        'suspended', 'unavailable', 'rejected', 'withdrawn' => 'bad',
        'student' => 'blue',
        'owner' => 'purple',
        'admin' => 'neutral',
        default => 'neutral',
    };
    return '<span class="ua-badge ua-badge-' . $tone . '"><span class="ua-badge-dot"></span>' . ua_escape(ucwords(str_replace('_', ' ', $value))) . '</span>';
}

function ua_page_start(string $current, string $title, string $description): void
{
    $links = [
        ['dashboard', 'Dashboard', 'dashboard.php', 'dashboard'],
        ['users', 'Manage Users', 'users.php', 'users'],
        ['listings', 'Manage Listings', 'listings.php', 'house'],
        ['universities', 'Universities', 'universities.php', 'university'],
    ];
    $name = trim((string) ($_SESSION['ua_admin_name'] ?? 'Administrator'));
    $initials = strtoupper(function_exists('mb_substr')
        ? mb_substr($name !== '' ? $name : 'A', 0, 1)
        : substr($name !== '' ? $name : 'A', 0, 1));
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . ua_escape($title) . ' | UniStay Admin</title>';
    echo '<link rel="stylesheet" href="/src/styles/style.css">';
    echo '<link rel="stylesheet" href="/src/styles/unistay-admin.css">';
    echo '<script src="/src/assets/admin.js" defer></script>';
    echo '</head><body class="ua-body"><div class="ua-app">';
    echo '<div class="ua-scrim" id="ua-scrim" hidden></div><aside class="ua-sidebar" id="ua-sidebar" aria-label="Admin navigation">';
    echo '<a class="ua-brand" href="/public/admin/dashboard.php"><span class="ua-brand-mark">' . ua_icon('house', 23) . '</span>';
    echo '<span><strong>UniStay</strong><small>ADMIN CONSOLE</small></span></a>';
    echo '<div class="ua-nav-heading">WORKSPACE</div><nav class="ua-nav">';
    foreach ($links as [$key, $label, $href, $icon]) {
        echo '<a class="ua-nav-link' . ($current === $key ? ' is-active' : '') . '" href="/public/admin/' . $href . '"'
            . ($current === $key ? ' aria-current="page"' : '') . '>'
            . ua_icon($icon, 18) . '<span>' . ua_escape($label) . '</span>' . ($current === $key ? ua_icon('chevron', 16) : '') . '</a>';
    }
    echo '</nav><div class="ua-nav-bottom"><div class="ua-nav-heading">QUICK LINKS</div>';
    echo '<a class="ua-nav-link" href="/public/index.php">' . ua_icon('external', 18) . '<span>View public site</span></a>';
    echo '<form action="/logout.php" method="post" class="ua-logout-form">';
    // frontend/logout.php expects module_csrf from shared Owner/Student navigation.
    if (empty($_SESSION['module_csrf'])) {
        $_SESSION['module_csrf'] = bin2hex(random_bytes(32));
    }
    echo '<input type="hidden" name="csrf_token" value="' . ua_escape($_SESSION['module_csrf']) . '">';
    echo '<button class="ua-nav-link ua-nav-logout" type="submit">' . ua_icon('logout', 18) . '<span>Log out</span></button></form>';
    echo '<div class="ua-sidebar-foot"><span class="ua-avatar">' . ua_escape($initials) . '</span><span><strong>' . ua_escape($name) . '</strong><small>System administrator</small></span></div>';
    echo '</div></aside><div class="ua-workspace"><header class="ua-topbar">';
    echo '<div class="ua-topbar-left"><button type="button" class="ua-icon-btn ua-menu-toggle" id="ua-menu-toggle" aria-label="Open menu" aria-controls="ua-sidebar" aria-expanded="false">' . ua_icon('menu') . '</button>';
    echo '<span class="ua-topbar-caption">Workspace <span>/</span> ' . ua_escape($title) . '</span></div>';
    echo '<div class="ua-topbar-actions"><span class="ua-live"><span></span> Admin access</span>';
    echo '<button type="button" class="ua-icon-btn" id="ua-theme-toggle" title="Toggle dark mode" aria-label="Toggle dark mode">' . ua_icon('moon', 18) . '</button>';
    echo '<span class="ua-avatar ua-avatar-top">' . ua_escape($initials) . '</span></div></header>';
    echo '<main class="ua-main"><div class="ua-page-head"><div><p class="ua-overline">UNISTAY / MANAGEMENT</p><h1>' . ua_escape($title) . '</h1><p>' . ua_escape($description) . '</p></div></div>';
    ua_flash_html();
}

function ua_page_end(): void
{
    echo '</main><footer class="ua-footer">© ' . date('Y') . ' UniStay · Administration dashboard</footer></div></div></body></html>';
}

function ua_valid_positive_id(mixed $raw): int
{
    $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new InvalidArgumentException('Invalid record ID.');
    }
    return (int) $id;
}

function ua_page_number(mixed $raw): int
{
    $page = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $page === false || $page === null ? 1 : min((int) $page, 100000);
}

function ua_pager(int $page, int $total, int $pageSize, array $filters): void
{
    $pages = max(1, (int) ceil($total / $pageSize));
    $page = min($pages, max(1, $page));
    echo '<div class="ua-pagination"><span>Showing ' . ($total ? number_format(($page - 1) * $pageSize + 1) : '0') . '–' . number_format(min($page * $pageSize, $total)) . ' of ' . number_format($total) . ' records</span><div>';
    foreach (['Previous' => $page - 1, 'Next' => $page + 1] as $label => $target) {
        if ($target < 1 || $target > $pages) {
            echo '<span class="ua-page-button is-disabled">' . ua_escape($label) . '</span>';
        } else {
            $params = array_merge($filters, ['page' => $target]);
            echo '<a class="ua-page-button" href="?' . ua_escape(http_build_query($params, '', '&', PHP_QUERY_RFC3986)) . '">' . ua_escape($label) . '</a>';
        }
    }
    echo '</div></div>';
}
