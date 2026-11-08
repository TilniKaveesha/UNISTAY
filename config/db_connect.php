<?php
// TiDB Cloud details 
$host = 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com'; // TiDB Host 
$port = '4000';                      // TiDB Port 
$dbname = 'sys';                     // database name
$username = '3kTPnCxB3Tj91rM.root';  // username 
$password = 'R9cg0CLSFK41VsCW';      // password

try {
    // PDO connection string (DSN) 
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // TiDB Cloud walata avashya SSL settings:
        PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/cert.pem', 
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Database connected successfully!";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
