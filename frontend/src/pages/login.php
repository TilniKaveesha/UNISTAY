<?php
declare(strict_types=1);

// Prevent the internal template from being visited as a second login URL.
if (!defined('UNISTAY_LOGIN_ENTRY')) {
    http_response_code(404);
    exit('Page not found.');
}

require_once __DIR__ . '/../../../backend/includes/guest_only.php';
unistayRequireGuest();

$pageTitle = 'Login | UniStay';
$errorMessage = '';
$emailValue = '';
$dashboardRoutes = [
    'student' => '/student/dashboard.php',
    'owner'   => '/owner/dashboard.php',
    'admin'   => '/public/admin/dashboard.php',
];

// Share one anti-CSRF token between password login and the Google JSON endpoint.
// The Google endpoint uses the existing PHP session to verify this token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$_SESSION['unistay_login_csrf'] = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['unistay_login_csrf'], $postedToken)) {
        $errorMessage = 'The login form has expired. Please refresh and try again.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errorMessage = 'Please enter a valid email and password.';
    } else {
        try {
            // The team's database connector must expose PDO as $pdo.
            require_once __DIR__ . '/../../../config/db_connect.php';
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new RuntimeException('Database connection is unavailable.');
            }

            // Match the real TiDB table name: `User` (not `users`).
            $stmt = $pdo->prepare(
                'SELECT user_id, full_name, password_hash, role, status
                 FROM `User` WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$emailValue]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $storedHash = is_array($user) ? ($user['password_hash'] ?? null) : null;

            if (!is_string($storedHash) || $storedHash === '' || !password_verify($password, $storedHash)) {
                $errorMessage = 'Invalid email or password.';
            } elseif (($user['status'] ?? null) !== 'active') {
                $errorMessage = 'Your account is unavailable. Please contact support.';
            } elseif (!array_key_exists((string) ($user['role'] ?? ''), $dashboardRoutes)) {
                $errorMessage = 'Your account role is unsupported.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['role'] = (string) $user['role'];
                $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
                unset($_SESSION['unistay_login_csrf']);

                header('Location: ' . $dashboardRoutes[$_SESSION['role']], true, 303);
                exit;
            }
        } catch (Throwable $e) {
            error_log('UniStay password login error: ' . get_class($e) . ' [code: ' . $e->getCode() . ']');
            $errorMessage = 'Sign-in is temporarily unavailable. Please try again.';
        }
    }
}

// Only a public Google OAuth Client ID is exposed to the browser.
$googleClientId = '';
$googleConfigPath = __DIR__ . '/../../../config/google.local.php';
if (is_file($googleConfigPath)) {
    $googleConfig = require $googleConfigPath;
    if (is_array($googleConfig)) {
        $googleClientId = trim((string) (
            $googleConfig['client_id']
            ?? $googleConfig['google_client_id']
            ?? ($googleConfig['web']['client_id'] ?? '')
        ));
    }
}
$googleEnabled = preg_match('/^[A-Za-z0-9._-]+\.apps\.googleusercontent\.com$/', $googleClientId) === 1;

function loginEscape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= loginEscape($pageTitle) ?></title>
    <!-- Preserve the original team's public-login visual design. -->
    <link rel="stylesheet" href="/src/styles/style.css">
    <link rel="stylesheet" href="/src/styles/unistay-google-login.css">
    <?php if ($googleEnabled): ?>
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>
<body>
<?php include __DIR__ . '/../components/header.php'; ?>

<section class="section section-light auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-heading">
                <h2>WELCOME BACK</h2>
                <p>Login to your UniStay account to continue.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="inquiry-message inquiry-error" role="alert">
                    <?= loginEscape($errorMessage) ?>
                </div>
            <?php elseif (isset($_GET['google'])): ?>
                <div class="inquiry-message inquiry-error" role="alert">
                    Google sign-in could not complete. Please try again.
                </div>
            <?php endif; ?>
            <div class="inquiry-message inquiry-error" id="google-login-error" role="alert" hidden></div>

            <form action="/public/login.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= loginEscape((string) $_SESSION['unistay_login_csrf']) ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="Enter your email" value="<?= loginEscape($emailValue) ?>"
                           autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Login</button>
            </form>

            <!-- Google sign-in is now embedded in this SAME UI, no extra login page. -->
            <div class="unistay-google-area">
                <div class="unistay-login-divider"><span>or</span></div>
                <?php if ($googleEnabled): ?>
                    <div class="form-group">
                        <label for="google-role">Joining as</label>
                        <select class="form-control" id="google-role">
                            <option value="student">Student</option>
                            <option value="owner">Boarding owner</option>
                        </select>
                        <p class="unistay-google-hint">Only used when creating a new account. Your existing role cannot be changed here.</p>
                    </div>
                    <div id="google-signin-button" class="unistay-google-button"></div>
                <?php else: ?>
                    <p class="unistay-google-hint">Google sign-in is not configured. Add your Web Client ID to <code>config/google.local.php</code>.</p>
                <?php endif; ?>
            </div>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/public/register.php">Create an account</a></p>
                <p><a href="/public/index.php">Back to home</a></p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Show' : 'Hide';
}

(() => {
    'use strict';
    const clientId = <?= json_encode($googleEnabled ? $googleClientId : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (!clientId) return;

    // Rendered by PHP from the same session used by /api/google-login.php.
    const csrfToken = <?= json_encode((string) $_SESSION['csrf_token'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const errorBox = document.getElementById('google-login-error');
    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.hidden = false;
    };

    window.addEventListener('load', () => {
        if (!window.google?.accounts?.id) {
            showError('Google sign-in is unavailable. Please refresh the page.');
            return;
        }
        google.accounts.id.initialize({
            client_id: clientId,
            auto_select: false,
            callback: async (response) => {
                if (!response?.credential) {
                    showError('Google did not return a sign-in credential.');
                    return;
                }
                errorBox.hidden = true;
                const role = document.getElementById('google-role').value;
                try {
                    // The existing backend verifies Google signature, aud, iss, expiry,
                    // stores/looks up the user in TiDB and sets the PHP session.
                    const result = await fetch('/api/google-login.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({credential: response.credential, role, csrf_token: csrfToken})
                    });
                    const responseText = await result.text();
                    let data = null;
                    try { data = JSON.parse(responseText); } catch (_) {}
                    if (!result.ok || data?.success === false || data?.error) {
                        showError(data?.message || data?.error || 'Unable to sign in with Google.');
                        return;
                    }
                    // Role routing is decided by PHP session, never by client selection.
                    window.location.replace('/public/login.php?google=1');
                } catch (_) {
                    showError('Unable to reach the UniStay server. Please try again.');
                }
            }
        });
        google.accounts.id.renderButton(document.getElementById('google-signin-button'), {
            type: 'standard', theme: 'outline', size: 'large',
            text: 'continue_with', shape: 'rectangular', width: 300
        });
    }, {once: true});
})();
</script>
<script>
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
</script>
</body>
</html>
