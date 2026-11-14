<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use the same token as the Owner/Student shared navigation.
    $storedToken = (string) ($_SESSION['module_csrf'] ?? '');
    $sentToken = (string) ($_POST['csrf_token'] ?? '');
    if ($storedToken === '' || !hash_equals($storedToken, $sentToken)) {
        http_response_code(403);
        exit('Invalid sign-out request. Refresh the dashboard and try again.');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
    header('Location: /public/login.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Method not allowed.');
}

// Support any existing legacy hyperlinks to /logout.php with a safe confirmation.
if (empty($_SESSION['user_id'])) {
    header('Location: /public/login.php', true, 303);
    exit;
}
if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(32));
}
$token = htmlspecialchars((string) $_SESSION['module_csrf'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign out | UniStay</title>
<style>
body{font:16px/1.5 system-ui,sans-serif;background:#f5f8fc;min-height:100vh;display:grid;place-items:center;margin:0;color:#18273a}
main{background:white;border:1px solid #dbe4ee;border-radius:12px;padding:2rem;max-width:400px;width:calc(100% - 2rem);box-shadow:0 8px 25px #0000000b}
button{border:0;border-radius:8px;background:#a53836;color:white;padding:10px 16px;cursor:pointer}
a{margin-left:12px;color:#1765c1}
</style>
</head><body><main><h1>Sign out of UniStay?</h1>
<p>You will need to sign in again to access your dashboard.</p>
<form method="post" action="/logout.php">
<input type="hidden" name="csrf_token" value="<?= $token ?>">
<button type="submit">Log out</button>
<a href="<?= ($_SESSION['role'] ?? '') === 'owner' ? '/owner/dashboard.php' : (($_SESSION['role'] ?? '') === 'admin' ? '/public/admin/dashboard.php' : '/student/dashboard.php') ?>">Cancel</a>
</form></main></body></html>
