<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/dashboard_module.php';
$studentId = module_require('student');
$pdo = db();
$clock = dashboard_clock();
$today = $clock->format('Y-m-d');
$weekEnd = $clock->modify('+7 days')->format('Y-m-d');

$student = dashboard_one($pdo, 'SELECT full_name FROM `User` WHERE user_id = ? LIMIT 1', [$studentId]);
$inquiries = dashboard_one($pdo, "SELECT COUNT(*) AS total,
    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved
    FROM `Inquiry` WHERE student_user_id = ?", [$studentId]);
$tenancies = dashboard_one($pdo, "SELECT COUNT(*) AS active FROM `Tenancy`
    WHERE student_user_id = ? AND status = 'active'", [$studentId]);
$recentInquiries = dashboard_many($pdo, "SELECT i.inquiry_id, i.status, i.listing_id, b.title AS listing_title
    FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id
    WHERE i.student_user_id = ? ORDER BY i.inquiry_id DESC LIMIT 5", [$studentId]);
$activeHomes = dashboard_many($pdo, "SELECT t.tenancy_id, t.start_date, t.agreed_rent,
    b.listing_id, b.title AS listing_title, b.address, u.name AS university_name, u.city
    FROM `Tenancy` t
    INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    LEFT JOIN `University` u ON u.university_id = b.university_id
    WHERE t.student_user_id = ? AND t.status = 'active' ORDER BY t.tenancy_id DESC LIMIT 5", [$studentId]);
$rentReady = true;
$rent = ['outstanding' => 0, 'overdue' => 0, 'due_soon' => 0];
$upcomingBills = [];
try {
    $rent = dashboard_one($pdo, "SELECT
        COALESCE(SUM(GREATEST(p.amount_due - p.amount_paid, 0)), 0) AS outstanding,
        COALESCE(SUM(CASE WHEN p.amount_due > p.amount_paid AND p.due_date < ? THEN 1 ELSE 0 END), 0) AS overdue,
        COALESCE(SUM(CASE WHEN p.amount_due > p.amount_paid AND p.due_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS due_soon
        FROM `RentPayment` p INNER JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
        WHERE t.student_user_id = ?", [$today, $today, $weekEnd, $studentId]);
    $upcomingBills = dashboard_many($pdo, "SELECT p.payment_id, p.due_date, p.amount_due,
        p.amount_paid, p.status, p.billing_month, b.title AS listing_title, b.listing_id
        FROM `RentPayment` p
        INNER JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
        INNER JOIN `BoardingListing` b ON b.listing_id = t.listing_id
        WHERE t.student_user_id = ? AND p.amount_due > p.amount_paid
        ORDER BY p.due_date ASC, p.payment_id ASC LIMIT 5", [$studentId]);
} catch (PDOException $e) {
    error_log('UniStay student dashboard rent lookup: ' . $e->getCode() . ' ' . $e->getMessage());
    $rentReady = false;
}
$studentName = trim((string)($student['full_name'] ?? '')) ?: 'Student';
$noticeCount = dashboard_number($inquiries['approved'] ?? 0)
    + ($rentReady ? dashboard_number($rent['overdue'] ?? 0) + dashboard_number($rent['due_soon'] ?? 0) : 0);
module_header('Student Dashboard', 'student');
?>
<section class="ud-hero ud-student-hero" aria-label="Welcome">
    <div><p class="ud-eyebrow">UNISTAY · STUDENT WORKSPACE</p><h2>Hi, <?= module_escape($studentName) ?>!</h2><p>Find a new boarding, track your inquiries and manage your tenancy details.</p></div>
    <div class="ud-hero-actions"><a class="btn" href="/student/listings.php">Browse boardings</a><a class="btn secondary" href="/student/inquiries.php">My inquiries</a></div>
</section>

<div class="ud-stat-grid" aria-label="Student statistics">
    <a class="ud-stat" href="/student/inquiries.php"><span>My inquiries</span><strong><?= dashboard_number($inquiries['total'] ?? 0) ?></strong><small><?= dashboard_number($inquiries['pending'] ?? 0) ?> pending</small></a>
    <a class="ud-stat" href="/student/tenancy.php"><span>Approved offers</span><strong><?= dashboard_number($inquiries['approved'] ?? 0) ?></strong><small>Awaiting your confirmation</small></a>
    <a class="ud-stat" href="/student/tenancy.php"><span>Active tenancies</span><strong><?= dashboard_number($tenancies['active'] ?? 0) ?></strong><small>Confirmed boarding agreements</small></a>
    <a class="ud-stat" href="/student/payments.php"><span>Outstanding rent</span><strong class="ud-money"><?= $rentReady ? module_escape(dashboard_currency($rent['outstanding'] ?? 0)) : 'Unavailable' ?></strong><small>Owner-entered payment records</small></a>
</div>

<div class="ud-two-col">
    <section class="panel ud-panel">
        <div class="ud-section-head"><div><p class="ud-eyebrow">YOUR UPDATES</p><h2>Activity alerts <?= $noticeCount > 0 ? '<span class="ud-alert-count">' . $noticeCount . '</span>' : '' ?></h2></div><span class="ud-subtle">Updated on page load</span></div>
        <div class="ud-alert-list">
            <?php if (dashboard_number($inquiries['approved'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/student/tenancy.php"><span class="ud-alert-symbol ud-blue">✓</span><span><strong><?= dashboard_number($inquiries['approved']) ?> boarding offer(s) approved</strong><small>Review and confirm if you'd like to move in</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($rentReady && dashboard_number($rent['overdue'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/student/payments.php"><span class="ud-alert-symbol ud-red">!</span><span><strong><?= dashboard_number($rent['overdue']) ?> overdue rent bill(s)</strong><small>Review the amounts recorded by your owner</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($rentReady && dashboard_number($rent['due_soon'] ?? 0) > 0): ?>
                <a class="ud-alert" href="/student/payments.php"><span class="ud-alert-symbol ud-blue">⌁</span><span><strong><?= dashboard_number($rent['due_soon']) ?> bill(s) due in the next 7 days</strong><small>Check due dates and balances</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if (dashboard_number($inquiries['total'] ?? 0) === 0): ?>
                <a class="ud-alert" href="/student/listings.php"><span class="ud-alert-symbol ud-blue">+</span><span><strong>Start exploring boardings</strong><small>Send an inquiry when you find a suitable place</small></span><span aria-hidden="true">›</span></a>
            <?php endif; ?>
            <?php if ($noticeCount === 0 && dashboard_number($inquiries['total'] ?? 0) > 0 && (!$rentReady || dashboard_number($rent['due_soon'] ?? 0) === 0)): ?>
                <p class="ud-empty">Nothing urgent right now. New offers and rent alerts appear here.</p>
            <?php endif; ?>
            <?php if (!$rentReady): ?><p class="ud-warning">Payment summary unavailable. Confirm that the `RentPayment` migration has been applied.</p><?php endif; ?>
        </div>
    </section>
    <section class="panel ud-panel">
        <div class="ud-section-head"><div><p class="ud-eyebrow">YOUR BOARDING</p><h2>Active tenancy</h2></div><a href="/student/tenancy.php">View details</a></div>
        <?php if (!$activeHomes): ?><p class="ud-empty">No active tenancy yet. Your student account stays active even before you move in.</p><a class="btn secondary" href="/student/listings.php">Explore boardings</a>
        <?php else: ?><div class="ud-item-list"><?php foreach ($activeHomes as $home): ?>
            <div class="ud-home"><strong><?= module_escape(dashboard_title($home['listing_title'], (int)$home['listing_id'])) ?></strong><small><?= module_escape((string)($home['address'] ?: ($home['university_name'] ?: 'University boarding'))) ?><?= $home['city'] ? ' · ' . module_escape((string)$home['city']) : '' ?></small><p>Agreed rent: <strong><?= module_escape(dashboard_currency($home['agreed_rent'])) ?></strong> / month</p><span class="ud-badge ud-confirmed">Active since <?= module_escape((string)$home['start_date']) ?></span></div>
        <?php endforeach; ?></div><?php endif; ?>
    </section>
</div>

<div class="ud-two-col">
    <section class="panel ud-panel"><div class="ud-section-head"><h2>Latest inquiries</h2><a href="/student/inquiries.php">View all</a></div>
        <?php if (!$recentInquiries): ?><p class="ud-empty">You haven't contacted any boarding owners yet.</p>
        <?php else: ?><div class="ud-item-list"><?php foreach ($recentInquiries as $item): ?>
            <div class="ud-item"><div><strong><?= module_escape(dashboard_title($item['listing_title'], (int)$item['listing_id'])) ?></strong><small>Inquiry #<?= (int)$item['inquiry_id'] ?></small></div><?= dashboard_badge((string)$item['status']) ?></div>
        <?php endforeach; ?></div><?php endif; ?>
    </section>
    <section class="panel ud-panel"><div class="ud-section-head"><h2>Unpaid bills</h2><a href="/student/payments.php">Payment history</a></div>
        <?php if (!$rentReady): ?><p class="ud-warning">Rent records unavailable.</p>
        <?php elseif (!$upcomingBills): ?><p class="ud-empty">No outstanding monthly bills.</p>
        <?php else: ?><div class="ud-item-list"><?php foreach ($upcomingBills as $bill): ?>
            <div class="ud-item"><div><strong><?= module_escape(dashboard_title($bill['listing_title'], (int)$bill['listing_id'])) ?></strong><small><?= module_escape(substr((string)$bill['billing_month'], 0, 7)) ?> · Due <?= module_escape((string)$bill['due_date']) ?></small></div><strong class="ud-balance"><?= module_escape(dashboard_currency(max(0, (float)$bill['amount_due'] - (float)$bill['amount_paid']))) ?></strong></div>
        <?php endforeach; ?></div><?php endif; ?>
    </section>
</div>
<?php module_footer(); ?>
