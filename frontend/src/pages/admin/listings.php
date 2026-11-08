<?php
require_once '../../../../config/db_connect.php';
require_once '../../../../backend/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'deactivate') {
        // Soft-delete to preserve tenancy history[span_10](start_span)[span_10](end_span)
        $stmt = $pdo->prepare("UPDATE boarding_listings SET availability_status = 'unavailable' WHERE listing_id = ?");
        $stmt->execute([$_POST['listing_id']]);
    }
}

$query = "SELECT l.listing_id, l.title, l.rent_amount, l.availability_status, u.full_name as owner_name 
          FROM boarding_listings l 
          JOIN users u ON l.owner_id = u.user_id 
          ORDER BY l.created_at DESC";
$listings = $pdo->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Listings - UniStay Admin</title>
</head>
<body>
    <div class="admin-layout" style="display: flex; font-family: sans-serif;">
        <nav class="sidebar">
            <h2>UniStay Admin</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Manage Users</a>
            <a href="listings.php">Manage Listings</a>
            <a href="universities.php">Manage Universities</a>
            <a href="../../../backend/logout.php">Logout</a>
        </nav>
        <main class="main-content" style="padding: 20px; flex-grow: 1;">
            <h1>Manage Listings</h1>
            <table border="1" cellpadding="10" cellspacing="0" style="width:100%;">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Rent Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing): ?>
                    <tr>
                        <td><?= htmlspecialchars($listing['title']) ?></td>
                        <td><?= htmlspecialchars($listing['owner_name']) ?></td>
                        <td><?= htmlspecialchars($listing['rent_amount']) ?></td>
                        <td><?= htmlspecialchars($listing['availability_status']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="listing_id" value="<?= $listing['listing_id'] ?>">
                                <button type="submit" name="action" value="deactivate">Force Unavailable</button>
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
