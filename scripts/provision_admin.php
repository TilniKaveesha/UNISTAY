<?php
/**
 * One-time / repeatable CLI-only provisioner for a single UniStay admin.
 * Password is never written to Git, PHP source, or the admin config file.
 * Use environment variables UNISTAY_ADMIN_EMAIL and UNISTAY_ADMIN_PASSWORD.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

require_once __DIR__ . '/../backend/includes/database.php';

$email = strtolower(trim((string) getenv('UNISTAY_ADMIN_EMAIL')));
$password = (string) getenv('UNISTAY_ADMIN_PASSWORD');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    fwrite(STDERR, "Set a valid UNISTAY_ADMIN_EMAIL first.\n");
    exit(1);
}
if (strlen($password) < 14 || strlen($password) > 128) {
    fwrite(STDERR, "Admin password must be between 14 and 128 characters.\n");
    exit(1);
}

try {
    $pdo = db();

    // Refuse to modify any student/owner account, or a different administrator.
    $adminStmt = $pdo->query('SELECT user_id, email FROM `User` WHERE role = \'admin\' LIMIT 2');
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($admins) > 1) {
        throw new RuntimeException('More than one admin exists. Resolve this manually before provisioning.');
    }
    if ($admins && strtolower((string) $admins[0]['email']) !== $email) {
        throw new RuntimeException('A different admin account exists. Refusing to create or replace it.');
    }

    $existingStmt = $pdo->prepare('SELECT user_id, role FROM `User` WHERE email = ? LIMIT 1');
    $existingStmt->execute([$email]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($existing && $existing['role'] !== 'admin') {
        throw new RuntimeException('That email belongs to another account role; choose a separate admin email.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($hash)) {
        throw new RuntimeException('Could not generate a password hash.');
    }

    // Write the email allowlist before creating/enabling the account, to fail closed.
    // The password hash lives in the User table, NOT this local config file.
    $configPath = __DIR__ . '/../config/admin.local.php';
    $contents = "<?php\nreturn ['email' => " . var_export($email, true) . "];\n";
    if (file_put_contents($configPath, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Could not write config/admin.local.php.');
    }

    if ($existing) {
        $update = $pdo->prepare(
            'UPDATE `User` SET password_hash = ?, status = ? WHERE user_id = ? AND role = ?'
        );
        $update->execute([$hash, 'active', (int) $existing['user_id'], 'admin']);
        echo "Existing UniStay admin password updated.\n";
    } else {
        $newId = (int) $pdo->query('SELECT NEXTVAL(unistay_user_seq)')->fetchColumn();
        if ($newId <= 0) {
            throw new RuntimeException('User ID sequence returned an invalid ID.');
        }
        $insert = $pdo->prepare(<<<'SQL'
            INSERT INTO `User` (user_id, full_name, email, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?, ?)
            SQL);
        $insert->execute([$newId, 'UniStay Administrator', $email, $hash, 'admin', 'active']);
        echo "One UniStay administrator account created.\n";
    }
    echo "Admin email: {$email}\nLogin via: /public/login.php\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Admin setup failed: {$e->getMessage()}\n");
    exit(1);
}
