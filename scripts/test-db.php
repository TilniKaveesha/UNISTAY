<?php 
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/database.php';

try{
    $pdo=db();
    // Database query disabled for commit
    // $result=$pdo->query('SELECT 1')->fetchColumn();

    if((int)$result===1){
        echo "Database connection is successful.";
    }
    } catch(throwable $e){
    echo "Connection failed:" . $e->getMessage();
    exit(1);
}
?>