<?php
declare(strict_types=1);

/**
 * Cloudinary storage adapter for UniStay. No Composer or .env dependency.
 * Requires the PHP cURL and fileinfo extensions, and a private config file.
 */

function unistay_cloudinary_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $filename = __DIR__ . '/../../config/cloudinary.local.php';
    if (!is_file($filename)) {
        throw new RuntimeException('Cloudinary is not configured. Copy config/cloudinary.example.php to config/cloudinary.local.php.');
    }

    $loaded = require $filename;
    if (!is_array($loaded)) {
        throw new RuntimeException('Cloudinary configuration must return an array.');
    }
    foreach (['cloud_name', 'api_key', 'api_secret'] as $key) {
        if (!isset($loaded[$key]) || !is_string($loaded[$key]) || trim($loaded[$key]) === '') {
            throw new RuntimeException('Missing required Cloudinary configuration: ' . $key);
        }
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $loaded['cloud_name'])) {
        throw new RuntimeException('Invalid Cloudinary cloud_name.');
    }
    $config = $loaded;
    return $config;
}

/**
 * Calls Cloudinary's server-to-server Upload API over verified HTTPS.
 * Auth details remain in the private PHP config file, never in the browser.
 */
function unistay_cloudinary_request(string $action, array $fields): array
{
    if (!extension_loaded('curl')) {
        throw new RuntimeException('PHP cURL extension is required for Cloudinary uploads.');
    }
    if (!in_array($action, ['upload', 'destroy'], true)) {
        throw new InvalidArgumentException('Unsupported Cloudinary operation.');
    }

    $cfg = unistay_cloudinary_config();
    $url = 'https://api.cloudinary.com/v1_1/' . $cfg['cloud_name'] . '/image/' . $action;
    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Could not initialize the cloud upload connection.');
    }

    $options = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $action === 'destroy' ? http_build_query($fields) : $fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 70,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $cfg['api_key'] . ':' . $cfg['api_secret'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ];
    // Helpful on development networks with working IPv4 but broken IPv6.
    if (($cfg['force_ipv4'] ?? false) && defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
    // Optional custom CA bundle. Normally use the trusted CA configured in php.ini.
    if (!empty($cfg['ca_bundle'])) {
        $path = (string)$cfg['ca_bundle'];
        if (!is_file($path)) {
            unset($handle);
            throw new RuntimeException('Configured Cloudinary CA bundle was not found.');
        }
        $options[CURLOPT_CAINFO] = $path;
    }
    curl_setopt_array($handle, $options);

    $body = curl_exec($handle);
    $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $curlError = curl_error($handle);
    unset($handle);

    if ($body === false) {
        throw new RuntimeException('Cloudinary network or TLS failure: ' . $curlError);
    }
    $result = json_decode($body, true);
    if (!is_array($result)) {
        throw new RuntimeException('Cloudinary returned an unreadable response (HTTP ' . $status . ').');
    }
    if ($status < 200 || $status >= 300) {
        $detail = (string)($result['error']['message'] ?? 'Cloud request rejected');
        throw new RuntimeException('Cloudinary HTTP ' . $status . ': ' . substr($detail, 0, 240));
    }
    return $result;
}

/**
 * Validates a PHP uploaded image, uploads it into a listing-specific cloud path,
 * and returns a stable HTTPS delivery URL plus the Cloudinary public ID.
 */
function unistay_cloudinary_upload(array $file, int $listingId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Image upload failed. Check the file and PHP upload size limits.');
    }
    $tmp = $file['tmp_name'] ?? null;
    if (!is_string($tmp) || !is_uploaded_file($tmp)) {
        throw new InvalidArgumentException('Invalid uploaded file.');
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 4 * 1024 * 1024) {
        throw new InvalidArgumentException('Each photo must be no larger than 4 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new InvalidArgumentException('Only JPEG, PNG and WebP are supported.');
    }
    $dims = @getimagesize($tmp);
    if (!$dims || $dims[0] < 1 || $dims[1] < 1 || $dims[0] > 6000 || $dims[1] > 6000) {
        throw new InvalidArgumentException('Invalid image or image dimensions exceed 6000 pixels.');
    }

    $publicId = 'unistay/listings/' . $listingId . '/' . bin2hex(random_bytes(16));
    $response = unistay_cloudinary_request('upload', [
        'file' => new CURLFile($tmp, $mime, 'property-photo'),
        'public_id' => $publicId,
        'overwrite' => 'false',
    ]);
    $remotePublicId = (string)($response['public_id'] ?? '');
    $secureUrl = (string)($response['secure_url'] ?? '');

    $urlParts = parse_url($secureUrl);
    $validUrl = is_array($urlParts)
        && ($urlParts['scheme'] ?? '') === 'https'
        && ($urlParts['host'] ?? '') === 'res.cloudinary.com'
        && !isset($urlParts['user']) && !isset($urlParts['pass'])
        && strlen($secureUrl) <= 2048;
    if (!$validUrl || $remotePublicId !== $publicId) {
        // An unexpected response is never trusted as a database photo URL.
        if ($remotePublicId !== '' && str_starts_with($remotePublicId, 'unistay/listings/' . $listingId . '/')) {
            try {
                unistay_cloudinary_delete($remotePublicId, $listingId);
            } catch (Throwable $cleanupError) {
                error_log('UniStay cloud cleanup failed after invalid response: ' . $cleanupError->getMessage());
            }
        }
        throw new RuntimeException('Cloudinary returned an unexpected asset identifier or URL.');
    }

    return ['file_path' => $secureUrl, 'cloud_public_id' => $remotePublicId];
}

/**
 * Delete only cloud assets created under this listing's namespace.
 * The database row should be deleted/committed before calling this function.
 */
function unistay_cloudinary_delete(string $publicId, int $listingId): void
{
    $prefix = 'unistay/listings/' . $listingId . '/';
    if (!str_starts_with($publicId, $prefix)) {
        throw new InvalidArgumentException('Refusing to delete an asset outside this listing.');
    }
    $result = unistay_cloudinary_request('destroy', [
        'public_id' => $publicId,
        'invalidate' => 'true',
    ]);
    if (!in_array((string)($result['result'] ?? ''), ['ok', 'not found'], true)) {
        throw new RuntimeException('Cloudinary did not confirm the image deletion.');
    }
}
