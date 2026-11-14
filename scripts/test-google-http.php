<?php
declare(strict_types=1);

// Checks only a public Google certificate endpoint. No OAuth token or database
// credentials are needed or transmitted.
require_once __DIR__ . '/../vendor/autoload.php';

echo 'PHP cURL extension: ' . (extension_loaded('curl') ? 'enabled' : 'disabled') . PHP_EOL;

$client = new \GuzzleHttp\Client([
    'force_ip_resolve' => 'v4',
    'connect_timeout' => 10,
    'timeout' => 20,
]);

try {
    $response = $client->get('https://www.googleapis.com/oauth2/v3/certs');
    echo 'Google HTTPS request: success (' . $response->getStatusCode() . ')' . PHP_EOL;
} catch (\GuzzleHttp\Exception\ConnectException $exception) {
   // $context = $exception->getHandlerContext();
    echo 'Google HTTPS request: connection failed' . PHP_EOL;
    echo 'Transport error number: ' . ($context['errno'] ?? 'not provided') . PHP_EOL;
    echo 'Transport detail: ' . ($context['error'] ?? $exception->getMessage()) . PHP_EOL;
} catch (\Throwable $exception) {
    echo 'Google HTTPS request: ' . get_class($exception) . PHP_EOL;
    echo 'Detail: ' . $exception->getMessage() . PHP_EOL;
}
