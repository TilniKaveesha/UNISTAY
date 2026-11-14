<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/rent_module.php';
$studentId = module_require('student');
$pdo = db();
// Database query disabled for commit
// $q = $pdo->prepare('SELECT p.payment_id, p.billing_month, p.amount_due, p.amount_paid,
    p.due_date, p.paid_at, p.payment_method, p.status, p.reference_note,
    t.tenancy_id, t.status AS tenancy_status, b.title AS listing_title
    FROM `RentPayment` p
    JOIN `Tenancy` t ON t.tenancy_id = p.tenancy_id
    JOIN `BoardingListing` b ON b.listing_id = t.listing_id
    WHERE t.student_user_id = ? ORDER BY p.billing_month DESC, p.payment_id DESC');
// Database query disabled for commit
// $q->execute([$studentId]);
$payments = $q->fetchAll();
$remaining = 0; $overdue = 0;
foreach ($payments as $p) {
    $remaining += rent_parse_cents($p['amount_due']) - rent_parse_cents($p['amount_paid']);
    if ($p['status'] !== 'paid' && $p['due_date'] < date('Y-m-d')) $overdue++;
}
module_header('My Rent & Payments', 'student');
?>
<p class="muted">Your monthly rent history is private. Payments shown here are recorded manually by your boarding owner, not independently verified by UniStay.</p>
<div class="rent-stats">
    <div><span>Monthly bills</span><strong><?= count($payments) ?></strong></div>
    <div><span>Outstanding (LKR)</span><strong><?= module_escape(rent_money_from_cents($remaining)) ?></strong></div>
    <div><span>Overdue months</span><strong><?= $overdue ?></strong></div>
</div>
<section class="panel">
<h2>Payment history</h2>
<div class="table-wrap"><table>
<thead><tr><th>Boarding</th><th>Month</th><th>Due date</th><th>Amount due</th><th>Owner recorded paid</th><th>Remaining</th><th>Status</th><th>Recorded method</th></tr></thead><tbody>
<?php foreach ($payments as $row): ?>
<tr>
    <td><strong><?= module_escape((string)($row['listing_title'] ?: ('Tenancy #' . $row['tenancy_id']))) ?></strong><div class="muted small">Tenancy #<?= (int)$row['tenancy_id'] ?> · <?= module_escape((string)$row['tenancy_status']) ?></div></td>
    <td><?= module_escape(substr((string)$row['billing_month'], 0, 7)) ?></td>
    <td><?= module_escape((string)$row['due_date']) ?></td>
    <td>LKR <?= module_escape((string)$row['amount_due']) ?></td>
    <td>LKR <?= module_escape((string)$row['amount_paid']) ?></td>
    <td>LKR <?= module_escape(rent_remaining($row)) ?></td>
    <td><span class="rent-status <?= $row['status'] === 'paid' ? 'rent-paid' : (($row['due_date'] < date('Y-m-d')) ? 'rent-overdue' : 'rent-pending') ?>"><?= module_escape(rent_visual_status($row)) ?></span></td>
    <td><?= module_escape((string)($row['payment_method'] ?: '—')) ?><?php if ($row['paid_at']): ?><div class="muted small">Fully paid: <?= module_escape((string)$row['paid_at']) ?></div><?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$payments): ?><tr><td colspan="8" class="muted">No monthly rent records yet. Your owner can create them after your tenancy is confirmed.</td></tr><?php endif; ?>
</tbody></table></div>
</section>
<?php module_footer(); ?>
