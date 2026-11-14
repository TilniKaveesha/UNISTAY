<?php
declare(strict_types=1);

if (!defined('UNISTAY_REGISTER_ENTRY')) {
    http_response_code(404);
    exit('Page not found.');
}

require_once __DIR__ . '/../../../backend/includes/guest_only.php';
unistayRequireGuest();

$pageTitle = 'Register | UniStay';
$errorMessage = '';
$values = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'student',
];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$_SESSION['unistay_login_csrf'] = $_SESSION['csrf_token'];

function registerEscape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $default) {
        if (array_key_exists($key, $_POST) && is_string($_POST[$key])) {
            $values[$key] = trim($_POST[$key]);
        }
    }
    $values['email'] = strtolower($values['email']);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
        $errorMessage = 'This registration form expired. Refresh the page and try again.';
    } elseif (strlen($values['full_name']) < 2 || strlen($values['full_name']) > 255) {
        $errorMessage = 'Enter a name between 2 and 255 characters.';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 255) {
        $errorMessage = 'Enter a valid email address.';
    } elseif (!in_array($values['role'], ['student', 'owner'], true)) {
        $errorMessage = 'Select Student or Boarding owner.';
    } elseif ($values['phone'] !== '' && !preg_match('/^[+0-9() .-]{7,20}$/', $values['phone'])) {
        $errorMessage = 'Enter a valid phone number, or leave it blank.';
    } elseif (strlen($password) < 10 || strlen($password) > 128) {
        $errorMessage = 'Use a password between 10 and 128 characters.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $errorMessage = 'Passwords do not match.';
    } else {
        try {
            require_once __DIR__ . '/../../../config/db_connect.php';
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                throw new RuntimeException('Database connection is unavailable.');
            }

            // Database query disabled for commit
            // $existing = $pdo->prepare('SELECT user_id FROM `User` WHERE email = ? LIMIT 1');
            // Database query disabled for commit
            // $existing->execute([$values['email']]);
            if ($existing->fetchColumn() !== false) {
                $errorMessage = 'An account already uses this email. Please sign in instead.';
            } else {
                // Use the SAME TiDB sequence as Google sign-in; never MAX(id) + 1.
                // Verify its exact name with the database team before deployment.
                // Database query disabled for commit
                // $newUserId = (int) $pdo->query('SELECT NEXTVAL(unistay_user_seq)')->fetchColumn();
                if ($newUserId <= 0) {
                    throw new RuntimeException('Unable to generate user ID.');
                }

                // Database query disabled for commit
                // $insert = $pdo->prepare(
                    'INSERT INTO `User`
                        (user_id, full_name, email, password_hash, role, phone, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                // Database query disabled for commit
                // $insert->execute([
                    $newUserId,
                    $values['full_name'],
                    $values['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $values['role'],
                    $values['phone'] !== '' ? $values['phone'] : null,
                    'active',
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = $values['role'];
                $_SESSION['full_name'] = $values['full_name'];
                unset($_SESSION['csrf_token'], $_SESSION['unistay_login_csrf']);

                header('Location: ' . unistayDashboardForRole($values['role']), true, 303);
                exit;
            }
        } catch (PDOException $e) {
            // 23000 / 1062: unique-constraint race with another registration request.
            if ($e->getCode() === '23000' || ($e->errorInfo[1] ?? null) === 1062) {
                $errorMessage = 'This email is already registered. Please sign in.';
            } else {
                error_log('UniStay registration PDO error: ' . $e->getCode());
                $errorMessage = 'Registration is temporarily unavailable. Please try again.';
            }
        } catch (Throwable $e) {
            error_log('UniStay registration error: ' . get_class($e));
            $errorMessage = 'Registration is temporarily unavailable. Please try again.';
        }
    }
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= registerEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="/UNISTAY_UIUX/frontend/src/styles/style.css">
    <link rel="stylesheet" href="/UNISTAY_UIUX/frontend/src/styles/unistay-register.css">
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
                <h2>JOIN UNISTAY</h2>
                <p>Create an account to find a boarding place or manage your own listings.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
            <div class="inquiry-message inquiry-error" role="alert"><?= registerEscape($errorMessage) ?></div>
            <?php endif; ?>
            <div class="inquiry-message inquiry-error" id="google-register-error" role="alert" hidden></div>

            <form action="/public/register.php" method="POST" class="auth-form" id="register-form">
                <input type="hidden" name="csrf_token" value="<?= registerEscape((string) $_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="register-role">Register as</label>
                    <select id="register-role" name="role" class="form-control" required>
                        <option value="student" <?= $values['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="owner" <?= $values['role'] === 'owner' ? 'selected' : '' ?>>Boarding owner</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input class="form-control" type="text" name="full_name" id="full_name" maxlength="255" required
                        autocomplete="name" value="<?= registerEscape($values['full_name']) ?>" placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label for="register-email">Email address</label>
                    <input class="form-control" type="email" name="email" id="register-email" maxlength="255" required
                        autocomplete="email" value="<?= registerEscape($values['email']) ?>" placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone <span class="optional-label">(optional)</span></label>
                    <input class="form-control" type="tel" name="phone" id="register-phone" maxlength="20"
                        autocomplete="tel" value="<?= registerEscape($values['phone']) ?>" placeholder="07X XXX XXXX">
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <div class="password-wrapper">
                        <input class="form-control" type="password" name="password" id="register-password" minlength="10" maxlength="128" required
                            autocomplete="new-password" placeholder="At least 10 characters">
                        <button type="button" class="password-toggle" data-toggle="register-password">Show</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="register-confirm">Confirm password</label>
                    <div class="password-wrapper">
                        <input class="form-control" type="password" name="confirm_password" id="register-confirm" minlength="10" maxlength="128" required
                            autocomplete="new-password" placeholder="Re-enter your password">
                        <button type="button" class="password-toggle" data-toggle="register-confirm">Show</button>
                    </div>
                </div>
                <p class="unistay-register-hint">Use a unique password. Students keep their own accounts even after joining a boarding.</p>
                <button type="submit" class="btn btn-primary auth-submit">Create account</button>
            </form>

            <div class="unistay-google-area">
                <div class="unistay-login-divider"><span>or</span></div>
                <?php if ($googleEnabled): ?>
                    <p class="unistay-google-hint">Prefer not to set a password? Sign up with Google.</p>
                    <div id="google-register-button" class="unistay-google-button"></div>
                <?php else: ?>
                    <p class="unistay-google-hint">Google sign-in is not configured on this installation.</p>
                <?php endif; ?>
            </div>

            <div class="auth-footer">
                <p>Already have an account? <a href="/public/login.php">Log in</a></p>
                <p><a href="/public/index.php">Back to home</a></p>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../components/footer.php'; ?>
<script>
(() => {
    document.querySelectorAll('button[data-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.toggle);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Hide' : 'Show';
        });
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) window.location.reload();
    });

    const clientId = <?= json_encode($googleEnabled ? $googleClientId : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (!clientId) return;
    const csrfToken = <?= json_encode((string) $_SESSION['csrf_token'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const errorBox = document.getElementById('google-register-error');
    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.hidden = false;
    };
    window.addEventListener('load', () => {
        if (!window.google?.accounts?.id) {
            showError('Google sign-up is unavailable. Refresh the page to retry.');
            return;
        }
        google.accounts.id.initialize({
            client_id: clientId,
            auto_select: false,
            callback: async ({credential}) => {
                if (!credential) return showError('Google did not return a credential.');
                const role = document.getElementById('register-role').value;
                errorBox.hidden = true;
                try {
                    const result = await fetch('/api/google-login.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': csrfToken,
                        },
                        body: JSON.stringify({credential, role, csrf_token: csrfToken})
                    });
                    const text = await result.text();
                    let data = null;
                    try { data = JSON.parse(text); } catch (_) {}
                    if (!result.ok || data?.success === false || data?.error) {
                        showError(data?.message || data?.error || 'Unable to sign up with Google.');
                        return;
                    }
                    // The endpoint owns the authenticated session; the guest guard routes by stored role.
                    window.location.replace('/public/register.php');
                } catch (_) {
                    showError('Cannot reach the UniStay server. Please try again.');
                }
            }
        });
        google.accounts.id.renderButton(document.getElementById('google-register-button'), {
            type: 'standard', theme: 'outline', size: 'large', text: 'signup_with', shape: 'rectangular', width: 300
        });
    }, {once: true});
})();
</script>
</body>
</html>
