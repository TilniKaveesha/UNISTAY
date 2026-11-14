<?php
/**
 * CLI-only runtime configuration generator for Render.
 * Secrets are read from environment variables and stored outside the web root.
 * No credentials are included in Git or Docker image layers.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$dir = $root . '/config';
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
    throw new RuntimeException('Cannot create private config directory');
}
umask(0077);

$get = static function (string $name): string {
    $value = getenv($name);
    // Preserve exact credentials, including leading/trailing spaces if present.
    return is_string($value) ? $value : '';
};

$requiredNames = ['TIDB_HOST', 'TIDB_DATABASE', 'TIDB_USERNAME', 'TIDB_PASSWORD'];
$missing = array_values(array_filter($requiredNames, static fn ($k) => trim($get($k)) === ''));
if ($missing !== []) {
    fwrite(STDERR, "Missing Render environment variables: " . implode(', ', $missing) . "\n");
    exit(1);
}

$portText = $get('TIDB_PORT') ?: '4000';
$port = filter_var($portText, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
if (!is_int($port)) {
    fwrite(STDERR, "TIDB_PORT must be a valid port\n");
    exit(1);
}

$caFile = '/etc/ssl/certs/ca-certificates.crt';
$custom = $get('TIDB_CA_PEM_BASE64');
if ($custom !== '') {
    $decoded = base64_decode($custom, true);
    if ($decoded === false || strlen($decoded) > 65536 || !str_contains($decoded, 'BEGIN CERTIFICATE')) {
        fwrite(STDERR, "Invalid TIDB_CA_PEM_BASE64 certificate value\n");
        exit(1);
    }
    $caFile = $dir . '/tidb-ca.pem';
    if (file_put_contents($caFile, $decoded, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write trusted TiDB CA certificate');
    }
}
if (!is_file($caFile)) {
    throw new RuntimeException('Trusted TiDB CA bundle is missing');
}

$save = static function (string $name, array $values) use ($dir): void {
    $path = $dir . '/' . $name;
    // PHP generates a quoted representation of each value; no hand-built secret escaping.
    $contents = "<?php\nreturn " . var_export($values, true) . ";\n";
    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write private config: ' . $name);
    }
    chmod($path, 0600);
};

$save('database.local.php', [
    'host' => $get('TIDB_HOST'),
    'port' => $port,
    'database' => $get('TIDB_DATABASE'),
    'username' => $get('TIDB_USERNAME'),
    'password' => $get('TIDB_PASSWORD'),
    'ssl_ca' => $caFile,
]);

$cloudKeys = [
    'cloud_name' => $get('CLOUDINARY_CLOUD_NAME'),
    'api_key' => $get('CLOUDINARY_API_KEY'),
    'api_secret' => $get('CLOUDINARY_API_SECRET'),
];
if (!in_array('', $cloudKeys, true)) {
    $save('cloudinary.local.php', $cloudKeys + [
        'force_ipv4' => false,
        'ca_bundle' => '',
    ]);
} elseif (count(array_filter($cloudKeys, static fn ($v) => $v !== '')) > 0) {
    fwrite(STDERR, "Warning: Cloudinary configuration is incomplete; uploads will be unavailable\n");
}

$googleClientId = $get('GOOGLE_CLIENT_ID');
if ($googleClientId !== '') {
    $save('google.local.php', [
        'client_id' => $googleClientId,
        'google_client_id' => $googleClientId,
        'client_secret' => $get('GOOGLE_CLIENT_SECRET'),
        'web' => ['client_id' => $googleClientId],
    ]);
}

$adminEmail = $get('UNISTAY_ADMIN_EMAIL');
if ($adminEmail !== '') {
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "UNISTAY_ADMIN_EMAIL must be an email address\n");
        exit(1);
    }
    // This is only the email allowlist, not the admin password. Admin account
    // must already be provisioned securely in TiDB from a trusted local CLI.
    $save('admin.local.php', ['email' => strtolower($adminEmail)]);
}

echo "UniStay runtime configuration generated successfully.\n";
