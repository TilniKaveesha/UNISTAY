<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../backend/includes/admin_ui.php';

$pdo = ua_bootstrap();

// These are all real TiDB entities used by the existing Owner/Student modules.
// Avoid old aliases such as users, boarding_listings or universities.
try {
    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM `User`')->fetchColumn();
    $totalListings = (int) $pdo->query('SELECT COUNT(*) FROM `BoardingListing`')->fetchColumn();
    $totalUniversities = (int) $pdo->query('SELECT COUNT(*) FROM `University`')->fetchColumn();
    $activeListings = (int) $pdo->query("SELECT COUNT(*) FROM `BoardingListing` WHERE availability = 'available'")->fetchColumn();

    $roleRows = $pdo->query('SELECT role, COUNT(*) AS total FROM `User` GROUP BY role')->fetchAll(PDO::FETCH_ASSOC);
    $statusRows = $pdo->query('SELECT availability, COUNT(*) AS total FROM `BoardingListing` GROUP BY availability')->fetchAll(PDO::FETCH_ASSOC);

    $recentUsers = $pdo->query(
        'SELECT user_id, full_name, email, role, status FROM `User` ORDER BY user_id DESC LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);
    $recentListings = $pdo->query(
        'SELECT b.listing_id, b.title, b.availability, b.rent_amount, u.full_name AS owner_name
         FROM `BoardingListing` b LEFT JOIN `User` u ON u.user_id = b.owner_id
         ORDER BY b.listing_id DESC LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC);
    // Inquiry is part of UniStay but a partially migrated environment might not have it yet.
    try {
        $pendingInquiries = (int) $pdo->query("SELECT COUNT(*) FROM `Inquiry` WHERE status = 'pending'")->fetchColumn();
    } catch (PDOException $e) {
        error_log('Admin inquiry metric unavailable: ' . $e->getMessage());
        $pendingInquiries = null;
    }

    $roles = ['student' => 0, 'owner' => 0, 'admin' => 0];
    foreach ($roleRows as $row) {
        if (array_key_exists((string) $row['role'], $roles)) $roles[(string) $row['role']] = (int) $row['total'];
    }
    $listingStatuses = ['available' => 0, 'soon' => 0, 'unavailable' => 0];
    foreach ($statusRows as $row) {
        if (array_key_exists((string) $row['availability'], $listingStatuses)) $listingStatuses[(string) $row['availability']] = (int) $row['total'];
    }
    $dataError = false;
} catch (Throwable $e) {
    error_log('Admin dashboard failed: ' . $e->getMessage());
    $dataError = true;
    $totalUsers = $totalListings = $totalUniversities = $activeListings = 0;
    $pendingInquiries = null;
    $roles = ['student' => 0, 'owner' => 0, 'admin' => 0];
    $listingStatuses = ['available' => 0, 'soon' => 0, 'unavailable' => 0];
    $recentUsers = $recentListings = [];
}

ua_page_start('dashboard', 'Overview', 'A snapshot of your UniStay platform, listings and community.');
?>
<?php if ($dataError): ?>
    <div class="ua-alert ua-alert-error" role="alert"><?= ua_icon('alert') ?> Unable to load dashboard data. Review the PHP server log and database schema.</div>
<?php endif; ?>

<div class="ua-stat-grid">
    <a class="ua-stat-card" href="/public/admin/users.php">
        <div class="ua-stat-top"><span>Registered users</span><span class="ua-stat-icon ua-icon-blue"><?= ua_icon('users') ?></span></div>
        <strong><?= number_format($totalUsers) ?></strong>
        <small>All platform accounts <?= ua_icon('arrow', 14) ?></small>
    </a>
    <a class="ua-stat-card" href="/public/admin/listings.php">
        <div class="ua-stat-top"><span>Boarding listings</span><span class="ua-stat-icon ua-icon-violet"><?= ua_icon('house') ?></span></div>
        <strong><?= number_format($totalListings) ?></strong>
        <small><?= number_format($activeListings) ?> available right now <?= ua_icon('arrow', 14) ?></small>
    </a>
    <a class="ua-stat-card" href="/public/admin/universities.php">
        <div class="ua-stat-top"><span>Universities</span><span class="ua-stat-icon ua-icon-mint"><?= ua_icon('university') ?></span></div>
        <strong><?= number_format($totalUniversities) ?></strong>
        <small>In the university directory <?= ua_icon('arrow', 14) ?></small>
    </a>
    <a class="ua-stat-card" href="/public/admin/listings.php">
        <div class="ua-stat-top"><span>Pending inquiries</span><span class="ua-stat-icon ua-icon-amber"><?= ua_icon('clock') ?></span></div>
        <strong><?= $pendingInquiries === null ? '—' : number_format($pendingInquiries) ?></strong>
        <small><?= $pendingInquiries === null ? 'Inquiry table not available' : 'Awaiting owner response' ?> <?= ua_icon('arrow', 14) ?></small>
    </a>
</div>

<div class="ua-columns ua-columns-charts">
    <section class="ua-panel">
        <div class="ua-panel-heading"><div><h2>Listing availability</h2><p>Current status of advertised boarding places</p></div><?= ua_icon('trend', 20) ?></div>
        <?php if ($totalListings === 0): ?>
            <div class="ua-empty">No listings yet. New owner properties will appear here.</div>
        <?php else: ?>
            <?php foreach (['available' => 'Available', 'soon' => 'Coming soon', 'unavailable' => 'Unavailable'] as $key => $label): ?>
                <?php $count = $listingStatuses[$key]; $percent = $totalListings > 0 ? 100 * $count / $totalListings : 0; ?>
                <div class="ua-breakdown-row">
                    <div class="ua-breakdown-label"><span><?= ua_escape($label) ?></span><strong><?= $count ?> <small>(<?= round($percent) ?>%)</small></strong></div>
                    <div class="ua-progress-track"><div class="ua-progress-fill ua-fill-<?= ua_escape($key) ?>" style="width:<?= round($percent, 1) ?>%"></div></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <section class="ua-panel">
        <div class="ua-panel-heading"><div><h2>User community</h2><p>Accounts by role</p></div><?= ua_icon('users', 20) ?></div>
        <div class="ua-role-list">
            <?php foreach (['student' => ['Students', 'blue'], 'owner' => ['Boarding owners', 'purple'], 'admin' => ['Administrators', 'neutral']] as $key => [$label, $tone]): ?>
                <div class="ua-role-item"><span class="ua-role-symbol ua-role-<?= $tone ?>"><?= ua_icon($key === 'student' ? 'users' : ($key === 'owner' ? 'house' : 'shield'), 20) ?></span><div><strong><?= ua_escape($label) ?></strong><small><?= $totalUsers > 0 ? round(100 * $roles[$key] / $totalUsers) : 0 ?>% of all accounts</small></div><strong><?= number_format($roles[$key]) ?></strong></div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="ua-columns">
    <section class="ua-panel ua-table-panel">
        <div class="ua-panel-heading"><div><h2>Recent users</h2><p>Latest accounts by user ID</p></div><a class="ua-text-link" href="/public/admin/users.php">View all <?= ua_icon('arrow', 16) ?></a></div>
        <div class="ua-table-scroll"><table class="ua-table"><thead><tr><th>Account</th><th>Role</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($recentUsers as $u): ?>
            <tr><td><strong><?= ua_escape($u['full_name'] ?: 'Unnamed user') ?></strong><small><?= ua_escape($u['email']) ?></small></td><td><?= ua_badge((string) $u['role']) ?></td><td><?= ua_badge((string) ($u['status'] ?? 'unknown')) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$recentUsers): ?><tr><td colspan="3" class="ua-empty-cell">No user records found.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section class="ua-panel ua-table-panel">
        <div class="ua-panel-heading"><div><h2>Recent listings</h2><p>Latest properties by listing ID</p></div><a class="ua-text-link" href="/public/admin/listings.php">View all <?= ua_icon('arrow', 16) ?></a></div>
        <div class="ua-table-scroll"><table class="ua-table"><thead><tr><th>Boarding</th><th>Rent / mo</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($recentListings as $listing): ?>
            <tr><td><strong><?= ua_escape($listing['title'] ?: 'Boarding #' . $listing['listing_id']) ?></strong><small><?= ua_escape($listing['owner_name'] ?: 'Owner not found') ?></small></td><td>Rs. <?= number_format((float) $listing['rent_amount']) ?></td><td><?= ua_badge((string) $listing['availability']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$recentListings): ?><tr><td colspan="3" class="ua-empty-cell">No listings found.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>
<?php ua_page_end(); ?>
