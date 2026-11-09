<?php
require_once __DIR__ . '/../../backend/includes/session.php';
startAppSession();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: /login.php');
    exit;
}
$name = htmlspecialchars((string) ($_SESSION['full_name'] ?? 'Student'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Dashboard - UniStay</title></head>
<body>
    <h1>Welcome, <?= $name ?></h1>
    <p>Your UniStay student account is signed in. Boarding inquiries are coming next.</p>
    <a href="/logout.php">Sign out</a>
</body>
</html>
