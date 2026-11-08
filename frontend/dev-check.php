<?php
declare(strict_types=1);

$appName = "UniStay";
$phpVersion = PHP_VERSION;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniStay Development</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($appName); ?></h1>

    <h2>PHP Server Running Successfully!</h2>

    <p>PHP Version:
        <?php echo htmlspecialchars($phpVersion); ?>
    </p>
</body>
</html>