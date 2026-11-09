<?php 
declare(strict_types=1);

require_once __DIR__ . '/../../backend/includes/auth.php';

requireRole('student');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>UniStay - Student Dashboard</title>

</head>
<body>
    <h1>University Student Dashboard</h1>
    <ul>
        <li>My Inquiries</li>
        <li>My Tenancy</li>
        <li>Rent History</li>
        <li>My Profile</li>
    </ul>

</body>

</html>