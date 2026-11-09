<?php

declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/auth.php';

requireRole('owner');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniStay -owner Dashboard</title>
</head>
<body>
    <h1>UniStay Owner Dashboard</h1>

    <h2>Boarding Management</h2>

    <ul>
        <li>My Boarding Listings</li>
        <li>Manage Inquiries</li>
        <li>Manage Tenants</li>
        <li>Rent Management</li>
    </ul>
</body>
</html>