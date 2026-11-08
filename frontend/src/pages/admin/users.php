<?php
require_once '../../../../config/db_connect.php';
require_once '../../../../backend/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->execute([$_POST['status'], $_POST['user_id']]);
}

$users = $pdo->query("SELECT user_id, full_name, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - UniStay Admin</title>
    <link rel="stylesheet" href="../../styles/style.css">
</head>
<body>
    <div class="admin-layout" style="display: flex; font-family: sans-serif;">
        <nav class="sidebar">
            <h2>UniStay Admin</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="listings.php">Manage Listings</a>
            <a href="universities.php">Manage Universities</a>
            <a href="../../backend/logout.php">Logout</a>
        </nav>
        <main class="main-content" style="padding: 20px; flex-grow: 1;">
            <h1>Manage Users</h1>
            <table border="1" cellpadding="10" cellspacing="0" style="width:100%; text-align:left;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= htmlspecialchars($user['status']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <select name="status">
                                    <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                                <button type="submit" name="update_status">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
