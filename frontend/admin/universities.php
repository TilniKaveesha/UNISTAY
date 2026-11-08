<?php
require_once '../../config/db_connect.php';
require_once '../../backend/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_university'])) {
    $stmt = $pdo->prepare("INSERT INTO universities (name, city, address) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['city'], $_POST['address']]);
}

$universities = $pdo->query("SELECT * FROM universities")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Universities - UniStay Admin</title>
</head>
<body>
    <div class="admin-layout" style="display: flex; font-family: sans-serif;">
        <main class="main-content" style="padding: 20px; flex-grow: 1;">
            <h1>Manage Universities</h1>
            
            <form method="POST" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc;">
                <h3>Add New University</h3>
                <input type="text" name="name" placeholder="University Name" required>
                <input type="text" name="city" placeholder="City" required>
                <input type="text" name="address" placeholder="Full Address" required>
                <button type="submit" name="add_university">Add</button>
            </form>

            <table border="1" cellpadding="10" cellspacing="0" style="width:100%;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>City</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($universities as $uni): ?>
                    <tr>
                        <td><?= htmlspecialchars($uni['name']) ?></td>
                        <td><?= htmlspecialchars($uni['city']) ?></td>
                        <td><?= htmlspecialchars($uni['address']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>