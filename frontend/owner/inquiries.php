<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/includes/unistay_module.php';
$ownerId = module_require('owner');
$pdo = db();
$path = '/owner/inquiries.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    module_check_csrf();
    try {
        $id = module_positive_int($_POST['inquiry_id'] ?? null, 'inquiry ID');
        $action = (string)($_POST['action'] ?? '');
        if (!in_array($action, ['approve', 'reject'], true)) throw new InvalidArgumentException('Invalid decision.');
        $pdo->beginTransaction();
        $q = $pdo->prepare('SELECT i.status FROM `Inquiry` i INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id WHERE i.inquiry_id = ? AND b.owner_id = ? FOR UPDATE');
        $q->execute([$id, $ownerId]);
        $status = $q->fetchColumn();
        if ($status !== 'pending') throw new InvalidArgumentException('This inquiry is missing or no longer pending.');
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $q = $pdo->prepare('UPDATE `Inquiry` SET status = ? WHERE inquiry_id = ? AND status = ?');
        $q->execute([$newStatus, $id, 'pending']);
        if ($q->rowCount() !== 1) throw new RuntimeException('Inquiry changed during processing.');
        $pdo->commit();
        module_flash('Inquiry #' . $id . ' ' . $newStatus . '.');
    } catch (InvalidArgumentException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_flash($e->getMessage(), true);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        module_fail($e, $path);
    }
    module_redirect($path);
}

$q = $pdo->prepare('SELECT i.inquiry_id, i.listing_id, i.student_user_id, i.message, i.status,
    b.title AS listing_title, b.rent_amount, b.capacity,
    u.full_name, u.email,
    (SELECT COUNT(*) FROM `Tenancy` t WHERE t.listing_id = b.listing_id AND t.status = \'active\') AS occupied
    FROM `Inquiry` i
    INNER JOIN `BoardingListing` b ON b.listing_id = i.listing_id
    LEFT JOIN `User` u ON u.user_id = i.student_user_id
    WHERE b.owner_id = ? ORDER BY i.inquiry_id DESC');
$q->execute([$ownerId]);
$allInquiries = $q->fetchAll();
$counts = ['all' => count($allInquiries), 'pending' => 0, 'approved' => 0, 'confirmed' => 0, 'rejected' => 0, 'withdrawn' => 0];
foreach ($allInquiries as $inquiry) {
    if (isset($counts[$inquiry['status']])) $counts[$inquiry['status']]++;
}
$filter = (string)($_GET['status'] ?? 'all');
if (!array_key_exists($filter, $counts)) $filter = 'all';
$filtered = $filter === 'all'
    ? $allInquiries
    : array_values(array_filter($allInquiries, static fn($row) => $row['status'] === $filter));
module_header('Student Inquiries', 'owner');
?>
<p class="muted">Review student messages and offer places directly. <strong>Approval is not a confirmed reservation</strong>—the student must accept it, and capacity is checked at confirmation.</p>
<div class="inquiry-stats">
  <div><span class="muted">All inquiries</span><strong><?= $counts['all'] ?></strong></div>
  <div><span class="muted">Awaiting response</span><strong><?= $counts['pending'] ?></strong></div>
  <div><span class="muted">Offers sent</span><strong><?= $counts['approved'] ?></strong></div>
  <div><span class="muted">Confirmed</span><strong><?= $counts['confirmed'] ?></strong></div>
</div>
<div class="status-filters" aria-label="Filter inquiries">
<?php foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $value => $label): ?>
  <a href="/owner/inquiries.php?status=<?= $value ?>" class="status-pill <?= $filter === $value ? 'active' : '' ?>"><?= $label ?> <span><?= $counts[$value] ?></span></a>
<?php endforeach; ?>
</div>
<div class="inquiry-card-list">
<?php foreach ($filtered as $item): ?>
  <article class="panel inquiry-card">
    <div class="inquiry-card-top">
      <div>
        <span class="muted small">Inquiry #<?= (int)$item['inquiry_id'] ?> · <?= module_escape((string)($item['listing_title'] ?: ('Boarding #' . $item['listing_id']))) ?></span>
        <h2><?= module_escape((string)($item['full_name'] ?: 'Student')) ?></h2>
        <p class="muted small"><?= module_escape((string)($item['email'] ?? 'Email not available')) ?></p>
      </div>
      <span class="status-tag status-<?= module_escape((string)$item['status']) ?>"><?= module_escape(ucfirst((string)$item['status'])) ?></span>
    </div>
    <p class="inquiry-message"><?= nl2br(module_escape((string)$item['message'])) ?></p>
    <div class="inquiry-card-bottom">
      <div class="muted small">LKR <?= module_escape((string)$item['rent_amount']) ?> / month · <?= max(0, (int)$item['capacity'] - (int)$item['occupied']) ?> place(s) currently remaining</div>
      <?php if ($item['status'] === 'pending'): ?>
        <div class="actions">
          <form method="post" class="inline" action="/owner/inquiries.php">
            <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
            <input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit">Approve offer</button>
          </form>
          <form method="post" class="inline" action="/owner/inquiries.php" onsubmit="return confirm('Reject this inquiry?');">
            <input type="hidden" name="csrf_token" value="<?= module_escape(module_csrf_token()) ?>">
            <input type="hidden" name="inquiry_id" value="<?= (int)$item['inquiry_id'] ?>">
            <input type="hidden" name="action" value="reject">
            <button class="danger" type="submit">Reject</button>
          </form>
        </div>
      <?php elseif ($item['status'] === 'approved'): ?>
        <span class="muted small">Waiting for the student to confirm</span>
      <?php elseif ($item['status'] === 'confirmed'): ?>
        <a class="btn secondary" href="/owner/tenants.php">View tenancy</a>
      <?php else: ?><span class="muted small">No further action required</span><?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
<?php if (!$filtered): ?><div class="panel muted">No inquiries match this filter.</div><?php endif; ?>
</div>
<?php module_footer(); ?>
