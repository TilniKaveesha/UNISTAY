<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/includes/session.php';
require_once __DIR__ . '/../../backend/includes/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jsonResponse(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jsonResponse(405, ['error' => 'Method not allowed.']);
}

startAppSession();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    jsonResponse(400, ['error' => 'Invalid request.']);
}

$csrf = $input['csrf_token'] ?? '';
if (!is_string($csrf) || !hash_equals(appCsrfToken(), $csrf)) {
    jsonResponse(403, ['error' => 'Session expired. Refresh the login page.']);
}

$credential = $input['credential'] ?? '';
$requestedRole = $input['role'] ?? 'student';
if (!is_string($credential) || $credential === '' || strlen($credential) > 8192) {
    jsonResponse(400, ['error' => 'Missing Google sign-in token.']);
}
if (!in_array($requestedRole, ['student', 'owner'], true)) {
    jsonResponse(400, ['error' => 'Invalid account type.']);
}

$googleConfigFile = __DIR__ . '/../../config/google.local.php';
$autoloadFile = __DIR__ . '/../../vendor/autoload.php';
if (!is_file($googleConfigFile) || !is_file($autoloadFile)) {
    jsonResponse(503, ['error' => 'Google sign-in is not configured yet.']);
}

$googleConfig = require $googleConfigFile;
$clientId = $googleConfig['client_id'] ?? '';
if (!is_string($clientId) || $clientId === '' || str_starts_with($clientId, 'YOUR_')) {
    jsonResponse(503, ['error' => 'Google sign-in is not configured yet.']);
}

require_once $autoloadFile;

try {
    // Google's PHP client validates the signature, audience, issuer and expiry.
    $googleClient = new \Google\Client(['client_id' => $clientId]);
    $claims = $googleClient->verifyIdToken($credential);

    if (!is_array($claims)
        || empty($claims['sub'])
        || empty($claims['email'])
        || ($claims['email_verified'] ?? false) !== true
        || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
        jsonResponse(401, ['error' => 'Google could not verify this account.']);
    }

    $googleSub = (string) $claims['sub'];
    $email = (string) $claims['email'];
    $fullName = trim((string) ($claims['name'] ?? '')) ?: 'UniStay User';

    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, role, status FROM users WHERE google_sub = ? LIMIT 1'
    );
    $stmt->execute([$googleSub]);
    $user = $stmt->fetch();

    if (!$user) {
        // Do not silently link an existing password account by email alone.
        // Linking can be added later after authenticating that existing account.
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(409, [
                'error' => 'An account already uses this email. Sign in with your password for now.',
            ]);
        }

        // Google-only accounts have no usable password, even when the schema
        // requires password_hash to be NOT NULL.
        $unusablePasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, status, google_sub) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $email, $unusablePasswordHash, $requestedRole, 'active', $googleSub]);
        $user = [
            'user_id' => (int) $pdo->lastInsertId(),
            'full_name' => $fullName,
            'role' => $requestedRole,
            'status' => 'active',
        ];
    }

    if ($user['status'] !== 'active') {
        jsonResponse(403, ['error' => 'This account is not active. Please contact support.']);
    }

    appLoginUser($user);
    jsonResponse(200, ['redirect' => appDashboardForRole((string) $user['role'])]);
} catch (\Throwable $exception) {
    // Never return SQL errors, tokens or credentials to the browser.
    error_log('UniStay Google sign-in error: ' . get_class($exception));
    jsonResponse(500, ['error' => 'Sign-in is temporarily unavailable. Please try again.']);
}
