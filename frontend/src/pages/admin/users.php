<?php

$pageTitle = "Manage Users | UniStay Admin";

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../backend/auth.php';

require_admin();

$pdo = db();

// Handle status updates for user accounts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $status = $_POST['status'] ?? '';
    $userId = $_POST['user_id'] ?? '';

    if (!empty($userId) && in_array($status, ['active', 'suspended'])) {
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->execute([$status, $userId]);

    }

}

// Fetch all registered platform users
$query = "
    SELECT user_id, full_name, email, role, status, created_at 
    FROM users 
    ORDER BY created_at DESC
";

$users = $pdo->query($query)->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../../src/styles/style.css">
    <link rel="stylesheet" href="users.css">

</head>

<body>

<?php include __DIR__ . '/../../components/header.php'; ?>


<main class="admin-page-layout">

    <div class="container">

        <!-- Page Heading -->
        <section class="dashboard-heading">

            <div>
                <p class="dashboard-subtitle">UNISTAY MANAGEMENT</p>
                <h1>Manage Users</h1>

                <p class="dashboard-welcome">
                    Monitor accounts, update roles, and manage access statuses.
                </p>
            </div>

        </section>


        <!-- Users Table Panel -->
        <section class="admin-panel">

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Account Role</th>
                            <th>Current Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                </td>
                                
                                <td>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                
                                <td>
                                    <span class="role-badge">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <?php 
                                        $statusClass = ($user['status'] === 'active') ? 'status-active' : 'status-suspended';
                                    ?>
                                    <span class="status-tag <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <form method="POST" class="inline-form">
                                        
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        
                                        <select name="status" class="form-control" style="padding: 6px 10px;">
                                            <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="suspended" <?php echo ($user['status'] == 'suspended') ? 'selected' : ''; ?>>
                                                Suspended
                                            </option>
                                        </select>

                                        <button type="submit" name="update_status" class="btn btn-outline" style="padding: 6px 12px;">
                                            Update
                                        </button>

                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>

            </div>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
