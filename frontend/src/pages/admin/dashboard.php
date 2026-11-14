<?php

$pageTitle = "Dashboard Overview | UniStay Admin";

require_once __DIR__ . '/../../../../config/db_connect.php';
require_once __DIR__ . '/../../../../backend/auth.php';

require_admin();

$pdo = db();

// Fetching accurate admin metrics
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$suspendedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

$listingCount = $pdo->query("SELECT COUNT(*) FROM boarding_listings")->fetchColumn();
$availableListings = $pdo->query("SELECT COUNT(*) FROM boarding_listings WHERE availability_status = 'available'")->fetchColumn();

$uniCount = $pdo->query("SELECT COUNT(*) FROM universities")->fetchColumn();

// Fetch recent listings for the dashboard overview table
$recentListingsQuery = "
    SELECT l.title, l.availability_status, u.full_name as owner_name 
    FROM boarding_listings l 
    JOIN `User` u ON l.owner_id = u.user_id 
    ORDER BY l.created_at DESC 
    LIMIT 5
";
$recentListings = $pdo->query($recentListingsQuery)->fetchAll();

// Fetch recent users for the dashboard overview table
$recentUsersQuery = "
    SELECT full_name, role, status 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recentUsers = $pdo->query($recentUsersQuery)->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="../../src/styles/style.css">
    <link rel="stylesheet" href="dashboard.css">

</head>

<body>

<?php include __DIR__ . '/../../components/header.php'; ?>


<main class="admin-dashboard">

    <div class="container">

        <!-- Dashboard heading -->
        <section class="dashboard-heading">

            <div>
                <p class="dashboard-subtitle">UNISTAY MANAGEMENT</p>
                <h1>Platform Overview</h1>

                <p class="dashboard-welcome">
                    A complete snapshot of your UniStay platform's health and activity.
                </p>
            </div>

            <a href="users.php" class="btn btn-primary">
                Manage Users
            </a>

        </section>


        <!-- Statistics -->
        <section class="dashboard-stats">

            <div class="dashboard-stat-card">

                <div class="stat-icon">👥</div>

                <p>Platform Users</p>

                <h2><?php echo number_format($userCount); ?></h2>

                <span>
                    <strong><?php echo $activeUsers; ?></strong> Active &middot; 
                    <strong class="text-danger"><?php echo $suspendedUsers; ?></strong> Suspended
                </span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">🏠</div>

                <p>Total Listings</p>

                <h2><?php echo number_format($listingCount); ?></h2>

                <span>
                    <strong><?php echo $availableListings; ?></strong> Currently Available
                </span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">🛡️</div>

                <p>Account Suspensions</p>

                <h2><?php echo number_format($suspendedUsers); ?></h2>

                <span>Review restricted accounts</span>

            </div>


            <div class="dashboard-stat-card">

                <div class="stat-icon">🏛️</div>

                <p>Partner Universities</p>

                <h2><?php echo number_format($uniCount); ?></h2>

                <span>In the university directory</span>

            </div>

        </section>


        <!-- Recent Activity Data Grid -->
        <section class="admin-grid-layout">

            <!-- Recent Listings Panel -->
            <div class="admin-panel">

                <div class="admin-panel-header">
                    <h2>Recently Added Listings</h2>
                    <a href="listings.php" class="btn btn-outline btn-small">View All</a>
                </div>

                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>Property Title</th>
                                <th>Owner</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($recentListings)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No listings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentListings as $listing): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($listing['owner_name']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = ($listing['availability_status'] === 'available') ? 'status-available' : 'status-unavailable';
                                            ?>
                                            <span class="status-tag <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($listing['availability_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>

                </div>

            </div>


            <!-- Recent Users Panel -->
            <div class="admin-panel">

                <div class="admin-panel-header">
                    <h2>Newest Registered Users</h2>
                    <a href="users.php" class="btn btn-outline btn-small">View All</a>
                </div>

                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($recentUsers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </div>

</main>


<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>
