<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/dashboard_module.php';
$ownerId = module_require('owner');
$pdo = db();
$clock = dashboard_clock();
$today = $clock->format('Y-m-d');
$weekEnd = $clock->modify('+7 days')->format('Y-m-d');
$firstMonth = $clock->format('Y-m-01');
$nextMonth = $clock->modify('first day of next month')->format('Y-m-01');

$owner = dashboard_one($pdo, 'SELECT full_name FROM `User` WHERE user_id = ? LIMIT 1', [$ownerId]);
$listings = dashboard_one($pdo, "SELECT COUNT(*) AS total,
    COALESCE(SUM(CASE WHEN availability = 'available' THEN 1 ELSE 0 END), 0) AS available
    FROM `BoardingListing` WHERE owner_id = ?", [$ownerId]);
$inquiries = dashboard_one($pdo, "SELECT COUNT(*) AS total,
    COALESCE(SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
    COALESCE(SUM(CASE WHEN i.status = 'approved' THEN 1 ELSE 0 END), 0) AS approved
    FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id
    WHERE b.owner_id = ?", [$ownerId]);
$tenancies = dashboard_one($pdo, "SELECT COUNT(*) AS active
    FROM `Tenancy` t INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    WHERE b.owner_id = ? AND t.status = 'active'", [$ownerId]);
$recentInquiries = dashboard_many($pdo, "SELECT i.inquiry_id, i.status, i.listing_id,
    b.title AS listing_title, u.full_name AS student_name
    FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id
    LEFT JOIN `User` u ON u.user_id = i.student_user_id
    WHERE b.owner_id = ? ORDER BY i.inquiry_id DESC LIMIT 5", [$ownerId]);

$rentReady = true;
$rent = ['outstanding' => 0, 'overdue' => 0, 'due_soon' => 0, 'month_due' => 0, 'month_paid' => 0];
$dueBills = [];
try {
    $rent = dashboard_one($pdo, "SELECT
        COALESCE(SUM(GREATEST(p.amount_due - p.amount_paid, 0)), 0) AS outstanding,
        COALESCE(SUM(CASE WHEN p.amount_due > p.amount_paid AND p.due_date < ? THEN 1 ELSE 0 END), 0) AS overdue,
        COALESCE(SUM(CASE WHEN p.amount_due > p.amount_paid AND p.due_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS due_soon,
        COALESCE(SUM(CASE WHEN p.billing_month >= ? AND p.billing_month < ? THEN p.amount_due ELSE 0 END), 0) AS month_due,
        COALESCE(SUM(CASE WHEN p.billing_month >= ? AND p.billing_month < ? THEN p.amount_paid ELSE 0 END), 0) AS month_paid
        FROM `RentPayment` p
        INNER JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
        INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
        WHERE b.owner_id = ?", [$today, $today, $weekEnd, $firstMonth, $nextMonth, $firstMonth, $nextMonth, $ownerId]);
    $dueBills = dashboard_many($pdo, "SELECT p.payment_id, p.due_date, p.amount_due, p.amount_paid,
        b.title AS listing_title, b.listing_id, u.full_name AS student_name
        FROM `RentPayment` p
        INNER JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
        INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
        INNER JOIN `User` u ON u.user_id = t.student_user_id
        WHERE b.owner_id = ? AND p.amount_due > p.amount_paid
        ORDER BY p.due_date ASC, p.payment_id ASC LIMIT 5", [$ownerId]);
} catch (PDOException $e) {
    error_log('UniStay owner dashboard rent lookup: ' . $e->getCode() . ' ' . $e->getMessage());
    $rentReady = false;
}
$ownerName = trim((string)($owner['full_name'] ?? '')) ?: 'Owner';
$noticeCount = dashboard_number($inquiries['pending'] ?? 0) + dashboard_number($inquiries['approved'] ?? 0)
    + ($rentReady ? dashboard_number($rent['overdue'] ?? 0) + dashboard_number($rent['due_soon'] ?? 0) : 0);
module_header('Owner Dashboard', 'owner');
?>
<section class="ud-hero" aria-label="Welcome">
    <div>
        <p class="ud-eyebrow">UNISTAY · OWNER WORKSPACE</p>
        <h2>Welcome back, <?= module_escape($ownerName) ?></h2>
        <p>Manage your boarding listings, respond to students and keep rent records in one place.</p>
    </div>
    <div class="ud-hero-actions"><a class="btn" href="/owner/listings.php">Manage listings</a><a class="btn secondary" href="/owner/inquiries.php">Review inquiries</a></div>
</section>

<div class="ud-stat-grid" aria-label="Owner statistics">
    <a class="ud-stat" href="/owner/listings.php"><span>My listings</span><strong><?= dashboard_number($listings['total'] ?? 0) ?></strong><small><?= dashboard_number($listings['available'] ?? 0) ?> marked available</small></a>
    <a class="ud-stat" href="/owner/inquiries.php?status=pending"><span>Pending inquiries</span><strong><?= dashboard_number($inquiries['pending'] ?? 0) ?></strong><small>Waiting for your response</small></a>
    <a class="ud-stat" href="/owner/tenants.php"><span>Active tenancies</span><strong><?= dashboard_number($tenancies['active'] ?? 0) ?></strong><small>Student-confirmed only</small></a>
    <a class="ud-stat" href="/owner/rent.php"><span>Outstanding rent</span><strong class="ud-money"><?= $rentReady ? module_escape(dashboard_currency($rent['outstanding'] ?? 0)) : 'Unavailable' ?></strong><small>Owner-recorded balances</small></a>
</div>

<div class="ud-two-col">
    <section class="panel ud-panel">
        <div class="ud-section-head"><div><p class="ud-eyebrow">NEEDS ATTENTION</p><h2>Activity alerts <?= $noticeCount > 0 ? '<span class="ud-alert-count">' . $noticeCount . '</span>' : '' ?></h2></div><span class="ud-subtle">Updated on page load</span></div>
        <div class="ud-alert-list">
            <?php if (dashboard_number($inquiries['pending'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/owner/inquiries.php?status=pending"><span class="ud-alert-symbol">!</span><span><strong><?= dashboard_number($inquiries['pending']) ?> inquiry/inquiries awaiting review</strong><small>Reply to interested students</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if (dashboard_number($inquiries['approved'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/owner/inquiries.php?status=approved"><span class="ud-alert-symbol ud-blue">↗</span><span><strong><?= dashboard_number($inquiries['approved']) ?> approved offer(s)</strong><small>Students have not yet confirmed tenancy</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($rentReady && dashboard_number($rent['overdue'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/owner/rent.php"><span class="ud-alert-symbol ud-red">!</span><span><strong><?= dashboard_number($rent['overdue']) ?> overdue rent bill(s)</strong><small>Outstanding after the due date</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($rentReady && dashboard_number($rent['due_soon'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/owner/rent.php"><span class="ud-alert-symbol ud-blue">⌁</span><span><strong><?= dashboard_number($rent['due_soon']) ?> bill(s) due within 7 days</strong><small>Review upcoming payments</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if (dashboard_number($listings['total'] ?? 0) === 0): ?>
                <a class="ud-alert" href="/owner/listings.php"><span class="ud-alert-symbol ud-blue">+</span><span><strong>Create your first listing</strong><small>Students can inquire once it's published</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($noticeCount === 0 && dashboard_number($inquiries['approved'] ?? 0) === 0 && (!$rentReady || dashboard_number($rent['due_soon'] ?? 0) === 0) && dashboard_number($listings['total'] ?? 0) > 0): ?>
                <p class="ud-empty">No urgent items right now. New inquiries and payment alerts appear here.</p>
            <?php endif; ?>
            <?php if (!$rentReady): ?><p class="ud-warning">Rent summary unavailable. Confirm that the `RentPayment` migration has been applied.</p><?php endif; ?>
        </div>
    </section>
    <section class="panel ud-panel">
        <div class="ud-section-head"><div><p class="ud-eyebrow">MONTHLY RENT · <?= module_escape($clock->format('M Y')) ?></p><h2>Rent summary</h2></div><a href="/owner/rent.php">Open ledger</a></div>
        <?php if ($rentReady): ?>
            <div class="ud-rent-values"><div><small>Billed for month</small><strong><?= module_escape(dashboard_currency($rent['month_due'] ?? 0)) ?></strong></div><div><small>Recorded paid against bills for month</small><strong><?= module_escape(dashboard_currency($rent['month_paid'] ?? 0)) ?></strong></div></div>
            <?php $due = (float)($rent['month_due'] ?? 0); $paid = (float)($rent['month_paid'] ?? 0); ?>
            <div class="ud-progress" role="progressbar" aria-label="Recorded paid portion of monthly billing" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $due > 0 ? min(100, max(0, round($paid / $due * 100))) : 0 ?>"><span style="width:<?= $due > 0 ? min(100, max(0, round($paid / $due * 100))) : 0 ?>%"></span></div>
            <p class="ud-subtle">Payments are recorded by owners. This is a summary of the selected billing month, not money transferred through UniStay.</p>
        <?php else: ?><p class="ud-warning">Rent data is unavailable until the rent ledger schema is installed.</p><?php endif; ?>
    </section>
</div>

<div class="ud-two-col">
    <section class="panel ud-panel"><div class="ud-section-head"><h2>Recent inquiries</h2><a href="/owner/inquiries.php">View all</a></div>
        <?php if (!$recentInquiries): ?><p class="ud-empty">No student inquiries yet.</p><?php else: ?>
        <div class="ud-item-list">
        <?php foreach ($recentInquiries as $item): ?>
            <div class="ud-item"><div><strong><?= module_escape((string)($item['student_name'] ?: 'Student')) ?></strong><small><?= module_escape(dashboard_title($item['listing_title'], (int)$item['listing_id'])) ?> · Inquiry #<?= (int)$item['inquiry_id'] ?></small></div><?= dashboard_badge((string)$item['status']) ?></div>
        <?php endforeach; ?></div><?php endif; ?>
    </section>
    <section class="panel ud-panel"><div class="ud-section-head"><h2>Outstanding rent</h2><a href="/owner/rent.php">View all</a></div>
        <?php if (!$rentReady): ?><p class="ud-warning">Rent records unavailable.</p>
        <?php elseif (!$dueBills): ?><p class="ud-empty">No outstanding monthly bills.</p>
        <?php else: ?><div class="ud-item-list">
            <?php foreach ($dueBills as $bill): ?>
                <div class="ud-item"><div><strong><?= module_escape((string)($bill['student_name'] ?: 'Student')) ?></strong><small><?= module_escape(dashboard_title($bill['listing_title'], (int)$bill['listing_id'])) ?> · Due <?= module_escape((string)$bill['due_date']) ?></small></div><strong class="ud-balance"><?= module_escape(dashboard_currency(max(0, (float)$bill['amount_due'] - (float)$bill['amount_paid']))) ?></strong></div>
            <?php endforeach; ?></div><?php endif; ?>
    </section>
</div>
<?php module_footer(); ?>
