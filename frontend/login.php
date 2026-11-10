<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/session.php';
startAppSession();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ' . appDashboardForRole((string) $_SESSION['role']));
    exit;
}

$error = '';
$csrfToken = appCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Session expired. Refresh the page and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        try {
            require_once __DIR__ . '/../config/db_connect.php';
            $stmt = $pdo->prepare(
                'SELECT user_id, full_name, password_hash, role, status FROM `User` WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !is_string($user['password_hash'])
                || !password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email or password.';
            } elseif ($user['status'] !== 'active') {
                $error = 'Your account is not active. Please contact support.';
            } else {
                appLoginUser($user);
                header('Location: ' . appDashboardForRole((string) $user['role']));
                exit;
            }
        } catch (\Throwable $exception) {
            error_log('UniStay password sign-in error: ' . get_class($exception));
            $error = 'Sign-in is temporarily unavailable. Please try again.';
        }
    }
}

$googleConfigFile = __DIR__ . '/../config/google.local.php';
$googleClientId = '';
if (is_file($googleConfigFile)) {
    $googleConfig = require $googleConfigFile;
    $googleClientId = (string) ($googleConfig['client_id'] ?? '');
    if (str_starts_with($googleClientId, 'YOUR_')) {
        $googleClientId = '';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - UniStay</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f2f5f8; min-height: 100vh; display: grid; place-items: center; margin: 0; padding: 20px; color: #1f2937; }
        .login-container { background: white; padding: 32px; border-radius: 16px; box-shadow: 0 15px 40px #0f172a12; width: 100%; max-width: 420px; }
        h1 { text-align: center; font-size: 28px; margin: 0 0 8px; color: #116765; }
        .intro { text-align: center; color: #64748b; font-size: 14px; margin: 0 0 25px; }
        label { display: block; font-size: 14px; margin: 0 0 6px; font-weight: 600; }
        input[type=email], input[type=password] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; margin-bottom: 16px; }
        .submit { width: 100%; padding: 12px; background: #116765; color: white; border: 0; border-radius: 8px; font-size: 15px; font-weight: 650; cursor: pointer; }
        .submit:hover { background: #0b5351; }
        .divider { text-align: center; margin: 24px 0; color: #94a3b8; font-size: 13px; }
        .roles { border: 1px solid #e2e8f0; border-radius: 8px; margin: 12px 0 20px; padding: 12px; }
        .roles legend { font-size: 13px; font-weight: 600; padding: 0 5px; }
        .roles label { display: inline-flex; align-items: center; gap: 5px; margin: 5px 14px 5px 0; font-weight: 400; }
        .roles p { margin: 6px 0 0; color: #64748b; font-size: 12px; }
        .google { display: flex; justify-content: center; min-height: 42px; }
        .notice { margin: 10px 0; padding: 12px; border-radius: 8px; font-size: 14px; background: #fff4e5; color: #884509; }
        .hint { text-align: center; color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
<main class="login-container">
    <h1>UniStay</h1>
    <p class="intro">Find a comfortable place near your university.</p>

    <?php if ($error !== ''): ?>
        <p class="notice" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p id="google-error" class="notice" role="alert" hidden></p>

    <?php if ($googleClientId !== ''): ?>
        <fieldset class="roles">
            <legend>I'm signing up as a</legend>
            <label><input type="radio" name="signup-role" value="student" checked> Student</label>
            <label><input type="radio" name="signup-role" value="owner"> Property owner</label>
            <p>Only used when creating a new Google account. Existing accounts keep their role.</p>
        </fieldset>
        <div id="google-button" class="google"></div>
    <?php else: ?>
        <p class="hint">Google sign-in will appear after its Client ID is configured.</p>
    <?php endif; ?>

    <div class="divider">or sign in with a password</div>
    <form method="post" action="/login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label for="email">Email address</label>
        <input type="email" name="email" id="email" autocomplete="email" required>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" autocomplete="current-password" required>
        <button class="submit" type="submit">Sign In</button>
    </form>
</main>

<?php if ($googleClientId !== ''): ?>
<script>
    function initGoogleSignIn() {
        google.accounts.id.initialize({
            client_id: <?= json_encode($googleClientId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            callback: async (googleResponse) => {
                const errorBox = document.getElementById('google-error');
                errorBox.hidden = true;
                try {
                    const response = await fetch('/api/google-login.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            credential: googleResponse.credential,
                            csrf_token: <?= json_encode($csrfToken) ?>,
                            role: document.querySelector('input[name="signup-role"]:checked').value
                        })
                    });
                    const data = await response.json();
                    if (!response.ok || !data.redirect) {
                        throw new Error(data.error || 'Sign-in failed. Please try again.');
                    }
                    window.location.assign(data.redirect);
                } catch (error) {
                    errorBox.textContent = error.message || 'Could not sign in.';
                    errorBox.hidden = false;
                }
            }
        });
        google.accounts.id.renderButton(document.getElementById('google-button'), {
            theme: 'outline', size: 'large', text: 'signin_with', width: 300
        });
    }
</script>
<script src="https://accounts.google.com/gsi/client" async defer onload="initGoogleSignIn()"></script>
<?php endif; ?>
</body>
</html>
