<?php
require_once '../../config/db_connect.php';
require_once '../../backend/auth.php';
require_admin(); 
$pdo = db();

$userCount = $pdo->query("SELECT COUNT(*) FROM `User`")->fetchColumn();
$listingCount = $pdo->query("SELECT COUNT(*) FROM boarding_listings")->fetchColumn();
$uniCount = $pdo->query("SELECT COUNT(*) FROM universities")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - UniStay</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-layout { display: flex; font-family: sans-serif; }
        .sidebar { width: 250px; background: #333; color: #fff; padding: 20px; min-height: 100vh; }
        .sidebar a { color: #fff; display: block; padding: 10px 0; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .main-content { padding: 20px; flex-grow: 1; }
        .card-grid { display: flex; gap: 20px; }
        .card { border: 1px solid #ccc; padding: 20px; border-radius: 5px; width: 200px; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <h2>UniStay Admin</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="listings.php">Manage Listings</a>
            <a href="universities.php">Manage Universities</a>
            <a href="/logout.php">Logout</a>
        </nav>
        <main class="main-content">
            <h1>Admin Dashboard</h1>
            <div class="card-grid">
                <div class="card">
                    <h3>Total Users</h3>
                    <p><?= htmlspecialchars($userCount) ?></p>
                </div>
                <div class="card">
                    <h3>Total Listings</h3>
                    <p><?= htmlspecialchars($listingCount) ?></p>
                </div>
                <div class="card">
                    <h3>Universities</h3>
                    <p><?= htmlspecialchars($uniCount) ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
