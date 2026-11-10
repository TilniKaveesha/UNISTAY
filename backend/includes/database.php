<?php
// Database connection function for the UniStay application
declare(strict_types=1);
// Establish a connection to the TiDB database using PDO
function db(): PDO
{
    static $pdo = null;
    // If the PDO instance already exists, return it
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = __DIR__ .
        '/../../config/database.local.php';
// Check if the database configuration file exists
    if (!is_file($configPath)) {
        throw new RuntimeException(
            'Database configuration file is missing.'
        );
    }

    $config = require $configPath;

    if (!is_file($config['ssl_ca'])) {
        throw new RuntimeException(
            'TiDB CA certificate is missing.'
        );
    }
    // Create a DSN (Data Source Name) string for the PDO connection
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );
    // Create a new PDO instance with the provided configuration
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES =>
                false,

            \Pdo\Mysql::ATTR_SSL_CA => $config['ssl_ca'],

            \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]
    );

    return $pdo;
}